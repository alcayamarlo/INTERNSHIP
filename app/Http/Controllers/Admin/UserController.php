<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Support\StudentIdFormat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function index(Request $request)
    {
        $view = in_array($request->query('view'), ['archived', 'suspended'], true) ? $request->query('view') : 'active';
        $query = User::with(['student', 'employer'])
            ->when($view === 'archived', fn ($query) => $query->whereNotNull('archived_at'))
            ->when($view === 'active', fn ($query) => $query->whereNull('archived_at'))
            ->when($view === 'suspended', fn ($query) => $query->whereNull('archived_at')->whereNotNull('suspended_at')->where(fn ($active) => $active->whereNull('suspended_until')->orWhere('suspended_until', '>', now())));

        if ($role = $request->get('role')) {
            $query->where('role', $role);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return view('admin.users.index', [
            'users' => $query->latest()->paginate(10)->withQueryString(),
            'view' => $view,
            'roles' => UserRole::all(),
            'assignableRoles' => UserRole::assignable(),
            'studentIdPattern' => StudentIdFormat::pattern(),
        ]);
    }

    public function create()
    {
        return view('admin.users.create', [
            'institutions' => Institution::query()->whereNotNull('verified_at')->orderBy('name')->get(),
            'roles' => UserRole::assignable(),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => $validated['role'],
                'phone' => $validated['phone'] ?? null,
            ]);
            // Admin-created accounts never receive a verification code, so
            // leaving them unverified locks the user out at the verify page.
            $user->forceFill(['email_verified_at' => now()])->save();

            if ($validated['role'] === UserRole::Employer->value) {
                Employer::create([
                    'user_id' => $user->id,
                    'company_name' => $validated['company_name'],
                    'contact_person' => $validated['name'],
                ]);
            } elseif ($validated['role'] === UserRole::Coordinator->value) {
                Coordinator::create([
                    'user_id' => $user->id,
                    'institution_id' => $validated['institution_id'],
                ]);
            } else {
                $student = Student::create([
                    'user_id' => $user->id,
                    'institution_id' => $validated['institution_id'],
                    'student_id_number' => mb_strtoupper(trim($validated['student_id_number'])),
                    'student_id_locked' => true,
                ]);
                $student->calculateProfileCompletion();
            }

            $this->activityLog->log(Auth::user(), 'admin_create_user', ['user_id' => $user->id]);

            return $user;
        });

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $validated = $request->validated();

        $actor = Auth::user();
        $newRole = UserRole::from($validated['role']);

        if ($actor->is($user) && $user->role !== $newRole) {
            throw ValidationException::withMessages([
                'role' => 'You cannot change your own administrator role.',
            ]);
        }

        DB::transaction(function () use ($user, $validated, $newRole, $actor) {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $previousRole = $user->role;
            $removingAdminAccess = $previousRole === UserRole::Admin
                && ($newRole !== UserRole::Admin || ! (bool) $validated['is_active']);

            if ($removingAdminAccess) {
                $activeAdmins = User::query()
                    ->where('role', UserRole::Admin->value)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->get();

                if ($activeAdmins->count() <= 1) {
                    throw ValidationException::withMessages([
                        'role' => 'The last active administrator cannot be removed or deactivated.',
                        'is_active' => 'The last active administrator cannot be removed or deactivated.',
                    ]);
                }
            }

            $user->fill(collect($validated)->except(['password', 'student_id_number'])->all());

            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            if ($previousRole !== $newRole) {
                $this->ensureRoleProfile($user, $newRole);
            }

            if ($newRole === UserRole::Student && ! empty($validated['student_id_number'])) {
                $studentIdNumber = mb_strtoupper(trim($validated['student_id_number']));
                $student = $user->student()->firstOrCreate([], ['profile_completion' => 0]);
                if ($student->student_id_number !== $studentIdNumber || ! $student->student_id_locked) {
                    $student->update([
                        'student_id_number' => $studentIdNumber,
                        'student_id_locked' => true,
                    ]);
                    $student->calculateProfileCompletion();
                }
            }

            $this->activityLog->log($actor, 'admin_update_user', ['user_id' => $user->id]);
            if ($previousRole !== $newRole) {
                $this->activityLog->log($actor, 'role_changed', ['user_id' => $user->id, 'from' => $previousRole?->value, 'to' => $newRole->value]);
            }
            if ($user->wasChanged('is_active')) {
                $this->activityLog->log($actor, $user->is_active ? 'account_activated' : 'account_deactivated', ['user_id' => $user->id]);
            }
            if (! empty($validated['password'])) {
                $this->activityLog->log($actor, 'admin_password_set', ['user_id' => $user->id]);
            }
        });

        return back()->with('success', 'User updated successfully.');
    }

    /** Blocks sign-in until the end date (or until lifted), showing the reason on the sign-in screen. */
    public function suspend(Request $request, User $user)
    {
        $this->authorize('update', $user);
        $validated = $request->validate([
            'suspension_reason' => ['required', 'string', 'max:1000'],
            'suspended_until' => ['nullable', 'date', 'after:now'],
        ], [
            'suspension_reason.required' => 'Give a reason. The user sees it when they try to sign in.',
            'suspended_until.after' => 'The end date must be in the future.',
        ]);

        try {
            app(\App\Services\AccountSuspension::class)->suspend(
                $user,
                Auth::user(),
                $validated['suspension_reason'],
                isset($validated['suspended_until']) ? \Illuminate\Support\Carbon::parse($validated['suspended_until'], 'Asia/Manila') : null
            );
        } catch (ValidationException $exception) {
            return back()->with('error', collect($exception->errors())->flatten()->first());
        }

        return back()->with('success', "{$user->name} is suspended ".($user->suspended_until ? 'until '.$user->suspended_until->timezone('Asia/Manila')->format('M d, Y h:i A') : 'until you lift it').'.');
    }

    public function liftSuspension(User $user)
    {
        $this->authorize('update', $user);
        app(\App\Services\AccountSuspension::class)->lift($user, Auth::user());

        return back()->with('success', "{$user->name}'s suspension was lifted. They can sign in again.");
    }

    public function destroy(User $user)
    {
        return $this->archive($user);
    }

    public function archive(User $user)
    {
        $this->authorize('archive', $user);

        $actor = Auth::user();
        DB::transaction(function () use ($user, $actor) {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($user->role === UserRole::Admin && $user->is_active) {
                $activeAdmins = User::query()
                    ->where('role', UserRole::Admin->value)
                    ->where('is_active', true)
                    ->whereNull('archived_at')
                    ->lockForUpdate()
                    ->get();

                if ($activeAdmins->count() <= 1) {
                    throw ValidationException::withMessages([
                        'role' => 'The last active administrator cannot be archived.',
                    ]);
                }
            }

            $user->update(['archived_at' => now()]);
            $this->activityLog->log($actor, 'admin_archive_user', ['user_id' => $user->id]);
        });

        return back()->with('success', 'User archived successfully.');
    }

    public function verifyEmail(User $user)
    {
        $this->authorize('update', $user);

        if ($user->email === null) {
            return back()->with('error', "{$user->name} has not added an email yet. They add and verify it after their first sign-in.");
        }

        if (! $user->hasVerifiedEmail()) {
            $user->forceFill([
                'email_verified_at' => now(),
                'verification_code' => null,
                'verification_code_expires_at' => null,
                'verification_attempts' => 0,
            ])->save();
            $this->activityLog->log(Auth::user(), 'admin_verify_user_email', ['user_id' => $user->id]);
        }

        return back()->with('success', "{$user->name}'s email is now verified and they can sign in.");
    }

    /** Marks a company as verified, unlocking posting, browsing students, and invitations. */
    public function verifyCompany(User $user)
    {
        $this->authorize('update', $user);
        $employer = $user->employer;
        abort_unless($employer, 404);

        if (! $employer->isVerified()) {
            $employer->update(['verified_at' => now(), 'verified_by_user_id' => Auth::id()]);
            $this->activityLog->log(Auth::user(), 'admin_verify_company', ['employer_id' => $employer->id]);
            app(\App\Services\NotificationService::class)->send(
                $user,
                'company_verified',
                'Your company is verified',
                "{$employer->company_name} is now verified. You can post internships, browse students, and invite them to apply."
            );
        }

        return back()->with('success', "{$employer->company_name} is now a verified company.");
    }

    public function unverifyCompany(User $user)
    {
        $this->authorize('update', $user);
        $employer = $user->employer;
        abort_unless($employer, 404);

        $employer->update(['verified_at' => null, 'verified_by_user_id' => null]);
        $this->activityLog->log(Auth::user(), 'admin_unverify_company', ['employer_id' => $employer->id]);

        return back()->with('success', "{$employer->company_name} is no longer verified.");
    }

    public function restore(User $user)
    {
        $this->authorize('restore', $user);

        $user->update(['archived_at' => null]);
        $this->activityLog->log(Auth::user(), 'admin_restore_user', ['user_id' => $user->id]);

        return back()->with('success', 'User restored successfully.');
    }

    public function forceDelete(User $user)
    {
        $this->authorize('forceDelete', $user);

        $actor = Auth::user();
        DB::transaction(function () use ($user, $actor): void {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_unless($user->archived_at, 404);
            $userId = $user->id;
            $user->forceDelete();
            $this->activityLog->log($actor, 'admin_permanently_delete_user', ['user_id' => $userId]);
        });

        return back()->with('success', 'Archived user permanently deleted.');
    }

    private function ensureRoleProfile(User $user, UserRole $role): void
    {
        if ($role === UserRole::Student) {
            $student = Student::firstOrCreate(
                ['user_id' => $user->id],
                ['profile_completion' => 0],
            );
            $student->calculateProfileCompletion();

            return;
        }

        match ($role) {
            UserRole::Employer => Employer::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'company_name' => $user->name,
                    'contact_person' => $user->name,
                ],
            ),
            UserRole::Coordinator => Coordinator::firstOrCreate(
                ['user_id' => $user->id],
            ),
            UserRole::Admin => null,
        };
    }
}

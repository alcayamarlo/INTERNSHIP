<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Support\StudentIdFormat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function index(Request $request)
    {
        $view = $request->query('view') === 'suspended' ? 'suspended' : 'all';
        $query = User::with(['student', 'employer', 'coordinator.program', 'supervisor.employer'])
            ->when($view === 'suspended', fn ($query) => $query->whereNotNull('suspended_at')->where(fn ($active) => $active->whereNull('suspended_until')->orWhere('suspended_until', '>', now())));

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
            // For the edit modal: a coordinator may keep their department or move to a free one.
            'departments' => Program::withCount('coordinators')->orderBy('code')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.users.create', [
            'roles' => UserRole::assignable(),
            // Only departments that still need a coordinator.
            'departments' => Program::query()->acceptingCoordinators()->orderBy('code')->get(),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        $user = $this->guardDepartment(fn () => DB::transaction(function () use ($validated) {
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
                $this->assertDepartmentHasRoom((int) $validated['program_id']);
                Coordinator::create([
                    'user_id' => $user->id,
                    'institution_id' => Institution::primary()->id, // fixed: the school
                    'program_id' => $validated['program_id'],
                ]);
            } else {
                $student = Student::create([
                    'user_id' => $user->id,
                    'institution_id' => Institution::primary()->id, // fixed: the school
                    'student_id_number' => mb_strtoupper(trim($validated['student_id_number'])),
                    'student_id_locked' => true,
                ]);
                $student->calculateProfileCompletion();
            }

            $this->activityLog->log(Auth::user(), 'admin_create_user', ['user_id' => $user->id]);

            return $user;
        }));

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $validated = $request->validated();

        $actor = Auth::user();
        $newRole = UserRole::from($validated['role']);

        // A supervisor is created by the company's HR account (with the company link), not through this form.
        if (($newRole === UserRole::Supervisor) !== ($user->role === UserRole::Supervisor)) {
            throw ValidationException::withMessages([
                'role' => 'Supervisors are created by their company\'s HR account. To stop one from signing in, set the status to inactive.',
            ]);
        }

        if ($actor->is($user) && $user->role !== $newRole) {
            throw ValidationException::withMessages([
                'role' => 'You cannot change your own administrator role.',
            ]);
        }

        $this->guardDepartment(fn () => DB::transaction(function () use ($user, $validated, $newRole, $actor) {
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

            $user->fill(collect($validated)->except(['password', 'student_id_number', 'program_id'])->all());

            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            if ($previousRole !== $newRole) {
                $this->ensureRoleProfile($user, $newRole);
            }

            if ($newRole === UserRole::Coordinator) {
                $coordinator = $user->coordinator()->firstOrCreate([]);
                if ((int) $coordinator->program_id !== (int) $validated['program_id']) {
                    $this->assertDepartmentHasRoom((int) $validated['program_id'], $coordinator->id);
                    $coordinator->update(['program_id' => $validated['program_id']]);
                    $this->activityLog->log($actor, 'coordinator_department_assigned', ['user_id' => $user->id, 'program_id' => (int) $validated['program_id']]);
                }
            } elseif ($previousRole === UserRole::Coordinator && $user->coordinator?->program_id) {
                // No longer a coordinator: the department is free for someone else.
                $user->coordinator->update(['program_id' => null]);
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
        }));

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

    /** Ends a lock after too many wrong passwords (App\Services\LoginLockout). */
    public function unlock(User $user)
    {
        $this->authorize('update', $user);
        app(\App\Services\LoginLockout::class)->unlock($user, Auth::user());

        return back()->with('success', "{$user->name} is unlocked and can sign in now.");
    }

    public function liftSuspension(User $user)
    {
        $this->authorize('update', $user);
        app(\App\Services\AccountSuspension::class)->lift($user, Auth::user());

        return back()->with('success', "{$user->name}'s suspension was lifted. They can sign in again.");
    }

    public function destroy(User $user)
    {
        return $this->deleteNow($user);
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

    /**
     * Delete from the Active list, in one step. Refused (with the reasons) for an account that has OJT records,
     * for your own account and for the last active administrator; set those to Inactive instead.
     */
    public function deleteNow(User $user)
    {
        $this->authorize('deleteNow', $user);

        $actor = Auth::user();
        $blocked = DB::transaction(function () use ($user, $actor): ?string {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($user->role === UserRole::Admin && $user->is_active && $user->archived_at === null) {
                $activeAdmins = User::query()
                    ->where('role', UserRole::Admin->value)
                    ->where('is_active', true)
                    ->whereNull('archived_at')
                    ->lockForUpdate()
                    ->count();

                if ($activeAdmins <= 1) {
                    return 'The last active administrator cannot be deleted.';
                }
            }

            $blockers = $user->deletionBlockers();
            if ($blockers !== []) {
                return "{$user->name} cannot be deleted because the account has ".implode(', ', $blockers).'. Set the account to Inactive (Edit) instead.';
            }

            $userId = $user->id;
            $user->forceDelete();
            $this->activityLog->log($actor, 'admin_permanently_delete_user', ['user_id' => $userId]);

            return null;
        });

        return $blocked === null
            ? back()->with('success', 'User permanently deleted.')
            : back()->with('error', $blocked);
    }

    /**
     * A department has at most Coordinator::MAX_PER_DEPARTMENT coordinators. The department row is locked while
     * we count, so two admins adding the 2nd and 3rd coordinator at the same moment cannot both succeed.
     * Call it inside the transaction, right before saving.
     */
    private function assertDepartmentHasRoom(int $programId, ?int $exceptCoordinatorId = null): void
    {
        Program::query()->whereKey($programId)->lockForUpdate()->first();

        if (! Coordinator::departmentHasRoom($programId, $exceptCoordinatorId)) {
            throw ValidationException::withMessages(['program_id' => Coordinator::DEPARTMENT_TAKEN]);
        }
    }

    /** Kept for the callers that wrap the save; the department limit is checked by assertDepartmentHasRoom(). */
    private function guardDepartment(\Closure $save): mixed
    {
        return $save();
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
            // Created by the company's HR account, never here.
            UserRole::Admin, UserRole::Supervisor => null,
        };
    }
}

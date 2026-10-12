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
        $query = User::with('student');

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
            'users' => $query->latest()->paginate(15)->withQueryString(),
            'roles' => UserRole::cases(),
            'studentIdPattern' => StudentIdFormat::pattern(),
        ]);
    }

    public function create()
    {
        return view('admin.users.create', [
            'institutions' => Institution::orderBy('name')->get(),
            'roles' => [UserRole::Employer, UserRole::Coordinator],
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

            if ($validated['role'] === UserRole::Employer->value) {
                Employer::create([
                    'user_id' => $user->id,
                    'company_name' => $validated['company_name'],
                    'contact_person' => $validated['name'],
                ]);
            } else {
                Coordinator::create([
                    'user_id' => $user->id,
                    'institution_id' => $validated['institution_id'],
                ]);
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

            $user->fill(collect($validated)->except('password')->all());

            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            if ($previousRole !== $newRole) {
                $this->ensureRoleProfile($user, $newRole);
            }

            $this->activityLog->log($actor, 'admin_update_user', ['user_id' => $user->id]);
        });

        return back()->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $actor = Auth::user();
        DB::transaction(function () use ($user, $actor) {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($user->role === UserRole::Admin) {
                $activeAdmins = User::query()
                    ->where('role', UserRole::Admin->value)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->get();

                if ($activeAdmins->count() <= 1) {
                    throw ValidationException::withMessages([
                        'role' => 'The last active administrator cannot be removed.',
                    ]);
                }
            }

            $user->delete();
            $this->activityLog->log($actor, 'admin_delete_user', ['user_id' => $user->id]);
        });

        return back()->with('success', 'User deleted successfully.');
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

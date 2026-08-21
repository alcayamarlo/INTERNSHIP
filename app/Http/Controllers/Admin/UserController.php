<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function index(Request $request)
    {
        $query = User::query();

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

            return $user;
        });

        $this->activityLog->log(Auth::user(), 'admin_create_user', ['user_id' => $user->id]);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $validated = $request->validated();

        $user->fill(collect($validated)->except('password')->all());

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        $this->activityLog->log(Auth::user(), 'admin_update_user', ['user_id' => $user->id]);

        return back()->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $userId = $user->id;
        $user->delete();
        $this->activityLog->log(auth()->user(), 'admin_delete_user', ['user_id' => $userId]);

        return back()->with('success', 'User deleted successfully.');
    }
}

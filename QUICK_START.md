# Skill-Bridge - Quick Start Guide

## 🚀 Get Running in 5 Minutes

### 1. Setup Database
```bash
# Run migrations (creates all tables)
php artisan migrate

# Seed demo data (creates test users)
php artisan db:seed
```

### 2. Start Server
```bash
php artisan serve
```

### 3. Login with Demo Accounts
- **Admin**: admin@skillbridge.test / password
- **Employer**: employer@skillbridge.test / password
- **Student**: student@skillbridge.test / password
- **Coordinator**: coordinator@skillbridge.test / password

---

## 🗂️ Project Structure

```
app/
├── Http/
│   ├── Controllers/          # 25+ controllers for each role
│   ├── Middleware/           # RoleMiddleware for authorization
│   └── Requests/             # Form request validation classes
├── Models/                   # 19 Eloquent models
├── Services/                 # Business logic (CompetencyMatching, Notifications, etc)
├── Enums/                    # Type-safe enums (UserRole, ApplicationStatus, etc)

database/
├── migrations/               # 9 tables with relationships
├── seeders/                  # DatabaseSeeder with demo data
├── factories/                # Model factories for testing

resources/views/
├── layouts/                  # app.blade.php (authenticated), guest.blade.php (auth pages)
├── auth/                     # Login, register, password reset
├── student/dashboard.blade.php
├── employer/dashboard.blade.php
├── coordinator/dashboard.blade.php
├── admin/dashboard.blade.php

routes/
└── web.php                   # All routes with role middleware
```

---

## 🔐 Authentication Basics

### Login Flow
```
1. User submits email/password → LoginController@login
2. Credentials validated via Auth::attempt()
3. Account must be active (is_active = 1)
4. Activity logged to system_logs
5. Redirected to role-specific dashboard
```

### Registration Flow
```
1. User selects role (student/employer/coordinator)
2. Form validation via RegisterRequest
3. User + role-specific profile created in transaction
4. Auto-logged in
5. Redirected to role dashboard
```

### Dashboard Routes
```
Student:     /student/dashboard         → student.dashboard
Employer:    /employer/dashboard        → employer.dashboard
Coordinator: /coordinator/dashboard     → coordinator.dashboard
Admin:       /admin/dashboard           → admin.dashboard
```

---

## 📱 Key Models & Relationships

### Users
```php
User::find(1)
  ->student()    // One-to-one
  ->employer()   // One-to-one
  ->coordinator() // One-to-one
```

### Student Dashboard
```php
$student = Auth::user()->student;
$competencies = $student->competencies;        // StudentCompetency
$applications = $student->applications();      // InternshipApplications
$profile_score = $student->profile_completion; // 0-100%
```

### Internships
```php
$internship->employer;          // Belongs to Employer
$internship->requirementsList(); // Requirements
$internship->applications;       // All applications
$internship->status;             // 'open' or 'closed'
```

### Applications
```php
$application->student;      // Belongs to Student
$application->internship;   // Belongs to Internship
$application->status;       // Enum: submitted, reviewed, interview, accepted, rejected
$application->match_percentage; // 0-100
```

---

## 🛡️ Authorization

### Check User Role
```php
if (Auth::user()->isRole(UserRole::Student)) {
    // Student-specific code
}
```

### Route Protection
```php
// routes/web.php
Route::middleware('role:student')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index']);
});

// In controller
abort_unless(Auth::user()->isRole(UserRole::Student), 403);
```

---

## 📊 Common Queries

### Get All Open Internships
```php
Internship::where('status', 'open')->with('employer')->get();
```

### Get Student's Applications with Details
```php
$student->applications()
  ->with(['internship.employer'])
  ->latest('applied_at')
  ->get();
```

### Get Employer's Recent Applicants
```php
InternshipApplication::whereIn(
    'internship_id',
    $employer->internships()->pluck('id')
)->with(['student.user'])->latest()->take(5)->get();
```

### Get Coordinator's Students
```php
Student::where('institution_id', $coordinator->institution_id)
  ->with(['user', 'competencies'])
  ->get();
```

---

## 🧪 Testing

### Login as Admin
```bash
# In browser
http://localhost:8000/login
# Enter: admin@skillbridge.test / password
```

### Create New User
```php
// In tinker: php artisan tinker
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => Hash::make('password'),
    'role' => UserRole::Student,
]);

Student::create(['user_id' => $user->id]);
```

### View Activity Logs
```php
// In admin dashboard or:
SystemLog::latest()->take(20)->get();
```

---

## 🔍 Debugging

### Check User Session
```php
// In controller
dd(Auth::user());  // Shows current authenticated user
dd(Auth::check()); // Boolean if authenticated
```

### Check Role
```php
dd(Auth::user()->role);       // UserRole enum
dd(Auth::user()->role->value); // String: 'student', 'employer', etc
```

### Test Middleware
```php
// Try accessing unauthorized route
http://localhost:8000/admin/dashboard  // If logged in as student → 403
```

---

## 📝 Common Tasks

### Add New Permission to Role
1. Update routes/web.php middleware
2. Add role check in controller: `abort_unless(...)`
3. Test access with that role

### Create New Dashboard Widget
1. Add logic to controller
2. Pass data to view: `view('...', ['data' => $data])`
3. Render in Blade with {{ }}

### Add Activity Logging
```php
use App\Services\ActivityLogService;

$this->activityLog->log($user, 'action_name', ['details' => 'value']);
```

### Send Notification
```php
use App\Services\NotificationService;

$notificationService->notify(
    $user,
    'application_status_changed',
    'Application Update',
    'Your application status changed to accepted'
);
```

---

## ⚠️ Important Files

**Routes**: `routes/web.php` - All application routes and role middleware
**Main Layout**: `resources/views/layouts/app.blade.php` - Authenticated pages layout
**Auth Layout**: `resources/views/layouts/guest.blade.php` - Login/register layout
**User Model**: `app/Models/User.php` - Core user model with relationships
**Middleware**: `app/Http/Middleware/RoleMiddleware.php` - Role-based authorization
**Controllers**: `app/Http/Controllers/Auth/` - Authentication controllers

---

## 📞 Support

### Check Documentation
- Phase 2 & 3 Details: `PHASE_2_3_IMPLEMENTATION.md`
- Architecture: `ARCHITECTURE.md`

### Debug Issues
1. Check `storage/logs/laravel.log`
2. Verify database migrations ran: `php artisan migrate:status`
3. Check user is active: `User::find(1)->is_active`
4. Verify role middleware: `RoleMiddleware.php`

---

## 🎯 Next Steps

1. **Profile Management** - Allow students to update their profiles
2. **Competency Management** - CRUD operations for student competencies
3. **Internship Posting** - Employers create/edit internships
4. **Smart Matching** - CompetencyMatchingService implementation
5. **Application Tracking** - Status updates and notifications
6. **Report Generation** - Export reports for coordinators

---

**Last Updated**: June 30, 2026
**Phase**: Phase 2 & 3 Complete

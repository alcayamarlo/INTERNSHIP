# Skill-Bridge - Laravel Artisan Commands Reference

## 🚀 Initial Setup Commands

### Database Setup
```bash
# Create database (manually in MySQL or use terminal)
mysql -u root -p
CREATE DATABASE skillbridge;
EXIT;

# Configure .env
cp .env.example .env
php artisan key:generate

# Edit .env file with database credentials:
# DB_HOST=127.0.0.1
# DB_DATABASE=skillbridge
# DB_USERNAME=root
# DB_PASSWORD=
```

### Running Migrations
```bash
# Run all pending migrations
php artisan migrate

# Show migration status
php artisan migrate:status

# Rollback all migrations
php artisan migrate:reset

# Rollback and re-run
php artisan migrate:refresh

# Rollback and re-run with seeds
php artisan migrate:fresh --seed

# Rollback to specific migration
php artisan migrate:rollback --step=3
```

### Seeding Database
```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=DatabaseSeeder

# Seed after fresh migration
php artisan migrate:fresh --seed
```

---

## 🏃 Running the Application

### Development Server
```bash
# Start Laravel development server
php artisan serve

# Specify host and port
php artisan serve --host=127.0.0.1 --port=8000

# Tinker shell (interactive PHP)
php artisan tinker
```

### Asset Building
```bash
# Build assets for development
npm run dev

# Build assets for production
npm run build

# Watch assets for changes
npm run watch
```

---

## 🔧 Cache Management

### Clear Cache
```bash
# Clear all caches
php artisan cache:clear

# Clear route cache
php artisan route:clear

# Clear config cache
php artisan config:clear

# Clear view cache
php artisan view:clear
```

### Build Cache
```bash
# Cache routes
php artisan route:cache

# Cache configuration
php artisan config:cache

# Cache views
php artisan view:cache
```

---

## 🗄️ Database Commands

### Query Builder & Testing
```bash
# Open interactive shell
php artisan tinker

# Examples in tinker:
>>> User::all()
>>> User::find(1)
>>> User::where('role', 'student')->get()
>>> Auth::check()
>>> Auth::user()
>>> DB::table('users')->get()
>>> SystemLog::latest()->take(10)->get()
```

### Database Inspections
```bash
# List all tables
SHOW TABLES;

# Describe table structure
DESC users;
DESCRIBE students;

# Count records
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM students;

# View migrations
SELECT * FROM migrations;
```

---

## 👥 User Management (Artisan)

### Create Users
```bash
# In tinker:
php artisan tinker

# Create admin user
>>> use App\Models\User;
>>> use App\Enums\UserRole;
>>> use Illuminate\Support\Facades\Hash;
>>> User::create([
    'name' => 'New Admin',
    'email' => 'newadmin@example.com',
    'password' => Hash::make('password'),
    'role' => UserRole::Administrator,
]);

# Create student user
>>> use App\Models\Student;
>>> $user = User::create([
    'name' => 'New Student',
    'email' => 'newstudent@example.com',
    'password' => Hash::make('password'),
    'role' => UserRole::Student,
]);
>>> Student::create(['user_id' => $user->id, 'institution_id' => 1]);
```

### Update Users
```bash
# In tinker:
>>> $user = User::find(1);
>>> $user->update(['name' => 'Updated Name']);
>>> $user->is_active = false;
>>> $user->save();
```

### Delete Users
```bash
# In tinker:
>>> User::find(1)->delete();
>>> User::where('email', 'test@example.com')->delete();
```

---

## 🔐 Authentication Testing

### Test Login Flow
```bash
# In tinker:
>>> Auth::attempt(['email' => 'admin@skillbridge.test', 'password' => 'password'])
true

>>> Auth::check()
true

>>> Auth::user()
// Returns user object

>>> Auth::logout()
>>> Auth::check()
false
```

### Activity Log Checking
```bash
# View recent logins
SELECT * FROM system_logs WHERE action = 'login' ORDER BY created_at DESC LIMIT 5;

# View all activities
SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 20;

# View specific user activities
SELECT * FROM system_logs WHERE user_id = 1 ORDER BY created_at DESC;
```

---

## 🧹 Maintenance Commands

### Application Status
```bash
# Show application info
php artisan about

# Check for issues
php artisan diagnose
```

### Log Management
```bash
# Clear logs
rm storage/logs/laravel.log

# View recent logs
tail -f storage/logs/laravel.log

# Watch logs in real-time
tail -f storage/logs/laravel.log | grep -i error
```

### Storage
```bash
# Create storage symlink
php artisan storage:link

# Clear storage
php artisan storage:clear

# Optimize application
php artisan optimize
```

---

## 🚀 Production Deployment

### Pre-Deployment Checklist
```bash
# 1. Install dependencies
composer install --no-dev

# 2. Build assets
npm run build

# 3. Cache config
php artisan config:cache

# 4. Cache routes
php artisan route:cache

# 5. Create storage link
php artisan storage:link

# 6. Run migrations (on production server)
php artisan migrate --force

# 7. Verify
php artisan about
```

### Environment Setup
```bash
# In .env:
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

---

## 🧪 Testing Commands

### Run Tests
```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/AuthTest.php

# Run with coverage
php artisan test --coverage

# Run verbose
php artisan test --verbose
```

### Generate Test Files
```bash
# Generate feature test
php artisan make:test AuthTest

# Generate unit test
php artisan make:test AuthTest --unit

# Generate with specific features
php artisan make:test LoginTest --features
```

---

## 📊 Monitoring & Debugging

### Query Debugging
```bash
# Enable query logging
>>> DB::enableQueryLog();
>>> User::all();
>>> dd(DB::getQueryLog());

# Or in code:
\DB::listen(function ($query) {
    dd($query->sql, $query->bindings);
});
```

### Email Testing
```bash
# In .env use:
MAIL_DRIVER=log

# Emails will be logged to storage/logs/laravel.log
tail -f storage/logs/laravel.log
```

---

## 🛠️ Tinker Useful Commands

### Interactive Shell
```bash
# Start tinker
php artisan tinker

# Inside tinker:
>>> help                    # Show help
>>> exit or quit           # Exit tinker
>>> clear                  # Clear screen
>>> history               # Show command history

# Run commands:
>>> Artisan::call('migrate')
>>> Cache::put('key', 'value', 60)
>>> Mail::raw('Test', function ($msg) => $msg->to('test@example.com'))
```

### Debugging in Tinker
```bash
>>> dd(variable)           # Dump and die
>>> var_dump(variable)     # Var dump
>>> dump(variable)         # Dump
>>> print_r(variable)      # Print array
```

---

## 🔍 Common Queries

### User Queries
```bash
# Get all users
>>> User::all()

# Get users by role
>>> User::where('role', 'student')->get()

# Get user with relationships
>>> User::with('student', 'employer', 'coordinator')->find(1)

# Count users by role
>>> User::where('role', 'student')->count()
```

### Student Queries
```bash
# Get all students
>>> Student::all()

# Get students from institution
>>> Student::where('institution_id', 1)->get()

# Get student with competencies
>>> Student::with('competencies')->find(1)

# Get student profile completion
>>> Student::find(1)->profile_completion
```

### Application Queries
```bash
# Get all applications
>>> InternshipApplication::all()

# Get applications by status
>>> InternshipApplication::where('status', 'submitted')->get()

# Get applications for student
>>> InternshipApplication::where('student_id', 1)->get()

# Get applications for internship
>>> InternshipApplication::where('internship_id', 1)->get()
```

---

## ⚡ Quick Commands Summary

```bash
# Setup
php artisan migrate:fresh --seed

# Development
php artisan serve

# Assets
npm run dev

# Testing
php artisan test

# Production
php artisan optimize
php artisan config:cache
php artisan route:cache

# Debug
php artisan tinker

# Maintenance
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan config:clear
```

---

## 📚 Resources

### Official Documentation
- [Laravel Documentation](https://laravel.com/docs)
- [Artisan Console](https://laravel.com/docs/artisan)
- [Database](https://laravel.com/docs/database)
- [Eloquent ORM](https://laravel.com/docs/eloquent)

### Common Issues & Solutions

**Issue**: "SQLSTATE[HY000] [2002] No such file or directory"
```bash
# Solution: Check .env database configuration
# Or use 127.0.0.1 instead of localhost
```

**Issue**: "Class not found" in tinker
```bash
# Solution: Use full namespace or add use statement
>>> use App\Models\User;
```

**Issue**: "Migration table not found"
```bash
# Solution: Run migrations first
php artisan migrate
```

**Issue**: "Permission denied" on storage
```bash
# Solution: Fix permissions
chmod -R 775 storage bootstrap/cache
```

---

## 🎯 Workflow Examples

### Complete Setup from Scratch
```bash
# 1. Create database
mysql -u root -p
CREATE DATABASE skillbridge;
EXIT;

# 2. Copy and configure .env
cp .env.example .env
php artisan key:generate
# Edit .env with database config

# 3. Run migrations and seed
php artisan migrate:fresh --seed

# 4. Install and build assets
npm install
npm run build

# 5. Start development
php artisan serve

# Access at http://localhost:8000
```

### Daily Development Workflow
```bash
# 1. Start server
php artisan serve

# 2. In another terminal, watch assets
npm run watch

# 3. Open tinker for testing
php artisan tinker

# 4. Make code changes
# Files auto-reload, assets auto-build

# 5. Test in browser
# http://localhost:8000
```

### Pre-Deployment Workflow
```bash
# 1. Run tests
php artisan test

# 2. Clear caches
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan config:clear

# 3. Build for production
npm run build

# 4. Optimize
php artisan optimize

# 5. Deploy code
git push production main

# 6. Run migrations on server
ssh production php artisan migrate --force
```

---

**Last Updated**: June 30, 2026
**Laravel Version**: 12
**Status**: Complete Reference

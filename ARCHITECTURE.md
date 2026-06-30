# Skill-Bridge System - Technical Architecture

## 📐 System Architecture Overview

The Skill-Bridge System follows Laravel's MVC (Model-View-Controller) architecture pattern with additional service layers for complex business logic.

```
┌─────────────────────────────────────────────────────────────┐
│                        CLIENT LAYER                          │
│  (Web Browser - Chrome, Firefox, Edge, Safari, Mobile)      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                     PRESENTATION LAYER                       │
│  • Blade Templates (HTML)                                    │
│  • Bootstrap 5 (CSS)                                         │
│  • Vanilla JavaScript + Chart.js                             │
│  • Responsive UI Components                                  │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                    APPLICATION LAYER                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │ Controllers  │  │  Middleware  │  │    Routes    │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │   Services   │  │ Form Requests│  │     Enums    │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                      BUSINESS LAYER                          │
│  • Eloquent Models                                           │
│  • Relationships                                             │
│  • Query Scopes                                              │
│  • Model Events                                              │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                       DATA LAYER                             │
│  • MySQL Database                                            │
│  • Migrations                                                │
│  • Seeders                                                   │
│  • Eloquent ORM                                              │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                      STORAGE LAYER                           │
│  • Local File System (uploads, resumes, certificates)       │
│  • Logs (application, error, activity)                      │
└─────────────────────────────────────────────────────────────┘
```

---

## 🏗️ Laravel MVC Pattern Implementation

### Models (Business Logic & Data)

**Location**: `app/Models/`

#### Core Models
- `User.php` - User authentication and role management
- `Student.php` - Student profile and academic data
- `Employer.php` - Company/employer information
- `Coordinator.php` - Institution coordinator details
- `Institution.php` - Educational institutions

#### Competency Models
- `Skill.php` - System-wide skill definitions
- `Competency.php` - Master competency database
- `StudentCompetency.php` - Student skills with proficiency
- `Certificate.php` - Student certificates/certifications

#### Portfolio Models
- `Portfolio.php` - Student portfolio items
- `Resume.php` - Generated resumes

#### Internship Models
- `Internship.php` - Internship postings
- `InternshipRequirement.php` - Required competencies
- `InternshipApplication.php` - Student applications

#### Communication Models
- `AppNotification.php` - System notifications
- `Message.php` - Direct messages
- `Announcement.php` - System announcements

#### System Models
- `Report.php` - Generated reports
- `SystemLog.php` - Activity logging

### Controllers (Request Handling)

**Location**: `app/Http/Controllers/`

#### Authentication Controllers
`app/Http/Controllers/Auth/`
- `LoginController.php` - User login/logout
- `RegisterController.php` - New user registration
- `ForgotPasswordController.php` - Password reset requests
- `ResetPasswordController.php` - Password updates

#### Student Controllers
`app/Http/Controllers/Student/`
- `DashboardController.php` - Student dashboard
- `ProfileController.php` - Profile management
- `CompetencyController.php` - Skills management
- `PortfolioController.php` - Portfolio & certificates
- `ResumeController.php` - Resume generation
- `InternshipController.php` - Browse & apply

#### Employer Controllers
`app/Http/Controllers/Employer/`
- `DashboardController.php` - Employer dashboard
- `ProfileController.php` - Company profile
- `InternshipController.php` - Posting management
- `ApplicantController.php` - Application review

#### Coordinator Controllers
`app/Http/Controllers/Coordinator/`
- `DashboardController.php` - Coordinator dashboard
- `ReportController.php` - Report generation

#### Administrator Controllers
`app/Http/Controllers/Admin/`
- `DashboardController.php` - Admin dashboard
- `UserController.php` - User management
- `AnnouncementController.php` - Announcements
- `SystemController.php` - Logs, backup, reports

#### Shared Controllers
- `SearchController.php` - Global search
- `NotificationController.php` - Notification management
- `MessageController.php` - Messaging system
- `AnalyticsController.php` - Chart data API

### Views (Presentation)

**Location**: `resources/views/`

```
views/
├── layouts/
│   ├── app.blade.php          # Authenticated layout
│   └── guest.blade.php        # Guest layout
├── auth/
│   ├── login.blade.php
│   ├── register.blade.php
│   ├── forgot-password.blade.php
│   └── reset-password.blade.php
├── student/
│   ├── dashboard.blade.php
│   ├── profile/
│   ├── competencies/
│   ├── portfolio/
│   ├── resume/
│   ├── internships/
│   └── applications/
├── employer/
│   ├── dashboard.blade.php
│   ├── profile/
│   ├── internships/
│   └── applicants/
├── coordinator/
│   ├── dashboard.blade.php
│   ├── students/
│   └── reports/
├── admin/
│   ├── dashboard.blade.php
│   ├── users/
│   ├── announcements/
│   ├── logs/
│   └── reports/
├── messages/
├── notifications/
├── search/
└── welcome.blade.php
```

---

## 🔐 Security Architecture

### Authentication Flow

```
1. User submits login form
     ↓
2. LoginController validates credentials
     ↓
3. Laravel checks hashed password
     ↓
4. Session created (stored in database)
     ↓
5. Remember token generated (if selected)
     ↓
6. User redirected to role-specific dashboard
```

### Authorization Flow

```
1. User attempts to access route
     ↓
2. 'auth' middleware checks session
     ↓
3. 'role' middleware checks user role
     ↓
4. Controller action executes
     ↓
5. Policy checks (if applicable)
     ↓
6. View rendered or action performed
```

### Middleware Stack

1. **EncryptCookies** - Encrypts all cookies
2. **AddQueuedCookiesToResponse** - Adds queued cookies
3. **StartSession** - Initiates session
4. **ShareErrorsFromSession** - Shares validation errors
5. **VerifyCsrfToken** - CSRF protection
6. **SubstituteBindings** - Route model binding
7. **auth** - Checks if user is authenticated
8. **role:{role}** - Checks if user has specific role

**Custom Middleware**: `RoleMiddleware.php`

```php
// Usage in routes
Route::middleware('role:student')->group(function () {
    // Student-only routes
});
```

---

## 🗄️ Database Architecture

### Entity Relationship Diagram (ERD)

```
┌───────────┐         ┌────────────┐         ┌──────────────┐
│   Users   │─────────│  Students  │─────────│ Institutions │
└───────────┘    1:1  └────────────┘    N:1  └──────────────┘
      │                     │
      │ 1:N                 │ 1:N
      │                     │
      ↓                     ↓
┌───────────┐         ┌─────────────────────┐
│ Employers │         │ StudentCompetencies │
└───────────┘         └─────────────────────┘
      │                          │
      │ 1:N                      │ N:1
      │                          ↓
      ↓                    ┌──────────────┐
┌─────────────┐            │ Competencies │
│ Internships │            └──────────────┘
└─────────────┘
      │
      │ 1:N
      ↓
┌─────────────────────────┐         ┌───────────┐
│ InternshipRequirements  │─────────│  Skills   │
└─────────────────────────┘    N:1  └───────────┘
      │
      │ 1:N
      ↓
┌──────────────────────────┐         ┌───────────┐
│ InternshipApplications   │─────────│ Students  │
└──────────────────────────┘    N:1  └───────────┘
```

### Database Tables

#### users
```sql
- id (PK)
- name
- email (unique)
- password (hashed)
- role (enum: student, employer, coordinator, administrator)
- phone
- avatar
- is_active (boolean)
- email_verified_at
- last_login_at
- remember_token
- timestamps
```

#### students
```sql
- id (PK)
- user_id (FK → users.id, unique)
- institution_id (FK → institutions.id, nullable)
- student_id_number
- program
- year_level
- career_objectives (text)
- profile_picture
- address
- date_of_birth
- profile_completion (0-100)
- timestamps
```

#### employers
```sql
- id (PK)
- user_id (FK → users.id, unique)
- company_name
- industry
- description (text)
- website
- logo
- address
- contact_person
- timestamps
```

#### internships
```sql
- id (PK)
- employer_id (FK → employers.id)
- title
- description (text)
- responsibilities (text)
- requirements (text)
- duration
- allowance (decimal)
- work_setup (enum: onsite, hybrid, remote)
- location
- status (enum: open, closed)
- timestamps
```

#### internship_requirements
```sql
- id (PK)
- internship_id (FK → internships.id)
- competency_id (FK → competencies.id, nullable)
- skill_id (FK → skills.id, nullable)
- requirement_name
- required_level (enum proficiency)
- timestamps
```

#### internship_applications
```sql
- id (PK)
- internship_id (FK → internships.id)
- student_id (FK → students.id)
- status (enum: pending, reviewed, interview, accepted, rejected, completed)
- cover_letter (text)
- match_percentage (0-100)
- applied_at
- interview_at (nullable)
- employer_notes (text, nullable)
- timestamps
- UNIQUE(internship_id, student_id)
```

#### student_competencies
```sql
- id (PK)
- student_id (FK → students.id)
- competency_id (FK → competencies.id, nullable)
- name
- category (enum)
- description (text)
- proficiency_level (enum: beginner, intermediate, advanced, expert)
- obtained_at (date, nullable)
- timestamps
```

### Indexes

**Performance-critical indexes**:
- `users.email` - Login lookups
- `users.role` - Role filtering
- `internships.employer_id` - Employer's internships
- `internships.status` - Open internships
- `internship_applications.student_id` - Student's applications
- `internship_applications.status` - Status filtering
- `student_competencies.student_id` - Competency lookups
- `notifications.user_id, read_at` - Unread notifications

---

## 🔧 Service Layer Architecture

**Location**: `app/Services/`

Services encapsulate complex business logic that doesn't belong in controllers or models.

### CompetencyMatchingService

**Purpose**: Calculate match percentage between students and internships

**Methods**:
```php
calculateMatch(Student $student, Internship $internship): int
getRecommendations(Student $student, int $limit = 10): array
```

**Algorithm**:
1. Fetch internship requirements
2. Map proficiency levels to scores
3. Compare student competencies with requirements
4. Calculate percentage match
5. Return sorted recommendations

### NotificationService

**Purpose**: Manage system notifications

**Methods**:
```php
notify(User $user, string $type, string $title, string $message): void
unreadCount(User $user): int
markAllRead(User $user): void
```

### ResumeBuilderService

**Purpose**: Generate professional PDF resumes

**Methods**:
```php
generate(Student $student): Resume
downloadPDF(Resume $resume): Response
```

**Process**:
1. Collect student data (profile, competencies, certificates)
2. Format data for PDF template
3. Generate PDF using DomPDF
4. Store in database with file path
5. Return Resume model

### ReportExportService

**Purpose**: Generate PDF/Excel reports

**Methods**:
```php
exportPlacementReport(array $filters): string
exportStudentReport(array $filters): string
exportCompetencyAnalytics(array $filters): string
```

### ActivityLogService

**Purpose**: Log user activities

**Methods**:
```php
log(string $action, User $user, array $details = []): void
getUserActivity(User $user, int $limit = 50): Collection
```

---

## 🎨 Frontend Architecture

### Technology Stack

- **HTML5** - Semantic markup
- **CSS3** - Custom styles + Bootstrap utilities
- **Bootstrap 5.3.3** - Responsive framework
- **JavaScript (ES6+)** - Vanilla JS for interactions
- **Chart.js 4.4.1** - Data visualization
- **Bootstrap Icons** - Icon system

### Asset Pipeline

**Development**:
```
resources/css/app.css  ────→  Vite  ────→  public/build/assets/app-[hash].css
resources/js/app.js    ────→  Vite  ────→  public/build/assets/app-[hash].js
```

**Production**:
```bash
npm run build  # Minifies and optimizes assets
```

### Custom JavaScript Components

**Location**: `resources/js/`

#### app.js
- Bootstrap initialization
- Global event listeners
- CSRF token setup
- Toast notification helper

#### Chart Rendering
```javascript
// Fetch data via AJAX
fetch('/api/analytics/charts')
    .then(response => response.json())
    .then(data => {
        // Render Chart.js charts
        new Chart(ctx, config);
    });
```

### CSS Architecture

**Custom CSS Variables**:
```css
:root {
    --sb-primary: #2563EB;      /* Blue */
    --sb-accent: #10B981;       /* Emerald Green */
    --sb-sidebar-width: 260px;
}
```

**Component Structure**:
- Sidebar navigation
- Top bar with search
- Dashboard cards
- Stat cards with border accent
- Modal forms
- Toast notifications
- Responsive tables

---

## 📡 API Architecture

### REST API Endpoints

All API endpoints return JSON responses.

#### Authentication Required
All API routes require the `auth` middleware.

#### Endpoints

**Notifications API**
```
GET /api/notifications/unread
Headers: Accept: application/json
Response: {
    "count": 5,
    "notifications": [...]
}
```

**Analytics API**
```
GET /api/analytics/charts
Headers: Accept: application/json
Response: {
    "applications_per_month": {...},
    "placements": {...},
    "competency_levels": {...}
}
```

### AJAX Pattern

**Frontend**:
```javascript
fetch('/api/notifications/unread', {
    headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
})
.then(response => response.json())
.then(data => {
    // Update UI
});
```

**Backend Controller**:
```php
public function unreadJson()
{
    return response()->json([
        'count' => auth()->user()->appNotifications()
            ->whereNull('read_at')->count()
    ]);
}
```

---

## 🔄 Request Lifecycle

### Typical Request Flow

```
1. User clicks "Apply to Internship"
     ↓
2. Browser sends POST request to /student/internships/{id}/apply
     ↓
3. Laravel receives request → Middleware stack
     ↓
4. CSRF verification passes
     ↓
5. auth middleware confirms user is logged in
     ↓
6. role:student middleware confirms user is student
     ↓
7. Routes to StudentInternshipController@apply
     ↓
8. Controller validates request data
     ↓
9. Controller calls CompetencyMatchingService
     ↓
10. Service calculates match percentage
     ↓
11. InternshipApplication model created
     ↓
12. Notification sent to employer
     ↓
13. Activity logged to system_logs
     ↓
14. Redirect with success message
     ↓
15. View rendered with updated data
```

---

## 🚀 Deployment Architecture

### Recommended Production Stack

```
┌─────────────────────────────────────────┐
│          Load Balancer (NGINX)          │
└─────────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────────┐
│       Web Server (Apache/NGINX)         │
│       PHP 8.2+ with OPcache             │
└─────────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────────┐
│      Application (Laravel 12)           │
│      Optimized (cached routes/config)   │
└─────────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────────┐
│      Database (MySQL 8+)                │
│      Master-Slave Replication           │
└─────────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────────┐
│      Cache (Redis/Memcached)            │
└─────────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────────┐
│      Queue Worker (Supervisor)          │
└─────────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────────┐
│      File Storage (S3 or Local)         │
└─────────────────────────────────────────┘
```

### Environment Configuration

**Development** (.env)
```env
APP_ENV=local
APP_DEBUG=true
```

**Production** (.env)
```env
APP_ENV=production
APP_DEBUG=false
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Optimization Commands

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Build optimized assets
npm run build
```

---

## 🔒 Security Best Practices Implemented

1. **CSRF Protection** - All forms include CSRF tokens
2. **SQL Injection Prevention** - Eloquent ORM with parameterized queries
3. **XSS Protection** - Blade automatic escaping (`{{ }}`)
4. **Password Hashing** - Bcrypt with salt
5. **File Upload Validation** - Mime type and size checks
6. **Rate Limiting** - Login throttling
7. **Session Security** - HTTP-only cookies
8. **Input Validation** - Laravel validation rules
9. **Authorization** - Role-based access control
10. **Activity Logging** - Audit trails

---

## 📊 Performance Optimization

### Database Optimization
- Indexed foreign keys
- Eager loading relationships (`with()`)
- Query result caching
- Pagination for large datasets

### Frontend Optimization
- Minified CSS/JS
- CDN for Bootstrap/Icons
- Lazy loading images
- Responsive images

### Backend Optimization
- OPcache enabled
- Config/Route caching
- Query optimization
- Efficient data structures

---

## 🧪 Testing Architecture

### Test Structure

```
tests/
├── Feature/           # Integration tests
│   ├── AuthTest.php
│   ├── StudentTest.php
│   └── EmployerTest.php
└── Unit/              # Unit tests
    ├── CompetencyMatchingTest.php
    └── ResumeBuilderTest.php
```

### Running Tests

```bash
php artisan test
```

---

## 📦 Dependency Management

### PHP Dependencies (Composer)
- **laravel/framework** - Core framework
- **barryvdh/laravel-dompdf** - PDF generation
- **laravel/tinker** - REPL tool

### JavaScript Dependencies (NPM)
- **vite** - Asset bundler
- **bootstrap** - CSS framework
- **chart.js** - Charts

---

**Last Updated**: June 30, 2026
**Version**: 1.0.0

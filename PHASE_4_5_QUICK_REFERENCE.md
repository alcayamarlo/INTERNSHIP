# Phase 4 & 5 Quick Reference Guide

## What's New? 🎉

### Phase 4: User Profiles
Every user can now:
- ✅ View and edit their profile
- ✅ Upload profile pictures or company logos
- ✅ Update personal/company information
- ✅ See validation errors inline
- ✅ Delete images anytime

### Phase 5: Dashboards
Every user role has a professional dashboard with:
- ✅ Key statistics at a glance
- ✅ Recent activity tables
- ✅ Quick action buttons
- ✅ Notifications panel
- ✅ Mobile-responsive design

---

## Accessing Features

### Student
```
Profile:   /student/profile
Dashboard: /student/dashboard
```

### Employer
```
Profile:   /employer/profile
Dashboard: /employer/dashboard
```

### Coordinator
```
Profile:   /coordinator/profile
Dashboard: /coordinator/dashboard
```

### Admin
```
Dashboard: /admin/dashboard
```

---

## Key Routes

### Profile Routes
| Route | Method | Description |
|-------|--------|-------------|
| `/student/profile` | GET | Show profile edit form |
| `/student/profile` | PUT | Update profile |
| `/student/profile-picture` | DELETE | Remove profile picture |
| `/employer/profile` | GET | Show company profile form |
| `/employer/profile` | PUT | Update company info |
| `/employer/company-logo` | DELETE | Remove company logo |
| `/coordinator/profile` | GET | Show coordinator profile |
| `/coordinator/profile` | PUT | Update coordinator info |
| `/coordinator/profile-picture` | DELETE | Remove profile picture |

### Dashboard Routes
| Route | Method | Description |
|-------|--------|-------------|
| `/student/dashboard` | GET | Student dashboard |
| `/employer/dashboard` | GET | Employer dashboard |
| `/coordinator/dashboard` | GET | Coordinator dashboard |
| `/admin/dashboard` | GET | Admin dashboard |

---

## Controllers

### Profile Controllers
- `app/Http/Controllers/Student/ProfileController.php`
- `app/Http/Controllers/Employer/ProfileController.php`
- `app/Http/Controllers/Coordinator/ProfileController.php`

**Methods:**
- `edit()` - Show profile form
- `update()` - Process profile update
- `deleteProfilePicture()` or `deleteCompanyLogo()` - Delete image

### Dashboard Controllers
- `app/Http/Controllers/Student/DashboardController.php`
- `app/Http/Controllers/Employer/DashboardController.php`
- `app/Http/Controllers/Coordinator/DashboardController.php`
- `app/Http/Controllers/Admin/DashboardController.php`

**Methods:**
- `index()` - Display dashboard with statistics

---

## Form Validation

### Student Profile
- Required: first_name, last_name, email, phone, address, city, province, zip_code
- Optional: middle_name, suffix, student_number, program, career_objectives, etc.
- Image: JPG, JPEG, PNG only, max 2MB

### Employer Profile
- Required: company_name, industry, contact_person, position, email, phone, street, city, province, zip_code
- Optional: website, description, company_size
- Image: JPG, JPEG, PNG only, max 2MB

### Coordinator Profile
- Required: name, email, phone, department, position, office_address
- Optional: none
- Image: JPG, JPEG, PNG only, max 2MB

---

## Files Uploaded Storage

| File Type | Storage Path |
|-----------|--------------|
| Student Profile Picture | `public/profiles/students/` |
| Employer Company Logo | `public/logos/employers/` |
| Coordinator Profile Picture | `public/profiles/coordinators/` |

**Access:** Use `Storage::url($path)` in Blade templates

---

## Views Structure

### Profile Views
```
resources/views/
├── student/profile/edit.blade.php
├── employer/profile/edit.blade.php
└── coordinator/profile/edit.blade.php
```

### Dashboard Views
```
resources/views/
├── student/dashboard.blade.php
├── employer/dashboard.blade.php
├── coordinator/dashboard.blade.php
└── admin/dashboard.blade.php
```

### Reusable Components
```
resources/views/components/
├── dashboard-stat-card.blade.php
├── dashboard-quick-actions.blade.php
└── dashboard-notifications.blade.php
```

---

## Component Usage Examples

### Stat Card Component
```blade
<x-dashboard-stat-card 
    title="Total Users"
    value="{{ $stats['total_users'] }}"
    color="#2563EB"
    description="Active system users"
/>
```

### Quick Actions Component
```blade
<x-dashboard-quick-actions title="Quick Actions" icon="bi bi-lightning-charge">
    <a href="/profile" class="list-group-item list-group-item-action">
        <i class="bi bi-person"></i> Edit Profile
    </a>
</x-dashboard-quick-actions>
```

### Notifications Component
```blade
<x-dashboard-notifications 
    :notifications="$notifications"
    title="Recent Notifications"
/>
```

---

## Database Changes

### New Migration
- `2026_06_30_000007_add_profile_to_coordinators.php`
  - Added `profile_picture` column
  - Added `office_address` column

### Updated Models
- `Student`: Added profile_picture to fillable
- `Employer`: Updated fillable attributes
- `Coordinator`: Added profile_picture and office_address to fillable

---

## Dashboard Data

### Student Dashboard
- Profile completion percentage
- Competency score
- Active applications count
- Unread notifications
- Recommended internships (5 most recent)
- Recent applications (5 most recent)

### Employer Dashboard
- Active internship count
- Total applicants
- Applications breakdown (submitted, reviewed, interview, accepted, rejected)
- Recent applications (5 most recent)
- Success rate percentage

### Coordinator Dashboard
- Total students in institution
- Total applications submitted
- Accepted placements
- Placement rate percentage
- Recent students (5 most recent)
- Recent applications (5 most recent)

### Admin Dashboard
- Total users breakdown (students, employers, coordinators)
- Total internships
- Total applications
- System placement rate
- Recent users (5 most recent)
- Recent system logs (10 most recent)
- Recent announcements (5 most recent)

---

## Demo Credentials

All demo accounts use password: `password`

| Email | Role | Access |
|-------|------|--------|
| `admin@skillbridge.test` | Administrator | `/admin/dashboard` |
| `student@skillbridge.test` | Student | `/student/dashboard` |
| `employer@skillbridge.test` | Employer | `/employer/dashboard` |
| `coordinator@skillbridge.test` | Coordinator | `/coordinator/dashboard` |

---

## Common Tasks

### Access Student Dashboard
1. Login as `student@skillbridge.test`
2. Click "Dashboard" in sidebar
3. Or navigate to `/student/dashboard`

### Edit Your Profile
1. Click "Profile" or "Edit Profile" button
2. Update fields
3. Upload/change profile picture if needed
4. Click "Save Changes"

### View Recent Applications (Student)
1. Dashboard shows 5 most recent
2. Click "View all" to see all applications
3. Applications are color-coded by status

### Check Placement Rate (Coordinator)
1. Dashboard shows placement rate percentage
2. Based on: accepted applications / total applications
3. Click "Generate Reports" for detailed analysis

### Manage Announcements (Admin)
1. Click "Manage Announcements" in dashboard
2. View all announcements
3. Create new announcements
4. Delete outdated announcements

---

## Color Scheme

| Status | Color | Hex |
|--------|-------|-----|
| Submitted | Blue | #3B82F6 |
| Reviewed | Amber | #F59E0B |
| Interview | Purple | #8B5CF6 |
| Accepted | Green | #10B981 |
| Rejected | Red | #EF4444 |
| Primary | Blue | #2563EB |
| Success | Green | #10B981 |
| Warning | Amber | #F59E0B |
| Danger | Red | #EF4444 |

---

## Bootstrap 5 Classes Used

- `.card` - Container for content
- `.card-header` - Card title section
- `.card-body` - Main content area
- `.btn btn-primary` - Primary buttons
- `.btn btn-outline-secondary` - Secondary buttons
- `.progress` - Progress bars
- `.badge` - Status indicators
- `.table table-hover` - Interactive tables
- `.list-group` - Action lists
- `.form-control` - Form inputs
- `.invalid-feedback` - Error messages

---

## Security Features

✅ Authentication middleware on all routes
✅ Role middleware for access control
✅ CSRF protection on all forms
✅ File upload validation
✅ File size limits (2MB max)
✅ File type restrictions (JPG, JPEG, PNG)
✅ Secure storage with symbolic links
✅ User can only access their own profile
✅ Password hashing (Bcrypt)
✅ Session management

---

## Performance Notes

- Uses eager loading to prevent N+1 queries
- Dashboard statistics calculated efficiently
- Recent activity limited to 5 items per view
- Pagination available for large datasets
- Images stored locally with CDN-ready structure
- Symbolic link for efficient file serving

---

## Next Steps (Phase 6+)

- Competency Management Module
- Resume Builder
- Portfolio Management
- Application Tracking System
- Interview Scheduler
- Performance Analytics
- Email Notifications
- Advanced Reporting

---

## Support & Troubleshooting

### Profile Picture Not Showing
1. Check storage symbolic link: `php artisan storage:link`
2. Verify file exists in `storage/app/public/`
3. Check permissions on storage directory

### Dashboard Not Loading
1. Verify user is authenticated
2. Check role middleware configuration
3. View Laravel logs: `storage/logs/`

### Form Validation Errors
1. Check form validation rules in FormRequest classes
2. Verify file size and type
3. Check browser console for JavaScript errors

### Database Issues
1. Run migration: `php artisan migrate`
2. Check migration status: `php artisan migrate:status`
3. View database tables: `php artisan tinker`

---

## Version Info

- **Laravel**: 12.x
- **PHP**: 8.2+
- **Bootstrap**: 5.x
- **Phase 4 & 5**: Complete ✅
- **Status**: Production Ready

---

*Last Updated: June 30, 2026*
*For detailed documentation, see PHASE_4_5_COMPLETION.md*

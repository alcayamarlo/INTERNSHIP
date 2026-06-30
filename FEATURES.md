# Skill-Bridge System - Complete Feature Documentation

## 📑 Table of Contents

1. [Authentication System](#authentication-system)
2. [Student Features](#student-features)
3. [Employer Features](#employer-features)
4. [Coordinator Features](#coordinator-features)
5. [Administrator Features](#administrator-features)
6. [Shared Features](#shared-features)
7. [API Endpoints](#api-endpoints)
8. [Competency Matching Algorithm](#competency-matching-algorithm)

---

## 🔐 Authentication System

### Login
- **Route**: `GET /login`, `POST /login`
- **Features**:
  - Email and password authentication
  - Remember me functionality
  - CSRF protection
  - Role-based dashboard redirection
  - Failed login tracking
  - Last login timestamp

### Registration
- **Route**: `GET /register`, `POST /register`
- **Features**:
  - Multi-role registration (Student, Employer, Coordinator)
  - Email validation
  - Password strength requirements
  - Automatic profile creation based on role
  - Email uniqueness check

### Password Reset
- **Routes**: 
  - `GET /forgot-password` (Request form)
  - `POST /forgot-password` (Send reset link)
  - `GET /reset-password/{token}` (Reset form)
  - `POST /reset-password` (Update password)
- **Features**:
  - Email-based password reset
  - Secure token generation
  - Token expiration (60 minutes)
  - Password confirmation

### Logout
- **Route**: `POST /logout`
- **Features**:
  - Session destruction
  - Remember token removal
  - Redirect to home page

---

## 👨‍🎓 Student Features

### Dashboard
- **Route**: `GET /student/dashboard`
- **Displays**:
  - Profile completion percentage
  - Average competency score
  - Top 5 recommended internships (sorted by match %)
  - Recent applications with status
  - Latest 5 notifications
  - Unread notification count
  - Quick action buttons

### Profile Management
- **Route**: `GET /student/profile`, `PUT /student/profile`
- **Fields**:
  - Personal Information (name, email, phone)
  - Student ID number
  - Institution
  - Program/Major
  - Year level
  - Career objectives
  - Address
  - Date of birth
  - Profile picture upload
- **Features**:
  - Automatic profile completion calculation
  - Image upload validation
  - Profile preview
  - Responsive form

### Competency Management
- **Routes**:
  - `GET /student/competencies` (List)
  - `POST /student/competencies` (Create)
  - `PUT /student/competencies/{id}` (Update)
  - `DELETE /student/competencies/{id}` (Delete)
- **Features**:
  - Add technical skills (e.g., PHP, Laravel, JavaScript)
  - Add soft skills (e.g., Communication, Teamwork)
  - Add certifications with details
  - Proficiency levels:
    - **Beginner** (25 points)
    - **Intermediate** (50 points)
    - **Advanced** (75 points)
    - **Expert** (100 points)
  - Category selection (Technical, Soft, Certification, Training)
  - Description/Notes field
  - Date obtained tracking
  - Competency suggestions from system database
- **UI**:
  - Card-based layout
  - Color-coded proficiency badges
  - Quick add modal
  - Inline editing
  - Confirmation before delete

### Portfolio Management
- **Routes**:
  - `GET /student/portfolio` (View)
  - `POST /student/portfolio` (Upload portfolio item)
  - `POST /student/certificates` (Upload certificate)
  - `DELETE /student/portfolio/{id}` (Delete portfolio)
  - `DELETE /student/certificates/{id}` (Delete certificate)
- **Portfolio Items**:
  - Projects
  - Awards
  - Transcripts
  - Other documents
- **Certificates**:
  - Title
  - Issuer
  - Issue date
  - File upload (PDF, JPG, PNG, DOCX)
- **Features**:
  - File type validation
  - File size limits
  - Thumbnail previews
  - Download/View files
  - Organized by type

### Resume Builder
- **Routes**:
  - `GET /student/resume` (View resumes)
  - `POST /student/resume/generate` (Generate new resume)
  - `GET /student/resume/{id}/download` (Download PDF)
- **Auto-generates**:
  - Personal information
  - Contact details
  - Education history
  - Skills and competencies with proficiency
  - Certifications
  - Portfolio highlights
  - Career objectives
- **Features**:
  - Professional PDF template
  - One-click generation
  - Multiple resume versions
  - Download as PDF
  - Print-ready format

### Internship Browsing
- **Routes**:
  - `GET /student/internships` (Browse)
  - `GET /student/internships/{id}` (View details)
  - `POST /student/internships/{id}/apply` (Apply)
- **Features**:
  - List all open internships
  - **Smart matching** - Shows match percentage for each internship
  - Filter by:
    - Location
    - Work setup (Remote, Hybrid, Onsite)
    - Company/Employer
    - Skills required
  - Search functionality
  - Internship details view:
    - Title and description
    - Responsibilities
    - Requirements
    - Required competencies
    - Duration
    - Allowance
    - Work setup
    - Company information
    - **Match percentage badge**
- **Match Percentage**:
  - Calculated in real-time
  - Based on student competencies vs requirements
  - Color-coded (Green: 75%+, Yellow: 50-74%, Red: <50%)
  - Detailed breakdown available

### Application Management
- **Route**: `GET /student/applications`
- **Displays**:
  - All submitted applications
  - Application status:
    - **Pending** - Under review
    - **Reviewed** - Employer reviewed
    - **Interview** - Interview scheduled
    - **Accepted** - Offer accepted
    - **Rejected** - Application declined
    - **Completed** - Internship finished
  - Application date
  - Internship details
  - Match percentage
  - Employer notes (if any)
  - Interview date/time (if scheduled)
- **Features**:
  - Status timeline view
  - Filter by status
  - Withdraw application option
  - View employer company profile

### Application Submission
- **Features**:
  - Cover letter text area
  - Automatic resume attachment
  - Portfolio visibility
  - One-click apply
  - Duplicate application prevention
  - Confirmation modal

---

## 🏢 Employer Features

### Dashboard
- **Route**: `GET /employer/dashboard`
- **Displays**:
  - Total active internships
  - Total applications received
  - Pending applications count
  - Accepted students count
  - Recent applications
  - Application status chart
  - Quick action buttons

### Company Profile
- **Route**: `GET /employer/profile`, `PUT /employer/profile`
- **Fields**:
  - Company name
  - Industry
  - Company description
  - Website URL
  - Office address
  - Contact person name
  - Contact email
  - Contact phone
  - Company logo upload
- **Features**:
  - Logo upload and preview
  - Validation for URLs
  - Public profile view (visible to students)

### Internship Management
- **Routes**:
  - `GET /employer/internships` (List)
  - `GET /employer/internships/create` (Create form)
  - `POST /employer/internships` (Store)
  - `GET /employer/internships/{id}/edit` (Edit form)
  - `PUT /employer/internships/{id}` (Update)
  - `DELETE /employer/internships/{id}` (Delete)
  - `POST /employer/internships/{id}/close` (Mark as closed)

#### Creating Internship Posting
**Fields**:
- **Title** (e.g., "Web Developer Intern")
- **Description** (Rich text - full job description)
- **Responsibilities** (Bullet points)
- **Requirements** (General requirements)
- **Required Skills/Competencies** (Multiple select)
  - Select from system skills
  - Set proficiency level required for each
- **Duration** (e.g., "3 months", "6 months")
- **Allowance** (Optional - monthly stipend)
- **Work Setup**:
  - Remote
  - Hybrid
  - Onsite
- **Location** (City/Address for onsite/hybrid)

**Features**:
- Draft save functionality
- Duplicate internship option
- Preview before publishing
- Required skills with levels
- Open/Closed status toggle

### Applicant Management
- **Routes**:
  - `GET /employer/applicants` (List all)
  - `GET /employer/applicants/{id}` (View details)
  - `PUT /employer/applicants/{id}/status` (Update status)
  - `GET /employer/applicants/{id}/resume` (Download resume)

**Features**:
- View all applications across all internships
- Filter by:
  - Internship
  - Status
  - Match percentage range
  - Application date
- Sort by:
  - Match percentage (highest first)
  - Application date
  - Student name

**Applicant Detail View**:
- Student profile information
- **Match percentage** with breakdown
- Student competencies list with proficiency
- Portfolio items
- Certificates
- Resume download button
- Cover letter
- Application date

**Status Management**:
- Update application status
- Add employer notes
- Schedule interview (date/time picker)
- Accept/Reject with reason
- Send status update notification to student

---

## 🎓 Coordinator Features

### Dashboard
- **Route**: `GET /coordinator/dashboard`
- **Displays**:
  - Total students in institution
  - Students with active applications
  - Students with accepted placements
  - Internship completion rate
  - Recent student activities
  - Placement statistics chart
  - Average student competency score

### Student Management
- **Routes**:
  - `GET /coordinator/students` (List)
  - `GET /coordinator/students/{id}` (View profile)
- **Features**:
  - View all students in institution
  - Filter by program, year level
  - Search by name, student ID
  - View student profiles (read-only)
  - Track student progress:
    - Profile completion
    - Competencies added
    - Applications submitted
    - Internship status
  - Export student list

**Student Profile View**:
- Personal information
- Academic details
- Competencies summary
- Application history
- Current internship status
- Portfolio overview
- Contact information

### Report Generation
- **Routes**:
  - `GET /coordinator/reports` (Report center)
  - `POST /coordinator/reports/generate` (Generate report)

**Report Types**:

1. **Placement Report**
   - Students with active internships
   - Placement rate by program
   - Top employers
   - Average match percentage
   - Export: PDF, Excel

2. **Student Progress Report**
   - Profile completion stats
   - Competency distribution
   - Application activity
   - Success rates
   - Export: PDF, Excel

3. **Competency Analytics**
   - Most common skills
   - Proficiency distribution
   - Gap analysis
   - Trending competencies
   - Export: PDF, Excel

4. **Employer Report**
   - Active employers
   - Internship postings
   - Acceptance rates
   - Industry distribution
   - Export: PDF, Excel

**Features**:
- Date range selection
- Program filter
- Year level filter
- Export format selection (PDF/Excel)
- Email report option
- Schedule recurring reports

---

## ⚙️ Administrator Features

### Dashboard
- **Route**: `GET /admin/dashboard`
- **Displays**:
  - System-wide statistics
  - Total users by role
  - Total active internships
  - Total applications
  - User growth chart
  - Application status distribution
  - Recent system activities
  - Storage usage

### User Management
- **Routes**:
  - `GET /admin/users` (List all users)
  - `PUT /admin/users/{id}` (Update user)
  - `DELETE /admin/users/{id}` (Delete user)

**Features**:
- View all users (students, employers, coordinators, admins)
- Filter by role
- Search by name, email
- User details:
  - Basic information
  - Role
  - Account status (active/inactive)
  - Last login
  - Registration date
  - Profile completion (students)
- **Actions**:
  - Toggle active/inactive status
  - Change role (with confirmation)
  - Reset password (send email)
  - Delete account (with confirmation)
  - View activity logs

### Announcement Management
- **Routes**:
  - `GET /admin/announcements` (List)
  - `POST /admin/announcements` (Create)
  - `DELETE /admin/announcements/{id}` (Delete)

**Features**:
- Create system-wide announcements
- Target specific roles or all users
- Rich text editor
- Schedule publish date/time
- Mark as important (highlighted)
- Edit/Delete announcements
- View read statistics

**Announcement Fields**:
- Title
- Content (rich text)
- Target role (All, Students, Employers, Coordinators)
- Publish date
- Importance level

### System Logs
- **Route**: `GET /admin/logs`
- **Displays**:
  - User activities
  - Login history
  - Failed login attempts
  - Data changes
  - Error logs
- **Features**:
  - Filter by:
    - User
    - Action type
    - Date range
    - IP address
  - Search functionality
  - Export logs (CSV)
  - View detailed log entries
  - IP address tracking
  - User agent information

**Logged Actions**:
- User login/logout
- Profile updates
- Application submissions
- Internship postings
- Status changes
- File uploads
- Admin actions
- Failed authentication attempts

### System Reports
- **Route**: `GET /admin/reports`, `POST /admin/reports`

**Available Reports**:

1. **User Statistics Report**
   - Total users by role
   - User growth trends
   - Active vs inactive users
   - Registration sources

2. **Application Analytics**
   - Total applications
   - Success rate
   - Average time to acceptance
   - Top employers
   - Popular internships

3. **Competency Analysis**
   - Most in-demand skills
   - Proficiency distribution
   - Skill gaps
   - Industry trends

4. **Platform Usage Report**
   - Page views
   - Feature usage
   - Peak usage times
   - User engagement metrics

5. **Financial Report** (if applicable)
   - Subscription revenue
   - Payment statistics
   - Active subscriptions

### Database Backup
- **Route**: `POST /admin/backup`
- **Features**:
  - One-click database backup
  - Scheduled automatic backups
  - Download backup file
  - Restore from backup
  - Backup history
  - Storage management

---

## 🔗 Shared Features (All Users)

### Global Search
- **Route**: `GET /search`
- **Searchable**:
  - Students (by name, program, skills)
  - Companies/Employers (by name, industry)
  - Internships (by title, description, requirements)
  - Skills/Competencies
- **Features**:
  - Auto-complete suggestions
  - Filter by category
  - Recent searches
  - Search history
  - Result pagination

### Notifications
- **Routes**:
  - `GET /notifications` (View all)
  - `POST /notifications/{id}/read` (Mark as read)
  - `POST /notifications/read-all` (Mark all as read)
  - `GET /api/notifications/unread` (JSON - unread count)

**Notification Types**:
- New internship matching your skills
- Application status update
- Interview scheduled
- Application accepted/rejected
- New message received
- New announcement
- Profile views (for employers)
- System notifications

**Features**:
- Real-time badge counter
- Toast notifications
- Notification bell with dropdown
- Read/Unread status
- Mark all as read
- Filter by type
- Date grouping

### Messaging System
- **Routes**:
  - `GET /messages` (Inbox)
  - `GET /messages/{user}` (Conversation)
  - `POST /messages/{user}` (Send message)

**Features**:
- Direct messaging between:
  - Student ↔ Employer
  - Student ↔ Coordinator
  - Employer ↔ Coordinator
  - Admin ↔ Anyone
- Conversation threads
- Unread message badges
- Search conversations
- Delete messages
- File attachments (if enabled)
- Read receipts
- Typing indicators (if real-time enabled)

### Analytics Dashboard
- **Route**: `GET /api/analytics/charts` (JSON)
- **Charts**:
  - Applications per month (Line chart)
  - Internship placements (Bar chart)
  - Student competency levels (Pie chart)
  - Most requested skills (Horizontal bar)
  - Active employers by industry (Doughnut chart)
- **Technology**: Chart.js
- **Features**:
  - Interactive tooltips
  - Responsive design
  - Export chart as image
  - Customizable date ranges

---

## 🔌 API Endpoints

### Public API (No Authentication)
None - all endpoints require authentication

### Authenticated API Endpoints

#### Notifications
```
GET /api/notifications/unread
Response: { "count": 5, "notifications": [...] }
```

#### Analytics
```
GET /api/analytics/charts
Response: {
  "applications_per_month": {...},
  "placements": {...},
  "competency_levels": {...},
  "top_skills": {...}
}
```

#### Search Autocomplete
```
GET /api/search/autocomplete?q=php
Response: {
  "students": [...],
  "internships": [...],
  "skills": [...]
}
```

---

## 🧮 Competency Matching Algorithm

### How It Works

The system uses a sophisticated matching algorithm to calculate compatibility between student competencies and internship requirements.

#### Step 1: Requirement Extraction
- Extract all required competencies from internship posting
- Each requirement has:
  - Competency/Skill name
  - Required proficiency level

#### Step 2: Proficiency Scoring
Proficiency levels are mapped to numerical scores:
- **Beginner**: 25 points
- **Intermediate**: 50 points
- **Advanced**: 75 points
- **Expert**: 100 points

#### Step 3: Student Competency Matching
For each internship requirement:
1. Find matching student competency (by name or ID)
2. Compare proficiency scores
3. Award match points:
   - **Full match**: Student proficiency >= required (1.0 point)
   - **Partial match**: Student proficiency >= 60% of required (0.5 points)
   - **No match**: Student lacks competency (0 points)

#### Step 4: Calculate Match Percentage
```
Match Percentage = (Total Match Points / Total Requirements) × 100
```

#### Step 5: Sorting and Recommendation
- Internships are sorted by match percentage (highest first)
- Recommendations shown on student dashboard
- Match % displayed as colored badge:
  - **Green**: 75%+ (Excellent match)
  - **Yellow**: 50-74% (Good match)
  - **Red**: 0-49% (Low match)

### Example Calculation

**Internship Requirements**:
1. PHP Development - Intermediate (50 points)
2. Laravel Framework - Beginner (25 points)
3. Database Design - Intermediate (50 points)

**Student Competencies**:
1. PHP Development - Advanced (75 points) ✅ Full match
2. Laravel Framework - Intermediate (50 points) ✅ Full match
3. JavaScript - Advanced (75 points) ❌ Not required

**Calculation**:
- PHP: 75 >= 50 → 1.0 point
- Laravel: 50 >= 25 → 1.0 point
- Database: Not possessed → 0 points
- **Total**: 2.0 / 3 = 66.67% **match**

### Benefits
- ✅ Objective, data-driven matching
- ✅ Helps students find suitable internships
- ✅ Helps employers find qualified candidates
- ✅ Reduces application waste
- ✅ Improves placement success rates
- ✅ Identifies skill gaps for students

---

## 🎯 Feature Highlights Summary

### 🔐 Security
- CSRF protection on all forms
- SQL injection prevention via Eloquent
- XSS protection via Blade escaping
- Password hashing (Bcrypt)
- File upload validation
- Role-based access control
- Activity logging

### 📱 Responsive Design
- Mobile-first Bootstrap 5
- Responsive tables
- Collapsible sidebar on mobile
- Touch-friendly buttons
- Adaptive forms

### 🚀 Performance
- Database query optimization
- Eager loading relationships
- Indexed database columns
- Cached configuration
- CDN for Bootstrap/Icons

### 🎨 User Experience
- Toast notifications
- Loading indicators
- Confirmation modals
- Inline form validation
- Auto-save drafts
- Keyboard shortcuts
- Breadcrumb navigation

### 📊 Data Management
- Soft deletes (when appropriate)
- Audit trails
- Data export (PDF, Excel)
- Batch operations
- Advanced filtering

### 🔔 Communication
- Real-time notifications
- In-app messaging
- Email notifications (configurable)
- Announcement system
- Status updates

---

**Last Updated**: June 30, 2026
**Version**: 1.0.0

# Skill-Bridge System

## Competency-Based Student Development and Internship Placement System

A comprehensive web-based platform built with Laravel 12 that connects students, employers, educational institutions, and administrators to simplify internship placement, competency tracking, portfolio management, and student career development.

---

## 🚀 Features

### Authentication & Authorization
- ✅ Secure user authentication with Laravel's built-in authentication
- ✅ Role-Based Access Control (RBAC)
- ✅ Password reset functionality
- ✅ Session management
- ✅ CSRF protection

### User Roles
1. **Administrator** - System management, user management, reports, announcements
2. **Student** - Profile management, competencies, portfolio, internship applications
3. **Employer** - Company profile, internship postings, applicant management
4. **Institution Coordinator** - Student monitoring, placement tracking, reporting

### Student Module
- ✅ Comprehensive dashboard with recommendations
- ✅ Profile management with completion tracking
- ✅ Competency management (skills, proficiency levels, certificates)
- ✅ Portfolio upload (certificates, projects, awards, transcripts)
- ✅ Automated resume builder with PDF export
- ✅ Internship browsing with smart matching
- ✅ Application tracking (Pending, Reviewed, Interview, Accepted, Rejected)
- ✅ Competency-based internship recommendations

### Employer Module
- ✅ Company profile management
- ✅ Internship posting (create, edit, delete, close)
- ✅ Applicant management with filtering
- ✅ Match percentage viewing
- ✅ Resume and portfolio downloads
- ✅ Application status updates
- ✅ Interview scheduling

### Coordinator Module
- ✅ Student monitoring dashboard
- ✅ Placement tracking
- ✅ Student profile viewing
- ✅ Report generation (PDF/Excel export)
- ✅ Competency analytics

### Administrator Module
- ✅ User management (CRUD operations)
- ✅ System-wide announcements
- ✅ Activity logging
- ✅ System reports and analytics
- ✅ Database backup functionality

### Advanced Features
- ✅ **Competency Matching Algorithm** - Automatically calculates match percentage between student competencies and internship requirements
- ✅ **Smart Recommendations** - AI-powered internship suggestions based on student skills
- ✅ **Real-time Notifications** - Instant updates for applications, messages, announcements
- ✅ **Messaging System** - Direct communication between students, employers, coordinators
- ✅ **Global Search** - Search students, companies, skills, competencies, internships
- ✅ **Analytics Dashboard** - Charts and statistics using Chart.js
- ✅ **Report Export** - Generate PDF and Excel reports
- ✅ **Responsive Design** - Fully mobile-friendly Bootstrap 5 UI

---

## 🛠️ Technology Stack

### Backend
- **Framework**: Laravel 12
- **PHP**: 8.2+
- **Database**: MySQL with PDO
- **Architecture**: MVC Pattern
- **ORM**: Eloquent
- **Authentication**: Laravel Breeze-style authentication
- **Validation**: Laravel Form Requests
- **Middleware**: Role-based access control

### Frontend
- **HTML5**
- **CSS3**
- **Bootstrap 5.3.3** (Responsive UI)
- **JavaScript** (Vanilla JS)
- **Chart.js** (Analytics charts)
- **Bootstrap Icons**

### Additional Libraries
- **barryvdh/laravel-dompdf**: PDF generation for resumes and reports

---

## 📋 Requirements

- **PHP**: >= 8.2
- **Composer**: Latest version
- **MySQL**: >= 5.7 or MariaDB >= 10.3
- **Node.js & NPM**: Latest LTS version
- **Web Server**: Apache (XAMPP) or Nginx

---

## 🚀 Installation & Setup

### 1. Clone or Navigate to Project
```bash
cd c:\xampp\htdocs\nexus
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Environment Configuration
Copy the `.env.example` to `.env`:
```bash
copy .env.example .env
```

Update the `.env` file with your database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nexus
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Generate Application Key
```bash
php artisan key:generate
```

### 5. Create Database
Create a MySQL database named `nexus`:
```sql
CREATE DATABASE nexus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 6. Run Migrations & Seeders
```bash
php artisan migrate:fresh --seed
```

This will create all tables and seed demo data including:
- Administrator account
- Coordinator account
- Employer account
- Student account
- Sample institutions, internships, and applications

### 7. Create Storage Link
```bash
php artisan storage:link
```

### 8. Build Frontend Assets
```bash
npm run build
```

For development with hot reload:
```bash
npm run dev
```

### 9. Start the Development Server
```bash
php artisan serve
```

The application will be accessible at: `http://localhost:8000`

---

## 👤 Default Login Credentials

### Administrator
- **Email**: admin@skillbridge.test
- **Password**: password

### Coordinator
- **Email**: coordinator@skillbridge.test
- **Password**: password

### Employer
- **Email**: employer@skillbridge.test
- **Password**: password

### Student
- **Email**: student@skillbridge.test
- **Password**: password

**⚠️ Change these passwords in production!**

---

## 📁 Project Structure

```
nexus/
├── app/
│   ├── Enums/                    # Enum classes (UserRole, ApplicationStatus, etc.)
│   ├── Http/
│   │   ├── Controllers/          # All controllers organized by role
│   │   │   ├── Admin/            # Admin controllers
│   │   │   ├── Auth/             # Authentication controllers
│   │   │   ├── Coordinator/      # Coordinator controllers
│   │   │   ├── Employer/         # Employer controllers
│   │   │   └── Student/          # Student controllers
│   │   └── Middleware/           # Custom middleware (RoleMiddleware)
│   ├── Models/                   # Eloquent models
│   └── Services/                 # Business logic services
├── config/                       # Configuration files
├── database/
│   ├── migrations/               # Database migrations
│   └── seeders/                  # Database seeders
├── public/                       # Public assets
├── resources/
│   ├── css/                      # CSS files
│   ├── js/                       # JavaScript files
│   └── views/                    # Blade templates
│       ├── admin/                # Admin views
│       ├── auth/                 # Authentication views
│       ├── coordinator/          # Coordinator views
│       ├── employer/             # Employer views
│       ├── student/              # Student views
│       └── layouts/              # Layout templates
├── routes/
│   └── web.php                   # Web routes
├── storage/                      # Storage for uploads and logs
└── tests/                        # Automated tests
```

---

## 🗄️ Database Schema

### Core Tables
- **users** - User accounts with role-based authentication
- **students** - Student profiles and academic information
- **employers** - Company profiles and contact information
- **coordinators** - Institution coordinator details
- **institutions** - Educational institutions

### Competency Management
- **skills** - System-wide skill definitions
- **competencies** - Master competency list
- **student_competencies** - Student skills with proficiency levels
- **certificates** - Student certificates and certifications

### Portfolio & Career
- **portfolios** - Student portfolio items (projects, awards)
- **resumes** - Generated student resumes

### Internship System
- **internships** - Internship postings
- **internship_requirements** - Required competencies per internship
- **internship_applications** - Student applications with match percentage

### Communication
- **notifications** - In-app notifications
- **messages** - Direct messaging between users
- **announcements** - System-wide announcements

### System Management
- **reports** - Generated reports (PDF/Excel)
- **system_logs** - Activity and security logs

---

## 🔐 Security Features

- **CSRF Protection** - All forms protected with CSRF tokens
- **SQL Injection Prevention** - Eloquent ORM with parameterized queries
- **XSS Protection** - Blade template escaping
- **Password Hashing** - Bcrypt password hashing
- **File Upload Validation** - Mime type and size validation
- **Authentication Middleware** - Route protection
- **Authorization Policies** - Resource-level permissions
- **Activity Logging** - User action tracking
- **Session Security** - Secure session management

---

## 🎨 UI/UX Features

- **Modern Bootstrap 5 Design** with custom color scheme
- **Responsive Layout** - Works on desktop, tablet, and mobile
- **Sidebar Navigation** - Role-specific menu items
- **Dashboard Cards** - Quick stats and metrics
- **Search & Filters** - Easy data discovery
- **Pagination** - Efficient data browsing
- **Toast Notifications** - Non-intrusive feedback
- **Modal Forms** - Inline editing
- **Loading Indicators** - User feedback during operations
- **Confirmation Dialogs** - Prevent accidental actions

### Color Theme
- **Primary**: #2563EB (Blue)
- **Accent**: #10B981 (Emerald Green)
- **Background**: #f8fafc (Light Gray)

---

## 🧪 Testing

Run automated tests:
```bash
php artisan test
```

---

## 📊 Key Algorithms

### Competency Matching Algorithm
The system uses an intelligent matching algorithm to calculate compatibility between student competencies and internship requirements:

1. **Requirement Analysis**: Extracts required competencies from internship postings
2. **Proficiency Mapping**: Maps proficiency levels to numerical scores
   - Beginner: 25 points
   - Intermediate: 50 points
   - Advanced: 75 points
   - Expert: 100 points
3. **Match Calculation**: Compares student competencies with requirements
4. **Percentage Score**: Generates 0-100% match score
5. **Sorting & Recommendation**: Displays internships sorted by best match

---

## 🚀 Deployment

### Production Checklist

1. **Environment Configuration**
   ```bash
   APP_ENV=production
   APP_DEBUG=false
   ```

2. **Database Optimization**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

3. **Security Hardening**
   - Change all default passwords
   - Set strong `APP_KEY`
   - Configure HTTPS
   - Enable rate limiting
   - Set up database backups

4. **Server Configuration**
   - Set document root to `/public`
   - Configure PHP 8.2+
   - Enable required PHP extensions
   - Set proper file permissions

---

## 🔧 Maintenance

### Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Database Backup
```bash
php artisan backup:run
```

### View Logs
```bash
tail -f storage/logs/laravel.log
```

---

## 📚 API Endpoints (JSON)

The system provides JSON endpoints for AJAX operations:

- `GET /api/notifications/unread` - Get unread notifications count
- `GET /api/analytics/charts` - Get dashboard chart data
- `POST /notifications/{id}/read` - Mark notification as read

---

## 🤝 Contributing

This is a complete academic project. For modifications:

1. Create a new branch
2. Make changes
3. Test thoroughly
4. Submit for review

---

## 📝 License

This project is built for educational purposes.

---

## 📞 Support

For technical issues or questions about the system:

- Check the Laravel documentation: https://laravel.com/docs/12.x
- Review the code comments in controllers and models
- Check the database seeders for example data

---

## ✨ Credits

**Built with:**
- Laravel 12
- Bootstrap 5
- Chart.js
- DomPDF
- PHP 8.2+
- MySQL

**Developed as a complete competency-based internship placement system.**

---

## 🎯 Future Enhancements

Potential features for future versions:

- Email notifications (SMTP integration)
- SMS notifications for important updates
- Video interview scheduling
- AI-powered skill gap analysis
- Mobile app (React Native/Flutter)
- Advanced analytics with ML predictions
- Integration with LinkedIn
- Automated certificate verification
- Real-time chat with WebSockets
- Multi-language support

---

**Last Updated**: June 30, 2026
**Version**: 1.0.0

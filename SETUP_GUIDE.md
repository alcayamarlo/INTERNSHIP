# Skill-Bridge System - Complete Setup Guide

This guide will walk you through setting up the Skill-Bridge System from scratch on a Windows environment using XAMPP.

---

## 📋 Prerequisites Checklist

Before starting, ensure you have:

- ✅ **XAMPP** installed (includes Apache, MySQL, PHP 8.2+)
- ✅ **Composer** installed (https://getcomposer.org/download/)
- ✅ **Node.js & NPM** installed (https://nodejs.org/)
- ✅ **Git** (optional, for version control)
- ✅ **Modern web browser** (Chrome, Firefox, Edge, Safari)

---

## 🚀 Step-by-Step Setup

### Step 1: Start XAMPP Services

1. Open **XAMPP Control Panel**
2. Start **Apache** service
3. Start **MySQL** service
4. Verify both services show **green** status

### Step 2: Create Database

1. Open your web browser and navigate to: `http://localhost/phpmyadmin`
2. Click on **"New"** in the left sidebar
3. Enter database name: `nexus`
4. Select collation: `utf8mb4_unicode_ci`
5. Click **"Create"**

### Step 3: Navigate to Project Directory

Open Command Prompt (CMD) or PowerShell and navigate to your project:

```bash
cd c:\xampp\htdocs\nexus
```

### Step 4: Install PHP Dependencies

```bash
composer install
```

**Wait for completion.** This may take 2-5 minutes depending on your internet speed.

### Step 5: Install Node Dependencies

```bash
npm install
```

**Wait for completion.** This may take 3-7 minutes.

### Step 6: Configure Environment

1. Copy the environment template:
```bash
copy .env.example .env
```

2. Open `.env` file in a text editor and verify these settings:

```env
APP_NAME="Skill-Bridge"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nexus
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

**Note:** If your MySQL has a password, update `DB_PASSWORD=yourpassword`

### Step 7: Generate Application Key

```bash
php artisan key:generate
```

You should see: `Application key set successfully.`

### Step 8: Run Database Migrations

This creates all database tables:

```bash
php artisan migrate
```

You should see a list of migrations being executed. All should show **"DONE"** in green.

### Step 9: Seed Database with Demo Data

```bash
php artisan db:seed
```

This will create:
- ✅ Administrator account
- ✅ Coordinator account
- ✅ Employer account (TechNova Solutions)
- ✅ Student account (Anna Dela Cruz)
- ✅ Sample institution (Metro State University)
- ✅ Sample internships with requirements
- ✅ Sample competencies and skills
- ✅ Sample application

### Step 10: Create Storage Symlink

This links storage to public directory for file uploads:

```bash
php artisan storage:link
```

You should see: `The [public/storage] link has been connected to [storage/app/public].`

### Step 11: Build Frontend Assets

For production build:
```bash
npm run build
```

**OR** for development with hot reload (recommended during development):
```bash
npm run dev
```

**Note:** Keep this terminal window open if using `npm run dev`

### Step 12: Start Laravel Development Server

Open a **new** Command Prompt/PowerShell window:

```bash
cd c:\xampp\htdocs\nexus
php artisan serve
```

You should see:
```
INFO  Server running on [http://127.0.0.1:8000].
Press Ctrl+C to stop the server.
```

---

## 🎉 Access the Application

### Main Application
Open your browser and visit: **http://localhost:8000**

You should see the Skill-Bridge welcome page with:
- Hero section
- Features overview
- Login and Register buttons

### Login Credentials

#### Administrator Dashboard
- **URL**: http://localhost:8000/login
- **Email**: `admin@skillbridge.test`
- **Password**: `password`
- **Access**: User management, announcements, system logs, reports

#### Student Dashboard
- **URL**: http://localhost:8000/login
- **Email**: `student@skillbridge.test`
- **Password**: `password`
- **Access**: Profile, competencies, portfolio, resume, internship browsing, applications

#### Employer Dashboard
- **URL**: http://localhost:8000/login
- **Email**: `employer@skillbridge.test`
- **Password**: `password`
- **Access**: Company profile, internship postings, applicant management

#### Coordinator Dashboard
- **URL**: http://localhost:8000/login
- **Email**: `coordinator@skillbridge.test`
- **Password**: `password`
- **Access**: Student monitoring, placement reports

---

## ✅ Verification Checklist

After setup, verify the following:

### Database Verification
1. Go to http://localhost/phpmyadmin
2. Select `nexus` database
3. Verify these tables exist:
   - ✅ users
   - ✅ students
   - ✅ employers
   - ✅ coordinators
   - ✅ institutions
   - ✅ competencies
   - ✅ student_competencies
   - ✅ certificates
   - ✅ portfolios
   - ✅ resumes
   - ✅ internships
   - ✅ internship_requirements
   - ✅ internship_applications
   - ✅ notifications
   - ✅ messages
   - ✅ announcements
   - ✅ reports
   - ✅ system_logs

### Application Verification
1. ✅ Welcome page loads correctly
2. ✅ Login page is accessible
3. ✅ Can log in as student
4. ✅ Student dashboard shows recommendations
5. ✅ Can navigate to all student pages
6. ✅ Can log out and log in as employer
7. ✅ Employer dashboard displays correctly
8. ✅ Can create new internship posting
9. ✅ Can log in as administrator
10. ✅ Admin dashboard shows system statistics

---

## 🔧 Troubleshooting

### Issue: "Access denied for user 'root'@'localhost'"

**Solution:**
1. Open `.env` file
2. Update `DB_PASSWORD` with your MySQL root password
3. Run `php artisan config:clear`
4. Try migrations again

### Issue: "Base table or view not found"

**Solution:**
```bash
php artisan migrate:fresh --seed
```

This drops all tables and recreates them.

### Issue: "The public/storage link has already been connected"

**Solution:**
```bash
rmdir public\storage
php artisan storage:link
```

### Issue: Port 8000 already in use

**Solution:**
Run on a different port:
```bash
php artisan serve --port=8001
```

Then access at: http://localhost:8001

### Issue: "Class 'App\Enums\UserRole' not found"

**Solution:**
```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Issue: 500 Internal Server Error

**Solution:**
1. Check storage permissions:
```bash
icacls storage /grant Everyone:F /T
icacls bootstrap\cache /grant Everyone:F /T
```

2. Clear all caches:
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

3. Check `storage/logs/laravel.log` for detailed error messages

### Issue: CSS/JS not loading

**Solution:**
1. Rebuild assets:
```bash
npm run build
```

2. Clear browser cache (Ctrl+Shift+Delete)

3. Hard refresh page (Ctrl+F5)

---

## 📝 Development Workflow

### Daily Development Routine

1. **Start XAMPP** (Apache + MySQL)

2. **Start Laravel Server**:
```bash
cd c:\xampp\htdocs\nexus
php artisan serve
```

3. **Start Vite Dev Server** (for hot reload):
```bash
npm run dev
```

4. **Access Application**: http://localhost:8000

### Making Database Changes

1. Create migration:
```bash
php artisan make:migration create_something_table
```

2. Edit migration file in `database/migrations/`

3. Run migration:
```bash
php artisan migrate
```

4. To rollback:
```bash
php artisan migrate:rollback
```

### Adding New Features

1. **Create Controller**:
```bash
php artisan make:controller SomethingController
```

2. **Create Model**:
```bash
php artisan make:model Something
```

3. **Add Routes** in `routes/web.php`

4. **Create Views** in `resources/views/`

### Clearing Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

Or clear everything at once:
```bash
php artisan optimize:clear
```

---

## 🎯 Testing Features

### Test Student Workflow

1. Login as student (`student@skillbridge.test`)
2. Go to **Competencies** → Add new skills
3. Go to **Portfolio** → Upload certificates
4. Go to **Resume** → Generate resume PDF
5. Go to **Internships** → Browse available internships
6. Click on internship → View match percentage
7. Click **Apply** → Submit application
8. Go to **Applications** → Track application status

### Test Employer Workflow

1. Login as employer (`employer@skillbridge.test`)
2. Go to **Internships** → Create New Internship
3. Add title, description, requirements, skills needed
4. Go to **Applicants** → View student applications
5. Click on applicant → View profile, resume, portfolio
6. Download student resume
7. Update application status (Accept/Reject/Interview)

### Test Competency Matching

1. Login as student
2. Add competency: "Laravel Framework" → Proficiency: "Intermediate"
3. Go to **Internships**
4. Look for internships requiring Laravel
5. Notice the **match percentage** displayed
6. Higher match % = Better alignment with requirements

### Test Administrator Functions

1. Login as admin (`admin@skillbridge.test`)
2. Go to **Users** → View all users
3. Toggle user active status
4. Go to **Announcements** → Create system-wide announcement
5. Go to **System Logs** → View activity logs
6. Go to **Reports** → Generate system reports

---

## 📊 Database Reset Commands

### Soft Reset (Keep data, re-run seeds only)
```bash
php artisan db:seed
```

### Hard Reset (Drop all tables, recreate, seed)
```bash
php artisan migrate:fresh --seed
```

**⚠️ Warning**: This deletes ALL data!

---

## 🔐 Security Notes

### For Development
- Default password is `password` for all demo accounts
- Debug mode is **ON** (shows detailed errors)
- CSRF protection is **ENABLED**

### For Production
1. Change all default passwords
2. Set `APP_DEBUG=false` in `.env`
3. Set `APP_ENV=production` in `.env`
4. Use strong database password
5. Enable HTTPS
6. Run: `php artisan config:cache`
7. Run: `php artisan route:cache`
8. Run: `php artisan view:cache`

---

## 📚 Additional Resources

### Laravel Documentation
- https://laravel.com/docs/12.x

### Bootstrap 5 Documentation
- https://getbootstrap.com/docs/5.3/

### Chart.js Documentation
- https://www.chartjs.org/docs/

### PHP Documentation
- https://www.php.net/manual/en/

---

## 🆘 Getting Help

### Check Logs
```bash
type storage\logs\laravel.log
```

### Run Health Check
```bash
php artisan about
```

This shows:
- PHP version
- Laravel version
- Database connection
- Cache drivers
- Environment

---

## ✨ Next Steps

After successful setup:

1. **Explore the Interface**
   - Log in as different user roles
   - Test all features
   - Upload sample documents

2. **Customize Branding**
   - Update `resources/views/layouts/app.blade.php` for colors
   - Modify `resources/views/welcome.blade.php` for landing page
   - Change logo and branding text

3. **Add Real Data**
   - Register real students
   - Add actual institutions
   - Post real internships

4. **Configure Email** (Optional)
   - Set up SMTP in `.env`
   - Enable email notifications
   - Test password reset emails

---

**Setup Complete! 🎉**

You now have a fully functional competency-based internship placement system running locally.

**Default Access:** http://localhost:8000
**Demo Accounts:** See "Login Credentials" section above

---

**Last Updated**: June 30, 2026
**Version**: 1.0.0

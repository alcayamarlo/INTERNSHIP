# Getting Started with Skill-Bridge System

Welcome to the Skill-Bridge System! This guide will help you get started quickly.

---

## 🚀 Option 1: Automated Setup (Recommended)

The easiest way to get started is using the automated setup script.

### Steps:

1. **Ensure XAMPP is Running**
   - Open XAMPP Control Panel
   - Start **Apache**
   - Start **MySQL**

2. **Create Database**
   - Open browser: http://localhost/phpmyadmin
   - Click "New" in sidebar
   - Database name: `nexus`
   - Collation: `utf8mb4_unicode_ci`
   - Click "Create"

3. **Run Setup Script**
   ```bash
   cd c:\xampp\htdocs\nexus
   setup.bat
   ```

4. **Start the Application**
   ```bash
   php artisan serve
   ```

5. **Open in Browser**
   - URL: http://localhost:8000
   - Login with demo accounts (see below)

**That's it!** The system is now ready to use.

---

## 🔧 Option 2: Manual Setup

If you prefer step-by-step control:

### 1. Install Dependencies

```bash
cd c:\xampp\htdocs\nexus

# PHP dependencies
composer install

# Node dependencies
npm install
```

### 2. Configure Environment

```bash
# Copy environment file
copy .env.example .env

# Generate application key
php artisan key:generate
```

### 3. Setup Database

**Create database** in phpMyAdmin:
- Name: `nexus`
- Collation: `utf8mb4_unicode_ci`

**Update .env file**:
```env
DB_DATABASE=nexus
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Run Migrations

```bash
php artisan migrate --seed
```

### 5. Create Storage Link

```bash
php artisan storage:link
```

### 6. Build Frontend Assets

```bash
npm run build
```

### 7. Start Server

```bash
php artisan serve
```

### 8. Access Application

Open browser: **http://localhost:8000**

---

## 👤 Demo Accounts

### Administrator
- **Email**: admin@skillbridge.test
- **Password**: password
- **Access**: Full system control

### Student
- **Email**: student@skillbridge.test
- **Password**: password
- **Name**: Anna Dela Cruz
- **Institution**: Metro State University

### Employer
- **Email**: employer@skillbridge.test
- **Password**: password
- **Company**: TechNova Solutions

### Coordinator
- **Email**: coordinator@skillbridge.test
- **Password**: password
- **Name**: Maria Santos

---

## 🎯 First Steps After Login

### As Student

1. **Complete Your Profile**
   - Go to "Profile" in sidebar
   - Fill in personal information
   - Upload profile picture
   - Add career objectives

2. **Add Competencies**
   - Go to "Competencies"
   - Click "Add Competency"
   - Add your skills with proficiency levels
   - Example: PHP - Advanced, Laravel - Intermediate

3. **Upload Certificates**
   - Go to "Portfolio"
   - Click "Upload Certificate"
   - Add certificates, projects, awards

4. **Generate Resume**
   - Go to "Resume"
   - Click "Generate Resume"
   - Download as PDF

5. **Browse Internships**
   - Go to "Internships"
   - See match percentages
   - Click on internship to view details
   - Click "Apply" to submit application

6. **Track Applications**
   - Go to "Applications"
   - View status of submitted applications

### As Employer

1. **Setup Company Profile**
   - Go to "Company Profile"
   - Fill in company details
   - Upload company logo

2. **Create Internship Posting**
   - Go to "Internships"
   - Click "Create Internship"
   - Fill in details:
     - Title
     - Description
     - Responsibilities
     - Requirements
     - Required skills (with proficiency levels)
     - Duration
     - Allowance
     - Work setup (Remote/Hybrid/Onsite)

3. **Review Applicants**
   - Go to "Applicants"
   - View applications
   - See match percentages
   - Review student profiles
   - Download resumes
   - Update application status

### As Coordinator

1. **View Students**
   - Go to "Students"
   - See all students in your institution
   - View student profiles
   - Track application progress

2. **Generate Reports**
   - Go to "Reports"
   - Select report type:
     - Placement Report
     - Student Progress
     - Competency Analytics
   - Choose date range
   - Export as PDF or Excel

### As Administrator

1. **View System Dashboard**
   - See system-wide statistics
   - User growth
   - Application trends

2. **Manage Users**
   - Go to "Users"
   - View all users
   - Toggle active/inactive status
   - Delete users if needed

3. **Create Announcements**
   - Go to "Announcements"
   - Click "Create Announcement"
   - Target specific roles or all users

4. **View System Logs**
   - Go to "System Logs"
   - Monitor user activities
   - Track login attempts
   - Review system events

---

## 🔍 Testing the Competency Matching

### Test Scenario

1. **Login as Student**
   - Email: student@skillbridge.test

2. **Add Some Competencies**
   - PHP Development - Advanced
   - Laravel Framework - Intermediate
   - Database Design - Intermediate
   - Communication - Advanced

3. **Browse Internships**
   - Go to "Internships"
   - Notice the "Web Developer Intern" posting
   - See the **match percentage** (should be 85%+)
   - Click to view details
   - See the breakdown of matching competencies

4. **Apply to Internship**
   - Click "Apply Now"
   - Write a cover letter
   - Submit application

5. **Logout and Login as Employer**
   - Email: employer@skillbridge.test

6. **View Applicants**
   - Go to "Applicants"
   - See the student application
   - View match percentage
   - Click to view student profile
   - Download resume
   - Update status to "Reviewed" or "Interview"

7. **Logout and Login as Student Again**
   - Go to "Applications"
   - See updated status
   - Check notifications

---

## 📱 Responsive Design Testing

Test the application on different screen sizes:

1. **Desktop** (1920x1080)
   - Sidebar visible
   - Full dashboard layout

2. **Tablet** (768x1024)
   - Sidebar collapses to hamburger menu
   - Cards stack vertically

3. **Mobile** (375x667)
   - Hamburger menu
   - Touch-friendly buttons
   - Stacked layout

**Browser DevTools**: Press F12 → Toggle device toolbar

---

## 🎨 UI Features to Explore

### Notifications
- Click bell icon in top bar
- See unread count badge
- Click "Mark all as read"

### Messaging
- Go to "Messages"
- Start conversation with another user
- Send messages
- See read/unread status

### Search
- Use search bar in top navigation
- Search for students, companies, skills
- See instant results

### Charts & Analytics
- View dashboard charts
- Hover over chart elements
- See interactive tooltips

### Toast Notifications
- Perform actions (add competency, apply to internship)
- See success toast in top-right corner

---

## 🔧 Common Tasks

### Adding New Competency
1. Go to Competencies page
2. Click "Add Competency"
3. Fill form:
   - Name: "React.js"
   - Category: Technical
   - Proficiency: Intermediate
   - Description: "Built SPAs with React"
4. Click "Save"

### Creating Internship
1. Go to Internships (Employer)
2. Click "Create Internship"
3. Fill all fields
4. Add required competencies
5. Click "Publish"

### Updating Application Status
1. Go to Applicants (Employer)
2. Click on an application
3. Select new status from dropdown
4. Add employer notes (optional)
5. Save interview date (if status is Interview)
6. Click "Update Status"

### Generating Report
1. Go to Reports (Coordinator)
2. Select report type
3. Choose date range
4. Select filters
5. Choose format (PDF or Excel)
6. Click "Generate Report"

---

## 🐛 Troubleshooting

### Database Connection Error
**Symptom**: "Connection refused" or "Access denied"

**Solution**:
1. Check XAMPP MySQL is running
2. Verify .env credentials:
   ```env
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=nexus
   DB_USERNAME=root
   DB_PASSWORD=
   ```
3. Run: `php artisan config:clear`

### Page Not Found (404)
**Symptom**: Routes not working

**Solution**:
1. Clear route cache: `php artisan route:clear`
2. Verify server is running: `php artisan serve`
3. Check URL: http://localhost:8000 (not 127.0.0.1:8000)

### CSS/JS Not Loading
**Symptom**: Unstyled pages

**Solution**:
1. Build assets: `npm run build`
2. Clear browser cache: Ctrl+Shift+Delete
3. Hard refresh: Ctrl+F5

### Storage Link Missing
**Symptom**: Uploaded images not showing

**Solution**:
```bash
# Remove old link
rmdir public\storage

# Create new link
php artisan storage:link
```

### Can't Login
**Symptom**: Invalid credentials error

**Solution**:
1. Verify using correct demo account email
2. Password is: `password`
3. Check database has users: `php artisan db:show`
4. Re-seed: `php artisan migrate:fresh --seed`

---

## 📚 Next Steps

Once you're comfortable with the basics:

1. **Explore All Features**
   - Test every module
   - Try all CRUD operations
   - Generate reports

2. **Read Documentation**
   - README.md - Overview
   - FEATURES.md - Complete feature list
   - ARCHITECTURE.md - Technical details

3. **Customize the System**
   - Change colors in layout files
   - Modify welcome page
   - Add custom fields

4. **Add Real Data**
   - Register actual students
   - Add real institutions
   - Create real internship postings

5. **Deploy to Production**
   - Follow production deployment guide
   - Setup HTTPS
   - Configure email
   - Enable backups

---

## 🎓 Learning Resources

### Laravel Documentation
- Official Docs: https://laravel.com/docs/12.x
- Blade Templates: https://laravel.com/docs/12.x/blade
- Eloquent ORM: https://laravel.com/docs/12.x/eloquent

### Bootstrap Documentation
- Official Docs: https://getbootstrap.com/docs/5.3/
- Components: https://getbootstrap.com/docs/5.3/components/
- Utilities: https://getbootstrap.com/docs/5.3/utilities/

### Chart.js Documentation
- Official Docs: https://www.chartjs.org/docs/

---

## 💡 Pro Tips

1. **Use the Search Feature** - Quickly find anything
2. **Check Notifications** - Stay updated on activities
3. **Match Percentage** - Higher match = Better fit
4. **Complete Profile** - Better recommendations
5. **Add Many Competencies** - Increases matches
6. **Use Filters** - Find internships faster
7. **Save Reports** - Export for records
8. **Check Logs** - Monitor system activity

---

## ✅ Verification Script

Run the verification script to check your setup:

```bash
verify.bat
```

This will check:
- PHP version
- Database connection
- File permissions
- Dependencies
- Configuration

---

## 🆘 Getting Help

### Check Logs
```bash
# View Laravel logs
type storage\logs\laravel.log

# View last 50 lines
powershell -command "Get-Content storage\logs\laravel.log -Tail 50"
```

### Run Health Check
```bash
php artisan about
```

Shows:
- PHP version
- Laravel version
- Environment
- Database connection
- Cache drivers

### Clear Everything
```bash
php artisan optimize:clear
```

Clears:
- Config cache
- Route cache
- View cache
- Application cache

---

## 📞 Support

For technical issues:
1. Check SETUP_GUIDE.md
2. Review error logs
3. Verify database connection
4. Check file permissions
5. Clear all caches

---

## 🎉 You're Ready!

You now have everything you need to use the Skill-Bridge System. Happy exploring!

**Quick Links**:
- Application: http://localhost:8000
- phpMyAdmin: http://localhost/phpmyadmin
- Laravel Docs: https://laravel.com/docs/12.x

---

**Last Updated**: June 30, 2026  
**Version**: 1.0.0

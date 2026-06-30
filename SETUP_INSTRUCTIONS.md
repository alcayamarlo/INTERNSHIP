# ⚡ SKILL-BRIDGE - SETUP INSTRUCTIONS

## 🚀 Complete Setup Guide (First Time)

### Step 1: Install Dependencies
```bash
composer install
```

### Step 2: Create .env File
```bash
cp .env.example .env
php artisan key:generate
```

### Step 3: Configure Database
Edit `.env` file:
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nexus
DB_USERNAME=root
DB_PASSWORD=
```

### Step 4: Run Migrations & Seed
```bash
php artisan migrate:fresh --seed
```

This command does:
- Drops all existing tables ⚠️ (be careful!)
- Runs all migrations
- Seeds demo data

### Step 5: Build Assets
```bash
npm install
npm run build
```

### Step 6: Start Server
```bash
php artisan serve
```

### Step 7: Access Application
- **URL**: http://localhost:8000
- **Login**: http://localhost:8000/login

---

## 👤 Demo Credentials

All accounts use password: `password`

| Role | Email |
|------|-------|
| Admin | admin@skillbridge.test |
| Coordinator | coordinator@skillbridge.test |
| Employer | employer@skillbridge.test |
| Student | student@skillbridge.test |

---

## ⚠️ Common Issues & Solutions

### Issue 1: "Table 'institutions' doesn't exist"

**Cause**: Migrations haven't been run

**Solution**:
```bash
php artisan migrate:fresh --seed
```

### Issue 2: Database Connection Error

**Cause**: Wrong database credentials in .env

**Solution**:
1. Check `.env` has correct DB_DATABASE, DB_USERNAME, DB_PASSWORD
2. Verify MySQL is running
3. Create database:
   ```sql
   CREATE DATABASE nexus;
   ```
4. Run migrations again:
   ```bash
   php artisan migrate:fresh --seed
   ```

### Issue 3: "Unknown column 'role' in field list"

**Cause**: Database wasn't properly reset

**Solution**:
```bash
php artisan migrate:fresh --seed
```

### Issue 4: Permission Denied on storage/

**Solution**:
```bash
chmod -R 775 storage bootstrap/cache
```

### Issue 5: npm: command not found

**Solution**:
- Install Node.js from https://nodejs.org
- Run `npm install` again

---

## 🔄 Development Workflow

### Daily Start
```bash
# Terminal 1: Start PHP server
php artisan serve

# Terminal 2: Watch assets (in another terminal)
npm run watch

# Terminal 3: View logs
tail -f storage/logs/laravel.log
```

### When You Make Changes
- **PHP/Laravel**: Auto-reloads (no action needed)
- **CSS/JavaScript**: Auto-builds if running `npm run watch`
- **Database schema**: Run migrations: `php artisan migrate`
- **Cache issues**: Clear cache: `php artisan cache:clear`

---

## 🗑️ Database Reset

### Reset Everything (Development Only ⚠️)
```bash
php artisan migrate:fresh --seed
```

### Rollback Last Migration
```bash
php artisan migrate:rollback
```

### Rollback All Migrations
```bash
php artisan migrate:reset
```

### Check Migration Status
```bash
php artisan migrate:status
```

---

## 🧪 Testing the Setup

### Verify Database
```bash
# Check tables exist
php artisan tinker
>>> DB::table('users')->count()    // Should show 4
>>> DB::table('institutions')->count()  // Should show 1
>>> exit
```

### Test Login
1. Go to http://localhost:8000/login
2. Enter: admin@skillbridge.test / password
3. Should redirect to /admin/dashboard

### Test Registration
1. Go to http://localhost:8000/register
2. Fill in form (pick Student role)
3. Should redirect to /student/dashboard

### Test Password Reset
1. Go to http://localhost:8000/forgot-password
2. Enter: admin@skillbridge.test
3. Should show "Reset link sent" message

---

## 📚 Important Commands

```bash
# Migrations
php artisan migrate              # Run pending migrations
php artisan migrate:fresh        # Reset all + re-run
php artisan migrate:fresh --seed # Reset + seed demo data
php artisan migrate:status       # Check migration status

# Cache
php artisan cache:clear          # Clear all cache
php artisan view:clear           # Clear view cache
php artisan route:clear          # Clear route cache
php artisan config:cache         # Cache config (production)

# Development
php artisan serve                # Start dev server
php artisan tinker              # Interactive shell

# Assets
npm run watch                    # Watch for changes
npm run build                    # Build for production
npm run dev                      # Build for development

# Database
php artisan db:seed              # Run seeders
php artisan db:seed --class=DatabaseSeeder  # Specific seeder

# Debugging
tail -f storage/logs/laravel.log # View logs
php artisan diagnostics          # Check system
```

---

## 🔐 Security Checklist (Before Production)

- [ ] `.env` has `APP_ENV=production`
- [ ] `.env` has `APP_DEBUG=false`
- [ ] All default credentials changed
- [ ] Email service configured
- [ ] Session timeout increased
- [ ] HTTPS enabled
- [ ] Database backup created
- [ ] Error logs not publicly accessible

---

## 📊 Verify Complete Setup

Run this to verify everything:

```bash
# Check migrations
php artisan migrate:status

# Check key files exist
ls -la bootstrap/app.php
ls -la config/app.php
ls -la routes/web.php

# Check database tables
php artisan tinker
>>> Schema::getColumnListing('users')
>>> DB::table('users')->count()
>>> exit
```

---

## 🎯 You're Ready!

If you've completed all steps without errors, you're ready to:
1. Login with demo credentials
2. Test registration flow
3. Explore the dashboards
4. Start Phase 4 development

---

## 📞 Troubleshooting Checklist

- [ ] MySQL running?
- [ ] Database created: `CREATE DATABASE nexus;`
- [ ] .env configured with correct DB credentials?
- [ ] Migrations ran: `php artisan migrate:status`
- [ ] Seeds applied: Check demo users exist
- [ ] Storage permissions: `chmod -R 775 storage`
- [ ] Node.js installed: `node --version`
- [ ] Assets built: `npm run build`

---

**Last Updated**: June 30, 2026
**Status**: Ready for Development

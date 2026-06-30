# Skill-Bridge System - Deployment Checklist

Use this checklist to ensure proper deployment of the Skill-Bridge System.

---

## 📋 Pre-Deployment Checklist

### Environment Setup

- [ ] PHP 8.2+ installed
- [ ] Composer installed
- [ ] Node.js & NPM installed
- [ ] MySQL 5.7+ or MariaDB 10.3+ installed
- [ ] Web server (Apache/Nginx) configured
- [ ] SSL certificate obtained (for HTTPS)

### Application Setup

- [ ] Project files copied to server
- [ ] `.env` file created and configured
- [ ] `APP_KEY` generated
- [ ] Database created
- [ ] Database credentials configured in `.env`
- [ ] Composer dependencies installed (`composer install --optimize-autoloader --no-dev`)
- [ ] NPM dependencies installed (`npm install`)
- [ ] Frontend assets built (`npm run build`)
- [ ] Database migrated (`php artisan migrate --force`)
- [ ] Storage link created (`php artisan storage:link`)

### File Permissions

- [ ] `storage/` directory writable (755)
- [ ] `storage/framework/` subdirectories writable
- [ ] `storage/logs/` writable
- [ ] `bootstrap/cache/` writable (755)
- [ ] `public/` readable (755)

### Security Configuration

- [ ] `APP_ENV=production` in `.env`
- [ ] `APP_DEBUG=false` in `.env`
- [ ] Strong `APP_KEY` generated
- [ ] Database password is strong and secure
- [ ] Default user passwords changed
- [ ] HTTPS enabled and enforced
- [ ] CSRF protection verified
- [ ] File upload validation tested

### Performance Optimization

- [ ] Config cached (`php artisan config:cache`)
- [ ] Routes cached (`php artisan route:cache`)
- [ ] Views cached (`php artisan view:cache`)
- [ ] OPcache enabled in PHP
- [ ] Composer autoloader optimized
- [ ] Assets minified and compressed
- [ ] CDN configured (optional)

---

## 🚀 Deployment Steps

### Step 1: Server Preparation

1. **Update System**
   ```bash
   sudo apt update && sudo apt upgrade -y
   ```

2. **Install Required Software**
   ```bash
   # PHP and extensions
   sudo apt install php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd
   
   # Composer
   curl -sS https://getcomposer.org/installer | php
   sudo mv composer.phar /usr/local/bin/composer
   
   # Node.js
   curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
   sudo apt install -y nodejs
   ```

3. **Install and Configure MySQL**
   ```bash
   sudo apt install mysql-server
   sudo mysql_secure_installation
   ```

4. **Install Web Server (Apache)**
   ```bash
   sudo apt install apache2
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

### Step 2: Project Deployment

1. **Clone or Upload Project**
   ```bash
   cd /var/www/
   # Upload via FTP or clone from Git
   ```

2. **Set Ownership**
   ```bash
   sudo chown -R www-data:www-data /var/www/nexus
   ```

3. **Set Permissions**
   ```bash
   cd /var/www/nexus
   sudo chmod -R 755 storage bootstrap/cache
   ```

4. **Install Dependencies**
   ```bash
   composer install --optimize-autoloader --no-dev
   npm install
   npm run build
   ```

5. **Configure Environment**
   ```bash
   cp .env.example .env
   nano .env
   ```

   Update:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   
   DB_DATABASE=your_database
   DB_USERNAME=your_username
   DB_PASSWORD=your_secure_password
   ```

6. **Generate Key**
   ```bash
   php artisan key:generate
   ```

7. **Run Migrations**
   ```bash
   php artisan migrate --force
   ```

8. **Create Storage Link**
   ```bash
   php artisan storage:link
   ```

9. **Optimize Application**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

### Step 3: Web Server Configuration

#### Apache Virtual Host

Create file: `/etc/apache2/sites-available/skillbridge.conf`

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    
    DocumentRoot /var/www/nexus/public
    
    <Directory /var/www/nexus/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/skillbridge-error.log
    CustomLog ${APACHE_LOG_DIR}/skillbridge-access.log combined
</VirtualHost>
```

Enable site:
```bash
sudo a2ensite skillbridge
sudo systemctl reload apache2
```

#### Nginx Configuration

Create file: `/etc/nginx/sites-available/skillbridge`

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/nexus/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/skillbridge /etc/nginx/sites-enabled/
sudo systemctl reload nginx
```

### Step 4: SSL Certificate (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com
```

Certbot will automatically:
- Obtain certificate
- Configure Apache/Nginx for HTTPS
- Setup auto-renewal

### Step 5: Database Backup Setup

Create backup script: `/var/www/nexus/backup.sh`

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/nexus"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="your_database"
DB_USER="your_username"
DB_PASS="your_password"

mkdir -p $BACKUP_DIR
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/backup_$DATE.sql.gz

# Keep only last 7 days
find $BACKUP_DIR -type f -mtime +7 -delete
```

Make executable:
```bash
chmod +x /var/www/nexus/backup.sh
```

Add to crontab (daily at 2 AM):
```bash
0 2 * * * /var/www/nexus/backup.sh
```

---

## ✅ Post-Deployment Verification

### Functional Tests

- [ ] Homepage loads correctly
- [ ] Login page accessible
- [ ] Can login as administrator
- [ ] Can login as student
- [ ] Can login as employer
- [ ] Can login as coordinator
- [ ] Student dashboard loads
- [ ] Employer dashboard loads
- [ ] Can add competency
- [ ] Can upload certificate
- [ ] Can create internship posting
- [ ] Can apply to internship
- [ ] Notifications work
- [ ] Messaging works
- [ ] Search functionality works
- [ ] Charts render correctly
- [ ] File uploads work
- [ ] PDF generation works (resume)
- [ ] Report export works
- [ ] Logout works

### Security Tests

- [ ] HTTPS working (lock icon in browser)
- [ ] HTTP redirects to HTTPS
- [ ] CSRF protection working
- [ ] Can't access admin pages without admin role
- [ ] Can't access student pages as employer
- [ ] File upload restrictions enforced
- [ ] SQL injection attempts blocked
- [ ] XSS attempts blocked

### Performance Tests

- [ ] Page load time < 3 seconds
- [ ] Database queries optimized (check query log)
- [ ] Images load quickly
- [ ] No 404 errors for assets
- [ ] Server response time acceptable

### Browser Tests

- [ ] Works in Chrome
- [ ] Works in Firefox
- [ ] Works in Edge
- [ ] Works in Safari
- [ ] Responsive on mobile (375px)
- [ ] Responsive on tablet (768px)
- [ ] Responsive on desktop (1920px)

---

## 🔧 Production Configuration

### Recommended .env Settings

```env
# Application
APP_NAME="Skill-Bridge"
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_secure_password

# Cache
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Email (SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Logging
LOG_CHANNEL=daily
LOG_LEVEL=error

# Session
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
```

---

## 📊 Monitoring Setup

### Log Monitoring

1. **Laravel Logs**
   ```bash
   tail -f /var/www/nexus/storage/logs/laravel.log
   ```

2. **Web Server Logs**
   ```bash
   # Apache
   tail -f /var/log/apache2/skillbridge-error.log
   
   # Nginx
   tail -f /var/log/nginx/error.log
   ```

### Uptime Monitoring

Services to consider:
- UptimeRobot (free)
- Pingdom
- StatusCake
- New Relic

### Performance Monitoring

- **Laravel Telescope** (development only)
- **Laravel Horizon** (for queues)
- **Blackfire.io** (profiling)
- **New Relic APM**

---

## 🔄 Maintenance Tasks

### Daily
- [ ] Check error logs
- [ ] Monitor disk space
- [ ] Verify backups completed

### Weekly
- [ ] Review application logs
- [ ] Check database size
- [ ] Test backup restoration
- [ ] Update content (announcements)

### Monthly
- [ ] Update dependencies (`composer update`, `npm update`)
- [ ] Security audit
- [ ] Performance review
- [ ] Database optimization
- [ ] Review user feedback

---

## 🆘 Emergency Procedures

### Application Down

1. Check web server status:
   ```bash
   sudo systemctl status apache2
   # or
   sudo systemctl status nginx
   ```

2. Check database:
   ```bash
   sudo systemctl status mysql
   ```

3. Check Laravel logs:
   ```bash
   tail -100 storage/logs/laravel.log
   ```

4. Restart services if needed:
   ```bash
   sudo systemctl restart apache2
   sudo systemctl restart mysql
   ```

### Database Corruption

1. Stop application
2. Restore from backup:
   ```bash
   mysql -u username -p database_name < backup.sql
   ```
3. Test restoration
4. Restart application

### Security Breach

1. Take site offline immediately
2. Change all passwords (database, admin users)
3. Review logs for suspicious activity
4. Patch vulnerability
5. Restore from clean backup if needed
6. Notify users if data compromised

---

## 📞 Support Contacts

### Technical Support
- System Administrator: admin@yourdomain.com
- Developer: dev@yourdomain.com
- Hosting Provider: support@hostingprovider.com

### Emergency
- On-call: +1-XXX-XXX-XXXX
- Escalation: escalation@yourdomain.com

---

## 📝 Change Default Credentials

**CRITICAL**: Change all default passwords immediately after deployment!

```sql
-- Update admin password
UPDATE users SET password = '$2y$12$new_hashed_password' WHERE email = 'admin@skillbridge.test';

-- Or via Artisan tinker
php artisan tinker
> $user = App\Models\User::where('email', 'admin@skillbridge.test')->first();
> $user->password = Hash::make('new-secure-password');
> $user->save();
```

---

## ✅ Final Checklist

Before going live:

- [ ] All tests pass
- [ ] All default passwords changed
- [ ] HTTPS working
- [ ] Backups configured
- [ ] Monitoring setup
- [ ] Error reporting configured
- [ ] Email sending working
- [ ] File uploads working
- [ ] Performance optimized
- [ ] Security hardened
- [ ] Documentation updated
- [ ] Team trained
- [ ] Support channels established

---

## 🎉 Go Live!

Once all checks pass:

1. **Final backup**
   ```bash
   ./backup.sh
   ```

2. **Enable production mode**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

3. **Announce to users**
   - Send welcome emails
   - Post announcement
   - Update social media

4. **Monitor closely for first 24 hours**
   - Watch error logs
   - Monitor performance
   - Check user feedback

---

**Deployment Date**: __________  
**Deployed By**: __________  
**Version**: 1.0.0  

---

**Good luck with your deployment!** 🚀

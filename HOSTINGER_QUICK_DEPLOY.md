# 🚀 QUICK DEPLOYMENT TO HOSTINGER (No Local PHP/Composer Needed)

## Your Credentials
- **Domain**: `outreach.click`
- **Email**: `admin@outreach.click` / `outreach@123`
- **SMTP**: `smtp.hostinger.com:465` (SSL)
- **Database**: `u311221303_Haseeb` / `u311221303_vps` / `@Haseeb786@!$`

---

## STEP 1: Prepare Database in hPanel (Do This First)

1. Login to **hPanel** → **Databases** → **MySQL Databases**
2. Create database: `u311221303_Haseeb`
3. Create user: `u311221303_vps` with password: `@Haseeb786@!$`
4. **Assign user to database** with **All Privileges**
5. Note the database host: `127.0.0.1` (not localhost)

---

## STEP 2: Upload Files via Hostinger File Manager

### Option A: Using File Manager (Easiest)
1. **hPanel** → **Files** → **File Manager**
2. Navigate to `public_html`
3. **Delete existing files** in `public_html` (backup first if needed)
4. **Upload** the project folder contents to `public_html/`
   - Select all files from `C:\Users\Haseeb\Desktop\new  php\` 
   - **EXCEPT**: `.git`, `node_modules`, `tests`, `.env*`, `storage/logs/*`, `storage/framework/*`, `bootstrap/cache/*`
5. **Move contents** of `public_html/public/` to `public_html/`
6. **Delete** the now-empty `public_html/public` folder

### Option B: Using FTP (FileZilla)
- Host: `ftp.outreach.click` (or your server IP)
- User: `u311221303_vps` (or your FTP username)
- Password: `@Haseeb786@!$`
- Upload to `/public_html/`

---

## STEP 3: Create .env File on Server

In **File Manager**, create new file `public_html/.env`:

```env
APP_NAME="Outreach CRM"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://outreach.click

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u311221303_Haseeb
DB_USERNAME=u311221303_vps
DB_PASSWORD=@Haseeb786@!$

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=admin@outreach.click
MAIL_PASSWORD=outreach@123
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="admin@outreach.click"
MAIL_FROM_NAME="${APP_NAME}"

SANCTUM_STATEFUL_DOMAINS=outreach.click
SESSION_DOMAIN=outreach.click
SESSION_SECURE_COOKIE=true

CRM_DEFAULT_TIMEZONE=UTC
CRM_DAILY_SEND_LIMIT=200
CRM_BATCH_SIZE=50
CRM_MAX_RETRIES=3
CRM_RETRY_DELAY=300
APP_TIMEZONE=UTC
```

---

## STEP 4: Run Commands via Hostinger Terminal (SSH)

1. **hPanel** → **Advanced** → **Terminal** (or SSH)
2. Run these commands **one by one**:

```bash
# Navigate to project
cd /home/u311221303/public_html

# Install PHP dependencies (Hostinger has composer)
composer install --optimize-autoloader --no-dev

# Generate app key
php artisan key:generate --force

# Run migrations
php artisan migrate --force

# Seed demo data (optional)
php artisan db:seed --force

# Create storage link
php artisan storage:link

# Set permissions
chmod -R 775 storage bootstrap/cache
chmod 640 .env

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## STEP 5: Configure Cron Job (Critical for Email Automation)

**hPanel** → **Advanced** → **Cron Jobs** → Add New:

```
Command: /usr/bin/php /home/u311221303/public_html/artisan schedule:run >> /dev/null 2>&1
Interval: * * * * * (Every minute)
```

**Verify it works**: In Terminal, run:
```bash
php artisan schedule:run
php artisan schedule:list
```

---

## STEP 6: Force HTTPS & Document Root

### Fix Document Root (if site shows directory listing):
Create/edit `public_html/.htaccess`:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

### Force HTTPS (in same .htaccess):
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## STEP 7: Test Email Configuration

In Terminal:
```bash
php artisan tinker
>>> Mail::raw('Test from Outreach CRM', function($m) { $m->to('admin@outreach.click')->subject('Test Email'); })
```

Check your inbox at `admin@outreach.click`.

---

## STEP 8: Verify Deployment

Visit: **https://outreach.click**

**Login**: `admin@outreach.click` / `password`

You should see:
- ✅ Dashboard with stats
- ✅ Leads, Companies, Campaigns menus
- ✅ Email Accounts (configure SMTP test)
- ✅ Pipeline, Tasks, Reports

---

## 🔧 TROUBLESHOOTING

| Issue | Fix |
|-------|-----|
| 500 Error | Check `storage/logs/laravel.log` |
| Database error | Verify `.env` DB credentials match hPanel exactly |
| Cron not running | Run `php artisan schedule:run` manually first |
| Emails not sending | Test SMTP in Email Accounts page; check `MAIL_PASSWORD` |
| Assets not loading | Run `php artisan storage:link` and check `.htaccess` |
| Permission denied | `chmod -R 775 storage bootstrap/cache` |

---

## 📁 FILES TO UPLOAD (from C:\Users\Haseeb\Desktop\new  php\)

**Upload ALL except:**
- ❌ `.git/`
- ❌ `node_modules/`
- ❌ `tests/`
- ❌ `.env*`
- ❌ `storage/logs/*`
- ❌ `storage/framework/cache/*`
- ❌ `storage/framework/sessions/*`
- ❌ `storage/framework/views/*`
- ❌ `bootstrap/cache/*`
- ❌ `*.md` (optional)
- ❌ `DEPLOYMENT.md`

**Key folders to upload:**
- ✅ `app/`
- ✅ `bootstrap/`
- ✅ `config/`
- ✅ `database/`
- ✅ `public/` (contents go to public_html root)
- ✅ `resources/`
- ✅ `routes/`
- ✅ `storage/`
- ✅ `vendor/` (after composer install on server)
- ✅ `artisan`
- ✅ `composer.json` / `composer.lock`
- ✅ `package.json` / `vite.config.js`

---

## 🎯 QUICK SSH COMMANDS REFERENCE

```bash
# Check PHP version
php -v

# Check composer
composer --version

# Clear all caches
php artisan optimize:clear

# Rebuild caches
php artisan config:cache && php artisan route:cache && php artisan view:cache

# Check queue
php artisan queue:work --once

# Test email
php artisan tinker --execute="Mail::raw('Test', fn(\$m) => \$m->to('admin@outreach.click')->subject('Test'))"
```

---

**Need help?** Check `storage/logs/laravel.log` for errors. The terminal in hPanel gives you full SSH access to run all Laravel commands.
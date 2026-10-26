# Outreach CRM - Hostinger Shared Hosting Deployment Guide

## Prerequisites

Before deploying, ensure you have:
- Hostinger shared hosting account with SSH access enabled
- Domain name pointed to Hostinger nameservers
- PHP 8.2+ available on your hosting plan
- MySQL/MariaDB database created
- Composer available (or ability to upload vendor folder)

## Server Requirements

### PHP Extensions Required
- BCMath
- Ctype
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- PDO MySQL
- Tokenizer
- XML
- ZIP
- GD or Imagick (for image processing)
- Intl (recommended)

### Hostinger Plan Verification
Check your hosting plan supports:
- ✅ PHP 8.2+
- ✅ MySQL/MariaDB
- ✅ Cron Jobs (for scheduler)
- ✅ SSH Access
- ✅ Composer (or ability to run `composer install` locally)
- ✅ 512MB+ memory limit
- ✅ 30+ second max execution time

## Step 1: Local Development Setup

```bash
# Clone or navigate to project
cd outreach-crm

# Install dependencies
composer install --optimize-autoloader --no-dev

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Seed database (optional)
php artisan db:seed --force

# Build frontend assets
npm install && npm run build

# Create storage link
php artisan storage:link
```

## Step 2: Database Setup on Hostinger

### Create Database via hPanel
1. Log into hPanel
2. Go to **Databases** → **MySQL Databases**
3. Create new database: `your_database_name`
4. Create user: `your_database_user` with a secure password
5. Assign user to database with **All Privileges**

### Import Schema (if not using migrations)
```bash
# On local machine, export schema
php artisan schema:dump --prune

# Or use mysqldump
mysqldump -u local_user -p local_db --no-data > schema.sql
```

## Step 3: Prepare Production Environment

### Create `.env` file for production
```env
APP_NAME="Outreach CRM"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_secure_db_password

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="${APP_NAME}"

SANCTUM_STATEFUL_DOMAINS=your-domain.com
SESSION_DOMAIN=your-domain.com
SESSION_SECURE_COOKIE=true

CRM_DEFAULT_TIMEZONE=UTC
CRM_DAILY_SEND_LIMIT=200
CRM_BATCH_SIZE=50
CRM_MAX_RETRIES=3
CRM_RETRY_DELAY=300
```

## Step 4: Upload Files to Hostinger

### Option A: Using SSH (Recommended)

```bash
# Create deployment archive (exclude unnecessary files)
tar -czf deploy.tar.gz \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='tests' \
    --exclude='.env*' \
    --exclude='*.log' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='bootstrap/cache/*' \
    .

# Upload to Hostinger
scp deploy.tar.gz your_username@your-server:/home/your_username/

# On server, extract
ssh your_username@your-server
cd /home/your_username
tar -xzf deploy.tar.gz -C public_html/
```

### Option B: Using FTP/File Manager
1. Run `composer install --optimize-autoloader --no-dev` locally
2. Run `npm run build` locally
3. Upload all files **except**:
   - `.git/`
   - `node_modules/`
   - `tests/`
   - `.env*`
   - `storage/logs/*`
   - `storage/framework/cache/*`
   - `storage/framework/sessions/*`
   - `storage/framework/views/*`
   - `bootstrap/cache/*`
4. Upload to `public_html/` directory

## Step 5: Configure Document Root

### Important: Point Document Root to `/public`

In hPanel:
1. Go to **Hosting** → **Manage** → **Website** → **Document Root**
2. Set document root to: `public_html/public`
3. Or create `.htaccess` in `public_html/`:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

## Step 6: Set File Permissions

```bash
# On server via SSH
cd /home/your_username/public_html

# Storage and cache writable
chmod -R 775 storage bootstrap/cache
chown -R your_username:your_username storage bootstrap/cache

# Ensure .env is not publicly accessible
chmod 640 .env
```

## Step 7: Run Production Commands

```bash
cd /home/your_username/public_html

# Generate key if not set
php artisan key:generate --force

# Run migrations
php artisan migrate --force

# Seed database (optional)
php artisan db:seed --force

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Create storage link
php artisan storage:link

# Clear any cached config
php artisan optimize:clear
```

## Step 8: Configure Cron Job for Scheduler

In hPanel → **Advanced** → **Cron Jobs**:

```bash
# Add this command (runs every minute)
* * * * * /usr/bin/php /home/your_username/public_html/artisan schedule:run >> /dev/null 2>&1
```

**Note**: Replace `/home/your_username/public_html` with your actual path.
Find your path by running `pwd` in your project directory via SSH.

### Verify Cron Works
```bash
# Test manually
php artisan schedule:run

# Check scheduled tasks
php artisan schedule:list
```

## Step 9: Configure Queue Worker (Optional but Recommended)

Since shared hosting doesn't support persistent workers, use cron-based queue processing:

### Add to Crontab (every 5 minutes)
```bash
*/5 * * * * /usr/bin/php /home/your_username/public_html/artisan queue:work --stop-when-empty --max-time=240 --tries=3 >> /dev/null 2>&1
```

Or use the built-in queue processing in the scheduler (already configured in `app/Console/Kernel.php`).

## Step 10: Configure Email (SMTP)

Update `.env` with your SMTP credentials:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com        # or your provider
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="Outreach CRM"
```

### Test Email Configuration
```bash
php artisan tinker
>>> Mail::raw('Test email', fn($m) => $m->to('test@example.com')->subject('Test'))
```

## Step 11: SSL/HTTPS Setup

1. In hPanel → **SSL** → **Force HTTPS**
2. Or add to `.htaccess` in `public_html/public/`:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## Step 12: DNS & SPF/DKIM/DMARC Setup

### SPF Record (TXT)
```
v=spf1 include:_spf.google.com ~all
```
Or for your provider:
```
v=spf1 include:your-smtp-provider.com ~all
```

### DKIM
Generate DKIM keys and add as TXT record (check your SMTP provider docs)

### DMARC (TXT)
```
v=DMARC1; p=quarantine; rua=mailto:dmarc@your-domain.com
```

## Step 13: Verify Deployment

### Checklist
- [ ] Application loads at `https://your-domain.com`
- [ ] Login works with demo credentials
- [ ] Dashboard displays correctly
- [ ] Leads/Companies/Campaigns pages work
- [ ] Email test sends successfully
- [ ] Cron job runs (check `storage/logs/laravel.log`)
- [ ] Queue processes (send test campaign)
- [ ] File uploads work (import CSV)
- [ ] HTTPS enforced
- [ ] `.env` not accessible via web

### Test Commands
```bash
# Check application
php artisan about

# Test queue
php artisan queue:work --once

# Test scheduler
php artisan schedule:run

# Test email
php artisan mail:test your-email@domain.com
```

## Troubleshooting

### Common Issues

#### 500 Internal Server Error
```bash
# Check logs
tail -f storage/logs/laravel.log

# Common fixes:
# 1. Check .env exists and has correct permissions
# 2. Run php artisan config:clear
# 3. Check PHP version: php -v
# 4. Verify extensions: php -m
```

#### Database Connection Failed
```bash
# Verify credentials
mysql -u your_database_user -p -h 127.0.0.1 your_database_name

# Check .env DB_* values match exactly
```

#### Cron Not Running
```bash
# Check cron log
grep CRON /var/log/syslog

# Test command manually
/usr/bin/php /home/your_username/public_html/artisan schedule:run
```

#### Queue Not Processing
```bash
# Check failed jobs
php artisan queue:failed

# Retry failed
php artisan queue:retry all

# Check jobs table
php artisan tinker
>>> DB::table('jobs')->count()
```

#### Permission Denied
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R your_username:your_username storage bootstrap/cache
```

#### Memory Limit Exceeded
```bash
# Check limit
php -i | grep memory_limit

# Increase in .user.ini or php.ini
memory_limit = 512M
```

## Maintenance

### Backup Strategy
```bash
# Database backup (add to daily cron)
mysqldump -u your_database_user -p your_database_name > backup_$(date +%F).sql

# Files backup
tar -czf files_backup_$(date +%F).tar.gz public_html --exclude='public_html/storage/logs/*'
```

### Updates
```bash
# Pull latest changes
git pull origin main

# Install dependencies
composer install --optimize-autoloader --no-dev

# Run migrations
php artisan migrate --force

# Rebuild assets
npm run build

# Clear caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Monitoring
- Check `storage/logs/laravel.log` regularly
- Monitor queue: `php artisan queue:monitor`
- Check disk space: `df -h`

## Security Checklist

- [ ] `.env` not publicly accessible
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` generated and unique
- [ ] Strong database password
- [ ] HTTPS enforced
- [ ] Secure cookies (`SESSION_SECURE_COOKIE=true`)
- [ ] CSRF protection enabled
- [ ] Rate limiting configured
- [ ] File upload validation
- [ ] SQL injection prevention (Eloquent ORM)
- [ ] XSS protection (Blade escaping)

## Support Commands

```bash
# Clear all caches
php artisan optimize:clear

# Rebuild caches
php artisan config:cache && php artisan route:cache && php artisan view:cache

# Check scheduled tasks
php artisan schedule:list

# Check queue status
php artisan queue:monitor

# Run specific job
php artisan queue:work --once

# Generate IDE helpers (dev only)
php artisan ide-helper:generate
```

## Path Reference

| Path | Description |
|------|-------------|
| `/home/your_username/public_html` | Project root |
| `/home/your_username/public_html/public` | Document root |
| `/home/your_username/public_html/storage` | Storage (writable) |
| `/home/your_username/public_html/bootstrap/cache` | Cache (writable) |
| `/home/your_username/public_html/.env` | Environment config |

## Useful Hostinger-Specific Commands

```bash
# Check PHP version
php -v

# Check PHP extensions
php -m | grep -E 'pdo|mysql|mbstring|xml|gd|zip|bcmath|intl'

# Check Composer version
composer --version

# Check Node/npm (for assets)
node -v && npm -v

# Check disk usage
du -sh public_html/

# Check memory limit
php -r "echo ini_get('memory_limit');"
```

## Rollback Procedure

```bash
# 1. Restore database
mysql -u your_database_user -p your_database_name < backup_2024-01-15.sql

# 2. Restore files
tar -xzf files_backup_2024-01-15.tar.gz -C /home/your_username/

# 3. Clear caches
php artisan optimize:clear

# 4. Run migrations (if needed)
php artisan migrate --force
```

---

## Need Help?

If you encounter issues:
1. Check `storage/logs/laravel.log` for errors
2. Verify all requirements are met
3. Ensure cron job is configured correctly
4. Test SMTP credentials independently
5. Contact Hostinger support for server-specific issues

**Important**: Never commit `.env` with real credentials to version control!

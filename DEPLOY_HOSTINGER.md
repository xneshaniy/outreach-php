# Hostinger Shared Hosting Deployment Guide

This guide covers deploying the Outreach CRM Laravel application to Hostinger shared hosting (no VPS).

## 1. Requirements

### PHP Version & Extensions
- **PHP 8.2 or higher** (Laravel 11 requirement)
- Required PHP extensions:
  - `bcmath`
  - `ctype`
  - `curl`
  - `dom`
  - `fileinfo`
  - `gd` (for DomPDF)
  - `iconv`
  - `intl`
  - `mbstring`
  - `openssl`
  - `pdo`
  - `pdo_mysql`
  - `tokenizer`
  - `xml`
  - `zip`

### Composer
- Composer 2.x (available on Hostinger shared hosting)

### Node.js & npm
- Node.js 18+ (for building frontend assets)
- npm 9+

## 2. Connect GitHub Repository in Hostinger

1. Log in to **Hostinger hPanel**
2. Go to **Websites** → **Manage** (for your domain `outreach.click`)
3. Navigate to **Git** → **Deploy**
4. Click **Connect Repository**
5. Select **GitHub** and authorize Hostinger
6. Choose repository: `xneshaniy/outreach-php`
7. Select branch: `main`
8. Set **Deployment Path**: `public_html` (or your domain's document root)
9. Click **Deploy**

## 3. Branch & Deployment Directory

| Setting | Value |
|---------|-------|
| **Branch** | `main` |
| **Deployment Directory** | `public_html` (document root) |
| **Auto-deploy** | Enabled (optional) |

> **Important**: The Laravel `public` folder must be the document root. On Hostinger shared hosting, this is typically `public_html`. The root `.htaccess` redirects all requests to the `public` folder.

## 4. Environment Variables Configuration

### In Hostinger hPanel:
1. Go to **Websites** → **Manage** → **Environment Variables**
2. Add the following variables (use **production values**, not placeholders):

```env
APP_NAME="Outreach CRM"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://outreach.click

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_strong_password

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=your_email@outreach.click
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="your_email@outreach.click"
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

### Generate APP_KEY:
```bash
php artisan key:generate --show
```
Copy the output (including `base64:`) to the `APP_KEY` variable.

### Security Notes:
- **Never** commit `.env` or `.env.production` to Git
- Use strong, unique passwords for database and email
- Set `APP_DEBUG=false` in production
- Use `SESSION_SECURE_COOKIE=true` for HTTPS

## 5. Install Composer Dependencies

### On Hostinger (via SSH or Terminal in hPanel):
```bash
# Navigate to your project root
cd ~/public_html

# Install production dependencies (optimized autoloader)
composer install --no-dev --optimize-autoloader --no-interaction

# If you need to regenerate autoloader after changes
composer dump-autoload --optimize
```

### Build Frontend Assets:
```bash
# Install npm dependencies
npm ci

# Build for production
npm run build
```

> **Note**: Hostinger shared hosting may have limited Node.js support. If `npm run build` fails, build assets locally and upload the `public/build` folder via FTP/SFTP.

## 6. Configure Document Root

### Option A: Root .htaccess (Already Configured)
The project includes a root `.htaccess` that redirects all requests to the `public` folder. This works on Hostinger shared hosting.

### Option B: Symlink (If Supported)
If Hostinger allows, create a symlink:
```bash
# In hPanel Terminal/SSH
cd ~/public_html
ln -s public public_html
```

### Verify public/index.php Exists
Ensure `public/index.php` is present (created during preparation).

## 7. Configure Writable Directories

Set proper permissions (755 for directories, 644 for files):

```bash
# Storage directories
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/

# Ensure storage subdirectories exist
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
```

> **Hostinger Note**: On shared hosting, permissions are often managed via hPanel File Manager. Use 755 for directories and 644 for files. The `storage` and `bootstrap/cache` directories must be writable by the web server.

## 8. Database Setup & Migrations

### Create Database in hPanel:
1. Go to **Databases** → **MySQL Databases**
2. Create new database and user
3. Assign user to database with all privileges
4. Note the credentials for environment variables

### Run Migrations:
```bash
# Run migrations
php artisan migrate --force

# If you need to seed initial data
php artisan db:seed --force
```

### Verify Database Connection:
```bash
php artisan migrate:status
```

## 9. Clear & Rebuild Laravel Caches

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

# Rebuild caches for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
```

> **Important**: Run `optimize:clear` before `optimize` to ensure clean state.

## 10. Verification Checklist

After deployment, verify:

| Check | Command / URL |
|-------|---------------|
| **Homepage** | Visit `https://outreach.click/` (should redirect to login) |
| **Login Page** | Visit `https://outreach.click/login` |
| **Database Connection** | `php artisan migrate:status` |
| **Route List** | `php artisan route:list` |
| **Queue Worker** | `php artisan queue:work --once` |
| **Schedule** | `php artisan schedule:run` |

### Test Login Flow:
1. Register a new account at `/register`
2. Verify workspace creation
3. Login at `/login`
4. Access dashboard at `/dashboard`

## 11. Rollback Procedure

If deployment fails:

### Quick Rollback (Git):
```bash
# In hPanel Terminal
cd ~/public_html
git log --oneline -5          # Find previous commit
git checkout <previous-commit> # Checkout previous version
composer install --no-dev --optimize-autoloader
npm run build                  # If needed
php artisan migrate --force    # If migrations were run
php artisan optimize:clear && php artisan optimize
```

### Full Rollback:
1. In hPanel **Git Deploy**, click **History**
2. Select previous successful deployment
3. Click **Rollback** or **Re-deploy**
4. Run post-deployment commands (migrations, cache clear, etc.)

### Database Rollback (if migration caused issues):
```bash
# Rollback last migration batch
php artisan migrate:rollback --force

# Or rollback specific steps
php artisan migrate:rollback --step=1 --force
```

## Additional Hostinger-Specific Notes

### Cron Jobs (for Scheduler)
In hPanel → **Cron Jobs**, add:
```bash
* * * * * cd ~/public_html && php artisan schedule:run >> /dev/null 2>&1
```

### Queue Worker (for Background Jobs)
In hPanel → **Cron Jobs** (or use Process Manager if available):
```bash
* * * * * cd ~/public_html && php artisan queue:work --sleep=3 --tries=3 --max-time=3600 >> /dev/null 2>&1
```

### SSL Certificate
- Hostinger provides free SSL via Let's Encrypt
- Enable in hPanel → **SSL** → **Force HTTPS**

### File Permissions Issues
If you get "Permission denied" errors:
1. In hPanel File Manager, set `storage/` and `bootstrap/cache/` to **755**
2. Ensure the web server user owns these directories (usually automatic on shared hosting)

### Memory Limit
If Composer or Artisan runs out of memory:
```bash
# Increase memory limit for specific commands
php -d memory_limit=512M artisan migrate --force
php -d memory_limit=512M composer install --no-dev --optimize-autoloader
```

### PHP Version
Ensure PHP 8.2+ is selected in hPanel → **PHP Configuration** → **PHP Version**

## Troubleshooting Common Issues

| Issue | Solution |
|-------|----------|
| **500 Error** | Check `storage/logs/laravel.log`; run `php artisan optimize:clear` |
| **Database Connection Failed** | Verify DB credentials in environment variables; ensure DB user has privileges |
| **Assets Not Loading** | Run `npm run build`; verify `public/build` exists; check `APP_URL` |
| **Session/Cookie Issues** | Verify `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE`, `SANCTUM_STATEFUL_DOMAINS` |
| **Permission Denied** | Set `storage/` and `bootstrap/cache/` to 755; check ownership |
| **Class Not Found** | Run `composer dump-autoload --optimize` |

## Support Commands

```bash
# Check Laravel version
php artisan --version

# Check environment
php artisan env

# View logs
tail -f storage/logs/laravel.log

# Test email configuration
php artisan tinker
>>> Mail::raw('Test', function($m) { $m->to('test@example.com')->subject('Test'); });
```
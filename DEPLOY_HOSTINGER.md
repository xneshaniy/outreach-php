# Hostinger Shared Hosting Deployment Guide
## Outreach CRM - Laravel 12 + Inertia.js + Vue 3

**Repository:** https://github.com/xneshaniy/outreach-php  
**Branch:** `main`  
**Domain:** `outreach.click`  
**PHP Version:** 8.3  
**Laravel Version:** 12.x  

---

## ⚠️ CRITICAL: Deployment Mode

**Hostinger MUST be configured as a PHP Application, NOT a Static Website.**

| Setting | Correct Value |
|---------|---------------|
| **Application Type** | `PHP` (or "PHP Application") |
| **Document Root** | `public` (not `dist`, not project root) |
| **Build Command** | See build steps below |
| **Output Directory** | `public/build` (Vite output), NOT `dist` |

**Why this fails:** Hostinger's "Static Publish Mode" looks for `dist/` folder and serves static files. Laravel requires PHP execution, database connections, sessions, and queue workers. Static hosting cannot run Laravel.

---

## 1. Hostinger Git Deployment Setup

### In Hostinger hPanel:

1. **Websites** → **Manage** (for `outreach.click`)
2. **Git** → **Deploy** (or **Git** in sidebar)
3. **Connect Repository**:
   - Select **GitHub**
   - Authorize Hostinger
   - Repository: `xneshaniy/outreach-php`
   - Branch: `main`
4. **Deployment Path**: `~/domains/outreach.click/public_html` (or your domain's root)
5. **Application Type**: Select **PHP** / **PHP Application**
6. **Document Root**: Set to `public` (relative to deployment path)
   - Full path: `~/domains/outreach.click/public_html/public`
7. **PHP Version**: `8.3`

---

## 2. Build Commands (Configure in Hostinger Git Deploy Settings)

Hostinger runs these commands automatically during deployment. Configure in **Git Deploy → Build Settings**:

```bash
# 1. Install PHP dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# 2. Install Node.js dependencies  
npm ci

# 3. Build frontend assets (Vite outputs to public/build/)
npm run build
```

**Important:** 
- Do NOT use `npm run build` output directory `dist` - Vite with laravel-vite-plugin outputs to `public/build/`
- The `public/build/manifest.json` MUST exist after build
- `package-lock.json` is committed for reproducible `npm ci`

---

## 3. Environment Variables (Hostinger hPanel → Environment Variables)

Add ALL of these in Hostinger's Environment Variables section:

```env
# Application
APP_NAME="Outreach CRM"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://outreach.click
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_TIMEZONE=UTC

# Logging
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# Database (use YOUR Hostinger MySQL credentials)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u311221303_haseeb
DB_USERNAME=u311221303_admin
DB_PASSWORD=your_database_password

# Cache & Session
CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_DOMAIN=outreach.click
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=outreach.click

# Queue
QUEUE_CONNECTION=database

# Mail (use YOUR Hostinger SMTP credentials)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=admin@outreach.click
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="admin@outreach.click"
MAIL_FROM_NAME="${APP_NAME}"

# App-specific
CRM_DEFAULT_TIMEZONE=UTC
CRM_DAILY_SEND_LIMIT=200
CRM_BATCH_SIZE=50
CRM_MAX_RETRIES=3
CRM_RETRY_DELAY=300
```

### Generate APP_KEY:
```bash
# Run locally, copy output including "base64:"
php artisan key:generate --show
```

---

## 4. Post-Deployment Commands (Run in Hostinger Terminal AFTER first deploy)

```bash
# Navigate to deployment directory
cd ~/domains/outreach.click/public_html

# Run migrations (only after DB is configured in hPanel)
php artisan migrate --force

# Clear and rebuild caches
php artisan optimize:clear
php artisan optimize

# Create storage symlink (for public file access)
php artisan storage:link

# Verify
php artisan migrate:status
php artisan route:list
```

---

## 5. Cron Jobs (Hostinger hPanel → Cron Jobs)

### Laravel Scheduler (runs every minute)
```
* * * * * cd ~/domains/outreach.click/public_html && php artisan schedule:run >> /dev/null 2>&1
```

### Queue Worker (processes background jobs)
**Option A: Bounded worker (recommended for shared hosting)**
```
* * * * * cd ~/domains/outreach.click/public_html && php artisan queue:work --sleep=3 --tries=3 --max-time=3600 --stop-when-empty >> /dev/null 2>&1
```

**Option B: If Hostinger has Process Manager, use persistent worker instead**

---

## 6. SSL & Security

1. **hPanel → SSL** → Enable **Force HTTPS**
2. **hPanel → SSL** → Enable **HSTS**
3. Verify `SESSION_SECURE_COOKIE=true` in env vars
4. Verify `APP_URL=https://outreach.click`

---

## 7. File Permissions (Hostinger File Manager)

Set these directories to **755**:
- `storage/` (and all subdirectories)
- `bootstrap/cache/`

Set files to **644**.

---

## 8. Verification Checklist

After deployment, test these URLs:

| URL | Expected Behavior |
|-----|-------------------|
| `https://outreach.click/` | Redirects to `/login` |
| `https://outreach.click/login` | Shows login page (Vue/Inertia) |
| `https://outreach.click/register` | Shows registration page |
| `https://outreach.click/dashboard` | Redirects to login if not authenticated |
| `https://outreach.click/up` | Returns 200 OK (health check) |

### Functional Tests:
1. Register new account → verify workspace created
2. Login → redirects to `/dashboard`
3. Navigate to Leads, Campaigns, Pipeline → all load
4. Logout → redirects to login
5. Vite assets load (no 404s in browser console)
6. No debug info exposed (APP_DEBUG=false)

---

## 9. Troubleshooting

### Build Fails: "No output directory found"
- **Cause:** Hostinger configured as Static site
- **Fix:** Change Application Type to **PHP** in Git Deploy settings

### Build Fails: Vite manifest not found
- **Cause:** `npm run build` didn't run or failed
- **Fix:** Check build logs; ensure `public/build/manifest.json` exists after build

### HTTP 500 Error
```bash
# Check logs
tail -f ~/domains/outreach.click/public_html/storage/logs/laravel.log

# Common fixes:
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Database Connection Failed
- Verify DB credentials in Hostinger Environment Variables
- Ensure DB user has ALL PRIVILEGES on the database
- Check `DB_HOST=127.0.0.1` (not localhost)

### Assets Not Loading (404)
- Verify `APP_URL=https://outreach.click` matches domain
- Verify `public/build/` exists with manifest.json and assets
- Check browser console for mixed content (HTTP vs HTTPS)

### Session/Cookie Issues
- Verify `SESSION_DOMAIN=outreach.click`
- Verify `SESSION_SECURE_COOKIE=true`
- Verify `SANCTUM_STATEFUL_DOMAINS=outreach.click`
- Clear browser cookies for the domain

### Permission Denied (storage/logs)
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

### Class Not Found
```bash
composer dump-autoload --optimize
```

### Queue Not Processing
- Check cron job is running: `crontab -l`
- Verify `QUEUE_CONNECTION=database`
- Check `jobs` table exists: `php artisan migrate:status`
- View failed jobs: `php artisan queue:failed`

---

## 10. Rollback Procedure

### Quick Rollback (Git):
```bash
cd ~/domains/outreach.click/public_html
git log --oneline -5
git checkout <previous-commit-hash>
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
```

### Hostinger Git Deploy Rollback:
1. hPanel → **Git Deploy** → **History**
2. Click **Rollback** on previous successful deployment
3. Run post-deploy commands

### Database Rollback:
```bash
php artisan migrate:rollback --force
# Or specific step:
php artisan migrate:rollback --step=1 --force
```

---

## 11. Project Structure Reference

```
~/domains/outreach.click/public_html/          ← Deployment root
├── app/                                       ← Laravel app code
├── bootstrap/
│   └── cache/                                 ← Writable (755)
├── config/
├── database/
├── public/                                    ← DOCUMENT ROOT
│   ├── index.php                              ← Entry point
│   ├── .htaccess                              ← Laravel rewrite rules
│   └── build/                                 ← Vite output (auto-generated)
│       ├── manifest.json                      ← REQUIRED
│       └── assets/
├── resources/
├── routes/
├── storage/                                   ← Writable (755)
│   ├── framework/
│   │   ├── cache/data/
│   │   ├── sessions/
│   │   └── views/
│   └── logs/
├── vendor/                                    ← Composer (auto-generated)
├── .env                                       ← Hostinger manages (NOT in Git)
├── .hostinger.yaml                            ← Deployment config
├── hostinger-build.sh                         ← Build script
├── artisan
├── composer.json
├── composer.lock
├── package.json
├── package-lock.json
├── vite.config.js
├── tailwind.config.js
└── postcss.config.js
```

---

## 12. Key Files for Deployment

### `.hostinger.yaml` - Deployment configuration
```yaml
application_type: php
php_version: "8.3"
document_root: "public"
build:
  - composer install --no-dev --optimize-autoloader --no-interaction
  - npm ci
  - npm run build
```

### `vite.config.js` - Vite outputs to `public/build/`
```js
import laravel from 'laravel-vite-plugin';
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
    ],
});
```

### Root `.htaccess` - Redirects to public/
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

---

## 13. Commands Summary

### Local Development:
```bash
composer install
npm install
npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### Hostinger Deployment (Auto):
1. Push to `main` branch
2. Hostinger runs build commands
3. Hostinger runs post-deploy commands
4. Site goes live

### Hostinger Manual (if auto-deploy fails):
```bash
cd ~/domains/outreach.click/public_html
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
```

---

## 14. Support Commands

```bash
# Check Laravel version
php artisan --version

# Check environment
php artisan env

# View logs
tail -f storage/logs/laravel.log

# Test queue
php artisan queue:work --once

# Test scheduler
php artisan schedule:run

# Clear all caches
php artisan optimize:clear

# Rebuild caches
php artisan optimize
```

---

## 15. Known Pre-Existing Issues

| Issue | Status | Notes |
|-------|--------|-------|
| `\App\Exports\LeadsExport` class missing | Pre-existing | Referenced in LeadController::export() but class not created |
| Pest TestCase.php missing | Pre-existing | Tests use PHPUnit-style `Tests\TestCase` base class |
| Unit tests directory empty | Pre-existing | No unit tests written yet |

These issues exist in the codebase prior to this deployment fix and do not affect core authentication, dashboard, or CRM functionality.
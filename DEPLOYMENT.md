# Outreach CRM - Hostinger Shared Hosting Deployment Guide

## Architecture

The application is deployed with a split directory layout on Hostinger:

```
/home/u311221303/domains/outreach.click/
├── crm/                    # Laravel application (NOT publicly accessible)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/             # (redundant copy, not served)
│   ├── resources/
│   ├── routes/
│   ├── storage/            # writable (logs, cache, sessions, views)
│   ├── tests/
│   ├── vendor/             # installed via composer on server
│   ├── queue-drain.php     # CLI queue drain script
│   └── .env                # production environment (NOT in Git)
├── public_html/            # Web root (publicly accessible)
│   ├── index.php           # Front controller (bootstraps ../crm)
│   ├── .htaccess           # Security headers
│   ├── .user.ini           # PHP settings
│   ├── build/              # Vite compiled assets
│   ├── css/
│   └── js/
```

## Prerequisites

- Hostinger Business shared hosting (PHP 8.3)
- MySQL database `u311221303_outrach`
- Cron job access (for scheduler + queue drain)
- SSH access (recommended for composer install)

## Step 1: Upload Files

### Option A: SSH + SCP (Recommended)

```bash
# Extract the deployment archive locally
unzip outreach-click.zip

# This creates: crm/ and public_html/ directories

# Upload crm/ to the home directory
scp -r crm/ u311221303@145.79.31.10:/home/u311221303/domains/outreach.click/

# Upload public_html/ contents to the web root
scp -r public_html/* u311221303@145.79.31.10:/home/u311221303/domains/outreach.click/public_html/

# On the server, install PHP dependencies
ssh u311221303@145.79.31.10
cd /home/u311221303/domains/outreach.click/crm
/opt/alt/php83/usr/bin/php /usr/bin/composer install --optimize-autoloader --no-dev
```

### Option B: Hostinger File Manager

1. Extract `outreach-click.zip` locally
2. Upload `crm/` contents to `/home/u311221303/domains/outreach.click/crm/`
3. Upload `public_html/` contents to `/home/u311221303/domains/outreach.click/public_html/`
4. Ensure `vendor/` exists in `crm/` (run `composer install` via SSH or Hostinger terminal)

**Never upload `.env` with real credentials.** The production `.env` already exists on the server — preserve it during updates.

## Step 2: Verify File Placement

```
public_html/ should contain: index.php, .htaccess, .user.ini, build/, css/, js/
crm/ should contain: app/, bootstrap/, config/, database/, routes/, resources/, storage/, queue-drain.php, .env
```

The `public_html/index.php` bootstraps the app from `../crm/` and sets the public path to `public_html/`.

## Step 3: Environment Configuration

The production `.env` already exists on the server at `crm/.env`. Do not overwrite it. Verify:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://outreach.click`
- `DB_DATABASE=u311221303_outrach`
- `QUEUE_CONNECTION=database`
- `CACHE_STORE=file`
- `SESSION_DRIVER=file`

## Step 4: Run Production Commands (via SSH)

```bash
cd /home/u311221303/domains/outreach.click/crm

# Clear and cache configuration
/opt/alt/php83/usr/bin/php artisan optimize:clear
/opt/alt/php83/usr/bin/php artisan config:cache

# Verify storage directories are writable
/opt/alt/php83/usr/bin/php artisan schedule:list
```

**Never run `migrate:fresh`, `db:wipe`, or destructive seeders against production.** Migrations have already been applied to the production database.

## Step 5: Configure Cron Jobs

In Hostinger hPanel → **Advanced** → **Cron Jobs**, create ONE cron job:

```
* * * * * /opt/alt/php83/usr/bin/php /home/u311221303/domains/outreach.click/crm/artisan schedule:run
```

This runs every minute and handles:
- Campaign processing (ProcessCampaignsJob)
- Reminder emails (SendReminderEmailsJob)
- Cleanup (CleanupJob)
- Database queue drain (QueueDrain::run)

**Do not add a separate `queue:work` cron job** — the host's PHP CLI segfaults during `queue:work` shutdown. The in-process drain via `QueueDrain` handles queue processing safely.

## Step 6: Verify Deployment

```bash
# Health endpoint
curl -I https://outreach.click/up          # Should return 200

# Login page
curl -I https://outreach.click/login        # Should return 200

# Homepage (redirects to dashboard or login)
curl -I https://outreach.click/             # Should return 302

# Cron output (check via Hostinger hPanel cron output)
```

## Step 7: Rollback Procedure

1. Restore database from backup (`mysqldump` or hPanel backup)
2. Restore files from previous deployment archive
3. Clear caches: `php artisan optimize:clear`
4. Never run `migrate:fresh` or `db:wipe`

## Security Notes

- `.env` is in `crm/` (above web root) — never publicly accessible
- `APP_DEBUG=false` in production
- No public diagnostic or installer scripts
- All probe scripts (`_deploy*.php`, `install.php`, etc.) have been removed from `public_html/`

## What Requires Manual Action

1. **Database credentials** — The production `.env` already has them; do not overwrite
2. **SMTP credentials** — Configure in production `.env` for email sending
3. **Cron job** — Create via Hostinger hPanel (see Step 5)
4. **Composer install** — Run on server after uploading `crm/`

## Files Changed in This Release

- `app/Models/Campaign.php` — Added `STATUS_FAILED` constant
- `app/Models/EmailAccount.php` — Added `getPassword()` accessor
- `app/Services/EmailService.php` — Fixed `getPassword()` → `password` accessor; added `CampaignRecipient` import
- `app/Services/LeadService.php` — Fixed dead code: lead score now calculated after creation
- `app/Http/Controllers/Settings/SettingsController.php` — Fixed `route('leads.import')` → `route('leads.import.form')`
- `tests/TestCase.php` — Added `createTestUser()` helper
- Multiple test files updated for invitation-only registration

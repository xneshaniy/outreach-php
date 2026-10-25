# 🚀 QUICK DEPLOYMENT TO HOSTINGER (Tested & Production Ready)

## Your Live Credentials
- **Domain**: `https://outreach.click`
- **Admin Email**: `admin@outreach.click` (or `admin@example.com`)
- **Default Login Password**: `password` (change after first login)
- **SMTP**: `smtp.hostinger.com:465` (SSL)
- **Database**: `u311221303_outrach` / `u311221303_admin` / `@Haseeb786@!$`

---

## STEP 1: Verify Database in hPanel (One-time check)

1. Login to **hPanel** → **Databases** → **MySQL Databases**
2. Ensure database exists: `u311221303_outrach`
3. Ensure user exists: `u311221303_admin` with password: `@Haseeb786@!$`
4. Ensure the user is assigned to the database with **All Privileges**
5. Database host is: `127.0.0.1`

---

## STEP 2: Upload Files to Server

You can upload using **Hostinger File Manager** or **FTP (FileZilla)**:

### Recommended:
1. In File Manager, open `public_html/`.
2. Upload the project files from `c:\Users\Haseeb\Desktop\new  php\`:
   - Keep the normal folder structure: `app/`, `bootstrap/`, `config/`, `database/`, `public/` (including `public/build/` and `public/.htaccess`), `resources/`, `routes/`, `storage/`, `vendor/`, `artisan`, `composer.json`, `composer.lock`, `.htaccess`, `.env`.
   - **Do NOT upload**: `.git/`, `node_modules/`, `tests/`.
   - Notice: `public/build/` (prebuilt CSS/JS assets) is already compiled and must be uploaded so no npm/Node is needed on the server!
   - Root `.htaccess` is already configured to automatically route requests to `public/` and protect sensitive files.

---

## STEP 3: Ensure .env is Configured

The `.env` file is already pre-configured with:
- Production `APP_KEY=base64:HtXXuptppub3AIvqbhQZp25IVGM2T+QQHiNKQ2tFGB0=`
- `APP_ENV=production` & `APP_DEBUG=false`
- `APP_URL=https://outreach.click`
- Database credentials (`u311221303_outrach` / `@Haseeb786@!$`)
- Hostinger SSL SMTP credentials (`smtp.hostinger.com:465`)

If `.env` was not copied, copy `.env.production` to `.env` in `public_html/`.

---

## STEP 4: Run Commands via Hostinger Terminal (SSH)

1. Open **hPanel** → **Advanced** → **Terminal** (or connect via SSH):
2. Run these commands:

```bash
cd /home/u311221303/public_html

# 1. Run database migrations
php artisan migrate --force

# 2. Seed default admin and initial data (only needs to run once)
php artisan db:seed --force

# 3. Create storage symlink
php artisan storage:link

# 4. Set directory permissions
chmod -R 775 storage bootstrap/cache
chmod 640 .env

# 5. Cache configurations and routes for maximum speed
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## STEP 5: Configure Cron Jobs (Critical for Queue & Campaigns)

In **hPanel** → **Advanced** → **Cron Jobs** → Add New Cron Job:

```
Command: /usr/bin/php /home/u311221303/public_html/artisan schedule:run >> /dev/null 2>&1
Interval: * * * * * (Once per minute)
```

> **Note**: The Laravel scheduler already includes the in-process queue drain (`queue:drain`), which processes scheduled email batches and reminder jobs every minute safely without crashing on shared hosting.

You can also run a manual drain anytime from Terminal:
```bash
php artisan queue:drain
```

---

## STEP 6: Verify Deployment

1. Open **https://outreach.click**
2. Login with:
   - **Email**: `admin@outreach.click`
   - **Password**: `password`
3. You are now live!
   - Dashboard with statistics
   - Leads management & CSV import/export
   - Email Campaigns & multi-step sequence automation
   - Email Accounts (Hostinger SMTP pre-configured)
   - Sales Pipeline & Deals
   - Tasks & Follow-up reminders
   - Workspace Settings

---

## 🔧 TROUBLESHOOTING

| Issue | Resolution |
|-------|------------|
| 500 Error | Check `tail -n 50 storage/logs/laravel.log` in Terminal |
| Cache mismatch | Run `php artisan optimize:clear && php artisan config:cache` |
| Permission error | Run `chmod -R 775 storage bootstrap/cache` |
| Queue test | Run `php artisan queue:drain --max=10` |
| Test SMTP Mail | Run `php artisan tinker --execute="Mail::raw('Test email', fn(\$m) => \$m->to('admin@outreach.click')->subject('CRM Test'));"` |

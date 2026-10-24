# Hostinger Deployment Guide (Laravel + Inertia)

**Repository:** https://github.com/xneshaniy/outreach-php  
**Branch:** `main`  
**Domain:** `outreach.click`  
**Runtime:** PHP 8.3, Laravel 12, MySQL, Inertia + Vue 3

---

## 1. How this app must be hosted

This is a **PHP application**, not a static site.

| Setting | Required value |
|---|---|
| Application type | PHP |
| Document root | the repository's `public/` directory |
| Frontend assets | `public/build/` (already committed) |
| Node.js at deploy time | **not required** |

Hostinger's static "publish" mode looks for a `dist/` folder and cannot execute PHP.
Laravel needs PHP, MySQL, sessions and queue workers, so the site must run as a PHP app
with `public/` as the web root.

Current server layout (Hostinger Git deploy):

```
~/domains/outreach.click/
├── hbuilds/
│   ├── config/.env          # environment file, NOT in git
│   └── source/repository/   # git checkout of this repository
└── public_html/             # must serve repository/public
```

If hPanel only allows a `public_html` document root, use the "PHP application"
feature to point it at `hbuilds/source/repository/public`, or make `public_html`
a symlink to that directory. Do **not** copy the repository into `public_html`.

---

## 2. Environment file

The production `.env` is maintained outside the repository at
`~/domains/outreach.click/hbuilds/config/.env`. It is never committed.
Use `.env.example` as the template.

Critical values:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://outreach.click
APP_KEY=base64:...        # generate once, then never rotate casually
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=
CACHE_STORE=file
QUEUE_CONNECTION=database
DB_HOST=127.0.0.1        # same-server MySQL; never the srvNNNN.hstgr.io host
```

> **APP_KEY warning:** mailbox passwords in `email_accounts` are encrypted with
> `APP_KEY`. Rotating it makes existing mailbox credentials unreadable. Generate it
> once with `php artisan key:generate --show` and keep a backup.

Generate a key locally (do not write it into git):

```bash
php artisan key:generate --show
```

---

## 3. Deploy or update the code

Frontend assets are committed to `public/build/`, so the server needs **Composer only**.

```bash
cd ~/domains/outreach.click/hbuilds/source/repository

git fetch origin main
git reset --hard origin/main          # fast-forward deploy; never force-push

composer install --no-dev --optimize-autoloader --no-interaction

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If hPanel runs the build for you, use the same order. Do not run
`npm ci && npm run build` on the server unless you intentionally changed the
frontend; the resulting assets already belong in `public/build/`.

Writable directories (chmod `755`, files `644`):

```bash
mkdir -p storage/logs storage/framework/{cache/data,sessions,views} bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

---

## 4. Database (owner-run, with backup first)

The agent must never run production migrations. The site owner should:

1. Take a database backup in hPanel.
2. Check the current state:

```bash
php artisan migrate:status
```

3. Only if migrations are pending, apply them:

```bash
php artisan migrate --force
```

Never run `migrate:fresh`, `db:wipe`, or seeders against production.
Permissions for team members are optional; workspace owners always have full
access inside their own workspace, so the CRM works without seeded permissions.

---

## 5. Scheduled jobs and queue

The scheduler sends reminders, processes campaigns and cleans old records. The
queue sends campaign emails and processes CSV imports (set `QUEUE_CONNECTION=database`).
Add both cron entries in hPanel → Cron Jobs:

```cron
* * * * * cd ~/domains/outreach.click/hbuilds/source/repository && php artisan schedule:run >> /dev/null 2>&1
*/5 * * * * cd ~/domains/outreach.click/hbuilds/source/repository && php artisan queue:work --stop-when-empty --sleep=3 --tries=3 --max-time=280 >> /dev/null 2>&1
```

Without the queue cron, imports stay `pending` and campaigns show no delivery
progress even though the UI works.

---

## 6. Safe verification (read-only)

Run in this order. None of these commands modify data:

```bash
cd ~/domains/outreach.click/hbuilds/source/repository

php artisan --version
php artisan about
php artisan migrate:status
php artisan route:list --except-vendor
php artisan config:show app | head -n 20
php artisan schedule:list
php artisan queue:failed
```

Then verify HTTP over HTTPS from the server:

```bash
curl -sS -o /dev/null -w '%{http_code} %{redirect_url}\n' https://outreach.click/
curl -sS -o /dev/null -w '%{http_code}\n' https://outreach.click/login
curl -sS -o /dev/null -w '%{http_code}\n' https://outreach.click/register
curl -sS -o /dev/null -w '%{http_code}\n' https://outreach.click/up
```

Expected:

| URL | Expected |
|---|---|
| `/` | `302` → `/dashboard` → `/login` when signed out |
| `/login` | `200`, Inertia login page |
| `/register` | `200`, Inertia register page |
| `/up` | `200` |
| `/nonexistent` | `404` branded error page |
| `/build/manifest.json` | `200` |

Also check that assets are served from the same domain (no mixed content) and
that `APP_DEBUG=false` errors return a generic page, not a stack trace.

---

## 7. Troubleshooting

### 500 error
```bash
tail -n 100 storage/logs/laravel.log
php artisan optimize:clear
php artisan config:cache
```

### 404 for `/build/...` assets
- `public/build/manifest.json` must exist in the deployed checkout.
- The document root must be `public/`, not the repository root.
- `APP_URL` must be `https://outreach.click`.

### Login redirect loop / session not persisting
- `SESSION_SECURE_COOKIE=true` and HTTPS enabled.
- `SESSION_DOMAIN` must be empty (or `outreach.click`, never `www.` variant mismatch).
- `storage/framework/sessions` writable.

### Database connection failed
- Use `DB_HOST=127.0.0.1` from the app.
- The `srvNNNN.hstgr.io` host only works for external tools after adding the
  connecting IP as a remote MySQL host.

### Emails fail
- Verify SMTP credentials and `MAIL_ENCRYPTION` (`ssl` for port 465, `tls` for 587).
- Check `storage/logs/laravel.log` and `php artisan queue:failed`.

### Site shows "Static site" or a directory listing
- Change the application type back to PHP and set the document root to `public`.

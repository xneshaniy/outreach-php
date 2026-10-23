# Outreach CRM

A complete, production-ready CRM for email outreach and lead management, built with Laravel 11 and designed specifically for **Hostinger Shared Hosting** (no VPS required).

## Features

### Lead Management
- ✅ Create, edit, archive, and delete leads
- ✅ Lead fields: name, email, company, website, job title, phone, industry, country, city, source, owner, tags, status, notes, follow-up date
- ✅ CSV import with column mapping and preview
- ✅ Duplicate detection using normalized email addresses
- ✅ Search, filters, sorting, pagination, bulk actions
- ✅ Authorized CSV exports
- ✅ Lead activity history
- ✅ Saved lists and campaign assignments

### Campaign Automation
- ✅ Draft, scheduled, active, paused, completed, cancelled states
- ✅ Audience selection from lead lists
- ✅ Multi-step email sequences
- ✅ Personalization tokens with fallback values (`{{first_name}}`, `{{company_name}}`, etc.)
- ✅ Configurable delays between messages (days, hours, minutes)
- ✅ Sending windows and time zones
- ✅ Daily and per-account sending limits
- ✅ Test sends and campaign previews
- ✅ Activity logs and error reports
- ✅ Pause, resume, cancel, and retry controls

### Email Integration
- ✅ SMTP configuration with encrypted credential storage
- ✅ Connection testing
- ✅ Open tracking (pixel)
- ✅ Click tracking (link wrapping)
- ✅ Unsubscribe links with suppression list
- ✅ Bounce handling
- ✅ Reply detection (stops future follow-ups)
- ✅ SPF/DKIM/DMARC setup instructions

### CRM Sales Pipeline
- ✅ Customizable pipelines with stages
- ✅ Drag-and-drop deal management
- ✅ Deal values, probabilities, weighted pipeline
- ✅ Expected close dates, win/loss tracking

### Tasks & Follow-ups
- ✅ Task types: task, call, email, meeting, note, follow-up
- ✅ Priorities, due dates, reminders
- ✅ Related to leads, companies, deals, campaigns
- ✅ Overdue and upcoming task views

### Client Portal (Workspace-based)
- ✅ Workspace-based multi-tenancy
- ✅ Role-based access control (Admin, Manager, User)
- ✅ Each client sees only their authorized data
- ✅ Server-side authorization on every route

### Reports & Analytics
- ✅ Dashboard with key metrics
- ✅ Leads report (sources, status, conversion)
- ✅ Campaign performance (delivery, open, click, reply rates)
- ✅ Sales pipeline (by stage, by owner)
- ✅ Team performance
- ✅ Email analytics

### Security
- ✅ Secure password hashing (bcrypt)
- ✅ Role-based authorization (Spatie Laravel Permission)
- ✅ CSRF protection
- ✅ Server-side validation
- ✅ Rate limiting
- ✅ Audit logging (all CRUD operations)
- ✅ Activity logging (Spatie Activitylog)
- ✅ CSV formula-injection protection
- ✅ Secret redaction from logs
- ✅ Secure error handling in production

## Tech Stack

- **Backend**: Laravel 11 (PHP 8.2+)
- **Database**: MySQL/MariaDB
- **Queue**: Database-backed (no Redis required)
- **Scheduler**: Laravel Scheduler + Hostinger Cron Jobs
- **Frontend**: Blade + Vue 3 (Inertia.js) + Tailwind CSS + Alpine.js
- **Auth**: Laravel Sanctum + Session
- **Permissions**: Spatie Laravel Permission
- **Activity Log**: Spatie Laravel Activitylog
- **Excel/CSV**: Maatwebsite Laravel Excel

## Hostinger Shared Hosting Compatibility

| Requirement | Status |
|-------------|--------|
| PHP 8.2+ | ✅ Supported |
| MySQL/MariaDB | ✅ Supported |
| Composer | ✅ Available via SSH |
| Cron Jobs | ✅ Available |
| SSH Access | ✅ Available |
| No Redis Required | ✅ Uses database queue |
| No Persistent Workers | ✅ Cron-based queue processing |
| No Custom Ports | ✅ Standard HTTP/HTTPS |
| No WebSockets | ✅ Not required |

## Installation

### Local Development

```bash
# Clone repository
git clone <repository-url>
cd outreach-crm

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env
# DB_DATABASE=outreach_crm
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations and seed
php artisan migrate --seed

# Build frontend assets
npm run build

# Start development server
php artisan serve
```

### Production Deployment (Hostinger)

See [DEPLOYMENT.md](DEPLOYMENT.md) for complete Hostinger shared hosting deployment guide.

**Quick Summary:**
1. Create database in hPanel: `u311221303_Haseeb` / `u311221303_vps`
2. Upload files to `public_html/`
3. Set document root to `public_html/public`
4. Configure `.env` with production values
5. Run migrations: `php artisan migrate --force`
6. Build assets: `npm run build`
7. Set up cron: `* * * * * /usr/bin/php /home/u311221303/public_html/artisan schedule:run`
8. Configure SMTP in `.env`

## Project Structure

```
app/
├── Console/Commands/          # Artisan commands
├── Exceptions/                # Exception handling
├── Http/
│   ├── Controllers/           # Controllers by module
│   ├── Middleware/            # Custom middleware
│   ├── Requests/              # Form requests
│   └── Resources/             # API resources
├── Jobs/                      # Queue jobs
├── Models/                    # Eloquent models
├── Policies/                  # Authorization policies
├── Providers/                 # Service providers
├── Services/                  # Business logic services
└── Traits/                    # Reusable traits

database/
├── migrations/                # Database migrations
├── factories/                 # Model factories
└── seeders/                   # Database seeders

resources/
├── js/
│   ├── Components/            # Vue components
│   ├── Layouts/               # Page layouts
│   └── Pages/                 # Inertia pages
├── css/                       # Tailwind CSS
└── views/                     # Blade views (emails, layouts)

routes/
├── web.php                    # Web routes
├── api.php                    # API routes
└── console.php                # Console routes

tests/
├── Feature/                   # Feature tests
└── Unit/                      # Unit tests
```

## Key Models

- **Workspace** - Multi-tenant container
- **User** - Authentication & authorization
- **Lead** - Prospects with full profile
- **Company** - Lead organizations
- **LeadList** - Static/dynamic lead collections
- **Campaign** - Email sequences
- **CampaignStep** - Individual email steps
- **CampaignRecipient** - Lead-campaign progress tracking
- **EmailAccount** - SMTP configurations
- **Message** - Sent email records
- **Pipeline/Stage/Deal** - Sales pipeline
- **Task** - Follow-ups and activities
- **SuppressionList** - Unsubscribe/bounce/complaint tracking

## Environment Variables

```env
APP_NAME="Outreach CRM"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u311221303_Haseeb
DB_USERNAME=u311221303_vps
DB_PASSWORD=@Haseeb786@!$

QUEUE_CONNECTION=database
SESSION_DRIVER=file
CACHE_DRIVER=file

MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
```

## Testing

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test
php artisan test --filter=LeadTest
```

## Cron Jobs

The following scheduled tasks run automatically via cron:

| Task | Frequency | Purpose |
|------|-----------|---------|
| `ProcessCampaignsJob` | Every minute | Send due campaign emails |
| `SendReminderEmailsJob` | Every 5 minutes | Task reminder notifications |
| `CleanupJob` | Daily 2 AM | Clean old logs, messages, imports |

## License

MIT License - See [LICENSE](LICENSE) for details.

## Support

For deployment issues on Hostinger:
1. Check `storage/logs/laravel.log`
2. Verify PHP version and extensions
3. Ensure cron job is configured correctly
4. Test SMTP credentials independently
5. Contact Hostinger support for server-specific issues
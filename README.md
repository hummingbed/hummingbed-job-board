# Hummingbed Job Board

Hummingbed Job Board is a full-stack recruitment marketplace built with
Laravel, Vue, Inertia, and Tailwind CSS. It supports the complete hiring
workflow for candidates, employers, and platform administrators.

The public interface is based on the Job Portal Figma community template and
has been connected to live Laravel data, filters, applications, saved jobs,
contact forms, and newsletters.

## Features

### Public marketplace

- Database-backed homepage, job catalogue, and job-detail pages
- Keyword, location, category, employment type, workplace, experience, date,
  and salary filters
- Latest and salary sorting with pagination
- Featured companies and jobs
- Related job recommendations
- Dynamic platform statistics and category counts
- Newsletter subscriptions and job-specific contact messages
- Responsive Figma-derived layouts

### Candidates

- Candidate dashboard and career metrics
- Profile, biography, location, phone, and visibility settings
- Résumé upload, deletion, and default résumé selection
- Job applications with résumé and cover letter
- Application status tracking and withdrawal
- Saved jobs
- Daily or weekly job alerts
- Interview schedule
- Notification centre and delivery preferences

### Employers

- Company onboarding and verification state
- Employer hiring dashboard and analytics
- Job creation, editing, moderation submission, and deletion
- Applicant pipeline and status management
- Candidate history and cover-letter review
- Interview scheduling
- Dummy subscription plans and billing

### Administrators

- Platform analytics
- Job moderation and featured-job controls
- Company verification and featured-company controls
- User search, role management, suspension, and reactivation
- Abuse-report review
- Dummy subscription visibility
- Category and skill management
- Administrative audit history

### Notifications and automation

- In-app and email notifications
- Employer notifications for new applications
- Candidate application-status updates
- Interview scheduling notifications and reminders
- Scheduled job-alert matching with duplicate-delivery prevention
- Configurable notification preferences

## Technology

- PHP 8.2+
- Laravel 12
- Vue 3
- Inertia.js 2
- Tailwind CSS 3
- Vite 7
- SQLite by default
- PHPUnit 11

## Requirements

- PHP 8.2 or newer
- Composer
- Node.js 20 or newer
- npm
- SQLite and the PHP SQLite extension

## Local installation

Clone the repository and enter the application directory:

```bash
git clone <repository-url> hummingbed-job-board
cd hummingbed-job-board
```

Install PHP and JavaScript dependencies:

```bash
composer install
npm install
```

Create the environment file and application key:

```bash
cp .env.example .env
php artisan key:generate
```

Create the SQLite database when it does not already exist:

```bash
touch database/database.sqlite
```

Run migrations and populate demo data:

```bash
php artisan migrate --seed
```

Start the development services:

```bash
composer run dev
```

The application is normally available at `http://localhost:8000`.

## Database

SQLite is the default connection:

```dotenv
DB_CONNECTION=sqlite
```

When `DB_DATABASE` is omitted, Laravel uses
`database/database.sqlite`. MySQL and PostgreSQL remain available through
Laravel's standard environment variables.

Run database operations with:

```bash
php artisan migrate
php artisan db:seed
php artisan migrate:fresh --seed
```

`migrate:fresh` deletes all existing tables and should only be used when that
data can be discarded.

## Demo data and accounts

The demo seeder is idempotent. Running it repeatedly updates stable demo
records without duplicating companies, jobs, applications, alerts, or billing
records.

All demo accounts use the password `password`.

| Role | Email |
| --- | --- |
| Administrator | `admin@example.com` |
| Employer | `employer@example.com` |
| Recruiter | `recruiter@example.com` |
| Candidate | `candidate@example.com` |
| Candidate | `amara@example.com` |

The seed data includes:

- Three verified companies
- Six published jobs plus pending and draft listings
- Categories and skills
- Candidate profiles and résumés
- Applications, pipeline history, notes, and interviews
- Saved jobs and job alerts
- Dummy subscriptions
- Reports, audit logs, and analytics
- Contact, newsletter, and notification records

Run only the extended marketplace seeder with:

```bash
php artisan db:seed --class=DemoMarketplaceSeeder
```

## Dummy payments

Billing is intentionally simulated. Activating a plan creates a subscription
with a `DUMMY-*` reference and does not call a payment gateway or charge a
card. Replace `DummyBillingController` before accepting real payments.

## Notifications, queues, and scheduled tasks

Local development uses the database queue. Start a worker with:

```bash
php artisan queue:work
```

Run Laravel's scheduler locally with:

```bash
php artisan schedule:work
```

The registered automation commands are:

```bash
php artisan job-alerts:send
php artisan interviews:send-reminders
```

Job alerts run daily at 08:00, and interview reminders are checked hourly in a
traditional Laravel deployment.

## File storage

Local uploads use the default local disk. For production, use an S3-compatible
disk so résumés and future company assets persist across application instances.

```dotenv
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=
AWS_ENDPOINT=
AWS_URL=
```

The S3 Flysystem adapter is already installed.

## Testing and code quality

Run the complete backend test suite:

```bash
php artisan test
```

Build the production frontend:

```bash
npm run build
```

Format PHP files with Laravel Pint:

```bash
./vendor/bin/pint
```

Current verification baseline: 44 tests and 160 assertions.

## Production deployment

Before deploying any Laravel application:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Configure a persistent database, mail provider, object storage, queue worker,
scheduler, HTTPS, backups, and application monitoring.

### Vercel

This repository contains an experimental Vercel PHP Functions setup:

- `vercel.json`
- `api/index.php`
- `.env.vercel.example`
- `.vercelignore`
- protected Vercel Cron endpoints

Read [VERCEL_DEPLOYMENT.md](VERCEL_DEPLOYMENT.md) before deploying. Vercel's
PHP support uses a community runtime, and its filesystem is read-only except
for temporary `/tmp` storage. Therefore:

- Do not use SQLite on Vercel production.
- Use PostgreSQL or MySQL through `DB_URL`.
- Use S3-compatible storage for uploads.
- Use `QUEUE_CONNECTION=sync` because persistent workers are unavailable.
- Configure `CRON_SECRET` for scheduled endpoints.

The application still uses SQLite by default for local development and tests.

## Important routes

| Area | Route |
| --- | --- |
| Homepage | `/` |
| Job marketplace | `/jobs` |
| Candidate workspace | `/candidate` |
| Employer workspace | `/employer` |
| Admin control panel | `/admin` |
| Notifications | `/notifications` |
| Health check | `/up` |

All candidate, employer, and administrator routes enforce authentication,
verified email, account status, and role authorization where applicable.

## Project structure

```text
app/Http/Controllers     Application workflows and page controllers
app/Http/Middleware      Role authorization and Inertia shared data
app/Models               Marketplace domain models
app/Notifications        Queued email and database notifications
app/Console/Commands     Job-alert and interview-reminder automation
database/migrations      Database schema
database/seeders         Core and demo data
resources/js/Pages       Public and authenticated Vue/Inertia pages
resources/js/Layouts     Shared authenticated layout
routes                   Web and scheduled command registration
tests/Feature            Workflow, authorization, deployment, and UI data tests
```

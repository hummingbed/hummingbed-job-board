# Vercel deployment

This repository includes a Vercel Functions entrypoint for Laravel, a Vite
production build, protected Vercel Cron routes, and S3-compatible file storage.

## Architecture and constraints

- PHP runs through the recommended community runtime `vercel-php@0.7.4`
  (PHP 8.3). PHP is not an official Vercel runtime.
- Vercel Functions have a read-only filesystem. Compiled Laravel views and
  caches use `/tmp`; SQLite and local uploads must not be used in production.
- Use an external PostgreSQL or MySQL database. The included environment
  template uses PostgreSQL through `DB_URL`.
- Use S3, Cloudflare R2, DigitalOcean Spaces, or another S3-compatible service
  for résumés and future company assets.
- `QUEUE_CONNECTION=sync` is intentional because Vercel cannot host a
  persistent `queue:work` process.

## 1. Prepare services

Create:

1. A PostgreSQL database, preferably in a region close to the Vercel Function.
2. An S3-compatible bucket.
3. SMTP credentials for notification email.

Copy the variables from `.env.vercel.example` into Vercel Project Settings →
Environment Variables. Generate values locally:

```bash
php artisan key:generate --show
openssl rand -hex 32
```

Use the first value for `APP_KEY` and the second for `CRON_SECRET`.

## 2. Create the project

Import the Git repository in the Vercel dashboard or use the CLI:

```bash
npm install --global vercel
vercel link
vercel env pull .env.vercel.local
```

The repository's `vercel.json` builds the Vite assets in `public/build`, serves
static files directly, and sends all remaining requests to `api/index.php`.

## 3. Initialize the production database

Run migrations from a trusted machine or CI runner using the production
database connection. Do not run migrations inside a request handler.

```bash
cp .env.vercel.local .env.production.local
php artisan migrate --force --env=production.local
php artisan db:seed --force --env=production.local
```

The demo seeder is idempotent and provides the documented demo users. Skip the
second command when production should start without demo accounts.

## 4. Deploy

```bash
vercel deploy --prod
```

After the first deployment, update `APP_URL` to the production domain and
redeploy. Confirm `/up`, `/`, `/jobs`, authentication, an upload, and email.

## Cron jobs

The two cron endpoints validate Vercel's `Authorization: Bearer CRON_SECRET`
header. Both schedules use UTC:

- interview reminders: daily at `07:00` UTC
- job-alert delivery: daily at `08:00` UTC

Daily schedules are compatible with Vercel Hobby. On Pro, the interview cron
may be changed back to hourly (`0 * * * *`) for more precise reminders. Vercel
Cron runs only against production deployments.

## Operational checks

```bash
php artisan test
npm run build
php artisan route:list --path=api/cron
```

Use Vercel Runtime Logs for PHP errors. Never use SQLite, the `public` local
disk, or file-based sessions/cache for production data on Vercel.

## References

- https://vercel.com/docs/functions/runtimes
- https://github.com/vercel-community/php
- https://vercel.com/docs/cron-jobs/manage-cron-jobs
- https://vercel.com/docs/cron-jobs/usage-and-pricing

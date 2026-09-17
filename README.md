# Resort Management System

A Laravel-based resort booking and management platform: public resort website with online booking,
a guest self-service booking portal, PayMongo (QR Ph) online payments with webhook reconciliation,
and a full admin panel (dashboard, inquiries, cottages, content, promo codes, exports, site settings).

The codebase is multi-resort capable — all resort identity, branding, and credentials are
configuration, so the same code can serve a different resort with no source changes
(see [Multi-resort deployment](#multi-resort-deployment)).

## Features

### Guest-facing site

- Home, cottages (list + detail), gallery, services, news/announcements, FAQs, reviews,
  contact/inquiry form, booking policy / privacy / terms pages.
- Availability lookup widget (`GET /availability/check`, throttled, read-only).
- Booking flow (`GET/POST /book`, throttled) with confirmation page
  (`/booking/confirmation/{inquiry}`).
- Dynamic `robots.txt` (derived from `APP_URL`, no static fallback) and `sitemap.xml`.
- JSON-LD structured data on key pages; `/health` monitoring endpoint.

### Guest booking portal

- Booking lookup, booking detail/status, self-service modification and cancellation,
  reviews, payment-proof upload, and invoice view + PDF download
  (`/booking/{inquiry}/...`, all throttled).

### Payments (PayMongo)

- Hosted checkout session creation (`POST /booking/{inquiry}/pay`).
- Signed webhook (`POST /paymongo/webhook`, CSRF-exempt, throttled) with reconciliation,
  duplicate-delivery safety, and late-payment handling.
- Manual payments, deposits, balance/full-payment classification, refunds, and
  cancellation quotes — all money math uses exact string/BCMath arithmetic
  (`App\Support\Money`), never floats.

### Admin panel (`/admin`)

- Dashboard (revenue charts, bookings), availability calendar, activity logs.
- Inquiries: confirm, cancel, mark-paid, refund, resync payment, approve/reject
  payment proofs.
- Content CRUD: cottages, gallery, services, FAQs, news posts, testimonials,
  promo codes, guests, users, site settings.
- CSV exports (inquiries, revenue, guests) with on-screen preview.
- Login with throttling, password reset, role-gated access.

## Screenshots

Sanitized documentation captures live under [`docs/screenshots/`](docs/screenshots/)
(homepage, booking flow, admin dashboard, inquiry management). They are intentionally
independent of `public/` branding assets so the README renders with or without them.
See `docs/screenshots/README.md` for the capture checklist and sanitization rules.

## Tech stack

| Layer      | Technology (verified in repo)                                    |
|------------|------------------------------------------------------------------|
| Backend    | PHP `^8.2`, Laravel `^12` (`composer.json`)                      |
| Frontend   | Vite, Tailwind CSS v4, Alpine.js (+ focus plugin), Chart.js `4.4.1`, flatpickr (`package.json`) |
| Auth/sessions | Database sessions; DB cache; bcrypt round 12                  |
| Database   | Supabase PostgreSQL in production; SQLite for local dev / tests  |
| Storage    | Cloudflare R2 (`FILESYSTEM_DISK=cloudflare`); S3 fallback config present but unused |
| Email      | Resend in production (queued mailables); `log` driver for dev    |
| Payments   | PayMongo (hosted checkout + webhooks)                            |
| PDFs       | `barryvdh/laravel-dompdf` (invoice PDF download)                 |
| Testing    | PHPUnit `^11` — `Unit` + `Feature` suites (`phpunit.xml`)        |
| Deployment | Vercel (`vercel-php` serverless function, cron); see `vercel.json` |

## Requirements

- PHP 8.2+ with BCMath, Composer 2
- Node.js 18+ and npm
- SQLite (local dev) or PostgreSQL (production parity)

## Local development (XAMPP + SQLite)

One-command setup (from `composer.json` `setup` script — installs deps, copies
`.env.example` to `.env` if missing, generates a key, migrates, and builds assets):

```bash
composer setup
php artisan db:seed
```

Or step by step:

```bash
composer install
copy .env.example .env        # Windows (XAMPP); use `cp` on macOS/Linux
php artisan key:generate
# .env: keep the SQLite lines (DB_CONNECTION=sqlite); production DB block stays commented
php artisan migrate --force
php artisan db:seed
npm install
npm run build                 # or `npm run dev` for HMR while developing
php artisan serve
```

Run everything concurrently (server + queue listener + log tail + Vite), per the
composer `dev` script:

```bash
composer dev
```

Useful commands:

```bash
php artisan test              # full suite (via composer test, clears config first)
vendor/bin/phpunit --colors=never
php artisan storage:link      # local-disk uploads
php artisan reservations:release-expired --hours=24
```

## Environment variables

Copy `.env.example` to `.env` and set per deployment. Grouped summary (see the
example file for inline guidance):

| Group | Keys |
|-------|------|
| App | `APP_NAME`, `APP_URL`, `APP_KEY`, `APP_ENV`, `APP_DEBUG`, `BOOKING_REF_PREFIX` (`RS-` default; only affects new codes) |
| Database | Prod: `DB_CONNECTION=pgsql` + `DB_HOST/PORT/DATABASE/USERNAME/PASSWORD/SSLMODE` (Supabase). Dev: `DB_CONNECTION=sqlite` |
| Sessions/cache | `SESSION_DRIVER=database`, `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE=true` (prod HTTPS), `CACHE_STORE=database` |
| Mail | Prod: `MAIL_MAILER=resend`, `MAIL_FROM_ADDRESS/NAME`, `RESEND_API_KEY`. Dev: `MAIL_MAILER=log` |
| Storage | `FILESYSTEM_DISK=cloudflare` + `CLOUDFLARE_R2_*` (`ACCESS_KEY_ID`, `SECRET_ACCESS_KEY`, `BUCKET`, `URL`, `ENDPOINT`) |
| Payments | `PAYMONGO_SECRET_KEY`, `PAYMONGO_WEBHOOK_SECRET` (Dashboard → Developers → API Keys) |
| Cron | `CRON_SECRET` — strong random value; the literal `change-me` is rejected |
| Proxies | `TRUSTED_PROXIES` — edge IPs/CIDRs allowed to set `X-Forwarded-*` (Vercel/Cloudflare); empty trusts none |

Queue note (from `.env.example`): all mailables are queued — keep `QUEUE_CONNECTION=sync`
until a queue worker runs (`php artisan queue:work`); `database` without a worker just
piles jobs that are never sent.

## Testing

- Suites: `tests/Unit` and `tests/Feature` (`phpunit.xml`); tests run on SQLite
  `:memory:` with array cache/session/mail drivers.
- Current checkpoint: **833 tests, 2,958 assertions, green**.
- Resort-identity gates guard multi-resort neutrality:
  `php artisan test --filter="ResortIdentity"` before launch (see
  `docs/second-resort-deployment.md` §6).

## Production deployment (Vercel + Supabase)

Configured in `vercel.json` (verified):

- Build: `npm run build`; output directory: `public`.
- PHP entry: `api/index.php` on `vercel-php@0.8.0`, `maxDuration` 300.
- All routes rewrite to `/api/index.php`; `/health` doubles as the platform check.
- Cron: `/cron/reservations` on `0 2 * * *` — invoked via GET with the
  `CRON_SECRET` bearer token; the Laravel scheduler (`routes/console.php`) remains
  the primary trigger on hosts with a worker.
- Cache headers for built assets, served images, `sitemap.xml`, and `robots.txt`.

Deploy checklist:

1. Set all production env vars in the Vercel dashboard (DB, R2, Resend, PayMongo,
   `CRON_SECRET`, `APP_URL`, `SESSION_DOMAIN`, `TRUSTED_PROXIES`).
2. Run `php artisan migrate --force` + `php artisan db:seed` against Supabase.
3. `php artisan config:clear`, then full `php artisan test` must be green.
4. Point the PayMongo webhook at `https://<APP_URL>/paymongo/webhook` and match
   `PAYMONGO_WEBHOOK_SECRET`.
5. Run a queue worker if `QUEUE_CONNECTION` is anything other than `sync`.

## Operations

- **PayMongo webhook:** `POST /paymongo/webhook` (CSRF-exempt, `throttle:webhook`).
  Keep `PAYMONGO_WEBHOOK_SECRET` in sync with the PayMongo dashboard.
- **Expired holds:** `reservations:release-expired` (scheduler primary) or
  `GET|POST /cron/reservations` with `Authorization: Bearer <CRON_SECRET>`
  (`throttle:cron`); explicit `--hours=` / `?hours=` overrides `booking_hold_hours`.
- **Storage:** R2 bucket must serve uploads publicly; locally run
  `php artisan storage:link`.
- **Cache:** production uses `composer build` (`config:cache`, `route:cache`,
  `view:cache`); clear with `php artisan config:clear` when diagnosing env issues.

## Multi-resort deployment

No code changes needed — see [`docs/second-resort-deployment.md`](docs/second-resort-deployment.md).
Checklist summary: replace `APP_NAME`, `APP_URL`, `SESSION_DOMAIN`,
`MAIL_FROM_*`, `BOOKING_REF_PREFIX`, all credentials; migrate + seed (seeds are
`firstOrCreate`, safe to re-run); rebrand via admin → Site Settings and content CRUD
(brand assets are Storage paths; empty values fall back to neutral placeholders, so a
fresh install never shows the reference resort's branding); repoint webhook, schedule
expiry release, verify with the `ResortIdentity*` gates + full suite.

## Project structure

```text
app/            # Controllers (incl. Admin/), Models, Services, Support/Money, Console Commands
routes/         # web.php (public/booking/payments/cron) + admin.php + console.php (scheduler)
resources/views # layouts, pages, admin, emails, components, errors
resources/js|css # Vite + Tailwind entry points (see vite.config.js)
config/         # session/database/mail/queue/filesystems tuned for Vercel + R2
database/       # migrations, factories, seeders (starter content)
api/index.php   # Vercel serverless entry
public/         # web root + built assets (build/ after `npm run build`)
docs/           # roadmap-p0-p12, second-resort-deployment, lighthouse-ui-checklist, screenshots/
tests/          # Unit + Feature suites (incl. money characterization coverage)
```

## Further docs

- `docs/roadmap-p0-p12.md` — phased delivery roadmap.
- `docs/second-resort-deployment.md` — new-resort launch checklist.
- `docs/lighthouse-ui-checklist.md` — frontend performance/UI checklist.

## License

MIT — see `composer.json` (`laravel/laravel` skeleton license).

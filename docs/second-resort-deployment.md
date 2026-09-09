# Second-Resort Deployment Checklist

Deploying this codebase for a resort other than the reference installation?
Work through this list. No source-code changes are required — everything is
configuration, database settings, or uploaded assets.

## 1. Environment (`.env`, copied from `.env.example`)

Replace every template value (all `*.example.com` placeholders):

- `APP_NAME` — the resort's name (used in titles, emails, invoices, admin).
- `APP_URL` — the canonical production URL (drives canonical links, sitemap,
  robots.txt, storage URLs, PayMongo return URLs).
- `SESSION_DOMAIN` — the production cookie domain.
- `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` — the resort's sender identity.
- `BOOKING_REF_PREFIX` — the resort's initials plus dash (e.g. `SP-`).
  Existing booking codes keep working; this only affects new ones.
- Database (`DB_*`), storage (`CLOUDFLARE_R2_*` or `AWS_*`), mail
  (`RESEND_API_KEY`), payments (`PAYMONGO_SECRET_KEY`,
  `PAYMONGO_WEBHOOK_SECRET`) and `CRON_SECRET` (never ship `change-me`).

## 2. Migrate and seed

```bash
php artisan migrate --force
php artisan db:seed
```

Seeds are starter content (`firstOrCreate`): re-running never overwrites
values already configured through the admin panel.

## 3. Site Settings (admin → Site Settings)

At minimum, replace: `site_name`, `site_description`, `contact_email`,
`contact_phone`, `address`, `operating_hours`, `hero_*`, section headings,
`facebook_url` / `instagram_url` / `tiktok_url`, `map_lat`, `map_lng`,
`map_embed_url` (must point at the new resort's map pin, not the seeded one),
`geo_region`, `geo_placename`, `address_locality`, `address_region`,
`address_country`, `about_body`, `footer_tagline`, `legal_privacy`,
`legal_terms`, `legal_booking_policy` (the dashboard warns while these still
contain draft copy), `booking_cutoff_hours`, `booking_hold_hours`,
`analytics_ga4_id`.

Brand assets are Storage paths: upload the resort's files and set
`site_logo`, `site_favicon`, `hero_background` and `og_image`. Empty values
fall back to the neutral shipped placeholders — a fresh install can never
present the reference resort's branding.

## 4. Content (admin CRUD)

Replace or rewrite: cottages (names, rates, photos, amenities), gallery,
services, FAQs, news posts and testimonials. Starter rows are generic;
anything resort-specific should be edited or deleted.

## 5. Operations

- Point the PayMongo webhook at `/paymongo/webhook` and confirm the signing
  secret matches `PAYMONGO_WEBHOOK_SECRET`.
- Schedule `reservations:release-expired` (or rely on `/cron/reservations`
  with the `CRON_SECRET` bearer token). An explicit `--hours` / `?hours=`
  value always overrides the `booking_hold_hours` setting.
- Run `php artisan storage:link` (local disk) or confirm the R2 bucket serves
  uploaded images publicly.

## 6. Verification

```bash
php artisan config:clear
php artisan test --filter="ResortIdentity"
php artisan test
```

- `ResortIdentityTest` — Phase 1 identity gate (6 tests).
- `ResortIdentityPhase2Test` — Phase 2 gate: logo, geo, rules (8 tests).
- `ResortIdentityPhase3Test` — Phase 3 gate: page copy, SEO, draft warning (5 tests).
- `ResortIdentityPhase4Test` — Phase 4 gate: fresh unseeded deploy neutrality (4 tests).
- Full suite must be green before launch.

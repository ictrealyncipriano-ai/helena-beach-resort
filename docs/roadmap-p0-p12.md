# Roadmap Notes — P0.2 → P1.2

Consolidated from codebase inspection 2026-09-10. Slice boundary: `.opencode/agents/*` untouched (`M reviewer.md`, `?? reusability-auditor.md` excluded from commits). Local `.env DB_CONNECTION=sqlite` — no `migrate --force` / `db:seed` locally; prod writes via Vercel only.

## P0.2 Green — DONE

- No code changed, no commit made.
- Vercel-ready validation (inspection only):
  - 51 migration files, incl. `database/migrations/2026_09_07_000001_phase4_supabase_indexes_and_backfill.php:24` pgsql `IF NOT EXISTS` indexes + CHECKs.
  - `database/seeders/DatabaseSeeder.php:25,40` creates `admin@helenaresort.com/super_admin` + `staff@helenaresort.com/staff` via `firstOrCreate`, random 32-char password if `ADMIN/STAFF_INITIAL_PASSWORD` unset. Calls `SiteSetting, Cottage, Photo, Faq, Testimonial, Service, Post` — all `firstOrCreate`, re-seed safe. `GallerySeeder.php:10` orphan no-op, never called.
- Reference gates: `ResortIdentityTest` (Ph1), `ResortIdentityPhase2/3/4Test` — see `docs/second-resort-deployment.md:65-77`.

## P0.3 Vercel Provision — Vercel-side, no code

Per `docs/second-resort-deployment.md:7-20` + `.env.example:1-100`:

1. Dashboard env: `APP_NAME`, `APP_URL`, `SESSION_DOMAIN`, `MAIL_FROM_ADDRESS/NAME`, `BOOKING_REF_PREFIX`, `DB_*` (`DB_SSLMODE=require`), `CLOUDFLARE_R2_*` (or `AWS_*`), `RESEND_API_KEY`, `PAYMONGO_SECRET_KEY`, `PAYMONGO_WEBHOOK_SECRET`, `CRON_SECRET` (never `change-me` — `app/Http/Controllers/CronController.php:19-26,65-82` fail-closed via `hash_equals`).
2. Run on Vercel/Supabase only:
   `php artisan migrate --force`
   `php artisan db:seed`
   Verify row counts, admin/staff login, `site_settings` values.
3. Runtime wiring (`vercel.json:3-16`, `routes/console.php:15,21,25`, `api/index.php:19-36`):
   - Build `npm run build`, output `public`, `api/index.php` `vercel-php@0.8.0` max 300s.
   - Cron `0 2 * * * /cron/reservations` + `reservations:release-expired daily 02:00`, `payments:reconcile 10min`, `payments:retry-refunds 30min` — confirm bearer fires.
   - PayMongo webhook → `/paymongo/webhook` (`app/Services/PayMongoService.php:140` HMAC, 300s skew).
   - R2 bucket public or `storage:link`; `QUEUE_CONNECTION=sync` until `queue:work` runs (`config/queue.php:16`).

## P0.4 Content + Verify — Vercel-side, no code

Per `docs/second-resort-deployment.md:32-63`:

1. Admin → Site Settings minimums: `site_name/description`, `contact_email/phone`, `address`, `operating_hours`, `hero_*`, section headings, `facebook/instagram/tiktok_url`, `map_lat/lng/embed_url`, `geo_region/placename`, `address_locality/region/country`, `about_body`, `footer_tagline`, `legal_privacy/terms/booking_policy` (dashboard warns on `Draft` — `app/Http/Controllers/Admin/DashboardController.php:98-106`), `booking_cutoff_hours` (24) / `booking_hold_hours` (48) (`database/seeders/SiteSettingSeeder.php:68-69`), `analytics_ga4_id`, `site_logo/favicon`, `hero_background`, `og_image`.
2. Rewrite: cottages, gallery, services, FAQs, posts, testimonials.
3. Verify:
   `php artisan config:clear`
   `php artisan test --filter=ResortIdentity`
   `php artisan test`
4. Manual QA still open (`docs/lighthouse-ui-checklist.md:18-70` — table empty, §§2-6 unchecked): keyboard trap/Escape, 360/390/768 breakpoints, dark waves/dividers, flatpickr/GA/reduced-motion/offline, zero `console.error`, guards (`beach-/sand-`, `SiteSetting::getValue` in views) = 0.

## P1.1 Cancellation Tiers — first code change

Current state is full-only (evidence):
- `app/Services/BookingCancellationService.php:30-74` refunds `collectedAmount()/refundableAmount()` (`app/Models/Inquiry.php:299`).
- `app/Services/RefundService.php:92-96` comment: full-only refunds of whatever was collected; ledger `TYPE_REFUND=refundableAmount()`.
- `app/Http/Controllers/Admin/InquiryController.php:407-463` refunds exactly collected, no tier calc, no override audit.
- Zero hits for `cancellation_policy_json|tiered` in `app/`, `database/migrations/`.
- Guest copy hardcodes free cancel: `resources/views/pages/booking-detail.blade.php:257`, `resources/views/pages/book.blade.php:162`.

Work:
1. New `cancellation_policy_json` SiteSetting, e.g. `{tiers:[{hours_before:168,refund_pct:100},{72:50},{24:0}], default:0}` + seeder default + admin docs in `resources/views/admin/site-settings/index.blade.php:28`.
2. Pure `CancellationQuote(inquiry, now)` → `{pct, refund_amount, forfeit}`. Single source used by guest `BookingCancellationService::processRefund`, admin `refund()`, `PayMongoService::refund(amount)`, `RefundService` ledger write.
3. `finalizeCancellation` stores `refund_amount=quote`, `releaseBlocks()`, `reverseStay()` if confirmed. Admin override `amount + reason` → `ActivityLogger.php:22 record('refund.override', …)` — closes gap (today only `Log::warning` in `BookingCancellationService.php:42,59`; `RefundService`/`RetryRefunds` emit no `ActivityLog`; manual-money offline completion unlogged).
4. Update guest/admin/emails/legal copy from cutoff-only to tier copy (`PageController.php:95-98` `legal_booking_policy` via `HtmlSanitizer`).
5. Tests: quote unit (boundaries, default, invalid JSON fallback), guest tier end-to-end, admin override audit, `tests/Feature/Payments/PaymentStateAuditTest.php:144` invariants hold (ledger==summary, late nets zero, no false success).

Open decision: tier shape — default proposal 168h/100, 72h/50, 24h/0, else confirm values.

## P1.2 Deposit Hardening — ex Phase-6 WP-1+

Baseline locked in `tests/Feature/Payments/DepositPolicyCharacterizationTest.php:59-275` (WP-0, intentional):
- Guest booking carries no deposit; admin accepts arbitrary deposit incl. `> total` (`:81-102`) — no % rule, no `<= total` cap.
- Confirm has no financial gate (`:104-118`; `Admin/BookingActionsController.php:28` pending-only).
- Raise `1500→2000` keeps stale `deposit_paid_at`, still reads paid (`:120-143`).
- Any `₱1` blocks `canModify` via `hasPayments()` (`app/Services/BookingEligibility.php:55`, test `:237-254`).
- `isDepositPaid()` = timestamp OR coverage, `amountDueNow()` deposit-first (`app/Models/Inquiry.php:189,215`).

Work: `deposit <= total` cap + optional % rule, confirm requires `isDepositPaid()`, clear stale stamp on raise, modify gate on deposit-coverage, proof-approve defaults follow new due-now.

## Order

P0.3 → P0.4 (no code) → P1.1 (first code) → P1.2. Confirm tier %s before P1.1 implementation starts.

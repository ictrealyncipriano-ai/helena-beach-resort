# Lighthouse + Manual UI Checklist

Reusable acceptance checklist for any public-facing Blade / CSS / Alpine change
in Helena Beach Resort. Run it after UI refactors so results stay comparable
over time instead of living in chat history.

## 0. Automated gates (run first)

- `php artisan view:cache` then `php artisan view:clear` — must pass before
  anything else; names the exact broken Blade file.
- Targeted tests first (`php artisan test --filter=<Name>`), full suite only
  when targeted is green. Current suite runs on sqlite `:memory:`; invoke
  phpunit directly (`php vendor/phpunit/phpunit/phpunit`) if the shell
  swallows PHP stdout.
- `npm run build` — confirm `admin-auth` CSS and the shared `theme-toggle`
  chunk are in `public/build/manifest.json`, imported by both entries.

## 1. Lighthouse (mobile, Chrome DevTools, one run per page)

Pages: `/` (home), `/book`, `/cottages`, one `/cottages/{slug}`,
`/contact`, one admin index (signed in as admin).

| Page | Perf | A11y | Best practices | SEO | Notes |
| ---- | ---: | ---: | -------------: | --: | ----- |
| `/` | | | | | |
| `/book` | | | | | |
| `/cottages` | | | | | |
| `/cottages/{slug}` | | | | | |
| `/contact` | | | | | |
| admin index | | | | | n/a |

Thresholds: Performance ≥ 90 (warn on any regression vs the previous run),
Accessibility ≥ 95 with zero *new* errors, Best Practices ≥ 90.
Record scores + date; keep old rows for history.

## 2. Keyboard-only pass (no mouse)

- [ ] Tab order matches visual order on home, `/book`, cottage cards.
- [ ] Mobile drawer opens, traps focus, closes on `Escape`, returns focus.
- [ ] Widget: select cottage → pick dates → `Book This Cottage` enables and
      carries `cottage_id`/`booking_type`/`check_in`/`check_out` to `/book`.
- [ ] Card `Book` reachable without activating the whole card.
- [ ] Gallery captions visible on focus; carousel arrows + dots operable.
- [ ] No keyboard trap anywhere; focus indicator visible on all controls.

## 3. Breakpoints

- [ ] 360×740 and 390×844 — CTAs visible without horizontal scroll.
- [ ] 768px — desktop nav does not wrap; drawer is the only nav.
- [ ] Desktop — grids (cottages 3-col, gallery 4-col) intact.
- [ ] No horizontal scroll at any width (watch `text-8xl` hero, tables).

## 4. Dark mode

- [ ] Hero/CTAs waves and dividers visible on `dark:bg-slate-800`.
- [ ] `book` summary dividers visible (`dark:border-teal-700`).
- [ ] Calendar blocked days (`red-300 line-through`) readable on dark.
- [ ] Auth pages (login / forgot / reset) gradient + card legible.

## 5. Degraded-JS checks

- [ ] Block `flatpickr.js` — `YYYY-MM-DD` typed dates still submit.
- [ ] Block GA — cookie banner still accepts/declines, no errors.
- [ ] `prefers-reduced-motion` — `.reveal` content visible, no animation.
- [ ] Offline availability fetch — widget shows error + Retry.

## 6. Console

- [ ] Zero `console.error` on every page above (watch the shared
      `theme-toggle` chunk split, Alpine `x-trap`, flatpickr 50 ms poll).

## 7. Manual guard checklist (no CI — run by hand, keep at 0 unless noted)

- `beach-` / `sand-` in `resources/views`: 0.
- No-op `bg-teal-700` + `hover:bg-teal-700` on the same element: 0
  (allowed: `bg-teal-600 → hover:bg-teal-700` one-shade step,
  `hover:bg-teal-700/50` translucent overlays).
- `@include('components.admin.pagination|empty-state|badge|confirm-dialog')`: 0.
- Manual breadcrumb `<nav class="flex items-center gap-1 text-xs">`: 0.
- `SiteSetting::getValue` in views: only emails / invoice / report-layout
  (off-request renders keep direct access deliberately).

## 8. Run log

| Date | Scope | Tests | Build | Lighthouse | Verdict |
| ---- | ----- | ----- | ----- | ---------- | ------- |
| 2026-09-05 | Phase 1+2+3A–H+E–H | targeted green; pre-existing calendar reds* | green | n/a (manual) | pass with notes |
| 2026-09-05 | Test-clock freeze (`tests/TestCase.php` → 2026-08-15 12:00) | **full suite green: 528 tests, 1807 assertions, 3 skipped, 0 failures** | green | n/a (manual) | pass — calendar reds resolved |
| | | | | | |

\* Pre-existing reds proven identical on pristine `bcec339` via worktree:
`BookingFlowTest`, `Phase5BookingUxTest` (minus the CTA test, fixed),
`DepositPaymentFlowTest`, `PaymentFlowTest`, `Phase8CodeQualityTest`,
`AdminMiddlewareTest`, `RefundServiceTest` — all hardcode 2026-09-01/03
booking dates that now lie in the past, so validation rejects and helpers
get null inquiries. Unrelated to UI slices (no validation/route/model file
in the arc diff).

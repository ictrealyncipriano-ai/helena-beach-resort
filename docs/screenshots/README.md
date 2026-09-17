# Documentation screenshots

Sanitized UI captures referenced by the main README. They must be representative of
the **current implementation** — never mock-ups, never branding pulled from `public/`.

## Files (exact names)

| File                    | Page / state to capture                        |
|-------------------------|------------------------------------------------|
| `homepage.png`          | Public homepage (`/`)                          |
| `booking-flow.png`      | Booking form (`/book`) with availability result |
| `admin-dashboard.png`   | Admin dashboard (`/admin/dashboard`)           |
| `inquiry-management.png`| Admin inquiry detail with booking actions      |

Embed in `README.md` only after the files land, e.g.:

```md
![Homepage](docs/screenshots/homepage.png)
```

## Sanitization rules (mandatory)

- No guest names, emails, phone numbers, or real booking references/codes.
- No payment secrets, amounts tied to real people, API keys, or private URLs.
- Use seeded/demo content only; prefer a fresh `php artisan migrate --force && php artisan db:seed`.
- Capture at 1440×900 (or 1280×800); PNG; keep each file under ~500 KB.
- Do not include the production resort logo as README identity — screenshots may
  show the running site's neutral/seeded branding only.

## How to capture

1. `composer setup && php artisan db:seed && npm run build` (or `composer dev` + `npm run dev`).
2. `php artisan serve`, open the pages above in a private window.
3. Save captures here with the exact filenames, then wire the `![...](...)` embeds
   into the Screenshots section of `README.md`.

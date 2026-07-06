# Printly

SaaS for printing businesses: print shops near schools, neighborhood printers, and custom
print businesses for paper, shirts, tarpaulins, and similar jobs. Customers order and pay
online, present a QR code in store, and track their job in a real-time queue. Stores get
auto-pricing, a live queue, and an optional local agent that routes jobs to physical printers.

## Status

Printly includes auth, RBAC, audit logs, the admin shell, stores, catalog and pricing,
orders and queue management, customer payments through PayMongo, an auto-print bridge,
and subscriptions with plan gating. Plans are defined in `backend/config/plans.php`
(`starter`, `pro`, `auto`); `auto` unlocks auto-print, and `pro`+ unlocks online payments.
Feature gates are enforced server-side through `Store::allows()` and the `plan:` middleware,
and surfaced in the UI on each store's Subscription page.

## Structure

- `backend/` - Laravel API.
- `frontend-v2/` - Vue 3 + Vite SPA for the admin shell and public storefront.
- `printly-landing/` - standalone static landing page.
- `planning/` - product validation, data model, and architecture notes.
- `tools/print-agent/` - local print-agent prototype.

## Local Dev

**Backend** (boots on SQLite out of the box):

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

Seeded logins, all using `password`:

- Platform admin: `admin@printly.test`
- Demo store owner: `owner@printly.test`
- Demo store staff: `staff@printly.test`

The seed creates a demo shop, **Campus Print Hub**, with a working catalog and storefront at
`/s/campus-print-hub`: upload, quote, order, track, and manage the store queue.

**Frontend**:

```bash
cd frontend-v2
npm install
npm run dev
```

### Running All Services

The full local flow needs four processes. From `backend/`:

```bash
composer dev
```

This starts the API server on `:8000`, queue worker, Reverb on `:8080`, and frontend Vite
on `:5173`.

| Process | Command from `backend/` | Why it is needed |
|---|---|---|
| API server | `php artisan serve` | Laravel API on `:8000`. |
| Queue worker | `php artisan queue:listen` | File analysis runs as a queued job. |
| Reverb | `php artisan reverb:start` | Real-time queue board, order tracking, and chat. |
| Frontend | `cd ../frontend-v2 && npm run dev` | Vue SPA on `:5173`. |

The frontend falls back to polling when `VITE_REVERB_APP_KEY` is blank in `frontend-v2/.env`.
To use websockets, set it to the backend's `REVERB_APP_KEY` and run `reverb:start`.

The production target is MySQL + Reverb. `npm run build` is the frontend production build check;
`npm run typecheck` may still report strictness warnings in older shared UI pieces.

## Planning Docs

- [`planning/00-validation.md`](planning/00-validation.md)
- [`planning/01-data-model-and-architecture.md`](planning/01-data-model-and-architecture.md)
- [`planning/02-clone-and-strip.md`](planning/02-clone-and-strip.md)

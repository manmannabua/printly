# Printly (working name)

SaaS for printing businesses — print shops near schools, neighborhood printers, and custom
print businesses (paper, t-shirts, tarpaulins, etc.). Customers order + pay online, present a
QR in store, and track their job in a real-time queue. Stores get auto-pricing, a live queue,
and an optional local agent that auto-routes jobs to physical printers.

Foundation cloned/stripped from the HRIS stack (Laravel 12 + Vue 3 + Reverb), via the proven
`real-estate` core skeleton.

## Status
Clean skeleton — auth, RBAC (Printly permission catalog), audit log, admin shell
(dashboard / users / roles / audit-logs), and the full UI component kit. Domain modules
(stores, catalog, orders/queue, payments) are the next build, per the planning docs.

## Structure
- `backend/` — Laravel API. Core models: User, Role, Permission, RolePermissionAudit, AuditLog.
- `frontend-v2/` — Vue 3 + Vite SPA (admin shell + UI kit).
- `planning/` — validation, data model & architecture, clone-and-strip runbook.

## Local dev
**Backend** (boots on SQLite out of the box):
```
cd backend
composer install
cp .env.example .env   # or use the committed local .env (sqlite)
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```
Seeded logins (all `password`):
- Platform admin: `admin@printly.test`
- Demo store owner: `owner@printly.test` · staff: `staff@printly.test`

The seed also creates a ready-to-use demo shop **Campus Print Hub** with a working
catalog (document + tarpaulin), so the storefront works out of the box at
`/s/campus-print-hub` — upload/quote → order → track, and the store queue board.

**Frontend**:
```
cd frontend-v2
npm install
npm run dev
```

### Running all services (needed for the full flow)

The app needs **four** processes in local dev, not just `serve` + `vite`. One command
runs them all (from `backend/`):

```
composer dev   # → server (:8000) + queue worker + reverb (:8080) + frontend vite (:5173)
```

Or run them in separate terminals:

| Process | Command (from `backend/`) | Why it's needed |
|---|---|---|
| API server | `php artisan serve` | The Laravel API on `:8000`. |
| **Queue worker** | `php artisan queue:listen` | **Required for file uploads.** File analysis (page count / paper size, which drives file-based pricing) runs as a queued job. Without a worker, an uploaded file stays `analysis_status: pending` forever and the quote never appears. |
| Reverb (websockets) | `php artisan reverb:start` | Real-time queue board + order tracking + chat. Broadcasts are queued; without Reverb they pile up in `failed_jobs` (harmless to the order flow, but no live updates). |
| Frontend | `cd ../frontend-v2 && npm run dev` | The Vue SPA on `:5173`. |

> **Realtime toggle:** the frontend falls back to polling when `VITE_REVERB_APP_KEY` is
> **blank** in `frontend-v2/.env` (the committed default). To use websockets, set it to the
> backend's `REVERB_APP_KEY` and run `reverb:start`.
>
> **Note:** `backend/resources/js` + `backend/vite.config.js` are vestigial from the HRIS
> clone and are not used — the SPA lives entirely in `frontend-v2/`.

> Production target is MySQL + Reverb (see `planning/01-data-model-and-architecture.md`).
> The production `vite build` passes clean; `npm run typecheck` still reports pre-existing
> strictness warnings in the inherited UI kit (they don't affect the build).

## Planning docs
- [`planning/00-validation.md`](planning/00-validation.md)
- [`planning/01-data-model-and-architecture.md`](planning/01-data-model-and-architecture.md)
- [`planning/02-clone-and-strip.md`](planning/02-clone-and-strip.md)

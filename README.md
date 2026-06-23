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
Seeded admin: `admin@printly.test` / `password`.

**Frontend**:
```
cd frontend-v2
npm install
npm run dev
```

> Production target is MySQL + Reverb (see `planning/01-data-model-and-architecture.md`).
> Type-check (`npm run typecheck`) currently reports pre-existing strictness warnings in the
> inherited UI kit; the production `vite build` is unaffected.

## Planning docs
- [`planning/00-validation.md`](planning/00-validation.md)
- [`planning/01-data-model-and-architecture.md`](planning/01-data-model-and-architecture.md)
- [`planning/02-clone-and-strip.md`](planning/02-clone-and-strip.md)

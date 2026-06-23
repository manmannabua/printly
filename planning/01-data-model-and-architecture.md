# Printly — Data Model & MVP Architecture

> Scope: the v1 MVP from [`00-validation.md`](00-validation.md) §7 — single-store SaaS,
> PDF-first, manual printing, GCash/guest payment, real-time queue. Schema is designed to
> *accommodate* phase-2 (spec-based products, print agent, marketplace) without rework, but
> those flows are not built in v1.
> Date: 2026-06-23

---

## 1. Architecture overview

```
                            ┌─────────────────────────────────────────┐
                            │            PRINTLY CLOUD (Laravel)        │
   Customer (mobile web)    │                                          │
   ┌──────────────┐         │  ┌────────────┐   ┌──────────────────┐   │
   │ Storefront   │ ──REST──┼─▶│  API        │──▶│ Pricing engine    │  │
   │ upload/pay/  │ ◀─WS────┼──│ (Sanctum)   │   ├──────────────────┤   │
   │ QR/status    │         │  │             │   │ File analysis     │   │
   └──────────────┘         │  └─────┬───────┘   │ (PDF→pages/size)  │   │
                            │        │           ├──────────────────┤   │
   Store staff (web)        │  ┌─────▼───────┐   │ Payment service   │   │
   ┌──────────────┐         │  │ Reverb (WS) │   │ (PayMongo/Xendit) │   │
   │ Queue board  │ ◀─WS────┼──│ broadcast   │   └──────────────────┘   │
   │ accept/print │ ──REST──┼─▶│             │   ┌──────────────────┐   │
   └──────────────┘         │  └─────────────┘   │ MySQL  │  Storage │   │
                            │                    │ (orders)│ (files)  │   │
                            └────────────────────┴──────────────────────┘
                                         ▲ (phase 2)
                            ┌────────────┴────────────┐
                            │  PRINT AGENT (in store)  │  polls jobs, maps to
                            │  desktop/RPi service     │  physical printers,
                            │  (model on biometric-    │  reports status back
                            │   bridge)                │  over same WS/REST
                            └──────────────────────────┘
```

**Components**
- **Cloud app** — Laravel API + Vue 3 SPA (storefront + store dashboard), MySQL, object/file
  storage, Reverb for WebSockets. Cloned from HRIS.
- **Print agent** — *phase 2.* Local service, same pattern as `biometric-bridge`: authenticates
  as a store, long-polls/subscribes for jobs assigned to a printer it manages, downloads the
  file, prints, posts status. v1 has **no agent** — staff print from the dashboard.

**Deployment topology** (one codebase, three modes):
- *Cloud shared* — multi-tenant, `store_id` scoping on every query (default for v1).
- *Dedicated VM* — same code, one tenant, isolated DB.
- *Local in-store* — same code on a mini-PC/NUC; payments optional (can run cash-only offline).

---

## 2. Tech stack (reuse from HRIS)

| Layer | Choice | Source |
|---|---|---|
| Backend | Laravel 11, PHP 8.3 | HRIS |
| Auth | Sanctum session + guest tokens | HRIS auth, adapted for guest |
| Frontend | Vue 3 + Vite + component system | `frontend-v2` |
| Real-time | Laravel Reverb | HRIS |
| DB | MySQL 8 | HRIS |
| Files | `Storage` (local disk dev / S3-compatible prod) | HRIS `private` disk pattern |
| PDF analysis | `smalot/pdfparser` (page count) + `Imagick`/Ghostscript (size, color) | new |
| Office→PDF | LibreOffice headless (`soffice --convert-to pdf`) | new |
| Payments | PayMongo (GCash/Maya/card) | new |
| Queue/jobs | Laravel queue (Redis or DB) for file analysis + conversion | HRIS |

---

## 3. Order lifecycle (state machine)

```
 draft ──submit──▶ pending_payment ──pay──▶ paid ──store accept──▶ accepted
                        │                     │                      │
                    (timeout/                 │                  in_progress
                     cancel)              (store reject          (printing)
                        ▼                  → refund)                 │
                    cancelled            ◀──────────                 ▼
                        ▲                                          ready
                        │                                            │
                    refunded ◀──── failed ◀── (print error)     (pickup)
                                                                     ▼
                                                                completed
```

State notes:
- **draft** — files uploaded, pricing computed, not yet submitted.
- **pending_payment** — awaiting GCash/card; PSP webhook flips to `paid`. (Cash/in-store
  option can skip straight to `accepted` if store allows pay-on-pickup.)
- **paid** — money captured (or authorized — see §6). QR is now valid; appears on store queue.
- **accepted** — store acknowledged; (phase 2: auto-assigned to a printer/agent).
- **in_progress / ready / completed** — printing → done → handed over (QR scan completes).
- **rejected / failed** → triggers **refund** (or wallet credit).

The transitions are the single source of truth; both Reverb broadcasts and audit log hang off
state changes.

---

## 4. Data model

Conventions follow HRIS: UUID PKs, `store_id` scoping, soft deletes where it matters,
`created_at/updated_at`. Money stored as **integer centavos** (`amount_cents`), never floats.

### 4.1 Tenancy & identity

```
stores
  id (uuid, pk)
  name, slug (unique)            -- slug = public storefront URL /s/{slug}
  plan                           -- starter | pro | auto
  status                         -- active | suspended | trial
  timezone, currency (PHP)
  lat, lng, address              -- nullable in v1; used by marketplace (phase 2)
  settings (json)               -- accepts_guest, pay_on_pickup_allowed, auto_print, etc.
  created_at, updated_at, deleted_at

users                            -- store staff/owners (HRIS auth reused)
  id, name, email, password, ...
  -- pivot store_user(store_id, user_id, role) OR direct store_id for single-store installs

roles / permissions / role_user  -- reused from HRIS RBAC (store-scoped)

customers                        -- end users who order (may be guest-promoted)
  id (uuid, pk)
  name, phone, email (nullable for guest)
  is_guest (bool)
  password (nullable)            -- set if they register
  created_at, updated_at
```

> Guest flow: a `customer` row with `is_guest=true` is created at checkout keyed by phone;
> can later be claimed/registered. Orders always reference a `customer_id`.

### 4.2 Catalog & pricing

```
product_types                    -- the "kind" of thing printed (defines the flow)
  id, store_id
  name                           -- "Document printing", "Tarpaulin", "T-shirt"
  pricing_mode                   -- file_based | spec_based   <<< the §5/§3 split in validation
  fulfillment                    -- manual (v1) | auto (phase 2 agent)
  is_active

products                         -- a sellable variant under a product_type
  id, store_id, product_type_id
  name                           -- "Short bond B&W", "A4 Colored", "2x3 Tarp"
  base_price_cents               -- spec_based: flat/base; file_based: per-page default
  is_active

price_rules                      -- file_based modifiers (paper size, color, duplex, ...)
  id, store_id, product_id
  attribute                      -- "paper_size" | "color" | "duplex" | "copies"
  match_value                    -- "A4" | "color" | "bw" | ...
  modifier_type                  -- per_page | per_job | multiplier
  amount_cents | multiplier
  -- e.g. color page = +3.00/page; A4 = ×1.0; long bond = +1.00/page

product_options                  -- spec_based choices (phase 2 surfacing)
  id, store_id, product_id
  name (Size/Material/...), choices (json), price_delta_cents per choice
```

**Pricing engine** (`PricingService`):
- *file_based:* `price = Σ over pages( base_per_page × matched multipliers ) + per-job rules`,
  using the analyzed `page_count`, `paper_size`, and color flag (v1: whole-doc color flag).
- *spec_based:* `price = base_price + Σ selected option deltas` (no file analysis required).
- Always returns an itemized breakdown stored on the order item for transparency/disputes.

### 4.3 Orders, items, files

```
orders
  id (uuid, pk), store_id, customer_id
  code                           -- short human code, e.g. "PRT-7Q3K", drives the QR
  status                         -- state machine §3
  subtotal_cents, fee_cents, total_cents
  payment_status                 -- unpaid | authorized | paid | refunded
  pay_method                     -- gcash | card | maya | cash_on_pickup
  placed_at, accepted_at, ready_at, completed_at
  notes
  created_at, updated_at

order_items
  id, order_id, product_id
  pricing_mode                   -- snapshot (file_based|spec_based)
  quantity (copies)
  unit_breakdown (json)          -- itemized pricing snapshot
  line_total_cents
  spec_selections (json)         -- spec_based chosen options (phase 2)

order_files
  id, order_item_id, store_id
  original_name, mime, size_bytes
  storage_path                   -- private disk
  -- analysis results:
  page_count, paper_size, is_color (nullable until analyzed), color_pages (nullable)
  preview_path                   -- generated thumbnail/preview
  analysis_status                -- pending | done | failed
```

### 4.4 Payments & billing

```
payments                         -- customer → store, per order
  id, order_id, store_id
  provider (paymongo), provider_ref
  amount_cents, status (pending|paid|failed|refunded)
  raw_payload (json)             -- webhook audit
  paid_at, refunded_at

refunds
  id, payment_id, amount_cents, reason, status, created_at

subscriptions                    -- Printly → store (your revenue, §6 of validation)
  id, store_id, plan, price_cents, period_start, period_end, status
  -- flat monthly model; metering is per-store plan, NOT per-print

(optional later) platform_fees   -- if you take a % on processed payments
  id, payment_id, amount_cents
```

### 4.5 Real-time & audit

```
order_events                     -- append-only state-change log (drives broadcasts + audit)
  id, order_id, store_id
  from_status, to_status, actor_type (system|staff|customer), actor_id, meta (json), created_at

print_jobs                       -- phase 2 (agent). Modeled now so schema is stable.
  id, order_item_id, store_id, printer_id (nullable)
  status (queued|sent|printing|done|error), error, attempts, created_at, updated_at

printers                         -- phase 2 (agent). Store's mapped physical printers.
  id, store_id, name, capabilities (json: sizes/color), agent_id, is_active
```

### 4.6 Entity relationship (text)

```
store 1───* users (via store_user)        store 1───* product_types 1───* products 1───* price_rules
store 1───* customers                      store 1───* orders 1───* order_items 1───* order_files
order 1───1 payment 1───* refunds          order 1───* order_events
store 1───1 subscription                   store 1───* printers 1───* print_jobs (phase 2)
order_item 1───* print_jobs (phase 2)
```

---

## 5. Key flows

### 5.1 Customer order (v1, file_based)
1. `GET /s/{slug}` → storefront (products + prices).
2. Upload file → `POST /api/storefront/{slug}/files` → stored on private disk, **queued job**
   runs `FileAnalysisService` (page count/size, generate preview). Storefront polls/subscribes
   for `analysis_status=done`.
3. Customer picks product + options (copies, color) → `POST .../quote` → `PricingService`
   returns itemized total. (No order row yet — or a `draft` order.)
4. `POST .../orders` → creates `pending_payment` order + items + files; returns PayMongo
   checkout (or `cash_on_pickup` if store allows → `accepted`).
5. PayMongo webhook `POST /api/webhooks/paymongo` → mark `paid`, broadcast to store queue.
6. Customer gets **QR (order.code)** + live status page (Reverb channel `order.{id}`).

### 5.2 Store fulfillment (v1, manual)
1. Queue board subscribes to `store.{id}.orders`; new `paid` order pops in.
2. Staff **Accept** → `accepted`; opens/downloads file, prints from dashboard manually.
3. Staff marks **Printing → Ready**; customer sees live updates.
4. Customer arrives, shows QR → staff scans/enters code → **Completed**.
5. On problem: **Reject/Fail** → refund (or wallet credit), broadcast.

### 5.3 Phase-2 auto-print (design only)
On `accepted`, create `print_jobs` mapped to a `printer` by paper size; the in-store **agent**
(biometric-bridge pattern) polls `GET /api/agent/jobs`, downloads file, prints, posts
`PATCH /api/agent/jobs/{id}` status → flows back to the same state machine.

---

## 6. Payment capture strategy

Two viable models — recommend **authorize → capture on accept** to minimize refunds:

- **Authorize on checkout, capture on store Accept.** Customer's funds held; if store rejects,
  just void (no refund friction). Best UX, but PSP must support auth/capture for GCash.
- **Capture immediately, refund on reject.** Simpler, universally supported, but refunds are
  slower/visible to the customer. **Use this for v1** if PayMongo GCash auth/capture is limited;
  revisit auth/capture later.

Subscription billing (Printly → store) is **separate** and flat monthly — keep the two ledgers
apart so local/offline stores (no customer payments through you) still pay subscription.

---

## 7. API surface (v1)

```
Public storefront (guest-friendly, slug-scoped)
  GET   /api/s/{slug}                      store + catalog
  POST  /api/s/{slug}/files                upload, returns file id (analysis async)
  GET   /api/s/{slug}/files/{id}           analysis status + preview
  POST  /api/s/{slug}/quote                itemized price for a basket
  POST  /api/s/{slug}/orders               create order → payment intent / QR
  GET   /api/orders/{code}                 customer order status (QR target)

Webhooks
  POST  /api/webhooks/paymongo             payment status

Store dashboard (auth: Sanctum + store RBAC)
  GET   /api/stores/{id}/orders            queue (filter by status)
  PATCH /api/stores/{id}/orders/{oid}      transition state (accept/print/ready/complete/reject)
  CRUD  /api/stores/{id}/product-types|products|price-rules
  GET   /api/stores/{id}/analytics         basic sales/peak-hours (Pro)

Agent (phase 2)
  POST  /api/agent/auth   GET /api/agent/jobs   PATCH /api/agent/jobs/{id}
```

---

## 8. Real-time channels (Reverb)

| Channel | Audience | Events |
|---|---|---|
| `order.{id}` (private) | the customer | `OrderStatusChanged`, `FileAnalyzed` |
| `store.{id}.orders` (private) | store staff | `OrderPlaced`, `OrderStatusChanged` |
| `store.{id}.agent` (private) | print agent (phase 2) | `PrintJobQueued` |

All broadcasts are emitted from `order_events` writes, so the log and the live UI never diverge.

---

## 9. Build order (maps to MVP §7)

1. **Clone & strip** HRIS → Printly (auth, RBAC, component system, Reverb, file disk). Drop
   payroll/leave/attendance/recruitment. (See next planning doc: clone-and-strip steps.)
2. **Catalog + pricing** (file_based only): product_types/products/price_rules + PricingService.
3. **File pipeline:** upload → queued FileAnalysisService (pdfparser + Imagick) → preview.
4. **Order + state machine + order_events + Reverb** broadcasts.
5. **Storefront** (mobile-first Vue): upload → quote → order → QR + live status.
6. **Store queue board** (Vue): real-time list, transitions, manual print, QR complete.
7. **Payments:** PayMongo checkout + webhook + refund; cash_on_pickup fallback.
8. **Subscriptions** (flat plan gate) — minimal; can be manual at first.
9. *(Phase 2)* spec_based products, print agent, marketplace.

---

## 10. Decisions captured / still open

**Captured (recommended defaults):**
- Money in centavos; UUID PKs; `store_id` scoping everywhere.
- v1 color = whole-document flag (store verifies); per-page color detection is phase 2.
- Capture-immediately + refund for v1; revisit auth/capture.
- Flat subscription (Printly→store) separate from customer payments.

**Open (need your call):**
- PayMongo vs Xendit (assumed PayMongo above).
- Do you hold customer funds + pay out stores, or do stores connect their own PSP account and
  you only bill subscription? (Recommend: stores get paid directly → far less compliance.)
- Multi-store-per-user (chains) in v1, or strictly one store per login?
- File retention policy (auto-delete printed files after N days — privacy + storage cost).
```

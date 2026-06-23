# Printly — Idea Validation

> Working name. A SaaS for printing businesses (print shops near schools, neighborhood
> printers, and any custom-printing business) + a marketplace to discover nearby printers.
> Date: 2026-06-23

---

## 1. Verdict

**Worth building — but reframe it.** The core wedge (skip-the-line, prepaid, file-safe
printing for students) is real and the pain is real in PH campus areas. However, two parts of
the pitch as stated will quietly sink it if taken literally:

1. The **marketplace ("find a nearby printer") should NOT be the v1**. It's a classic
   chicken-and-egg cold-start. Lead with a single-store SaaS tool; bolt on discovery only
   once you have store density in one area (e.g. one university belt).
2. The **"₱1 per print" micro-fee is the wrong meter** (see §6). It's hard to trust, easy to
   bypass, and punishes big jobs. Switch to a flat monthly subscription per store (+ optional
   transaction %). This also makes local/offline deployment coherent.

Build it as: **"Shopify/Square for print shops"** — a store gets an online order page, a QR
queue, auto-pricing, and an optional auto-print bridge. Marketplace is phase 2.

---

## 2. Why this is attractive

- **Acute, repeated pain.** Lining up with a flash drive near schools is a daily ritual for
  millions of PH students. Queues, USB viruses, "do you have the file?", wrong settings,
  paying with exact change — all real friction.
- **The shop owner has pain too:** congestion at peak hours, students who change their mind,
  "I'll pay later," manual price math, no record of sales.
- **Underserved locally.** Global tools exist (PaperCut, ezeep, Printix, PrinterOn; campus
  kiosks like Wepa/Pharos in the US) but they target enterprise/university IT, not the
  ₱-economy neighborhood shop. No strong PH SaaS owns "neighborhood print shop." Gap = opening.
- **You can ship fast.** ~70% of the plumbing already exists in HRIS (auth, RBAC, multi-tenant,
  Reverb real-time, import/file services, the Vue component system). See §8.
- **You've done a bridge before.** The hardest technical piece (cloud → physical printer) is
  the same shape as your `biometric-bridge`. That's a real moat vs. a pure web competitor.

---

## 3. Value proposition (must hold for BOTH sides or it dies)

**Student/customer**
- Order from your phone, pay with GCash/Maya, walk in, show QR, grab prints. No USB, no line.
- See live status: queued → printing → done.
- Trust: see the exact price before paying; guaranteed it prints as previewed (this is the
  make-or-break — see §5 file fidelity).

**Store owner**
- Prepaid orders (no "pay later" leakage), auto-priced (no math), queue on a screen.
- Optional: auto-route to the right printer (short bond / long bond / photo) via a local agent.
- Sales records, peak-hour insight, off-hours orders (order at night, pick up at open).

If the student value is only "skip a 2-minute line," adoption is weak. The real hooks are
**prepaid + no-USB + order-ahead + price certainty**. Lead marketing with those.

---

## 4. Competitors / alternatives

| Alternative | Why they fall short for this niche |
|---|---|
| Status quo (USB + line + cash) | Free, zero learning curve — **this is your real competitor**, not other apps |
| PaperCut / Printix / ezeep | Enterprise/edu IT print governance; pricey, complex, not consumer-facing, not ₱-priced |
| PrinterOn / cloud print | Print-from-anywhere plumbing, no storefront/pricing/payments/queue for shops |
| Campus kiosks (Wepa, Pharos) | US campus contracts; capital-heavy hardware model |
| Messenger/email the file | What students do today; no pricing, no payment, no queue, manual |

**Takeaway:** competing on tech vs PaperCut is a trap. Compete on **fit + price + payments**
for the informal PH print economy. The status quo (free) is what you must beat on convenience.

---

## 5. The genuinely hard problems (validate these before building)

These are the things that decide whether the product is magic or a support nightmare.

1. **Cloud → physical printer (the auto-print bridge).** A browser cannot drive a USB/network
   printer. Google Cloud Print is dead (2020). You need a **local print agent** installed in
   the store that polls the cloud for jobs and dispatches to the correct mapped printer. This
   is the same architecture as `biometric-bridge`. Treat it as a first-class component.
   *Mitigation: v1 can be "manual print" only — the agent is a phase-2 upsell.*

2. **Auto-scan & auto-price a file.** Page count and page size from PDF = easy. **Color vs
   B&W per page = hard** (rasterize + ink-coverage via Ghostscript, or per-page heuristics).
   Office files (docx/pptx) must be converted to PDF first (LibreOffice headless) and
   **rendering fidelity will differ** from the student's Word. Wrong pricing or wrong layout =
   disputes. *Mitigation: support PDF-first; for Office, show "preview may differ, store will
   confirm" and let the store adjust before charging.*

3. **"Dynamic — any print type (tshirt, tarp)" conflicts with "auto-scan & auto-price."**
   Paper is file-derived and auto-priceable. T-shirts/tarps/mugs are **option-derived**
   (size, material, qty) and often need a manual quote. You need **two product models:**
   - *File-based products* (paper): auto page-count/color/size pricing, can auto-print.
   - *Spec-based products* (tshirt/tarp): customer picks options + uploads artwork → quote /
     fixed price, always manual fulfillment.
   Don't force one flow to do both.

4. **Trust & disputes / refunds.** "I paid, it printed wrong / didn't print." You need a clear
   refund path and a store-confirm step before money is captured (authorize → capture on
   accept, or wallet credit on failure). This is a policy problem as much as code.

5. **Payments in PH.** Need GCash/Maya/cards via a PSP (PayMongo or Xendit). Settlement,
   payout to stores, your fee deduction, refunds. Non-trivial but well-trodden.

6. **Behavior change.** Students must change a deeply ingrained habit, and stores must trust a
   prepaid flow. Onboarding friction is the silent killer. Need an in-store "order here" QR
   poster and a dead-simple guest flow.

---

## 6. Monetization — rework the meter

Stated: store setup fee + **₱1 per customer print, billed monthly**.

**Problems with per-print:**
- *Ambiguous unit:* per page? per order? A 200-page thesis = ₱200 fee on one order = absurd.
- *Hard to meter & trust:* the store will suspect your counter; you can't audit their walk-ins.
- *Easy to bypass:* "just bring your USB and I'll print it cheaper off-app."
- *Breaks offline/local deploy:* a shop running fully on-prem has no metered link to you.

**Recommended model:**
- **Flat monthly SaaS subscription per store**, tiered by features:
  - *Starter* — online order page + QR queue + manual pricing (cheap, land-grab).
  - *Pro* — auto-pricing + payments + analytics.
  - *Auto* — local print-agent / auto-routing + dedicated/offline deploy.
- **Setup/onboarding fee** for hardware-assisted (agent install, printer mapping) — keep.
- **Optional payment markup** (e.g. small % on top of PSP fee) when *you* process the payment —
  this scales with usage honestly and doesn't punish big jobs, because it's a tiny %, not ₱1.
- Marketplace (phase 2): **featured placement / lead fee**, not per-print.

This makes "deploy locally vs cloud" clean: local = license/subscription; cloud = subscription
(+ optional payment %).

**Quick sanity math (why flat wins):** a busy shop doing 500 prints/day ≈ 15k prints/mo. At
₱1/print that's ₱15k/mo — a small shop will balk and bypass. A ₱799–₱1,999/mo flat tier they
can reason about and won't try to game.

---

## 7. Recommended MVP (thinnest thing worth shipping)

Pick **one** university belt / one anchor store. Single-tenant per store at first.

**In scope (v1):**
- Store onboarding: products (file-based: bond short/long/A4, B&W/color), price rules.
- Customer flow: upload PDF → auto page count + size → price → pay (GCash) **or** guest-pay →
  get QR + order number.
- Store dashboard: real-time order queue (Reverb), states queued→printing→done, mark/print
  manually, print the customer's file from the dashboard.
- Customer live status page (same Reverb channel).
- Basic auth (login + guest), store admin RBAC.

**Explicitly NOT in v1 (phase 2+):**
- Auto-print bridge (manual print first — ship the agent once the queue UX is loved).
- Spec-based products (tshirt/tarp) — add the second product model after paper works.
- Color-per-page auto-detection (start: whole-doc color flag chosen by customer, store verifies).
- Marketplace / nearby discovery.
- Multi-PSP, payouts, wallet.

Ship v1 to one real shop, watch a week of real orders, then decide what to automate.

---

## 8. HRIS reuse map (what to copy vs drop)

You already proved this pattern with `D:\dev\real-estate` (cloned + stripped from HRIS).

**Copy mostly as-is:**
- Laravel + Vue 3 skeleton, build/deploy scripts, `frontend-v2` component system
  (AppPageHeader, AppDataTable, AppCard, AppSearchBar, AppPagination, AppButton, AppIcon,
  modals) and the list-page pattern.
- Auth + session + RBAC/permissions engine.
- **Reverb real-time** → perfect for the live order queue + status.
- **File/import services** (OpenSpout etc.) → adapt for upload + parsing pipeline.
- Multi-tenancy scaffolding (from `hris-mt`) → each store = a tenant; supports
  shared-cloud / dedicated-VM / local deploy directly.

**New, build fresh:**
- File ingestion + PDF/Office analysis pipeline (page count, size, color, LibreOffice convert).
- Product/pricing engine with the **two product models** (file-based vs spec-based).
- Payments integration (PayMongo/Xendit) + order/payment state machine + refunds.
- **Print agent** (model on `biometric-bridge`): polls jobs, maps to local printers, reports
  status back over the same real-time channel.
- Order/queue domain + customer-facing storefront (guest-friendly, mobile-first).

**Drop entirely:** payroll, leave, attendance, recruitment, BIR, org chart — all HRIS-specific.

---

## 9. Open questions to answer next

1. **Beachhead:** which one school belt / anchor store do you launch on? (Density > breadth.)
2. **Anchor store:** do you already have a print shop owner who'll co-design v1? (Critical.)
3. **Payments:** PayMongo vs Xendit? Do you (platform) hold funds and pay out, or does the
   store get paid directly and you bill subscription separately? (Simpler: latter.)
4. **Deploy default:** is v1 cloud-shared multi-tenant, or per-store install? (Recommend
   cloud-shared for speed; offer dedicated/local as a tier.)
5. **File scope at launch:** PDF-only, or Office too? (Recommend PDF-only first.)
6. **Auto-print:** confirm phase-2. v1 manual-print only?

---

## 10. One-line summary

Strong wedge, reuse-friendly, real gap — **but** win by shipping a single-store
"Square-for-print-shops" with a flat subscription and manual printing first; defer the
marketplace and the ₱1/print meter, and respect that "any print type" needs a second
(spec-based) product model alongside the auto-priced paper flow.

# Printly — Clone & Strip Runbook (HRIS → Printly)

> How to derive the Printly codebase from HRIS, same way `D:\dev\real-estate` was created.
> Goal: keep the **foundation** (auth, RBAC, audit, the Vue component system, Reverb, file
> storage, build/deploy), drop **all HRIS domain** (payroll/leave/attendance/recruitment/etc.),
> then scaffold Printly's domain per [`01-data-model-and-architecture.md`](01-data-model-and-architecture.md).
> Date: 2026-06-23
>
> **Reference baseline:** real-estate kept only these backend models — `User`, `Role`,
> `Permission`, `RolePermissionAudit`, `AuditLog` — and reduced `Services/` to its own domain.
> Printly follows the same cut.

---

## 0. Principle

Do **not** branch HRIS. Copy the two tracked trees only (`backend/` + `frontend-v2/`) into a
fresh repo, delete domain, keep core, then build. This matches the deploy rule that only
`backend/` + `frontend-v2/` are real (see memory: deploy sync exclusions).

**Do NOT copy:** `.claude/`, `planning/`, `references/`, `security/`, `mobile-app/`,
`se-mobile-app/`, `marketing/`, root `storage/`, `node_modules/`, `vendor/`, `.scratch/`,
`.mcp.json`, `snap.txt`, `tmp_*`, `*.network-response`.

---

## 1. Bootstrap the repo

```bash
# from D:\dev
mkdir printly/backend printly/frontend-v2

# copy backend, excluding heavy/uncommitted dirs
robocopy hris\backend printly\backend /E /XD vendor node_modules storage .scratch \
  /XF .env snap.txt
# recreate a clean storage skeleton (don't copy HRIS storage contents)
mkdir printly\backend\storage\app\private printly\backend\storage\framework\{cache,sessions,views} printly\backend\storage\logs

# copy frontend, excluding build artifacts
robocopy hris\frontend-v2 printly\frontend-v2 /E /XD node_modules dist .vite

cd printly
git init
# copy root .gitignore from hris, add Printly README
```

> On Windows use `robocopy` (PowerShell) — `/E` recurse, `/XD` exclude dirs, `/XF` exclude
> files. Verify `printly\backend\vendor` and both `node_modules` are absent before `git add`.

After copy: `composer install` (backend), `npm install` (frontend-v2), copy `.env.example` →
`.env`, `php artisan key:generate`.

---

## 2. Backend — what to KEEP

### Keep these `app/` subtrees mostly intact
- `app/Providers`, `app/Console`, `app/Exceptions`, `app/Casts`, `app/Traits`, `app/Helpers`
  (prune HRIS-specific helpers later), `app/Contracts`.
- `app/Http/Middleware`, `app/Http/Kernel`, form-request base classes, API response traits.
- **Auth stack:** `app/Services/Auth/*`, `app/Models/User.php`, Sanctum config.
- **RBAC + audit:** `Models/Role.php`, `Models/Permission.php`, `Models/RolePermissionAudit.php`,
  `Models/UserPermissionOverride.php`, `Models/UserPermissionOverrideAudit.php`,
  `Models/AuditLog.php`, `Services/PermissionChecker.php`, `Policies/` base wiring.
  *(Keep the override mechanism — see memory: direct permissions override role permissions.)*
- **Settings:** `Models/Setting.php` + its service (becomes store settings backbone).
- **Real-time:** `routes/channels.php` (strip HRIS channels), broadcasting config, Reverb.
- **Notifications backbone:** `app/Notifications` base + `NotificationPreference` +
  `BroadcastNotificationService` (prune HRIS notification classes).
- **Import scaffolding** (optional, useful for bulk price import later): `Services/Imports/*`,
  `Models/ImportSession.php`, `Models/ImportRow.php` (see memory: import service patterns).
- `config/*` (review each), `bootstrap/`, `public/index.php`, base `routes/web.php` shell.

### Keep these support services (generic, reusable)
- `HtmlSanitizer.php`, any file/storage helper, queue config. `BusinessHoursCalculator` only if
  you want store open-hours logic later (optional).

---

## 3. Backend — what to DELETE

Delete every HRIS-domain model, service, controller, migration, policy, observer, event,
listener, mail, job, and route group. Concretely:

### Models to DELETE (everything except the keep-list in §2)
All of: `Announcement*`, `Applicant*`, `Attendance*`, `BankAccount`, `Benefit*`,
`Biometric*`, `Calendar*`, `Career*`, `Chat*`, `Clearance*`, `Client*`, `CompanyManual`,
`Compensation*`, `ContactMessage`, `Department*`, `DesktopActivity*`, `Document*`,
`Dtr*`, `Employee*`, `Employment*`, `Exam*`, `ExitInterview`, `FinalPay*`, `Government*`,
`Holiday`, `Interview*`, `IssueGroup*`, `It*` (all IT-asset/ticket models), `Job*`,
`Leave*`, `Loan*`, `Manual*`, `MeetingType`, `Notice*`, `Offboarding*`, `Onboarding*`,
`OneOnOne*`, `OrgChart*`, `Payroll*`, `Payslip*`, `Performance*`, `Policy*`, `Position*`,
`Recruitment*`, `Resume*`, `Salary*`, `Schedule*`, `Signature*`, `Sil*`, `Skill*`, `Tax*`,
`Team*`, `ThirteenthMonth*`, `TimeLog*`, `Tour*`, `Training*`, `UserDeviceToken`(keep if FCM
wanted later — else drop), `UserReport`, `UserTourCompletion`.

> Keep ONLY: `User`, `Role`, `Permission`, `RolePermissionAudit`, `UserPermissionOverride`,
> `UserPermissionOverrideAudit`, `AuditLog`, `Setting`, (optional) `ImportSession`, `ImportRow`.

### Services to DELETE
Drop the entire HRIS domain set — `AI/`, `Assessment/`, `DesktopActivity/`, `Document/`,
`Exports/`, `Leave/`, `Parsers/`, `Payroll/`, `Reports/`, `Signature/`, and every top-level
`*Service.php` tied to the deleted models (Employee/Applicant/Chat/It*/Payroll/Leave/Career/
Manual/Performance/Onboarding/etc.). **Keep** `Auth/`, `PermissionChecker`, `HtmlSanitizer`,
import scaffolding (if kept), `BroadcastNotificationService`.

### Controllers / routes / migrations
- `app/Http/Controllers/Api/*` — delete all HRIS controllers; keep auth + a base controller.
- `routes/web.php` / api route file — strip to: auth, user, role/permission, settings, health.
- `database/migrations/` — delete all HRIS-domain migrations. **Keep** the framework ones
  (users, password resets, sessions, cache, jobs, personal_access_tokens) + roles/permissions
  + permission-override + audit_logs + settings migrations.
- `database/seeders/` — keep `RolePermissionSeeder` shell (rewrite permissions for Printly),
  `DatabaseSeeder`; delete all HRIS seeders (Demo/Showcase/etc.).
- `tests/` — delete HRIS feature tests; keep auth/permission test harness as a template.

> ⚠️ Permissions: rewrite the permission catalog for Printly's domain (stores, products,
> orders, queue). Remember direct-permission overrides (memory) when seeding.

---

## 4. Frontend — what to KEEP

The component system is the crown jewel — keep it whole.

- **`src/components/ui/*`** — keep ALL (AppPageHeader, AppDataTable, AppCard, AppSearchBar,
  AppPagination, AppButton, AppModal, AppInput, AppSelect, AppCurrencyInput, AppStatusDisplay,
  AppRowActions, AppEmptyState, AppConfirmDialog, charts, table/sheet/sidebar primitives, etc.).
  These encode your UI standards (see memory: list-page structure, modal footer buttons,
  AppButton icon pattern, pagination).
- **`src/components/common/*`** — AppIcon, AppSpinner, AppToast. Keep.
- **`src/components/layout/*`**, `src/layouts/*` — keep, retheme.
- **Composables to keep:** `useApiList`, `useApiResource`, `useForm`, `usePermission`,
  `useReverbChannel`, `useRealtimeList`, `usePresence`, `useNotifications`, `useDarkMode`,
  `useNetworkStatus`, `useBranding`, `useCommandPalette`, `useTabUrl`, `useCamera`(QR scan!),
  `usePinVerification`/`useIdleLock` (optional store-terminal lock), `useGeolocation`/
  `useGoogleMaps` (keep for phase-2 marketplace map).
- `src/lib`, `src/utils`, `src/services` (API client, axios wrapper — keep; prune endpoints),
  `src/directives`, `src/router` (gut routes), `src/stores/auth.ts`, `src/types` (prune),
  `src/sentry`, `src/workers`, `App.vue`, `main.ts`.
- **PWA bits** (`components/pwa`, service worker) — keep; storefront benefits from installable
  mobile-web. (Aligns with memory: mobile web build flags / Flutter-web learnings.)

---

## 5. Frontend — what to DELETE

- **`src/pages/*`** — delete ALL HRIS page folders (admin/announcements/assessment/attendance/
  benefits/biometrics/calendar/careers-cms/chat/clients/departments/desktop-activity/documents/
  employee*/holidays/it-support/leave*/manual/notice-*/offboarding/org-chart/payroll*/
  performance/positions/recruitment/reports/roles*/salary*/schedules/sil/teams/training/users
  except keep `auth/`, `settings/` shell, `DashboardPage.vue`, `NotFoundPage.vue`,
  `ForbiddenPage.vue`). Rebuild pages for Printly.
- **`src/components/{announcements,attendance,calendar,careers-cms,chat,clients,dashboard,
  desktop-activity,employee,employees,guide,hr-chat,it-support,leave,org-chart,payroll,
  recruitment,reports,signatures,skeletons,system,tour}`** — delete (domain-specific).
  Keep `shared/`, `notifications/`, `pwa/`, `ui/`, `common/`, `layout/`.
- **Composables to delete:** `useAttendanceClock`, `useCalendar`, `useChat*`, `useCallSession`,
  `useHr*`, `useJobMatchScore`, `useOrgOptions`, `usePipelineBoard`, `useTeamPicker`,
  `useOfflineSync`(unless needed), `useSidebarCounts`(rewrite). Delete `stores/chat.ts`,
  `stores/tour.ts`.
- `AppSidebar.vue`/`AppBottomNav.vue`/`CommandPalette.vue` — keep files, **rewrite nav items**
  for Printly (Storefront / Orders / Queue / Catalog / Settings).
- `__tests__` — delete HRIS specs, keep config + a sample.

---

## 6. Rename / rebrand pass

- Global find/replace `hris` → `printly` in: `composer.json`, `package.json`, `.env.example`
  (`APP_NAME`, DB name `printly`, `REVERB_APP_*`, queue names), `config/app.php`, vite config,
  app title/manifest, README.
- New DB: `printly` (dev) — `php artisan migrate:fresh` after §7 migrations exist.
- Reverb: fresh `REVERB_APP_KEY/SECRET` (don't reuse HRIS/demo keys — see memory on key match).
- Branding: swap logo/colors via `useBranding`; retheme `tailwind.config`.

---

## 7. Scaffold Printly domain (post-strip)

Build per the data model doc. Suggested generation order (use your `scaffold-module` skill):

1. `stores` + tenancy scoping + `store_user` pivot + Printly permission catalog.
2. `customers` (+ guest support) and storefront auth/guest tokens.
3. Catalog: `product_types`, `products`, `price_rules`, `product_options` + `PricingService`.
4. Files: `order_files` + upload endpoint + queued `FileAnalysisService` (pdfparser/Imagick) +
   LibreOffice convert worker.
5. Orders: `orders`, `order_items`, `order_events` + state machine + Reverb broadcasts.
6. Payments: `payments`, `refunds` + PayMongo service + webhook; `subscriptions` (flat plan).
7. Frontend: storefront (`/s/{slug}`) + store queue board + catalog admin + settings.
8. *(Phase 2)* `printers`, `print_jobs`, agent endpoints; spec-based product UI; marketplace.

---

## 8. Verification checklist

- [ ] `composer install` + `npm install` clean; no references to deleted models (grep
      `Employee`, `Payroll`, `Leave`, `Applicant` across `app/` and `src/` → zero).
- [ ] `php artisan migrate:fresh --seed` succeeds with only core + Printly tables.
- [ ] Login works; RBAC + permission overrides enforced; audit log writes.
- [ ] Reverb connects; a test broadcast reaches the client.
- [ ] `npm run build` (web) succeeds — remember `--dart-define` is mobile/Flutter only; the
      Vue web build just needs correct `VITE_*` API/Reverb hosts.
- [ ] Vue app boots to a Printly dashboard with retheme, no dead HRIS routes.

---

## 9. Gotchas (from HRIS memory)

- **Deploy tracks only `backend/` + `frontend-v2/`** — keep that contract; don't reintroduce
  extra tracked dirs.
- **Permission overrides:** new Printly role perms must also be inserted into user_permissions
  for affected users (direct perms bypass role perms).
- **Don't reuse Reverb keys** across HRIS/demo/Printly — mismatched keys = silently dead
  real-time.
- **Single-tenant simplification:** if you launch cloud-shared multi-tenant, decide the scoping
  story up front (`store_id` everywhere) — don't half-apply it like the develop/MT split in HRIS.
- This is a **brand-new repo**: commit to `main`/`develop` here freely; the "always commit to
  develop, never deploy/production" rule is HRIS-specific, not Printly.

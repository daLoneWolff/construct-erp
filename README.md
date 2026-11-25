# Himalaya Construction ERP — local demo + multi-company target

A Nepal-focused construction ERP (Laravel 13 + Livewire 4.1 + Flux UI 2 +
Tailwind CSS 4, PostgreSQL 16). Its primary flow is
**Project → milestone → contractor work package → payment**: agreed amount, paid
amount, and unpaid balance. Employees/attendance, vendors/POs, running bills,
and reports remain as support records. AD dates only; NPR formatting; VAT/TDS
and SSF calculations apply to those support records, never to contractor
balances.

> **Status:** the seeded runtime is the **single-company local demo**. T1 added
> the tenant schema, T2 added registration, Google sign-in, verification,
> password reset, and empty-company onboarding, T3 enforced tenant scoping with
> server-side company resolution, model/route scopes, and company-aware audit
> rows, T4 made calculations, documents, exports, and numbering company-aware,
> and T5 confined demo reset to the fictional demo company. T6 completed the
> module empty states and full acceptance pass; T7 is documentation-only. See
> `MILESTONES.md` → T0–T7.

**Read [`AGENTS.md`](AGENTS.md) first** — it is the entry point and links the full
spec in reading order. This README covers running, reseeding, and verifying the
local demo database, plus the target setup requirements.

## Requirements

- PHP **8.5.12+** (never an older patch — see `ARCHITECTURE.md` → "Version baseline")
- Composer 2
- Node 20+ and npm
- PostgreSQL 16

## Current demo setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

# create the database once
createdb construction_erp

php artisan migrate:fresh --seed   # DatabaseSeeder calls DemoResetSeeder
npm run dev                        # separate terminal, or: npm run build
php artisan serve                  # http://127.0.0.1:8000
```

## Demo login (local fictional data only)

The seeded demo still has one account for the fictional Himalaya Builders demo
company. T2 adds signup, Google sign-in, password reset, and email verification
for new accounts:

| Email | Password |
|---|---|
| `admin@himalaya.test` | `password` |

Every screen is available to this demo admin account. These credentials are
**not** how a real customer gets access in the target product.

## Target account and company setup (T2 + T3 + T4 + T5 + T6 delivered; T7 documentation only)

The T2 flow is:

1. Visitor opens `/register` and signs up with name, email, and password, or
   chooses **Continue with Google**.
2. Email/password users verify their email; Google emails are treated as
   verified.
3. Password reset is available at `/forgot-password` for email/password accounts.
4. The user creates a company (name, PAN, VAT, address, district, fiscal year).
5. The creator becomes the company Owner/Admin and lands on an empty dashboard
   with placeholder visuals and CTAs to create the first project, contractor,
   and payment.
6. No demo projects, contractors, payments, or totals are copied into the new
   company.

T3 enforces tenant isolation for Eloquent models, route bindings, application
access, and audit rows. T4 scopes dashboard totals, balances, reports, CSV
exports, and payment/document numbering to the current company, including raw
`DB::table` reconciliation queries. T5 makes `DemoResetSeeder` delete and reseed
only the fictional demo company; registered users, companies, memberships,
records, and audit history are preserved. Full module empty states and the full
cross-company acceptance script were completed in T6.

Target environment additions are documented in `ARCHITECTURE.md` and
`PRODUCTION_RUNBOOK.md`: mail driver, `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`,
`GOOGLE_REDIRECT_URI`, queue/session config, HTTPS, backups, and monitoring.
`DEPLOY_LATER.md` remains the historical research bookmark.

## Reset the demo data

Either click **Reset Demo** in the topbar, or run:

```bash
php artisan migrate:fresh --seed
```

Reseeding is deterministic for a given day — dates are pinned relative to
`today()`, so a reset always reproduces the documented dataset in `SEED_DATA.md`.

`DemoResetSeeder` now deletes only the fictional demo company and its admin, then
reseeds that dataset. Registered companies, users, memberships, records, and
audit history are left intact. The topbar action is still hidden and the route
forbidden unless `DEMO_RESET_ENABLED=true` and the signed-in company is the demo
company. Never run a demo reset against production.

## Verify the current local database

The app uses the PostgreSQL connection in `.env` (database `construction_erp`).
The test suite separately uses SQLite `:memory:`, which is why tests do not
touch the demo database. To confirm the demo database is ready:

```bash
php artisan migrate:status
php artisan tinker --execute="echo 'Seeded admin users: '.\App\Models\User::count().PHP_EOL;"
php artisan tinker --execute="echo 'Contractors: '.\App\Models\Contractor::count().PHP_EOL; echo 'Jobs: '.\App\Models\ProjectJob::count().PHP_EOL; echo 'Payments: '.\App\Models\ContractorPayment::count().PHP_EOL;"
php artisan tinker --execute="echo 'Agreed: '.\App\Support\Npr::format(app(\App\Services\CalculationService::class)->overallPaymentSummary()['agreed_paisa']).PHP_EOL; echo 'Paid: '.\App\Support\Npr::format(app(\App\Services\CalculationService::class)->overallPaymentSummary()['paid_paisa']).PHP_EOL; echo 'Outstanding: '.\App\Support\Npr::format(app(\App\Services\CalculationService::class)->overallPaymentSummary()['balance_paisa']).PHP_EOL; echo 'Next: '.app(\App\Services\CalculationService::class)->nextContractorPaymentNo().PHP_EOL;"
```

Expected after seeding: every migration `Ran`, `Seeded admin users: 1`,
`Contractors: 6`, `Jobs: 8`, `Payments: 11`, agreed
`रू 1,52,00,000.00`, paid `रू 1,10,50,000.00`, outstanding
`रू 41,50,000.00`, and next payment number `CTP-<current AD year>-0012`.

## Documentation

| File | Contents |
|---|---|
| `AGENTS.md` | Entry point, current/target product model, rules, folder layout |
| `PRODUCT.md` | Confirmed current demo truth and target multi-company product truth |
| `REDESIGN_PLAN.md` | Approved demo redesign record and target onboarding flow |
| `MILESTONES.md` | Build status, environment notes, and T0–T7 gates |
| `PRD.md` | Current and target scope |
| `ARCHITECTURE.md` | Stack, auth, mail, OAuth, tenancy, audit, local setup |
| `DATA_MODEL.md` | Every table, column, relationship, and tenant key |
| `CALCULATIONS.md` | Every formula, scoped to the current company |
| `ROUTES_AND_UI.md` | Auth/onboarding routes, app routes, print, CSV, empty states |
| `NEPAL_LOCALIZATION.md` | NPR, PAN, VAT, TDS wording; per-company localization fields |
| `SEED_DATA.md` | Local fictional demo dataset only |
| `BUILD_PLAN.md` | Historical hour-by-hour plan (do not update) |
| `ACCEPTANCE.md` | Current demo script plus target onboarding/isolation checks |
| `ROADMAP.md` | Later exclusions |
| `PRODUCTION_RUNBOOK.md` | Active deployment execution guide: Railway + Dockerfile / PHP 8.5 cutover, verification, backups, monitoring, rollback |
| `DEPLOY_LATER.md` | Historical deployment research; the runbook is the active guide |

## Deployment

Execution remains deferred and separately approved. `PRODUCTION_RUNBOOK.md` is
the active Railway + Dockerfile / PHP 8.5 cutover guide; `DEPLOY_LATER.md` is
retained only as historical version research. No Dockerfile, hosting config,
`.env`, secret value, or live deployment is included in the local V1/T phases.
The pre-existing `.github/workflows/tests.yml` is starter-kit test-only CI; no
deployment CI was added.

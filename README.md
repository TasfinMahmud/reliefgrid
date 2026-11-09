# ReliefGrid — Humanitarian Aid Distribution and Settlement Resilience Platform

CSE470 Software Engineering project. ReliefGrid helps humanitarian teams working in displacement
settlements register households, plan assistance entitlements, manage warehouse stock, run shelter
repairs and coordinate preparedness and incident response at the level of settlement **blocks**.

The application is built with **Laravel 12 (MVC)**, **Blade + Bootstrap 5** views (bundled with Vite),
**PostgreSQL**, and **Laravel Breeze** for session-based authentication.

## Sprint 1 scope

Sprint 1 delivers the supporting infrastructure (authentication, roles, reference data) and five of the
twenty approved features:

| # | Feature | Status |
|---|---------|--------|
| 1 | Household registration and token issuance | ✅ Implemented |
| 2 | Assistance cycle and entitlement planning | ✅ Implemented |
| 5 | Warehouse and stock management | ✅ Implemented |
| 12 | Shelter repair workflow | ✅ Implemented |
| 14 | Preparedness and incident response | ✅ Implemented |

Feature descriptions, acceptance criteria and test scenarios are in [`docs/FEATURES.md`](docs/FEATURES.md).
The MVC mapping of every component is in [`docs/MVC_MAPPING.md`](docs/MVC_MAPPING.md).
Sprint goals and attainment are tracked in [`docs/SPRINTS.md`](docs/SPRINTS.md).

> Login, registration, logout, password reset and profile management are supporting capabilities
> (Laravel Breeze) and are **not** counted as project features, in line with the course guidelines.

## Technology stack

| Layer | Technology |
|-------|------------|
| Language | PHP 8.2+ (developed and tested on 8.3 and 8.5) |
| Framework | Laravel 12 — Model (Eloquent + service classes), View (Blade), Controller (HTTP controllers + Form Requests + Policies) |
| Frontend | Blade templates, Bootstrap 5.3, Alpine.js, Vite; `qrcode` (client-side QR rendering) |
| Database | PostgreSQL 16/17 (SQLite supported for quick local demos and used in-memory for the automated tests) |
| Auth | Laravel Breeze (bcrypt password hashing, session authentication, rate-limited login) |
| Testing | PHPUnit 11 feature tests (52 tests so far; each feature adds its own) |

## Running the project on Windows

### 1. Install the tools (once)

1. **Laravel Herd** — <https://herd.laravel.com/windows> (bundles PHP 8.4/8.5, Composer and nginx).
2. **PostgreSQL** — <https://www.postgresql.org/download/windows/>. Remember the password you set for the
   `postgres` user. Open **pgAdmin** and create a database named `reliefgrid`
   (right-click *Databases → Create → Database*).
3. **Node.js LTS** — <https://nodejs.org/> (only needed if you change CSS/JS; built assets are committed-free but
   produced with `npm run build`).
4. **Git for Windows** — <https://git-scm.com/download/win>.

Open a *new* PowerShell window after installing so the tools are on your PATH, and check:

```powershell
php -v
composer -V
node -v
```

### 2. Get the code and install dependencies

```powershell
git clone https://github.com/TasfinMahmud/reliefgrid.git
cd reliefgrid
composer install
npm install
npm run build
```

### 3. Configure the environment

```powershell
copy .env.example .env
php artisan key:generate
```

Open `.env` and set the database section to your PostgreSQL credentials:

```ini
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=reliefgrid
DB_USERNAME=postgres
DB_PASSWORD=your-postgres-password
```

### 4. Create the schema and demo data

```powershell
php artisan migrate --seed
php artisan storage:link
```

`migrate --seed` creates all tables and loads a demo dataset (6 blocks, 24 households with tokens, an active
and a draft assistance cycle; each feature adds its own demo data). To reset everything later: `php artisan migrate:fresh --seed`.

### 5. Run it

```powershell
php artisan serve
```

Open <http://localhost:8000> and log in with one of the demo accounts (password is `password` for all):

| Email | Role | What they can do |
|-------|------|------------------|
| `admin@reliefgrid.test` | Administrator | Everything, including user and role management |
| `coordinator@reliefgrid.test` | Coordinator | Activate cycles, approve transfers and repairs, issue alerts, verify incident closure |
| `field@reliefgrid.test` | Field Officer | Register households, issue/reissue tokens, submit repair requests, report incidents, record evacuation occupancy |
| `warehouse@reliefgrid.test` | Warehouse Officer | Receive and issue stock, request transfers, issue repair materials |
| `assessor@reliefgrid.test` | Shelter Assessor | Assess repair requests, upload geo-tagged verification photos, complete repairs |

Self-registration is open (`/register`) and always creates a Field Officer; an administrator promotes
accounts from *Setup → Users & roles*.

### Running the automated tests

```powershell
php artisan test
```

The tests use an in-memory SQLite database (configured in `phpunit.xml`), so they never touch your
PostgreSQL data.

## How MVC is implemented

* **Models** (`app/Models`) are Eloquent models that own relationships, casts, scopes and small data
  helpers (e.g. `StockBatch::balance()`, `RepairStatus::canTransitionTo()`). Business rules that span several
  models live in **service classes** (`app/Services`): `HouseholdService`, `EntitlementPlanner` (one per feature). Enums in `app/Enums` define statuses, roles and
  the allowed workflow transitions.
* **Views** (`resources/views`) are Blade templates with Bootstrap 5 markup. They only present data and
  collect input; they contain no business logic beyond `@can` checks for showing/hiding actions.
* **Controllers** (`app/Http/Controllers`) coordinate requests: they authorise through **Policies**
  (`app/Policies`), validate through **Form Requests** (`app/Http/Requests`), call a service or model, and
  return a view or redirect. Route definitions are in `routes/web.php` (application) and `routes/auth.php`
  (Breeze authentication).

See [`docs/MVC_MAPPING.md`](docs/MVC_MAPPING.md) for a table of every model, view and controller per feature.

## Project structure

Each feature is self-contained and plugs into the application through its own files (see
[`CONTRIBUTING.md`](CONTRIBUTING.md)): a routes file, a navigation partial, a dashboard widget and a seeder are
discovered automatically, so team members can develop features in parallel without touching shared files.

```
app/
  Dashboard/        Widget interface + registry; Widgets/<Feature>Widget.php per feature
  Enums/            Role, statuses, hazard types, workflow transition tables
  Http/Controllers/ One controller per resource/workflow (+ Admin/UserController, Auth/*)
  Http/Middleware/  EnsureUserHasRole (route-level roles), EnsureUserIsActive
  Http/Requests/    Form Request validation classes
  Models/           Eloquent models
  Policies/         Authorisation rules per model/workflow step
  Services/         Domain logic for each feature
database/
  migrations/       Schema (PostgreSQL/SQLite compatible)
  seeders/          DatabaseSeeder (core data) + Features/<Feature>Seeder.php per feature
  factories/        Model factories used by tests and seeders
resources/views/
  layouts/nav/      One navigation partial per feature
  dashboard/widgets/ One dashboard panel per feature
  <feature>/        Blade views grouped by feature
routes/
  web.php           Core routes + loader for routes/features/*.php
  features/         One routes file per feature
  auth.php          Breeze authentication routes
tests/Feature/      Feature tests per workflow (+ Breeze auth tests)
docs/               Feature list (+ docs/features/*.md), MVC mapping, sprint records
```

## Significant third-party packages

| Package | Purpose |
|---------|---------|
| `laravel/breeze` (dev) | Authentication scaffolding: login, registration, password reset, profile |
| `bootstrap` 5.3 + `@popperjs/core` | UI framework for the Blade views |
| `alpinejs` | Lightweight interactivity (bundled by Breeze) |
| `qrcode` (npm) | Renders the opaque token UID as a QR code on the printable token card (client side) |
| `laravel-vite-plugin` / `vite` | Asset bundling |
| `phpunit/phpunit`, `fakerphp/faker` (dev) | Automated tests and factories |

No package implements an assessed feature on the team's behalf: entitlement computation, the append-only
stock ledger, workflow state machines and geo-tag validation are implemented in the application code.

## Notes and known limitations (Sprint 1)

* Features 5, 12 and 14 are being integrated by their owners (see the table above).
* The PWA/offline distribution mode, ML services (duplicate detection, complaint triage, damage grading) and
  the remaining features are scheduled for later sprints.
* `composer.json` pins `config.platform.php` to 8.3.0 so dependency resolution stays compatible with PHP 8.3
  environments; the application itself runs on PHP 8.2–8.5.
* Email is logged to `storage/logs/laravel.log` (`MAIL_MAILER=log`); password-reset links appear there in
  development.

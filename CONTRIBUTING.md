# Contributing to ReliefGrid

Every feature is developed by one team member and plugs into the application through its **own files**, so
two people can work in parallel without editing the same shared file. Follow this checklist when you add a
feature.

## Where a feature lives

| Concern | Location | Example (Feature 1) |
|---------|----------|---------------------|
| Routes | `routes/features/<feature>.php` — loaded automatically inside the authenticated group of `routes/web.php` | `routes/features/households.php` |
| Navigation entry | `resources/views/layouts/nav/<feature>.blade.php` — one `<li>` (or dropdown) included from `layouts/navigation.blade.php` | `layouts/nav/households.blade.php` |
| Dashboard | `app/Dashboard/Widgets/<Feature>Widget.php` (cards, quick actions, optional panel) + `resources/views/dashboard/widgets/<feature>.blade.php` — discovered by `App\Dashboard\WidgetRegistry` | `HouseholdWidget` |
| Demo data | `database/seeders/Features/Feature<NN><Name>Seeder.php` — run by `DatabaseSeeder` in file order; must be idempotent (return early if its data exists) | `Feature01HouseholdSeeder` |
| Models / enums | `app/Models`, `app/Enums` | `Household`, `HouseholdStatus` |
| Business rules | `app/Services/<Feature>Service.php` | `HouseholdService` |
| HTTP | `app/Http/Controllers`, `app/Http/Requests/<Feature>/`, `app/Policies` | `HouseholdController`, `HouseholdPolicy` |
| Views | `resources/views/<feature>/` | `resources/views/households/` |
| Migrations | `database/migrations/2026_11_07_000<NN>x_*.php` | `..._000010_create_households_table.php` |
| Tests | `tests/Feature/<Feature>Test.php` | `HouseholdRegistrationTest` |
| Documentation | `docs/features/<NN>-<feature>.md`, linked from `docs/FEATURES.md` | — |

Shared files you **may** touch, but only in your own region: a relation method on an existing model
(e.g. `Block::incidents()`), an extra column on a shared list page, or an `@includeIf` already present in a layout.
If you think a feature needs a change to a core file (`routes/web.php`, `DashboardController`,
`DatabaseSeeder`, layouts), discuss it first so it is done once.

## Dependencies between features

Features may *use* each other (Feature 12 issues repair materials through the Feature 5 stock ledger) but must
not *require* each other to install. Resolve cross-feature services lazily (`app(StockService::class)` inside
the method that needs it), guard optional links with `class_exists(...)` / `Schema::hasTable(...)`, and skip
tests that need the other feature when it is absent (`$this->markTestSkipped(...)`).

## Git workflow

1. `git clone https://github.com/TasfinMahmud/reliefgrid.git` and make sure `git config user.name` /
   `user.email` match your GitHub account.
2. Work on `main` (small team) or on a branch `feature/<nn>-<name>` and open a pull request.
3. Commit in meaningful steps with messages that say *what changed*: "Implement Feature 5: warehouse and stock
   management", "Add stock ledger tests and demo data" — never "update" or "final".
4. Run `php artisan test` before pushing. Push at least at the end of every sprint, including unfinished work
   (mark it clearly in the commit message).

## Definition of done for a feature

* Models, migrations, service, controllers, policies, views, routes file, nav partial, widget, seeder.
* Feature tests covering the normal path, invalid input and boundary cases.
* `docs/features/<NN>-<feature>.md` with purpose, workflow, acceptance criteria and test scenarios.
* The feature row in the README and the MVC mapping table updated.

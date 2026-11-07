# MVC mapping

Laravel implements MVC as follows in ReliefGrid:

* **Model** — Eloquent models in `app/Models` (data, relationships, casts, scopes, small data helpers) plus
  domain **services** in `app/Services` that hold multi-model business rules, and **enums** in `app/Enums`
  that define statuses and legal transitions. Models never render output and never read the HTTP request.
* **View** — Blade templates in `resources/views`. They display data passed by controllers and collect input.
  The only logic they contain is presentation logic (`@can` to hide actions, formatting).
* **Controller** — classes in `app/Http/Controllers`. Each action: authorises (Policy), validates
  (Form Request or inline rules), delegates to a service/model, and returns a view or redirect with a flash
  message. Middleware (`auth`, `active`, `role:`) runs before controllers.

## Shared infrastructure

| Concern | Model | View | Controller / HTTP |
|---------|-------|------|-------------------|
| Authentication | `User` | `auth/*`, `profile/*`, `layouts/guest` | `Auth/*Controller`, `ProfileController`, `LoginRequest`, `ProfileUpdateRequest` |
| Roles & access | `Enums\Role`, `User::hasRole()` | `layouts/navigation` (role-aware menu) | `Middleware\EnsureUserHasRole`, `Middleware\EnsureUserIsActive`, all `Policies\*` |
| User admin | `User` | `admin/users/*` | `Admin\UserController`, `UserPolicy` |
| Blocks | `Block` | `blocks/*` | `BlockController`, `BlockPolicy` |
| Items | `Item` | `items/*` | `ItemController`, `ItemPolicy` |
| Dashboard | (aggregates) | `dashboard` | `DashboardController` |
| Layout & components | — | `layouts/app`, `components/{flash,badge,input-error,confirm-form,modal-form}` | `View\Components\AppLayout`, `GuestLayout` |

## Feature 1 — Household registration and token issuance

| Model | View | Controller / HTTP |
|-------|------|-------------------|
| `Household`, `Token` (table `household_tokens`), `HouseholdEvent`; `Enums\HouseholdStatus`, `Enums\TokenStatus`; **`Services\HouseholdService`** (register, update, deactivate, reactivate, issue/reissue/revoke token, event log) | `households/{index,create,edit,show,_form}`, `tokens/card` | `HouseholdController`, `TokenController`; `Household\StoreHouseholdRequest`, `Household\UpdateHouseholdRequest`; `HouseholdPolicy` |

## Feature 2 — Assistance cycle and entitlement planning

| Model | View | Controller / HTTP |
|-------|------|-------------------|
| `AssistanceCycle`, `EntitlementRule` (formula), `Entitlement`; `Enums\CycleStatus`; **`Services\EntitlementPlanner`** (compute, preview, overlap check, activate/lock, close) | `cycles/{index,form,show,_rule_fields,preview}` | `AssistanceCycleController`, `EntitlementRuleController`; `Assistance\CycleRequest`, `Assistance\EntitlementRuleRequest`; `AssistanceCyclePolicy` |

## Request flow example (Feature 2, "Activate cycle")

1. `POST /cycles/{cycle}/activate` → `routes/web.php` → middleware `auth`, `active`.
2. `AssistanceCycleController::activate()` calls `$this->authorize('activate', $cycle)` →
   `AssistanceCyclePolicy::activate()` checks the role (coordinator/admin) and that the cycle is a draft.
3. The controller validates the confirmation checkbox and calls `EntitlementPlanner::activate()`.
4. The service checks that rules exist and produce entitlements, inserts one `Entitlement` row per household
   and item, and marks the `AssistanceCycle` active (locking the rules) inside a database transaction.
5. The controller redirects to `cycles/show` with a flash message; the Blade view now renders the locked
   rules and the generated totals.

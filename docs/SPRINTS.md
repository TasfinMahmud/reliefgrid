# Sprint records

One-week sprints. Each sprint starts (Saturday) with goals and ends (Friday) with an attainment report.

## Sprint 1 — 7 Nov 2026 → 13 Nov 2026

### Goals (set at sprint start)

1. Project skeleton: Laravel 12, Breeze authentication (login/register/logout/password reset/profile),
   Bootstrap 5 UI, PostgreSQL schema, role model (Admin, Coordinator, Field Officer, Warehouse Officer,
   Shelter Assessor) with Policies.
2. Feature 1 — Household registration and token issuance: register/update/deactivate, consent capture,
   opaque QR token card, lost-token reissue with history.
3. Feature 2 — Assistance cycle and entitlement planning: cycles, size-band rules, preview, approval that
   locks rules and materialises entitlements.
4. Feature 5 — Warehouse and stock management: batch receipts with expiry, issues, approved transfers,
   append-only ledger, reconciliation and expiry reports.
5. Feature 12 — Shelter repair workflow: assessment → approval → material issuance (ledger-linked) →
   completion blocked until a geo-tagged verification photo exists.
6. Feature 14 — Preparedness and incident response: block-level hazard alerts, evacuation site
   capacity/occupancy, incident lifecycle to verified closure.
7. Automated feature tests for every workflow (normal, invalid and boundary cases); README with setup.

### Attainment (to be confirmed at sprint end)

| Goal | Status | Evidence |
|------|--------|----------|
| Skeleton, auth, roles | Done | `app/Enums/Role.php`, `app/Policies`, Breeze tests |
| Feature 1 | Done | `HouseholdRegistrationTest` (11 tests) |
| Feature 2 | Done | `AssistanceCycleTest` (9 tests) |
| Feature 5 | In progress (Raihan) | integration via `routes/features/warehouse.php` |
| Feature 12 | In progress (Tuba) | integration via `routes/features/shelter.php` |
| Feature 14 | In progress (Lamisa) | integration via `routes/features/preparedness.php` |
| Tests & docs | In progress | `php artisan test`: 52 passed so far; `README.md`, `docs/`, `CONTRIBUTING.md` |

Blockers / lessons: PHP 8.5 resolves some Symfony packages to versions requiring PHP 8.4+, so
`config.platform.php` is pinned to 8.3.0 in `composer.json` to keep the dependency set portable.

### Planned for Sprint 2 (draft)

* Feature 3 — Distribution session and queue management (token scanning against the entitlements created in
  Feature 2; stock issue through the ledger from Feature 5; offline queue).
* Feature 7 — Last-mile dispatch and delivery confirmation.
* Feature 10/11 — Complaint triage and grievance loop (first version without ML).

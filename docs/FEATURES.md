# ReliefGrid — Feature definitions, acceptance criteria and test scenarios

Numbering follows the approved 20-feature list in the project proposal. Only the Sprint 1 features are
detailed here; the remaining features keep their numbers and will be documented when implemented.

Supporting capabilities that are **not** counted as features: login, registration, logout, password reset,
profile editing, user/role administration, and the Block and Item reference-data screens.

---

## Feature 1 — Household registration and token issuance

**Purpose.** Register households with minimal data, record informed consent, issue an opaque QR token card
that carries no personal data, replace lost tokens, and keep a full history of every change.

**Users.** Field Officer (register, update, deactivate, issue/reissue tokens); Coordinator/Admin (all of the
above plus reactivation and outright revocation); all staff can look up a household or token.

**Workflow.**
1. Field officer fills the registration form (block, head of household, size, shelter, phone, vulnerability
   notes) and ticks the informed-consent statement (version-stamped).
2. The system assigns a sequential household code (`HH-000001`) and, by default, issues a token:
   a random 20-character UID (`RG-…`) rendered as a QR code on a printable card.
3. Updates are recorded with the list of changed fields. Deactivation requires a reason and revokes the
   active token; reactivation (managers only) allows a fresh token to be issued.
4. "Report lost / reissue" marks the current token lost (or revoked) with a reason and issues a replacement
   that references the old one. All tokens remain visible in the household's token history.
5. A token UID can be looked up from the navigation bar (QR scanning arrives with Feature 3).

**Acceptance criteria.**
* Registration without consent is rejected; consent timestamp and version are stored.
* Token UIDs are unique, opaque (no personal data) and at most one token per household is active.
* Reissue preserves the old token with status *lost/revoked*, its reason and who did it, and links the new
  token to it.
* Deactivated households cannot receive tokens or entitlements; their active token is revoked.
* Every action appears in the household's event history with actor and timestamp.

**Test scenarios** (`tests/Feature/HouseholdRegistrationTest.php`): normal registration; missing consent;
invalid size (0, 51) and phone; second active token refused; reissue history; deactivation revokes token and
blocks issuing; reactivation role check; update change log; token lookup (valid/invalid); token card hides
personal data.

---

## Feature 2 — Assistance cycle and entitlement planning

**Purpose.** Plan what every household is entitled to in an assistance cycle, preview it, and lock it through
coordinator approval.

**Users.** Coordinator/Admin (create cycles, define rules, activate, close); all staff can view cycles and
entitlement lists.

**Workflow.**
1. Create a cycle (name, period, optional block scope). It starts as *draft*.
2. Add entitlement rules per item and household-size band:
   `quantity = base + per_member × household_size`, capped at `max_quantity`. Bands for the same item may not
   overlap.
3. The cycle page shows a live preview: totals per item, eligible household count and households that would
   get nothing; the full preview lists every household/item line.
4. *Approve & activate* (coordinator confirmation required) materialises one entitlement row per household
   and item, records the approver, and **locks** the rules (no add/edit/delete, no cycle edit).
5. Active cycles can be closed; draft cycles can be deleted.

**Acceptance criteria.**
* Only draft cycles accept rule changes; activation requires at least one rule producing entitlements.
* Inactive households and households outside the block scope are excluded.
* Entitlement quantities match the formula and cap exactly (verified by tests).
* After activation the rules are read-only and the entitlement list is persisted.

**Test scenarios** (`tests/Feature/AssistanceCycleTest.php`): role check; date validation; formula and cap
computation; overlapping bands rejected; rule needs positive quantity; activation without rules refused;
activation materialises entitlements and locks rules; confirmation required; close/delete rules.

---

## Feature 5 — Warehouse and stock management

Documented in [`features/05-warehouse-and-stock-management.md`](features/05-warehouse-and-stock-management.md).

---

## Feature 12 — Shelter repair workflow

Documented in [`features/12-shelter-repair-workflow.md`](features/12-shelter-repair-workflow.md).

---

## Feature 14 — Preparedness and incident response

Documented in [`features/14-preparedness-and-incident-response.md`](features/14-preparedness-and-incident-response.md).

---

## Supporting capabilities (not counted as features)

* **Authentication** (Laravel Breeze): registration (always Field Officer), login with rate limiting, logout,
  password reset by email, profile and password update, account deletion. Deactivated accounts cannot log in and
  are logged out on their next request.
* **Roles and permissions:** Administrator, Coordinator, Field Officer, Warehouse Officer, Shelter Assessor —
  enforced by Policies (per action) and the `role` middleware (per route group).
* **Reference data:** settlement Blocks and the Item catalogue.

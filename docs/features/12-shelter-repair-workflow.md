# Feature 12 – Shelter repair workflow

**Owner:** Tuba  **Sprint:** 1

## Purpose
Move a shelter repair request through **assessment → approval → material issuance → completion**, with closure
blocked until a **geo-tagged verification photograph** has been captured.

## Actors and permissions (`ShelterRepairRequestPolicy`)
| Step | Allowed roles | Required state |
|------|---------------|----------------|
| Submit request | Field Officer, Shelter Assessor, Coordinator, Admin | – |
| Assess (severity + materials list) | Shelter Assessor, Admin | Submitted |
| Approve | Coordinator, Admin | Assessed |
| Reject (reason required) | Coordinator, Admin | Submitted or Assessed |
| Issue materials | Warehouse Officer, Admin | Approved / Materials issued |
| Add verification photo | Assessor, Field Officer, Coordinator, Admin | not closed |
| Complete | Assessor, Coordinator, Admin | Approved (no materials) or Materials issued |

## Workflow (state machine – `App\Enums\RepairStatus`)
```
submitted -> assessed -> approved -> materials_issued -> completed
     \            \
      `------------`--> rejected (terminal)
```
`approved -> completed` is allowed only when the assessment listed no materials. Every transition is written to
`shelter_repair_events` (timeline) with actor, time and note.

## Business rules (`App\Services\ShelterRepairService`)
1. A request takes its block from the household; code is `SR-00001`, `SR-00002`, …
2. Assessment replaces the material list; materials have a required quantity and a unit.
3. Issued quantity per line can never exceed the required quantity (checked inside a transaction with a row lock).
4. When Feature 5 (`StockService`/`StockBatch`) is installed and a batch is chosen, the issue is posted to the
   stock ledger (`StockService::issue`); otherwise it is recorded on the repair only. Feature 12 does not require
   Feature 5 to be installed.
5. When every material line is fully issued the request moves to *Materials issued*.
6. **A photo is accepted only if it is geo-tagged**: coordinates are read from the JPEG EXIF GPS block; if the
   file has none, the browser/device location (captured with the "Use my current location" button) must be sent.
   `(0, 0)` and out-of-range coordinates are rejected.
7. **Completion is blocked** while no geo-tagged photo exists and while planned materials are outstanding.
8. On PostgreSQL with PostGIS the photo also gets a `geography(Point,4326)` value and a GiST index.

## Data model
`shelter_repair_requests`, `shelter_repair_materials`, `shelter_verification_photos`, `shelter_repair_events`
(migrations `2026_11_07_000040`–`000043`).

## Acceptance criteria
* A field officer can submit a request for an active household; it appears in the list with status *Submitted*.
* Each workflow step is available only to the roles above and only in the right state; other attempts return 403.
* A rejected request cannot be changed any more.
* Over-issuing a material is refused with a validation message.
* A request cannot be completed without a geo-tagged photo; after one is uploaded it can.
* The timeline shows every step with user and time.

## Test scenarios (`tests/Feature/ShelterRepairTest.php`, `tests/Unit/RepairStatusTest.php`)
Submission and validation · role enforcement per step · terminal rejection · over-issue refused and automatic move
to *Materials issued* · closure blocked without photo, allowed with one · non-geo-tagged photo refused · EXIF GPS
extraction (`tests/fixtures/gps.jpg`) · state-machine and coordinate unit tests · list/detail pages render.

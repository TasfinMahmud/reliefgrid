# Feature 14 — Preparedness and incident response

Part of the ReliefGrid feature documentation — see [`../FEATURES.md`](../FEATURES.md) for the full list.

**Purpose.** Issue block-level hazard alerts, track evacuation site capacity and occupancy, and log incidents
through investigation and response to a verified closure.

**Users.** Coordinator/Admin (issue/cancel alerts, manage sites, verify closure); Field Officer (record
occupancy, report incidents, run investigation/response); all staff can view.

**Workflow.**
1. Alerts: hazard type, severity (advisory/watch/warning/emergency), message, instructions, expiry, one or more
   blocks. The alert board shows the highest active severity per block; alerts expire automatically or are
   cancelled with a reason.
2. Evacuation sites: capacity, facilities, contact; occupancy readings are logged with previous/new value and
   author; sites can be closed/reopened.
3. Incidents: `reported → under_investigation → response_in_progress → closed`. Investigation and response
   notes, affected counts and the evacuation site used are recorded; closure requires a coordinator/admin to
   enter verification notes.

**Acceptance criteria.**
* An alert must target at least one block; expired or cancelled alerts are not shown as active.
* Occupancy can never exceed capacity or be negative; capacity cannot be reduced below current occupancy;
  closed sites refuse readings.
* Incident steps must happen in order; closure is only possible by a manager and only from
  *response in progress* with investigation and response notes present.

**Test scenarios** (`tests/Feature/PreparednessTest.php`): alert issue and visibility; validation and role;
cancel and expiry; occupancy bounds and log; capacity/closed-site rules; incident validation; full lifecycle
with order and role checks; notes on open incidents only.

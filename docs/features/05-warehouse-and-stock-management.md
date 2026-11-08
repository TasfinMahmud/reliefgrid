# Feature 5 — Warehouse and stock management

Part of the ReliefGrid feature documentation — see [`../FEATURES.md`](../FEATURES.md) for the full list.

**Purpose.** Receive stock in batches with expiry, issue stock, transfer between hubs with approval, and derive
every balance from an append-only movement ledger that reconciles exactly.

**Users.** Warehouse Officer (receive, issue, request transfers); Coordinator (approve/reject transfers);
Admin (both); all staff can view stock, ledger and reports.

**Workflow.**
1. Receive: item, batch number, quantity, expiry, supplier → a batch and a `receipt` ledger entry.
2. Issue: choose a batch (FEFO order shown), quantity and purpose (optionally an assistance cycle) → an
   `issue` ledger entry (negative). Expired batches and quantities above the balance are refused.
3. Transfer: request from a source batch to another warehouse → *pending*. Coordinator approval writes a
   `transfer_out` at the source and a `transfer_in` at the destination (creating a destination batch with the
   same number, expiry and origin link). Rejection or cancellation leaves the ledger untouched.
4. Balances per item, per batch and per warehouse are always `SUM(quantity)` over the ledger.
5. Reconciliation report recomputes receipts, issues, transfers per batch and flags any mismatch; expiry report
   lists batches with stock that are expired or expire within 30/60 days.

**Acceptance criteria.**
* Ledger rows can never be updated or deleted (model guard); corrections are new rows.
* No batch can go negative; pending transfers reserve stock.
* A requester cannot approve their own transfer.
* Reconciliation shows "OK" for every batch after any sequence of operations.

**Test scenarios** (`tests/Feature/StockLedgerTest.php`): receipt; validation (expired, future date, zero
quantity, duplicate batch); roles; issue over balance; expired batch; append-only guard; transfer approval
writes both entries; transfer quantity limits; rejected transfer; derived balances and reconciliation; expiry
report.

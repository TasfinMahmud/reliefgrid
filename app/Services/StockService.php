<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Enums\TransferStatus;
use App\Models\Item;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Feature 5 – Warehouse and stock management.
 *
 * Every change to stock is an entry in the append-only movement ledger.
 * Balances are never stored; they are derived by summing the ledger.
 */
class StockService
{
    /**
     * Receive a new batch into a warehouse.
     *
     * @param  array{batch_number:string, expiry_date?:string|null, supplier?:string|null, quantity:float|string, received_at:string, notes?:string|null}  $data
     */
    public function receive(Warehouse $warehouse, Item $item, array $data, User $actor): StockBatch
    {
        $qty = round((float) $data['quantity'], 2);
        if ($qty <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Received quantity must be greater than zero.']);
        }

        $exists = StockBatch::where('warehouse_id', $warehouse->id)
            ->where('item_id', $item->id)
            ->where('batch_number', $data['batch_number'])
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages(['batch_number' => 'This batch number already exists for this item in this warehouse.']);
        }

        return DB::transaction(function () use ($warehouse, $item, $data, $qty, $actor) {
            $batch = StockBatch::create([
                'warehouse_id' => $warehouse->id,
                'item_id' => $item->id,
                'batch_number' => $data['batch_number'],
                'expiry_date' => $data['expiry_date'] ?? null,
                'supplier' => $data['supplier'] ?? null,
                'received_quantity' => $qty,
                'received_at' => $data['received_at'],
                'received_by' => $actor->id,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->record($batch, MovementType::Receipt, $qty, $actor, null, 'Batch received'.(! empty($data['supplier']) ? ' from '.$data['supplier'] : ''));

            return $batch;
        });
    }

    /**
     * Issue stock out of a batch (for a distribution, a repair, etc.).
     * Fails if the batch does not hold enough.
     */
    public function issue(StockBatch $batch, float $quantity, User $actor, ?Model $reference = null, ?string $note = null): StockMovement
    {
        $quantity = round($quantity, 2);
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Issued quantity must be greater than zero.']);
        }
        if ($batch->isExpired()) {
            throw ValidationException::withMessages(['stock_batch_id' => 'This batch has expired and cannot be issued.']);
        }
        $balance = $this->batchBalance($batch);
        if ($quantity > $balance) {
            throw ValidationException::withMessages([
                'quantity' => sprintf('Only %s %s available in batch %s.', number_format($balance, 2), $batch->item->unit, $batch->batch_number),
            ]);
        }

        return DB::transaction(fn () => $this->record($batch, MovementType::Issue, -$quantity, $actor, $reference, $note ?? 'Stock issued'));
    }

    /**
     * Request a transfer of stock between two hubs (requires approval).
     */
    public function requestTransfer(StockBatch $batch, Warehouse $to, float $quantity, User $actor, ?string $reason = null): StockTransfer
    {
        $quantity = round($quantity, 2);
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Transfer quantity must be greater than zero.']);
        }
        if ($to->id === $batch->warehouse_id) {
            throw ValidationException::withMessages(['to_warehouse_id' => 'Source and destination warehouses must differ.']);
        }
        if (! $to->is_active) {
            throw ValidationException::withMessages(['to_warehouse_id' => 'The destination warehouse is not active.']);
        }

        $available = $this->batchBalance($batch) - $this->pendingTransferQuantity($batch);
        if ($quantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => sprintf('Only %s %s available (after pending transfers) in batch %s.', number_format($available, 2), $batch->item->unit, $batch->batch_number),
            ]);
        }

        return StockTransfer::create([
            'from_warehouse_id' => $batch->warehouse_id,
            'to_warehouse_id' => $to->id,
            'item_id' => $batch->item_id,
            'stock_batch_id' => $batch->id,
            'quantity' => $quantity,
            'status' => TransferStatus::Pending,
            'reason' => $reason,
            'requested_by' => $actor->id,
            'requested_at' => now(),
        ]);
    }

    /**
     * Approve a transfer: writes a transfer_out at the source and a transfer_in
     * at the destination (creating/reusing a batch with the same number).
     */
    public function approveTransfer(StockTransfer $transfer, User $approver, ?string $note = null): StockTransfer
    {
        if (! $transfer->isPending()) {
            throw ValidationException::withMessages(['status' => 'Only pending transfers can be approved.']);
        }
        if ($transfer->requested_by === $approver->id && ! $approver->isAdmin()) {
            throw ValidationException::withMessages(['status' => 'A transfer cannot be approved by the person who requested it.']);
        }

        $source = $transfer->batch;
        $qty = (float) $transfer->quantity;
        if ($qty > $this->batchBalance($source)) {
            throw ValidationException::withMessages(['quantity' => 'The source batch no longer holds enough stock for this transfer.']);
        }

        return DB::transaction(function () use ($transfer, $approver, $note, $source, $qty) {
            $destination = StockBatch::firstOrCreate(
                [
                    'warehouse_id' => $transfer->to_warehouse_id,
                    'item_id' => $transfer->item_id,
                    'batch_number' => $source->batch_number,
                ],
                [
                    'expiry_date' => $source->expiry_date,
                    'supplier' => $source->supplier,
                    'received_quantity' => 0,
                    'received_at' => now()->toDateString(),
                    'received_by' => $approver->id,
                    'origin_batch_id' => $source->id,
                    'notes' => 'Created by transfer #'.$transfer->id.' from '.$transfer->fromWarehouse->code,
                ]
            );

            $this->record($source, MovementType::TransferOut, -$qty, $approver, $transfer, 'Transfer to '.$transfer->toWarehouse->code);
            $this->record($destination, MovementType::TransferIn, $qty, $approver, $transfer, 'Transfer from '.$transfer->fromWarehouse->code);

            $transfer->status = TransferStatus::Approved;
            $transfer->reviewed_by = $approver->id;
            $transfer->reviewed_at = now();
            $transfer->review_note = $note;
            $transfer->destination_batch_id = $destination->id;
            $transfer->save();

            return $transfer;
        });
    }

    public function rejectTransfer(StockTransfer $transfer, User $reviewer, string $note): StockTransfer
    {
        if (! $transfer->isPending()) {
            throw ValidationException::withMessages(['status' => 'Only pending transfers can be rejected.']);
        }
        $transfer->status = TransferStatus::Rejected;
        $transfer->reviewed_by = $reviewer->id;
        $transfer->reviewed_at = now();
        $transfer->review_note = $note;
        $transfer->save();

        return $transfer;
    }

    public function cancelTransfer(StockTransfer $transfer, User $actor): StockTransfer
    {
        if (! $transfer->isPending()) {
            throw ValidationException::withMessages(['status' => 'Only pending transfers can be cancelled.']);
        }
        $transfer->status = TransferStatus::Cancelled;
        $transfer->reviewed_by = $actor->id;
        $transfer->reviewed_at = now();
        $transfer->review_note = 'Cancelled by requester';
        $transfer->save();

        return $transfer;
    }

    // ------------------------------------------------------------------
    // Balances & reporting (always derived from the ledger)
    // ------------------------------------------------------------------

    public function batchBalance(StockBatch $batch): float
    {
        return round((float) StockMovement::where('stock_batch_id', $batch->id)->sum('quantity'), 2);
    }

    public function pendingTransferQuantity(StockBatch $batch): float
    {
        return round((float) StockTransfer::where('stock_batch_id', $batch->id)
            ->where('status', TransferStatus::Pending->value)->sum('quantity'), 2);
    }

    /**
     * Stock per item in a warehouse: [item_id => ['item' => Item, 'balance' => float, 'batches' => int]]
     */
    public function warehouseStock(Warehouse $warehouse): Collection
    {
        $balances = StockMovement::where('warehouse_id', $warehouse->id)
            ->selectRaw('item_id, SUM(quantity) as balance, COUNT(DISTINCT stock_batch_id) as batches')
            ->groupBy('item_id')
            ->get()
            ->keyBy('item_id');

        $items = Item::whereIn('id', $balances->keys())->orderBy('name')->get();

        return $items->map(fn (Item $item) => [
            'item' => $item,
            'balance' => round((float) $balances[$item->id]->balance, 2),
            'batches' => (int) $balances[$item->id]->batches,
        ]);
    }

    /**
     * Batches in a warehouse with their derived balances, FEFO ordered.
     *
     * @return Collection<int, array{batch: StockBatch, balance: float}>
     */
    public function warehouseBatches(Warehouse $warehouse, bool $onlyWithStock = true): Collection
    {
        $batches = StockBatch::with('item')->where('warehouse_id', $warehouse->id)->fefo()->get();
        $sums = StockMovement::whereIn('stock_batch_id', $batches->pluck('id'))
            ->selectRaw('stock_batch_id, SUM(quantity) as balance')
            ->groupBy('stock_batch_id')->pluck('balance', 'stock_batch_id');

        return $batches->map(fn (StockBatch $b) => [
            'batch' => $b,
            'balance' => round((float) ($sums[$b->id] ?? 0), 2),
        ])->filter(fn ($row) => ! $onlyWithStock || $row['balance'] > 0)->values();
    }

    /**
     * Reconciliation: for every batch, recompute inbound/outbound totals from
     * the ledger and confirm they match the derived balance exactly.
     *
     * @return Collection<int, array>
     */
    public function reconcile(Warehouse $warehouse): Collection
    {
        $batches = StockBatch::with('item')->where('warehouse_id', $warehouse->id)->orderBy('item_id')->orderBy('received_at')->get();
        $byType = StockMovement::whereIn('stock_batch_id', $batches->pluck('id'))
            ->selectRaw('stock_batch_id, type, SUM(quantity) as total')
            ->groupBy('stock_batch_id', 'type')->get()
            ->groupBy('stock_batch_id');

        return $batches->map(function (StockBatch $b) use ($byType) {
            $rows = $byType[$b->id] ?? collect();
            $get = fn (MovementType $t) => round((float) ($rows->firstWhere('type', $t)?->total ?? 0), 2);
            $receipts = $get(MovementType::Receipt);
            $transfersIn = $get(MovementType::TransferIn);
            $issues = $get(MovementType::Issue);        // negative
            $transfersOut = $get(MovementType::TransferOut); // negative
            $ledgerBalance = round($receipts + $transfersIn + $issues + $transfersOut, 2);
            $expected = round($receipts + $transfersIn - abs($issues) - abs($transfersOut), 2);

            $problems = [];
            if (abs($ledgerBalance - $expected) > 0.001) {
                $problems[] = 'Ledger sum does not equal inbound − outbound.';
            }
            if ($ledgerBalance < 0) {
                $problems[] = 'Negative balance.';
            }
            if ($receipts > 0 && abs($receipts - (float) $b->received_quantity) > 0.001) {
                $problems[] = 'Receipt entries do not match the batch received quantity.';
            }

            return [
                'batch' => $b,
                'receipts' => $receipts,
                'transfers_in' => $transfersIn,
                'issues' => abs($issues),
                'transfers_out' => abs($transfersOut),
                'balance' => $ledgerBalance,
                'ok' => empty($problems),
                'problems' => $problems,
            ];
        });
    }

    /** Batches with stock that are expired or expiring within $days. */
    public function expiryAlerts(int $days = 30): Collection
    {
        $batches = StockBatch::with(['item', 'warehouse'])
            ->where(function ($q) use ($days) {
                $q->expired()->orWhere(fn ($q2) => $q2->expiringWithin($days));
            })->orderBy('expiry_date')->get();

        $sums = StockMovement::whereIn('stock_batch_id', $batches->pluck('id'))
            ->selectRaw('stock_batch_id, SUM(quantity) as balance')
            ->groupBy('stock_batch_id')->pluck('balance', 'stock_batch_id');

        return $batches->map(fn (StockBatch $b) => [
            'batch' => $b,
            'balance' => round((float) ($sums[$b->id] ?? 0), 2),
            'expired' => $b->isExpired(),
        ])->filter(fn ($row) => $row['balance'] > 0)->values();
    }

    private function record(StockBatch $batch, MovementType $type, float $signedQuantity, User $actor, ?Model $reference, ?string $note): StockMovement
    {
        return StockMovement::create([
            'warehouse_id' => $batch->warehouse_id,
            'item_id' => $batch->item_id,
            'stock_batch_id' => $batch->id,
            'type' => $type,
            'quantity' => $signedQuantity,
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference?->getKey(),
            'note' => $note,
            'created_by' => $actor->id,
            'created_at' => now(),
        ]);
    }
}

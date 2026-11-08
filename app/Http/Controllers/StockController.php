<?php

namespace App\Http\Controllers;

use App\Enums\MovementType;
use App\Http\Requests\Stock\IssueStockRequest;
use App\Http\Requests\Stock\ReceiveStockRequest;
use App\Models\AssistanceCycle;
use App\Models\Item;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Feature 5 – Receipts, issues and the movement ledger.
 */
class StockController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    public function receiveForm(Warehouse $warehouse): View
    {
        $this->authorize('operate', $warehouse);

        return view('stock.receive', ['warehouse' => $warehouse, 'items' => Item::active()->orderBy('name')->get()]);
    }

    public function receive(ReceiveStockRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $data = $request->validated();
        $item = Item::findOrFail($data['item_id']);

        $batch = $this->stock->receive($warehouse, $item, $data, $request->user());

        return redirect()->route('warehouses.show', $warehouse)
            ->with('success', sprintf('Received %s %s of %s (batch %s).', number_format((float) $batch->received_quantity, 2), $item->unit, $item->name, $batch->batch_number));
    }

    public function issueForm(Warehouse $warehouse): View
    {
        $this->authorize('operate', $warehouse);

        return view('stock.issue', [
            'warehouse' => $warehouse,
            'batches' => $this->stock->warehouseBatches($warehouse)->filter(fn ($r) => ! $r['batch']->isExpired())->values(),
            'cycles' => AssistanceCycle::active()->orderBy('name')->get(),
        ]);
    }

    public function issue(IssueStockRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $data = $request->validated();
        $batch = StockBatch::findOrFail($data['stock_batch_id']);
        abort_unless($batch->warehouse_id === $warehouse->id, 404);

        $reference = ! empty($data['assistance_cycle_id']) ? AssistanceCycle::find($data['assistance_cycle_id']) : null;
        $this->stock->issue($batch, (float) $data['quantity'], $request->user(), $reference, $data['note']);

        return redirect()->route('warehouses.show', $warehouse)
            ->with('success', sprintf('Issued %s %s of %s from batch %s.', number_format((float) $data['quantity'], 2), $batch->item->unit, $batch->item->name, $batch->batch_number));
    }

    /** Movement ledger with filters. */
    public function ledger(Request $request): View
    {
        $this->authorize('viewAny', Warehouse::class);

        $movements = StockMovement::with(['warehouse', 'item', 'batch', 'createdBy'])
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            ->when($request->filled('item_id'), fn ($q) => $q->where('item_id', $request->integer('item_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(30)->withQueryString();

        return view('stock.ledger', [
            'movements' => $movements,
            'warehouses' => Warehouse::orderBy('code')->get(),
            'items' => Item::orderBy('name')->get(),
            'types' => MovementType::cases(),
        ]);
    }

    public function batch(StockBatch $batch): View
    {
        $this->authorize('view', $batch->warehouse);
        $batch->load(['item', 'warehouse', 'receivedBy', 'originBatch.warehouse', 'movements.createdBy']);

        return view('stock.batch', ['batch' => $batch, 'balance' => $this->stock->batchBalance($batch)]);
    }

    public function expiry(): View
    {
        $this->authorize('viewAny', Warehouse::class);

        return view('stock.expiry', ['rows' => $this->stock->expiryAlerts(60)]);
    }
}

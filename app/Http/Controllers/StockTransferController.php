<?php

namespace App\Http\Controllers;

use App\Enums\TransferStatus;
use App\Http\Requests\Stock\TransferRequest;
use App\Models\StockBatch;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Feature 5 – Transfers between hubs with coordinator approval.
 */
class StockTransferController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', StockTransfer::class);

        $transfers = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'item', 'batch', 'requestedBy', 'reviewedBy'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('requested_at')->paginate(20)->withQueryString();

        return view('stock.transfers.index', ['transfers' => $transfers, 'statuses' => TransferStatus::cases()]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', StockTransfer::class);

        $from = $request->filled('warehouse_id') ? Warehouse::find($request->integer('warehouse_id')) : null;
        $warehouses = Warehouse::active()->orderBy('code')->get();

        $batches = $from ? $this->stock->warehouseBatches($from)->filter(fn ($r) => ! $r['batch']->isExpired())->values() : collect();

        return view('stock.transfers.create', compact('from', 'warehouses', 'batches'));
    }

    public function store(TransferRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $batch = StockBatch::with('warehouse')->findOrFail($data['stock_batch_id']);
        $this->authorize('requestTransfer', $batch->warehouse);
        $to = Warehouse::findOrFail($data['to_warehouse_id']);

        $transfer = $this->stock->requestTransfer($batch, $to, (float) $data['quantity'], $request->user(), $data['reason'] ?? null);

        return redirect()->route('transfers.index')->with('success', "Transfer #{$transfer->id} requested; awaiting coordinator approval.");
    }

    public function show(StockTransfer $transfer): View
    {
        $this->authorize('view', $transfer);
        $transfer->load(['fromWarehouse', 'toWarehouse', 'item', 'batch', 'destinationBatch', 'requestedBy', 'reviewedBy', 'movements']);

        return view('stock.transfers.show', ['transfer' => $transfer, 'available' => $this->stock->batchBalance($transfer->batch)]);
    }

    public function approve(Request $request, StockTransfer $transfer): RedirectResponse
    {
        $this->authorize('review', $transfer);
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:255']]);

        $this->stock->approveTransfer($transfer, $request->user(), $data['review_note'] ?? null);

        return redirect()->route('transfers.show', $transfer)->with('success', 'Transfer approved; ledger entries written at both warehouses.');
    }

    public function reject(Request $request, StockTransfer $transfer): RedirectResponse
    {
        $this->authorize('review', $transfer);
        $data = $request->validate(['review_note' => ['required', 'string', 'max:255']]);

        $this->stock->rejectTransfer($transfer, $request->user(), $data['review_note']);

        return redirect()->route('transfers.show', $transfer)->with('success', 'Transfer rejected.');
    }

    public function cancel(Request $request, StockTransfer $transfer): RedirectResponse
    {
        $this->authorize('cancel', $transfer);
        $this->stock->cancelTransfer($transfer, $request->user());

        return redirect()->route('transfers.index')->with('success', 'Transfer cancelled.');
    }
}

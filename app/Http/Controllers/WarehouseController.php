<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Feature 5 – Warehouses (hubs) and their stock overview.
 */
class WarehouseController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    public function index(): View
    {
        $this->authorize('viewAny', Warehouse::class);

        $warehouses = Warehouse::with('block')->withCount('batches')->orderBy('code')->get()
            ->map(function (Warehouse $w) {
                $w->setAttribute('stock_lines', $this->stock->warehouseStock($w)->count());

                return $w;
            });

        return view('warehouses.index', [
            'warehouses' => $warehouses,
            'expiry' => $this->stock->expiryAlerts(30),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Warehouse::class);

        return view('warehouses.form', ['warehouse' => new Warehouse(['is_active' => true]), 'blocks' => Block::orderBy('code')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Warehouse::class);
        $warehouse = Warehouse::create($this->validated($request));

        return redirect()->route('warehouses.show', $warehouse)->with('success', "Warehouse {$warehouse->code} created.");
    }

    public function show(Warehouse $warehouse): View
    {
        $this->authorize('view', $warehouse);

        return view('warehouses.show', [
            'warehouse' => $warehouse->load('block'),
            'stock' => $this->stock->warehouseStock($warehouse),
            'batches' => $this->stock->warehouseBatches($warehouse, false),
            'pendingTransfers' => $warehouse->outgoingTransfers()->where('status', 'pending')->with(['item', 'toWarehouse'])->get(),
        ]);
    }

    public function edit(Warehouse $warehouse): View
    {
        $this->authorize('update', $warehouse);

        return view('warehouses.form', ['warehouse' => $warehouse, 'blocks' => Block::orderBy('code')->get()]);
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $this->authorize('update', $warehouse);
        $warehouse->update($this->validated($request, $warehouse));

        return redirect()->route('warehouses.show', $warehouse)->with('success', "Warehouse {$warehouse->code} updated.");
    }

    /** Reconciliation report: ledger totals per batch. */
    public function reconcile(Warehouse $warehouse): View
    {
        $this->authorize('view', $warehouse);

        $rows = $this->stock->reconcile($warehouse);

        return view('warehouses.reconcile', ['warehouse' => $warehouse, 'rows' => $rows, 'allOk' => $rows->every(fn ($r) => $r['ok'])]);
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, ?Warehouse $warehouse = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('warehouses', 'code')->ignore($warehouse?->id)],
            'name' => ['required', 'string', 'max:120'],
            'block_id' => ['nullable', 'integer', 'exists:blocks,id'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}

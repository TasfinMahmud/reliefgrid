<x-app-layout title="Warehouses">
    <x-slot name="header">
        <h1>Warehouses &amp; stock</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('stock.ledger') }}" class="btn btn-outline-secondary btn-sm">Ledger</a>
            @can('create', \App\Models\StockTransfer::class)
                <a href="{{ route('transfers.create') }}" class="btn btn-outline-secondary btn-sm">Request transfer</a>
            @endcan
            @can('create', \App\Models\Warehouse::class)
                <a href="{{ route('warehouses.create') }}" class="btn btn-rg btn-sm">New warehouse</a>
            @endcan
        </div>
    </x-slot>

    @if($expiry->isNotEmpty())
        <div class="alert alert-warning">
            <strong>{{ $expiry->count() }}</strong> batch(es) with stock are expired or expire within 30 days.
            <a href="{{ route('stock.expiry') }}" class="alert-link">View expiry report</a>
        </div>
    @endif

    <div class="row g-3">
        @forelse($warehouses as $warehouse)
            <div class="col-md-6 col-xl-4">
                <div class="card shadow-sm h-100 {{ $warehouse->is_active ? '' : 'opacity-75' }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2 class="h5 mb-0"><a href="{{ route('warehouses.show', $warehouse) }}" class="text-decoration-none">{{ $warehouse->code }} · {{ $warehouse->name }}</a></h2>
                                <div class="small text-muted">{{ $warehouse->block?->display_name ?? 'No block' }}{{ $warehouse->address ? ' · '.$warehouse->address : '' }}</div>
                            </div>
                            <span class="badge {{ $warehouse->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $warehouse->is_active ? 'Active' : 'Inactive' }}</span>
                        </div>
                        <div class="mt-3 d-flex gap-4 small">
                            <div><div class="text-muted">Items in stock</div><div class="fs-5 fw-semibold">{{ $warehouse->stock_lines }}</div></div>
                            <div><div class="text-muted">Batches</div><div class="fs-5 fw-semibold">{{ $warehouse->batches_count }}</div></div>
                        </div>
                    </div>
                    <div class="card-footer bg-white d-flex gap-2">
                        <a href="{{ route('warehouses.show', $warehouse) }}" class="btn btn-sm btn-outline-secondary">Stock</a>
                        @can('operate', $warehouse)
                            <a href="{{ route('stock.receive', $warehouse) }}" class="btn btn-sm btn-rg">Receive</a>
                            <a href="{{ route('stock.issue', $warehouse) }}" class="btn btn-sm btn-outline-danger">Issue</a>
                        @endcan
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-muted">No warehouses yet.</div>
        @endforelse
    </div>
</x-app-layout>

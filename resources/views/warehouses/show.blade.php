<x-app-layout :title="$warehouse->code">
    <x-slot name="header">
        <div>
            <h1>{{ $warehouse->code }} · {{ $warehouse->name }} <span class="badge {{ $warehouse->is_active ? 'bg-success' : 'bg-secondary' }} fs-6 align-middle">{{ $warehouse->is_active ? 'Active' : 'Inactive' }}</span></h1>
            <div class="text-muted small">{{ $warehouse->block?->display_name ?? 'No block' }}{{ $warehouse->address ? ' · '.$warehouse->address : '' }}</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('warehouses.reconcile', $warehouse) }}" class="btn btn-outline-secondary btn-sm">Reconcile</a>
            <a href="{{ route('stock.ledger', ['warehouse_id' => $warehouse->id]) }}" class="btn btn-outline-secondary btn-sm">Ledger</a>
            @can('update', $warehouse)
                <a href="{{ route('warehouses.edit', $warehouse) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
            @endcan
            @can('operate', $warehouse)
                <a href="{{ route('transfers.create', ['warehouse_id' => $warehouse->id]) }}" class="btn btn-outline-secondary btn-sm">Transfer out</a>
                <a href="{{ route('stock.issue', $warehouse) }}" class="btn btn-outline-danger btn-sm">Issue stock</a>
                <a href="{{ route('stock.receive', $warehouse) }}" class="btn btn-rg btn-sm">Receive stock</a>
            @endcan
        </div>
    </x-slot>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Stock by item <span class="small text-muted fw-normal">(derived from ledger)</span></div>
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Item</th><th class="text-end">Balance</th><th class="text-end">Batches</th></tr></thead>
                    <tbody>
                    @forelse($stock as $row)
                        <tr>
                            <td>{{ $row['item']->name }}</td>
                            <td class="text-end fw-semibold">{{ number_format($row['balance'], 2) }} {{ $row['item']->unit }}</td>
                            <td class="text-end">{{ $row['batches'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">No stock movements yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($pendingTransfers->isNotEmpty())
                <div class="card shadow-sm mt-4">
                    <div class="card-header fw-semibold">Pending outgoing transfers</div>
                    <ul class="list-group list-group-flush">
                        @foreach($pendingTransfers as $t)
                            <li class="list-group-item small">
                                <a href="{{ route('transfers.show', $t) }}">#{{ $t->id }}</a> {{ number_format((float) $t->quantity, 2) }} {{ $t->item->unit }} {{ $t->item->name }} → {{ $t->toWarehouse->code }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Batches <span class="small text-muted fw-normal">(FEFO order: first expiry first)</span></div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light"><tr><th>Batch</th><th>Item</th><th>Expiry</th><th>Received</th><th class="text-end">Received qty</th><th class="text-end">Balance</th></tr></thead>
                        <tbody>
                        @forelse($batches as $row)
                            @php($b = $row['batch'])
                            <tr class="{{ $b->isExpired() && $row['balance'] > 0 ? 'table-danger' : ($b->expiresWithin(30) && $row['balance'] > 0 ? 'table-warning' : ($row['balance'] <= 0 ? 'text-muted' : '')) }}">
                                <td><a href="{{ route('stock.batch', $b) }}">{{ $b->batch_number }}</a>@if($b->origin_batch_id) <span class="badge bg-info text-dark">transferred in</span>@endif</td>
                                <td>{{ $b->item->name }}</td>
                                <td>{{ $b->expiry_date?->format('d M Y') ?? '—' }} @if($b->isExpired())<span class="badge bg-danger">expired</span>@elseif($b->expiresWithin(30))<span class="badge bg-warning text-dark">soon</span>@endif</td>
                                <td>{{ $b->received_at->format('d M Y') }}</td>
                                <td class="text-end">{{ number_format((float) $b->received_quantity, 2) }}</td>
                                <td class="text-end fw-semibold">{{ number_format($row['balance'], 2) }} {{ $b->item->unit }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">No batches.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

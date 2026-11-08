<x-app-layout title="Stock ledger">
    <x-slot name="header">
        <div>
            <h1>Stock movement ledger</h1>
            <div class="text-muted small">Append-only. Entries are never edited or deleted; corrections are new entries.</div>
        </div>
    </x-slot>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="warehouse_id" class="form-select">
                <option value="">All warehouses</option>
                @foreach($warehouses as $w)
                    <option value="{{ $w->id }}" @selected(request('warehouse_id') == $w->id)>{{ $w->display_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="item_id" class="form-select">
                <option value="">All items</option>
                @foreach($items as $i)
                    <option value="{{ $i->id }}" @selected(request('item_id') == $i->id)>{{ $i->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="type" class="form-select">
                <option value="">All types</option>
                @foreach($types as $t)
                    <option value="{{ $t->value }}" @selected(request('type') === $t->value)>{{ $t->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light"><tr><th>#</th><th>When</th><th>Warehouse</th><th>Item</th><th>Batch</th><th>Type</th><th class="text-end">Quantity</th><th>Reference</th><th>Note</th><th>By</th></tr></thead>
                <tbody>
                @forelse($movements as $m)
                    <tr>
                        <td class="text-muted">{{ $m->id }}</td>
                        <td class="small text-nowrap">{{ $m->created_at->format('d M Y H:i') }}</td>
                        <td>{{ $m->warehouse->code }}</td>
                        <td>{{ $m->item->name }}</td>
                        <td><a href="{{ route('stock.batch', $m->batch) }}">{{ $m->batch->batch_number }}</a></td>
                        <td><x-badge :status="$m->type" /></td>
                        <td class="text-end fw-semibold {{ (float) $m->quantity < 0 ? 'text-danger' : 'text-success' }}">{{ (float) $m->quantity > 0 ? '+' : '' }}{{ number_format((float) $m->quantity, 2) }} {{ $m->item->unit }}</td>
                        <td class="small">{{ $m->referenceLabel() ?? '—' }}</td>
                        <td class="small text-muted">{{ $m->note }}</td>
                        <td class="small">{{ $m->createdBy?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-muted">No movements.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $movements->links() }}</div>
</x-app-layout>

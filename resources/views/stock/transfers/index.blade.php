<x-app-layout title="Stock transfers">
    <x-slot name="header">
        <h1>Stock transfers</h1>
        @can('create', \App\Models\StockTransfer::class)
            <a href="{{ route('transfers.create') }}" class="btn btn-rg btn-sm">Request transfer</a>
        @endcan
    </x-slot>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Any status</option>
                @foreach($statuses as $s)
                    <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light"><tr><th>#</th><th>From → To</th><th>Item</th><th>Batch</th><th class="text-end">Quantity</th><th>Status</th><th>Requested</th><th>Reviewed</th></tr></thead>
                <tbody>
                @forelse($transfers as $t)
                    <tr>
                        <td><a href="{{ route('transfers.show', $t) }}">#{{ $t->id }}</a></td>
                        <td>{{ $t->fromWarehouse->code }} → {{ $t->toWarehouse->code }}</td>
                        <td>{{ $t->item->name }}</td>
                        <td>{{ $t->batch->batch_number }}</td>
                        <td class="text-end">{{ number_format((float) $t->quantity, 2) }} {{ $t->item->unit }}</td>
                        <td><x-badge :status="$t->status" /></td>
                        <td class="small">{{ $t->requested_at->format('d M Y H:i') }}<br><span class="text-muted">{{ $t->requestedBy?->name }}</span></td>
                        <td class="small">{{ $t->reviewed_at?->format('d M Y H:i') ?? '—' }}@if($t->reviewedBy)<br><span class="text-muted">{{ $t->reviewedBy->name }}</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-muted">No transfers.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $transfers->links() }}</div>
</x-app-layout>

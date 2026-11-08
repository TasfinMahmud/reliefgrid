<x-app-layout :title="'Reconciliation · '.$warehouse->code">
    <x-slot name="header">
        <div>
            <h1>Reconciliation · {{ $warehouse->code }}</h1>
            <div class="text-muted small">Every balance is recomputed from the append-only movement ledger and checked against inbound − outbound.</div>
        </div>
        <a href="{{ route('warehouses.show', $warehouse) }}" class="btn btn-outline-secondary btn-sm">Back to warehouse</a>
    </x-slot>

    <div class="alert {{ $allOk ? 'alert-success' : 'alert-danger' }}">
        @if($allOk)
            All {{ $rows->count() }} batch(es) reconcile exactly.
        @else
            {{ $rows->where('ok', false)->count() }} batch(es) have discrepancies — see highlighted rows.
        @endif
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr><th>Batch</th><th>Item</th><th class="text-end">Receipts</th><th class="text-end">Transfers in</th><th class="text-end">Issues</th><th class="text-end">Transfers out</th><th class="text-end">Ledger balance</th><th>Check</th></tr>
                </thead>
                <tbody>
                @forelse($rows as $r)
                    <tr class="{{ $r['ok'] ? '' : 'table-danger' }}">
                        <td><a href="{{ route('stock.batch', $r['batch']) }}">{{ $r['batch']->batch_number }}</a></td>
                        <td>{{ $r['batch']->item->name }}</td>
                        <td class="text-end">{{ number_format($r['receipts'], 2) }}</td>
                        <td class="text-end">{{ number_format($r['transfers_in'], 2) }}</td>
                        <td class="text-end">{{ number_format($r['issues'], 2) }}</td>
                        <td class="text-end">{{ number_format($r['transfers_out'], 2) }}</td>
                        <td class="text-end fw-semibold">{{ number_format($r['balance'], 2) }} {{ $r['batch']->item->unit }}</td>
                        <td>
                            @if($r['ok'])
                                <span class="badge bg-success">OK</span>
                            @else
                                <span class="badge bg-danger">Mismatch</span> <span class="small">{{ implode(' ', $r['problems']) }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-muted">No batches.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>

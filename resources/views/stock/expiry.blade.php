<x-app-layout title="Expiry report">
    <x-slot name="header">
        <div>
            <h1>Expiry report</h1>
            <div class="text-muted small">Batches with stock that are expired or expire within 60 days.</div>
        </div>
    </x-slot>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Expiry</th><th>Status</th><th>Item</th><th>Batch</th><th>Warehouse</th><th class="text-end">Balance</th></tr></thead>
                <tbody>
                @forelse($rows as $row)
                    @php($b = $row['batch'])
                    <tr class="{{ $row['expired'] ? 'table-danger' : ($b->expiresWithin(30) ? 'table-warning' : '') }}">
                        <td>{{ $b->expiry_date->format('d M Y') }}</td>
                        <td>
                            @if($row['expired'])
                                <span class="badge bg-danger">Expired {{ $b->expiry_date->diffForHumans() }}</span>
                            @else
                                <span class="badge {{ $b->expiresWithin(30) ? 'bg-warning text-dark' : 'bg-info text-dark' }}">{{ $b->expiry_date->diffForHumans() }}</span>
                            @endif
                        </td>
                        <td>{{ $b->item->name }}</td>
                        <td><a href="{{ route('stock.batch', $b) }}">{{ $b->batch_number }}</a></td>
                        <td>{{ $b->warehouse->code }}</td>
                        <td class="text-end fw-semibold">{{ number_format($row['balance'], 2) }} {{ $b->item->unit }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">No batches expiring within 60 days.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>

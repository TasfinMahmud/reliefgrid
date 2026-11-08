{{-- Feature 5 dashboard panel --}}
<div class="card shadow-sm h-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Stock expiring within 30 days</span>
        <a href="{{ route('stock.expiry') }}" class="small">Expiry report</a>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Item</th><th>Batch</th><th>Warehouse</th><th>Expiry</th><th class="text-end">Balance</th></tr></thead>
            <tbody>
            @forelse($expiry->take(6) as $row)
                <tr class="{{ $row['expired'] ? 'table-danger' : '' }}">
                    <td>{{ $row['batch']->item->name }}</td>
                    <td><a href="{{ route('stock.batch', $row['batch']) }}">{{ $row['batch']->batch_number }}</a></td>
                    <td>{{ $row['batch']->warehouse->code }}</td>
                    <td>{{ $row['batch']->expiry_date->format('d M Y') }} @if($row['expired'])<span class="badge bg-danger">expired</span>@endif</td>
                    <td class="text-end">{{ number_format($row['balance'], 2) }} {{ $row['batch']->item->unit }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">Nothing expiring soon.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

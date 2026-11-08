<x-app-layout :title="'Batch '.$batch->batch_number">
    <x-slot name="header">
        <div>
            <h1>Batch {{ $batch->batch_number }} · {{ $batch->item->name }}</h1>
            <div class="text-muted small">{{ $batch->warehouse->display_name }}</div>
        </div>
        <a href="{{ route('warehouses.show', $batch->warehouse) }}" class="btn btn-outline-secondary btn-sm">Back to warehouse</a>
    </x-slot>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">Current balance</dt><dd class="col-7 fs-5 fw-bold">{{ number_format($balance, 2) }} {{ $batch->item->unit }}</dd>
                        <dt class="col-5">Received quantity</dt><dd class="col-7">{{ number_format((float) $batch->received_quantity, 2) }} {{ $batch->item->unit }}</dd>
                        <dt class="col-5">Received on</dt><dd class="col-7">{{ $batch->received_at->format('d M Y') }} by {{ $batch->receivedBy?->name ?? '—' }}</dd>
                        <dt class="col-5">Expiry</dt>
                        <dd class="col-7">
                            {{ $batch->expiry_date?->format('d M Y') ?? 'n/a' }}
                            @if($batch->isExpired())<span class="badge bg-danger">expired</span>@elseif($batch->expiresWithin(30))<span class="badge bg-warning text-dark">expires soon</span>@endif
                        </dd>
                        <dt class="col-5">Supplier</dt><dd class="col-7">{{ $batch->supplier ?? '—' }}</dd>
                        @if($batch->originBatch)
                            <dt class="col-5">Origin</dt><dd class="col-7">Transferred from {{ $batch->originBatch->warehouse->code }} (batch #{{ $batch->originBatch->id }})</dd>
                        @endif
                        <dt class="col-5">Notes</dt><dd class="col-7">{{ $batch->notes ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Ledger entries for this batch</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>When</th><th>Type</th><th class="text-end">Quantity</th><th class="text-end">Running balance</th><th>Reference</th><th>Note</th><th>By</th></tr></thead>
                        <tbody>
                        @php($running = 0)
                        @foreach($batch->movements as $m)
                            @php($running += (float) $m->quantity)
                            <tr>
                                <td class="small text-nowrap">{{ $m->created_at->format('d M Y H:i') }}</td>
                                <td><x-badge :status="$m->type" /></td>
                                <td class="text-end {{ (float) $m->quantity < 0 ? 'text-danger' : 'text-success' }}">{{ (float) $m->quantity > 0 ? '+' : '' }}{{ number_format((float) $m->quantity, 2) }}</td>
                                <td class="text-end fw-semibold">{{ number_format($running, 2) }}</td>
                                <td class="small">{{ $m->referenceLabel() ?? '—' }}</td>
                                <td class="small text-muted">{{ $m->note }}</td>
                                <td class="small">{{ $m->createdBy?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

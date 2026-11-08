<x-app-layout :title="'Transfer #'.$transfer->id">
    <x-slot name="header">
        <div>
            <h1>Transfer #{{ $transfer->id }} <x-badge :status="$transfer->status" class="align-middle fs-6" /></h1>
            <div class="text-muted small">{{ $transfer->fromWarehouse->display_name }} → {{ $transfer->toWarehouse->display_name }}</div>
        </div>
        <div class="d-flex gap-2">
            @can('review', $transfer)
                <button type="button" class="btn btn-rg btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal">Approve</button>
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
            @endcan
            @can('cancel', $transfer)
                <x-confirm-form :action="route('transfers.cancel', $transfer)" confirm="Cancel this transfer request?">
                    <button class="btn btn-outline-secondary btn-sm">Cancel request</button>
                </x-confirm-form>
            @endcan
            <a href="{{ route('transfers.index') }}" class="btn btn-outline-secondary btn-sm">All transfers</a>
        </div>
    </x-slot>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-4">Item</dt><dd class="col-8">{{ $transfer->item->name }}</dd>
                        <dt class="col-4">Source batch</dt><dd class="col-8"><a href="{{ route('stock.batch', $transfer->batch) }}">{{ $transfer->batch->batch_number }}</a> (currently {{ number_format($available, 2) }} {{ $transfer->item->unit }} available)</dd>
                        <dt class="col-4">Quantity</dt><dd class="col-8 fw-bold">{{ number_format((float) $transfer->quantity, 2) }} {{ $transfer->item->unit }}</dd>
                        <dt class="col-4">Reason</dt><dd class="col-8">{{ $transfer->reason ?? '—' }}</dd>
                        <dt class="col-4">Requested</dt><dd class="col-8">{{ $transfer->requested_at->format('d M Y H:i') }} by {{ $transfer->requestedBy?->name ?? '—' }}</dd>
                        @if($transfer->reviewed_at)
                            <dt class="col-4">Reviewed</dt><dd class="col-8">{{ $transfer->reviewed_at->format('d M Y H:i') }} by {{ $transfer->reviewedBy?->name ?? '—' }}<br><em>{{ $transfer->review_note }}</em></dd>
                        @endif
                        @if($transfer->destinationBatch)
                            <dt class="col-4">Destination batch</dt><dd class="col-8"><a href="{{ route('stock.batch', $transfer->destinationBatch) }}">{{ $transfer->destinationBatch->batch_number }}</a> in {{ $transfer->toWarehouse->code }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Ledger entries</div>
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Warehouse</th><th>Type</th><th class="text-end">Quantity</th><th>When</th></tr></thead>
                    <tbody>
                    @forelse($transfer->movements as $m)
                        <tr>
                            <td>{{ $m->warehouse->code }}</td>
                            <td><x-badge :status="$m->type" /></td>
                            <td class="text-end {{ (float) $m->quantity < 0 ? 'text-danger' : 'text-success' }}">{{ (float) $m->quantity > 0 ? '+' : '' }}{{ number_format((float) $m->quantity, 2) }}</td>
                            <td class="small">{{ $m->created_at->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No ledger entries yet (written on approval).</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @can('review', $transfer)
        <x-modal-form id="approveModal" title="Approve transfer #{{ $transfer->id }}" :action="route('transfers.approve', $transfer)" submit="Approve &amp; execute">
            <p>This will write a <strong>transfer out</strong> of {{ number_format((float) $transfer->quantity, 2) }} {{ $transfer->item->unit }} at {{ $transfer->fromWarehouse->code }} and a matching <strong>transfer in</strong> at {{ $transfer->toWarehouse->code }}.</p>
            <label for="approve_note" class="form-label">Note (optional)</label>
            <input id="approve_note" name="review_note" class="form-control" maxlength="255">
        </x-modal-form>
        <x-modal-form id="rejectModal" title="Reject transfer #{{ $transfer->id }}" :action="route('transfers.reject', $transfer)" submit="Reject" submit-class="btn-danger">
            <label for="reject_note" class="form-label required">Reason</label>
            <input id="reject_note" name="review_note" class="form-control" required maxlength="255">
        </x-modal-form>
    @endcan
</x-app-layout>

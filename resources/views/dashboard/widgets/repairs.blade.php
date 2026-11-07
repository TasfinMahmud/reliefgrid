{{-- Feature 12 – dashboard panel: latest open repair requests --}}
<div class="card shadow-sm h-100">
    <div class="card-header fw-semibold d-flex justify-content-between">
        <span>Open shelter repairs</span>
        <a href="{{ route('repairs.index') }}" class="small">View all</a>
    </div>
    <ul class="list-group list-group-flush">
        @forelse($repairs as $repair)
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>
                    <a href="{{ route('repairs.show', $repair) }}" class="fw-semibold">{{ $repair->code }}</a>
                    <span class="text-muted small">{{ $repair->household?->head_name }} · {{ $repair->block?->code }} · {{ $repair->damageTypeLabel() }}</span>
                </span>
                <span class="badge {{ $repair->status->badgeClass() }}">{{ $repair->status->label() }}</span>
            </li>
        @empty
            <li class="list-group-item text-muted">No open repair requests.</li>
        @endforelse
    </ul>
</div>

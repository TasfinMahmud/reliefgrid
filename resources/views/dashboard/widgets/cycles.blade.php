{{-- Feature 2 dashboard panel --}}
<div class="card shadow-sm h-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Assistance cycles</span>
        <a href="{{ route('cycles.index') }}" class="small">All cycles</a>
    </div>
    <ul class="list-group list-group-flush">
        @forelse($cycles as $cycle)
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <a href="{{ route('cycles.show', $cycle) }}" class="fw-semibold text-decoration-none">{{ $cycle->name }}</a>
                    <div class="small text-muted">{{ $cycle->starts_on->format('d M') }} – {{ $cycle->ends_on->format('d M Y') }} · {{ $cycle->entitlements_count }} entitlements</div>
                </div>
                <x-badge :status="$cycle->status" />
            </li>
        @empty
            <li class="list-group-item text-muted">No cycles yet.</li>
        @endforelse
    </ul>
</div>

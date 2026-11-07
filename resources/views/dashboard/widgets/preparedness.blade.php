{{-- Feature 14 dashboard panel --}}
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Active hazard alerts</span>
                <a href="{{ route('alerts.index') }}" class="small">All alerts</a>
            </div>
            <ul class="list-group list-group-flush">
                @forelse($alerts as $alert)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <x-badge :status="$alert->severity" class="me-1" />
                                <a href="{{ route('alerts.show', $alert) }}" class="fw-semibold text-decoration-none">{{ $alert->title }}</a>
                                <div class="small text-muted">{{ $alert->hazard_type->label() }} · Blocks: {{ $alert->blocks->pluck('code')->join(', ') }}</div>
                            </div>
                            <span class="small text-muted text-nowrap">{{ $alert->issued_at->diffForHumans() }}</span>
                        </div>
                    </li>
                @empty
                    <li class="list-group-item text-muted">No active alerts.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Open incidents</span>
                <a href="{{ route('incidents.index') }}" class="small">All incidents</a>
            </div>
            <ul class="list-group list-group-flush">
                @forelse($incidents as $incident)
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div>
                            <a href="{{ route('incidents.show', $incident) }}" class="fw-semibold text-decoration-none">{{ $incident->code }}</a>
                            {{ $incident->title }}
                            <div class="small text-muted">{{ $incident->block->code }} · {{ $incident->hazard_type->label() }} · {{ $incident->occurred_at->format('d M H:i') }}</div>
                        </div>
                        <x-badge :status="$incident->status" />
                    </li>
                @empty
                    <li class="list-group-item text-muted">No open incidents.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Evacuation site capacity</span>
                <a href="{{ route('sites.index') }}" class="small">All sites</a>
            </div>
            <ul class="list-group list-group-flush">
                @forelse($sites as $site)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('sites.show', $site) }}" class="text-decoration-none fw-semibold">{{ $site->code }} · {{ $site->name }}</a>
                            <span class="small">{{ $site->current_occupancy }} / {{ $site->capacity }} <span class="badge {{ $site->statusBadgeClass() }}">{{ $site->statusLabel() }}</span></span>
                        </div>
                        <div class="progress mt-1" style="height: 6px;" role="progressbar" aria-valuenow="{{ $site->occupancyRate() }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar {{ $site->occupancyRate() >= 80 ? 'bg-danger' : 'bg-success' }}" style="width: {{ min($site->occupancyRate(), 100) }}%"></div>
                        </div>
                    </li>
                @empty
                    <li class="list-group-item text-muted">No open evacuation sites.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

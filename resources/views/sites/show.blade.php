<x-app-layout :title="$site->code">
    <x-slot name="header">
        <div>
            <h1>{{ $site->code }} · {{ $site->name }} <span class="badge {{ $site->statusBadgeClass() }} fs-6 align-middle">{{ $site->statusLabel() }}</span></h1>
            <div class="text-muted small">Block {{ $site->block->display_name }}</div>
        </div>
        <div class="d-flex gap-2">
            @can('updateOccupancy', $site)
                @if($site->is_open)
                    <button type="button" class="btn btn-rg btn-sm" data-bs-toggle="modal" data-bs-target="#occupancyModal">Record occupancy</button>
                @endif
            @endcan
            @can('update', $site)
                <a href="{{ route('sites.edit', $site) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                <x-confirm-form :action="route('sites.toggle-open', $site)" :confirm="$site->is_open ? 'Close this site?' : 'Reopen this site?'">
                    <button class="btn btn-outline-{{ $site->is_open ? 'danger' : 'success' }} btn-sm">{{ $site->is_open ? 'Close site' : 'Open site' }}</button>
                </x-confirm-form>
            @endcan
        </div>
    </x-slot>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-end">
                        <div><div class="stat-label">Occupancy</div><div class="stat-value">{{ number_format($site->current_occupancy) }} <span class="fs-6 text-muted">/ {{ number_format($site->capacity) }}</span></div></div>
                        <div class="text-end"><div class="stat-label">Available</div><div class="fs-4 fw-semibold">{{ number_format($site->availableCapacity()) }}</div></div>
                    </div>
                    <div class="progress mt-2" style="height: 10px;" role="progressbar" aria-valuenow="{{ $site->occupancyRate() }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar {{ $site->occupancyRate() >= 100 ? 'bg-danger' : ($site->occupancyRate() >= 80 ? 'bg-warning' : 'bg-success') }}" style="width: {{ min($site->occupancyRate(), 100) }}%"></div>
                    </div>
                    <div class="small text-muted mt-1">{{ $site->occupancyRate() }}% full</div>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-body small">
                    <dl class="row mb-0">
                        <dt class="col-4">Facilities</dt><dd class="col-8">{{ $site->facilities ?? '—' }}</dd>
                        <dt class="col-4">Contact</dt><dd class="col-8">{{ $site->contact_person ?? '—' }}{{ $site->contact_phone ? ' · '.$site->contact_phone : '' }}</dd>
                        <dt class="col-4">Coordinates</dt><dd class="col-8">{{ $site->latitude && $site->longitude ? $site->latitude.', '.$site->longitude : '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Occupancy log</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>When</th><th class="text-end">From</th><th class="text-end">To</th><th>Note</th><th>By</th></tr></thead>
                        <tbody>
                        @forelse($site->occupancyLogs as $log)
                            <tr>
                                <td class="small text-nowrap">{{ $log->created_at->format('d M Y H:i') }}</td>
                                <td class="text-end">{{ $log->previous_occupancy }}</td>
                                <td class="text-end fw-semibold">{{ $log->occupancy }}</td>
                                <td class="small">{{ $log->note }}</td>
                                <td class="small">{{ $log->recordedBy?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No readings yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @can('updateOccupancy', $site)
        <x-modal-form id="occupancyModal" title="Record occupancy · {{ $site->code }}" :action="route('sites.occupancy', $site)" submit="Save reading">
            <label for="occupancy" class="form-label required">People currently on site (max {{ $site->capacity }})</label>
            <input id="occupancy" name="occupancy" type="number" min="0" max="{{ $site->capacity }}" class="form-control" value="{{ old('occupancy', $site->current_occupancy) }}" required>
            <label for="occ_note" class="form-label mt-3">Note</label>
            <input id="occ_note" name="note" class="form-control" maxlength="255" placeholder="e.g. 18:00 headcount">
        </x-modal-form>
    @endcan
</x-app-layout>

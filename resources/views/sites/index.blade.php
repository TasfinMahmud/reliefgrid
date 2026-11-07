<x-app-layout title="Evacuation sites">
    <x-slot name="header">
        <div>
            <h1>Evacuation sites</h1>
            <div class="text-muted small">Open capacity: {{ number_format($totals['occupancy']) }} / {{ number_format($totals['capacity']) }} occupied</div>
        </div>
        @can('create', \App\Models\EvacuationSite::class)
            <a href="{{ route('sites.create') }}" class="btn btn-rg btn-sm">New site</a>
        @endcan
    </x-slot>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light"><tr><th>Code</th><th>Name</th><th>Block</th><th class="text-end">Capacity</th><th class="text-end">Occupancy</th><th style="width: 18%">Fill</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($sites as $site)
                    <tr>
                        <td><a href="{{ route('sites.show', $site) }}" class="fw-semibold">{{ $site->code }}</a></td>
                        <td>{{ $site->name }}</td>
                        <td>{{ $site->block->code }}</td>
                        <td class="text-end">{{ number_format($site->capacity) }}</td>
                        <td class="text-end">{{ number_format($site->current_occupancy) }} <span class="text-muted small">({{ $site->occupancyRate() }}%)</span></td>
                        <td>
                            <div class="progress" style="height: 8px;" role="progressbar" aria-valuenow="{{ $site->occupancyRate() }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar {{ $site->occupancyRate() >= 100 ? 'bg-danger' : ($site->occupancyRate() >= 80 ? 'bg-warning' : 'bg-success') }}" style="width: {{ min($site->occupancyRate(), 100) }}%"></div>
                            </div>
                        </td>
                        <td><span class="badge {{ $site->statusBadgeClass() }}">{{ $site->statusLabel() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted">No evacuation sites.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>

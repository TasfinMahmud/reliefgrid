<x-app-layout title="Shelter repairs">
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Shelter repair requests</h1>
            @can('create', \App\Models\ShelterRepairRequest::class)
                <a href="{{ route('repairs.create') }}" class="btn btn-primary btn-sm">New repair request</a>
            @endcan
        </div>
    </x-slot>

    @if(session('success'))
        <div class="alert alert-success" data-autodismiss>{{ session('success') }}</div>
    @endif

    <form method="GET" class="card shadow-sm mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-4">
                <label for="q" class="form-label">Search</label>
                <input type="text" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="SR code, household code or head name">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="block_id" class="form-label">Block</label>
                <select id="block_id" name="block_id" class="form-select">
                    <option value="">All blocks</option>
                    @foreach($blocks as $block)
                        <option value="{{ $block->id }}" @selected((string) ($filters['block_id'] ?? '') === (string) $block->id)>{{ $block->code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-outline-primary flex-fill">Filter</button>
                <a href="{{ route('repairs.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Code</th><th>Household</th><th>Block</th><th>Damage</th><th>Severity</th><th>Status</th><th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($repairs as $repair)
                        <tr>
                            <td><a href="{{ route('repairs.show', $repair) }}" class="fw-semibold">{{ $repair->code }}</a></td>
                            <td>{{ $repair->household?->code }} · {{ $repair->household?->head_name }}</td>
                            <td>{{ $repair->block?->code }}</td>
                            <td>{{ $repair->damageTypeLabel() }}</td>
                            <td>{{ $repair->severityLabel() }}</td>
                            <td><span class="badge {{ $repair->status->badgeClass() }}">{{ $repair->status->label() }}</span></td>
                            <td class="text-muted small">{{ $repair->updated_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No repair requests match the filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($repairs->hasPages())
            <div class="card-footer">{{ $repairs->links() }}</div>
        @endif
    </div>
</x-app-layout>

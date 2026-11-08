{{-- Feature 1 dashboard panel --}}
<div class="card shadow-sm h-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Recently registered households</span>
        <a href="{{ route('households.index') }}" class="small">All households</a>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light"><tr><th>Code</th><th>Head of household</th><th>Block</th><th class="text-end">Size</th><th>Status</th><th>Registered</th></tr></thead>
            <tbody>
            @forelse($recentHouseholds as $household)
                <tr>
                    <td><a href="{{ route('households.show', $household) }}">{{ $household->code }}</a></td>
                    <td>{{ $household->head_name }}</td>
                    <td>{{ $household->block->code }}</td>
                    <td class="text-end">{{ $household->household_size }}</td>
                    <td><x-badge :status="$household->status" /></td>
                    <td class="small text-muted">{{ $household->created_at->format('d M Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">No households registered yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

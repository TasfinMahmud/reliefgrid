<x-app-layout title="Dashboard">
    <x-slot name="header">
        <div>
            <h1>Dashboard</h1>
            <div class="text-muted small">Welcome back, {{ auth()->user()->name }} · {{ auth()->user()->roleLabel() }}</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @can('create', \App\Models\Household::class)
                <a href="{{ route('households.create') }}" class="btn btn-rg btn-sm">Register household</a>
            @endcan
        </div>
    </x-slot>

    <div class="row g-3 mb-4">
        @php($cards = [
            ['Active households', $stats['households_active'], route('households.index'), $stats['households_total'].' registered'],
            ['Active tokens', $stats['tokens_active'], route('households.index'), 'QR cards in circulation'],
            ['Active users', $stats['users_active'], route('dashboard'), 'staff accounts'],
            ['Blocks', $stats['blocks'], route('blocks.index'), 'settlement blocks'],
            ['Items', $stats['items'], route('items.index'), 'in catalogue'],
        ])
        @foreach($cards as [$label, $value, $url, $sub])
            <div class="col-6 col-md-4 col-xl-2">
                <a href="{{ $url }}" class="text-decoration-none text-dark">
                    <div class="card stat-card h-100">
                        <div class="card-body py-3">
                            <div class="stat-label">{{ $label }}</div>
                            <div class="stat-value">{{ $value }}</div>
                            <div class="small text-muted">{{ $sub }}</div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
    <div class="row g-4">
        <div class="col-lg-{{ isset($cycles) ? '7' : '12' }}">
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
        </div>
    </div>
</x-app-layout>

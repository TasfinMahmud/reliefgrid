<x-app-layout title="Hazard alerts">
    <x-slot name="header">
        <h1>Hazard alerts</h1>
        @can('create', \App\Models\HazardAlert::class)
            <a href="{{ route('alerts.create') }}" class="btn btn-rg btn-sm">Issue alert</a>
        @endcan
    </x-slot>

    <div class="card shadow-sm mb-4">
        <div class="card-header fw-semibold">Block alert board <span class="small text-muted fw-normal">(active alerts per block)</span></div>
        <div class="card-body">
            <div class="row g-2">
                @foreach($board as $block)
                    @php($top = $block->hazardAlerts->sortByDesc(fn ($a) => $a->severity->rank())->first())
                    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                        <div class="border rounded p-2 h-100 {{ $top ? ($top->severity->value === 'emergency' ? 'border-danger bg-danger-subtle' : ($top->severity->value === 'warning' ? 'border-warning bg-warning-subtle' : 'border-info bg-info-subtle')) : 'bg-light' }}">
                            <div class="fw-semibold">{{ $block->code }}</div>
                            <div class="small text-muted">{{ $block->name }}</div>
                            @if($top)
                                <div class="mt-1"><x-badge :status="$top->severity" /> <span class="small">{{ $top->hazard_type->label() }}</span></div>
                                @if($block->hazardAlerts->count() > 1)<div class="small text-muted">+{{ $block->hazardAlerts->count() - 1 }} more</div>@endif
                            @else
                                <div class="small text-success mt-1">No active alert</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Any status</option>
                @foreach(\App\Enums\AlertStatus::cases() as $s)
                    <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="block_id" class="form-select">
                <option value="">All blocks</option>
                @foreach($blocks as $b)
                    <option value="{{ $b->id }}" @selected(request('block_id') == $b->id)>{{ $b->display_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light"><tr><th>Issued</th><th>Severity</th><th>Hazard</th><th>Title</th><th>Blocks</th><th>Expires</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($alerts as $a)
                    <tr>
                        <td class="small text-nowrap">{{ $a->issued_at->format('d M Y H:i') }}<br><span class="text-muted">{{ $a->issuedBy?->name }}</span></td>
                        <td><x-badge :status="$a->severity" /></td>
                        <td>{{ $a->hazard_type->label() }}</td>
                        <td><a href="{{ route('alerts.show', $a) }}" class="fw-semibold">{{ $a->title }}</a></td>
                        <td class="small">{{ $a->blocks->pluck('code')->join(', ') }}</td>
                        <td class="small">{{ $a->expires_at?->format('d M Y H:i') ?? 'until cancelled' }}</td>
                        <td><x-badge :status="$a->effectiveStatus()" /></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted">No alerts.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $alerts->links() }}</div>
</x-app-layout>

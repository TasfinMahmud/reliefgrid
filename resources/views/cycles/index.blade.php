<x-app-layout title="Assistance cycles">
    <x-slot name="header">
        <h1>Assistance cycles</h1>
        @can('create', \App\Models\AssistanceCycle::class)
            <a href="{{ route('cycles.create') }}" class="btn btn-rg btn-sm">New cycle</a>
        @endcan
    </x-slot>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Any status</option>
                @foreach($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>Cycle</th><th>Period</th><th>Status</th><th class="text-end">Rules</th><th class="text-end">Entitlements</th><th class="text-end">Households</th><th>Activated</th></tr></thead>
                <tbody>
                @forelse($cycles as $cycle)
                    <tr>
                        <td><a href="{{ route('cycles.show', $cycle) }}" class="fw-semibold">{{ $cycle->name }}</a></td>
                        <td class="small">{{ $cycle->starts_on->format('d M Y') }} – {{ $cycle->ends_on->format('d M Y') }}</td>
                        <td><x-badge :status="$cycle->status" /></td>
                        <td class="text-end">{{ $cycle->rules_count }}</td>
                        <td class="text-end">{{ $cycle->entitlements_count }}</td>
                        <td class="text-end">{{ $cycle->eligible_household_count ?? '—' }}</td>
                        <td class="small text-muted">{{ $cycle->activated_at?->format('d M Y H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted">No cycles yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $cycles->links() }}</div>
</x-app-layout>

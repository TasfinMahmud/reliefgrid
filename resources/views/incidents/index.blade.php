<x-app-layout title="Incidents">
    <x-slot name="header">
        <h1>Incident log</h1>
        @can('create', \App\Models\Incident::class)
            <a href="{{ route('incidents.create') }}" class="btn btn-rg btn-sm">Report incident</a>
        @endcan
    </x-slot>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Any status</option>
                @foreach($statuses as $s)
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
        <div class="col-md-3">
            <select name="hazard_type" class="form-select">
                <option value="">Any hazard</option>
                @foreach($hazardTypes as $v => $l)
                    <option value="{{ $v }}" @selected(request('hazard_type') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light"><tr><th>Code</th><th>Occurred</th><th>Block</th><th>Hazard</th><th>Title</th><th class="text-end">People affected</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($incidents as $i)
                    <tr>
                        <td><a href="{{ route('incidents.show', $i) }}" class="fw-semibold">{{ $i->code }}</a></td>
                        <td class="small text-nowrap">{{ $i->occurred_at->format('d M Y H:i') }}</td>
                        <td>{{ $i->block->code }}</td>
                        <td>{{ $i->hazard_type->label() }}</td>
                        <td>{{ $i->title }}</td>
                        <td class="text-end">{{ $i->people_affected ?? '—' }}</td>
                        <td><x-badge :status="$i->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted">No incidents.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $incidents->links() }}</div>
</x-app-layout>

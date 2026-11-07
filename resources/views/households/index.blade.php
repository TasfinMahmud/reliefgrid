<x-app-layout title="Households">
    <x-slot name="header">
        <h1>Households</h1>
        @can('create', \App\Models\Household::class)
            <a href="{{ route('households.create') }}" class="btn btn-rg btn-sm">Register household</a>
        @endcan
    </x-slot>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search code, head of household, phone, shelter">
        </div>
        <div class="col-md-3">
            <select name="block_id" class="form-select">
                <option value="">All blocks</option>
                @foreach($blocks as $block)
                    <option value="{{ $block->id }}" @selected(request('block_id') == $block->id)>{{ $block->display_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">Any status</option>
                @foreach($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100">Filter</button>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr><th>Code</th><th>Head of household</th><th>Block</th><th class="text-end">Size</th><th>Shelter</th><th>Status</th><th>Token</th><th>Registered</th></tr>
                </thead>
                <tbody>
                @forelse($households as $household)
                    <tr>
                        <td><a href="{{ route('households.show', $household) }}" class="fw-semibold">{{ $household->code }}</a></td>
                        <td>{{ $household->head_name }}</td>
                        <td>{{ $household->block->code }}</td>
                        <td class="text-end">{{ $household->household_size }}</td>
                        <td>{{ $household->shelter_number ?? '—' }}</td>
                        <td><x-badge :status="$household->status" /></td>
                        <td>
                            @if($household->activeToken)
                                <a href="{{ route('tokens.card', $household->activeToken) }}" class="badge bg-success text-decoration-none">…{{ $household->activeToken->shortUid() }}</a>
                            @else
                                <span class="badge bg-secondary">none</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $household->created_at->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-muted">No households match.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $households->links() }}</div>
</x-app-layout>

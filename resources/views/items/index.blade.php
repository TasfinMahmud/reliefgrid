<x-app-layout title="Items">
    <x-slot name="header">
        <h1>Item catalogue</h1>
        @can('create', \App\Models\Item::class)
            <a href="{{ route('items.create') }}" class="btn btn-rg btn-sm">New item</a>
        @endcan
    </x-slot>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>Code</th><th>Name</th><th>Category</th><th>Unit</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td class="fw-semibold">{{ $item->code }}</td>
                        <td>{{ $item->name }} @if($item->description)<div class="small text-muted">{{ $item->description }}</div>@endif</td>
                        <td>{{ $item->categoryLabel() }}</td>
                        <td>{{ $item->unit }}</td>
                        <td><span class="badge {{ $item->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-end">
                            @can('update', $item)
                                <a href="{{ route('items.edit', $item) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">No items yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>

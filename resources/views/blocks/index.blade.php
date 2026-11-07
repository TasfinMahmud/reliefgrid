<x-app-layout title="Blocks">
    <x-slot name="header">
        <h1>Settlement blocks</h1>
        @can('create', \App\Models\Block::class)
            <a href="{{ route('blocks.create') }}" class="btn btn-rg btn-sm">New block</a>
        @endcan
    </x-slot>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Code</th><th>Name</th><th>Camp</th><th>Sector</th><th class="text-end">Est. population</th><th></th></tr>
                </thead>
                <tbody>
                @forelse($blocks as $block)
                    <tr>
                        <td class="fw-semibold">{{ $block->code }}</td>
                        <td>{{ $block->name }}</td>
                        <td>{{ $block->camp ?? '—' }}</td>
                        <td>{{ $block->sector ?? '—' }}</td>
                        <td class="text-end">{{ $block->estimated_population ? number_format($block->estimated_population) : '—' }}</td>
                        <td class="text-end">
                            @can('update', $block)
                                <a href="{{ route('blocks.edit', $block) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">No blocks yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>

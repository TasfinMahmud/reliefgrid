<x-app-layout :title="'Preview · '.$cycle->name">
    <x-slot name="header">
        <div>
            <h1>{{ $cycle->isDraft() ? 'Entitlement preview' : 'Entitlement list' }} <x-badge :status="$cycle->status" class="align-middle fs-6" /></h1>
            <div class="text-muted small">{{ $cycle->name }} · {{ $rows->count() }} entitlement line(s)</div>
        </div>
        <a href="{{ route('cycles.show', $cycle) }}" class="btn btn-outline-secondary btn-sm">Back to cycle</a>
    </x-slot>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Totals per item</div>
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Item</th><th class="text-end">HH</th><th class="text-end">Qty</th></tr></thead>
                    <tbody>
                    @foreach($totals as $t)
                        <tr><td>{{ $t['item']->name }}</td><td class="text-end">{{ $t['households'] }}</td><td class="text-end">{{ number_format($t['quantity'], 2) }} {{ $t['item']->unit }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="table-responsive" style="max-height: 70vh;">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light sticky-top"><tr><th>Household</th><th>Block</th><th class="text-end">Size</th><th>Item</th><th class="text-end">Quantity</th><th>Rule</th></tr></thead>
                        <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td><a href="{{ route('households.show', $row['household']) }}">{{ $row['household']->code }}</a> <span class="text-muted small">{{ $row['household']->head_name }}</span></td>
                                <td>{{ $row['household']->block->code }}</td>
                                <td class="text-end">{{ $row['household']->household_size }}</td>
                                <td>{{ $row['item']->name }}</td>
                                <td class="text-end">{{ number_format($row['quantity'], 2) }} {{ $row['item']->unit }}</td>
                                <td class="small text-muted">{{ $row['rule']?->sizeBandLabel() ?? 'materialised' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">No entitlements.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

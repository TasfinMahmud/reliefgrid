<x-app-layout :title="$cycle->name">
    <x-slot name="header">
        <div>
            <h1>{{ $cycle->name }} <x-badge :status="$cycle->status" class="align-middle fs-6" /></h1>
            <div class="text-muted small">
                {{ $cycle->starts_on->format('d M Y') }} – {{ $cycle->ends_on->format('d M Y') }} ·
                Scope: {{ $cycle->blocks->isEmpty() ? 'all blocks' : $cycle->blocks->pluck('code')->join(', ') }} ·
                {{ $eligibleCount }} eligible household(s)
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('cycles.preview', $cycle) }}" class="btn btn-outline-secondary btn-sm">{{ $cycle->isDraft() ? 'Full preview' : 'Entitlement list' }}</a>
            @can('update', $cycle)
                <a href="{{ route('cycles.edit', $cycle) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
            @endcan
            @can('activate', $cycle)
                <button type="button" class="btn btn-rg btn-sm" data-bs-toggle="modal" data-bs-target="#activateModal">Approve &amp; activate</button>
            @endcan
            @can('close', $cycle)
                <x-confirm-form :action="route('cycles.close', $cycle)" confirm="Close this cycle? No further changes will be possible.">
                    <button class="btn btn-outline-dark btn-sm">Close cycle</button>
                </x-confirm-form>
            @endcan
            @can('delete', $cycle)
                <x-confirm-form :action="route('cycles.destroy', $cycle)" method="DELETE" confirm="Delete this draft cycle and its rules?">
                    <button class="btn btn-outline-danger btn-sm">Delete draft</button>
                </x-confirm-form>
            @endcan
        </div>
    </x-slot>

    @if($cycle->description)
        <p class="text-muted">{{ $cycle->description }}</p>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Entitlement rules @if($cycle->rulesLocked())<span class="badge bg-dark ms-1">locked</span>@endif</span>
                    @can('manageRules', $cycle)
                        <button type="button" class="btn btn-rg btn-sm" data-bs-toggle="modal" data-bs-target="#ruleModal">Add rule</button>
                    @endcan
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Item</th><th>Household size</th><th>Quantity formula</th><th>Notes</th>@can('manageRules', $cycle)<th></th>@endcan</tr></thead>
                        <tbody>
                        @forelse($cycle->rules as $rule)
                            <tr>
                                <td>{{ $rule->item->name }} <span class="text-muted">({{ $rule->item->unit }})</span></td>
                                <td>{{ $rule->sizeBandLabel() }}</td>
                                <td>{{ $rule->formulaLabel() }} {{ $rule->item->unit }}</td>
                                <td class="small text-muted">{{ $rule->notes }}</td>
                                @can('manageRules', $cycle)
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editRule{{ $rule->id }}">Edit</button>
                                        <x-confirm-form :action="route('cycles.rules.destroy', [$cycle, $rule])" method="DELETE" confirm="Remove this rule?">
                                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                                        </x-confirm-form>
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No rules yet. Add one rule per item and household-size band.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer small text-muted">
                    Quantity = base + per-member × household size (capped at max). Bands for the same item must not overlap.
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Approval trail</div>
                <div class="card-body small">
                    <div>Created {{ $cycle->created_at->format('d M Y H:i') }} by {{ $cycle->createdBy?->name ?? '—' }}</div>
                    @if($cycle->activated_at)
                        <div class="text-success">Activated {{ $cycle->activated_at->format('d M Y H:i') }} by {{ $cycle->activatedBy?->name ?? '—' }} — rules locked, {{ $cycle->entitlements()->count() }} entitlements generated for {{ $cycle->eligible_household_count }} households.</div>
                    @endif
                    @if($cycle->closed_at)
                        <div class="text-dark">Closed {{ $cycle->closed_at->format('d M Y H:i') }} by {{ $cycle->closedBy?->name ?? '—' }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">{{ $cycle->isDraft() ? 'Preview totals (live)' : 'Generated entitlements' }}</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Item</th><th class="text-end">Households</th><th class="text-end">Total quantity</th></tr></thead>
                        <tbody>
                        @if($cycle->isDraft())
                            @forelse($preview['totals'] as $t)
                                <tr><td>{{ $t['item']->name }}</td><td class="text-end">{{ $t['households'] }}</td><td class="text-end">{{ number_format($t['quantity'], 2) }} {{ $t['item']->unit }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="text-muted">Nothing to preview yet.</td></tr>
                            @endforelse
                        @else
                            @forelse($totals as $t)
                                <tr><td>{{ $t->item->name }}</td><td class="text-end">{{ $t->households }}</td><td class="text-end">{{ number_format((float) $t->quantity, 2) }} {{ $t->item->unit }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="text-muted">No entitlements.</td></tr>
                            @endforelse
                        @endif
                        </tbody>
                    </table>
                </div>
                @if($cycle->isDraft())
                    <div class="card-footer small text-muted">
                        {{ $preview['household_count'] }} eligible household(s);
                        {{ $preview['households_without_entitlement'] }} would receive nothing under the current rules.
                    </div>
                @endif
            </div>
        </div>
    </div>

    @can('manageRules', $cycle)
        <x-modal-form id="ruleModal" title="Add entitlement rule" :action="route('cycles.rules.store', $cycle)" submit="Add rule">
            @include('cycles._rule_fields', ['rule' => null, 'prefix' => 'new'])
        </x-modal-form>
        @foreach($cycle->rules as $rule)
            <x-modal-form id="editRule{{ $rule->id }}" title="Edit rule" :action="route('cycles.rules.update', [$cycle, $rule])" method="PUT" submit="Save rule">
                @include('cycles._rule_fields', ['rule' => $rule, 'prefix' => 'r'.$rule->id])
            </x-modal-form>
        @endforeach
    @endcan

    @can('activate', $cycle)
        <x-modal-form id="activateModal" title="Approve and activate cycle" :action="route('cycles.activate', $cycle)" submit="Activate cycle">
            <p>Activating will <strong>lock the entitlement rules</strong> and generate
                <strong>{{ $preview['rows']->count() }}</strong> entitlement record(s) for
                <strong>{{ $preview['household_count'] }}</strong> household(s).</p>
            <ul class="small">
                @foreach($preview['totals'] as $t)
                    <li>{{ $t['item']->name }}: {{ number_format($t['quantity'], 2) }} {{ $t['item']->unit }} across {{ $t['households'] }} households</li>
                @endforeach
            </ul>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="confirm" id="confirm_activate" value="1" required>
                <label class="form-check-label" for="confirm_activate">I have reviewed the preview and approve this cycle.</label>
            </div>
        </x-modal-form>
    @endcan

    @if($errors->hasAny(['item_id', 'min_household_size', 'max_household_size', 'base_quantity', 'per_member_quantity', 'max_quantity']))
        <script>document.addEventListener('DOMContentLoaded', () => new bootstrap.Modal('#ruleModal').show());</script>
    @endif
</x-app-layout>

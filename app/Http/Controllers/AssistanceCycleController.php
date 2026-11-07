<?php

namespace App\Http\Controllers;

use App\Enums\CycleStatus;
use App\Http\Requests\Assistance\CycleRequest;
use App\Models\AssistanceCycle;
use App\Models\Block;
use App\Models\Item;
use App\Services\EntitlementPlanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Feature 2 – Assistance cycles.
 */
class AssistanceCycleController extends Controller
{
    public function __construct(private readonly EntitlementPlanner $planner) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AssistanceCycle::class);

        $cycles = AssistanceCycle::withCount(['rules', 'entitlements'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('starts_on')->paginate(15)->withQueryString();

        return view('cycles.index', ['cycles' => $cycles, 'statuses' => CycleStatus::cases()]);
    }

    public function create(): View
    {
        $this->authorize('create', AssistanceCycle::class);

        return view('cycles.form', ['cycle' => new AssistanceCycle, 'blocks' => Block::orderBy('code')->get(), 'selected' => []]);
    }

    public function store(CycleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $blockIds = $data['block_ids'] ?? [];
        unset($data['block_ids']);

        $cycle = AssistanceCycle::create($data + ['status' => CycleStatus::Draft, 'created_by' => $request->user()->id]);
        $cycle->blocks()->sync($blockIds);

        return redirect()->route('cycles.show', $cycle)->with('success', "Cycle \"{$cycle->name}\" created as draft. Add entitlement rules next.");
    }

    public function show(AssistanceCycle $cycle): View
    {
        $this->authorize('view', $cycle);

        $cycle->load(['rules.item', 'blocks', 'createdBy', 'activatedBy', 'closedBy']);
        $items = Item::active()->orderBy('name')->get();
        $preview = $cycle->isDraft() ? $this->planner->preview($cycle) : null;
        $totals = $cycle->isDraft() ? null : $this->planner->materialisedTotals($cycle);
        $eligibleCount = $cycle->isDraft() ? $cycle->eligibleHouseholdsQuery()->count() : $cycle->eligible_household_count;

        return view('cycles.show', compact('cycle', 'items', 'preview', 'totals', 'eligibleCount'));
    }

    public function edit(AssistanceCycle $cycle): View
    {
        $this->authorize('update', $cycle);

        return view('cycles.form', [
            'cycle' => $cycle,
            'blocks' => Block::orderBy('code')->get(),
            'selected' => $cycle->blocks()->pluck('blocks.id')->all(),
        ]);
    }

    public function update(CycleRequest $request, AssistanceCycle $cycle): RedirectResponse
    {
        $data = $request->validated();
        $blockIds = $data['block_ids'] ?? [];
        unset($data['block_ids']);

        $cycle->update($data);
        $cycle->blocks()->sync($blockIds);

        return redirect()->route('cycles.show', $cycle)->with('success', 'Cycle updated.');
    }

    /** Full entitlement preview (per household). */
    public function preview(AssistanceCycle $cycle): View
    {
        $this->authorize('view', $cycle);

        if ($cycle->isDraft()) {
            $preview = $this->planner->preview($cycle);
            $rows = $preview['rows'];
            $totals = $preview['totals'];
        } else {
            $rows = $cycle->entitlements()->with(['household.block', 'item'])->get()
                ->sortBy(fn ($e) => $e->household->code)
                ->map(fn ($e) => ['household' => $e->household, 'item' => $e->item, 'quantity' => (float) $e->quantity, 'rule' => null]);
            $totals = $this->planner->materialisedTotals($cycle)
                ->map(fn ($t) => ['item' => $t->item, 'households' => $t->households, 'quantity' => (float) $t->quantity]);
        }

        return view('cycles.preview', compact('cycle', 'rows', 'totals'));
    }

    /** Coordinator approval: locks the rules and materialises entitlements. */
    public function activate(Request $request, AssistanceCycle $cycle): RedirectResponse
    {
        $this->authorize('activate', $cycle);
        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Please confirm that you have reviewed the preview.']);

        $this->planner->activate($cycle, $request->user());

        return redirect()->route('cycles.show', $cycle)
            ->with('success', "Cycle activated. {$cycle->entitlements()->count()} entitlements generated for {$cycle->eligible_household_count} households; rules are now locked.");
    }

    public function close(Request $request, AssistanceCycle $cycle): RedirectResponse
    {
        $this->authorize('close', $cycle);
        $this->planner->close($cycle, $request->user());

        return redirect()->route('cycles.show', $cycle)->with('success', 'Cycle closed.');
    }

    public function destroy(AssistanceCycle $cycle): RedirectResponse
    {
        $this->authorize('delete', $cycle);
        $cycle->delete();

        return redirect()->route('cycles.index')->with('success', 'Draft cycle deleted.');
    }
}

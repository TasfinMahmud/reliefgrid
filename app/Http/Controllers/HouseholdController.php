<?php

namespace App\Http\Controllers;

use App\Enums\HouseholdStatus;
use App\Http\Requests\Household\StoreHouseholdRequest;
use App\Http\Requests\Household\UpdateHouseholdRequest;
use App\Models\Block;
use App\Models\Household;
use App\Services\HouseholdService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Feature 1 – Household registration.
 */
class HouseholdController extends Controller
{
    public function __construct(private readonly HouseholdService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Household::class);

        $households = Household::query()
            ->with(['block', 'activeToken'])
            ->search($request->string('q'))
            ->when($request->filled('block_id'), fn ($q) => $q->where('block_id', $request->integer('block_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('households.index', [
            'households' => $households,
            'blocks' => Block::orderBy('code')->get(),
            'statuses' => HouseholdStatus::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Household::class);

        return view('households.create', [
            'blocks' => Block::orderBy('code')->get(),
            'consentVersion' => Household::CONSENT_VERSION,
        ]);
    }

    public function store(StoreHouseholdRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $issueToken = $request->boolean('issue_token', true);
        unset($data['issue_token']);

        $household = $this->service->register($data, $request->user(), $issueToken);

        return redirect()->route('households.show', $household)
            ->with('success', "Household {$household->code} registered".($issueToken ? ' and token issued.' : '.'));
    }

    public function show(Household $household): View
    {
        $this->authorize('view', $household);

        $household->load(['block', 'registeredBy', 'deactivatedBy', 'tokens.issuedBy', 'tokens.invalidatedBy', 'events.user']);

        return view('households.show', ['household' => $household]);
    }

    public function edit(Household $household): View
    {
        $this->authorize('update', $household);

        return view('households.edit', [
            'household' => $household,
            'blocks' => Block::orderBy('code')->get(),
            'consentVersion' => Household::CONSENT_VERSION,
        ]);
    }

    public function update(UpdateHouseholdRequest $request, Household $household): RedirectResponse
    {
        $this->service->update($household, $request->validated(), $request->user());

        return redirect()->route('households.show', $household)->with('success', "Household {$household->code} updated.");
    }

    public function deactivate(Request $request, Household $household): RedirectResponse
    {
        $this->authorize('deactivate', $household);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $this->service->deactivate($household, $data['reason'], $request->user());

        return redirect()->route('households.show', $household)->with('success', "Household {$household->code} deactivated.");
    }

    public function reactivate(Request $request, Household $household): RedirectResponse
    {
        $this->authorize('reactivate', $household);
        $this->service->reactivate($household, $request->user());

        return redirect()->route('households.show', $household)->with('success', "Household {$household->code} reactivated. Issue a new token.");
    }
}

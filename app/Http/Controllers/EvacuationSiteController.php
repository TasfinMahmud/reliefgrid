<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\EvacuationSite;
use App\Services\PreparednessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Feature 14 – Evacuation sites: capacity and occupancy tracking.
 */
class EvacuationSiteController extends Controller
{
    public function __construct(private readonly PreparednessService $service) {}

    public function index(): View
    {
        $this->authorize('viewAny', EvacuationSite::class);

        $sites = EvacuationSite::with('block')->orderBy('block_id')->orderBy('code')->get();

        return view('sites.index', [
            'sites' => $sites,
            'totals' => [
                'capacity' => $sites->where('is_open', true)->sum('capacity'),
                'occupancy' => $sites->where('is_open', true)->sum('current_occupancy'),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', EvacuationSite::class);

        return view('sites.form', ['site' => new EvacuationSite(['is_open' => true]), 'blocks' => Block::orderBy('code')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', EvacuationSite::class);
        $site = EvacuationSite::create($this->validated($request));

        return redirect()->route('sites.show', $site)->with('success', "Evacuation site {$site->code} created.");
    }

    public function show(EvacuationSite $site): View
    {
        $this->authorize('view', $site);
        $site->load(['block', 'occupancyLogs.recordedBy']);

        return view('sites.show', ['site' => $site]);
    }

    public function edit(EvacuationSite $site): View
    {
        $this->authorize('update', $site);

        return view('sites.form', ['site' => $site, 'blocks' => Block::orderBy('code')->get()]);
    }

    public function update(Request $request, EvacuationSite $site): RedirectResponse
    {
        $this->authorize('update', $site);
        $data = $this->validated($request, $site);
        if ($data['capacity'] < $site->current_occupancy) {
            return back()->withErrors(['capacity' => "Capacity cannot be lower than the current occupancy ({$site->current_occupancy})."])->withInput();
        }
        $site->update($data);

        return redirect()->route('sites.show', $site)->with('success', "Evacuation site {$site->code} updated.");
    }

    public function occupancy(Request $request, EvacuationSite $site): RedirectResponse
    {
        $this->authorize('updateOccupancy', $site);
        $data = $request->validate([
            'occupancy' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $this->service->updateOccupancy($site, (int) $data['occupancy'], $request->user(), $data['note'] ?? null);

        return redirect()->route('sites.show', $site)->with('success', "Occupancy recorded: {$site->current_occupancy} / {$site->capacity}.");
    }

    public function toggleOpen(Request $request, EvacuationSite $site): RedirectResponse
    {
        $this->authorize('update', $site);
        $this->service->setSiteOpen($site, ! $site->is_open, $request->user());

        return redirect()->route('sites.show', $site)->with('success', $site->is_open ? 'Site opened.' : 'Site closed.');
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, ?EvacuationSite $site = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('evacuation_sites', 'code')->ignore($site?->id)],
            'name' => ['required', 'string', 'max:120'],
            'block_id' => ['required', 'integer', 'exists:blocks,id'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100000'],
            'facilities' => ['nullable', 'string', 'max:1000'],
            'contact_person' => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);
    }
}

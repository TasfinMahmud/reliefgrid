<?php

namespace App\Http\Controllers;

use App\Enums\HazardType;
use App\Enums\IncidentStatus;
use App\Models\Block;
use App\Models\EvacuationSite;
use App\Models\HazardAlert;
use App\Models\Incident;
use App\Services\PreparednessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Feature 14 – Incident log: report → investigate → respond → verified closure.
 */
class IncidentController extends Controller
{
    public function __construct(private readonly PreparednessService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Incident::class);

        $incidents = Incident::with(['block', 'reportedBy'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('block_id'), fn ($q) => $q->where('block_id', $request->integer('block_id')))
            ->when($request->filled('hazard_type'), fn ($q) => $q->where('hazard_type', $request->string('hazard_type')))
            ->orderByDesc('occurred_at')->paginate(20)->withQueryString();

        return view('incidents.index', [
            'incidents' => $incidents,
            'statuses' => IncidentStatus::cases(),
            'blocks' => Block::orderBy('code')->get(),
            'hazardTypes' => HazardType::options(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Incident::class);

        return view('incidents.create', [
            'blocks' => Block::orderBy('code')->get(),
            'hazardTypes' => HazardType::options(),
            'alerts' => HazardAlert::currentlyActive()->orderByDesc('issued_at')->get(),
            'selectedAlert' => $request->integer('hazard_alert_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Incident::class);
        $data = $request->validate([
            'block_id' => ['required', 'integer', 'exists:blocks,id'],
            'hazard_type' => ['required', Rule::enum(HazardType::class)],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
            'hazard_alert_id' => ['nullable', 'integer', 'exists:hazard_alerts,id'],
            'households_affected' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'people_affected' => ['nullable', 'integer', 'min:0', 'max:10000000'],
        ], ['occurred_at.before_or_equal' => 'The incident time cannot be in the future.']);

        $incident = $this->service->reportIncident($data, $request->user());

        return redirect()->route('incidents.show', $incident)->with('success', "Incident {$incident->code} reported.");
    }

    public function show(Incident $incident): View
    {
        $this->authorize('view', $incident);
        $incident->load(['block', 'reportedBy', 'investigatedBy', 'responseLead', 'verifiedBy', 'hazardAlert', 'evacuationSite', 'updates.user']);

        return view('incidents.show', [
            'incident' => $incident,
            'sites' => EvacuationSite::where('block_id', $incident->block_id)->orWhere('is_open', true)->orderBy('code')->get(),
        ]);
    }

    public function investigate(Request $request, Incident $incident): RedirectResponse
    {
        $this->authorize('investigate', $incident);
        $data = $request->validate(['investigation_notes' => ['required', 'string', 'min:10', 'max:3000']]);
        $this->service->startInvestigation($incident, $request->user(), $data['investigation_notes']);

        return redirect()->route('incidents.show', $incident)->with('success', 'Investigation started.');
    }

    public function respond(Request $request, Incident $incident): RedirectResponse
    {
        $this->authorize('respond', $incident);
        $data = $request->validate([
            'response_notes' => ['required', 'string', 'min:10', 'max:3000'],
            'households_affected' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'people_affected' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'injuries' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'deaths' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'evacuation_site_id' => ['nullable', 'integer', 'exists:evacuation_sites,id'],
        ]);
        $this->service->startResponse($incident, $request->user(), $data);

        return redirect()->route('incidents.show', $incident)->with('success', 'Response started.');
    }

    public function note(Request $request, Incident $incident): RedirectResponse
    {
        $this->authorize('addNote', $incident);
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);
        $this->service->addNote($incident, $request->user(), $data['note']);

        return redirect()->route('incidents.show', $incident)->with('success', 'Note added.');
    }

    public function close(Request $request, Incident $incident): RedirectResponse
    {
        $this->authorize('close', $incident);
        $data = $request->validate(['verification_notes' => ['required', 'string', 'min:10', 'max:2000']]);
        $this->service->verifyAndClose($incident, $request->user(), $data['verification_notes']);

        return redirect()->route('incidents.show', $incident)->with('success', "Incident {$incident->code} verified and closed.");
    }
}

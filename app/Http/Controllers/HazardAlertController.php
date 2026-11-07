<?php

namespace App\Http\Controllers;

use App\Enums\AlertSeverity;
use App\Enums\HazardType;
use App\Models\Block;
use App\Models\HazardAlert;
use App\Services\PreparednessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Feature 14 – Block-level hazard alerts.
 */
class HazardAlertController extends Controller
{
    public function __construct(private readonly PreparednessService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', HazardAlert::class);
        $this->service->expireAlerts();

        $alerts = HazardAlert::with(['blocks', 'issuedBy'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('block_id'), fn ($q) => $q->whereHas('blocks', fn ($b) => $b->where('blocks.id', $request->integer('block_id'))))
            ->orderByDesc('issued_at')->paginate(20)->withQueryString();

        // Active alerts summarised per block for the "alert board".
        $board = Block::orderBy('code')->with(['hazardAlerts' => fn ($q) => $q->currentlyActive()])->get();

        return view('alerts.index', ['alerts' => $alerts, 'board' => $board, 'blocks' => Block::orderBy('code')->get()]);
    }

    public function create(): View
    {
        $this->authorize('create', HazardAlert::class);

        return view('alerts.create', [
            'blocks' => Block::orderBy('code')->get(),
            'hazardTypes' => HazardType::options(),
            'severities' => AlertSeverity::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', HazardAlert::class);
        $data = $request->validate([
            'hazard_type' => ['required', Rule::enum(HazardType::class)],
            'severity' => ['required', Rule::enum(AlertSeverity::class)],
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'block_ids' => ['required', 'array', 'min:1'],
            'block_ids.*' => ['integer', 'exists:blocks,id'],
        ], ['block_ids.required' => 'Select at least one block for this alert.']);

        $alert = $this->service->issueAlert($data, $request->user());

        return redirect()->route('alerts.show', $alert)->with('success', "Alert issued to {$alert->blocks()->count()} block(s).");
    }

    public function show(HazardAlert $alert): View
    {
        $this->authorize('view', $alert);
        $alert->load(['blocks', 'issuedBy', 'cancelledBy', 'incidents.block']);

        return view('alerts.show', ['alert' => $alert]);
    }

    public function cancel(Request $request, HazardAlert $alert): RedirectResponse
    {
        $this->authorize('cancel', $alert);
        $data = $request->validate(['cancel_reason' => ['required', 'string', 'max:255']]);
        $this->service->cancelAlert($alert, $request->user(), $data['cancel_reason']);

        return redirect()->route('alerts.show', $alert)->with('success', 'Alert cancelled.');
    }
}

<?php

namespace App\Dashboard\Widgets;

use App\Dashboard\AbstractWidget;
use App\Models\EvacuationSite;
use App\Models\HazardAlert;
use App\Models\Incident;
use App\Services\PreparednessService;

/**
 * Feature 14 – active alerts, open incidents and evacuation site capacity.
 */
class PreparednessWidget extends AbstractWidget
{
    public function __construct(private readonly PreparednessService $preparedness) {}

    public function order(): int
    {
        return 50;
    }

    public function cards(): array
    {
        $this->preparedness->expireAlerts();

        return [
            ['Active alerts', HazardAlert::currentlyActive()->count(), route('alerts.index'), Incident::open()->count().' open incidents'],
        ];
    }

    public function actions(): array
    {
        return [['Report incident', route('incidents.create'), 'btn-outline-secondary', 'create', Incident::class]];
    }

    public function view(): ?string
    {
        return 'dashboard.widgets.preparedness';
    }

    public function width(): int
    {
        return 12;
    }

    public function data(): array
    {
        return [
            'alerts' => HazardAlert::currentlyActive()->with('blocks')->orderByDesc('issued_at')->limit(5)->get(),
            'incidents' => Incident::open()->with('block')->orderByDesc('occurred_at')->limit(5)->get(),
            'sites' => EvacuationSite::where('is_open', true)->with('block')->orderBy('code')->get(),
        ];
    }
}

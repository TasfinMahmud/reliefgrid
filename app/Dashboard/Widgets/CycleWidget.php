<?php

namespace App\Dashboard\Widgets;

use App\Dashboard\AbstractWidget;
use App\Models\AssistanceCycle;

/**
 * Feature 2 – assistance cycle figures.
 */
class CycleWidget extends AbstractWidget
{
    public function order(): int
    {
        return 20;
    }

    public function cards(): array
    {
        return [
            ['Active cycles', AssistanceCycle::active()->count(), route('cycles.index'), AssistanceCycle::where('status', 'draft')->count().' draft'],
        ];
    }

    public function view(): ?string
    {
        return 'dashboard.widgets.cycles';
    }

    public function data(): array
    {
        return ['cycles' => AssistanceCycle::withCount('entitlements')->orderByDesc('starts_on')->limit(5)->get()];
    }
}

<?php

namespace App\Dashboard\Widgets;

use App\Dashboard\AbstractWidget;
use App\Models\Household;
use App\Models\Token;

/**
 * Feature 1 – household and token figures.
 */
class HouseholdWidget extends AbstractWidget
{
    public function order(): int
    {
        return 10;
    }

    public function cards(): array
    {
        return [
            ['Active households', Household::active()->count(), route('households.index'), Household::count().' registered'],
            ['Active tokens', Token::where('status', 'active')->count(), route('households.index'), 'QR cards in circulation'],
        ];
    }

    public function actions(): array
    {
        return [['Register household', route('households.create'), 'btn-rg', 'create', Household::class]];
    }

    public function view(): ?string
    {
        return 'dashboard.widgets.households';
    }

    public function data(): array
    {
        return ['recentHouseholds' => Household::with('block')->orderByDesc('id')->limit(6)->get()];
    }
}

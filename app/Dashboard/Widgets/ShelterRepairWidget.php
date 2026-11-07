<?php

namespace App\Dashboard\Widgets;

use App\Dashboard\AbstractWidget;
use App\Models\ShelterRepairRequest;

/**
 * Feature 12 – open shelter repair requests on the dashboard.
 */
class ShelterRepairWidget extends AbstractWidget
{
    public function order(): int
    {
        return 40;
    }

    public function cards(): array
    {
        return [
            ['Open repairs', ShelterRepairRequest::open()->count(), route('repairs.index'), 'shelter requests'],
        ];
    }

    public function actions(): array
    {
        return [['Repair request', route('repairs.create'), 'btn-outline-secondary', 'create', ShelterRepairRequest::class]];
    }

    public function view(): ?string
    {
        return 'dashboard.widgets.repairs';
    }

    public function width(): int
    {
        return 12;
    }

    public function data(): array
    {
        return [
            'repairs' => ShelterRepairRequest::open()->with(['household', 'block'])->orderByDesc('updated_at')->limit(5)->get(),
        ];
    }
}

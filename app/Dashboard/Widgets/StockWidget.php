<?php

namespace App\Dashboard\Widgets;

use App\Dashboard\AbstractWidget;
use App\Enums\TransferStatus;
use App\Models\StockTransfer;
use App\Services\StockService;

/**
 * Feature 5 – pending transfers and stock expiring soon.
 */
class StockWidget extends AbstractWidget
{
    public function __construct(private readonly StockService $stock) {}

    public function order(): int
    {
        return 30;
    }

    public function cards(): array
    {
        return [
            ['Pending transfers', StockTransfer::where('status', TransferStatus::Pending->value)->count(), route('transfers.index'), 'awaiting approval'],
        ];
    }

    public function view(): ?string
    {
        return 'dashboard.widgets.stock';
    }

    public function data(): array
    {
        return ['expiry' => $this->stock->expiryAlerts(30)];
    }
}

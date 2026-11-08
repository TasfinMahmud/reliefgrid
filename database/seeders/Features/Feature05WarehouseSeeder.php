<?php

namespace Database\Seeders\Features;

use App\Models\AssistanceCycle;
use App\Models\Block;
use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Database\Seeder;

/**
 * Feature 5 – two warehouses, batch receipts, issues and transfers.
 */
class Feature05WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        if (Warehouse::count() > 0) {
            return;
        }

        $stock = app(StockService::class);
        $officer = User::where('email', 'warehouse@reliefgrid.test')->firstOrFail();
        $coordinator = User::where('email', 'coordinator@reliefgrid.test')->firstOrFail();
        $block = fn (string $code) => Block::where('code', $code)->first();
        $item = fn (string $code) => Item::where('code', $code)->firstOrFail();
        $cycle = class_exists(AssistanceCycle::class) ? AssistanceCycle::active()->first() : null;

        $main = Warehouse::create(['code' => 'WH-MAIN', 'name' => 'Main warehouse', 'block_id' => $block('B-04')?->id, 'address' => 'Market Road, Camp 4', 'is_active' => true]);
        $hub = Warehouse::create(['code' => 'WH-C5', 'name' => 'Camp 5 hub', 'block_id' => $block('B-05')?->id, 'address' => 'Lower Valley', 'is_active' => true]);

        $rice = $stock->receive($main, $item('RICE'), ['batch_number' => 'RICE-2026-10A', 'quantity' => 5000, 'expiry_date' => now()->addMonths(10)->toDateString(), 'received_at' => now()->subDays(20)->toDateString(), 'supplier' => 'WFP'], $officer);
        $stock->receive($main, $item('RICE'), ['batch_number' => 'RICE-2026-09B', 'quantity' => 1200, 'expiry_date' => now()->addDays(20)->toDateString(), 'received_at' => now()->subDays(45)->toDateString(), 'supplier' => 'WFP'], $officer);
        $stock->receive($main, $item('LENTIL'), ['batch_number' => 'LEN-2026-10', 'quantity' => 800, 'expiry_date' => now()->addMonths(8)->toDateString(), 'received_at' => now()->subDays(18)->toDateString(), 'supplier' => 'WFP'], $officer);
        $oil = $stock->receive($main, $item('OIL'), ['batch_number' => 'OIL-2026-10', 'quantity' => 600, 'expiry_date' => now()->addMonths(14)->toDateString(), 'received_at' => now()->subDays(18)->toDateString(), 'supplier' => 'UNHCR'], $officer);
        $stock->receive($main, $item('SALT'), ['batch_number' => 'SALT-2026-08', 'quantity' => 300, 'expiry_date' => null, 'received_at' => now()->subDays(60)->toDateString(), 'supplier' => 'Local purchase'], $officer);
        $tarp = $stock->receive($main, $item('TARP'), ['batch_number' => 'TARP-2026-07', 'quantity' => 400, 'expiry_date' => null, 'received_at' => now()->subDays(90)->toDateString(), 'supplier' => 'IOM'], $officer);
        $stock->receive($main, $item('BAMBOO'), ['batch_number' => 'BMB-2026-07', 'quantity' => 1500, 'expiry_date' => null, 'received_at' => now()->subDays(90)->toDateString(), 'supplier' => 'IOM'], $officer);
        $stock->receive($main, $item('ROPE'), ['batch_number' => 'ROPE-2026-07', 'quantity' => 250, 'expiry_date' => null, 'received_at' => now()->subDays(90)->toDateString(), 'supplier' => 'IOM'], $officer);
        $stock->receive($hub, $item('SOAP'), ['batch_number' => 'SOAP-2026-09', 'quantity' => 3000, 'expiry_date' => now()->addYears(2)->toDateString(), 'received_at' => now()->subDays(30)->toDateString(), 'supplier' => 'UNICEF'], $officer);
        $stock->receive($hub, $item('HYG-KIT'), ['batch_number' => 'HYG-2026-09', 'quantity' => 500, 'expiry_date' => now()->addYears(1)->toDateString(), 'received_at' => now()->subDays(30)->toDateString(), 'supplier' => 'UNICEF'], $officer);

        $stock->issue($rice, 960, $officer, $cycle, 'Distribution point DP-1, day 1');
        $stock->issue($oil, 80, $officer, $cycle, 'Distribution point DP-1, day 1');

        $transfer = $stock->requestTransfer($rice, $hub, 1000, $officer, 'Pre-position for Camp 5 distribution');
        $stock->approveTransfer($transfer, $coordinator, 'Approved for DP-5');
        $stock->requestTransfer($tarp, $hub, 50, $officer, 'Shelter repairs in Camp 5');
    }
}

<?php

namespace Database\Seeders\Features;

use App\Models\AssistanceCycle;
use App\Models\Block;
use App\Models\Item;
use App\Models\User;
use App\Services\EntitlementPlanner;
use Illuminate\Database\Seeder;

/**
 * Feature 2 – one activated food cycle and one draft hygiene cycle.
 */
class Feature02CycleSeeder extends Seeder
{
    public function run(): void
    {
        if (AssistanceCycle::count() > 0) {
            return;
        }

        $planner = app(EntitlementPlanner::class);
        $coordinator = User::where('email', 'coordinator@reliefgrid.test')->firstOrFail();
        $item = fn (string $code) => Item::where('code', $code)->firstOrFail();

        $cycle = AssistanceCycle::create([
            'name' => 'November 2026 general food distribution',
            'description' => 'Monthly food basket for all active households.',
            'starts_on' => '2026-11-10', 'ends_on' => '2026-11-20',
            'status' => 'draft', 'created_by' => $coordinator->id,
        ]);
        $cycle->rules()->createMany([
            ['item_id' => $item('RICE')->id, 'min_household_size' => 1, 'max_household_size' => null, 'base_quantity' => 0, 'per_member_quantity' => 12, 'max_quantity' => 96, 'notes' => '12 kg per person, capped at 8 persons'],
            ['item_id' => $item('LENTIL')->id, 'min_household_size' => 1, 'max_household_size' => null, 'base_quantity' => 0, 'per_member_quantity' => 1.5, 'max_quantity' => 12],
            ['item_id' => $item('OIL')->id, 'min_household_size' => 1, 'max_household_size' => 4, 'base_quantity' => 2, 'per_member_quantity' => 0],
            ['item_id' => $item('OIL')->id, 'min_household_size' => 5, 'max_household_size' => null, 'base_quantity' => 4, 'per_member_quantity' => 0],
            ['item_id' => $item('SALT')->id, 'min_household_size' => 1, 'max_household_size' => null, 'base_quantity' => 1, 'per_member_quantity' => 0],
        ]);
        $planner->activate($cycle, $coordinator);

        $draft = AssistanceCycle::create([
            'name' => 'December 2026 hygiene kits (Camp 4)',
            'description' => 'Hygiene kit top-up for Camp 4 blocks only.',
            'starts_on' => '2026-12-01', 'ends_on' => '2026-12-10',
            'status' => 'draft', 'created_by' => $coordinator->id,
        ]);
        $draft->blocks()->sync(Block::whereIn('code', ['B-01', 'B-02', 'B-03', 'B-04'])->pluck('id'));
        $draft->rules()->createMany([
            ['item_id' => $item('HYG-KIT')->id, 'min_household_size' => 1, 'max_household_size' => null, 'base_quantity' => 1, 'per_member_quantity' => 0],
            ['item_id' => $item('SOAP')->id, 'min_household_size' => 1, 'max_household_size' => null, 'base_quantity' => 0, 'per_member_quantity' => 2],
        ]);
    }
}

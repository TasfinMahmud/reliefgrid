<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\AssistanceCycle;
use App\Models\Block;
use App\Models\Household;
use App\Models\Item;
use App\Models\User;
use App\Services\HouseholdService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data for ReliefGrid. Every account uses the password "password".
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------------------------------------- Users
        $users = [];
        foreach ([
            ['Admin User', 'admin@reliefgrid.test', Role::Admin],
            ['Nadia Coordinator', 'coordinator@reliefgrid.test', Role::Coordinator],
            ['Rafi Field Officer', 'field@reliefgrid.test', Role::FieldOfficer],
            ['Mina Warehouse', 'warehouse@reliefgrid.test', Role::WarehouseOfficer],
            ['Tanvir Assessor', 'assessor@reliefgrid.test', Role::ShelterAssessor],
        ] as [$name, $email, $role]) {
            $users[$role->value] = User::firstOrCreate(['email' => $email], [
                'name' => $name,
                'password' => Hash::make('password'),
                'role' => $role,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }
        $admin = $users['admin'];
        $coordinator = $users['coordinator'];
        $field = $users['field_officer'];
        $warehouseOfficer = $users['warehouse_officer'];
        $assessor = $users['shelter_assessor'];

        // --------------------------------------------------------- Blocks
        $blocks = collect([
            ['B-01', 'Hillside North', 'Camp 4', 'North', 21.2041, 92.1612, 3200],
            ['B-02', 'Hillside South', 'Camp 4', 'South', 21.2002, 92.1630, 2800],
            ['B-03', 'Riverside', 'Camp 4', 'East', 21.1988, 92.1701, 4100],
            ['B-04', 'Market Road', 'Camp 4', 'East', 21.2010, 92.1688, 3600],
            ['B-05', 'Lower Valley', 'Camp 5', 'West', 21.1950, 92.1555, 2200],
            ['B-06', 'Upper Ridge', 'Camp 5', 'West', 21.1970, 92.1520, 1900],
        ])->map(fn ($b) => Block::firstOrCreate(['code' => $b[0]], [
            'name' => $b[1], 'camp' => $b[2], 'sector' => $b[3], 'latitude' => $b[4], 'longitude' => $b[5], 'estimated_population' => $b[6],
        ]))->keyBy('code');

        // ---------------------------------------------------------- Items
        $items = collect([
            ['RICE', 'Rice', 'food', 'kg'],
            ['LENTIL', 'Lentils', 'food', 'kg'],
            ['OIL', 'Vegetable oil', 'food', 'litre'],
            ['SALT', 'Iodised salt', 'food', 'kg'],
            ['SOAP', 'Soap bar', 'wash', 'pcs'],
            ['HYG-KIT', 'Hygiene kit', 'wash', 'pcs'],
            ['TARP', 'Tarpaulin 4x6m', 'shelter', 'pcs'],
            ['BAMBOO', 'Bamboo pole 4m', 'shelter', 'pcs'],
            ['ROPE', 'Rope 30m', 'shelter', 'pcs'],
            ['SANDBAG', 'Sandbag', 'shelter', 'pcs'],
            ['LPG', 'LPG cylinder 12kg', 'fuel', 'pcs'],
        ])->map(fn ($i) => Item::firstOrCreate(['code' => $i[0]], ['name' => $i[1], 'category' => $i[2], 'unit' => $i[3], 'is_active' => true]))->keyBy('code');

        if (Household::count() > 0) {
            $this->command?->info('Demo data already present; skipping transactional demo records.');

            return;
        }

        // ----------------------------------------------- Feature 1: households
        $householdService = app(HouseholdService::class);
        $heads = ['Abdul Karim', 'Fatema Begum', 'Mohammad Hasan', 'Rahima Khatun', 'Noor Alam', 'Sakina Bibi', 'Jamal Uddin', 'Ayesha Akter',
            'Hafiz Ullah', 'Rukiya Begum', 'Salim Mia', 'Hasina Khatun', 'Yunus Ali', 'Shahida Begum', 'Rashid Ahmed', 'Laila Khatun',
            'Nurul Islam', 'Morium Begum', 'Zakir Hossain', 'Sufia Khatun', 'Kamal Hossain', 'Amina Khatun', 'Faruk Ahmed', 'Nasima Begum'];
        $households = collect();
        foreach ($heads as $i => $name) {
            $block = $blocks->values()[$i % $blocks->count()];
            $households->push($householdService->register([
                'block_id' => $block->id,
                'head_name' => $name,
                'head_sex' => $i % 2 ? 'female' : 'male',
                'head_age' => 22 + ($i * 7) % 50,
                'phone' => $i % 3 ? sprintf('017%08d', 10000000 + $i * 9137) : null,
                'household_size' => 1 + ($i * 5) % 8,
                'shelter_number' => sprintf('%s-S%02d', $block->code, $i + 1),
                'consent_given' => true,
            ], $field));
        }
        // One lost-token reissue and one deactivation to show history.
        $householdService->reissueToken($households[3], 'Card lost during heavy rain', $field);
        $householdService->deactivate($households[22], 'Family relocated to Camp 7', $coordinator);

        $this->command?->info('Demo data seeded. Log in with admin@reliefgrid.test / password (or coordinator@, field@, warehouse@, assessor@).');
    }
}

<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Block;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data for ReliefGrid. Every account uses the password "password".
 *
 * Core reference data (users, blocks, items) is seeded here; each feature
 * ships its own seeder in database/seeders/Features, which are run in file
 * order afterwards.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUsers();
        $this->seedBlocks();
        $this->seedItems();

        foreach (glob(database_path('seeders/Features/*.php')) ?: [] as $file) {
            $class = 'Database\\Seeders\\Features\\'.basename($file, '.php');
            if (class_exists($class)) {
                $this->call($class);
            }
        }

        $this->command?->info('Demo data seeded. Log in with admin@reliefgrid.test / password (or coordinator@, field@, warehouse@, assessor@).');
    }

    private function seedUsers(): void
    {
        foreach ([
            ['Admin User', 'admin@reliefgrid.test', Role::Admin],
            ['Nadia Coordinator', 'coordinator@reliefgrid.test', Role::Coordinator],
            ['Rafi Field Officer', 'field@reliefgrid.test', Role::FieldOfficer],
            ['Mina Warehouse', 'warehouse@reliefgrid.test', Role::WarehouseOfficer],
            ['Tanvir Assessor', 'assessor@reliefgrid.test', Role::ShelterAssessor],
        ] as [$name, $email, $role]) {
            User::firstOrCreate(['email' => $email], [
                'name' => $name,
                'password' => Hash::make('password'),
                'role' => $role,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }
    }

    private function seedBlocks(): void
    {
        foreach ([
            ['B-01', 'Hillside North', 'Camp 4', 'North', 21.2041, 92.1612, 3200],
            ['B-02', 'Hillside South', 'Camp 4', 'South', 21.2002, 92.1630, 2800],
            ['B-03', 'Riverside', 'Camp 4', 'East', 21.1988, 92.1701, 4100],
            ['B-04', 'Market Road', 'Camp 4', 'East', 21.2010, 92.1688, 3600],
            ['B-05', 'Lower Valley', 'Camp 5', 'West', 21.1950, 92.1555, 2200],
            ['B-06', 'Upper Ridge', 'Camp 5', 'West', 21.1970, 92.1520, 1900],
        ] as $b) {
            Block::firstOrCreate(['code' => $b[0]], [
                'name' => $b[1], 'camp' => $b[2], 'sector' => $b[3], 'latitude' => $b[4], 'longitude' => $b[5], 'estimated_population' => $b[6],
            ]);
        }
    }

    private function seedItems(): void
    {
        foreach ([
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
        ] as $i) {
            Item::firstOrCreate(['code' => $i[0]], ['name' => $i[1], 'category' => $i[2], 'unit' => $i[3], 'is_active' => true]);
        }
    }
}

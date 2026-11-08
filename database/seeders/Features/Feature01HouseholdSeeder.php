<?php

namespace Database\Seeders\Features;

use App\Models\Block;
use App\Models\Household;
use App\Models\User;
use App\Services\HouseholdService;
use Illuminate\Database\Seeder;

/**
 * Feature 1 – demo households with tokens, one reissue and one deactivation.
 */
class Feature01HouseholdSeeder extends Seeder
{
    public function run(): void
    {
        if (Household::count() > 0) {
            return;
        }

        $service = app(HouseholdService::class);
        $field = User::where('email', 'field@reliefgrid.test')->firstOrFail();
        $coordinator = User::where('email', 'coordinator@reliefgrid.test')->firstOrFail();
        $blocks = Block::orderBy('code')->get();

        $heads = ['Abdul Karim', 'Fatema Begum', 'Mohammad Hasan', 'Rahima Khatun', 'Noor Alam', 'Sakina Bibi', 'Jamal Uddin', 'Ayesha Akter',
            'Hafiz Ullah', 'Rukiya Begum', 'Salim Mia', 'Hasina Khatun', 'Yunus Ali', 'Shahida Begum', 'Rashid Ahmed', 'Laila Khatun',
            'Nurul Islam', 'Morium Begum', 'Zakir Hossain', 'Sufia Khatun', 'Kamal Hossain', 'Amina Khatun', 'Faruk Ahmed', 'Nasima Begum'];

        $households = collect();
        foreach ($heads as $i => $name) {
            $block = $blocks[$i % $blocks->count()];
            $households->push($service->register([
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

        // One lost-token reissue and one deactivation to show the history trail.
        $service->reissueToken($households[3], 'Card lost during heavy rain', $field);
        $service->deactivate($households[22], 'Family relocated to Camp 7', $coordinator);
    }
}

<?php

namespace Database\Seeders\Features;

use App\Models\Block;
use App\Models\EvacuationSite;
use App\Models\HazardAlert;
use App\Models\User;
use App\Services\PreparednessService;
use Illuminate\Database\Seeder;

/**
 * Feature 14 – hazard alerts, evacuation sites with occupancy, and incidents.
 */
class Feature14PreparednessSeeder extends Seeder
{
    public function run(): void
    {
        if (HazardAlert::count() > 0) {
            return;
        }

        $prep = app(PreparednessService::class);
        $field = User::where('email', 'field@reliefgrid.test')->firstOrFail();
        $coordinator = User::where('email', 'coordinator@reliefgrid.test')->firstOrFail();
        $block = fn (string $code) => Block::where('code', $code)->firstOrFail();

        $alert = $prep->issueAlert([
            'hazard_type' => 'landslide', 'severity' => 'warning',
            'title' => 'Heavy rainfall forecast – landslide risk on slopes',
            'message' => '120 mm of rain forecast over 48 hours. Slopes in Hillside blocks are saturated.',
            'instructions' => 'Households within 10 m of steep slopes should move to the evacuation site at night.',
            'expires_at' => now()->addDays(3), 'block_ids' => [$block('B-01')->id, $block('B-02')->id],
        ], $coordinator);
        $prep->issueAlert([
            'hazard_type' => 'flood', 'severity' => 'advisory',
            'title' => 'Rising river level – monitor Riverside drains',
            'message' => 'River gauge at 2.1 m, rising 10 cm per hour.',
            'expires_at' => now()->addDays(2), 'block_ids' => [$block('B-03')->id],
        ], $coordinator);

        $siteA = EvacuationSite::create(['code' => 'EV-01', 'name' => 'Hillside community centre', 'block_id' => $block('B-01')->id, 'capacity' => 400, 'is_open' => true, 'facilities' => 'Water point, 6 latrines, solar lighting', 'contact_person' => 'Site focal: Kabir', 'contact_phone' => '01711000111']);
        $siteB = EvacuationSite::create(['code' => 'EV-02', 'name' => 'Riverside primary school', 'block_id' => $block('B-03')->id, 'capacity' => 250, 'is_open' => true, 'facilities' => '4 classrooms, water tank', 'contact_person' => 'Head teacher', 'contact_phone' => '01711000222']);
        EvacuationSite::create(['code' => 'EV-03', 'name' => 'Lower Valley mosque hall', 'block_id' => $block('B-05')->id, 'capacity' => 150, 'is_open' => false]);
        $prep->updateOccupancy($siteA, 120, $field, 'Evening headcount after alert');
        $prep->updateOccupancy($siteA, 210, $field, 'Morning headcount');
        $prep->updateOccupancy($siteB, 40, $field, 'Families from low-lying shelters');

        $inc1 = $prep->reportIncident([
            'block_id' => $block('B-02')->id, 'hazard_type' => 'landslide', 'title' => 'Minor slope failure behind shelters S12–S15',
            'description' => 'Soil slipped ~3 m behind four shelters at 02:30; no injuries reported, two shelters partially buried.',
            'occurred_at' => now()->subHours(20), 'hazard_alert_id' => $alert->id, 'households_affected' => 4, 'people_affected' => 19,
        ], $field);
        $prep->startInvestigation($inc1, $coordinator, 'Site visited 07:00. Two shelters damaged, slope still unstable. Families moved to EV-01.');
        $prep->startResponse($inc1, $coordinator, ['response_notes' => 'Sandbag line being built; 4 households registered at EV-01; repair requests to follow.', 'households_affected' => 4, 'people_affected' => 19, 'injuries' => 0, 'deaths' => 0, 'evacuation_site_id' => $siteA->id]);

        $inc2 = $prep->reportIncident([
            'block_id' => $block('B-03')->id, 'hazard_type' => 'flood', 'title' => 'Drain overflow flooding 6 shelters',
            'description' => 'Main drain blocked with debris; water 20 cm deep inside shelters along the river path.',
            'occurred_at' => now()->subDays(3), 'households_affected' => 6, 'people_affected' => 31,
        ], $field);
        $prep->startInvestigation($inc2, $field, 'Blockage located at culvert near S40.');
        $prep->startResponse($inc2, $field, ['response_notes' => 'Culvert cleared by WASH team; shelters drained.', 'injuries' => 0, 'deaths' => 0]);
        $prep->verifyAndClose($inc2, $coordinator, 'Verified on site 2 days after clearance; no standing water, drain flowing.');

        $prep->reportIncident([
            'block_id' => $block('B-06')->id, 'hazard_type' => 'fire', 'title' => 'Cooking fire damaged one shelter',
            'description' => 'LPG stove fire spread to tarpaulin wall; extinguished by neighbours within minutes.',
            'occurred_at' => now()->subHours(5), 'households_affected' => 1, 'people_affected' => 6,
        ], $field);
    }
}

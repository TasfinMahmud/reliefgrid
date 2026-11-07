<?php

namespace Database\Seeders\Features;

use App\Enums\RepairStatus;
use App\Models\Household;
use App\Models\ShelterRepairEvent;
use App\Models\ShelterRepairRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Feature 12 demo data: four repair requests, one in each main workflow state.
 * Idempotent – does nothing if repair requests already exist.
 */
class Feature12ShelterRepairSeeder extends Seeder
{
    public function run(): void
    {
        if (ShelterRepairRequest::query()->exists()) {
            return;
        }

        $households = Household::query()->active()->orderBy('id')->limit(4)->get();
        if ($households->count() < 4) {
            return; // core demo households not seeded yet
        }

        $field = User::where('email', 'field@reliefgrid.test')->first();
        $assessor = User::where('email', 'assessor@reliefgrid.test')->first();
        $manager = User::where('email', 'coordinator@reliefgrid.test')->first();
        $warehouse = User::where('email', 'warehouse@reliefgrid.test')->first();
        if (! $field || ! $assessor || ! $manager || ! $warehouse) {
            return;
        }

        // 1. Submitted
        $this->make($households[0], 'roof', 'Roof sheet torn by wind; rain enters the sleeping area.', $field,
            [['submitted', null, RepairStatus::Submitted, $field, 'Roof sheet torn by wind; rain enters the sleeping area.']]);

        // 2. Assessed, waiting for approval
        $r2 = $this->make($households[1], 'walls', 'Bamboo wall frame is leaning after heavy rain.', $field, [
            ['submitted', null, RepairStatus::Submitted, $field, null],
            ['assessed', RepairStatus::Submitted, RepairStatus::Assessed, $assessor, 'Frame needs re-tying and two new poles.'],
        ], RepairStatus::Assessed, ['severity' => 'moderate', 'assessed_by' => $assessor->id, 'assessed_at' => now()->subDays(2), 'estimated_days' => 2]);
        $this->materials($r2, [['Bamboo pole', 'pcs', 2], ['Rope', 'm', 20]]);

        // 3. Approved, materials not yet issued
        $r3 = $this->make($households[2], 'drainage', 'Water pools at the door and floods the shelter.', $field, [
            ['submitted', null, RepairStatus::Submitted, $field, null],
            ['assessed', RepairStatus::Submitted, RepairStatus::Assessed, $assessor, 'Dig drain and lay tarpaulin.'],
            ['approved', RepairStatus::Assessed, RepairStatus::Approved, $manager, 'Approved for next week.'],
        ], RepairStatus::Approved, [
            'severity' => 'major', 'assessed_by' => $assessor->id, 'assessed_at' => now()->subDays(4),
            'approved_by' => $manager->id, 'approved_at' => now()->subDays(3), 'estimated_days' => 3,
        ]);
        $this->materials($r3, [['Tarpaulin', 'pcs', 1], ['Sandbag', 'pcs', 12]]);

        // 4. Completed with a geo-tagged verification photo
        $r4 = $this->make($households[3], 'door_window', 'Shelter door will not close.', $field, [
            ['submitted', null, RepairStatus::Submitted, $field, null],
            ['assessed', RepairStatus::Submitted, RepairStatus::Assessed, $assessor, 'Replace hinges and bamboo door panel.'],
            ['approved', RepairStatus::Assessed, RepairStatus::Approved, $manager, null],
            ['materials_issued', RepairStatus::Approved, RepairStatus::MaterialsIssued, $warehouse, 'All materials issued.'],
            ['completed', RepairStatus::MaterialsIssued, RepairStatus::Completed, $assessor, 'Door fitted and closes properly.'],
        ], RepairStatus::Completed, [
            'severity' => 'minor', 'assessed_by' => $assessor->id, 'assessed_at' => now()->subDays(9),
            'approved_by' => $manager->id, 'approved_at' => now()->subDays(8),
            'completed_by' => $assessor->id, 'completed_at' => now()->subDays(6), 'estimated_days' => 1,
            'completion_notes' => 'Door fitted and closes properly.',
        ]);
        $m = $this->materials($r4, [['Door hinge', 'pcs', 2]]);
        $r4->materials()->update(['quantity_issued' => 2]);

        // 1x1 placeholder image so the gallery has something to show.
        $path = 'repair-photos/'.$r4->code.'/demo.png';
        Storage::disk('public')->put($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
        ));
        $r4->photos()->create([
            'path' => $path,
            'original_name' => 'demo.png',
            'caption' => 'Repaired door (demo)',
            'latitude' => 21.2105000,
            'longitude' => 92.1600000,
            'accuracy_m' => 8,
            'geo_source' => 'device',
            'captured_at' => now()->subDays(6),
            'uploaded_by' => $assessor->id,
        ]);
    }

    private function make(Household $household, string $type, string $description, User $reporter, array $events, RepairStatus $status = RepairStatus::Submitted, array $extra = []): ShelterRepairRequest
    {
        $request = ShelterRepairRequest::create([
            'household_id' => $household->id,
            'block_id' => $household->block_id,
            'damage_type' => $type,
            'description' => $description,
            'status' => $status,
            'reported_by' => $reporter->id,
        ] + $extra);
        $request->update(['code' => sprintf('SR-%05d', $request->id)]);

        foreach ($events as [$action, $from, $to, $user, $note]) {
            ShelterRepairEvent::create([
                'shelter_repair_request_id' => $request->id,
                'user_id' => $user->id,
                'action' => $action,
                'from_status' => $from?->value,
                'to_status' => $to?->value,
                'note' => $note,
            ]);
        }

        return $request;
    }

    private function materials(ShelterRepairRequest $request, array $lines)
    {
        foreach ($lines as [$name, $unit, $qty]) {
            $request->materials()->create(['item_name' => $name, 'unit' => $unit, 'quantity_required' => $qty]);
        }

        return $request->materials;
    }
}

<?php

namespace Tests\Feature;

use App\Enums\RepairStatus;
use App\Models\Household;
use App\Models\ShelterRepairRequest;
use App\Models\User;
use App\Services\ShelterRepairService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature 12 – Shelter repair workflow.
 * Uses the demo accounts created by the core seeder (see README), so no User factory is needed.
 */
class ShelterRepairTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed();
    }

    private function actAs(string $role): User
    {
        $user = User::where('email', "{$role}@reliefgrid.test")->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    private function household(): Household
    {
        return Household::query()->active()->firstOrFail();
    }

    private function newRequest(): ShelterRepairRequest
    {
        $this->actAs('field');
        $this->post(route('repairs.store'), [
            'household_id' => $this->household()->id,
            'damage_type' => 'roof',
            'description' => 'Roof sheet torn by the storm last night.',
        ])->assertRedirect();

        return ShelterRepairRequest::query()->latest('id')->firstOrFail();
    }

    private function assessWithMaterials(ShelterRepairRequest $repair, ?array $materials = null): void
    {
        $this->actAs('assessor');
        $this->post(route('repairs.assess', $repair), [
            'severity' => 'moderate',
            'estimated_days' => 2,
            'materials' => $materials ?? [['item_name' => 'Tarpaulin', 'unit' => 'pcs', 'quantity_required' => 2]],
        ])->assertRedirect();
    }

    private function geoPhoto(ShelterRepairRequest $repair): void
    {
        $this->actAs('assessor');
        $this->post(route('repairs.photos.store', $repair), [
            'photo' => UploadedFile::fake()->image('after.jpg'),
            'latitude' => 21.2105,
            'longitude' => 92.16,
            'accuracy' => 10,
            'geo_source' => 'device',
        ])->assertSessionHasNoErrors();
    }

    public function test_field_officer_submits_a_request_and_gets_a_code(): void
    {
        $repair = $this->newRequest();

        $this->assertSame(RepairStatus::Submitted, $repair->status);
        $this->assertSame(sprintf('SR-%05d', $repair->id), $repair->code);
        $this->assertDatabaseHas('shelter_repair_events', ['shelter_repair_request_id' => $repair->id, 'action' => 'submitted']);
    }

    public function test_submission_is_validated(): void
    {
        $this->actAs('field');

        $this->post(route('repairs.store'), ['household_id' => 999999, 'damage_type' => 'nope', 'description' => 'short'])
            ->assertSessionHasErrors(['household_id', 'damage_type', 'description']);
    }

    public function test_only_the_right_role_can_perform_each_step(): void
    {
        $repair = $this->newRequest();

        $this->actAs('field');
        $this->post(route('repairs.assess', $repair), ['severity' => 'minor'])->assertForbidden();

        $this->assessWithMaterials($repair);

        $this->actAs('assessor');
        $this->post(route('repairs.approve', $repair))->assertForbidden();

        $this->actAs('coordinator');
        $this->post(route('repairs.approve', $repair))->assertRedirect();

        $this->actAs('field');
        $this->post(route('repairs.materials.issue', [$repair, $repair->materials()->first()]), ['quantity' => 1])->assertForbidden();
    }

    public function test_rejection_is_terminal(): void
    {
        $repair = $this->newRequest();

        $this->actAs('coordinator');
        $this->post(route('repairs.reject', $repair), ['reason' => 'Not a shelter defect.'])->assertRedirect();
        $this->assertSame(RepairStatus::Rejected, $repair->fresh()->status);

        $this->post(route('repairs.approve', $repair))->assertForbidden();
        $this->actAs('assessor');
        $this->post(route('repairs.assess', $repair), ['severity' => 'minor'])->assertForbidden();
    }

    public function test_materials_cannot_be_over_issued_and_status_moves_when_all_issued(): void
    {
        $repair = $this->newRequest();
        $this->assessWithMaterials($repair);
        $this->actAs('coordinator');
        $this->post(route('repairs.approve', $repair))->assertRedirect();
        $material = $repair->materials()->first();

        $this->actAs('warehouse');
        $this->post(route('repairs.materials.issue', [$repair, $material]), ['quantity' => 5])
            ->assertSessionHasErrors('quantity');

        $this->post(route('repairs.materials.issue', [$repair, $material]), ['quantity' => 1])->assertRedirect();
        $this->assertSame(RepairStatus::Approved, $repair->fresh()->status);

        $this->post(route('repairs.materials.issue', [$repair, $material]), ['quantity' => 1])->assertRedirect();
        $this->assertSame(RepairStatus::MaterialsIssued, $repair->fresh()->status);
    }

    public function test_closure_is_blocked_without_a_geo_tagged_photo(): void
    {
        $repair = $this->newRequest();
        $this->assessWithMaterials($repair, []); // no materials needed
        $this->actAs('coordinator');
        $this->post(route('repairs.approve', $repair))->assertRedirect();

        $this->post(route('repairs.complete', $repair))->assertSessionHasErrors('complete');
        $this->assertSame(RepairStatus::Approved, $repair->fresh()->status);

        $this->geoPhoto($repair);

        $this->actAs('coordinator');
        $this->post(route('repairs.complete', $repair), ['notes' => 'Done.'])->assertRedirect();
        $this->assertSame(RepairStatus::Completed, $repair->fresh()->status);
    }

    public function test_photo_without_any_geotag_is_rejected(): void
    {
        $repair = $this->newRequest();

        $this->actAs('assessor');
        $this->post(route('repairs.photos.store', $repair), ['photo' => UploadedFile::fake()->image('plain.jpg')])
            ->assertSessionHasErrors('photo');

        $this->assertSame(0, $repair->photos()->count());
    }

    public function test_exif_gps_is_read_from_the_photo(): void
    {
        if (! function_exists('exif_read_data')) {
            $this->markTestSkipped('The PHP exif extension is not enabled.');
        }

        $coords = app(ShelterRepairService::class)->extractExifGps(base_path('tests/fixtures/gps.jpg'));

        $this->assertNotNull($coords);
        $this->assertEqualsWithDelta(21.2105, $coords['latitude'], 0.0001);
        $this->assertEqualsWithDelta(92.16, $coords['longitude'], 0.0001);
    }

    public function test_exif_tagged_photo_is_accepted_without_device_location(): void
    {
        if (! function_exists('exif_read_data')) {
            $this->markTestSkipped('The PHP exif extension is not enabled.');
        }

        $repair = $this->newRequest();
        $this->actAs('assessor');

        $file = new UploadedFile(base_path('tests/fixtures/gps.jpg'), 'gps.jpg', 'image/jpeg', null, true);
        $this->post(route('repairs.photos.store', $repair), ['photo' => $file])->assertSessionHasNoErrors();

        $photo = $repair->photos()->firstOrFail();
        $this->assertSame('exif', $photo->geo_source);
        $this->assertEqualsWithDelta(21.2105, $photo->latitude, 0.0001);
    }

    public function test_list_and_detail_pages_render(): void
    {
        $repair = $this->newRequest();

        $this->actAs('coordinator');
        $this->get(route('repairs.index'))->assertOk()->assertSee($repair->code);
        $this->get(route('repairs.show', $repair))->assertOk()->assertSee($repair->code);
        $this->get(route('repairs.index', ['status' => 'submitted']))->assertOk();
    }
}

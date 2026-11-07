<?php

namespace Tests\Feature;

use App\Enums\AlertStatus;
use App\Enums\IncidentStatus;
use App\Models\Block;
use App\Models\EvacuationSite;
use App\Models\HazardAlert;
use App\Models\Incident;
use App\Models\User;
use App\Services\PreparednessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature 14 – Preparedness and incident response.
 */
class PreparednessTest extends TestCase
{
    use RefreshDatabase;

    private User $coordinator;

    private User $field;

    private Block $blockA;

    private Block $blockB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->coordinator = User::factory()->coordinator()->create();
        $this->field = User::factory()->fieldOfficer()->create();
        $this->blockA = Block::factory()->create();
        $this->blockB = Block::factory()->create();
    }

    // ---------------------------------------------------------------- alerts

    public function test_coordinator_can_issue_block_level_alert(): void
    {
        $this->actingAs($this->coordinator)->post('/alerts', [
            'hazard_type' => 'flood', 'severity' => 'warning', 'title' => 'River rising',
            'message' => 'Gauge at 2.1 m and rising; move to higher ground tonight.',
            'expires_at' => now()->addDay()->format('Y-m-d\TH:i'), 'block_ids' => [$this->blockA->id],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $alert = HazardAlert::first();
        $this->assertTrue($alert->isCurrentlyActive());
        $this->assertSame([$this->blockA->id], $alert->blocks()->pluck('blocks.id')->all());

        $this->actingAs($this->field)->get('/alerts')->assertOk()->assertSee('River rising');
        $this->actingAs($this->field)->get('/dashboard')->assertOk()->assertSee('River rising');
    }

    public function test_alert_validation_and_role(): void
    {
        $this->actingAs($this->field)->post('/alerts', [
            'hazard_type' => 'flood', 'severity' => 'warning', 'title' => 'x', 'message' => 'long enough message', 'block_ids' => [$this->blockA->id],
        ])->assertForbidden();

        $this->actingAs($this->coordinator)->post('/alerts', [
            'hazard_type' => 'tsunami', 'severity' => 'warning', 'title' => 'x', 'message' => 'short', 'block_ids' => [],
            'expires_at' => now()->subHour()->format('Y-m-d\TH:i'),
        ])->assertSessionHasErrors(['hazard_type', 'message', 'block_ids', 'expires_at']);
    }

    public function test_alert_can_be_cancelled_and_expired_alerts_are_not_active(): void
    {
        $alert = app(PreparednessService::class)->issueAlert([
            'hazard_type' => 'storm', 'severity' => 'watch', 'title' => 'Storm', 'message' => 'Strong winds expected tonight.',
            'expires_at' => null, 'block_ids' => [$this->blockA->id, $this->blockB->id],
        ], $this->coordinator);

        $this->actingAs($this->field)->post("/alerts/{$alert->id}/cancel", ['cancel_reason' => 'x'])->assertForbidden();
        $this->actingAs($this->coordinator)->post("/alerts/{$alert->id}/cancel", ['cancel_reason' => 'Winds subsided'])->assertRedirect();
        $this->assertSame(AlertStatus::Cancelled, $alert->fresh()->status);
        $this->actingAs($this->coordinator)->post("/alerts/{$alert->id}/cancel", ['cancel_reason' => 'again'])->assertForbidden();

        $expired = HazardAlert::create([
            'hazard_type' => 'flood', 'severity' => 'advisory', 'title' => 'Old', 'message' => 'Old alert message.', 'status' => 'active',
            'issued_by' => $this->coordinator->id, 'issued_at' => now()->subDays(3), 'expires_at' => now()->subDay(),
        ]);
        $this->assertFalse($expired->isCurrentlyActive());
        $this->assertSame(AlertStatus::Expired, $expired->effectiveStatus());
        $this->assertSame(0, HazardAlert::currentlyActive()->count());
    }

    // ------------------------------------------------------- evacuation sites

    public function test_occupancy_cannot_exceed_capacity_and_is_logged(): void
    {
        $site = EvacuationSite::factory()->create(['block_id' => $this->blockA->id, 'capacity' => 100]);

        $this->actingAs($this->field)->post("/sites/{$site->id}/occupancy", ['occupancy' => 101])->assertSessionHasErrors('occupancy');
        $this->actingAs($this->field)->post("/sites/{$site->id}/occupancy", ['occupancy' => -1])->assertSessionHasErrors('occupancy');
        $this->actingAs($this->field)->post("/sites/{$site->id}/occupancy", ['occupancy' => 100, 'note' => 'Full'])->assertRedirect()->assertSessionHasNoErrors();

        $site->refresh();
        $this->assertSame(100, $site->current_occupancy);
        $this->assertTrue($site->isFull());
        $this->assertSame('Full', $site->statusLabel());
        $log = $site->occupancyLogs()->first();
        $this->assertSame(0, $log->previous_occupancy);
        $this->assertSame(100, $log->occupancy);

        $this->actingAs(User::factory()->warehouseOfficer()->create())->post("/sites/{$site->id}/occupancy", ['occupancy' => 5])->assertForbidden();
    }

    public function test_capacity_cannot_be_reduced_below_occupancy_and_closed_sites_reject_readings(): void
    {
        $site = EvacuationSite::factory()->create(['block_id' => $this->blockA->id, 'capacity' => 100, 'current_occupancy' => 60]);

        $this->actingAs($this->coordinator)->put("/sites/{$site->id}", [
            'code' => $site->code, 'name' => $site->name, 'block_id' => $site->block_id, 'capacity' => 50,
        ])->assertSessionHasErrors('capacity');

        $this->actingAs($this->coordinator)->post("/sites/{$site->id}/toggle-open")->assertRedirect();
        $this->assertFalse($site->fresh()->is_open);
        $this->actingAs($this->field)->post("/sites/{$site->id}/occupancy", ['occupancy' => 10])->assertSessionHasErrors('occupancy');
    }

    // --------------------------------------------------------------- incidents

    private function report(): Incident
    {
        $this->actingAs($this->field)->post('/incidents', [
            'block_id' => $this->blockA->id, 'hazard_type' => 'landslide', 'title' => 'Slope failure',
            'description' => 'Soil slipped behind four shelters overnight.', 'occurred_at' => now()->subHour()->format('Y-m-d\TH:i'),
            'households_affected' => 4,
        ])->assertRedirect()->assertSessionHasNoErrors();

        return Incident::first();
    }

    public function test_incident_report_validation(): void
    {
        $this->actingAs($this->field)->post('/incidents', [
            'block_id' => $this->blockA->id, 'hazard_type' => 'landslide', 'title' => 'x', 'description' => 'short',
            'occurred_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ])->assertSessionHasErrors(['description', 'occurred_at']);
    }

    public function test_incident_moves_through_investigation_and_response_to_verified_closure(): void
    {
        $incident = $this->report();
        $this->assertSame('INC-00001', $incident->code);
        $this->assertSame(IncidentStatus::Reported, $incident->status);

        // Steps must happen in order.
        $this->actingAs($this->field)->post("/incidents/{$incident->id}/respond", ['response_notes' => 'Too early to respond'])->assertForbidden();
        $this->actingAs($this->coordinator)->post("/incidents/{$incident->id}/close", ['verification_notes' => 'Not yet possible'])->assertForbidden();

        $this->actingAs($this->field)->post("/incidents/{$incident->id}/investigate", ['investigation_notes' => 'Visited site, slope unstable.'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(IncidentStatus::UnderInvestigation, $incident->fresh()->status);

        $site = EvacuationSite::factory()->create(['block_id' => $this->blockA->id]);
        $this->actingAs($this->field)->post("/incidents/{$incident->id}/respond", [
            'response_notes' => 'Families moved to the evacuation site; sandbags placed.', 'people_affected' => 19, 'injuries' => 0, 'deaths' => 0, 'evacuation_site_id' => $site->id,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $incident->refresh();
        $this->assertSame(IncidentStatus::ResponseInProgress, $incident->status);
        $this->assertSame(19, $incident->people_affected);
        $this->assertSame($site->id, $incident->evacuation_site_id);

        // Only a coordinator/admin can verify and close.
        $this->actingAs($this->field)->post("/incidents/{$incident->id}/close", ['verification_notes' => 'I checked it myself'])->assertForbidden();
        $this->actingAs($this->coordinator)->post("/incidents/{$incident->id}/close", ['verification_notes' => 'short'])->assertSessionHasErrors('verification_notes');
        $this->actingAs($this->coordinator)->post("/incidents/{$incident->id}/close", ['verification_notes' => 'Site visited; sandbag line complete, families housed.'])->assertRedirect()->assertSessionHasNoErrors();

        $incident->refresh();
        $this->assertSame(IncidentStatus::Closed, $incident->status);
        $this->assertSame($this->coordinator->id, $incident->verified_by);
        $this->assertNotNull($incident->closed_at);
        $this->assertSame(4, $incident->updates()->count());

        // Closed incidents accept no more notes.
        $this->actingAs($this->field)->post("/incidents/{$incident->id}/note", ['note' => 'late note'])->assertForbidden();
        $this->actingAs($this->coordinator)->get("/incidents/{$incident->id}")->assertOk()->assertSee('Closed (verified)');
    }

    public function test_notes_can_be_added_to_open_incidents(): void
    {
        $incident = $this->report();
        $this->actingAs($this->field)->post("/incidents/{$incident->id}/note", ['note' => 'Rain continuing.'])->assertRedirect();
        $this->assertSame(2, $incident->updates()->count());
    }
}

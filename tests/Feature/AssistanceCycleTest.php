<?php

namespace Tests\Feature;

use App\Models\AssistanceCycle;
use App\Models\Block;
use App\Models\Household;
use App\Models\Item;
use App\Models\User;
use App\Services\EntitlementPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature 2 – Assistance cycle and entitlement planning.
 */
class AssistanceCycleTest extends TestCase
{
    use RefreshDatabase;

    private User $coordinator;

    private Block $blockA;

    private Block $blockB;

    private Item $rice;

    private Item $oil;

    protected function setUp(): void
    {
        parent::setUp();
        $this->coordinator = User::factory()->coordinator()->create();
        $this->blockA = Block::factory()->create();
        $this->blockB = Block::factory()->create();
        $this->rice = Item::factory()->create(['name' => 'Rice', 'unit' => 'kg']);
        $this->oil = Item::factory()->create(['name' => 'Oil', 'unit' => 'litre']);

        Household::factory()->create(['block_id' => $this->blockA->id, 'household_size' => 2]);
        Household::factory()->create(['block_id' => $this->blockA->id, 'household_size' => 5]);
        Household::factory()->create(['block_id' => $this->blockB->id, 'household_size' => 9]);
        Household::factory()->inactive()->create(['block_id' => $this->blockA->id, 'household_size' => 4]);
    }

    private function createCycle(array $blockIds = []): AssistanceCycle
    {
        $this->actingAs($this->coordinator)->post('/cycles', [
            'name' => 'Nov food', 'starts_on' => '2026-11-10', 'ends_on' => '2026-11-20', 'block_ids' => $blockIds,
        ])->assertRedirect();

        return AssistanceCycle::first();
    }

    public function test_only_managers_can_create_cycles(): void
    {
        $this->actingAs(User::factory()->fieldOfficer()->create())->post('/cycles', [
            'name' => 'x', 'starts_on' => '2026-11-10', 'ends_on' => '2026-11-20',
        ])->assertForbidden();
    }

    public function test_end_date_must_not_precede_start_date(): void
    {
        $this->actingAs($this->coordinator)->post('/cycles', [
            'name' => 'x', 'starts_on' => '2026-11-20', 'ends_on' => '2026-11-10',
        ])->assertSessionHasErrors('ends_on');
    }

    public function test_rules_compute_entitlements_by_household_size(): void
    {
        $cycle = $this->createCycle();

        $this->actingAs($this->coordinator)->post("/cycles/{$cycle->id}/rules", [
            'item_id' => $this->rice->id, 'min_household_size' => 1, 'max_household_size' => '',
            'base_quantity' => 0, 'per_member_quantity' => 12, 'max_quantity' => 96,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($this->coordinator)->post("/cycles/{$cycle->id}/rules", [
            'item_id' => $this->oil->id, 'min_household_size' => 1, 'max_household_size' => 4,
            'base_quantity' => 2, 'per_member_quantity' => 0, 'max_quantity' => '',
        ])->assertSessionHasNoErrors();
        $this->actingAs($this->coordinator)->post("/cycles/{$cycle->id}/rules", [
            'item_id' => $this->oil->id, 'min_household_size' => 5, 'max_household_size' => '',
            'base_quantity' => 4, 'per_member_quantity' => 0, 'max_quantity' => '',
        ])->assertSessionHasNoErrors();

        $preview = app(EntitlementPlanner::class)->preview($cycle->fresh());

        $this->assertSame(3, $preview['household_count']); // inactive household excluded
        $byItem = $preview['totals']->keyBy(fn ($t) => $t['item']->id);
        $this->assertEquals(12 * 2 + 12 * 5 + 96, $byItem[$this->rice->id]['quantity']); // 9 members capped at 96
        $this->assertEquals(2 + 4 + 4, $byItem[$this->oil->id]['quantity']);
        $this->assertSame(0, $preview['households_without_entitlement']);
    }

    public function test_overlapping_size_bands_for_the_same_item_are_rejected(): void
    {
        $cycle = $this->createCycle();
        $this->actingAs($this->coordinator)->post("/cycles/{$cycle->id}/rules", [
            'item_id' => $this->oil->id, 'min_household_size' => 1, 'max_household_size' => 4, 'base_quantity' => 2, 'per_member_quantity' => 0,
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->coordinator)->post("/cycles/{$cycle->id}/rules", [
            'item_id' => $this->oil->id, 'min_household_size' => 4, 'max_household_size' => 8, 'base_quantity' => 3, 'per_member_quantity' => 0,
        ])->assertSessionHasErrors('min_household_size');

        $this->assertSame(1, $cycle->rules()->count());
    }

    public function test_rule_needs_a_positive_quantity_and_valid_band(): void
    {
        $cycle = $this->createCycle();
        $this->actingAs($this->coordinator)->post("/cycles/{$cycle->id}/rules", [
            'item_id' => $this->oil->id, 'min_household_size' => 5, 'max_household_size' => 3, 'base_quantity' => 0, 'per_member_quantity' => 0,
        ])->assertSessionHasErrors(['max_household_size', 'base_quantity']);
    }

    public function test_cycle_cannot_be_activated_without_rules(): void
    {
        $cycle = $this->createCycle();
        $this->actingAs($this->coordinator)->post("/cycles/{$cycle->id}/activate", ['confirm' => '1'])
            ->assertSessionHasErrors('rules');
        $this->assertTrue($cycle->fresh()->isDraft());
    }

    public function test_activation_materialises_entitlements_and_locks_rules(): void
    {
        $cycle = $this->createCycle([$this->blockA->id]);
        $this->actingAs($this->coordinator)->post("/cycles/{$cycle->id}/rules", [
            'item_id' => $this->rice->id, 'min_household_size' => 1, 'max_household_size' => '', 'base_quantity' => 0, 'per_member_quantity' => 10,
        ]);

        $this->actingAs($this->coordinator)->post("/cycles/{$cycle->id}/activate", ['confirm' => '1'])->assertRedirect()->assertSessionHasNoErrors();

        $cycle->refresh();
        $this->assertTrue($cycle->isActive());
        $this->assertSame($this->coordinator->id, $cycle->activated_by);
        $this->assertSame(2, $cycle->eligible_household_count); // only block A, active households
        $this->assertSame(2, $cycle->entitlements()->count());
        $this->assertEquals(70, $cycle->entitlements()->sum('quantity')); // 2*10 + 5*10

        // Rules are locked: adding/editing/deleting rules is forbidden now.
        $this->actingAs($this->coordinator)->post("/cycles/{$cycle->id}/rules", [
            'item_id' => $this->oil->id, 'min_household_size' => 1, 'max_household_size' => '', 'base_quantity' => 1, 'per_member_quantity' => 0,
        ])->assertForbidden();
        $rule = $cycle->rules()->first();
        $this->actingAs($this->coordinator)->delete("/cycles/{$cycle->id}/rules/{$rule->id}")->assertForbidden();
        $this->actingAs($this->coordinator)->get("/cycles/{$cycle->id}/edit")->assertForbidden();

        // Cannot activate twice.
        $this->actingAs($this->coordinator)->post("/cycles/{$cycle->id}/activate", ['confirm' => '1'])->assertForbidden();
    }

    public function test_activation_requires_explicit_confirmation(): void
    {
        $cycle = $this->createCycle();
        $this->actingAs($this->coordinator)->post("/cycles/{$cycle->id}/rules", [
            'item_id' => $this->rice->id, 'min_household_size' => 1, 'max_household_size' => '', 'base_quantity' => 1, 'per_member_quantity' => 0,
        ]);
        $this->actingAs($this->coordinator)->post("/cycles/{$cycle->id}/activate")->assertSessionHasErrors('confirm');
        $this->assertTrue($cycle->fresh()->isDraft());
    }

    public function test_active_cycle_can_be_closed_and_draft_can_be_deleted(): void
    {
        $cycle = $this->createCycle();
        $this->actingAs($this->coordinator)->post("/cycles/{$cycle->id}/close")->assertForbidden();

        $this->actingAs($this->coordinator)->delete("/cycles/{$cycle->id}")->assertRedirect('/cycles');
        $this->assertDatabaseCount('assistance_cycles', 0);
    }
}

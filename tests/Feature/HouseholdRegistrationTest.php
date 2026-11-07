<?php

namespace Tests\Feature;

use App\Enums\TokenStatus;
use App\Models\Block;
use App\Models\Household;
use App\Models\Token;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature 1 – Household registration and token issuance.
 */
class HouseholdRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private User $officer;

    private Block $block;

    protected function setUp(): void
    {
        parent::setUp();
        $this->officer = User::factory()->fieldOfficer()->create();
        $this->block = Block::factory()->create();
    }

    /** @return array<string,mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'block_id' => $this->block->id,
            'head_name' => 'Fatema Begum',
            'head_sex' => 'female',
            'head_age' => 34,
            'phone' => '01712345678',
            'household_size' => 5,
            'shelter_number' => 'S-12',
            'consent_given' => '1',
            'issue_token' => '1',
        ], $overrides);
    }

    public function test_registration_with_consent_creates_household_token_and_history(): void
    {
        $response = $this->actingAs($this->officer)->post('/households', $this->payload());

        $household = Household::first();
        $response->assertRedirect("/households/{$household->id}");

        $this->assertSame('HH-000001', $household->code);
        $this->assertTrue($household->consent_given);
        $this->assertNotNull($household->consent_given_at);
        $this->assertSame(Household::CONSENT_VERSION, $household->consent_version);
        $this->assertSame($this->officer->id, $household->registered_by);

        $token = $household->activeToken;
        $this->assertNotNull($token);
        $this->assertStringStartsWith('RG-', $token->token_uid);
        $this->assertSame(TokenStatus::Active, $token->status);

        $events = $household->events()->pluck('event_type')->all();
        $this->assertContains('registered', $events);
        $this->assertContains('consent_recorded', $events);
        $this->assertContains('token_issued', $events);
    }

    public function test_registration_without_consent_is_rejected(): void
    {
        $this->actingAs($this->officer)
            ->from('/households/create')
            ->post('/households', $this->payload(['consent_given' => null]))
            ->assertRedirect('/households/create')
            ->assertSessionHasErrors('consent_given');

        $this->assertDatabaseCount('households', 0);
    }

    public function test_household_size_and_phone_are_validated(): void
    {
        $this->actingAs($this->officer)
            ->post('/households', $this->payload(['household_size' => 0, 'phone' => 'abc']))
            ->assertSessionHasErrors(['household_size', 'phone']);

        $this->actingAs($this->officer)
            ->post('/households', $this->payload(['household_size' => 51]))
            ->assertSessionHasErrors(['household_size']);
    }

    public function test_token_uids_are_opaque_and_unique(): void
    {
        $this->actingAs($this->officer)->post('/households', $this->payload());
        $this->actingAs($this->officer)->post('/households', $this->payload(['head_name' => 'Abdul Karim']));

        $uids = Token::pluck('token_uid');
        $this->assertCount(2, $uids->unique());
        foreach ($uids as $uid) {
            $this->assertStringNotContainsString('Fatema', $uid);
            $this->assertMatchesRegularExpression('/^RG-[A-Z0-9]{20}$/', $uid);
        }
    }

    public function test_a_second_active_token_cannot_be_issued(): void
    {
        $this->actingAs($this->officer)->post('/households', $this->payload());
        $household = Household::first();

        $this->actingAs($this->officer)->post("/households/{$household->id}/tokens")->assertSessionHasErrors('token');
        $this->assertSame(1, $household->tokens()->count());
    }

    public function test_reissue_marks_old_token_lost_and_preserves_history(): void
    {
        $this->actingAs($this->officer)->post('/households', $this->payload());
        $household = Household::first();
        $old = $household->activeToken;

        $this->actingAs($this->officer)->post("/households/{$household->id}/tokens/reissue", [
            'reason' => 'Card lost in flood', 'outcome' => 'lost',
        ])->assertRedirect();

        $household->refresh();
        $old->refresh();
        $new = $household->activeToken;

        $this->assertSame(TokenStatus::Lost, $old->status);
        $this->assertSame('Card lost in flood', $old->invalidation_reason);
        $this->assertNotNull($new);
        $this->assertNotSame($old->id, $new->id);
        $this->assertSame($old->id, $new->replaces_token_id);
        $this->assertSame(2, $household->tokens()->count());
        $this->assertSame(1, $household->tokens()->where('status', 'active')->count());
        $this->assertTrue($household->events()->where('event_type', 'token_reissued')->exists());
    }

    public function test_deactivation_revokes_the_active_token_and_blocks_token_issue(): void
    {
        $this->actingAs($this->officer)->post('/households', $this->payload());
        $household = Household::first();

        $this->actingAs($this->officer)->post("/households/{$household->id}/deactivate", ['reason' => 'Relocated'])->assertRedirect();

        $household->refresh();
        $this->assertFalse($household->isActive());
        $this->assertSame('Relocated', $household->deactivation_reason);
        $this->assertNull($household->activeToken);
        $this->assertSame(TokenStatus::Revoked, $household->tokens()->first()->status);

        $this->actingAs($this->officer)->post("/households/{$household->id}/tokens")->assertForbidden();
    }

    public function test_only_managers_can_reactivate_and_a_new_token_is_then_issued(): void
    {
        $this->actingAs($this->officer)->post('/households', $this->payload());
        $household = Household::first();
        $this->actingAs($this->officer)->post("/households/{$household->id}/deactivate", ['reason' => 'Relocated']);

        $this->actingAs($this->officer)->post("/households/{$household->id}/reactivate")->assertForbidden();

        $coordinator = User::factory()->coordinator()->create();
        $this->actingAs($coordinator)->post("/households/{$household->id}/reactivate")->assertRedirect();
        $this->assertTrue($household->fresh()->isActive());

        $this->actingAs($this->officer)->post("/households/{$household->id}/tokens")->assertRedirect();
        $this->assertNotNull($household->fresh()->activeToken);
    }

    public function test_update_records_changed_fields_in_history(): void
    {
        $this->actingAs($this->officer)->post('/households', $this->payload());
        $household = Household::first();

        $this->actingAs($this->officer)->put("/households/{$household->id}", $this->payload(['household_size' => 6, 'phone' => '01799999999']))
            ->assertRedirect("/households/{$household->id}");

        $event = $household->events()->where('event_type', 'updated')->first();
        $this->assertNotNull($event);
        $this->assertArrayHasKey('household_size', $event->changes);
        $this->assertSame(6, $household->fresh()->household_size);
    }

    public function test_token_lookup_finds_household(): void
    {
        $this->actingAs($this->officer)->post('/households', $this->payload());
        $token = Token::first();

        $this->actingAs($this->officer)->post('/tokens/lookup', ['token_uid' => $token->token_uid])
            ->assertRedirect("/households/{$token->household_id}");
        $this->actingAs($this->officer)->post('/tokens/lookup', ['token_uid' => 'RG-DOESNOTEXIST'])
            ->assertSessionHasErrors('token_uid');
    }

    public function test_token_card_renders_qr_payload(): void
    {
        $this->actingAs($this->officer)->post('/households', $this->payload());
        $token = Token::first();

        $this->actingAs($this->officer)->get("/tokens/{$token->id}/card")
            ->assertOk()
            ->assertSee('data-qr="'.$token->token_uid.'"', false)
            ->assertDontSee('Fatema Begum');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/blocks')->assertRedirect('/login');
    }

    public function test_only_admins_can_open_user_management(): void
    {
        $this->actingAs(User::factory()->fieldOfficer()->create())->get('/admin/users')->assertForbidden();
        $this->actingAs(User::factory()->coordinator()->create())->get('/admin/users')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/admin/users')->assertOk();
    }

    public function test_field_officer_cannot_manage_blocks(): void
    {
        $block = Block::factory()->create();
        $this->actingAs(User::factory()->fieldOfficer()->create())
            ->get("/blocks/{$block->id}/edit")->assertForbidden();
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_user_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $user->update(['is_active' => false]);

        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_admin_can_change_a_users_role_but_not_deactivate_self(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->fieldOfficer()->create();

        $this->actingAs($admin)->put("/admin/users/{$target->id}", [
            'name' => $target->name, 'email' => $target->email, 'role' => 'coordinator',
        ])->assertRedirect('/admin/users');
        $this->assertSame('coordinator', $target->fresh()->role->value);

        $this->actingAs($admin)->patch("/admin/users/{$admin->id}/toggle-active")->assertForbidden();
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_self_registration_creates_a_field_officer(): void
    {
        $this->post('/register', [
            'name' => 'New Staff', 'email' => 'new@reliefgrid.test',
            'password' => 'password-123', 'password_confirmation' => 'password-123',
        ])->assertRedirect('/dashboard');

        $this->assertSame('field_officer', User::where('email', 'new@reliefgrid.test')->first()->role->value);
    }
}

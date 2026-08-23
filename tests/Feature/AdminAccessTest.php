<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/admin');
    }

    public function test_regular_user_cannot_access_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/dashboard')->assertForbidden();
    }

    public function test_admin_can_access_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get('/admin/dashboard')->assertOk();
    }

    public function test_authenticated_admin_visiting_admin_root_is_redirected_to_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_link_is_only_visible_to_authenticated_administrators(): void
    {
        $this->get('/')->assertDontSee('>Admin</a>', false);

        $regularUser = User::factory()->create(['is_admin' => false]);
        $this->actingAs($regularUser)->get('/')->assertDontSee('>Admin</a>', false);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)
            ->get('/')
            ->assertSee(route('admin.dashboard'))
            ->assertSee('>Admin</a>', false);
    }

    public function test_admin_layout_has_accessible_mobile_menu(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('data-admin-menu-toggle', false)
            ->assertSee('aria-controls="admin-navigation"', false)
            ->assertSee('data-admin-mobile-menu', false);
    }

    public function test_public_and_admin_layouts_use_the_simplified_favicon(): void
    {
        $this->get('/')->assertOk()->assertSee('favicon.svg');

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertSee('favicon.svg');
    }
}

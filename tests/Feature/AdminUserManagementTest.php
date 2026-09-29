<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtSchedule;
use App\Models\Membership;
use App\Models\PointHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected User $vipCustomer;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);

        $this->admin = User::factory()->create([
            'name' => 'Admin Gelanggang',
            'email' => 'admin@smasharena.com',
            'phone' => '081234567890',
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');

        $this->customer = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '089876543210',
            'is_active' => true,
        ]);
        $this->customer->assignRole('user');

        $this->vipCustomer = User::factory()->create([
            'name' => 'Siti Rahma',
            'email' => 'siti@example.com',
            'phone' => '081122334455',
            'is_active' => true,
        ]);
        $this->vipCustomer->assignRole('user');

        // Upgrade vipCustomer to Gold
        $this->vipCustomer->membership()->update([
            'points' => 350,
            'tier' => 'gold',
        ]);
    }

    public function test_admin_can_view_users_index_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->has('users.data')
                ->has('summary')
                ->has('filters')
                ->where('summary.total_users', 3)
                ->where('summary.admin_count', 1)
                ->where('summary.gold_members', 1)
            );
    }

    public function test_customer_cannot_access_users_management_page(): void
    {
        $response = $this->actingAs($this->customer)->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.users.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_filter_users_by_search_query(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.index', [
            'search' => 'Budi',
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Budi Santoso')
            );
    }

    public function test_admin_can_filter_users_by_tier(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.index', [
            'tier' => 'gold',
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Siti Rahma')
                ->where('users.data.0.tier', 'gold')
            );
    }

    public function test_admin_can_filter_users_by_role(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.index', [
            'role' => 'admin',
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Admin Gelanggang')
            );
    }

    public function test_admin_can_view_user_detail_json(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('admin.users.show', $this->vipCustomer->id));

        $response->assertOk()
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'is_active',
                    'role',
                    'tier',
                    'points',
                    'discount_percentage',
                    'created_at',
                    'bookings_count',
                    'total_spent',
                    'recent_bookings',
                    'recent_point_histories',
                ],
            ]);

        $this->assertEquals('Siti Rahma', $response->json('user.name'));
        $this->assertEquals('gold', $response->json('user.tier'));
        $this->assertEquals(350, $response->json('user.points'));
        $this->assertEquals(10, $response->json('user.discount_percentage'));
    }

    public function test_admin_can_promote_user_to_admin(): void
    {
        $response = $this->actingAs($this->admin)->patch(route('admin.users.update-role', $this->customer->id), [
            'role' => 'admin',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue($this->customer->fresh()->hasRole('admin'));
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $response = $this->actingAs($this->admin)->patch(route('admin.users.update-role', $this->admin->id), [
            'role' => 'user',
        ]);

        $response->assertSessionHas('error');
        $this->assertTrue($this->admin->fresh()->hasRole('admin'));
    }

    public function test_admin_can_toggle_user_status(): void
    {
        // Deactivate
        $resDeactivate = $this->actingAs($this->admin)->patch(route('admin.users.toggle-status', $this->customer->id));
        $resDeactivate->assertSessionHas('success');
        $this->assertFalse($this->customer->fresh()->is_active);

        // Re-activate
        $resActivate = $this->actingAs($this->admin)->patch(route('admin.users.toggle-status', $this->customer->id));
        $resActivate->assertSessionHas('success');
        $this->assertTrue($this->customer->fresh()->is_active);
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $response = $this->actingAs($this->admin)->patch(route('admin.users.toggle-status', $this->admin->id));

        $response->assertSessionHas('error');
        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->customer->update(['is_active' => false]);

        $response = $this->post(route('login'), [
            'email' => $this->customer->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_can_adjust_loyalty_points_and_auto_upgrade_tier(): void
    {
        // Customer starts with 0 points (bronze)
        $this->assertEquals(0, $this->customer->membership->points);
        $this->assertEquals('bronze', $this->customer->membership->tier);

        // Admin adds 150 points (should promote to silver)
        $response = $this->actingAs($this->admin)->post(route('admin.users.adjust-points', $this->customer->id), [
            'type' => 'add',
            'points' => 150,
            'description' => 'Reward partisipasi event',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->customer->refresh();
        $this->assertEquals(150, $this->customer->membership->points);
        $this->assertEquals('silver', $this->customer->membership->tier);

        // Point history created
        $this->assertDatabaseHas('point_histories', [
            'user_id' => $this->customer->id,
            'points_earned' => 150,
            'description' => 'Penyesuaian Admin (+): Reward partisipasi event',
        ]);
    }

    public function test_admin_can_subtract_loyalty_points(): void
    {
        // VIP customer has 350 points (gold)
        $response = $this->actingAs($this->admin)->post(route('admin.users.adjust-points', $this->vipCustomer->id), [
            'type' => 'subtract',
            'points' => 200, // Remaining 150 points (tier becomes silver)
            'description' => 'Koreksi poin',
        ]);

        $response->assertRedirect();
        $this->vipCustomer->refresh();

        $this->assertEquals(150, $this->vipCustomer->membership->points);
        $this->assertEquals('silver', $this->vipCustomer->membership->tier);

        $this->assertDatabaseHas('point_histories', [
            'user_id' => $this->vipCustomer->id,
            'points_used' => 200,
            'description' => 'Penyesuaian Admin (-): Koreksi poin',
        ]);
    }

    public function test_admin_can_delete_user(): void
    {
        $userToDelete = User::factory()->create(['name' => 'User Hapus']);
        $userToDelete->assignRole('user');

        $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $userToDelete->id));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $userToDelete->id]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $this->admin->id));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }
}

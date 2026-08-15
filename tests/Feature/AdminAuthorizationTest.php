<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_route(): void
    {
        $response = $this->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(401);
    }

    public function test_regular_user_cannot_access_admin_route(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_route(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard');

        $response
            ->assertStatus(200)
            ->assertJson([
                'users_count' => 1,
                'items_count' => 0,
                'categories_count' => 0,
            ]);
    }
}

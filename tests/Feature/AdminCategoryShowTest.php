<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson(
            "/api/v1/admin/categories/{$category->id}",
        );

        $response->assertStatus(401);
    }

    public function test_regular_user_cannot_view_category(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $category = Category::factory()->create();

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson(
                "/api/v1/admin/categories/{$category->id}",
            );

        $response->assertStatus(403);
    }

    public function test_admin_can_view_category(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = Category::factory()->create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson(
                "/api/v1/admin/categories/{$category->id}",
            );

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'slug',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $category->id,
                    'name' => 'Test Category',
                    'slug' => 'test-category',
                ],
            ]);
    }

    public function test_admin_cannot_view_nonexistent_category(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/categories/999999');

        $response->assertStatus(404);
    }
}

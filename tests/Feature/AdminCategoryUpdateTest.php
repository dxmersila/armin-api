<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_update_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->putJson(
            "/api/v1/admin/categories/{$category->id}",
            [
                'name' => 'Updated Category',
            ],
        );

        $response->assertStatus(401);
    }

    public function test_regular_user_cannot_update_category(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $category = Category::factory()->create();

        $response = $this
            ->actingAs($user, 'sanctum')
            ->putJson(
                "/api/v1/admin/categories/{$category->id}",
                [
                    'name' => 'Updated Category',
                ],
            );

        $response->assertStatus(403);
    }

    public function test_admin_can_update_category(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = Category::factory()->create([
            'name' => 'Original Category',
            'slug' => 'original-category',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->putJson(
                "/api/v1/admin/categories/{$category->id}",
                [
                    'name' => 'Updated Category',
                    'slug' => 'updated-category',
                ],
            );

        $response
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $category->id,
                    'name' => 'Updated Category',
                    'slug' => 'updated-category',
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Category',
            'slug' => 'updated-category',
        ]);
    }

    public function test_admin_can_keep_existing_slug(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = Category::factory()->create([
            'name' => 'Original Category',
            'slug' => 'original-category',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->putJson(
                "/api/v1/admin/categories/{$category->id}",
                [
                    'name' => 'Updated Category',
                    'slug' => 'original-category',
                ],
            );

        $response
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $category->id,
                    'name' => 'Updated Category',
                    'slug' => 'original-category',
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Category',
            'slug' => 'original-category',
        ]);
    }

    public function test_admin_cannot_update_category_with_duplicate_slug(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = Category::factory()->create([
            'name' => 'Original Category',
            'slug' => 'original-category',
        ]);

        Category::factory()->create([
            'name' => 'Another Category',
            'slug' => 'another-category',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->putJson(
                "/api/v1/admin/categories/{$category->id}",
                [
                    'slug' => 'another-category',
                ],
            );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'slug',
            ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Original Category',
            'slug' => 'original-category',
        ]);
    }
}

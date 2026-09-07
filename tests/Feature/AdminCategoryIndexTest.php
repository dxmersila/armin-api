<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_categories(): void
    {
        $response = $this->getJson('/api/v1/admin/categories');

        $response->assertStatus(401);
    }

    public function test_regular_user_cannot_list_categories(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/categories');

        $response->assertStatus(403);
    }

    public function test_admin_can_list_categories(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Category::factory()->count(3)->create();

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/categories');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                    ],
                ],
                'links',
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total',
                ],
            ])
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_admin_can_search_categories_by_name_or_slug(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $nameMatch = Category::factory()->create([
            'name' => 'Laravel Development',
            'slug' => 'laravel-development',
        ]);

        $slugMatch = Category::factory()->create([
            'name' => 'React Development',
            'slug' => 'laravel-react-development',
        ]);

        Category::factory()->create([
            'name' => 'Unrelated Category',
            'slug' => 'unrelated-category',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/categories?search=laravel');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $nameMatch->id,
                'name' => 'Laravel Development',
            ])
            ->assertJsonFragment([
                'id' => $slugMatch->id,
                'name' => 'React Development',
            ]);
    }

    public function test_admin_can_sort_categories_by_latest(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $oldest = Category::factory()->create([
            'created_at' => now()->subDays(2),
        ]);

        $newest = Category::factory()->create([
            'created_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/categories?sort=latest');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $newest->id)
            ->assertJsonPath('data.1.id', $oldest->id);
    }

    public function test_admin_can_sort_categories_by_oldest(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $oldest = Category::factory()->create([
            'created_at' => now()->subDays(2),
        ]);

        $newest = Category::factory()->create([
            'created_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/categories?sort=oldest');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $oldest->id)
            ->assertJsonPath('data.1.id', $newest->id);
    }

    public function test_admin_can_sort_categories_by_name(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $zulu = Category::factory()->create([
            'name' => 'Zulu',
            'slug' => 'zulu',
        ]);

        $alpha = Category::factory()->create([
            'name' => 'Alpha',
            'slug' => 'alpha',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/categories?sort=name');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $alpha->id)
            ->assertJsonPath('data.1.id', $zulu->id);
    }

    public function test_admin_category_index_rejects_invalid_filter_values(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson(
                '/api/v1/admin/categories'
                . '?search=' . str_repeat('a', 101)
                . '&sort=invalid'
            );

        $response->assertStatus(422);
    }

    public function test_admin_categories_are_paginated_by_fifteen(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Category::factory()->count(16)->create();

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/categories');

        $response
            ->assertStatus(200)
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 16)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_admin_categories_are_ordered_by_name_then_id(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $firstSameName = Category::factory()->create([
            'name' => 'Alpha',
            'slug' => 'alpha-one',
        ]);

        $secondSameName = Category::factory()->create([
            'name' => 'Alpha',
            'slug' => 'alpha-two',
        ]);

        $beta = Category::factory()->create([
            'name' => 'Beta',
            'slug' => 'beta',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/categories');

        $response
            ->assertStatus(200)
            ->assertJsonPath('data.0.id', $firstSameName->id)
            ->assertJsonPath('data.0.name', 'Alpha')
            ->assertJsonPath('data.1.id', $secondSameName->id)
            ->assertJsonPath('data.1.name', 'Alpha')
            ->assertJsonPath('data.2.id', $beta->id)
            ->assertJsonPath('data.2.name', 'Beta');
    }
}

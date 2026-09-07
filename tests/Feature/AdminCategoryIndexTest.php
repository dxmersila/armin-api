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

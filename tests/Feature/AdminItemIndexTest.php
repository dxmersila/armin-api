<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminItemIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_admin_items(): void
    {
        $response = $this->getJson('/api/v1/admin/items');

        $response->assertStatus(401);
    }

    public function test_regular_user_cannot_view_admin_items(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/items');

        $response->assertStatus(403);
    }

    public function test_admin_can_view_all_items(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Item::factory()->create([
            'title' => 'Visible Item',
            'is_visible' => true,
        ]);

        Item::factory()->create([
            'title' => 'Hidden Item',
            'is_visible' => false,
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/items');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'description',
                        'is_visible',
                        'categories',
                        'tags',
                        'media',
                    ],
                ],
                'links',
                'meta',
            ]);

        $response->assertJsonFragment([
            'title' => 'Visible Item',
        ]);

        $response->assertJsonFragment([
            'title' => 'Hidden Item',
        ]);
    }

    public function test_admin_items_are_paginated(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Item::factory()->count(20)->create();

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/items');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        $this->assertCount(15, $response->json('data'));
    }
}

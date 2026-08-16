<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminItemShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_admin_item(): void
    {
        $item = Item::factory()->create();

        $response = $this->getJson(
            "/api/v1/admin/items/{$item->id}"
        );

        $response->assertStatus(401);
    }

    public function test_regular_user_cannot_view_admin_item(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $item = Item::factory()->create();

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/v1/admin/items/{$item->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_view_item(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $item = Item::factory()->create([
            'title' => 'Admin Test Item',
            'is_visible' => true,
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/items/{$item->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'description',
                    'is_visible',
                    'categories',
                    'tags',
                    'media',
                ],
            ])
            ->assertJsonPath('data.id', $item->id)
            ->assertJsonPath('data.title', 'Admin Test Item')
            ->assertJsonPath('data.is_visible', true);
    }

    public function test_admin_can_view_hidden_item(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $item = Item::factory()->create([
            'title' => 'Hidden Admin Item',
            'is_visible' => false,
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/items/{$item->id}");

        $response
            ->assertStatus(200)
            ->assertJsonPath('data.id', $item->id)
            ->assertJsonPath('data.title', 'Hidden Admin Item')
            ->assertJsonPath('data.is_visible', false);
    }

    public function test_non_existing_item_returns_404(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/items/999999');

        $response->assertStatus(404);
    }
}

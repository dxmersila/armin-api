<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminItemCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_create_item(): void
    {
        $response = $this->postJson('/api/v1/admin/items', [
            'title' => 'Test Item',
        ]);

        $response->assertStatus(401);
    }

    public function test_regular_user_cannot_create_item(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/v1/admin/items', [
                'title' => 'Test Item',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_create_item(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/items', [
                'title' => 'Test Item',
                'description' => 'Test item description.',
                'content' => 'Test item protected content.',
                'categories' => [$category->id],
                'tags' => [$tag->id],
            ]);

        $response
            ->assertStatus(201)
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
            ->assertJson([
                'data' => [
                    'title' => 'Test Item',
                    'slug' => 'test-item',
                    'description' => 'Test item description.',
                    'content' => 'Test item protected content.',
                    'is_visible' => false,
                ],
            ]);

        $item = Item::where('title', 'Test Item')->firstOrFail();

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'title' => 'Test Item',
            'slug' => 'test-item',
            'content' => 'Test item protected content.',
            'is_visible' => false,
        ]);

        $this->assertDatabaseHas('category_item', [
            'item_id' => $item->id,
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('item_tag', [
            'item_id' => $item->id,
            'tag_id' => $tag->id,
        ]);
    }
}

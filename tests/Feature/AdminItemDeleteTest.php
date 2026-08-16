<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminItemDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_delete_item(): void
    {
        $item = Item::factory()->create();

        $response = $this->deleteJson(
            "/api/v1/admin/items/{$item->id}"
        );

        $response->assertStatus(401);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
        ]);
    }

    public function test_regular_user_cannot_delete_item(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $item = Item::factory()->create();

        $response = $this
            ->actingAs($user)
            ->deleteJson("/api/v1/admin/items/{$item->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
        ]);
    }

    public function test_admin_can_delete_item(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $item = Item::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->deleteJson("/api/v1/admin/items/{$item->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('items', [
            'id' => $item->id,
        ]);
    }

    public function test_deleting_item_removes_category_relationships(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $item = Item::factory()->create();

        $categoryOne = Category::factory()->create();
        $categoryTwo = Category::factory()->create();

        $item->categories()->attach([
            $categoryOne->id,
            $categoryTwo->id,
        ]);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/admin/items/{$item->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('category_item', [
            'item_id' => $item->id,
            'category_id' => $categoryOne->id,
        ]);

        $this->assertDatabaseMissing('category_item', [
            'item_id' => $item->id,
            'category_id' => $categoryTwo->id,
        ]);
    }

    public function test_deleting_item_removes_tag_relationships(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $item = Item::factory()->create();

        $tagOne = Tag::factory()->create();
        $tagTwo = Tag::factory()->create();

        $item->tags()->attach([
            $tagOne->id,
            $tagTwo->id,
        ]);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/admin/items/{$item->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('item_tag', [
            'item_id' => $item->id,
            'tag_id' => $tagOne->id,
        ]);

        $this->assertDatabaseMissing('item_tag', [
            'item_id' => $item->id,
            'tag_id' => $tagTwo->id,
        ]);
    }

    public function test_non_existing_item_returns_404(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this
            ->actingAs($admin)
            ->deleteJson('/api/v1/admin/items/999999');

        $response->assertStatus(404);
    }
}

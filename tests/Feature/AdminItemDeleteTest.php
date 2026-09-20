<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Tag;
use App\Models\User;
use App\Models\File;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;
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

    public function test_admin_cannot_delete_item_with_order_history(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
        ]);

        $order = \App\Models\Order::factory()->create([
            'user_id' => $user->id,
        ]);

        \App\Models\OrderItem::factory()->create([
            'order_id' => $order->id,
            'item_id' => $item->id,
            'price' => $item->price,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/items/{$item->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'item_id' => $item->id,
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

    public function test_deleting_item_removes_media_and_unused_file(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $item = Item::factory()->create();

        $file = File::create([
            'name' => 'test.jpg',
            'path' => 'media/test.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1000,
        ]);

        Storage::disk('public')->put(
            'media/test.jpg',
            'dummy content'
        );

        $media = Media::create([
            'item_id' => $item->id,
            'file_id' => $file->id,
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/items/{$item->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('items', [
            'id' => $item->id,
        ]);

        $this->assertDatabaseMissing('media', [
            'id' => $media->id,
        ]);

        $this->assertDatabaseMissing('files', [
            'id' => $file->id,
        ]);

        Storage::disk('public')->assertMissing('media/test.jpg');
    }

    public function test_deleting_item_does_not_remove_file_still_used_by_other_media(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $item = Item::factory()->create();
        $anotherItem = Item::factory()->create();

        $file = File::create([
            'name' => 'shared.jpg',
            'path' => 'media/shared.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1000,
        ]);

        Storage::disk('public')->put(
            'media/shared.jpg',
            'dummy content'
        );

        $media = Media::create([
            'item_id' => $item->id,
            'file_id' => $file->id,
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $anotherMedia = Media::create([
            'item_id' => $anotherItem->id,
            'file_id' => $file->id,
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/items/{$item->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('media', [
            'id' => $media->id,
        ]);

        $this->assertDatabaseHas('media', [
            'id' => $anotherMedia->id,
            'file_id' => $file->id,
        ]);

        $this->assertDatabaseHas('files', [
            'id' => $file->id,
        ]);

        Storage::disk('public')->assertExists('media/shared.jpg');
    }
}

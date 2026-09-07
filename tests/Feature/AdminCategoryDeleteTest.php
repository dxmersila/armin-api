<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_delete_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->deleteJson(
            "/api/v1/admin/categories/{$category->id}",
        );

        $response->assertStatus(401);
    }

    public function test_regular_user_cannot_delete_category(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $category = Category::factory()->create();

        $response = $this
            ->actingAs($user, 'sanctum')
            ->deleteJson(
                "/api/v1/admin/categories/{$category->id}",
            );

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_category(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = Category::factory()->create();

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->deleteJson(
                "/api/v1/admin/categories/{$category->id}",
            );

        $response->assertStatus(204);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_deleting_category_removes_pivot_but_keeps_item(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = Category::factory()->create();
        $item = Item::factory()->create();

        $item->categories()->attach($category);

        $this->assertDatabaseHas('category_item', [
            'category_id' => $category->id,
            'item_id' => $item->id,
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->deleteJson(
                "/api/v1/admin/categories/{$category->id}",
            );

        $response->assertStatus(204);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);

        $this->assertDatabaseMissing('category_item', [
            'category_id' => $category->id,
            'item_id' => $item->id,
        ]);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
        ]);
    }

    public function test_deleting_nonexistent_category_returns_not_found(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/admin/categories/999999');

        $response->assertStatus(404);
    }
}

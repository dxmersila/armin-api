<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminItemUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_update_item(): void
    {
        $item = Item::factory()->create();

        $response = $this->putJson(
            "/api/v1/admin/items/{$item->id}",
            [
                'title' => 'Updated Title',
            ]
        );

        $response->assertStatus(401);
    }

    public function test_regular_user_cannot_update_item(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $item = Item::factory()->create();

        $response = $this
            ->actingAs($user, 'sanctum')
            ->putJson(
                "/api/v1/admin/items/{$item->id}",
                [
                    'title' => 'Updated Title',
                ]
            );

        $response->assertStatus(403);
    }

    public function test_admin_can_update_item(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $item = Item::factory()->create([
            'title' => 'Updated Title',
            'slug' => 'updated-title',
            'description' => 'Updated description',
            'is_visible' => true,
            'price' => 500000,
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->putJson(
                "/api/v1/admin/items/{$item->id}",
                [
                    'title' => 'Updated Title',
                    'slug' => 'updated-title',
                    'description' => 'Updated description',
                    'content' => 'Updated protected content',
                    'is_visible' => true,
                ]
            );

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'description',
                    'content',
                    'is_visible',
                    'categories',
                    'tags',
                    'media',
                    'price',
                ],
            ]);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'title' => 'Updated Title',
            'slug' => 'updated-title',
            'description' => 'Updated description',
            'content' => 'Updated protected content',
            'is_visible' => true,
        ]);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'price' => 500000,
        ]);
    }

    public function test_admin_can_update_categories_and_tags(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $item = Item::factory()->create();

        $category = Category::factory()->create();

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->putJson(
                "/api/v1/admin/items/{$item->id}",
                [
                    'categories' => [$category->id],
                    'tags' => ['Laravel'],
                ]
            );

        $response->assertStatus(200);

        $this->assertDatabaseHas('category_item', [
            'item_id' => $item->id,
            'category_id' => $category->id,
        ]);

        $tag = Tag::query()
            ->where('name', 'Laravel')
            ->firstOrFail();

        $this->assertDatabaseHas('item_tag', [
            'item_id' => $item->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_admin_can_update_only_selected_fields_without_changing_other_fields(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $item = Item::factory()->create([
            'title' => 'Original Title',
            'slug' => 'original-slug',
            'description' => 'Original description',
            'is_visible' => true,
        ]);

        $originalSlug = $item->slug;

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->putJson(
                "/api/v1/admin/items/{$item->id}",
                [
                    'title' => 'Only Title Changed',
                ]
            );

        $response->assertStatus(200);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'title' => 'Only Title Changed',
            'slug' => $originalSlug,
            'description' => 'Original description',
            'is_visible' => true,
        ]);
    }

    public function test_admin_can_replace_categories_and_tags_completely(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $item = Item::factory()->create();

        $categoryOne = Category::factory()->create();
        $categoryTwo = Category::factory()->create();
        $categoryThree = Category::factory()->create();

        $tagOne = Tag::factory()->create([
            'name' => 'Laravel',
            'slug' => 'laravel',
        ]);

        $tagTwo = Tag::factory()->create([
            'name' => 'API',
            'slug' => 'api',
        ]);

        $tagThree = Tag::factory()->create([
            'name' => 'SaaS',
            'slug' => 'saas',
        ]);

        $item->categories()->sync([
            $categoryOne->id,
            $categoryTwo->id,
        ]);

        $item->tags()->sync([
            $tagOne->id,
            $tagTwo->id,
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->putJson(
                "/api/v1/admin/items/{$item->id}",
                [
                    'categories' => [$categoryThree->id],
                    'tags' => ['SaaS'],
                ]
            );

        $response->assertStatus(200);

        $this->assertDatabaseHas('category_item', [
            'item_id' => $item->id,
            'category_id' => $categoryThree->id,
        ]);

        $this->assertDatabaseMissing('category_item', [
            'item_id' => $item->id,
            'category_id' => $categoryOne->id,
        ]);

        $this->assertDatabaseMissing('category_item', [
            'item_id' => $item->id,
            'category_id' => $categoryTwo->id,
        ]);

        $this->assertDatabaseHas('item_tag', [
            'item_id' => $item->id,
            'tag_id' => $tagThree->id,
        ]);

        $this->assertDatabaseMissing('item_tag', [
            'item_id' => $item->id,
            'tag_id' => $tagOne->id,
        ]);

        $this->assertDatabaseMissing('item_tag', [
            'item_id' => $item->id,
            'tag_id' => $tagTwo->id,
        ]);
    }

    public function test_admin_cannot_update_item_with_duplicate_slug(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $existingItem = Item::factory()->create([
            'slug' => 'existing-slug',
        ]);

        $item = Item::factory()->create([
            'slug' => 'original-slug',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->putJson(
                "/api/v1/admin/items/{$item->id}",
                [
                    'slug' => $existingItem->slug,
                ]
            );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'slug',
            ]);
    }

    public function test_admin_can_keep_own_slug(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $item = Item::factory()->create([
            'slug' => 'my-own-slug',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->putJson(
                "/api/v1/admin/items/{$item->id}",
                [
                    'title' => 'Updated Title',
                    'slug' => 'my-own-slug',
                ]
            );

        $response->assertStatus(200);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'slug' => 'my-own-slug',
            'title' => 'Updated Title',
        ]);
    }

    public function test_admin_can_update_price(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $item = Item::factory()->create([
            'price' => 100000,
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->putJson(
                "/api/v1/admin/items/{$item->id}",
                [
                    'price' => 250000,
                ]
            );

        $response->assertStatus(200);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'price' => 250000,
        ]);
    }

    public function test_non_existing_item_returns_404(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->putJson(
                '/api/v1/admin/items/999999',
                [
                    'title' => 'Updated Title',
                ]
            );

        $response->assertStatus(404);
    }
}

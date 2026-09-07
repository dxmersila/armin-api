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

    public function test_admin_can_search_items_by_title_or_slug(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $titleMatch = Item::factory()->create([
            'title' => 'Laravel Administration Guide',
            'slug' => 'laravel-admin-guide',
        ]);

        $slugMatch = Item::factory()->create([
            'title' => 'React Dashboard',
            'slug' => 'laravel-react-dashboard',
        ]);

        Item::factory()->create([
            'title' => 'Unrelated Item',
            'slug' => 'unrelated-item',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/items?search=laravel');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $titleMatch->id,
                'title' => 'Laravel Administration Guide',
            ])
            ->assertJsonFragment([
                'id' => $slugMatch->id,
                'title' => 'React Dashboard',
            ])
            ->assertJsonMissing([
                'id' => Item::query()
                    ->where('title', 'Unrelated Item')
                    ->value('id'),
            ]);
    }

    public function test_admin_search_does_not_search_description(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Item::factory()->create([
            'title' => 'Visible Result',
            'slug' => 'visible-result',
            'description' => 'Contains the secret search phrase',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/items?search=secret');

        $response
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_admin_can_filter_items_by_visibility(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $visibleItem = Item::factory()->create([
            'title' => 'Visible Item',
            'is_visible' => true,
        ]);

        Item::factory()->create([
            'title' => 'Hidden Item',
            'is_visible' => false,
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/items?is_visible=1');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visibleItem->id);
    }

    public function test_admin_can_filter_items_by_hidden_visibility(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Item::factory()->create([
            'title' => 'Visible Item',
            'is_visible' => true,
        ]);

        $hiddenItem = Item::factory()->create([
            'title' => 'Hidden Item',
            'is_visible' => false,
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/items?is_visible=0');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $hiddenItem->id);
    }

    public function test_admin_can_filter_items_by_access_type(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $freeItem = Item::factory()->create([
            'title' => 'Free Item',
            'access_type' => 'free',
            'price' => 0,
        ]);

        Item::factory()->create([
            'title' => 'Paid Item',
            'access_type' => 'paid',
            'price' => 100,
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/items?access_type=free');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $freeItem->id);
    }

    public function test_admin_can_filter_items_by_paid_access_type(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Item::factory()->create([
            'title' => 'Free Item',
            'access_type' => 'free',
            'price' => 0,
        ]);

        $paidItem = Item::factory()->create([
            'title' => 'Paid Item',
            'access_type' => 'paid',
            'price' => 100,
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/items?access_type=paid');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $paidItem->id);
    }

    public function test_admin_can_filter_items_by_category(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = \App\Models\Category::factory()->create();

        $matchingItem = Item::factory()->create([
            'title' => 'Matching Item',
        ]);

        $otherItem = Item::factory()->create([
            'title' => 'Other Item',
        ]);

        $matchingItem->categories()->attach($category);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson(
                '/api/v1/admin/items?category_id=' . $category->id
            );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingItem->id);

        $this->assertNotSame(
            $matchingItem->id,
            $otherItem->id
        );
    }

    public function test_admin_can_sort_items_by_oldest(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $oldest = Item::factory()->create([
            'created_at' => now()->subDays(2),
        ]);

        $newest = Item::factory()->create([
            'created_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/items?sort=oldest');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $oldest->id)
            ->assertJsonPath('data.1.id', $newest->id);
    }

    public function test_admin_can_sort_items_by_title(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $zuluItem = Item::factory()->create([
            'title' => 'Zulu Item',
        ]);

        $alphaItem = Item::factory()->create([
            'title' => 'Alpha Item',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/items?sort=title');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $alphaItem->id)
            ->assertJsonPath('data.1.id', $zuluItem->id);
    }

    public function test_admin_can_combine_item_filters(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = \App\Models\Category::factory()->create();

        $matchingItem = Item::factory()->create([
            'title' => 'Laravel Paid Course',
            'slug' => 'laravel-paid-course',
            'is_visible' => true,
            'access_type' => 'paid',
            'price' => 100,
        ]);

        $matchingItem->categories()->attach($category);

        Item::factory()->create([
            'title' => 'Laravel Free Course',
            'slug' => 'laravel-free-course',
            'is_visible' => true,
            'access_type' => 'free',
            'price' => 0,
        ]);

        Item::factory()->create([
            'title' => 'Laravel Hidden Course',
            'slug' => 'laravel-hidden-course',
            'is_visible' => false,
            'access_type' => 'paid',
            'price' => 100,
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson(
                '/api/v1/admin/items'
                . '?search=laravel'
                . '&is_visible=1'
                . '&access_type=paid'
                . '&category_id=' . $category->id
            );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingItem->id);
    }

    public function test_admin_item_index_rejects_invalid_filter_values(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson(
                '/api/v1/admin/items'
                . '?is_visible=invalid'
                . '&access_type=invalid'
                . '&category_id=999999'
                . '&sort=invalid'
            );

        $response->assertStatus(422);
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

    public function test_admin_items_are_deterministically_ordered_by_id_when_created_at_matches(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $createdAt = now();

        $firstItem = Item::factory()->create([
            'created_at' => $createdAt,
        ]);

        $secondItem = Item::factory()->create([
            'created_at' => $createdAt,
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/items');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $secondItem->id)
            ->assertJsonPath('data.1.id', $firstItem->id);
    }
}

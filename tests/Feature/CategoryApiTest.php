<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_endpoint_returns_paginated_categories(): void
    {
        Category::factory()->count(15)->create();

        $response = $this->getJson('/api/v1/categories');

        $response
            ->assertStatus(200)
            ->assertJsonCount(12, 'data')
            ->assertJsonPath('meta.per_page', 12);
    }

    public function test_categories_endpoint_sorts_by_name(): void
    {
        Category::factory()->create([
            'name' => 'Zebra',
            'slug' => 'zebra',
        ]);

        Category::factory()->create([
            'name' => 'Alpha',
            'slug' => 'alpha',
        ]);

        $response = $this->getJson('/api/v1/categories');

        $response
            ->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Alpha');
    }

    public function test_categories_page_must_be_at_least_one(): void
    {
        $response = $this->getJson('/api/v1/categories?page=0');

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['page']);
    }

    public function test_category_endpoint_returns_only_visible_items(): void
    {
        $category = Category::factory()->create([
            'slug' => 'design',
        ]);

        $visibleItem = Item::factory()->create([
            'title' => 'Visible Item',
            'is_visible' => true,
        ]);

        $hiddenItem = Item::factory()->create([
            'title' => 'Hidden Item',
            'is_visible' => false,
        ]);

        $category->items()->attach([
            $visibleItem->id,
            $hiddenItem->id,
        ]);

        $response = $this->getJson('/api/v1/categories/design');

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'title' => 'Visible Item',
            ])
            ->assertJsonMissing([
                'title' => 'Hidden Item',
            ]);
    }

    public function test_category_endpoint_is_paginated(): void
    {
        $category = Category::factory()->create([
            'slug' => 'design',
        ]);

        $items = Item::factory()->count(15)->create([
            'is_visible' => true,
        ]);

        $category->items()->attach($items->pluck('id'));

        $response = $this->getJson('/api/v1/categories/design');

        $response
            ->assertStatus(200)
            ->assertJsonCount(12, 'data')
            ->assertJsonPath('meta.per_page', 12);
    }

    public function test_non_existing_category_returns_404(): void
    {
        $response = $this->getJson('/api/v1/categories/does-not-exist');

        $response->assertStatus(404);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_items_endpoint_returns_only_visible_items(): void
    {
        Item::factory()->create([
            'title' => 'Visible Item',
            'is_visible' => true,
        ]);

        Item::factory()->create([
            'title' => 'Hidden Item',
            'is_visible' => false,
        ]);

        $response = $this->getJson('/api/v1/items');

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'title' => 'Visible Item',
            ])
            ->assertJsonMissing([
                'title' => 'Hidden Item',
            ]);
    }

    public function test_items_endpoint_can_search_by_title(): void
    {
        Item::factory()->create([
            'title' => 'Laravel Template Item',
            'description' => 'Test description',
            'is_visible' => true,
        ]);

        Item::factory()->create([
            'title' => 'Another Item',
            'description' => 'Something else',
            'is_visible' => true,
        ]);

        $response = $this->getJson('/api/v1/items?search=Laravel');

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'title' => 'Laravel Template Item',
            ])
            ->assertJsonMissing([
                'title' => 'Another Item',
            ]);
    }

    public function test_items_endpoint_can_sort_latest(): void
    {
        Item::factory()->create([
            'title' => 'Old Item',
            'is_visible' => true,
            'created_at' => now()->subDay(),
        ]);

        Item::factory()->create([
            'title' => 'New Item',
            'is_visible' => true,
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/items?sort=latest');

        $response
            ->assertStatus(200)
            ->assertJsonPath('data.0.title', 'New Item');
    }
}

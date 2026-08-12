<?php

namespace Tests\Feature;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemShowApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_visible_item_can_be_viewed(): void
    {
        $item = Item::factory()->create([
            'slug' => 'visible-item',
            'is_visible' => true,
        ]);

        $response = $this->getJson('/api/v1/items/visible-item');

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $item->id,
            ]);
    }

    public function test_hidden_item_cannot_be_viewed(): void
    {
        Item::factory()->create([
            'slug' => 'hidden-item',
            'is_visible' => false,
        ]);

        $response = $this->getJson('/api/v1/items/hidden-item');

        $response->assertStatus(404);
    }

    public function test_non_existing_item_returns_404(): void
    {
        $response = $this->getJson('/api/v1/items/does-not-exist');

        $response->assertStatus(404);
    }
}

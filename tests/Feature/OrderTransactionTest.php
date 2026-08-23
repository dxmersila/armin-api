<?php

namespace Tests\Feature;

use App\Models\Access;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_order_creation_does_not_create_partial_order(): void
    {
        $user = User::factory()->create();

        $purchasableItem = Item::factory()->create([
            'access_type' => 'paid',
            'is_visible' => true,
            'price' => 100000,
        ]);

        $alreadyOwnedItem = Item::factory()->create([
            'access_type' => 'paid',
            'is_visible' => true,
            'price' => 200000,
        ]);

        Access::create([
            'user_id' => $user->id,
            'item_id' => $alreadyOwnedItem->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'item_ids' => [
                $purchasableItem->id,
                $alreadyOwnedItem->id,
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['item_ids']);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }
}

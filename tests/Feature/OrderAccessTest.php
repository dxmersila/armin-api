<?php

namespace Tests\Feature;

use App\Models\Access;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_pending_order_does_not_grant_access(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
            'is_visible' => true,
            'price' => 100000,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'item_ids' => [$item->id],
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseMissing('accesses', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $this->assertDatabaseCount('accesses', 0);
    }
}

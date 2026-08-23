<?php

namespace Tests\Feature;

use App\Models\Access;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderExistingAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_purchase_item_they_already_have_access_to(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
            'price' => 100000,
        ]);

        Access::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'item_ids' => [$item->id],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['item_ids']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_user_can_purchase_item_they_do_not_have_access_to(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
            'price' => 100000,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'item_ids' => [$item->id],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.total_price', 100000);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'total_price' => 100000,
        ]);
    }
}

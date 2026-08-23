<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_create_order(): void
    {
        $item = Item::factory()->create([
            'access_type' => 'paid',
            'price' => 100000,
        ]);

        $response = $this->postJson('/api/v1/orders', [
            'item_ids' => [$item->id],
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_order(): void
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
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total_price', 100000);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status' => 'pending',
            'total_price' => 100000,
        ]);

        $this->assertDatabaseHas('order_items', [
            'item_id' => $item->id,
            'price' => 100000,
        ]);
    }

    public function test_item_ids_are_required(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['item_ids']);
    }

    public function test_item_ids_must_be_an_array(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'item_ids' => 1,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['item_ids']);
    }

    public function test_item_ids_cannot_be_empty(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'item_ids' => [],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['item_ids']);
    }

    public function test_item_ids_must_exist(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'item_ids' => [999999],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['item_ids.0']);
    }

    public function test_item_ids_must_be_unique(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
            'price' => 100000,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'item_ids' => [$item->id, $item->id],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['item_ids.1']);
    }

    public function test_authenticated_user_can_create_order_with_multiple_items(): void
    {
        $user = User::factory()->create();

        $firstItem = Item::factory()->create([
            'access_type' => 'paid',
            'price' => 100000,
        ]);

        $secondItem = Item::factory()->create([
            'access_type' => 'paid',
            'price' => 250000,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'item_ids' => [
                $firstItem->id,
                $secondItem->id,
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total_price', 350000);

        $this->assertDatabaseHas('order_items', [
            'item_id' => $firstItem->id,
            'price' => 100000,
        ]);

        $this->assertDatabaseHas('order_items', [
            'item_id' => $secondItem->id,
            'price' => 250000,
        ]);
    }

    public function test_order_item_price_is_snapshot_of_item_price(): void
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

        $response->assertCreated();

        $item->update([
            'price' => 200000,
        ]);

        $this->assertDatabaseHas('order_items', [
            'item_id' => $item->id,
            'price' => 100000,
        ]);
    }

    public function test_free_item_cannot_be_purchased(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'free',
            'price' => 0,
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

    public function test_hidden_paid_item_cannot_be_purchased(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
            'price' => 100000,
            'is_visible' => false,
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
}

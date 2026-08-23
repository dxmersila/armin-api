<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class OrderCreationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_order_with_selected_items(): void
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

        $order = app(OrderCreationService::class)->create(
            $user,
            [
                $firstItem->id,
                $secondItem->id,
            ]
        );

        $this->assertInstanceOf(Order::class, $order);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'item_id' => $firstItem->id,
            'price' => 100000,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'item_id' => $secondItem->id,
            'price' => 250000,
        ]);

        $this->assertSame(350000, $order->total_price);
    }

    public function test_it_uses_current_item_price_and_ignores_client_price(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
            'price' => 100000,
        ]);

        $order = app(OrderCreationService::class)->create(
            $user,
            [$item->id]
        );

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'item_id' => $item->id,
            'price' => 100000,
        ]);

        $this->assertSame(100000, $order->total_price);
    }

    public function test_it_rejects_empty_item_list(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        app(OrderCreationService::class)->create(
            $user,
            []
        );
    }

    public function test_it_rejects_non_existing_item(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        app(OrderCreationService::class)->create(
            $user,
            [999999]
        );
    }
}

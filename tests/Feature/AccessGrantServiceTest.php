<?php

namespace Tests\Feature;

use App\Models\Access;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\AccessGrantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessGrantServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_grants_access_to_all_order_items(): void
    {
        $user = User::factory()->create();

        $itemOne = Item::factory()->create([
            'access_type' => 'paid',
        ]);

        $itemTwo = Item::factory()->create([
            'access_type' => 'paid',
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'item_id' => $itemOne->id,
            'price' => 100000,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'item_id' => $itemTwo->id,
            'price' => 200000,
        ]);

        app(AccessGrantService::class)
            ->grantForOrder($order);

        $this->assertDatabaseHas('accesses', [
            'user_id' => $user->id,
            'item_id' => $itemOne->id,
        ]);

        $this->assertDatabaseHas('accesses', [
            'user_id' => $user->id,
            'item_id' => $itemTwo->id,
        ]);
    }

    public function test_it_does_not_create_duplicate_access(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'item_id' => $item->id,
            'price' => 100000,
        ]);

        app(AccessGrantService::class)
            ->grantForOrder($order);

        app(AccessGrantService::class)
            ->grantForOrder($order);

        $this->assertSame(
            1,
            Access::where('user_id', $user->id)
                ->where('item_id', $item->id)
                ->count()
        );
    }

    public function test_it_does_nothing_for_order_without_items(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
        ]);

        app(AccessGrantService::class)
            ->grantForOrder($order);

        $this->assertDatabaseCount('accesses', 0);
    }
}

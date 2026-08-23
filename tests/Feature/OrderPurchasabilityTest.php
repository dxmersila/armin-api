<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use App\Services\OrderCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderPurchasabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_item_can_be_purchased(): void
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
    }

    public function test_free_item_cannot_be_purchased(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'free',
            'price' => 0,
        ]);

        $this->expectException(ValidationException::class);

        app(OrderCreationService::class)->create(
            $user,
            [$item->id]
        );
    }

    public function test_hidden_paid_item_cannot_be_purchased(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
            'price' => 100000,
            'is_visible' => false,
        ]);

        $this->expectException(ValidationException::class);

        app(OrderCreationService::class)->create(
            $user,
            [$item->id]
        );
    }
}

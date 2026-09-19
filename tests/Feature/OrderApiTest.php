<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Models\Payment;
use App\Models\Media;
use App\Enums\OrderType;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use Carbon\Carbon;

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

    public function test_authenticated_user_can_list_own_orders(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $firstItem = Item::factory()->create([
            'access_type' => 'paid',
            'price' => 100000,
        ]);

        $secondItem = Item::factory()->create([
            'access_type' => 'paid',
            'price' => 200000,
        ]);

        $otherItem = Item::factory()->create([
            'access_type' => 'paid',
            'price' => 300000,
        ]);

        $olderOrder = Order::factory()->create([
            'user_id' => $user->id,
            'total_price' => 100000,
        ]);

        $olderOrder->orderItems()->create([
            'item_id' => $firstItem->id,
            'price' => 100000,
        ]);

        $newerOrder = Order::factory()->create([
            'user_id' => $user->id,
            'total_price' => 200000,
        ]);

        $newerOrder->orderItems()->create([
            'item_id' => $secondItem->id,
            'price' => 200000,
        ]);

        $otherOrder = Order::factory()->create([
            'user_id' => $otherUser->id,
            'total_price' => 300000,
        ]);

        $otherOrder->orderItems()->create([
            'item_id' => $otherItem->id,
            'price' => 300000,
        ]);

        $olderOrder->update([
            'created_at' => now()->subDay(),
        ]);

        $newerOrder->update([
            'created_at' => now()->addSecond(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/orders');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newerOrder->id)
            ->assertJsonPath('data.1.id', $olderOrder->id)
            ->assertJsonPath('data.0.user_id', $user->id)
            ->assertJsonPath('data.1.user_id', $user->id)
            ->assertJsonPath('data.0.items.0.item_id', $secondItem->id)
            ->assertJsonPath('data.1.items.0.item_id', $firstItem->id);

        $response->assertJsonMissing([
            'id' => $otherOrder->id,
        ]);
    }

    public function test_guest_cannot_list_orders(): void
    {
        $response = $this->getJson('/api/v1/orders');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_own_order(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
            'price' => 150000,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_price' => 150000,
        ]);

        $order->orderItems()->create([
            'item_id' => $item->id,
            'price' => 150000,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'status' => 'pending',
            'amount' => 150000,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/orders/{$order->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total_price', 150000);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'status',
                'total_price',
                'items',
                'payments',
                'created_at',
            ],
        ]);

        $response
            ->assertJsonPath('data.items.0.item_id', $item->id)
            ->assertJsonPath('data.items.0.price', 150000)
            ->assertJsonPath('data.payments.0.order_id', $order->id)
            ->assertJsonPath('data.payments.0.amount', 150000)
            ->assertJsonPath('data.payments.0.status', 'pending');
    }

    public function test_order_items_include_item_media_in_order_response(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
            'price' => 150000,
        ]);

        $media = Media::factory()->create([
            'item_id' => $item->id,
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'paid',
            'total_price' => 150000,
        ]);

        $order->orderItems()->create([
            'item_id' => $item->id,
            'price' => 150000,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/orders');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.items.0.item_id', $item->id)
            ->assertJsonPath(
                'data.0.items.0.item.media.0.id',
                $media->id
            )
            ->assertJsonPath(
                'data.0.items.0.item.media.0.is_primary',
                true
            );
    }

    public function test_authenticated_user_can_view_subscription_plan_in_own_order(): void
    {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'name' => '1 Year',
            'duration_months' => 12,
            'price' => 1000000,
            'is_active' => true,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION,
            'subscription_plan_id' => $plan->id,
            'total_price' => 1000000,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(
            "/api/v1/orders/{$order->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.type',
                OrderType::SUBSCRIPTION->value
            )
            ->assertJsonPath(
                'data.subscription_plan.id',
                $plan->id
            )
            ->assertJsonPath(
                'data.subscription_plan.name',
                '1 Year'
            )
            ->assertJsonPath(
                'data.subscription_plan.duration_months',
                12
            )
            ->assertJsonPath(
                'data.subscription_plan.price',
                1000000
            );
    }

    public function test_user_cannot_view_another_users_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/orders/{$order->id}")
            ->assertNotFound();
    }

    public function test_guest_cannot_view_order_details(): void
    {
        $order = Order::factory()->create();

        $this->getJson("/api/v1/orders/{$order->id}")
            ->assertUnauthorized();
    }

    public function test_non_existing_order_returns_not_found(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/orders/999999')
            ->assertNotFound();
    }

    public function test_authenticated_user_can_create_subscription_order(): void
    {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'price' => 300000,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'type' => OrderType::SUBSCRIPTION->value,
            'subscription_plan_id' => $plan->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.type', OrderType::SUBSCRIPTION->value)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total_price', 300000);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION->value,
            'subscription_plan_id' => $plan->id,
            'status' => 'pending',
            'total_price' => 300000,
        ]);
    }

    public function test_inactive_subscription_plan_cannot_be_purchased(): void
    {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'is_active' => false,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'type' => OrderType::SUBSCRIPTION->value,
            'subscription_plan_id' => $plan->id,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subscription_plan_id']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_type_must_be_valid(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'type' => 'invalid',
            'item_ids' => [1],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_subscription_order_requires_subscription_plan_id(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'type' => OrderType::SUBSCRIPTION->value,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subscription_plan_id']);
    }

    public function test_item_order_requires_item_ids(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'type' => OrderType::ITEM->value,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['item_ids']);
    }

    public function test_order_rejects_fields_from_another_order_type(): void
    {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'is_active' => true,
        ]);

        $item = Item::factory()->create([
            'access_type' => 'paid',
            'price' => 100000,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'type' => OrderType::SUBSCRIPTION->value,
            'subscription_plan_id' => $plan->id,
            'item_ids' => [$item->id],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['item_ids']);
    }

    public function test_subscription_order_includes_subscription_plan(): void
    {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'name' => '3 Months',
            'duration_months' => 3,
            'price' => 300000,
            'is_active' => true,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION,
            'subscription_plan_id' => $plan->id,
            'total_price' => 300000,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(
            "/api/v1/orders/{$order->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.type',
                OrderType::SUBSCRIPTION->value
            )
            ->assertJsonPath(
                'data.subscription_plan.id',
                $plan->id
            )
            ->assertJsonPath(
                'data.subscription_plan.name',
                '3 Months'
            )
            ->assertJsonPath(
                'data.subscription_plan.duration_months',
                3
            )
            ->assertJsonPath(
                'data.subscription_plan.price',
                300000
            );
    }

    public function test_authenticated_user_can_list_subscription_order(): void
    {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'name' => '3 Months',
            'duration_months' => 3,
            'price' => 300000,
            'is_active' => true,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION,
            'subscription_plan_id' => $plan->id,
            'total_price' => 300000,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/orders');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $order->id)
            ->assertJsonPath(
                'data.0.type',
                OrderType::SUBSCRIPTION->value
            );
    }

    public function test_user_cannot_create_subscription_order_with_active_subscription(): void
    {
        Carbon::setTestNow('2026-08-26 12:00:00');

        $user = User::factory()->create();

        $currentPlan = SubscriptionPlan::factory()->create([
            'is_active' => true,
        ]);

        $newPlan = SubscriptionPlan::factory()->create([
            'price' => 300000,
            'is_active' => true,
        ]);

        $existingOrder = Order::factory()->create([
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION,
            'subscription_plan_id' => $currentPlan->id,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'order_id' => $existingOrder->id,
            'subscription_plan_id' => $currentPlan->id,
            'starts_at' => '2026-08-01 12:00:00',
            'ends_at' => '2026-11-01 12:00:00',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'type' => OrderType::SUBSCRIPTION->value,
            'subscription_plan_id' => $newPlan->id,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subscription_plan_id']);

        $this->assertDatabaseCount(
            'orders',
            1
        );
    }
}

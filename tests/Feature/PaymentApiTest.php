<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_payment_for_own_pending_order(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
            'price' => 150000,
            'is_visible' => true,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING,
            'total_price' => 150000,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'item_id' => $item->id,
            'price' => 150000,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/orders/{$order->id}/payments");

        $response
            ->assertCreated()
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.amount', 150000)
            ->assertJsonPath('data.status', PaymentStatus::PENDING->value);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 150000,
            'status' => PaymentStatus::PENDING->value,
        ]);
    }

    public function test_user_cannot_create_payment_for_another_users_order(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $anotherUser->id,
            'status' => OrderStatus::PENDING,
            'total_price' => 150000,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/orders/{$order->id}/payments")
            ->assertForbidden();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_cannot_be_created_for_non_pending_order(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PAID,
            'total_price' => 150000,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/orders/{$order->id}/payments")
            ->assertUnprocessable();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_cannot_be_created_for_zero_price_order(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING,
            'total_price' => 0,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/orders/{$order->id}/payments")
            ->assertUnprocessable();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_guest_cannot_create_payment(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'total_price' => 150000,
        ]);

        $this->postJson("/api/v1/orders/{$order->id}/payments")
            ->assertUnauthorized();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_authenticated_user_can_process_own_pending_payment(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING,
            'total_price' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PENDING,
            'amount' => 150000,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/payments/{$payment->id}/process")
            ->assertOk()
            ->assertJsonPath('data.id', $payment->id)
            ->assertJsonPath('data.status', PaymentStatus::PAID->value);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::PAID->value,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PAID->value,
        ]);
    }

    public function test_user_cannot_process_another_users_payment(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $anotherUser->id,
            'status' => OrderStatus::PENDING,
            'total_price' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PENDING,
            'amount' => 150000,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/payments/{$payment->id}/process")
            ->assertForbidden();
    }

    public function test_guest_cannot_process_payment(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'total_price' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PENDING,
            'amount' => 150000,
        ]);

        $this->postJson("/api/v1/payments/{$payment->id}/process")
            ->assertUnauthorized();
    }

    public function test_paid_payment_cannot_be_processed_again(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PAID,
            'total_price' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PAID,
            'amount' => 150000,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/payments/{$payment->id}/process")
            ->assertUnprocessable();
    }
}

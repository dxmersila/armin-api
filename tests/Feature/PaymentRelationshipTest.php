<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_belongs_to_order(): void
    {
        $order = Order::factory()->create();

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
        ]);

        $this->assertTrue(
            $payment->order->is($order)
        );
    }

    public function test_order_has_many_payments(): void
    {
        $order = Order::factory()->create();

        Payment::factory()->count(2)->create([
            'order_id' => $order->id,
        ]);

        $this->assertCount(
            2,
            $order->payments
        );
    }

    public function test_payment_amount_is_independent_from_order_total_price(): void
    {
        $order = Order::factory()->create([
            'total_price' => 100000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 100000,
        ]);

        $order->update([
            'total_price' => 200000,
        ]);

        $payment->refresh();

        $this->assertSame(
            100000,
            $payment->amount
        );

        $this->assertSame(
            200000,
            $order->total_price
        );
    }
}

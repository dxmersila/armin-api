<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
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

    public function test_payment_stores_gateway_data_and_casts_paid_at(): void
    {
        $payment = Payment::factory()->create([
            'gateway' => 'zarinpal',
            'gateway_reference' => 'AUTH-123456',
            'gateway_transaction_id' => 'TX-123456',
            'paid_at' => now(),
        ]);

        $payment->refresh();

        $this->assertSame(
            'zarinpal',
            $payment->gateway
        );

        $this->assertSame(
            'AUTH-123456',
            $payment->gateway_reference
        );

        $this->assertSame(
            'TX-123456',
            $payment->gateway_transaction_id
        );

        $this->assertInstanceOf(
            \Carbon\Carbon::class,
            $payment->paid_at
        );
    }
}

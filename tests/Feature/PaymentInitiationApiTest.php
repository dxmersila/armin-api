<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Contracts\Payments\PaymentInitiationResult;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentGatewayResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentInitiationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_initiate_own_payment(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_price' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PENDING,
            'amount' => 150000,
        ]);

        $gateway = $this->mock(PaymentGateway::class);

        $gateway
            ->shouldReceive('name')
            ->once()
            ->andReturn('fake');

        $gateway
            ->shouldReceive('initiate')
            ->once()
            ->withArgs(function (Payment $receivedPayment) use ($payment) {
                return $receivedPayment->is($payment);
            })
            ->andReturn(new PaymentInitiationResult(
                redirectUrl: 'https://gateway.test/pay/123',
                reference: 'ref-123',
            ));

        $this->mock(PaymentGatewayResolver::class, function ($resolver) use ($gateway) {
            $resolver
                ->shouldReceive('resolve')
                ->once()
                ->andReturn($gateway);
        });

        Sanctum::actingAs($user);

        $response = $this->postJson(
            "/api/v1/payments/{$payment->id}/initiate"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.payment_id',
                $payment->id
            )
            ->assertJsonPath(
                'data.redirect_url',
                'https://gateway.test/pay/123'
            )
            ->assertJsonPath(
                'data.reference',
                'ref-123'
            );

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::PENDING->value,
            'gateway' => 'fake',
            'gateway_reference' => 'ref-123',
        ]);
    }

    public function test_user_cannot_initiate_another_users_payment(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $owner->id,
            'status' => 'pending',
            'total_price' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PENDING,
            'amount' => 150000,
        ]);

        Sanctum::actingAs($otherUser);

        $this->postJson(
            "/api/v1/payments/{$payment->id}/initiate"
        )->assertForbidden();
    }

    public function test_guest_cannot_initiate_payment(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_price' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PENDING,
            'amount' => 150000,
        ]);

        $this->postJson(
            "/api/v1/payments/{$payment->id}/initiate"
        )->assertUnauthorized();
    }

    public function test_failed_payment_cannot_be_initiated(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_price' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::FAILED,
            'amount' => 150000,
        ]);

        Sanctum::actingAs($user);

        $this->postJson(
            "/api/v1/payments/{$payment->id}/initiate"
        )
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Only pending payments can be initiated.'
            );
    }

    public function test_paid_payment_cannot_be_initiated(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'paid',
            'total_price' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PAID,
            'amount' => 150000,
        ]);

        Sanctum::actingAs($user);

        $this->postJson(
            "/api/v1/payments/{$payment->id}/initiate"
        )
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Only pending payments can be initiated.'
            );
    }
}

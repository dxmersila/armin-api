<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Contracts\Payments\PaymentInitiationResult;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\PaymentGatewayResolver;
use App\Services\PaymentInitiationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentInitiationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_initiates_payment_through_resolved_gateway(): void
    {
        $payment = Payment::factory()->create();

        $gateway = $this->mock(PaymentGateway::class);

        $gateway
            ->shouldReceive('name')
            ->once()
            ->andReturn('fake');

        $gateway
            ->shouldReceive('initiate')
            ->once()
            ->with($payment)
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

        $result = app(PaymentInitiationService::class)
            ->initiate($payment);

        $this->assertSame(
            'https://gateway.test/pay/123',
            $result->redirectUrl
        );

        $this->assertSame(
            'ref-123',
            $result->reference
        );

        $this->assertSame(
            'fake',
            $payment->fresh()->gateway
        );

        $this->assertSame(
            'ref-123',
            $payment->fresh()->gateway_reference
        );

        $this->assertSame(
            PaymentStatus::PENDING,
            $payment->fresh()->status
        );

        $this->assertNull(
            $payment->fresh()->gateway_transaction_id
        );

        $this->assertNull(
            $payment->fresh()->paid_at
        );
    }

    public function test_failed_payment_cannot_be_initiated(): void
    {
        $payment = Payment::factory()->create([
            'status' => PaymentStatus::FAILED,
        ]);

        $resolver = $this->mock(PaymentGatewayResolver::class);

        $resolver
            ->shouldNotReceive('resolve');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Only pending payments can be initiated.'
        );

        app(PaymentInitiationService::class)
            ->initiate($payment);
    }

    public function test_paid_payment_cannot_be_initiated(): void
    {
        $payment = Payment::factory()->create([
            'status' => PaymentStatus::PAID,
        ]);

        $resolver = $this->mock(PaymentGatewayResolver::class);

        $resolver
            ->shouldNotReceive('resolve');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Only pending payments can be initiated.'
        );

        app(PaymentInitiationService::class)
            ->initiate($payment);
    }
}

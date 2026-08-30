<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Services\FakePaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FakePaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_fake_gateway_successfully_pays_payment(): void
    {
        $payment = Payment::factory()->create([
            'amount' => 150000,
        ]);

        $result = app(FakePaymentGateway::class)->pay($payment);

        $this->assertTrue($result->successful);

        $this->assertSame(
            'fake-' . $payment->id,
            $result->transactionId
        );
    }
}

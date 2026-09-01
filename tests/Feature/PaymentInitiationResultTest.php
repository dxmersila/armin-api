<?php

namespace Tests\Feature;

use App\Contracts\Payments\PaymentInitiationResult;
use Tests\TestCase;

class PaymentInitiationResultTest extends TestCase
{
    public function test_it_stores_redirect_url_and_reference(): void
    {
        $result = new PaymentInitiationResult(
            redirectUrl: 'https://gateway.test/pay/123',
            reference: 'ref-123',
        );

        $this->assertSame(
            'https://gateway.test/pay/123',
            $result->redirectUrl
        );

        $this->assertSame(
            'ref-123',
            $result->reference
        );
    }
}

<?php

namespace Tests\Feature;

use App\Contracts\Payments\PaymentGatewayCallback;
use Tests\TestCase;

class PaymentGatewayCallbackTest extends TestCase
{
    public function test_it_stores_reference_status_and_metadata(): void
    {
        $callback = new PaymentGatewayCallback(
            reference: 'AUTH-123456',
            status: 'OK',
            metadata: [
                'authority' => 'AUTH-123456',
                'raw_status' => 'OK',
            ],
        );

        $this->assertSame(
            'AUTH-123456',
            $callback->reference
        );

        $this->assertSame(
            'OK',
            $callback->status
        );

        $this->assertSame(
            [
                'authority' => 'AUTH-123456',
                'raw_status' => 'OK',
            ],
            $callback->metadata
        );
    }

    public function test_metadata_defaults_to_empty_array(): void
    {
        $callback = new PaymentGatewayCallback(
            reference: 'AUTH-123456',
            status: 'OK',
        );

        $this->assertSame(
            [],
            $callback->metadata
        );
    }
}

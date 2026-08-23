<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_payment_is_pending_by_default(): void
    {
        $payment = Payment::factory()->create();

        $this->assertSame(
            PaymentStatus::PENDING,
            $payment->status
        );
    }

    public function test_payment_can_have_paid_status(): void
    {
        $payment = Payment::factory()->create([
            'status' => PaymentStatus::PAID,
        ]);

        $payment->refresh();

        $this->assertSame(
            PaymentStatus::PAID,
            $payment->status
        );
    }

    public function test_payment_can_have_failed_status(): void
    {
        $payment = Payment::factory()->create([
            'status' => PaymentStatus::FAILED,
        ]);

        $payment->refresh();

        $this->assertSame(
            PaymentStatus::FAILED,
            $payment->status
        );
    }
}

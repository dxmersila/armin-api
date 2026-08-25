<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\PaymentStatus;
use Illuminate\Validation\Rule;

class AdminOrderIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'string',
                new Enum(OrderStatus::class),
            ],

            'user_id' => [
                'sometimes',
                'integer',
                'exists:users,id',
            ],

            'payment_status' => [
                'sometimes',
                Rule::enum(PaymentStatus::class),
            ],
        ];
    }
}

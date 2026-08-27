<?php

namespace App\Http\Requests;

use App\Enums\OrderType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => $this->input('type', OrderType::ITEM->value),
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => [
                Rule::in([
                    OrderType::ITEM->value,
                    OrderType::SUBSCRIPTION->value,
                ]),
            ],

            'item_ids' => [
                'required_if:type,' . OrderType::ITEM->value,
                'prohibited_unless:type,' . OrderType::ITEM->value,
                'array',
                'min:1',
            ],

            'item_ids.*' => [
                'integer',
                'distinct',
                'exists:items,id',
            ],

            'subscription_plan_id' => [
                'required_if:type,' . OrderType::SUBSCRIPTION->value,
                'prohibited_unless:type,' . OrderType::SUBSCRIPTION->value,
                'integer',
                'exists:subscription_plans,id',
            ],
        ];
    }
}

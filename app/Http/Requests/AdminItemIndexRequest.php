<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminItemIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'is_visible' => [
                'nullable',
                'boolean',
            ],

            'access_type' => [
                'nullable',
                Rule::in([
                    'free',
                    'paid',
                ]),
            ],

            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'sort' => [
                'nullable',
                Rule::in([
                    'latest',
                    'oldest',
                    'title',
                ]),
            ],
        ];
    }
}

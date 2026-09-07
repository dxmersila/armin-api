<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                'unique:items,slug',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'content' => [
                'nullable',
                'string',
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

            'price' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'categories' => [
                'nullable',
                'array',
            ],

            'categories.*' => [
                'integer',
                Rule::exists('categories', 'id'),
            ],

            'tags' => [
                'nullable',
                'array',
            ],

            'tags.*' => [
                'string',
                'max:255',
            ],
        ];
    }
}

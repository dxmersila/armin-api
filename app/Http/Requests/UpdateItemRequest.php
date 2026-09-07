<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
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
                'sometimes',
                'string',
                'max:255',
            ],

            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('items', 'slug')
                    ->ignore($this->route('item')->id),
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'content' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'is_visible' => [
                'sometimes',
                'boolean',
            ],

            'access_type' => [
                'sometimes',
                Rule::in([
                    'free',
                    'paid',
                ]),
            ],

            'price' => [
                'sometimes',
                'integer',
                'min:0',
            ],

            'categories' => [
                'sometimes',
                'array',
            ],

            'categories.*' => [
                'integer',
                Rule::exists('categories', 'id'),
            ],

            'tags' => [
                'sometimes',
                'array',
            ],

            'tags.*' => [
                'string',
                'max:255',
            ],

        ];
    }
}

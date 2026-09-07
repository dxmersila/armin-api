<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUserIndexRequest extends FormRequest
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

            'role' => [
                'nullable',
                Rule::in([
                    'admin',
                    'user',
                ]),
            ],

            'sort' => [
                'nullable',
                Rule::in([
                    'latest',
                    'oldest',
                    'name',
                ]),
            ],
        ];
    }
}

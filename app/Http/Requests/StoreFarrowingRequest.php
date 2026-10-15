<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFarrowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'farrowed_on' => ['required', 'date'],
            'born_alive' => ['required', 'integer', 'min:1'],
            'stillborn' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}

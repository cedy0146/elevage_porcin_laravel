<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWeaningRequest extends FormRequest
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
            'weaned_count' => ['required', 'integer', 'min:1'],
            'weaned_on' => ['required', 'date'],
            'pen_id' => ['nullable', 'integer', Rule::exists('pens', 'id')->where('farm_id', $this->user()->farm_id)],
        ];
    }
}

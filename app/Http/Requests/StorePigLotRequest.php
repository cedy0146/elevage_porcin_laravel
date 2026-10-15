<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePigLotRequest extends FormRequest
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
            'pen_id' => ['required', 'integer', Rule::exists('pens', 'id')->where('farm_id', $this->user()->farm_id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('pig_lots', 'code')],
            'source' => ['sometimes', Rule::in(['purchase', 'birth', 'transfer'])],
            'initial_count' => ['required', 'integer', 'min:1', 'max:1000'],
            'initial_average_weight' => ['sometimes', 'numeric', 'min:0', 'max:500'],
            'arrived_on' => ['required', 'date'],
            'expected_end_on' => ['nullable', 'date', 'after_or_equal:arrived_on'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}

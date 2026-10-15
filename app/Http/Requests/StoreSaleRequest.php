<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
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
            'pig_lot_id' => ['required', 'integer', Rule::exists('pig_lots', 'id')->where('farm_id', $this->user()->farm_id)],
            'head_count' => ['required', 'integer', 'min:1'],
            'total_weight' => ['required', 'numeric', 'gt:0'],
            'price_per_kg' => ['required', 'numeric', 'gt:0'],
            'customer_name' => ['required', 'string', 'max:100'],
            'sold_on' => ['required', 'date'],
        ];
    }
}

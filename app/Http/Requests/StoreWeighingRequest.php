<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWeighingRequest extends FormRequest
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
            'weighed_on' => ['required', 'date'],
            'average_weight' => ['required', 'numeric', 'gt:0', 'max:500'],
            'sample_size' => ['required', 'integer', 'min:1'],
        ];
    }
}

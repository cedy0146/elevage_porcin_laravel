<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeedingRequest extends FormRequest
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
            'feed_type_id' => ['required', 'integer', Rule::exists('feed_types', 'id')->where('farm_id', $this->user()->farm_id)],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'fed_on' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}

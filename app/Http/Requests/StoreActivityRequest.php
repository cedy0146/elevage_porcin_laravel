<?php

namespace App\Http\Requests;

use App\Enums\ActivityType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
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
            'pig_lot_id' => ['nullable', 'integer', Rule::exists('pig_lots', 'id')->where('farm_id', $this->user()->farm_id)],
            'type' => ['required', Rule::enum(ActivityType::class)],
            'performed_on' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Enums\HealthEventType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHealthEventRequest extends FormRequest
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
            'type' => ['required', Rule::enum(HealthEventType::class)],
            'name' => ['required', 'string', 'max:100'],
            'scheduled_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

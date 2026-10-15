<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVaccineScheduleRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:100'],
            'target_age_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'tolerance_days' => ['sometimes', 'integer', 'min:0', 'max:30'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

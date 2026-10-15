<?php

namespace App\Http\Requests;

use App\Enums\PenType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePenRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50', Rule::unique('pens', 'code')->where('farm_id', $this->user()->farm_id)],
            'name' => ['required', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'min:1', 'max:1000'],
            'type' => ['required', Rule::enum(PenType::class)],
            'status' => ['sometimes', Rule::in(['free', 'maintenance'])],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMatingRequest extends FormRequest
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
            'sow_id' => ['required', 'integer', Rule::exists('breeders', 'id')->where('farm_id', $this->user()->farm_id)],
            'boar_id' => ['nullable', 'integer', Rule::exists('breeders', 'id')->where('farm_id', $this->user()->farm_id)],
            'mated_on' => ['required', 'date'],
        ];
    }
}

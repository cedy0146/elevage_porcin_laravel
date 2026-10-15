<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMortalityRequest extends FormRequest
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
            'died_on' => ['required', 'date'],
            'count' => ['required', 'integer', 'min:1'],
            'cause' => ['nullable', 'string', 'max:255'],
        ];
    }
}

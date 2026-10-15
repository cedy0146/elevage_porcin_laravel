<?php

namespace App\Http\Requests;

use App\Enums\BreederSex;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBreederRequest extends FormRequest
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
            'tag' => ['required', 'string', 'max:50', Rule::unique('breeders', 'tag')->where('farm_id', $this->user()->farm_id)],
            'sex' => ['required', Rule::enum(BreederSex::class)],
            'breed' => ['nullable', 'string', 'max:50'],
            'born_on' => ['nullable', 'date'],
        ];
    }
}

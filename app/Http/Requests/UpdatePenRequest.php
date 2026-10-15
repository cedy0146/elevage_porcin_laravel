<?php

namespace App\Http\Requests;

use App\Enums\PenType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Les statuts occupied/free pilotés par les lots ne sont pas modifiables
     * directement : seule la maintenance est ouverte à la mise à jour.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('pens', 'code')
                ->where('farm_id', $this->user()->farm_id)
                ->ignore($this->route('pen'))],
            'name' => ['sometimes', 'string', 'max:100'],
            'capacity' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'type' => ['sometimes', Rule::enum(PenType::class)],
            'status' => ['sometimes', Rule::in(['free', 'maintenance'])],
        ];
    }
}

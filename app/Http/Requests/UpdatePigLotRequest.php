<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePigLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Seuls les champs non critiques d'un lot peuvent être modifiés :
     * l'effectif et les dates d'arrivée sont gérés par les services métier.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'expected_end_on' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

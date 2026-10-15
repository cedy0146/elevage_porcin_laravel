<?php

namespace App\Http\Requests;

use App\Enums\FeedStage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeedTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * La quantité initiale n'est jamais acceptée ici : le stock se gère
     * uniquement via StockService (entrées, ajustements, distributions).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'stage' => ['required', Rule::enum(FeedStage::class)],
            'unit' => ['sometimes', 'string', 'max:20'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'minimum_threshold' => ['required', 'numeric', 'min:0'],
        ];
    }
}

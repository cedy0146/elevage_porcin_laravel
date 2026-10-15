<?php

namespace App\Http\Requests;

use App\Enums\FeedStage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeedTypeRequest extends FormRequest
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
            'stage' => ['sometimes', Rule::enum(FeedStage::class)],
            'unit' => ['sometimes', 'string', 'max:20'],
            'unit_price' => ['sometimes', 'numeric', 'min:0'],
            'minimum_threshold' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}

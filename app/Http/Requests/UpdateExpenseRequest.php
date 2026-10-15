<?php

namespace App\Http\Requests;

use App\Enums\ExpenseCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
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
            'category' => ['sometimes', Rule::enum(ExpenseCategory::class)],
            'amount' => ['sometimes', 'numeric', 'gt:0'],
            'spent_on' => ['sometimes', 'date'],
            'pig_lot_id' => ['nullable', 'integer', Rule::exists('pig_lots', 'id')->where('farm_id', $this->user()->farm_id)],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}

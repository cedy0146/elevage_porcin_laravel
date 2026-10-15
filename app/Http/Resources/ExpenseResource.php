<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'amount' => $this->amount,
            'spent_on' => $this->spent_on?->toDateString(),
            'description' => $this->description,
            'pig_lot_id' => $this->pig_lot_id,
            'lot' => $this->whenLoaded('pigLot', fn () => $this->pigLot?->code),
            'user' => $this->whenLoaded('user', fn () => $this->user?->name),
        ];
    }
}

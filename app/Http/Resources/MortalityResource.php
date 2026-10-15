<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MortalityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'died_on' => $this->died_on?->toDateString(),
            'count' => $this->count,
            'cause' => $this->cause,
            'pig_lot_id' => $this->pig_lot_id,
            'lot' => $this->whenLoaded('pigLot', fn () => $this->pigLot?->code),
            'user' => $this->whenLoaded('user', fn () => $this->user?->name),
        ];
    }
}

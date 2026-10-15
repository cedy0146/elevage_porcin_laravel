<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeighingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'weighed_on' => $this->weighed_on?->toDateString(),
            'average_weight' => $this->average_weight,
            'sample_size' => $this->sample_size,
            'pig_lot_id' => $this->pig_lot_id,
            'lot' => $this->whenLoaded('pigLot', fn () => $this->pigLot?->code),
            'user' => $this->whenLoaded('user', fn () => $this->user?->name),
        ];
    }
}

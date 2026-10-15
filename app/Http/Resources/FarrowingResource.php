<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FarrowingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'farrowed_on' => $this->farrowed_on?->toDateString(),
            'born_alive' => $this->born_alive,
            'stillborn' => $this->stillborn,
            'weaned_count' => $this->weaned_count,
            'weaned_on' => $this->weaned_on?->toDateString(),
            'mating_id' => $this->mating_id,
            'sow_id' => $this->sow_id,
            'sow' => $this->whenLoaded('sow', fn () => $this->sow?->tag),
            'pig_lot_id' => $this->pig_lot_id,
            'lot' => $this->whenLoaded('pigLot', fn () => $this->pigLot?->code),
            'user' => $this->whenLoaded('user', fn () => $this->user?->name),
        ];
    }
}

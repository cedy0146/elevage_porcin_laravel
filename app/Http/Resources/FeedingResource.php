<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedingResource extends JsonResource
{
    /**
     * Les champs financiers ne sont visibles que par les managers.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'fed_on' => $this->fed_on?->toDateString(),
            'notes' => $this->notes,
            'unit_cost' => $this->when($request->user()?->isManager(), fn () => $this->unit_cost),
            'total_cost' => $this->when($request->user()?->isManager(), fn () => $this->total_cost),
            'pig_lot_id' => $this->pig_lot_id,
            'lot' => $this->whenLoaded('pigLot', fn () => $this->pigLot?->code),
            'feed_type_id' => $this->feed_type_id,
            'feed_type' => $this->whenLoaded('feedType', fn () => $this->feedType?->name),
            'user' => $this->whenLoaded('user', fn () => $this->user?->name),
        ];
    }
}

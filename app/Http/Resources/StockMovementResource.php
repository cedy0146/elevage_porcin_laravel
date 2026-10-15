<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'quantity_before' => $this->quantity_before,
            'quantity_after' => $this->quantity_after,
            'unit_cost' => $this->when($request->user()?->isManager(), fn () => $this->unit_cost),
            'reason' => $this->reason,
            'moved_at' => $this->moved_at?->toIso8601String(),
            'feed_type' => $this->whenLoaded('feedType', fn () => $this->feedType?->name),
            'feed_type_id' => $this->feed_type_id,
            'lot' => $this->whenLoaded('pigLot', fn () => $this->pigLot?->code),
            'user' => $this->whenLoaded('user', fn () => $this->user?->name),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedTypeResource extends JsonResource
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
            'name' => $this->name,
            'stage' => $this->stage,
            'unit' => $this->unit,
            'quantity' => $this->quantity,
            'minimum_threshold' => $this->minimum_threshold,
            'unit_price' => $this->when($request->user()?->isManager(), fn () => $this->unit_price),
            'is_critical' => (float) $this->quantity <= (float) $this->minimum_threshold,
        ];
    }
}

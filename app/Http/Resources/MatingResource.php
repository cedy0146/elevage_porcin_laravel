<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'mated_on' => $this->mated_on?->toDateString(),
            'expected_farrowing_on' => $this->expected_farrowing_on?->toDateString(),
            'sow_id' => $this->sow_id,
            'sow' => $this->whenLoaded('sow', fn () => $this->sow?->tag),
            'boar_id' => $this->boar_id,
            'boar' => $this->whenLoaded('boar', fn () => $this->boar?->tag),
            'user' => $this->whenLoaded('user', fn () => $this->user?->name),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PigLotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'source' => $this->source,
            'status' => $this->status,
            'pen' => new PenResource($this->whenLoaded('pen')),
            'pen_id' => $this->pen_id,
            'initial_count' => $this->initial_count,
            'current_count' => $this->current_count,
            'initial_average_weight' => $this->initial_average_weight,
            'arrived_on' => $this->arrived_on?->toDateString(),
            'expected_end_on' => $this->expected_end_on?->toDateString(),
            'closed_on' => $this->closed_on?->toDateString(),
            'age_days' => $this->ageInDays,
            'notes' => $this->notes,
        ];
    }
}

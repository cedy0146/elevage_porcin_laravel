<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HealthEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'status' => $this->status,
            'scheduled_on' => $this->scheduled_on?->toDateString(),
            'done_on' => $this->done_on?->toDateString(),
            'cost' => $this->when($request->user()?->isManager(), fn () => $this->cost),
            'notes' => $this->notes,
            'pig_lot_id' => $this->pig_lot_id,
            'lot' => $this->whenLoaded('pigLot', fn () => $this->pigLot?->code),
            'vaccine_schedule_id' => $this->vaccine_schedule_id,
            'vaccine_schedule' => new VaccineScheduleResource($this->whenLoaded('vaccineSchedule')),
            'user' => $this->whenLoaded('user', fn () => $this->user?->name),
        ];
    }
}

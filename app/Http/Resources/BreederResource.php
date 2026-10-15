<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BreederResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tag' => $this->tag,
            'sex' => $this->sex,
            'breed' => $this->breed,
            'born_on' => $this->born_on?->toDateString(),
            'status' => $this->status,
        ];
    }
}

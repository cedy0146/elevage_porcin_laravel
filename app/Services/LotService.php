<?php

namespace App\Services;

use App\Models\PigLot;

class LotService
{
    /**
     * Indicateurs simplifiés d'un lot (complété par la suite : feed conversion,
     * GMQ, taux de mortalité, marge...).
     *
     * @return array<string, mixed>
     */
    public function indicators(PigLot $lot): array
    {
        return [
            'current_count' => $lot->currentCount,
            'age_days' => $lot->ageInDays,
        ];
    }
}

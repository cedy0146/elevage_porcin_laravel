<?php

namespace Database\Seeders;

use App\Models\Farm;
use App\Models\Pen;
use Illuminate\Database\Seeder;

class PenSeeder extends Seeder
{
    /**
     * 5 loges pour la Ferme Fanantenana, 3 pour la Porcherie Soa.
     */
    public function run(): void
    {
        $farmOne = Farm::where('name', 'Ferme Fanantenana')->firstOrFail();
        $farmTwo = Farm::where('name', 'Porcherie Soa')->firstOrFail();

        $pens = [
            ['farm' => $farmOne, 'code' => 'P-01', 'name' => 'Loge engraissement 1', 'capacity' => 60, 'type' => 'fattening'],
            ['farm' => $farmOne, 'code' => 'P-02', 'name' => 'Loge engraissement 2', 'capacity' => 60, 'type' => 'fattening'],
            ['farm' => $farmOne, 'code' => 'P-03', 'name' => 'Loge engraissement 3', 'capacity' => 50, 'type' => 'fattening'],
            ['farm' => $farmOne, 'code' => 'P-04', 'name' => 'Loge maternite', 'capacity' => 40, 'type' => 'maternity'],
            ['farm' => $farmOne, 'code' => 'P-05', 'name' => 'Loge quarantine', 'capacity' => 30, 'type' => 'quarantine'],
            ['farm' => $farmTwo, 'code' => 'P-01', 'name' => 'Loge engraissement 1', 'capacity' => 40, 'type' => 'fattening'],
            ['farm' => $farmTwo, 'code' => 'P-02', 'name' => 'Loge engraissement 2', 'capacity' => 40, 'type' => 'fattening'],
            ['farm' => $farmTwo, 'code' => 'P-03', 'name' => 'Loge maternite', 'capacity' => 30, 'type' => 'maternity'],
        ];

        foreach ($pens as $pen) {
            Pen::forceCreate([
                'farm_id' => $pen['farm']->id,
                'code' => $pen['code'],
                'name' => $pen['name'],
                'capacity' => $pen['capacity'],
                'type' => $pen['type'],
                'status' => 'free',
            ]);
        }
    }
}

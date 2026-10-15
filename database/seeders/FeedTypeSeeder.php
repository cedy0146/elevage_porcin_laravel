<?php

namespace Database\Seeders;

use App\Models\Farm;
use App\Models\FeedType;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Database\Seeder;

class FeedTypeSeeder extends Seeder
{
    /**
     * 4 types d'aliment pour la Ferme Fanantenana (dont UN sous le seuil,
     * déclenché par une sortie dans LotSeeder) et 2 pour la Porcherie Soa.
     * Le stock initial passe par StockService::recordInitial.
     */
    public function run(): void
    {
        $stocks = app(StockService::class);

        $farmOne = Farm::where('name', 'Ferme Fanantenana')->firstOrFail();
        $farmTwo = Farm::where('name', 'Porcherie Soa')->firstOrFail();

        $managerOne = User::where('email', 'manager@elevage.test')->firstOrFail();
        $managerTwo = User::where('email', 'manager.soa@elevage.test')->firstOrFail();

        $types = [
            ['farm' => $farmOne, 'user' => $managerOne, 'name' => 'Aliment porcelet', 'stage' => 'piglet', 'unit_price' => 3200, 'minimum_threshold' => 100, 'initial' => 250],
            ['farm' => $farmOne, 'user' => $managerOne, 'name' => 'Aliment croissance', 'stage' => 'grower', 'unit_price' => 2900, 'minimum_threshold' => 80, 'initial' => 900],
            ['farm' => $farmOne, 'user' => $managerOne, 'name' => 'Aliment finition', 'stage' => 'finisher', 'unit_price' => 2700, 'minimum_threshold' => 80, 'initial' => 900],
            ['farm' => $farmOne, 'user' => $managerOne, 'name' => 'Aliment truie', 'stage' => 'sow', 'unit_price' => 3500, 'minimum_threshold' => 50, 'initial' => 400],
            ['farm' => $farmTwo, 'user' => $managerTwo, 'name' => 'Aliment croissance', 'stage' => 'grower', 'unit_price' => 2800, 'minimum_threshold' => 50, 'initial' => 500],
            ['farm' => $farmTwo, 'user' => $managerTwo, 'name' => 'Aliment finition', 'stage' => 'finisher', 'unit_price' => 2600, 'minimum_threshold' => 50, 'initial' => 400],
        ];

        foreach ($types as $type) {
            $feedType = new FeedType;
            $feedType->forceFill([
                'farm_id' => $type['farm']->id,
                'name' => $type['name'],
                'stage' => $type['stage'],
                'unit' => 'kg',
                'unit_price' => $type['unit_price'],
                'minimum_threshold' => $type['minimum_threshold'],
                'quantity' => 0,
            ])->save();

            $stocks->recordInitial($feedType, $type['initial'], $type['user']);
        }
    }
}

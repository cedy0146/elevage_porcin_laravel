<?php

namespace Database\Seeders;

use App\Models\Farm;
use Illuminate\Database\Seeder;

class FarmSeeder extends Seeder
{
    /**
     * Deux exploitations de demonstration, pour prouver l'isolation SaaS.
     */
    public function run(): void
    {
        Farm::create([
            'name' => 'Ferme Fanantenana',
            'location' => 'Antsirabe',
            'type' => 'porcine',
            'capacity' => 250,
            'manager_name' => 'Rakoto Andry',
            'status' => 'active',
        ]);

        Farm::create([
            'name' => 'Porcherie Soa',
            'location' => 'Ambatolampy',
            'type' => 'porcine',
            'capacity' => 120,
            'manager_name' => 'Rasoa Hanta',
            'status' => 'active',
        ]);
    }
}

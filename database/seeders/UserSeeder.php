<?php

namespace Database\Seeders;

use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * 1 admin plateforme + 1 manager et 2 techniciens par exploitation.
     * Mot de passe de demonstration : « password ».
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Administrateur',
            'email' => 'admin@elevage.test',
        ]);

        $farmOne = Farm::where('name', 'Ferme Fanantenana')->firstOrFail();
        $farmTwo = Farm::where('name', 'Porcherie Soa')->firstOrFail();

        User::factory()->manager()->create([
            'farm_id' => $farmOne->id,
            'name' => 'Rakoto Andry',
            'email' => 'manager@elevage.test',
        ]);

        User::factory()->technician()->create([
            'farm_id' => $farmOne->id,
            'name' => 'Ranaivo Hery',
            'email' => 'technicien1@elevage.test',
        ]);

        User::factory()->technician()->create([
            'farm_id' => $farmOne->id,
            'name' => 'Rasoa Miora',
            'email' => 'technicien2@elevage.test',
        ]);

        User::factory()->manager()->create([
            'farm_id' => $farmTwo->id,
            'name' => 'Rasoa Hanta',
            'email' => 'manager.soa@elevage.test',
        ]);

        User::factory()->technician()->create([
            'farm_id' => $farmTwo->id,
            'name' => 'Andrianina Zo',
            'email' => 'technicien.soa@elevage.test',
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Jeu de demonstration reconstructible : php artisan migrate:fresh --seed.
     * La file est configurée en sync dans LotSeeder pour que les listeners
     * (notification d'alerte de stock) s'exécutent pendant le seeding.
     */
    public function run(): void
    {
        $this->call([
            FarmSeeder::class,
            UserSeeder::class,
            PenSeeder::class,
            VaccineScheduleSeeder::class,
            FeedTypeSeeder::class,
            LotSeeder::class,
            ReproductionSeeder::class,
        ]);

        Carbon::setTestNow();
    }
}

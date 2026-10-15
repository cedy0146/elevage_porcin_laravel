<?php

namespace Database\Seeders;

use App\Models\Farm;
use App\Models\VaccineSchedule;
use Illuminate\Database\Seeder;

class VaccineScheduleSeeder extends Seeder
{
    /**
     * Calendrier vaccinal d'exemple (à valider avec un vétérinaire) :
     * fer à J3, déparasitage à J30, peste porcine classique à J45, rappel à J60.
     * Appliqué aux deux exploitations pour que l'ouverture des lots planifie
     * automatiquement les actes (HealthService::planForLot).
     */
    public function run(): void
    {
        $calendars = [
            ['name' => 'Fer', 'target_age_days' => 3, 'tolerance_days' => 1],
            ['name' => 'Deparasitage', 'target_age_days' => 30, 'tolerance_days' => 3],
            ['name' => 'Vaccin peste porcine classique', 'target_age_days' => 45, 'tolerance_days' => 2],
            ['name' => 'Rappel peste porcine classique', 'target_age_days' => 60, 'tolerance_days' => 2],
        ];

        foreach (Farm::all() as $farm) {
            foreach ($calendars as $calendar) {
                $schedule = new VaccineSchedule;
                $schedule->forceFill([
                    'farm_id' => $farm->id,
                    'name' => $calendar['name'],
                    'target_age_days' => $calendar['target_age_days'],
                    'tolerance_days' => $calendar['tolerance_days'],
                    'is_active' => true,
                ])->save();
            }
        }
    }
}

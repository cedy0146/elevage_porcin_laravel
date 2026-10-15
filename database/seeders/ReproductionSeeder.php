<?php

namespace Database\Seeders;

use App\Models\Breeder;
use App\Models\Farm;
use App\Models\Pen;
use App\Models\User;
use App\Services\ReproductionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReproductionSeeder extends Seeder
{
    /**
     * Reproducteurs de la Ferme Fanantenana : 6 truies, 1 verrat,
     * 3 saillies (une echouee, une en cours, une avec mise bas +
     * sevrage qui cree un lot de naissance). Doit s'executer apres
     * LotSeeder (les loges et les utilisateurs doivent exister).
     */
    public function run(): void
    {
        // LotSeeder laisse Carbon gele : reprendre la date reelle.
        Carbon::setTestNow();

        $reproduction = app(ReproductionService::class);

        $farmOne = Farm::where('name', 'Ferme Fanantenana')->firstOrFail();
        $managerOne = User::where('email', 'manager@elevage.test')->firstOrFail();
        $techOne = User::where('email', 'technicien1@elevage.test')->firstOrFail();

        $maternity = Pen::where('farm_id', $farmOne->id)->where('code', 'P-04')->firstOrFail();

        $sows = [];
        foreach (range(1, 6) as $i) {
            $sows[] = Breeder::forceCreate([
                'farm_id' => $farmOne->id,
                'tag' => 'T-10'.$i,
                'sex' => 'sow',
                'breed' => $i % 2 === 0 ? 'Large White' : 'Landrace',
                'born_on' => now()->subYears(3)->subMonths($i)->toDateString(),
                'status' => 'active',
            ]);
        }

        $boar = Breeder::forceCreate([
            'farm_id' => $farmOne->id,
            'tag' => 'V-01',
            'sex' => 'boar',
            'breed' => 'Pietrain',
            'born_on' => now()->subYears(2)->toDateString(),
            'status' => 'active',
        ]);

        // Saillie echouee : la truie T-104 redevient disponible.
        $failed = $reproduction->registerMating($sows[3], $boar, now()->subDays(40), $managerOne);
        $reproduction->markFailed($failed);

        // Saillie recente : T-102 en gestation, terme dans ~2 mois.
        $reproduction->registerMating($sows[1], $boar, now()->subDays(30), $techOne);

        // Saillie avec mise bas terminee et sevrage (T-101).
        // Saillie il y a 130 jours, mise bas il y a 16 jours (gestation 114 j),
        // sevrage il y a 3 jours.
        $mating = $reproduction->registerMating($sows[0], $boar, now()->subDays(130), $managerOne);
        $farrowing = $reproduction->recordFarrowing(
            $mating,
            now()->subDays(16),
            bornAlive: 10,
            stillborn: 1,
            user: $managerOne,
        );
        $reproduction->recordWeaning($farrowing, 9, now()->subDays(3), $maternity->id, $managerOne);
    }
}

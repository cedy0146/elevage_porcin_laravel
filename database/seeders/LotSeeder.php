<?php

namespace Database\Seeders;

use App\Enums\ActivityType;
use App\Enums\ExpenseCategory;
use App\Models\Activity;
use App\Models\Farm;
use App\Models\FeedType;
use App\Models\Pen;
use App\Models\PigLot;
use App\Models\User;
use App\Models\Weighing;
use App\Services\FeedingService;
use App\Services\FinanceService;
use App\Services\LotService;
use App\Services\MortalityService;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class LotSeeder extends Seeder
{
    /**
     * Historique realiste construit AVEC les services (jamais d'insertions
     * brutes pour les mouvements de stock, distributions, mortalites, ventes
     * et ouvertures de lot). Carbon::setTestNow place chaque operation a sa
     * date historique ; la file est en sync pour que les listeners (dont la
     * notification d'alerte de stock) s'executent immediatement.
     */
    public function run(): void
    {
        config(['queue.default' => 'sync']);

        $lots = app(LotService::class);
        $feedings = app(FeedingService::class);
        $mortalities = app(MortalityService::class);
        $finance = app(FinanceService::class);
        $stocks = app(StockService::class);

        $farmOne = Farm::where('name', 'Ferme Fanantenana')->firstOrFail();
        $farmTwo = Farm::where('name', 'Porcherie Soa')->firstOrFail();

        $managerOne = User::where('email', 'manager@elevage.test')->firstOrFail();
        $techOne = User::where('email', 'technicien1@elevage.test')->firstOrFail();
        $techTwo = User::where('email', 'technicien2@elevage.test')->firstOrFail();
        $managerTwo = User::where('email', 'manager.soa@elevage.test')->firstOrFail();
        $techSoa = User::where('email', 'technicien.soa@elevage.test')->firstOrFail();

        $grower = FeedType::where('farm_id', $farmOne->id)->where('name', 'Aliment croissance')->firstOrFail();
        $finisher = FeedType::where('farm_id', $farmOne->id)->where('name', 'Aliment finition')->firstOrFail();
        $piglet = FeedType::where('farm_id', $farmOne->id)->where('name', 'Aliment porcelet')->firstOrFail();

        $penOne = Pen::where('farm_id', $farmOne->id)->where('code', 'P-01')->firstOrFail();
        $penTwo = Pen::where('farm_id', $farmOne->id)->where('code', 'P-02')->firstOrFail();
        $penThree = Pen::where('farm_id', $farmOne->id)->where('code', 'P-03')->firstOrFail();

        // ------------------------------------------------------------------
        // FERME FANANTENANA — lot termine L-2026-01 (ouvert il y a 70 jours)
        // ------------------------------------------------------------------
        $dayZero = now()->startOfDay()->subDays(70);
        Carbon::setTestNow($dayZero);

        $closedLot = $lots->open([
            'pen_id' => $penOne->id,
            'code' => 'L-2026-01',
            'source' => 'purchase',
            'initial_count' => 40,
            'initial_average_weight' => 25,
            'arrived_on' => $dayZero->toDateString(),
            'expected_end_on' => $dayZero->copy()->addDays(50)->toDateString(),
            'notes' => 'Lot d\'engraissement de demonstration.',
        ], $managerOne, 9_500_000.0);

        for ($day = 1; $day <= 50; $day++) {
            Carbon::setTestNow($dayZero->copy()->addDays($day));

            $ration = $day <= 25 ? $grower : $finisher;
            $quantity = $day <= 25 ? 6.0 : 8.0;

            $feedings->distribute($closedLot, $ration, $quantity, $techOne, null, 'Ration quotidienne');

            if ($day % 7 === 0) {
                $this->weigh($closedLot, $techTwo, $day, 25 + $day * 0.7);
            }

            if ($day === 12) {
                $mortalities->record($closedLot, 2, 'Grippe porcine', $techTwo, now());
            }

            if ($day === 30) {
                $mortalities->record($closedLot, 1, 'Blessure a la patte', $techTwo, now());
            }
        }

        // Ventes en deux fois : la deuxieme vide le lot et le cloture.
        Carbon::setTestNow($dayZero->copy()->addDays(55));
        $finance->recordSale($closedLot, 20, 1900.0, 9_500.0, 'Socopa', $managerOne, now());

        Carbon::setTestNow($dayZero->copy()->addDays(56));
        $finance->recordSale($closedLot, 17, 1615.0, 9_500.0, 'Trader Antsirabe', $managerOne, now());

        $this->activities($closedLot, $farmOne, $techOne, $techTwo, $dayZero);

        // ------------------------------------------------------------------
        // FERME FANANTENANA — lot actif L-2026-02 (60 jours d'historique)
        // ------------------------------------------------------------------
        Carbon::setTestNow($dayZero->copy()->addDays(10));
        $activeTwo = $this->openAndFeed($lots, $feedings, $mortalities, [
            'pen' => $penTwo,
            'code' => 'L-2026-02',
            'initialCount' => 50,
            'initialWeight' => 22,
            'start' => now()->copy(),
            'days' => 60,
            'mortalityDay' => 20,
            'mortalityCount' => 2,
            'grower' => $grower,
            'finisher' => $finisher,
            'manager' => $managerOne,
            'tech' => $techOne,
            'vet' => $techTwo,
            'purchaseCost' => 11_000_000.0,
        ]);

        $this->activities($activeTwo, $farmOne, $techOne, $techTwo, now()->copy()->subDays(60));

        // ------------------------------------------------------------------
        // FERME FANANTENANA — lot actif L-2026-03 (40 jours d'historique)
        // ------------------------------------------------------------------
        Carbon::setTestNow($dayZero->copy()->addDays(30));
        $activeThree = $this->openAndFeed($lots, $feedings, $mortalities, [
            'pen' => $penThree,
            'code' => 'L-2026-03',
            'initialCount' => 45,
            'initialWeight' => 28,
            'start' => now()->copy(),
            'days' => 40,
            'mortalityDay' => 15,
            'mortalityCount' => 1,
            'grower' => $finisher,
            'finisher' => $finisher,
            'manager' => $managerOne,
            'tech' => $techTwo,
            'vet' => $techOne,
            'purchaseCost' => 10_200_000.0,
        ]);

        $this->activities($activeThree, $farmOne, $techTwo, $techOne, now()->copy()->subDays(40));

        // Depenses d'exploitation (sans lot), hors historique de production.
        Carbon::setTestNow(now()->subDays(15));
        $finance->recordExpense($farmOne, ExpenseCategory::Labor, 600_000, now(), $managerOne, null, 'Paie equipe mars');
        $finance->recordExpense($farmOne, ExpenseCategory::Energy, 250_000, now(), $managerOne, null, 'Facture electricite');

        Carbon::setTestNow(now()->subDays(5));
        $finance->recordExpense($farmOne, ExpenseCategory::Veterinary, 180_000, now(), $managerOne, $activeTwo, 'Traitement antibiotique');
        $finance->recordExpense($farmOne, ExpenseCategory::Feed, 1_450_000, now(), $managerOne, null, 'Achat 500 kg aliment croissance');

        // ------------------------------------------------------------------
        // PORCHERIE SOA — lot actif L-2026-10 (isolation SaaS)
        // ------------------------------------------------------------------
        $penSoa = Pen::where('farm_id', $farmTwo->id)->where('code', 'P-01')->firstOrFail();
        $growerSoa = FeedType::where('farm_id', $farmTwo->id)->where('name', 'Aliment croissance')->firstOrFail();
        $finisherSoa = FeedType::where('farm_id', $farmTwo->id)->where('name', 'Aliment finition')->firstOrFail();

        Carbon::setTestNow(now()->subDays(30)->startOfDay());
        $soaLot = $this->openAndFeed($lots, $feedings, $mortalities, [
            'pen' => $penSoa,
            'code' => 'L-2026-10',
            'initialCount' => 30,
            'initialWeight' => 25,
            'start' => now()->copy(),
            'days' => 30,
            'mortalityDay' => 10,
            'mortalityCount' => 1,
            'grower' => $growerSoa,
            'finisher' => $finisherSoa,
            'manager' => $managerTwo,
            'tech' => $techSoa,
            'vet' => $techSoa,
            'purchaseCost' => 6_800_000.0,
        ]);

        $this->activities($soaLot, $farmTwo, $techSoa, $techSoa, now()->copy()->subDays(30));

        Carbon::setTestNow(now()->subDays(3));
        $finance->recordExpense($farmTwo, ExpenseCategory::Labor, 350_000, now(), $managerTwo, null, 'Paie technicien');

        // ------------------------------------------------------------------
        // Alerte de stock : sortie qui fait passer l'aliment porcelet SOUS
        // son seuil minimum (100 kg) -> StockCritical -> notification aux
        // managers de la Ferme Fanantenana uniquement (file sync).
        // ------------------------------------------------------------------
        Carbon::setTestNow(now());
        $stocks->issue($piglet, 230, $managerOne, 'Sortie pour rations porcelets');

        Carbon::setTestNow();
    }

    /**
     * Ouvre un lot et construit son historique (distributions tous les 2
     * jours, pesees hebdomadaires, une mortalite) via les services.
     */
    private function openAndFeed(
        LotService $lots,
        FeedingService $feedings,
        MortalityService $mortalities,
        array $data,
    ): PigLot {
        Carbon::setTestNow($data['start']);

        $lot = $lots->open([
            'pen_id' => $data['pen']->id,
            'code' => $data['code'],
            'source' => 'purchase',
            'initial_count' => $data['initialCount'],
            'initial_average_weight' => $data['initialWeight'],
            'arrived_on' => $data['start']->toDateString(),
            'expected_end_on' => $data['start']->copy()->addDays($data['days'] + 15)->toDateString(),
        ], $data['manager'], $data['purchaseCost']);

        for ($day = 1; $day <= $data['days']; $day++) {
            Carbon::setTestNow($data['start']->copy()->addDays($day));

            if ($day % 2 === 0) {
                $ration = $day <= (int) ($data['days'] / 2) ? $data['grower'] : $data['finisher'];
                $quantity = $day <= (int) ($data['days'] / 2) ? 6.0 : 8.0;

                $feedings->distribute($lot, $ration, $quantity, $data['tech'], null, 'Ration quotidienne');
            }

            if ($day % 7 === 0) {
                $this->weigh($lot, $data['vet'], $day, $data['initialWeight'] + $day * 0.65);
            }

            if ($day === $data['mortalityDay']) {
                $mortalities->record($lot, $data['mortalityCount'], 'Cause inconnue', $data['vet'], now());
            }
        }

        return $lot;
    }

    /**
     * Pesee hebdomadaire (pas de service dedie : insertion directe explicite).
     */
    private function weigh(PigLot $lot, User $user, int $day, float $averageWeight): Weighing
    {
        $weighing = new Weighing;
        $weighing->forceFill([
            'farm_id' => $lot->farm_id,
            'pig_lot_id' => $lot->id,
            'user_id' => $user->id,
            'weighed_on' => now()->toDateString(),
            'average_weight' => round($averageWeight, 2),
            'sample_size' => 15,
        ])->save();

        return $weighing;
    }

    /**
     * Quelques activites techniques historiques (nettoyage, traitement).
     */
    private function activities(PigLot $lot, Farm $farm, User $userOne, User $userTwo, Carbon $start): void
    {
        $entries = [
            ['type' => ActivityType::Cleaning, 'day' => 5, 'user' => $userOne, 'description' => 'Nettoyage et desinfection de la loge', 'cost' => 50_000],
            ['type' => ActivityType::Treatment, 'day' => 18, 'user' => $userTwo, 'description' => 'Traitement antiparasitaire', 'cost' => 80_000],
            ['type' => ActivityType::Maintenance, 'day' => 35, 'user' => $userOne, 'description' => 'Reparation de l\'abreuvoir', 'cost' => 35_000],
        ];

        foreach ($entries as $entry) {
            Activity::forceCreate([
                'farm_id' => $farm->id,
                'pig_lot_id' => $lot->id,
                'user_id' => $entry['user']->id,
                'type' => $entry['type'],
                'performed_on' => $start->copy()->addDays($entry['day'])->toDateString(),
                'description' => $entry['description'],
                'cost' => $entry['cost'],
            ]);
        }
    }
}

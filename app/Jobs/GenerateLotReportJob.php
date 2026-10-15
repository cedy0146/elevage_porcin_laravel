<?php

namespace App\Jobs;

use App\Models\PigLot;
use App\Services\LotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Génère le rapport JSON de fin de lot (indicateurs, totaux) puis le journalise.
 * La tâche est exécutée en queue : la requête HTTP répond immédiatement (202).
 */
class GenerateLotReportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array|int $backoff = [10, 30, 60];

    public function __construct(public PigLot $lot) {}

    public function handle(): void
    {
        $report = [
            'lot' => [
                'id' => $this->lot->id,
                'code' => $this->lot->code,
                'status' => $this->lot->status?->value,
                'arrived_on' => $this->lot->arrived_on?->toDateString(),
                'closed_on' => $this->lot->closed_on?->toDateString(),
                'initial_count' => $this->lot->initial_count,
                'current_count' => $this->lot->currentCount,
            ],
            'indicators' => app(LotService::class)->indicators($this->lot),
            'generated_at' => now()->toIso8601String(),
        ];

        $path = storage_path('app/private/reports/lot-'.$this->lot->id.'-'.now()->format('Y-m-d').'.json');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        Log::channel('business')->info('Rapport de lot genere', [
            'lot_id' => $this->lot->id,
            'farm_id' => $this->lot->farm_id,
            'path' => $path,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::channel('business')->error('Echec de generation du rapport de lot', [
            'lot_id' => $this->lot->id,
            'farm_id' => $this->lot->farm_id,
            'message' => $exception->getMessage(),
        ]);
    }
}

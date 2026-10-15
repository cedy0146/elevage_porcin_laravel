<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MarkDoneRequest;
use App\Http\Requests\StoreHealthEventRequest;
use App\Http\Resources\HealthEventResource;
use App\Models\HealthEvent;
use App\Models\PigLot;
use App\Services\HealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class HealthEventsController extends Controller
{
    /**
     * Actes sanitaires (filtres : pig_lot_id, status, overdue=1).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', HealthEvent::class);

        $perPage = min(max($request->integer('per_page', 15), 1), 50);

        $events = HealthEvent::query()
            ->when($request->filled('pig_lot_id'), fn ($query) => $query->where('pig_lot_id', $request->integer('pig_lot_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->input('overdue') === '1', fn ($query) => $query->overdue())
            ->with(['pigLot:id,code', 'vaccineSchedule:id,name', 'user:id,name'])
            ->orderByDesc('scheduled_on')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return HealthEventResource::collection($events);
    }

    public function store(StoreHealthEventRequest $request): JsonResponse
    {
        Gate::authorize('create', HealthEvent::class);

        $validated = $request->validated();
        $lot = PigLot::query()->findOrFail($validated['pig_lot_id']);

        $event = new HealthEvent;
        $event->forceFill([
            'farm_id' => $lot->farm_id,
            'pig_lot_id' => $lot->id,
            'vaccine_schedule_id' => null,
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'name' => $validated['name'],
            'scheduled_on' => Carbon::parse($validated['scheduled_on'])->toDateString(),
            'status' => 'todo',
            'notes' => $validated['notes'] ?? null,
        ])->save();

        return (new HealthEventResource($event->load(['pigLot:id,code', 'user:id,name'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Marque un acte sanitaire comme effectué (calcul du retard inclus).
     */
    public function done(HealthEvent $health_event, MarkDoneRequest $request, HealthService $health): JsonResponse
    {
        Gate::authorize('create', HealthEvent::class);

        $validated = $request->validated();

        $result = $health->markDone(
            $health_event,
            $request->user(),
            Carbon::parse($validated['done_on']),
            (float) ($validated['cost'] ?? 0),
        );

        return (new HealthEventResource($result['event']->load(['pigLot:id,code', 'user:id,name'])))
            ->additional([
                'lateness_days' => $result['lateness_days'],
                'within_tolerance' => $result['within_tolerance'],
            ])
            ->response();
    }

    public function show(HealthEvent $health_event): HealthEventResource
    {
        Gate::authorize('view', $health_event);

        return new HealthEventResource($health_event->load(['pigLot:id,code', 'vaccineSchedule:id,name', 'user:id,name']));
    }
}

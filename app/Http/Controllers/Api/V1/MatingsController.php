<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFarrowingRequest;
use App\Http\Requests\StoreMatingRequest;
use App\Http\Resources\FarrowingResource;
use App\Http\Resources\MatingResource;
use App\Models\Breeder;
use App\Models\Mating;
use App\Services\ReproductionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class MatingsController extends Controller
{
    /**
     * Saillies (filtre ?status=pending|confirmed|failed|farrowed).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Mating::class);

        $perPage = min(max($request->integer('per_page', 15), 1), 50);

        $matings = Mating::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->with(['sow:id,tag', 'boar:id,tag', 'user:id,name'])
            ->orderByDesc('mated_on')
            ->paginate($perPage)
            ->withQueryString();

        return MatingResource::collection($matings);
    }

    public function store(StoreMatingRequest $request, ReproductionService $reproduction): JsonResponse
    {
        Gate::authorize('create', Mating::class);

        $validated = $request->validated();

        $mating = $reproduction->registerMating(
            sow: Breeder::query()->findOrFail($validated['sow_id']),
            boar: isset($validated['boar_id']) ? Breeder::query()->findOrFail($validated['boar_id']) : null,
            matedOn: Carbon::parse($validated['mated_on']),
            user: $request->user(),
        );

        return (new MatingResource($mating->load(['sow:id,tag', 'boar:id,tag', 'user:id,name'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Déclare la saillie échouée.
     */
    public function failed(Mating $mating, ReproductionService $reproduction): MatingResource
    {
        Gate::authorize('create', Mating::class);

        return new MatingResource($reproduction->markFailed($mating)->load(['sow:id,tag', 'boar:id,tag']));
    }

    /**
     * Enregistre la mise bas de la saillie.
     */
    public function farrowing(Mating $mating, StoreFarrowingRequest $request, ReproductionService $reproduction): JsonResponse
    {
        Gate::authorize('create', Mating::class);

        $validated = $request->validated();

        $farrowing = $reproduction->recordFarrowing(
            mating: $mating,
            farrowedOn: Carbon::parse($validated['farrowed_on']),
            bornAlive: (int) $validated['born_alive'],
            stillborn: (int) ($validated['stillborn'] ?? 0),
            user: $request->user(),
        );

        return (new FarrowingResource($farrowing->load(['sow:id,tag', 'user:id,name'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Mating $mating): MatingResource
    {
        Gate::authorize('view', $mating);

        return new MatingResource($mating->load(['sow:id,tag', 'boar:id,tag', 'user:id,name']));
    }
}

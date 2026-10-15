<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWeaningRequest;
use App\Http\Resources\FarrowingResource;
use App\Models\Farrowing;
use App\Services\ReproductionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class FarrowingsController extends Controller
{
    /**
     * Mises bas de l'exploitation.
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Farrowing::class);

        return FarrowingResource::collection(
            Farrowing::query()
                ->with(['sow:id,tag', 'pigLot:id,code', 'user:id,name'])
                ->latest('farrowed_on')
                ->paginate()
                ->withQueryString()
        );
    }

    public function show(Farrowing $farrowing): FarrowingResource
    {
        Gate::authorize('view', $farrowing);

        return new FarrowingResource($farrowing->load(['sow:id,tag', 'pigLot:id,code', 'user:id,name']));
    }

    /**
     * Enregistre le sevrage (crée le lot de naissance si pen_id fourni).
     */
    public function weaning(Farrowing $farrowing, StoreWeaningRequest $request, ReproductionService $reproduction): JsonResponse
    {
        Gate::authorize('create', Farrowing::class);

        $validated = $request->validated();

        $weaned = $reproduction->recordWeaning(
            farrowing: $farrowing,
            weanedCount: (int) $validated['weaned_count'],
            weanedOn: Carbon::parse($validated['weaned_on']),
            penId: $validated['pen_id'] ?? null,
            user: $request->user(),
        );

        return (new FarrowingResource($weaned->load(['sow:id,tag', 'pigLot:id,code'])))
            ->response();
    }
}

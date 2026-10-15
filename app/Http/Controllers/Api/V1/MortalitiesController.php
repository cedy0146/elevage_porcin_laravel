<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMortalityRequest;
use App\Http\Resources\MortalityResource;
use App\Models\Mortality;
use App\Models\PigLot;
use App\Services\MortalityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class MortalitiesController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Mortality::class);

        return MortalityResource::collection(
            Mortality::query()
                ->with(['pigLot:id,code', 'user:id,name'])
                ->latest('died_on')
                ->latest('id')
                ->paginate()
                ->withQueryString()
        );
    }

    public function store(StoreMortalityRequest $request, MortalityService $mortalities): JsonResponse
    {
        Gate::authorize('create', Mortality::class);

        $validated = $request->validated();

        $mortality = $mortalities->record(
            PigLot::query()->findOrFail($validated['pig_lot_id']),
            (int) $validated['count'],
            $validated['cause'] ?? null,
            $request->user(),
            Carbon::parse($validated['died_on']),
        );

        return (new MortalityResource($mortality->load(['pigLot:id,code', 'user:id,name'])))
            ->response()
            ->setStatusCode(201);
    }
}

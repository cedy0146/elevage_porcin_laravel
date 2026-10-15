<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFarmRequest;
use App\Http\Requests\UpdateFarmRequest;
use App\Http\Resources\FarmResource;
use App\Models\Farm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class FarmsController extends Controller
{
    /**
     * Administration des exploitations (role:admin).
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Farm::class);

        return FarmResource::collection(Farm::query()->orderBy('name')->paginate());
    }

    public function store(StoreFarmRequest $request): JsonResponse
    {
        Gate::authorize('create', Farm::class);

        $farm = Farm::create($request->validated());

        return (new FarmResource($farm))->response()->setStatusCode(201);
    }

    public function show(Farm $farm): FarmResource
    {
        Gate::authorize('view', $farm);

        return new FarmResource($farm);
    }

    public function update(UpdateFarmRequest $request, Farm $farm): FarmResource
    {
        Gate::authorize('update', $farm);

        $farm->update($request->validated());

        return new FarmResource($farm);
    }

    public function destroy(Farm $farm): Response
    {
        Gate::authorize('delete', $farm);

        $farm->delete();

        return response()->noContent();
    }
}

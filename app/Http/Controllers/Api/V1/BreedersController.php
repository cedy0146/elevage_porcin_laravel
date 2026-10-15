<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBreederRequest;
use App\Http\Requests\UpdateBreederRequest;
use App\Http\Resources\BreederResource;
use App\Models\Breeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class BreedersController extends Controller
{
    /**
     * Reproducteurs (truies et verrats) de l'exploitation.
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Breeder::class);

        return BreederResource::collection(
            Breeder::query()->orderBy('tag')->paginate()
        );
    }

    public function store(StoreBreederRequest $request): JsonResponse
    {
        Gate::authorize('create', Breeder::class);

        $breeder = new Breeder;
        $breeder->forceFill(array_merge(
            ['farm_id' => $request->user()->farm_id, 'status' => 'active'],
            $request->validated(),
        ))->save();

        return (new BreederResource($breeder))->response()->setStatusCode(201);
    }

    public function show(Breeder $breeder): BreederResource
    {
        Gate::authorize('view', $breeder);

        return new BreederResource($breeder);
    }

    public function update(UpdateBreederRequest $request, Breeder $breeder): BreederResource
    {
        Gate::authorize('update', $breeder);

        // sex et status ne sont modifiables que via le service/manager explicite.
        $breeder->fill($request->safe()->except(['sex', 'status']));

        if ($request->filled('status') && $request->user()->isManager()) {
            $breeder->forceFill(['status' => $request->validated('status')]);
        }

        $breeder->save();

        return new BreederResource($breeder);
    }

    public function destroy(Breeder $breeder): Response
    {
        Gate::authorize('delete', $breeder);

        $breeder->delete();

        return response()->noContent();
    }
}

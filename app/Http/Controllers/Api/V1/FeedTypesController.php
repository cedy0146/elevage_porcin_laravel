<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFeedTypeRequest;
use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Http\Requests\StoreStockEntryRequest;
use App\Http\Requests\UpdateFeedTypeRequest;
use App\Http\Resources\FeedTypeResource;
use App\Http\Resources\StockMovementResource;
use App\Models\FeedType;
use App\Services\StockAutonomyService;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class FeedTypesController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', FeedType::class);

        return FeedTypeResource::collection(
            FeedType::query()->orderBy('name')->paginate()
        );
    }

    public function store(StoreFeedTypeRequest $request): JsonResponse
    {
        Gate::authorize('create', FeedType::class);

        $feedType = new FeedType;
        $feedType->forceFill(array_merge(
            ['farm_id' => $request->user()->farm_id, 'quantity' => 0],
            $request->validated(),
        ))->save();

        return (new FeedTypeResource($feedType))->response()->setStatusCode(201);
    }

    public function show(FeedType $feed_type): FeedTypeResource
    {
        Gate::authorize('view', $feed_type);

        return new FeedTypeResource($feed_type);
    }

    public function update(UpdateFeedTypeRequest $request, FeedType $feed_type): FeedTypeResource
    {
        Gate::authorize('update', $feed_type);

        $feed_type->fill($request->validated())->save();

        return new FeedTypeResource($feed_type);
    }

    public function destroy(FeedType $feed_type): Response
    {
        Gate::authorize('delete', $feed_type);

        $feed_type->delete();

        return response()->noContent();
    }

    /**
     * Autonomie estimée de chaque aliment (module personnel).
     */
    public function autonomy(Request $request, StockAutonomyService $autonomy)
    {
        Gate::authorize('viewAny', FeedType::class);

        return response()->json(
            $autonomy->forFarm($request->user()->farm)->values()
        );
    }

    /**
     * Entrée en stock (réapprovisionnement) — manager.
     */
    public function entries(StoreStockEntryRequest $request, FeedType $feed_type, StockService $stock)
    {
        Gate::authorize('update', $feed_type);

        $movement = $stock->receive(
            $feed_type,
            (float) $request->validated('quantity'),
            $request->user(),
            $request->filled('unit_cost') ? (float) $request->validated('unit_cost') : null,
            $request->validated('reason'),
        );

        return (new StockMovementResource($movement))->response()->setStatusCode(201);
    }

    /**
     * Ajustement d'inventaire — manager.
     */
    public function adjustments(StoreStockAdjustmentRequest $request, FeedType $feed_type, StockService $stock)
    {
        Gate::authorize('update', $feed_type);

        $movement = $stock->adjust(
            $feed_type,
            (float) $request->validated('quantity'),
            $request->user(),
            $request->validated('reason'),
        );

        return (new StockMovementResource($movement))->response()->setStatusCode(201);
    }
}

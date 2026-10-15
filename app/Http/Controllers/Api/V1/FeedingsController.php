<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFeedingRequest;
use App\Http\Resources\FeedingResource;
use App\Models\Feeding;
use App\Models\FeedType;
use App\Models\PigLot;
use App\Services\FeedingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class FeedingsController extends Controller
{
    /**
     * Liste paginée avec filtres, tri protégé et pagination.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Feeding::class);

        $perPage = min(max($request->integer('per_page', 15), 1), 50);

        $feedings = Feeding::query()
            ->when($request->filled('pig_lot_id'), fn ($query) => $query->forLot($request->integer('pig_lot_id')))
            ->when($request->filled('feed_type_id'), fn ($query) => $query->forFeedType($request->integer('feed_type_id')))
            ->when($request->filled('user_id'), fn ($query) => $query->forUser($request->integer('user_id')))
            ->from($request->input('from'))
            ->to($request->input('to'))
            ->sortApi(
                $request->string('sort', 'created_at')->toString(),
                $request->string('direction', 'desc')->toString(),
            )
            ->with(['pigLot:id,code', 'feedType:id,name', 'user:id,name'])
            ->paginate($perPage)
            ->withQueryString();

        return FeedingResource::collection($feedings);
    }

    public function store(StoreFeedingRequest $request, FeedingService $feedings): JsonResponse
    {
        Gate::authorize('create', Feeding::class);

        $validated = $request->validated();

        $feeding = $feedings->distribute(
            PigLot::query()->findOrFail($validated['pig_lot_id']),
            FeedType::query()->findOrFail($validated['feed_type_id']),
            (float) $validated['quantity'],
            $request->user(),
            isset($validated['fed_on']) ? Carbon::parse($validated['fed_on']) : null,
            $validated['notes'] ?? null,
        );

        return (new FeedingResource($feeding->load(['pigLot:id,code', 'feedType:id,name', 'user:id,name'])))
            ->response()
            ->setStatusCode(201);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWeighingRequest;
use App\Http\Resources\WeighingResource;
use App\Models\PigLot;
use App\Models\Weighing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class WeighingsController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Weighing::class);

        return WeighingResource::collection(
            Weighing::query()
                ->with(['pigLot:id,code', 'user:id,name'])
                ->latest('weighed_on')
                ->latest('id')
                ->paginate()
                ->withQueryString()
        );
    }

    public function store(StoreWeighingRequest $request): JsonResponse
    {
        Gate::authorize('create', Weighing::class);

        $validated = $request->validated();

        $lot = PigLot::query()->findOrFail($validated['pig_lot_id']);

        $weighing = new Weighing;
        $weighing->forceFill([
            'farm_id' => $lot->farm_id,
            'pig_lot_id' => $lot->id,
            'user_id' => $request->user()->id,
            'weighed_on' => Carbon::parse($validated['weighed_on'])->toDateString(),
            'average_weight' => $validated['average_weight'],
            'sample_size' => $validated['sample_size'],
        ])->save();

        return (new WeighingResource($weighing->load(['pigLot:id,code', 'user:id,name'])))
            ->response()
            ->setStatusCode(201);
    }
}

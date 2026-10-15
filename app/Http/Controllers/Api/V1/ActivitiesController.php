<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ActivitiesController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Activity::class);

        return ActivityResource::collection(
            Activity::query()
                ->with(['user:id,name', 'pigLot:id,code'])
                ->latest('performed_on')
                ->latest('id')
                ->paginate()
                ->withQueryString()
        );
    }

    public function store(StoreActivityRequest $request): ActivityResource
    {
        Gate::authorize('create', Activity::class);

        $activity = new Activity;
        $activity->forceFill(array_merge(
            ['farm_id' => $request->user()->farm_id, 'user_id' => $request->user()->id],
            $request->validated(),
        ))->save();

        return (new ActivityResource($activity->load(['user:id,name', 'pigLot:id,code'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Activity $activity): ActivityResource
    {
        Gate::authorize('view', $activity);

        return new ActivityResource($activity->load(['user:id,name', 'pigLot:id,code']));
    }
}

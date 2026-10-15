<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVaccineScheduleRequest;
use App\Http\Requests\UpdateVaccineScheduleRequest;
use App\Http\Resources\VaccineScheduleResource;
use App\Models\VaccineSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class VaccineSchedulesController extends Controller
{
    /**
     * Calendriers de vaccination (manager en écriture, technician en lecture).
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', VaccineSchedule::class);

        return VaccineScheduleResource::collection(
            VaccineSchedule::query()->orderBy('target_age_days')->paginate()
        );
    }

    public function store(StoreVaccineScheduleRequest $request): JsonResponse
    {
        Gate::authorize('create', VaccineSchedule::class);

        $schedule = new VaccineSchedule;
        $schedule->forceFill(array_merge(
            ['farm_id' => $request->user()->farm_id],
            $request->validated(),
        ))->save();

        return (new VaccineScheduleResource($schedule))->response()->setStatusCode(201);
    }

    public function show(VaccineSchedule $vaccine_schedule): VaccineScheduleResource
    {
        Gate::authorize('view', $vaccine_schedule);

        return new VaccineScheduleResource($vaccine_schedule);
    }

    public function update(UpdateVaccineScheduleRequest $request, VaccineSchedule $vaccine_schedule): VaccineScheduleResource
    {
        Gate::authorize('update', $vaccine_schedule);

        $vaccine_schedule->fill($request->validated())->save();

        return new VaccineScheduleResource($vaccine_schedule);
    }

    public function destroy(VaccineSchedule $vaccine_schedule): Response
    {
        Gate::authorize('delete', $vaccine_schedule);

        $vaccine_schedule->delete();

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePenRequest;
use App\Http\Requests\UpdatePenRequest;
use App\Http\Resources\PenResource;
use App\Models\Pen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PensController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Pen::class);

        return PenResource::collection(
            Pen::query()->with('farm')->orderBy('code')->paginate()
        );
    }

    public function store(StorePenRequest $request): JsonResponse
    {
        Gate::authorize('create', Pen::class);

        $pen = new Pen;
        $pen->forceFill(array_merge(
            ['farm_id' => $request->user()->farm_id],
            $request->validated(),
        ))->save();

        return (new PenResource($pen))->response()->setStatusCode(201);
    }

    public function show(Pen $pen): PenResource
    {
        Gate::authorize('view', $pen);

        return new PenResource($pen);
    }

    public function update(UpdatePenRequest $request, Pen $pen): PenResource
    {
        Gate::authorize('update', $pen);

        $pen->fill($request->validated())->save();

        return new PenResource($pen);
    }

    public function destroy(Pen $pen): Response
    {
        Gate::authorize('delete', $pen);

        $pen->delete();

        return response()->noContent();
    }
}

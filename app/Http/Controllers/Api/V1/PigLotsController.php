<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePigLotRequest;
use App\Http\Requests\UpdatePigLotRequest;
use App\Http\Resources\PigLotResource;
use App\Jobs\GenerateLotReportJob;
use App\Models\PigLot;
use App\Services\LotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PigLotsController extends Controller
{
    /**
     * Liste paginée avec recherche, filtres, tri protégé et pagination.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', PigLot::class);

        $perPage = min(max($request->integer('per_page', 15), 1), 50);

        $lots = PigLot::query()
            ->when($request->filled('q'), fn ($query) => $query->search($request->string('q')))
            ->when($request->filled('status'), fn ($query) => $query->statusIs($request->string('status')))
            ->when($request->filled('pen_id'), fn ($query) => $query->forPen($request->integer('pen_id')))
            ->arrivedFrom($request->input('arrived_from'))
            ->arrivedTo($request->input('arrived_to'))
            ->sortApi(
                $request->string('sort', 'created_at')->toString(),
                $request->string('direction', 'desc')->toString(),
            )
            ->with(['pen:id,code,name'])
            ->paginate($perPage)
            ->withQueryString();

        return PigLotResource::collection($lots);
    }

    public function store(StorePigLotRequest $request, LotService $lots): JsonResponse
    {
        Gate::authorize('create', PigLot::class);

        $lot = $lots->open(
            $request->validated(),
            $request->user(),
            $request->validated('purchase_cost') !== null ? (float) $request->validated('purchase_cost') : null,
        );

        return (new PigLotResource($lot->load('pen')))->response()->setStatusCode(201);
    }

    public function show(PigLot $pig_lot): PigLotResource
    {
        Gate::authorize('view', $pig_lot);

        return new PigLotResource($pig_lot->load('pen'));
    }

    public function update(UpdatePigLotRequest $request, PigLot $pig_lot): PigLotResource
    {
        Gate::authorize('update', $pig_lot);

        $pig_lot->fill($request->validated())->save();

        return new PigLotResource($pig_lot->load('pen'));
    }

    public function destroy(PigLot $pig_lot): Response
    {
        Gate::authorize('delete', $pig_lot);

        $pig_lot->delete();

        return response()->noContent();
    }

    /**
     * Indicateurs de gestion du lot (IC, GMQ, mortalité, marge...).
     */
    public function indicators(PigLot $pig_lot, LotService $lots): JsonResponse
    {
        Gate::authorize('view', $pig_lot);

        return response()->json($lots->indicators($pig_lot));
    }

    /**
     * Clôture manuelle d'un lot.
     */
    public function close(PigLot $pig_lot, Request $request, LotService $lots): PigLotResource
    {
        Gate::authorize('update', $pig_lot);

        $closed = $lots->close($pig_lot, $request->user());

        return new PigLotResource($closed);
    }

    /**
     * Génération asynchrone du rapport JSON de fin de lot.
     */
    public function report(PigLot $pig_lot): JsonResponse
    {
        Gate::authorize('view', $pig_lot);

        GenerateLotReportJob::dispatch($pig_lot);

        return response()->json([
            'message' => 'Le rapport du lot est en cours de génération.',
        ], 202);
    }
}

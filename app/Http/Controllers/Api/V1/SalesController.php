<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\PigLot;
use App\Models\Sale;
use App\Services\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class SalesController extends Controller
{
    /**
     * Finances réservées aux managers (403 pour un technicien).
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Sale::class);

        return SaleResource::collection(
            Sale::query()
                ->with(['pigLot:id,code', 'user:id,name'])
                ->latest('sold_on')
                ->latest('id')
                ->paginate()
                ->withQueryString()
        );
    }

    public function store(StoreSaleRequest $request, FinanceService $finance): JsonResponse
    {
        Gate::authorize('create', Sale::class);

        $validated = $request->validated();

        $sale = $finance->recordSale(
            lot: PigLot::query()->findOrFail($validated['pig_lot_id']),
            headCount: (int) $validated['head_count'],
            totalWeight: (float) $validated['total_weight'],
            pricePerKg: (float) $validated['price_per_kg'],
            customerName: $validated['customer_name'],
            user: $request->user(),
            soldOn: Carbon::parse($validated['sold_on']),
        );

        return (new SaleResource($sale->load(['pigLot:id,code', 'user:id,name'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Sale $sale): SaleResource
    {
        Gate::authorize('view', $sale);

        return new SaleResource($sale->load(['pigLot:id,code', 'user:id,name']));
    }
}

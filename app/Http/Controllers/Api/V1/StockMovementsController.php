<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Resources\StockMovementResource;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StockMovementsController extends Controller
{
    /**
     * Journal des mouvements de stock (lecture seule, filtrable).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', StockMovement::class);

        $request->validate([
            'feed_type_id' => ['nullable', 'integer', Rule::exists('feed_types', 'id')->where('farm_id', $request->user()->farm_id)],
            'type' => ['nullable', Rule::enum(StockMovementType::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $perPage = min(max($request->integer('per_page', 15), 1), 50);

        $movements = StockMovement::query()
            ->when($request->filled('feed_type_id'), fn ($query) => $query->where('feed_type_id', $request->integer('feed_type_id')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->filled('from'), fn ($query) => $query->where('moved_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($query) => $query->where('moved_at', '<=', $request->input('to').' 23:59:59'))
            ->with(['feedType:id,name', 'pigLot:id,code', 'user:id,name'])
            ->latest('moved_at')
            ->paginate($perPage)
            ->withQueryString();

        return StockMovementResource::collection($movements);
    }
}

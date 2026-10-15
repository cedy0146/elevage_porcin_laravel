<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Models\PigLot;
use App\Services\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class ExpensesController extends Controller
{
    /**
     * Finances réservées aux managers (403 pour un technicien).
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Expense::class);

        return ExpenseResource::collection(
            Expense::query()
                ->with(['pigLot:id,code', 'user:id,name'])
                ->latest('spent_on')
                ->latest('id')
                ->paginate()
                ->withQueryString()
        );
    }

    public function store(StoreExpenseRequest $request, FinanceService $finance): JsonResponse
    {
        Gate::authorize('create', Expense::class);

        $validated = $request->validated();

        $expense = $finance->recordExpense(
            farm: $request->user()->farm,
            category: $validated['category'],
            amount: (float) $validated['amount'],
            spentOn: Carbon::parse($validated['spent_on']),
            user: $request->user(),
            pigLot: isset($validated['pig_lot_id'])
                ? PigLot::query()->findOrFail($validated['pig_lot_id'])
                : null,
            description: $validated['description'] ?? null,
        );

        return (new ExpenseResource($expense->load(['pigLot:id,code', 'user:id,name'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Expense $expense): ExpenseResource
    {
        Gate::authorize('view', $expense);

        return new ExpenseResource($expense->load(['pigLot:id,code', 'user:id,name']));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): ExpenseResource
    {
        Gate::authorize('update', $expense);

        $expense->fill($request->validated())->save();

        return new ExpenseResource($expense->load(['pigLot:id,code', 'user:id,name']));
    }

    public function destroy(Expense $expense): Response
    {
        Gate::authorize('delete', $expense);

        $expense->delete();

        return response()->noContent();
    }
}

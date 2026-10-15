<?php

use App\Http\Controllers\Api\V1\ActivitiesController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ExpensesController;
use App\Http\Controllers\Api\V1\FarmsController;
use App\Http\Controllers\Api\V1\FeedingsController;
use App\Http\Controllers\Api\V1\FeedTypesController;
use App\Http\Controllers\Api\V1\MortalitiesController;
use App\Http\Controllers\Api\V1\PensController;
use App\Http\Controllers\Api\V1\PigLotsController;
use App\Http\Controllers\Api\V1\SalesController;
use App\Http\Controllers\Api\V1\StockMovementsController;
use App\Http\Controllers\Api\V1\UsersController;
use App\Http\Controllers\Api\V1\WeighingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        Route::get('dashboard', [DashboardController::class, 'index']);

        Route::apiResource('pens', PensController::class);

        Route::apiResource('pig-lots', PigLotsController::class);
        Route::get('pig-lots/{pig_lot}/indicators', [PigLotsController::class, 'indicators']);
        Route::post('pig-lots/{pig_lot}/close', [PigLotsController::class, 'close']);
        Route::post('pig-lots/{pig_lot}/report', [PigLotsController::class, 'report']);

        Route::get('feed-types/autonomy', [FeedTypesController::class, 'autonomy']);
        Route::apiResource('feed-types', FeedTypesController::class);
        Route::post('feed-types/{feed_type}/entries', [FeedTypesController::class, 'entries']);
        Route::post('feed-types/{feed_type}/adjustments', [FeedTypesController::class, 'adjustments']);

        Route::apiResource('activities', ActivitiesController::class)->only(['index', 'store', 'show']);

        Route::get('stock-movements', [StockMovementsController::class, 'index']);

        Route::apiResource('feedings', FeedingsController::class)->only(['index', 'store']);
        Route::apiResource('weighings', WeighingsController::class)->only(['index', 'store']);
        Route::apiResource('mortalities', MortalitiesController::class)->only(['index', 'store']);

        Route::apiResource('expenses', ExpensesController::class);
        Route::apiResource('sales', SalesController::class)->only(['index', 'store', 'show']);

        Route::middleware('role:admin')->group(function () {
            Route::apiResource('farms', FarmsController::class);
            Route::apiResource('users', UsersController::class);
        });
    });
});

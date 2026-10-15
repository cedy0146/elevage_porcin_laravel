<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Tableau de bord agrégé de l'exploitation de l'utilisateur.
     */
    public function index(Request $request, DashboardService $dashboard): JsonResponse
    {
        $farm = $request->user()->farm;

        abort_if($farm === null, 403, 'Aucune exploitation associée à votre compte.');

        return response()->json($dashboard->forFarm($farm));
    }
}

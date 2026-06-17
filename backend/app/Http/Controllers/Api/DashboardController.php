<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function admin()
    {
        $stats = $this->dashboardService->getAdminStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    public function seller()
    {
        $user = Auth::user();
        $store = $user->profile->store;

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum memiliki toko',
            ], 404);
        }

        $stats = $this->dashboardService->getSellerStats($store->id);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    public function buyer()
    {
        $user = Auth::user();
        $stats = $this->dashboardService->getBuyerStats($user->id);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
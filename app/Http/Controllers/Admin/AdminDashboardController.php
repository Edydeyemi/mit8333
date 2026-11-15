<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardMetricsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function __construct(
        protected DashboardMetricsService $metricsService
    ) {}

    /**
     * Display the Executive Admin & Operations Command Dashboard.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        if (!$user->can('reports.view')) {
            abort(403, 'Unauthorized access to campus operations command dashboard.');
        }

        $metrics = $this->metricsService->getAdminMetrics();

        return view('admin.admin_dashboard', [
            'user' => $user,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Provide asynchronous JSON payload for live widgets and Chart.js polling.
     */
    public function data(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->can('reports.view')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $metrics = $this->metricsService->getAdminMetrics();

        return response()->json($metrics);
    }
}


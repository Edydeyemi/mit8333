<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Services\DashboardMetricsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentDashboardController extends Controller
{
    public function __construct(
        protected DashboardMetricsService $metricsService
    ) {}

    /**
     * Display the student / resident portal overview dashboard.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $metrics = $this->metricsService->getUserMetrics($user);

        // Fetch Eloquent models for server-side Blade table rendering
        $recentRequests = ServiceRequest::forUser($user->id)
            ->with(['category', 'assignedOfficer'])
            ->latest()
            ->take(5)
            ->get();

        return view('frontend.student_dashboard', [
            'user' => $user,
            'totalLogged' => $metrics['kpis']['totalLogged'],
            'inProgress' => $metrics['kpis']['inProgress'],
            'resolved' => $metrics['kpis']['resolved'],
            'activeTickets' => $metrics['kpis']['activeTickets'],
            'emergencyCount' => $metrics['kpis']['emergencyCount'],
            'recentRequests' => $recentRequests,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Return asynchronous JSON metrics and tickets for live polling.
     */
    public function data(Request $request): JsonResponse
    {
        $user = $request->user();
        $metrics = $this->metricsService->getUserMetrics($user);

        return response()->json($metrics);
    }
}

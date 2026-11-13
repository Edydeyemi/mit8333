<?php

declare(strict_types=1);

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Services\DashboardMetricsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacilityDashboardController extends Controller
{
    public function __construct(
        protected DashboardMetricsService $metricsService
    ) {}

    /**
     * Display the facility officer's personal desk dashboard.
     */
    public function index(Request $request): View
    {
        $officer = $request->user();
        if (!$officer->can('tasks.view')) {
            abort(403, 'Unauthorized access to facility maintenance desk.');
        }

        $metrics = $this->metricsService->getOfficerMetrics($officer);

        // Fetch Eloquent models for server-side Blade table rendering
        $queue = ServiceRequest::forOfficer($officer->id)
            ->with(['category', 'user'])
            ->whereIn('status', ['assigned', 'in_progress', 'on_hold'])
            ->orderByRaw("CASE priority WHEN 'emergency' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();

        $emergencyTicket = ServiceRequest::forOfficer($officer->id)
            ->where('priority', 'emergency')
            ->whereIn('status', ['assigned', 'in_progress', 'on_hold'])
            ->latest()
            ->first();

        return view('facility.facility_dashboard', [
            'user' => $officer,
            'kpiMyAssigned' => $metrics['kpis']['myAssigned'],
            'kpiMyEmergency' => $metrics['kpis']['myEmergency'],
            'kpiMyResolved' => $metrics['kpis']['myResolved'],
            'slaComplianceRate' => $metrics['kpis']['slaComplianceRate'],
            'queue' => $queue,
            'emergencyTicket' => $emergencyTicket,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Return asynchronous JSON metrics and queue for live widgets.
     */
    public function data(Request $request): JsonResponse
    {
        $officer = $request->user();
        if (!$officer->can('tasks.view')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $metrics = $this->metricsService->getOfficerMetrics($officer);

        return response()->json($metrics);
    }
}

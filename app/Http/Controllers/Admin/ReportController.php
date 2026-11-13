<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\DashboardMetricsService;
use App\Services\ExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        protected DashboardMetricsService $metricsService,
        protected ExportService $exportService
    ) {}

    /**
     * Display the Executive Analytics, SLA Performance, and Immutable Audit Trail.
     */
    public function index(Request $request): View
    {
        Gate::authorize('reports.view');

        // 1. Executive Performance Digest
        $totalMonthlyRequests = ServiceRequest::where('created_at', '>=', now()->startOfMonth())->count();
        $overallSlaRate = $this->metricsService->calculateSlaCompliance();
        $activeTechnicians = User::where('role', 'officer')->where('status', 'active')->count();

        // 2. Departmental SLA Compliance Breakdown
        $categories = ServiceCategory::where('is_active', true)->get();
        $departmentalSla = [];

        foreach ($categories as $category) {
            $catRequests = ServiceRequest::where('service_category_id', $category->id)->count();
            $catResolved = ServiceRequest::where('service_category_id', $category->id)
                ->where('status', 'resolved')
                ->count();

            // Calculate category specific SLA
            $catRate = 95.0; // Default base
            if ($catResolved > 0) {
                $targetHours = $category->sla_hours ?: 24;
                $resolvedList = ServiceRequest::where('service_category_id', $category->id)
                    ->where('status', 'resolved')
                    ->whereNotNull('resolved_at')
                    ->get(['created_at', 'resolved_at']);

                $compliant = 0;
                foreach ($resolvedList as $item) {
                    if (Carbon::parse($item->created_at)->diffInHours(Carbon::parse($item->resolved_at), false) <= $targetHours) {
                        $compliant++;
                    }
                }

                $catRate = round(($compliant / $catResolved) * 100, 1);
            }

            $departmentalSla[] = [
                'name' => $category->name,
                'slug' => $category->slug,
                'target_hours' => $category->sla_hours ?: 24,
                'compliance_rate' => $catRate,
                'status_color' => $catRate >= 95 ? 'success' : ($catRate >= 90 ? 'warning' : 'danger'),
            ];
        }

        // 3. High-Frequency Campus Failure Zones (Hotspots)
        $hotspots = ServiceRequest::select('building', 'wing_floor', DB::raw('COUNT(*) as total_incidents'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('building', 'wing_floor')
            ->orderByDesc('total_incidents')
            ->limit(5)
            ->get();

        // 4. Immutable Audit Stream with Filtering
        $auditQuery = AuditLog::with('user');

        if ($request->filled('action')) {
            $auditQuery->where('action', $request->input('action'));
        }

        if ($request->filled('user_id')) {
            $auditQuery->where('actor_id', $request->input('user_id'));
        }

        if ($request->filled('from_date')) {
            $auditQuery->whereDate('created_at', '>=', Carbon::parse($request->input('from_date')));
        }

        if ($request->filled('to_date')) {
            $auditQuery->whereDate('created_at', '<=', Carbon::parse($request->input('to_date')));
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $auditQuery->where(function ($q) use ($term): void {
                $q->where('action', 'like', "%{$term}%")
                    ->orWhere('actor_name', 'like', "%{$term}%")
                    ->orWhereHas('actor', function ($u) use ($term): void {
                        $u->where('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%");
                    });
            });
        }

        $auditLogs = $auditQuery->latest()->paginate(20)->withQueryString();
        $auditUsers = User::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']);

        return view('admin.reports.index', [
            'totalMonthlyRequests' => $totalMonthlyRequests,
            'overallSlaRate' => $overallSlaRate,
            'activeTechnicians' => $activeTechnicians,
            'departmentalSla' => $departmentalSla,
            'hotspots' => $hotspots,
            'auditLogs' => $auditLogs,
            'auditUsers' => $auditUsers,
            'filters' => $request->all(),
        ]);
    }

    /**
     * Export service requests to Excel XLSX.
     */
    public function exportRequests(Request $request): BinaryFileResponse
    {
        Gate::authorize('reports.view');
        return $this->exportService->exportServiceRequests($request->all());
    }

    /**
     * Export officer KPIs to Excel XLSX.
     */
    public function exportOfficers(): BinaryFileResponse
    {
        Gate::authorize('reports.view');
        return $this->exportService->exportOfficerPerformance();
    }

    /**
     * Export audit logs to Excel XLSX.
     */
    public function exportAudits(Request $request): BinaryFileResponse
    {
        Gate::authorize('reports.view');
        return $this->exportService->exportAuditLogs($request->all());
    }
}

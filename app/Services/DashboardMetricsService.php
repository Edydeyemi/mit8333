<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardMetricsService
{
    /**
     * Standard SLA resolution hours mapped by priority.
     */
    public const SLA_HOURS = [
        'emergency' => 4,
        'high' => 24,
        'medium' => 48,
        'low' => 72,
    ];

    /**
     * Dashboard chart theme palette.
     */
    public const CHART_COLORS = [
        '#c59b27', // Gold
        '#f59e0b', // Amber
        '#10b981', // Emerald
        '#0284c7', // Sky
        '#8b5cf6', // Purple
        '#f43f5e', // Rose
        '#0f1d36', // Navy
    ];

    /**
     * Compile administrative overview metrics, charts data, and audit stream.
     *
     * @return array<string, mixed>
     */
    public function getAdminMetrics(): array
    {
        $totalRequests = ServiceRequest::count();
        $activeDispatches = ServiceRequest::whereIn('status', ['assigned', 'in_progress', 'on_hold'])->count();
        $emergencyCount = ServiceRequest::where('priority', 'emergency')
            ->whereNotIn('status', ['resolved', 'cancelled', 'rejected'])
            ->count();
        $unassignedCount = ServiceRequest::where('status', 'submitted')->count();
        $slaComplianceRate = $this->calculateSlaCompliance();

        $trends = $this->get7DayRequestTrends();
        $categories = $this->getCategoryBreakdown();
        $officerPerformance = $this->getOfficerPerformance();
        $recentAudits = $this->getRecentAuditLogs();

        return [
            'kpis' => [
                'totalRequests' => $totalRequests,
                'activeDispatches' => $activeDispatches,
                'emergencyCount' => $emergencyCount,
                'unassignedCount' => $unassignedCount,
                'slaComplianceRate' => $slaComplianceRate,
            ],
            'trends' => $trends,
            'categories' => $categories,
            'officerPerformance' => $officerPerformance,
            'recentAudits' => $recentAudits,
        ];
    }

    /**
     * Compile facility maintenance technician desk metrics and queue.
     *
     * @param User $officer
     * @return array<string, mixed>
     */
    public function getOfficerMetrics(User $officer): array
    {
        $activeQuery = ServiceRequest::forOfficer($officer->id)
            ->whereIn('status', ['assigned', 'in_progress', 'on_hold']);

        $kpiMyAssigned = (clone $activeQuery)->count();
        $kpiMyEmergency = (clone $activeQuery)->where('priority', 'emergency')->count();
        $kpiMyResolved = ServiceRequest::forOfficer($officer->id)
            ->where('status', 'resolved')
            ->where('resolved_at', '>=', now()->startOfMonth())
            ->count();

        $slaRate = $this->calculateSlaCompliance($officer->id);

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

        $velocity = $this->getOfficerWeeklyVelocity($officer->id);
        $categories = $this->getOfficerCategoryBreakdown($officer->id);

        return [
            'kpis' => [
                'myAssigned' => $kpiMyAssigned,
                'myEmergency' => $kpiMyEmergency,
                'myResolved' => $kpiMyResolved,
                'slaComplianceRate' => $slaRate,
            ],
            'emergencyTicket' => $emergencyTicket ? [
                'id' => $emergencyTicket->id,
                'slug' => $emergencyTicket->slug,
                'tracking_code' => $emergencyTicket->tracking_code,
                'title' => $emergencyTicket->title,
                'building' => $emergencyTicket->building,
                'room_number' => $emergencyTicket->room_number,
                'show_url' => route('facility.tasks.show', $emergencyTicket->slug),
            ] : null,
            'queue' => $queue->map(fn(ServiceRequest $item) => [
                'id' => $item->id,
                'slug' => $item->slug,
                'tracking_code' => $item->tracking_code,
                'category_name' => $item->category?->name ?: 'General',
                'category_icon' => $item->category?->icon ?: 'bi-tools',
                'category_color' => $item->category?->color ?: '#0a192f',
                'title' => $item->title,
                'building' => $item->building,
                'room_number' => $item->room_number,
                'priority' => ucfirst($item->priority),
                'priority_badge_class' => $item->priority_badge_class,
                'status_label' => $item->status_label,
                'status_badge_class' => $item->status_badge_class,
                'submitter_name' => $item->user?->name ?: 'Resident',
                'show_url' => route('facility.tasks.show', $item->slug),
            ])->values()->all(),
            'velocity' => $velocity,
            'categories' => $categories,
        ];
    }

    /**
     * Compile student / resident portal metrics, recent requests, and chart distribution.
     *
     * @param User $user
     * @return array<string, mixed>
     */
    public function getUserMetrics(User $user): array
    {
        $totalLogged = ServiceRequest::forUser($user->id)->count();
        $inProgress = ServiceRequest::forUser($user->id)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->count();
        $resolved = ServiceRequest::forUser($user->id)
            ->where('status', 'resolved')
            ->count();
        $activeTickets = ServiceRequest::forUser($user->id)
            ->whereIn('status', ['submitted', 'assigned', 'in_progress', 'on_hold'])
            ->count();
        $emergencyCount = ServiceRequest::forUser($user->id)
            ->where('priority', 'emergency')
            ->whereNotIn('status', ['resolved', 'cancelled', 'rejected'])
            ->count();

        $recentRequests = ServiceRequest::forUser($user->id)
            ->with(['category', 'assignedOfficer'])
            ->latest()
            ->take(5)
            ->get();

        $statusBreakdown = $this->getUserStatusBreakdown($user->id);
        $categoryBreakdown = $this->getUserCategoryBreakdown($user->id);

        return [
            'kpis' => [
                'totalLogged' => $totalLogged,
                'inProgress' => $inProgress,
                'resolved' => $resolved,
                'activeTickets' => $activeTickets,
                'emergencyCount' => $emergencyCount,
            ],
            'recentRequests' => $recentRequests->map(fn(ServiceRequest $req) => [
                'id' => $req->id,
                'slug' => $req->slug,
                'tracking_code' => $req->tracking_code,
                'category_name' => $req->category?->name ?: 'General',
                'category_icon' => $req->category?->icon ?: 'bi-tools',
                'title' => $req->title,
                'building' => $req->building,
                'room_number' => $req->room_number,
                'priority' => ucfirst($req->priority),
                'priority_badge_class' => $req->priority_badge_class,
                'status_label' => $req->status_label,
                'status_badge_class' => $req->status_badge_class,
                'officer_name' => $req->assignedOfficer?->name ?: 'Pending Dispatch',
                'show_url' => route('user.requests.show', $req->slug),
            ])->values()->all(),
            'statusBreakdown' => $statusBreakdown,
            'categoryBreakdown' => $categoryBreakdown,
        ];
    }

    /**
     * Calculate institution-wide or technician-specific SLA Compliance Rate (0-100%).
     *
     * @param int|null $officerId
     * @return float
     */
    public function calculateSlaCompliance(?int $officerId = null): float
    {
        $query = ServiceRequest::where('status', 'resolved')
            ->whereNotNull('resolved_at');

        if ($officerId !== null) {
            $query->where('assigned_officer_id', $officerId);
        }

        $resolvedRequests = $query->get(['id', 'priority', 'created_at', 'resolved_at']);

        if ($resolvedRequests->isEmpty()) {
            return 95.0; // Institutional baseline SLA compliance
        }

        $metCount = 0;
        foreach ($resolvedRequests as $req) {
            $slaLimitHours = self::SLA_HOURS[$req->priority] ?? 48;
            $createdAt = Carbon::parse($req->created_at);
            $resolvedAt = Carbon::parse($req->resolved_at);

            if ($createdAt->diffInHours($resolvedAt, false) <= $slaLimitHours) {
                $metCount++;
            }
        }

        return round(($metCount / $resolvedRequests->count()) * 100, 1);
    }

    /**
     * Generate 7-day chronological incident influx vs resolution trends.
     *
     * @return array<string, mixed>
     */
    public function get7DayRequestTrends(): array
    {
        $labels = [];
        $logged = [];
        $resolved = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $labels[] = $day->format('D'); // e.g., Mon, Tue

            $start = $day->copy()->startOfDay();
            $end = $day->copy()->endOfDay();

            $logged[] = ServiceRequest::whereBetween('created_at', [$start, $end])->count();
            $resolved[] = ServiceRequest::where('status', 'resolved')
                ->whereBetween('resolved_at', [$start, $end])
                ->count();
        }

        return [
            'labels' => $labels,
            'logged' => $logged,
            'resolved' => $resolved,
        ];
    }

    /**
     * Aggregate incident distribution by active service categories.
     *
     * @return array<string, mixed>
     */
    public function getCategoryBreakdown(): array
    {
        $categories = ServiceCategory::withCount('requests')
            ->orderByDesc('requests_count')
            ->get();

        $labels = [];
        $counts = [];
        $colors = [];

        foreach ($categories as $index => $cat) {
            $labels[] = $cat->name;
            $counts[] = $cat->requests_count;
            $colors[] = self::CHART_COLORS[$index % count(self::CHART_COLORS)];
        }

        // If categories have no requests yet, provide friendly fallback
        if (empty($labels)) {
            $labels = ['Electricity', 'Plumbing', 'Classroom AV', 'Network/IT', 'Furniture', 'HVAC'];
            $counts = [0, 0, 0, 0, 0, 0];
            $colors = array_slice(self::CHART_COLORS, 0, 6);
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
            'colors' => $colors,
        ];
    }

    /**
     * Retrieve lead technician performance and active workload.
     *
     * @return array<string, mixed>
     */
    public function getOfficerPerformance(): array
    {
        $officers = User::where('role', 'officer')
            ->where('status', 'active')
            ->take(6)
            ->get();

        $labels = [];
        $completed = [];
        $active = [];

        foreach ($officers as $officer) {
            $labels[] = $officer->name;
            $completed[] = ServiceRequest::forOfficer($officer->id)
                ->where('status', 'resolved')
                ->count();
            $active[] = ServiceRequest::forOfficer($officer->id)
                ->whereIn('status', ['assigned', 'in_progress', 'on_hold'])
                ->count();
        }

        return [
            'labels' => $labels,
            'completed' => $completed,
            'active' => $active,
        ];
    }

    /**
     * Retrieve recent system audit logs with human-formatted descriptions.
     *
     * @param int $limit
     * @return array<int, array<string, string>>
     */
    public function getRecentAuditLogs(int $limit = 6): array
    {
        $logs = AuditLog::with('actor')
            ->latest()
            ->take($limit)
            ->get();

        return $logs->map(function (AuditLog $log) {
            $actor = $log->actor_name ?: ($log->actor?->name ?: 'System Dispatcher');
            $action = $log->action;
            $timeAgo = Carbon::parse($log->created_at)->diffForHumans();

            $details = "Performed {$action} on " . class_basename($log->auditable_type);
            if ($log->changes && is_array($log->changes)) {
                if (isset($log->changes['status'])) {
                    $old = $log->changes['status']['old'] ?? 'initial';
                    $new = $log->changes['status']['new'] ?? 'updated';
                    $details = "Progressed status from " . ucfirst((string) $old) . " to " . ucfirst((string) $new);
                } elseif (isset($log->changes['assigned_officer_id'])) {
                    $details = "Reassigned work order to maintenance technician";
                } elseif ($action === 'created') {
                    $details = "Submitted new campus service ticket";
                }
            }

            return [
                'id' => (string) $log->id,
                'actor' => $actor,
                'action' => ucfirst($action),
                'details' => $details,
                'time_ago' => $timeAgo,
            ];
        })->all();
    }

    /**
     * Calculate 7-day velocity for an individual technician.
     *
     * @param int $officerId
     * @return array<string, mixed>
     */
    protected function getOfficerWeeklyVelocity(int $officerId): array
    {
        $labels = [];
        $assigned = [];
        $resolved = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $labels[] = $day->format('D');

            $start = $day->copy()->startOfDay();
            $end = $day->copy()->endOfDay();

            $assigned[] = ServiceRequest::forOfficer($officerId)
                ->whereBetween('assigned_at', [$start, $end])
                ->count();

            $resolved[] = ServiceRequest::forOfficer($officerId)
                ->where('status', 'resolved')
                ->whereBetween('resolved_at', [$start, $end])
                ->count();
        }

        return [
            'labels' => $labels,
            'assigned' => $assigned,
            'resolved' => $resolved,
        ];
    }

    /**
     * Group an individual officer's assigned tasks by category.
     *
     * @param int $officerId
     * @return array<string, mixed>
     */
    protected function getOfficerCategoryBreakdown(int $officerId): array
    {
        $tasks = ServiceRequest::forOfficer($officerId)
            ->with('category')
            ->get();

        $grouped = $tasks->groupBy(fn(ServiceRequest $r) => $r->category?->name ?: 'General');

        $labels = [];
        $counts = [];
        $colors = [];
        $idx = 0;

        foreach ($grouped as $catName => $items) {
            $labels[] = $catName;
            $counts[] = count($items);
            $colors[] = self::CHART_COLORS[$idx % count(self::CHART_COLORS)];
            $idx++;
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
            'colors' => $colors,
        ];
    }

    /**
     * User's requests breakdown by lifecycle status.
     *
     * @param int $userId
     * @return array<string, mixed>
     */
    protected function getUserStatusBreakdown(int $userId): array
    {
        $counts = [
            'Submitted' => ServiceRequest::forUser($userId)->where('status', 'submitted')->count(),
            'Assigned' => ServiceRequest::forUser($userId)->where('status', 'assigned')->count(),
            'In Progress' => ServiceRequest::forUser($userId)->where('status', 'in_progress')->count(),
            'On Hold' => ServiceRequest::forUser($userId)->where('status', 'on_hold')->count(),
            'Resolved' => ServiceRequest::forUser($userId)->where('status', 'resolved')->count(),
            'Cancelled' => ServiceRequest::forUser($userId)->whereIn('status', ['cancelled', 'rejected'])->count(),
        ];

        return [
            'labels' => array_keys($counts),
            'counts' => array_values($counts),
            'colors' => [
                '#f59e0b', // Amber (Submitted)
                '#0284c7', // Sky (Assigned)
                '#c59b27', // Gold (In Progress)
                '#64748b', // Slate (On Hold)
                '#10b981', // Emerald (Resolved)
                '#f43f5e', // Rose (Cancelled)
            ],
        ];
    }

    /**
     * User's requests grouped by service category.
     *
     * @param int $userId
     * @return array<string, mixed>
     */
    protected function getUserCategoryBreakdown(int $userId): array
    {
        $requests = ServiceRequest::forUser($userId)->with('category')->get();
        $grouped = $requests->groupBy(fn(ServiceRequest $r) => $r->category?->name ?: 'General');

        $labels = [];
        $counts = [];
        $colors = [];
        $idx = 0;

        foreach ($grouped as $catName => $items) {
            $labels[] = $catName;
            $counts[] = count($items);
            $colors[] = self::CHART_COLORS[$idx % count(self::CHART_COLORS)];
            $idx++;
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
            'colors' => $colors,
        ];
    }
}


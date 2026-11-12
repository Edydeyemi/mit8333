<?php

declare(strict_types=1);

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class FacilityDashboardController extends Controller
{
    /**
     * Display the facility officer's personal desk dashboard.
     */
    public function index(Request $request): View
    {
        $officer = $request->user();
        if (!$officer->can('tasks.view')) {
            abort(403, 'Unauthorized access to facility maintenance desk.');
        }

        $activeQuery = ServiceRequest::forOfficer($officer->id)
            ->whereIn('status', ['assigned', 'in_progress', 'on_hold']);

        $kpiMyAssigned = (clone $activeQuery)->count();
        $kpiMyEmergency = (clone $activeQuery)->where('priority', 'emergency')->count();
        $kpiMyResolved = ServiceRequest::forOfficer($officer->id)
            ->where('status', 'resolved')
            ->where('resolved_at', '>=', now()->startOfMonth())
            ->count();

        // High-priority immediate queue
        $queue = ServiceRequest::forOfficer($officer->id)
            ->with(['category', 'user'])
            ->whereIn('status', ['assigned', 'in_progress', 'on_hold'])
            ->orderByRaw("CASE priority WHEN 'emergency' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();

        // Top emergency banner if any emergency ticket exists
        $emergencyTicket = ServiceRequest::forOfficer($officer->id)
            ->where('priority', 'emergency')
            ->whereIn('status', ['assigned', 'in_progress', 'on_hold'])
            ->latest()
            ->first();

        return view('facility.facility_dashboard', [
            'user' => $officer,
            'kpiMyAssigned' => $kpiMyAssigned,
            'kpiMyEmergency' => $kpiMyEmergency,
            'kpiMyResolved' => $kpiMyResolved,
            'queue' => $queue,
            'emergencyTicket' => $emergencyTicket,
        ]);
    }
}


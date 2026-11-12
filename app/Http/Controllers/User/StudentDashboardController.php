<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StudentDashboardController extends Controller
{
    /**
     * Display the student / resident portal overview dashboard.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $totalLogged = ServiceRequest::forUser($user->id)->count();
        $inProgress = ServiceRequest::forUser($user->id)->whereIn('status', ['assigned', 'in_progress'])->count();
        $resolved = ServiceRequest::forUser($user->id)->where('status', 'resolved')->count();
        $activeTickets = ServiceRequest::forUser($user->id)->whereIn('status', ['submitted', 'assigned', 'in_progress', 'on_hold'])->count();
        $emergencyCount = ServiceRequest::forUser($user->id)->where('priority', 'emergency')->whereNotIn('status', ['resolved', 'cancelled'])->count();

        $recentRequests = ServiceRequest::forUser($user->id)
            ->with(['category', 'assignedOfficer'])
            ->latest()
            ->take(5)
            ->get();

        return view('frontend.student_dashboard', [
            'user' => $user,
            'totalLogged' => $totalLogged,
            'inProgress' => $inProgress,
            'resolved' => $resolved,
            'activeTickets' => $activeTickets,
            'emergencyCount' => $emergencyCount,
            'recentRequests' => $recentRequests,
        ]);
    }
}

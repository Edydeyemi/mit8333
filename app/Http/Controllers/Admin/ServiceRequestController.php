<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignServiceRequestRequest;
use App\Mail\OfficerAssignedNotificationMail;
use App\Models\AuditLog;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestAssignment;
use App\Models\ServiceRequestStatusUpdate;
use App\Models\User;
use App\Notifications\SystemAlertNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ServiceRequestController extends Controller
{
    /**
     * Predefined campus buildings list for filtering.
     *
     * @var array<int, string>
     */
    protected array $buildings = [
        "Queen's Hall (Hostel)",
        'Engineering Complex Block A',
        'Science & Research Center',
        'Main University Library',
        'Maxwell Lecture Theatres',
        'Senate & Administration Building',
        'Postgraduate Hall',
        'Sports & Recreation Pavilion',
        'Other',
    ];

    /**
     * Display a paginated listing of all campus service requests with filtering.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        if (!$user->can('tasks.assign') && !$user->can('reports.view')) {
            abort(403, 'Unauthorized access to facility incident console.');
        }

        $filters = $request->only(['search', 'category', 'status', 'priority', 'building']);

        $query = ServiceRequest::with(['category', 'user', 'assignedOfficer'])
            ->filter($filters)
            ->latest();

        $requests = $query->paginate(15)->withQueryString();
        $categories = ServiceCategory::orderBy('name')->get();
        $officers = User::where('role', 'officer')
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        // Calculate aggregate metrics
        $metrics = [
            'total' => ServiceRequest::count(),
            'unassigned' => ServiceRequest::whereNull('assigned_officer_id')->where('status', 'submitted')->count(),
            'active_dispatches' => ServiceRequest::whereIn('status', ['assigned', 'in_progress', 'on_hold'])->count(),
            'resolved' => ServiceRequest::where('status', 'resolved')->count(),
        ];

        return view('admin.requests.submitted_requests', [
            'requests' => $requests,
            'categories' => $categories,
            'officers' => $officers,
            'buildings' => $this->buildings,
            'filters' => $filters,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Display the specified service request details, history, and status updates.
     */
    public function show(Request $request, ServiceRequest $serviceRequest): View
    {
        $user = $request->user();
        if (!$user->can('tasks.assign') && !$user->can('reports.view')) {
            abort(403, 'Unauthorized access to ticket details.');
        }

        $serviceRequest->load([
            'category',
            'user',
            'assignedOfficer',
            'evidencePhotos',
            'resolutionProofs',
            'statusUpdates.user',
            'statusUpdates.attachments',
            'auditLogs',
        ]);

        $officers = User::where('role', 'officer')
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        return view('admin.requests.request_details', [
            'serviceRequest' => $serviceRequest,
            'officers' => $officers,
        ]);
    }

    /**
     * Assign or reassign a service request to a maintenance officer.
     */
    public function assign(
        AssignServiceRequestRequest $request,
        ServiceRequest $serviceRequest
    ): JsonResponse|RedirectResponse {
        Gate::authorize('assign', $serviceRequest);

        $validated = $request->validated();
        $admin = $request->user();

        /** @var User|null $officer */
        $officer = User::where('id', $validated['assigned_officer_id'])
            ->where('role', 'officer')
            ->where('status', 'active')
            ->first();

        if (!$officer) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected officer is not an active maintenance technician.',
                ], 422);
            }
            return redirect()->back()->with('error', 'The selected officer is not an active maintenance technician.');
        }

        try {
            DB::transaction(function () use ($serviceRequest, $officer, $admin, $validated): void {
                $previousStatus = $serviceRequest->status;
                $previousOfficer = $serviceRequest->assignedOfficer;
                $newStatus = ($previousStatus === 'submitted') ? 'assigned' : $previousStatus;

                // Update service request
                $serviceRequest->assigned_officer_id = $officer->id;
                $serviceRequest->assigned_at = now();
                $serviceRequest->status = $newStatus;
                $serviceRequest->save();

                // Save in separate status updates table
                $notes = "Assigned to maintenance technician {$officer->name}.";
                if (!empty($validated['notes'])) {
                    $notes .= ' Notes: ' . trim((string) $validated['notes']);
                }

                // Close any existing active assignment for this ticket
                ServiceRequestAssignment::where('service_request_id', $serviceRequest->id)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'reassigned',
                        'unassigned_at' => now(),
                    ]);

                // Create dedicated assignment record
                ServiceRequestAssignment::create([
                    'service_request_id' => $serviceRequest->id,
                    'officer_id' => $officer->id,
                    'assigned_by_id' => $admin->id,
                    'assigned_at' => now(),
                    'status' => 'active',
                    'notes' => $validated['notes'] ?? null,
                ]);

                ServiceRequestStatusUpdate::create([
                    'service_request_id' => $serviceRequest->id,
                    'user_id' => $admin->id,
                    'previous_status' => $previousStatus,
                    'status' => $newStatus,
                    'notes' => $notes,
                ]);

                // Record explicitly in audit trail
                AuditLog::log(
                    'assigned',
                    $serviceRequest,
                    [
                        'action_description' => "Work order delegated to {$officer->name}",
                        'previous_officer' => $previousOfficer?->name ?: 'Unassigned',
                        'new_officer' => $officer->name,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus,
                        'admin_notes' => $validated['notes'] ?? null,
                    ],
                    $admin
                );

                // Notify Assigned Officer (In-App & Email)
                $officer->notify(new SystemAlertNotification(
                    title: 'New Work Order Dispatched',
                    message: "You have been assigned to Ticket {$serviceRequest->tracking_code}: {$serviceRequest->title} at {$serviceRequest->building} ({$serviceRequest->room_number}).",
                    category: 'task_assigned',
                    url: route('facility.tasks.index'),
                    icon: 'bi-tools'
                ));

                if ($officer->email) {
                    Mail::to($officer->email)->queue(new OfficerAssignedNotificationMail($serviceRequest, $officer, $officer, true));
                }

                // Notify Submitter (In-App & Email)
                if ($serviceRequest->user) {
                    $serviceRequest->user->notify(new SystemAlertNotification(
                        title: 'Technician Dispatched',
                        message: "Technician {$officer->name} has been allocated to your complaint ({$serviceRequest->tracking_code}).",
                        category: 'technician_assigned',
                        url: route('user.requests.show', $serviceRequest->slug),
                        icon: 'bi-person-check-fill'
                    ));

                    if ($serviceRequest->user->email) {
                        Mail::to($serviceRequest->user->email)->queue(new OfficerAssignedNotificationMail($serviceRequest, $serviceRequest->user, $officer, false));
                    }
                }
            });

            $successMsg = "Work order {$serviceRequest->tracking_code} assigned to {$officer->name} successfully.";

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'officer' => [
                        'id' => $officer->id,
                        'name' => $officer->name,
                        'initials' => $officer->initials,
                    ],
                    'status' => $serviceRequest->status,
                    'status_label' => $serviceRequest->status_label,
                ]);
            }

            return redirect()->back()->with('success', $successMsg);
        } catch (\Throwable $e) {
            Log::error('Failed to assign service request to technician: ' . $e->getMessage(), [
                'exception' => $e,
                'request_id' => $serviceRequest->id,
                'officer_id' => $officer->id,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while dispatching technician: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Unable to dispatch technician. Please try again.');
        }
    }
}


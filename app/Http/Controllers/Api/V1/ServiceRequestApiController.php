<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AssignServiceRequestApiRequest;
use App\Http\Requests\Api\StoreServiceRequestApiRequest;
use App\Http\Requests\Api\UpdateServiceRequestApiRequest;
use App\Http\Requests\Api\UpdateServiceRequestStatusApiRequest;
use App\Mail\OfficerAssignedNotificationMail;
use App\Mail\RequestStatusUpdatedNotificationMail;
use App\Mail\RequestSubmittedNotificationMail;
use App\Mail\TaskCompletedNotificationMail;
use App\Models\AuditLog;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestAssignment;
use App\Models\ServiceRequestStatusUpdate;
use App\Models\User;
use App\Notifications\SystemAlertNotification;
use App\Services\AttachmentStorageService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ServiceRequestApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AttachmentStorageService $attachmentService
    ) {}

    /**
     * Display a paginated listing of service requests based on caller's role.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = ServiceRequest::with([
            'category:id,name,slug,icon',
            'user:id,first_name,last_name,email',
            'assignedOfficer:id,first_name,last_name,email',
        ]);

        // Role-based scope isolation
        if ($user->can('reports.view') || $user->can('tasks.assign')) {
            // Admins can view all, with optional officer filter
            if ($request->filled('officer_id')) {
                $query->where('assigned_officer_id', $request->input('officer_id'));
            }
        } elseif ($user->hasRole('officer') || $user->can('tasks.view')) {
            // Maintenance officers only view tickets assigned to them
            $query->where('assigned_officer_id', $user->id);
        } else {
            // Normal campus users (student/staff) only view their own lodged tickets
            $query->where('user_id', $user->id);
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('category_slug')) {
            $query->whereHas('category', function ($q) use ($request): void {
                $q->where('slug', $request->input('category_slug'));
            });
        }

        if ($request->filled('building')) {
            $query->where('building', $request->input('building'));
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term): void {
                $q->where('tracking_code', 'like', "%{$term}%")
                    ->orWhere('title', 'like', "%{$term}%")
                    ->orWhere('location', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $paginated = $query->latest()->paginate($perPage);

        return $this->successResponse($paginated, 'Service requests retrieved successfully.');
    }

    /**
     * Store a newly created service request with attachments.
     */
    public function store(StoreServiceRequestApiRequest $request): JsonResponse
    {
        $validated = $request->validated();
        /** @var User $user */
        $user = $request->user();

        try {
            $serviceRequest = DB::transaction(function () use ($validated, $user, $request): ServiceRequest {
                $serviceRequest = ServiceRequest::create([
                    'user_id' => $user->id,
                    'service_category_id' => $validated['service_category_id'],
                    'priority' => $validated['priority'],
                    'building' => $validated['building'],
                    'wing_floor' => $validated['wing_floor'],
                    'room_number' => $validated['room_number'],
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'status' => 'submitted',
                ]);

                // Store uploaded evidence photos
                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $file) {
                        if ($file->isValid()) {
                            $this->attachmentService->storeEvidence($file, $serviceRequest, $user);
                        }
                    }
                }

                // Initial status progression update
                ServiceRequestStatusUpdate::create([
                    'service_request_id' => $serviceRequest->id,
                    'user_id' => $user->id,
                    'previous_status' => null,
                    'status' => 'submitted',
                    'notes' => 'Fault ticket lodged via REST API.',
                ]);

                return $serviceRequest;
            });

            // Dispatch alert to campus dispatch administrators & requester with queued emails
            try {
                if ($user->email) {
                    Mail::to($user->email)->queue(new RequestSubmittedNotificationMail($serviceRequest, $user, false));
                }

                $admins = User::whereIn('role', ['admin', 'super_admin'])->get();
                foreach ($admins as $admin) {
                    $admin->notify(new SystemAlertNotification(
                        title: 'New Service Request Lodged (API)',
                        message: "Ticket {$serviceRequest->tracking_code} lodged by {$user->name} in {$serviceRequest->building}.",
                        category: 'dispatch',
                        url: url("/admin/requests/{$serviceRequest->slug}"),
                        icon: 'bi-exclamation-triangle-fill'
                    ));

                    if ($admin->email) {
                        Mail::to($admin->email)->queue(new RequestSubmittedNotificationMail($serviceRequest, $admin, true));
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to dispatch notifications for service request: ' . $e->getMessage());
            }

            $serviceRequest->load(['category', 'user:id,first_name,last_name,email', 'attachments']);

            return $this->successResponse($serviceRequest, 'Service request lodged successfully.', 201);
        } catch (\Throwable $e) {
            Log::error('API: Failed to lodge service request: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('Failed to create service request due to internal error.', 500);
        }
    }

    /**
     * Display full details of a specific service request.
     */
    public function show(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Scope verification
        if ($user->can('reports.view') || $user->can('tasks.assign')) {
            // Admins can view any ticket
        } elseif ($user->hasRole('officer') || $user->can('tasks.view')) {
            if ($serviceRequest->assigned_officer_id !== $user->id) {
                return $this->errorResponse('Access denied. You are not assigned to this work order.', 403);
            }
        } else {
            if ($serviceRequest->user_id !== $user->id) {
                return $this->errorResponse('Access denied. You do not own this service request.', 403);
            }
        }

        $serviceRequest->load([
            'category',
            'user:id,first_name,last_name,email',
            'assignedOfficer:id,first_name,last_name,email',
            'evidencePhotos',
            'resolutionProofs',
            'statusUpdates.user:id,first_name,last_name',
            'statusUpdates.attachments',
            'auditLogs',
        ]);

        return $this->successResponse($serviceRequest, 'Service request details retrieved.');
    }

    /**
     * Update details of an existing service request (Requester only, while status is submitted).
     */
    public function update(UpdateServiceRequestApiRequest $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $validated = $request->validated();
        /** @var User $user */
        $user = $request->user();

        try {
            DB::transaction(function () use ($serviceRequest, $validated, $request, $user): void {
                $serviceRequest->update(array_filter([
                    'service_category_id' => $validated['service_category_id'] ?? null,
                    'priority' => $validated['priority'] ?? null,
                    'building' => $validated['building'] ?? null,
                    'wing_floor' => $validated['wing_floor'] ?? null,
                    'room_number' => $validated['room_number'] ?? null,
                    'title' => $validated['title'] ?? null,
                    'description' => $validated['description'] ?? null,
                ], fn($val) => $val !== null));

                // Process file attachments if uploaded
                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $file) {
                        if ($file->isValid()) {
                            $this->attachmentService->storeEvidence($file, $serviceRequest, $user);
                        }
                    }
                }
            });

            $serviceRequest->load(['category', 'user', 'attachments', 'statusUpdates']);

            return $this->successResponse($serviceRequest, 'Service request updated successfully.');
        } catch (\Throwable $e) {
            Log::error('API: Failed to update service request: ' . $e->getMessage());
            return $this->errorResponse('Failed to update service request.', 500);
        }
    }

    /**
     * Assign or reassign a maintenance technician (Admin / Dispatcher).
     */
    public function assign(AssignServiceRequestApiRequest $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $validated = $request->validated();
        /** @var User $admin */
        $admin = $request->user();

        /** @var User|null $officer */
        $officer = User::where('id', $validated['assigned_officer_id'])
            ->where('role', 'officer')
            ->where('status', 'active')
            ->first();

        if (!$officer) {
            return $this->errorResponse('The selected officer is not an active maintenance technician.', 422);
        }

        try {
            DB::transaction(function () use ($serviceRequest, $officer, $admin, $validated): void {
                $previousStatus = $serviceRequest->status;
                $newStatus = ($previousStatus === 'submitted') ? 'assigned' : $previousStatus;

                $serviceRequest->assigned_officer_id = $officer->id;
                $serviceRequest->assigned_at = now();
                $serviceRequest->status = $newStatus;
                $serviceRequest->save();

                // Close previous active assignment if exists
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

                // Record status update
                ServiceRequestStatusUpdate::create([
                    'service_request_id' => $serviceRequest->id,
                    'user_id' => $admin->id,
                    'previous_status' => $previousStatus,
                    'status' => $newStatus,
                    'notes' => $validated['notes'] ?? "Assigned to maintenance technician {$officer->name}.",
                ]);

                // Audit trail entry
                AuditLog::create([
                    'user_id' => $admin->id,
                    'action' => 'assigned_technician',
                    'auditable_type' => ServiceRequest::class,
                    'auditable_id' => $serviceRequest->id,
                    'details' => "Technician {$officer->name} assigned to ticket {$serviceRequest->tracking_code} via API.",
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            });

            // Dispatch notification to officer & requester
            try {
                $officer->notify(new SystemAlertNotification(
                    title: 'New Maintenance Task Assigned',
                    message: "You have been assigned work order {$serviceRequest->tracking_code} in {$serviceRequest->building}.",
                    category: 'task',
                    url: url("/facility/tasks/{$serviceRequest->slug}"),
                    icon: 'bi-tools'
                ));

                if ($officer->email) {
                    Mail::to($officer->email)->queue(new OfficerAssignedNotificationMail($serviceRequest, $officer, $officer, true));
                }

                if ($serviceRequest->user && $serviceRequest->user->email) {
                    Mail::to($serviceRequest->user->email)->queue(new OfficerAssignedNotificationMail($serviceRequest, $serviceRequest->user, $officer, false));
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to notify officer/user of assignment: ' . $e->getMessage());
            }

            $serviceRequest->load(['category', 'user:id,first_name,last_name', 'assignedOfficer:id,first_name,last_name']);

            return $this->successResponse($serviceRequest, 'Technician assigned successfully.');
        } catch (\Throwable $e) {
            Log::error('API: Failed to assign technician: ' . $e->getMessage());
            return $this->errorResponse('Failed to assign technician due to internal server error.', 500);
        }
    }

    /**
     * Technician updates status of an assigned work order.
     */
    public function updateStatus(
        UpdateServiceRequestStatusApiRequest $request,
        ServiceRequest $serviceRequest
    ): JsonResponse {
        /** @var User $officer */
        $officer = $request->user();

        // Enforce assignment ownership (officers can only transition their own tasks)
        if (!$officer->can('tasks.assign') && $serviceRequest->assigned_officer_id !== $officer->id) {
            return $this->errorResponse('Unauthorized. You are not assigned to this work order.', 403);
        }

        $validated = $request->validated();
        $newStatus = $validated['status'];
        $notes = !empty($validated['notes']) ? trim((string) $validated['notes']) : null;

        try {
            DB::transaction(function () use ($serviceRequest, $newStatus, $notes, $officer, $request): void {
                $previousStatus = $serviceRequest->status;

                $serviceRequest->status = $newStatus;
                if ($newStatus === 'resolved') {
                    $serviceRequest->resolved_at = now();
                }
                $serviceRequest->save();

                $statusUpdate = ServiceRequestStatusUpdate::create([
                    'service_request_id' => $serviceRequest->id,
                    'user_id' => $officer->id,
                    'previous_status' => $previousStatus,
                    'status' => $newStatus,
                    'notes' => $notes,
                ]);

                // Store resolution / progress proof attachments
                if ($request->hasFile('attachments')) {
                    $attachmentType = ($newStatus === 'resolved') ? 'resolution_proof' : 'evidence';
                    foreach ($request->file('attachments') as $file) {
                        if ($file->isValid()) {
                            $this->attachmentService->storeStatusAttachment(
                                file: $file,
                                request: $serviceRequest,
                                update: $statusUpdate,
                                uploader: $officer,
                                type: $attachmentType
                            );
                        }
                    }
                }

                AuditLog::create([
                    'user_id' => $officer->id,
                    'action' => 'status_updated',
                    'auditable_type' => ServiceRequest::class,
                    'auditable_id' => $serviceRequest->id,
                    'details' => "Status transitioned from {$previousStatus} to {$newStatus} by {$officer->name} via API.",
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            });

            // Notify requester of status change
            try {
                if ($serviceRequest->user) {
                    $serviceRequest->user->notify(new SystemAlertNotification(
                        title: 'Work Order Status Updated',
                        message: "Your ticket {$serviceRequest->tracking_code} is now " . strtoupper($newStatus) . '.',
                        category: 'status',
                        url: url("/portal/requests/{$serviceRequest->slug}"),
                        icon: ($newStatus === 'resolved') ? 'bi-check-circle-fill' : 'bi-info-circle-fill'
                    ));

                    if ($serviceRequest->user->email) {
                        if ($newStatus === 'resolved') {
                            Mail::to($serviceRequest->user->email)->queue(new TaskCompletedNotificationMail($serviceRequest, $serviceRequest->user, $notes));
                        } else {
                            Mail::to($serviceRequest->user->email)->queue(new RequestStatusUpdatedNotificationMail($serviceRequest, $serviceRequest->user, $previousStatus, $newStatus, $notes));
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to notify requester of status update: ' . $e->getMessage());
            }

            $serviceRequest->load(['statusUpdates.attachments', 'resolutionProofs']);

            return $this->successResponse($serviceRequest, 'Work order status updated successfully.');
        } catch (\Throwable $e) {
            Log::error('API: Failed to update status: ' . $e->getMessage());
            return $this->errorResponse('Failed to update work order status.', 500);
        }
    }

    /**
     * Cancel a service request (Requester only).
     */
    public function cancel(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (!$serviceRequest->canBeCancelledBy($user)) {
            return $this->errorResponse('This request cannot be cancelled at its current stage.', 403);
        }

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $reason = $request->input('reason', 'Cancelled by requester via API.');

        try {
            DB::transaction(function () use ($serviceRequest, $user, $reason): void {
                $previousStatus = $serviceRequest->status;

                $serviceRequest->status = 'cancelled';
                $serviceRequest->cancelled_at = now();
                $serviceRequest->cancellation_reason = $reason;
                $serviceRequest->save();

                ServiceRequestAssignment::where('service_request_id', $serviceRequest->id)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'cancelled',
                        'unassigned_at' => now(),
                    ]);

                ServiceRequestStatusUpdate::create([
                    'service_request_id' => $serviceRequest->id,
                    'user_id' => $user->id,
                    'previous_status' => $previousStatus,
                    'status' => 'cancelled',
                    'notes' => $reason,
                ]);

                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'cancelled_request',
                    'auditable_type' => ServiceRequest::class,
                    'auditable_id' => $serviceRequest->id,
                    'details' => "Ticket {$serviceRequest->tracking_code} cancelled by requester.",
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            });

            return $this->successResponse($serviceRequest, 'Service request cancelled successfully.');
        } catch (\Throwable $e) {
            Log::error('API: Failed to cancel service request: ' . $e->getMessage());
            return $this->errorResponse('Failed to cancel request.', 500);
        }
    }
}

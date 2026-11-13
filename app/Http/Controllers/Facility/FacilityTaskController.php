<?php

declare(strict_types=1);

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Http\Requests\Facility\UpdateTaskStatusRequest;
use App\Mail\RequestStatusUpdatedNotificationMail;
use App\Mail\TaskCompletedNotificationMail;
use App\Models\AuditLog;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatusUpdate;
use App\Notifications\SystemAlertNotification;
use App\Services\AttachmentStorageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class FacilityTaskController extends Controller
{
    /**
     * Display the field task pipeline, Kanban board, and data table view.
     */
    public function index(Request $request): View
    {
        $officer = $request->user();
        if (!$officer->can('tasks.view')) {
            abort(403, 'Unauthorized access to field task pipeline.');
        }

        $filters = $request->only(['search', 'category']);

        $baseQuery = ServiceRequest::forOfficer($officer->id)
            ->with(['category', 'user', 'attachments', 'statusUpdates'])
            ->filter($filters);

        $allTasks = (clone $baseQuery)
            ->orderByRaw("CASE priority WHEN 'emergency' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->latest()
            ->get();

        // Categorize into Kanban buckets
        $assignedTasks = $allTasks->where('status', 'assigned')->values();
        $inProgressTasks = $allTasks->where('status', 'in_progress')->values();
        $onHoldTasks = $allTasks->where('status', 'on_hold')->values();
        $resolvedTasks = $allTasks->where('status', 'resolved')->values();

        $categories = ServiceCategory::orderBy('name')->get();

        return view('facility.facility_tasks', [
            'user' => $officer,
            'allTasks' => $allTasks,
            'assignedTasks' => $assignedTasks,
            'inProgressTasks' => $inProgressTasks,
            'onHoldTasks' => $onHoldTasks,
            'resolvedTasks' => $resolvedTasks,
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    /**
     * Display full details of an assigned work order.
     */
    public function show(Request $request, ServiceRequest $serviceRequest): View
    {
        Gate::authorize('view', $serviceRequest);

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

        return view('facility.task_details', [
            'serviceRequest' => $serviceRequest,
        ]);
    }

    /**
     * Update the progress status of an assigned work order and upload attachments.
     */
    public function updateStatus(
        UpdateTaskStatusRequest $request,
        ServiceRequest $serviceRequest,
        AttachmentStorageService $attachmentService
    ): JsonResponse|RedirectResponse {
        Gate::authorize('updateStatus', $serviceRequest);

        $validated = $request->validated();
        $officer = $request->user();
        $newStatus = $validated['status'];
        $notes = !empty($validated['notes']) ? trim((string) $validated['notes']) : null;

        try {
            DB::transaction(function () use ($serviceRequest, $newStatus, $notes, $officer, $request, $attachmentService): void {
                $previousStatus = $serviceRequest->status;

                // Update Service Request status
                $serviceRequest->status = $newStatus;
                if ($newStatus === 'resolved') {
                    $serviceRequest->resolved_at = now();
                }
                $serviceRequest->save();

                // Save Status Update in separate table
                $statusUpdate = ServiceRequestStatusUpdate::create([
                    'service_request_id' => $serviceRequest->id,
                    'user_id' => $officer->id,
                    'previous_status' => $previousStatus,
                    'status' => $newStatus,
                    'notes' => $notes,
                ]);

                // Process file attachments if uploaded
                $uploadedFilesCount = 0;
                if ($request->hasFile('attachments')) {
                    $attachmentType = ($newStatus === 'resolved') ? 'resolution_proof' : 'evidence';
                    foreach ($request->file('attachments') as $file) {
                        if ($file->isValid()) {
                            $attachmentService->storeStatusAttachment(
                                file: $file,
                                request: $serviceRequest,
                                update: $statusUpdate,
                                uploader: $officer,
                                type: $attachmentType
                            );
                            $uploadedFilesCount++;
                        }
                    }
                }

                // Add to Audit Trail for this Service Request
                AuditLog::log(
                    'status_updated',
                    $serviceRequest,
                    [
                        'action_description' => "Work order shifted from " . strtoupper($previousStatus) . " to " . strtoupper($newStatus),
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus,
                        'technician_notes' => $notes,
                        'attachments_uploaded' => $uploadedFilesCount,
                        'officer_name' => $officer->name,
                    ],
                    $officer
                );

                // Notify requester
                if ($serviceRequest->user) {
                    $statusLabel = match ($newStatus) {
                        'in_progress' => 'In Progress (Technician on Site)',
                        'on_hold' => 'On Hold (Awaiting Parts/Access)',
                        'resolved' => 'Completed & Resolved',
                        default => ucfirst(str_replace('_', ' ', $newStatus)),
                    };

                    $serviceRequest->user->notify(new SystemAlertNotification(
                        title: "Work Order Update: {$serviceRequest->tracking_code}",
                        message: "Technician {$officer->name} transitioned status to {$statusLabel}." . ($notes ? " Note: {$notes}" : ""),
                        category: 'task_status_updated',
                        url: route('user.requests.show', $serviceRequest->slug),
                        icon: ($newStatus === 'resolved') ? 'bi-check2-circle' : 'bi-arrow-repeat'
                    ));

                    // Queue transactional email
                    if ($serviceRequest->user->email) {
                        if ($newStatus === 'resolved') {
                            Mail::to($serviceRequest->user->email)->queue(new TaskCompletedNotificationMail($serviceRequest, $serviceRequest->user, $notes));
                        } else {
                            Mail::to($serviceRequest->user->email)->queue(new RequestStatusUpdatedNotificationMail($serviceRequest, $serviceRequest->user, $previousStatus, $newStatus, $notes));
                        }
                    }
                }
            });

            $humanStatus = ucfirst(str_replace('_', ' ', $newStatus));
            $successMsg = "Work order {$serviceRequest->tracking_code} updated to {$humanStatus}.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'status' => $serviceRequest->status,
                    'status_label' => $serviceRequest->status_label,
                    'status_badge' => $serviceRequest->status_badge_class,
                ]);
            }

            return redirect()->back()->with('success', $successMsg);
        } catch (\Throwable $e) {
            Log::error('Failed to update task status: ' . $e->getMessage(), [
                'exception' => $e,
                'request_id' => $serviceRequest->id,
                'officer_id' => $officer->id,
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update work order: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to update status. Please try again.');
        }
    }
}


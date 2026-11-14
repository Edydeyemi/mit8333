<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreServiceRequestRequest;
use App\Http\Requests\User\UpdateServiceRequestRequest;
use App\Mail\RequestSubmittedNotificationMail;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestAssignment;
use App\Models\User;
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

class ServiceRequestController extends Controller
{
    /**
     * Predefined campus buildings directory.
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
     * Display a paginated listing of the user's service requests.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = ServiceRequest::forUser($user->id)
            ->with(['category', 'assignedOfficer', 'attachments'])
            ->filter($request->only(['search', 'category', 'status', 'priority']))
            ->latest();

        $requests = $query->paginate(15)->withQueryString();
        $categories = ServiceCategory::active()->orderBy('name')->get();

        return view('frontend.service_requests', [
            'requests' => $requests,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'status', 'priority']),
        ]);
    }

    /**
     * Show the form for creating a new service request.
     */
    public function create(): View
    {
        Gate::authorize('create', ServiceRequest::class);

        $categories = ServiceCategory::active()->orderBy('name')->get();

        return view('frontend.submit_request', [
            'categories' => $categories,
            'buildings' => $this->buildings,
        ]);
    }

    /**
     * Store a newly created service request in storage.
     */
    public function store(
        StoreServiceRequestRequest $request,
        AttachmentStorageService $attachmentService
    ): RedirectResponse {
        $validated = $request->validated();
        $user = $request->user();

        // Handle custom building name if 'Other' was selected
        $building = $validated['building'];
        if ($building === 'Other' && !empty($request->input('custom_building'))) {
            $building = trim((string) $request->input('custom_building'));
        }

        try {
            $serviceRequest = DB::transaction(function () use ($validated, $user, $building, $request, $attachmentService): ServiceRequest {
                $serviceRequest = ServiceRequest::create([
                    'user_id' => $user->id,
                    'service_category_id' => $validated['service_category_id'],
                    'priority' => $validated['priority'],
                    'building' => $building,
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
                            $attachmentService->storeEvidence($file, $serviceRequest, $user);
                        }
                    }
                }

                return $serviceRequest;
            });

            // Notify campus administrators & requester with queued emails and in-app alerts
            try {
                if ($user->email) {
                    Mail::to($user->email)->queue(new RequestSubmittedNotificationMail($serviceRequest, $user, false));
                }

                $admins = User::whereIn('role', ['admin', 'super_admin'])->get();
                foreach ($admins as $admin) {
                    $admin->notify(new SystemAlertNotification(
                        title: 'New Service Request Lodged',
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

            return redirect()
                ->route('user.requests.show', $serviceRequest->slug)
                ->with('success', "Service request {$serviceRequest->tracking_code} submitted successfully! Our dispatch team has been alerted.");
        } catch (\Throwable $e) {
            Log::error('Failed to lodge service request', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Unable to submit service request. Please review your input and try again.');
        }
    }

    /**
     * Show the form for editing an existing service request (while submitted).
     */
    public function edit(ServiceRequest $serviceRequest): View
    {
        Gate::authorize('update', $serviceRequest);

        $categories = ServiceCategory::active()->orderBy('name')->get();

        return view('frontend.edit_request', [
            'serviceRequest' => $serviceRequest,
            'categories' => $categories,
            'buildings' => $this->buildings,
        ]);
    }

    /**
     * Update the specified service request in storage.
     */
    public function update(
        UpdateServiceRequestRequest $request,
        ServiceRequest $serviceRequest,
        AttachmentStorageService $attachmentService
    ): RedirectResponse {
        Gate::authorize('update', $serviceRequest);

        $validated = $request->validated();
        $user = $request->user();

        // Handle custom building name if 'Other' was selected
        $building = $validated['building'];
        if ($building === 'Other' && !empty($request->input('custom_building'))) {
            $building = trim((string) $request->input('custom_building'));
        }

        try {
            DB::transaction(function () use ($serviceRequest, $validated, $building, $request, $attachmentService, $user): void {
                $serviceRequest->update([
                    'service_category_id' => $validated['service_category_id'],
                    'priority' => $validated['priority'],
                    'building' => $building,
                    'wing_floor' => $validated['wing_floor'],
                    'room_number' => $validated['room_number'],
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                ]);

                // Store any newly uploaded attachments
                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $file) {
                        if ($file->isValid()) {
                            $attachmentService->storeEvidence($file, $serviceRequest, $user);
                        }
                    }
                }
            });

            return redirect()
                ->route('user.requests.show', $serviceRequest->slug)
                ->with('success', "Service request {$serviceRequest->tracking_code} updated successfully.");
        } catch (\Throwable $e) {
            Log::error('Failed to update service request', [
                'user_id' => $user->id,
                'request_id' => $serviceRequest->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Unable to update service request. Please review your input and try again.');
        }
    }

    /**
     * Display the specified service request details and lifecycle.
     */
    public function show(ServiceRequest $serviceRequest): View
    {
        Gate::authorize('view', $serviceRequest);

        $serviceRequest->load([
            'category',
            'attachments.uploader',
            'assignedOfficer',
            'user',
            'statusUpdates.user',
            'statusUpdates.attachments',
        ]);
        $latestAudit = $serviceRequest->auditLogs()->first();

        return view('frontend.request_details', [
            'serviceRequest' => $serviceRequest,
            'latestAudit' => $latestAudit,
        ]);
    }

    /**
     * Cancel an existing service request initiated by the student.
     */
    public function cancel(Request $request, ServiceRequest $serviceRequest): JsonResponse|RedirectResponse
    {
        Gate::authorize('cancel', $serviceRequest);

        $request->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $serviceRequest->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $request->input('cancellation_reason', 'Cancelled by requester'),
        ]);

        ServiceRequestAssignment::where('service_request_id', $serviceRequest->id)
            ->where('status', 'active')
            ->update([
                'status' => 'cancelled',
                'unassigned_at' => now(),
            ]);

        $message = "Service request {$serviceRequest->tracking_code} has been successfully cancelled.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => 'cancelled',
            ]);
        }

        return redirect()
            ->route('user.requests.show', $serviceRequest->slug)
            ->with('success', $message);
    }
}

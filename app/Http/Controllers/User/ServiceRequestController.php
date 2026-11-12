<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreServiceRequestRequest;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
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

            // Notify campus administrators of the new incident report
            try {
                $admins = User::whereIn('role', ['admin', 'super_admin'])->get();
                foreach ($admins as $admin) {
                    $admin->notify(new SystemAlertNotification(
                        title: 'New Service Request Lodged',
                        message: "Ticket {$serviceRequest->tracking_code} lodged by {$user->name} in {$serviceRequest->building}.",
                        category: 'dispatch',
                        url: url("/admin/requests/{$serviceRequest->slug}"),
                        icon: 'bi-exclamation-triangle-fill'
                    ));
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to dispatch admin notification for service request: ' . $e->getMessage());
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

@extends('layouts.app')

@section('title', 'Ticket ' . $serviceRequest->tracking_code . ' | ' . (getenv('APP_NAME') ?: 'Headworld'))

@section('header_title')
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('user.requests.index') }}" class="btn btn-sm btn-subtle" title="Back to my requests">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-navy-900 text-warning font-mono">{{ $serviceRequest->tracking_code }}</span>
                <h5 class="fw-bold mb-0 text-navy-900 text-truncate" style="max-width: 400px;">{{ $serviceRequest->title }}
                </h5>
            </div>
            <small class="text-muted">Lodged {{ $serviceRequest->created_at->diffForHumans() }}</small>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-content">
        <!-- Ticket Header Banner -->
        <div class="card-modern p-4 mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                        <span class="badge bg-secondary text-navy-900 border">
                            <i class="bi {{ $serviceRequest->category?->icon ?: 'bi-tools' }} me-1"></i>
                            {{ $serviceRequest->category?->name ?: 'General' }}
                        </span>
                        <span class="{{ $serviceRequest->priority_badge_class }}">
                            <i class="bi bi-clock me-1"></i> {{ ucfirst($serviceRequest->priority) }} Priority
                        </span>
                        <span class="{{ $serviceRequest->status_badge_class }}">
                            <span class="status-dot"></span>{{ $serviceRequest->status_label }}
                        </span>
                        <span class="text-muted small">&bull; Logged
                            {{ $serviceRequest->created_at->format('M d, Y h:i A') }}</span>
                    </div>
                    <h3 class="fw-bold text-navy-900 mb-1">{{ $serviceRequest->title }}</h3>
                    <p class="text-muted small mb-0 font-mono">
                        System Reference:
                        <strong>REQ-{{ str_pad((string) $serviceRequest->id, 5, '0', STR_PAD_LEFT) }}</strong>
                        &bull; Tracking: <strong>{{ $serviceRequest->tracking_code }}</strong>
                    </p>
                </div>

                <!-- Dynamic Action Button -->
                <div class="d-flex gap-2 align-items-center">
                    @if ($serviceRequest->canBeEditedBy(auth()->user()))
                        <a href="{{ route('user.requests.edit', $serviceRequest->slug) }}" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
                            <i class="bi bi-pencil-square"></i> Edit Details
                        </a>
                    @endif
                    @if ($serviceRequest->canBeCancelledBy(auth()->user()))
                        <button type="button" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1"
                            data-bs-toggle="modal" data-bs-target="#cancelTicketModal">
                            <i class="bi bi-x-circle"></i> Cancel Request
                        </button>
                    @endif
                    <a href="{{ route('user.requests.index') }}" class="btn btn-subtle btn-sm">
                        <i class="bi bi-list-task me-1"></i> Back to History
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Details, Photos, Resolution -->
            <div class="col-lg-7">
                <!-- Specification Card -->
                <div class="card-modern p-4 mb-4">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-file-text-fill text-warning"></i> Incident Specification
                    </h5>
                    <p class="text-main mb-4 lead fs-6" style="white-space: pre-line;">{{ $serviceRequest->description }}
                    </p>

                    <div class="row g-3 p-3 rounded-3 bg-light border">
                        <div class="col-sm-6">
                            <span class="small text-muted d-block">Facility Location</span>
                            <strong class="text-navy-900 fs-6">{{ $serviceRequest->building }}</strong>
                            <div class="small text-muted">{{ $serviceRequest->wing_floor }} &bull;
                                {{ $serviceRequest->room_number }}</div>
                        </div>
                        <div class="col-sm-6">
                            <span class="small text-muted d-block">Reported By</span>
                            <strong class="text-navy-900 fs-6">{{ $serviceRequest->user?->name }}</strong>
                            <div class="small text-muted">{{ $serviceRequest->user?->email }}</div>
                            @if ($serviceRequest->user?->mobile)
                                <div class="small text-muted"><i
                                        class="bi bi-telephone me-1"></i>{{ $serviceRequest->user->mobile }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Visual Evidence Gallery -->
                <div class="card-modern p-4 mb-4">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-images text-warning"></i> Visual Evidence & Attachments
                        ({{ $serviceRequest->evidencePhotos->count() }})
                    </h5>

                    @if ($serviceRequest->evidencePhotos->isNotEmpty())
                        <div class="row g-3">
                            @foreach ($serviceRequest->evidencePhotos as $photo)
                                <div class="col-6 col-sm-4">
                                    <div class="card border rounded-3 overflow-hidden shadow-sm h-100">
                                        <div class="position-relative" style="height: 140px; cursor: pointer;"
                                            data-bs-toggle="modal" data-bs-target="#photoModal_{{ $photo->id }}">
                                            <img src="{{ $photo->url }}" alt="{{ $photo->file_name }}"
                                                class="w-100 h-100 object-fit-cover">
                                            <div
                                                class="position-absolute bottom-0 start-0 end-0 p-1 bg-dark bg-opacity-75 text-white small text-truncate px-2">
                                                <i class="bi bi-zoom-in me-1"></i> Preview
                                            </div>
                                        </div>
                                        <div class="p-2 bg-light">
                                            <div class="small fw-semibold text-truncate" title="{{ $photo->file_name }}">
                                                {{ $photo->file_name }}</div>
                                            <div class="small text-muted">{{ $photo->formatted_size }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Photo Lightbox Modal -->
                                <div class="modal fade" id="photoModal_{{ $photo->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h6 class="modal-title fw-bold text-truncate">{{ $photo->file_name }}
                                                    ({{ $photo->formatted_size }})</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body text-center p-0 bg-dark">
                                                <img src="{{ $photo->url }}" alt="{{ $photo->file_name }}" class="img-fluid rounded"
                                                    style="max-height: 75vh;">
                                            </div>
                                            <div class="modal-footer justify-content-between">
                                                <span class="small text-muted">Uploaded
                                                    {{ $photo->created_at->format('M d, Y H:i') }}</span>
                                                <a href="{{ $photo->url }}" target="_blank" download class="btn btn-sm btn-subtle">
                                                    <i class="bi bi-download me-1"></i> Download Original
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted bg-light rounded-3 border">
                            <i class="bi bi-camera-slash fs-2 d-block mb-1"></i>
                            <span class="small">No evidence photos were attached to this request.</span>
                        </div>
                    @endif
                </div>

                <!-- Technician Status Progression Updates -->
                @if ($serviceRequest->statusUpdates->isNotEmpty())
                    <div class="card-modern p-4 mb-4">
                        <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-clock-history text-warning"></i> Technician Progress Notes & Updates
                            ({{ $serviceRequest->statusUpdates->count() }})
                        </h5>

                        <div class="d-flex flex-column gap-3">
                            @foreach ($serviceRequest->statusUpdates as $update)
                                <div class="p-3 rounded-3 bg-light border">
                                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="{{ $update->status_badge_class }}">
                                                <span class="status-dot"></span>{{ $update->status_label }}
                                            </span>
                                            @if ($update->previous_status)
                                                <small class="text-muted">
                                                    (Shifted from <span
                                                        class="fw-semibold">{{ ucfirst(str_replace('_', ' ', $update->previous_status)) }}</span>)
                                                </small>
                                            @endif
                                        </div>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar-event me-1"></i>
                                            {{ $update->created_at->format('M d, Y h:i A') }}
                                        </small>
                                    </div>

                                    @if ($update->notes)
                                        <p class="text-main mb-2 small" style="white-space: pre-line;">{{ $update->notes }}</p>
                                    @endif

                                    <div class="pt-2 border-top small text-muted">
                                        <i class="bi bi-person-badge me-1"></i> Logged by:
                                        <strong>{{ $update->user?->name ?: 'Maintenance Desk' }}</strong>
                                    </div>

                                    @if ($update->attachments->isNotEmpty())
                                        <div class="mt-2 pt-2 border-top">
                                            <span class="small fw-bold text-navy-900 d-block mb-1">
                                                <i class="bi bi-paperclip me-1"></i> Update Attachments:
                                            </span>
                                            <div class="row g-2">
                                                @foreach ($update->attachments as $att)
                                                    <div class="col-6 col-sm-4 col-md-3">
                                                        <div class="card border rounded p-1 shadow-sm text-center bg-white h-100">
                                                            @if ($att->isImage())
                                                                <a href="{{ $att->url }}" target="_blank">
                                                                    <img src="{{ $att->url }}" alt="{{ $att->file_name }}"
                                                                        class="img-fluid rounded"
                                                                        style="height: 60px; object-fit: cover; width: 100%;">
                                                                </a>
                                                            @else
                                                                <div class="py-2">
                                                                    <i class="bi bi-file-earmark-text fs-4 text-primary"></i>
                                                                </div>
                                                            @endif
                                                            <a href="{{ $att->url }}" target="_blank"
                                                                class="small text-truncate d-block text-navy-900 fw-semibold mt-1 px-1"
                                                                style="font-size:0.75rem;">
                                                                {{ $att->file_name }}
                                                            </a>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Resolution / Cancellation Notice -->
                @if ($serviceRequest->status === 'resolved')
                    <div class="card-modern p-4 mb-4 border-success border-2">
                        <div class="d-flex align-items-center gap-2 mb-2 text-success fw-bold fs-5">
                            <i class="bi bi-check-circle-fill"></i> Maintenance Resolved & Verified
                        </div>
                        <p class="text-muted mb-2">
                            This service request was completed and signed off
                            @if ($serviceRequest->resolved_at)
                                on {{ $serviceRequest->resolved_at->format('M d, Y \a\t h:i A') }}
                            @endif.
                        </p>
                    </div>
                @elseif ($serviceRequest->status === 'cancelled')
                    <div class="card-modern p-4 mb-4 border-dark border-2 bg-light">
                        <div class="d-flex align-items-center gap-2 mb-2 text-dark fw-bold fs-5">
                            <i class="bi bi-x-octagon-fill text-danger"></i> Request Cancelled
                        </div>
                        <p class="text-muted mb-1">
                            This service ticket was cancelled
                            @if ($serviceRequest->cancelled_at)
                                on {{ $serviceRequest->cancelled_at->format('M d, Y \a\t h:i A') }}
                            @endif.
                        </p>
                        @if ($serviceRequest->cancellation_reason)
                            <div class="p-3 bg-white border rounded-3 mt-2">
                                <span class="small text-muted fw-bold d-block">Reason Provided:</span>
                                <span class="small text-dark">{{ $serviceRequest->cancellation_reason }}</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Right Column: Timeline & Technician -->
            <div class="col-lg-5">
                <!-- Assigned Technician Card -->
                <div class="card-modern p-4 mb-4">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-person-badge-fill text-warning"></i> Assigned Specialist
                    </h5>

                    @if ($serviceRequest->assignedOfficer)
                        <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-light border">
                            <div class="user-avatar bg-navy-900 text-gold fw-bold"
                                style="width: 48px; height: 48px; font-size: 1.1rem;">
                                {{ $serviceRequest->assignedOfficer->initials }}
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-navy-900 fs-6">{{ $serviceRequest->assignedOfficer->name }}</div>
                                <div class="small text-muted">Certified Maintenance Officer</div>
                                <div class="small text-muted mt-1">
                                    <i class="bi bi-envelope me-1"></i>{{ $serviceRequest->assignedOfficer->email }}
                                </div>
                                @if ($serviceRequest->assignedOfficer->mobile)
                                    <div class="small text-muted">
                                        <i class="bi bi-telephone me-1"></i>{{ $serviceRequest->assignedOfficer->mobile }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        @if ($serviceRequest->assigned_at)
                            <div class="small text-muted mt-2 text-end">
                                <i class="bi bi-calendar-check me-1"></i> Assigned
                                {{ $serviceRequest->assigned_at->diffForHumans() }}
                            </div>
                        @endif
                    @else
                        <div class="p-3 rounded-3 bg-light border text-center text-muted">
                            <i class="bi bi-hourglass-split fs-2 d-block text-warning mb-1"></i>
                            <div class="fw-bold text-navy-900 mb-1">Pending Technician Dispatch</div>
                            <small>Your complaint is in the central dispatch queue. A specialist will be allocated according to
                                SLA priority.</small>
                        </div>
                    @endif
                </div>

                <!-- Lifecycle Audit Trail Timeline -->
                <div class="card-modern p-4 mb-4">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-warning"></i> Progress Lifecycle
                    </h5>

                    <div class="timeline-audit">
                        <!-- Step 1: Complaint Lodged -->
                        <div
                            class="timeline-item d-flex gap-3 pb-3 border-start border-2 ps-3 position-relative border-primary">
                            <div class="timeline-badge bg-primary text-white rounded-circle position-absolute"
                                style="left: -12px; top: 0; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;">
                                <i class="bi bi-check-lg"></i>
                            </div>
                            <div>
                                <div class="fw-bold small text-navy-900">Incident Lodged</div>
                                <div class="small text-muted">{{ $serviceRequest->created_at->format('M d, Y h:i A') }}
                                </div>
                                <div class="small text-muted">Ticket created by {{ $serviceRequest->user?->name }}</div>
                            </div>
                        </div>

                        <!-- Step 2: Officer Assigned -->
                        @php
                            $step2Active = in_array($serviceRequest->status, ['assigned', 'in_progress', 'on_hold', 'resolved']);
                        @endphp
                        <div
                            class="timeline-item d-flex gap-3 pb-3 border-start border-2 ps-3 position-relative {{ $step2Active ? 'border-primary' : 'border-light' }}">
                            <div class="timeline-badge {{ $step2Active ? 'bg-primary text-white' : 'bg-light text-muted border' }} rounded-circle position-absolute"
                                style="left: -12px; top: 0; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;">
                                <i class="bi {{ $step2Active ? 'bi-check-lg' : 'bi-dash' }}"></i>
                            </div>
                            <div>
                                <div class="fw-bold small {{ $step2Active ? 'text-navy-900' : 'text-muted' }}">Technician
                                    Assigned</div>
                                @if ($serviceRequest->assignedOfficer && $serviceRequest->assigned_at)
                                    <div class="small text-muted">{{ $serviceRequest->assigned_at->format('M d, Y h:i A') }}
                                    </div>
                                    <div class="small text-muted">Assigned to {{ $serviceRequest->assignedOfficer->name }}</div>
                                @else
                                    <div class="small text-muted">Pending officer allocation</div>
                                @endif
                            </div>
                        </div>

                        <!-- Step 3: Work in Progress -->
                        @php
                            $step3Active = in_array($serviceRequest->status, ['in_progress', 'on_hold', 'resolved']);
                        @endphp
                        <div
                            class="timeline-item d-flex gap-3 pb-3 border-start border-2 ps-3 position-relative {{ $step3Active ? 'border-primary' : 'border-light' }}">
                            <div class="timeline-badge {{ $step3Active ? 'bg-primary text-white' : 'bg-light text-muted border' }} rounded-circle position-absolute"
                                style="left: -12px; top: 0; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;">
                                <i class="bi {{ $step3Active ? 'bi-check-lg' : 'bi-dash' }}"></i>
                            </div>
                            <div>
                                <div class="fw-bold small {{ $step3Active ? 'text-navy-900' : 'text-muted' }}">Field Work
                                    Underway</div>
                                @if ($serviceRequest->status === 'in_progress')
                                    <span class="badge bg-primary text-white small">Active on Site</span>
                                @elseif ($serviceRequest->status === 'on_hold')
                                    <span class="badge bg-warning text-dark small">On Hold (Awaiting Parts)</span>
                                @elseif ($serviceRequest->status === 'resolved')
                                    <div class="small text-muted">Repairs completed</div>
                                @else
                                    <div class="small text-muted">Awaiting technician arrival</div>
                                @endif
                            </div>
                        </div>

                        <!-- Step 4: Resolution -->
                        @php
                            $step4Active = $serviceRequest->status === 'resolved';
                        @endphp
                        <div class="timeline-item d-flex gap-3 ps-3 position-relative">
                            <div class="timeline-badge {{ $step4Active ? 'bg-success text-white' : ($serviceRequest->status === 'cancelled' ? 'bg-dark text-white' : 'bg-light text-muted border') }} rounded-circle position-absolute"
                                style="left: -12px; top: 0; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;">
                                <i
                                    class="bi {{ $step4Active ? 'bi-check-lg' : ($serviceRequest->status === 'cancelled' ? 'bi-x' : 'bi-dash') }}"></i>
                            </div>
                            <div>
                                <div
                                    class="fw-bold small {{ $step4Active ? 'text-success' : ($serviceRequest->status === 'cancelled' ? 'text-dark' : 'text-muted') }}">
                                    {{ $serviceRequest->status === 'cancelled' ? 'Request Cancelled' : 'Resolved & Inspected' }}
                                </div>
                                @if ($serviceRequest->resolved_at)
                                    <div class="small text-muted">{{ $serviceRequest->resolved_at->format('M d, Y h:i A') }}
                                    </div>
                                @elseif ($serviceRequest->cancelled_at)
                                    <div class="small text-muted">{{ $serviceRequest->cancelled_at->format('M d, Y h:i A') }}
                                    </div>
                                @else
                                    <div class="small text-muted">Pending completion</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Last Modified By / Audit Record Card -->
                <div class="card-modern p-4">
                    <h6 class="fw-bold mb-2 d-flex align-items-center gap-2 text-navy-900">
                        <i class="bi bi-shield-check text-warning"></i> Last modified by
                    </h6>
                    @if ($latestAudit)
                        <div class="p-3 rounded-3 bg-light border small">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong class="text-navy-900">{{ $latestAudit->actor_name ?: 'System' }}</strong>
                                <span class="badge bg-secondary text-capitalize">{{ $latestAudit->action }}</span>
                            </div>
                            <div class="text-muted">{{ $latestAudit->created_at->format('M d, Y \a\t h:i:s A') }}</div>
                            <div class="text-muted">({{ $latestAudit->created_at->diffForHumans() }})</div>
                        </div>
                    @else
                        <div class="p-3 rounded-3 bg-light border small text-muted">
                            Initial record created on {{ $serviceRequest->created_at->format('M d, Y') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Request Confirmation Modal -->
    @if ($serviceRequest->canBeCancelledBy(auth()->user()))
        <div class="modal fade" id="cancelTicketModal" tabindex="-1" aria-labelledby="cancelTicketModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('user.requests.cancel', $serviceRequest->slug) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold text-danger" id="cancelTicketModalLabel">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Cancel Service Request
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-3">
                                Are you sure you want to cancel ticket <strong>{{ $serviceRequest->tracking_code }}</strong>?
                                Once cancelled, technicians will stop work on this fault report.
                            </p>
                            <div class="mb-3">
                                <label for="cancellationReasonInput" class="form-label-headworld small">Reason for cancellation
                                    (optional)</label>
                                <textarea name="cancellation_reason" id="cancellationReasonInput" class="form-control-headworld"
                                    rows="3"
                                    placeholder="e.g. Issue resolved itself, lodged duplicate ticket, moved rooms..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-subtle" data-bs-dismiss="modal">Keep Ticket Active</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-x-circle me-1"></i> Confirm Cancellation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
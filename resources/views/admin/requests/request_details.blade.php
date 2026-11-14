@extends('layouts.app')

@section('title', 'Ticket ' . $serviceRequest->tracking_code . ' | Dispatch Command')

@section('header_title')
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.requests.index') }}" class="btn btn-sm btn-subtle" title="Back to dispatch list">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-navy-900 text-warning font-mono">{{ $serviceRequest->tracking_code }}</span>
                <h5 class="fw-bold mb-0 text-navy-900 text-truncate" style="max-width: 450px;">{{ $serviceRequest->title }}
                </h5>
            </div>
            <small class="text-muted">Lodged {{ $serviceRequest->created_at->diffForHumans() }} by
                {{ $serviceRequest->user?->name }}</small>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-content">
        <!-- Banner Header -->
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
                        <span class="text-muted small">&bull; Created
                            {{ $serviceRequest->created_at->format('M d, Y h:i A') }}</span>
                    </div>
                    <h3 class="fw-bold text-navy-900 mb-1">{{ $serviceRequest->title }}</h3>
                    <p class="text-muted small mb-0 font-mono">
                        Tracking Code: <strong>{{ $serviceRequest->tracking_code }}</strong> &bull;
                        System UUID: <span class="text-secondary">{{ $serviceRequest->slug }}</span>
                    </p>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-gold btn-sm d-inline-flex align-items-center gap-1"
                        onclick="openAssignModal('{{ $serviceRequest->slug }}', '{{ $serviceRequest->tracking_code }}', '{{ $serviceRequest->assigned_officer_id }}')">
                        <i class="bi bi-person-gear"></i>
                        {{ $serviceRequest->assignedOfficer ? 'Reassign Officer' : 'Assign Officer' }}
                    </button>
                    <a href="{{ route('admin.requests.index') }}" class="btn btn-subtle btn-sm">
                        <i class="bi bi-list-task me-1"></i> All Requests
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Incident Specification & Visual Evidence -->
            <div class="col-lg-7">
                <!-- Specification Card -->
                <div class="card-modern p-4 mb-4">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-text-fill text-warning"></i> Incident Specification
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
                            <span class="small text-muted d-block">Lodged By</span>
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
                        <i class="bi bi-images text-warning"></i> Evidence & Initial Attachments
                        ({{ $serviceRequest->evidencePhotos->count() }})
                    </h5>

                    @if ($serviceRequest->evidencePhotos->isNotEmpty())
                        <div class="row g-3">
                            @foreach ($serviceRequest->evidencePhotos as $photo)
                                <div class="col-6 col-sm-4">
                                    <div class="card border rounded-3 overflow-hidden shadow-sm h-100">
                                        @if ($photo->isImage())
                                            <div class="position-relative" style="height: 140px; cursor: pointer;"
                                                data-bs-toggle="modal" data-bs-target="#photoModal_{{ $photo->id }}">
                                                <img src="{{ $photo->url }}" alt="{{ $photo->file_name }}"
                                                    class="w-100 h-100 object-fit-cover">
                                                <div class="position-absolute bottom-0 start-0 end-0 p-1 bg-dark bg-opacity-75 text-white small text-truncate text-center"
                                                    style="font-size:0.75rem;">
                                                    <i class="bi bi-zoom-in me-1"></i> {{ $photo->file_name }}
                                                </div>
                                            </div>
                                        @else
                                            <div class="p-3 text-center d-flex flex-column align-items-center justify-content-center"
                                                style="height: 140px;">
                                                <i class="bi bi-file-earmark-pdf text-danger fs-1"></i>
                                                <a href="{{ $photo->url }}" target="_blank"
                                                    class="small text-truncate mt-1 d-block text-navy-900 fw-semibold"
                                                    style="max-width: 120px;">
                                                    {{ $photo->file_name }}
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Photo Lightbox Modal -->
                                @if ($photo->isImage())
                                    <div class="modal fade" id="photoModal_{{ $photo->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-centered">
                                            <div class="modal-content border-0 bg-transparent">
                                                <div class="modal-header border-0 pb-0 justify-content-end">
                                                    <a href="{{ $photo->url }}" target="_blank" class="btn btn-sm btn-light me-2">
                                                        <i class="bi bi-box-arrow-up-right me-1"></i> Open Original
                                                    </a>
                                                    <button type="button" class="btn btn-close btn-close-white"
                                                        data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body text-center p-2">
                                                    <img src="{{ $photo->url }}" alt="{{ $photo->file_name }}"
                                                        class="img-fluid rounded-3 shadow-lg" style="max-height: 80vh;">
                                                    <div class="text-white mt-2 small">{{ $photo->file_name }} &bull;
                                                        {{ $photo->formatted_size }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted bg-light rounded-3 border">
                            <i class="bi bi-camera text-muted fs-3 d-block mb-1"></i>
                            <span class="small">No initial evidence photographs lodged with this complaint.</span>
                        </div>
                    @endif
                </div>

                <!-- Status Progression History (from dedicated table) -->
                <div class="card-modern p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-journal-check text-warning"></i> Status Progression History
                            ({{ $serviceRequest->statusUpdates->count() }})
                        </h5>
                        <span class="badge bg-secondary text-muted border">Dedicated Updates Stream</span>
                    </div>

                    @if ($serviceRequest->statusUpdates->isNotEmpty())
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
                                            <i class="bi bi-clock me-1"></i> {{ $update->created_at->format('M d, Y h:i A') }}
                                            ({{ $update->created_at->diffForHumans() }})
                                        </small>
                                    </div>

                                    @if ($update->notes)
                                        <p class="text-main mb-2 small" style="white-space: pre-line;">{{ $update->notes }}</p>
                                    @endif

                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top small text-muted">
                                        <span>
                                            <i class="bi bi-person-fill me-1"></i> Updated by:
                                            <strong>{{ $update->user?->name ?: 'System' }}</strong>
                                            @if ($update->user?->role)
                                                <span
                                                    class="badge bg-white text-dark border ms-1">{{ ucfirst(str_replace('_', ' ', $update->user->role)) }}</span>
                                            @endif
                                        </span>
                                    </div>

                                    <!-- Attached Evidence/Proof for this Status Update -->
                                    @if ($update->attachments->isNotEmpty())
                                        <div class="mt-3 pt-2 border-top">
                                            <span class="small fw-bold text-navy-900 d-block mb-2">
                                                <i class="bi bi-paperclip me-1"></i> Attachments Uploaded with this Update
                                                ({{ $update->attachments->count() }}):
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
                                                            <span class="text-muted"
                                                                style="font-size:0.7rem;">{{ $att->formatted_size }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-3 text-muted bg-light rounded-3 border">
                            <span class="small">No intermediate technician progress updates logged yet.</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Column: Assigned Specialist & Complete Audit Trail -->
            <div class="col-lg-5">
                <!-- Assigned Technician Card -->
                <div class="card-modern p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-person-badge-fill text-warning"></i> Assigned Specialist
                        </h5>
                        <button type="button" class="btn btn-sm btn-subtle"
                            onclick="openAssignModal('{{ $serviceRequest->slug }}', '{{ $serviceRequest->tracking_code }}', '{{ $serviceRequest->assigned_officer_id }}')">
                            <i class="bi bi-pencil-square"></i> Change
                        </button>
                    </div>

                    @if ($serviceRequest->assignedOfficer)
                        <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-light border">
                            <div class="user-avatar bg-navy-900 text-gold fw-bold"
                                style="width: 48px; height: 48px; font-size: 1.1rem; display: flex; align-items: center; justify-content: center;">
                                {{ $serviceRequest->assignedOfficer->initials }}
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-navy-900 fs-6">{{ $serviceRequest->assignedOfficer->name }}</div>
                                <div class="small text-muted">
                                    {{ $serviceRequest->assignedOfficer->department ?? 'Campus Facility Team' }}</div>
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
                                <i class="bi bi-calendar-check me-1"></i> Allocated
                                {{ $serviceRequest->assigned_at->diffForHumans() }}
                                ({{ $serviceRequest->assigned_at->format('M d, Y h:i A') }})
                            </div>
                        @endif
                    @else
                        <div class="p-3 rounded-3 bg-light border text-center text-muted">
                            <i class="bi bi-exclamation-circle text-warning fs-2 d-block mb-1"></i>
                            <div class="fw-bold text-navy-900 mb-1">Unassigned Work Order</div>
                            <small class="d-block mb-3">This request is waiting for a maintenance officer to be
                                delegated.</small>
                            <button type="button" class="btn btn-gold btn-sm"
                                onclick="openAssignModal('{{ $serviceRequest->slug }}', '{{ $serviceRequest->tracking_code }}', null)">
                                <i class="bi bi-person-plus me-1"></i> Dispatch Technician Now
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Complete Incident Audit Trail Stream -->
                <div class="card-modern p-4 mb-4">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-shield-shaded text-warning"></i> Incident Audit Trail Stream
                    </h5>

                    @if ($serviceRequest->auditLogs->isNotEmpty())
                        <div class="timeline-audit">
                            @foreach ($serviceRequest->auditLogs as $log)
                                <div
                                    class="timeline-item d-flex gap-3 pb-3 border-start border-2 ps-3 position-relative border-primary">
                                    <div class="timeline-badge bg-primary text-white rounded-circle position-absolute"
                                        style="left: -11px; top: 0; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.65rem;">
                                        <i class="bi bi-check-lg"></i>
                                    </div>
                                    <div class="w-100">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold small text-navy-900 text-capitalize">
                                                {{ str_replace('_', ' ', $log->action) }}
                                            </span>
                                            <span class="small text-muted" style="font-size:0.75rem;">
                                                {{ $log->created_at->format('M d, h:i A') }}
                                            </span>
                                        </div>
                                        <div class="small text-muted">Actor: <strong>{{ $log->actor_name }}</strong></div>
                                        @if (!empty($log->changes['action_description']))
                                            <div class="p-2 bg-light rounded small mt-1 border">
                                                {{ $log->changes['action_description'] }}
                                            </div>
                                        @elseif (!empty($log->changes['status']))
                                            <div class="p-2 bg-light rounded small mt-1 border">
                                                Status shifted:
                                                <span class="badge bg-secondary">{{ $log->changes['status']['old'] ?? 'N/A' }}</span>
                                                &rarr;
                                                <span class="badge bg-primary">{{ $log->changes['status']['new'] ?? 'N/A' }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-3 text-muted small">
                            No audit trail logs recorded yet.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Reassignment Modal -->
    <div class="modal fade modal-headworld" id="assignOfficerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-gear text-warning me-1"></i> Delegate to Maintenance Officer
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="assignOfficerForm" method="POST" action="">
                    @csrf
                    <div class="modal-body">
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <div class="small text-muted">Target Ticket:</div>
                            <strong class="font-mono text-navy-900 fs-6" id="modalTargetTrackingCode">---</strong>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-headworld fw-bold">Select Qualified Field Technician <span
                                    class="text-danger">*</span></label>
                            <select name="assigned_officer_id" id="officerSelect" class="form-select-headworld" required>
                                <option value="">-- Choose Field Specialist --</option>
                                @foreach ($officers as $officer)
                                    <option value="{{ $officer->id }}" {{ $serviceRequest->assigned_officer_id === $officer->id ? 'selected' : '' }}>
                                        {{ $officer->name }} &bull; {{ $officer->department ?? 'Campus Facility Team' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-headworld">Dispatch & Triage Instructions (Optional)</label>
                            <textarea name="notes" id="assignNotes" class="form-control-headworld" rows="3"
                                placeholder="Special tools needed, safety instructions, or estimated completion time..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-subtle" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-gold">
                            <i class="bi bi-check2-circle me-1"></i> Confirm Assignment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openAssignModal(slug, trackingCode, currentOfficerId) {
            const form = document.getElementById('assignOfficerForm');
            form.action = `/admin/requests/${slug}/assign`;
            document.getElementById('modalTargetTrackingCode').textContent = trackingCode;

            const select = document.getElementById('officerSelect');
            if (currentOfficerId) {
                select.value = currentOfficerId;
            }

            const modal = new bootstrap.Modal(document.getElementById('assignOfficerModal'));
            modal.show();
        }
    </script>
@endpush
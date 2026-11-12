@extends('layouts.app')

@section('title', 'Work Order ' . $serviceRequest->tracking_code . ' | Technician Desk')

@section('header_title')
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('facility.tasks.index') }}" class="btn btn-sm btn-subtle" title="Back to task board">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-navy-900 text-warning font-mono">{{ $serviceRequest->tracking_code }}</span>
                <h5 class="fw-bold mb-0 text-navy-900 text-truncate" style="max-width: 450px;">{{ $serviceRequest->title }}
                </h5>
            </div>
            <small class="text-muted">Assigned to you &bull; {{ $serviceRequest->created_at->diffForHumans() }}</small>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-content">
        <!-- Top Action Card -->
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
                        @if ($serviceRequest->assigned_at)
                            <span class="text-muted small">&bull; Assigned
                                {{ $serviceRequest->assigned_at->diffForHumans() }}</span>
                        @endif
                    </div>
                    <h3 class="fw-bold text-navy-900 mb-1">{{ $serviceRequest->title }}</h3>
                    <p class="text-muted small mb-0 font-mono">
                        Work Order: <strong>{{ $serviceRequest->tracking_code }}</strong> &bull;
                        Ref: REQ-{{ str_pad((string) $serviceRequest->id, 5, '0', STR_PAD_LEFT) }}
                    </p>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-gold btn-sm d-inline-flex align-items-center gap-1"
                        data-bs-toggle="modal" data-bs-target="#updateTaskStatusModal">
                        <i class="bi bi-arrow-repeat"></i> Update Progress Status
                    </button>
                    <a href="{{ route('facility.tasks.index') }}" class="btn btn-subtle btn-sm">
                        <i class="bi bi-kanban me-1"></i> Task Pipeline
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Specs, Visual Evidence, Quick Update -->
            <div class="col-lg-7">
                <!-- Specification Card -->
                <div class="card-modern p-4 mb-4">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-tools text-warning"></i> Maintenance Job Specification
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
                            <span class="small text-muted d-block">Raised By</span>
                            <strong class="text-navy-900 fs-6">{{ $serviceRequest->user?->name }}</strong>
                            <div class="small text-muted">{{ $serviceRequest->user?->email }}</div>
                            @if ($serviceRequest->user?->mobile)
                                <div class="small text-muted"><i
                                        class="bi bi-telephone me-1"></i>{{ $serviceRequest->user->mobile }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Initial Evidence Lodged by Submitter -->
                <div class="card-modern p-4 mb-4">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-images text-warning"></i> Submitter Photos & Evidence
                        ({{ $serviceRequest->evidencePhotos->count() }})
                    </h5>

                    @if ($serviceRequest->evidencePhotos->isNotEmpty())
                        <div class="row g-3">
                            @foreach ($serviceRequest->evidencePhotos as $photo)
                                <div class="col-6 col-sm-4">
                                    <div class="card border rounded-3 overflow-hidden shadow-sm h-100">
                                        @if ($photo->isImage())
                                            <a href="{{ $photo->url }}" target="_blank" class="position-relative d-block"
                                                style="height: 140px;">
                                                <img src="{{ $photo->url }}" alt="{{ $photo->file_name }}"
                                                    class="w-100 h-100 object-fit-cover">
                                                <div class="position-absolute bottom-0 start-0 end-0 p-1 bg-dark bg-opacity-75 text-white small text-truncate text-center"
                                                    style="font-size:0.75rem;">
                                                    <i class="bi bi-zoom-in me-1"></i> {{ $photo->file_name }}
                                                </div>
                                            </a>
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
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted bg-light rounded-3 border">
                            <span class="small">No initial evidence photographs lodged with this complaint.</span>
                        </div>
                    @endif
                </div>

                <!-- Status Progression History (from dedicated table) -->
                <div class="card-modern p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-journal-check text-warning"></i> Status Progression History & Technician Notes
                        </h5>
                        <span class="badge bg-secondary text-muted border">{{ $serviceRequest->statusUpdates->count() }}
                            Updates</span>
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
                                            <i class="bi bi-person-fill me-1"></i> Recorded by:
                                            <strong>{{ $update->user?->name ?: 'System' }}</strong>
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
                            <span class="small">No technician progress updates logged yet. Use the update form below to record
                                on-site work.</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Column: Quick Status Update Card & Lifecycle Timeline -->
            <div class="col-lg-5">
                <!-- Inline Status Update Form Card -->
                <div class="card-modern p-4 mb-4 border border-warning">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-arrow-repeat text-warning"></i> Log Status Progression
                    </h5>
                    <form method="POST" action="{{ route('facility.tasks.status', $serviceRequest->slug) }}"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label-headworld fw-bold">Target Status <span
                                    class="text-danger">*</span></label>
                            <select name="status" class="form-select-headworld" required id="inlineStatusSelect"
                                onchange="handleInlineStatus(this.value)">
                                <option value="in_progress" {{ $serviceRequest->status === 'in_progress' ? 'selected' : '' }}>
                                    In Progress (Active Work on Site)
                                </option>
                                <option value="on_hold" {{ $serviceRequest->status === 'on_hold' ? 'selected' : '' }}>
                                    On Hold (Awaiting Parts / Campus Access)
                                </option>
                                <option value="resolved" {{ $serviceRequest->status === 'resolved' ? 'selected' : '' }}>
                                    Resolved (Sign-off & Complete)
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-headworld fw-bold" id="inlineNotesLabel">
                                Technical Notes & Work Done <span class="text-danger" id="inlineStar">*</span>
                            </label>
                            <textarea name="notes" id="inlineNotes" class="form-control-headworld" rows="3"
                                placeholder="Detail parts replaced, safety tests conducted, or reason for holding work..."
                                required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-headworld fw-bold d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-paperclip me-1"></i> Attach Proof / Photos</span>
                                <small class="text-muted">(Optional)</small>
                            </label>
                            <input type="file" name="attachments[]" class="form-control-headworld" multiple
                                accept="image/*,.pdf,.doc,.docx">
                            <small class="text-muted">Upload photos of repaired components, receipts, or test
                                readouts.</small>
                        </div>

                        <button type="submit" class="btn btn-gold w-100" id="inlineSubmitBtn">
                            <i class="bi bi-check2-circle me-1"></i> Save Status Progression
                        </button>
                    </form>
                </div>

                <!-- Audit Trail Stream -->
                <div class="card-modern p-4 mb-4">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-shield-shaded text-warning"></i> Work Order Audit Trail
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
                                                Status: <span
                                                    class="badge bg-secondary">{{ $log->changes['status']['old'] ?? 'N/A' }}</span>
                                                &rarr; <span
                                                    class="badge bg-primary">{{ $log->changes['status']['new'] ?? 'N/A' }}</span>
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

    <!-- Update Status Modal (Alternative trigger) -->
    <div class="modal fade modal-headworld" id="updateTaskStatusModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-arrow-repeat text-warning me-1"></i> Update Work Order Progress Status
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('facility.tasks.status', $serviceRequest->slug) }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <div class="small text-muted">Assigned Work Order:</div>
                            <strong class="font-mono text-navy-900 fs-6">{{ $serviceRequest->tracking_code }} &bull;
                                {{ $serviceRequest->title }}</strong>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-headworld fw-bold">Select New Progress State <span
                                    class="text-danger">*</span></label>
                            <select name="status" class="form-select-headworld" required>
                                <option value="in_progress">In Progress (Active Work on Site)</option>
                                <option value="on_hold">On Hold (Awaiting Parts / Campus Access)</option>
                                <option value="resolved">Resolved (Sign-off & Complete Work Order)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-headworld fw-bold">Technician Notes & Incident Remarks <span
                                    class="text-danger">*</span></label>
                            <textarea name="notes" class="form-control-headworld" rows="4"
                                placeholder="Detail parts replaced, safety tests performed, or reason for hold..."
                                required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-headworld fw-bold d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-paperclip me-1"></i> Upload Evidence / Resolution Proof
                                    Attachments</span>
                                <span class="badge bg-secondary text-muted border">Optional (Max 5 files, up to 10MB
                                    each)</span>
                            </label>
                            <input type="file" name="attachments[]" class="form-control-headworld" multiple
                                accept="image/*,.pdf,.doc,.docx">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-subtle" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-gold">
                            <i class="bi bi-check2-circle me-1"></i> Save Status Progression
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function handleInlineStatus(status) {
            const star = document.getElementById('inlineStar');
            const notesEl = document.getElementById('inlineNotes');
            const submitBtn = document.getElementById('inlineSubmitBtn');

            if (status === 'resolved') {
                notesEl.required = true;
                star.style.display = 'inline';
                submitBtn.className = 'btn btn-gold w-100';
                submitBtn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Sign-Off & Complete Work Order';
            } else if (status === 'on_hold') {
                notesEl.required = true;
                star.style.display = 'inline';
                submitBtn.className = 'btn btn-warning w-100';
                submitBtn.innerHTML = '<i class="bi bi-pause-circle me-1"></i> Place On Hold';
            } else {
                notesEl.required = false;
                star.style.display = 'none';
                submitBtn.className = 'btn btn-primary w-100';
                submitBtn.innerHTML = '<i class="bi bi-play-circle me-1"></i> Save In Progress';
            }
        }
    </script>
@endpush
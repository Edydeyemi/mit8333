@extends('layouts.app')

@section('title', 'Field Task Pipeline & Kanban | ' . (getenv('APP_NAME') ?: 'Headworld'))

@section('header_title')
    <div class="d-flex align-items-center justify-content-between w-100 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0 text-navy-900">Task Management Pipeline</h5>
            <small class="text-muted">Interactive Field Kanban & Work Order Table</small>
        </div>
        <!-- View Toggle Switcher (Hybrid Kanban & Table) -->
        <div class="btn-group p-1 bg-light rounded-3 border" role="group">
            <button type="button" id="btnViewKanban" class="btn btn-sm btn-gold active" onclick="switchView('kanban')">
                <i class="bi bi-kanban me-1"></i> Kanban
            </button>
            <button type="button" id="btnViewTable" class="btn btn-sm btn-light" onclick="switchView('table')">
                <i class="bi bi-table me-1"></i> Table
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-content">
        <!-- Filter Toolbar -->
        <div class="card-modern p-3 mb-4">
            <form method="GET" action="{{ route('facility.tasks.index') }}" id="taskFilterForm">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" name="search" id="taskSearchInput" class="form-control-headworld border-start-0"
                                placeholder="Filter tasks by tracking code, room, or issue..." value="{{ $filters['search'] ?? '' }}">
                        </div>
                    </div>

                    <div class="col-sm-6 col-md-3">
                        <select name="category" id="taskCategorySelect" class="form-select-headworld" onchange="this.form.submit()">
                            <option value="all">All Categories</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ ($filters['category'] ?? '') == (string)$cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-sm-6 col-md-4 d-flex justify-content-md-end gap-2">
                        @if (!empty($filters['search']) || !empty($filters['category']))
                            <a href="{{ route('facility.tasks.index') }}" class="btn btn-subtle btn-sm d-flex align-items-center">
                                <i class="bi bi-x-circle me-1"></i> Clear Filter
                            </a>
                        @endif
                        <span class="badge bg-navy-900 text-warning d-flex align-items-center px-3 py-2 rounded-3">
                            <i class="bi bi-tools me-1"></i> {{ $allTasks->count() }} Total Assigned Tasks
                        </span>
                    </div>
                </div>
            </form>
        </div>

        <!-- 1. KANBAN PIPELINE VIEW -->
        <div id="kanbanContainer" class="kanban-board">
            <!-- Column 1: Assigned / Queued -->
            <div class="kanban-col">
                <div class="kanban-col-header">
                    <span class="kanban-col-title">
                        <span class="status-dot bg-primary"></span> Assigned / Queued
                    </span>
                    <span class="kanban-count" id="countAssigned">{{ $assignedTasks->count() }}</span>
                </div>
                <div class="kanban-cards-wrapper" id="colAssigned">
                    @forelse ($assignedTasks as $task)
                        @include('facility.partials.task_card', ['task' => $task])
                    @empty
                        <div class="text-center py-4 text-muted small">No queued tasks.</div>
                    @endforelse
                </div>
            </div>

            <!-- Column 2: In Progress -->
            <div class="kanban-col">
                <div class="kanban-col-header">
                    <span class="kanban-col-title">
                        <span class="status-dot bg-success"></span> In Progress
                    </span>
                    <span class="kanban-count" id="countInProgress">{{ $inProgressTasks->count() }}</span>
                </div>
                <div class="kanban-cards-wrapper" id="colInProgress">
                    @forelse ($inProgressTasks as $task)
                        @include('facility.partials.task_card', ['task' => $task])
                    @empty
                        <div class="text-center py-4 text-muted small">No tasks currently in progress.</div>
                    @endforelse
                </div>
            </div>

            <!-- Column 3: On Hold -->
            <div class="kanban-col">
                <div class="kanban-col-header">
                    <span class="kanban-col-title">
                        <span class="status-dot" style="background:#8b5cf6;"></span> On Hold (Parts / Access)
                    </span>
                    <span class="kanban-count" id="countOnHold">{{ $onHoldTasks->count() }}</span>
                </div>
                <div class="kanban-cards-wrapper" id="colOnHold">
                    @forelse ($onHoldTasks as $task)
                        @include('facility.partials.task_card', ['task' => $task])
                    @empty
                        <div class="text-center py-4 text-muted small">No tasks on hold.</div>
                    @endforelse
                </div>
            </div>

            <!-- Column 4: Resolved -->
            <div class="kanban-col">
                <div class="kanban-col-header">
                    <span class="kanban-col-title">
                        <span class="status-dot" style="background:#10b981;"></span> Resolved / Inspected
                    </span>
                    <span class="kanban-count" id="countCompleted">{{ $resolvedTasks->count() }}</span>
                </div>
                <div class="kanban-cards-wrapper" id="colCompleted">
                    @forelse ($resolvedTasks as $task)
                        @include('facility.partials.task_card', ['task' => $task])
                    @empty
                        <div class="text-center py-4 text-muted small">No completed tasks yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- 2. DATA TABLE VIEW (Initially Hidden) -->
        <div id="tableContainer" class="card-modern" style="display: none;">
            <div class="card-body-headworld p-0">
                <div class="table-responsive-wrapper border-0">
                    <table class="table-headworld">
                        <thead>
                            <tr>
                                <th>Tracking Code</th>
                                <th>Category</th>
                                <th>Title & Location</th>
                                <th>Priority</th>
                                <th>Current Status</th>
                                <th>Updates Logged</th>
                                <th class="text-end">Transition Actions</th>
                            </tr>
                        </thead>
                        <tbody id="taskTableTbody">
                            @forelse ($allTasks as $task)
                                <tr>
                                    <td>
                                        <a href="{{ route('facility.tasks.show', $task->slug) }}" class="fw-bold font-mono text-navy-900">
                                            {{ $task->tracking_code }}
                                        </a>
                                        <div class="small text-muted font-mono" style="font-size:0.75rem;">REQ-{{ str_pad((string)$task->id, 5, '0', STR_PAD_LEFT) }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary text-navy-900 border">
                                            <i class="bi {{ $task->category?->icon ?: 'bi-tools' }} me-1" style="color: {{ $task->category?->color ?: '#0a192f' }};"></i>
                                            {{ $task->category?->name ?: 'General' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width:250px;" title="{{ $task->title }}">{{ $task->title }}</div>
                                        <small class="text-muted">{{ $task->building }} &bull; {{ $task->room_number }}</small>
                                    </td>
                                    <td>
                                        <span class="{{ $task->priority_badge_class }}">
                                            {{ ucfirst($task->priority) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="{{ $task->status_badge_class }}">
                                            <span class="status-dot"></span>{{ $task->status_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary text-navy-900 border">
                                            <i class="bi bi-clock-history me-1"></i> {{ $task->statusUpdates->count() }} updates
                                        </span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="btn btn-sm btn-subtle py-1 px-2"
                                            onclick="openUpdateModal('{{ $task->slug }}', '{{ $task->tracking_code }}', 'in_progress', 'Starting active field work on fault location.')">
                                            Progress
                                        </button>
                                        <button type="button" class="btn btn-sm btn-gold py-1 px-2"
                                            onclick="openUpdateModal('{{ $task->slug }}', '{{ $task->tracking_code }}', 'resolved', 'Inspected fault location, replaced worn components, verified nominal operation, and performed safety tests.')">
                                            Resolve
                                        </button>
                                        <a href="{{ route('facility.tasks.show', $task->slug) }}" class="btn btn-sm btn-veritas py-1 px-2" title="Inspect details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No assigned maintenance work orders found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status & Attach Proof Modal -->
    <div class="modal fade modal-headworld" id="updateTaskStatusModal" tabindex="-1" aria-labelledby="updateTaskStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="updateTaskStatusModalLabel">
                        <i class="bi bi-arrow-repeat text-warning me-1"></i> Update Work Order Progress Status
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="updateTaskStatusForm" method="POST" action="" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <div class="small text-muted">Assigned Work Order:</div>
                            <strong class="font-mono text-navy-900 fs-6" id="statusTargetTrackingCode">---</strong>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-headworld fw-bold">Select New Progress State <span class="text-danger">*</span></label>
                            <select name="status" id="taskStatusSelect" class="form-select-headworld" required onchange="handleStatusChange(this.value)">
                                <option value="in_progress">In Progress (Active Work Started on Site)</option>
                                <option value="on_hold">On Hold (Awaiting Spare Parts / Campus Access)</option>
                                <option value="resolved">Resolved (Sign-off & Complete Work Order)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-headworld fw-bold" id="statusNotesLabel">
                                Technician Notes & Incident Remarks <span class="text-danger" id="notesRequiredStar">*</span>
                            </label>
                            <textarea name="notes" id="statusNotes" class="form-control-headworld" rows="4"
                                placeholder="Detail parts replaced (e.g. replaced copper elbow, reset 40A circuit breaker), safety tests conducted, or parts awaiting delivery..."
                                required></textarea>
                            <small class="text-muted">These notes will be logged in the dedicated status history and campus audit trail.</small>
                        </div>

                        <!-- Attachments Upload -->
                        <div class="mb-3">
                            <label class="form-label-headworld fw-bold d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-paperclip me-1"></i> Upload Evidence / Resolution Proof Attachments</span>
                                <span class="badge bg-secondary text-muted border">Optional (Max 5 files, up to 10MB each)</span>
                            </label>
                            <input type="file" name="attachments[]" id="statusAttachments" class="form-control-headworld"
                                multiple accept="image/*,.pdf,.doc,.docx">
                            <small class="text-muted d-block mt-1">
                                Attach photos of repaired equipment, replacement receipts, inspection certificates, or manufacturer spec sheets.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-subtle" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-gold" id="btnSubmitStatus">
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
        let activeView = "kanban";

        function switchView(view) {
            activeView = view;
            const kanbanEl = document.getElementById("kanbanContainer");
            const tableEl = document.getElementById("tableContainer");
            const btnK = document.getElementById("btnViewKanban");
            const btnT = document.getElementById("btnViewTable");

            if (view === "kanban") {
                kanbanEl.style.display = "grid";
                tableEl.style.display = "none";
                btnK.className = "btn btn-sm btn-gold active";
                btnT.className = "btn btn-sm btn-light";
            } else {
                kanbanEl.style.display = "none";
                tableEl.style.display = "block";
                btnK.className = "btn btn-sm btn-light";
                btnT.className = "btn btn-sm btn-gold active";
            }
        }

        function openUpdateModal(slug, trackingCode, targetStatus, defaultNotes) {
            const form = document.getElementById('updateTaskStatusForm');
            form.action = `/facility/tasks/${slug}/status`;
            document.getElementById('statusTargetTrackingCode').textContent = trackingCode;
            
            const select = document.getElementById('taskStatusSelect');
            if (targetStatus) {
                select.value = targetStatus;
            }
            
            const notesEl = document.getElementById('statusNotes');
            if (defaultNotes) {
                notesEl.value = defaultNotes;
            } else {
                notesEl.value = '';
            }

            handleStatusChange(select.value);

            const modal = new bootstrap.Modal(document.getElementById('updateTaskStatusModal'));
            modal.show();
        }

        function handleStatusChange(status) {
            const notesEl = document.getElementById('statusNotes');
            const star = document.getElementById('notesRequiredStar');
            const submitBtn = document.getElementById('btnSubmitStatus');
            const modalTitle = document.getElementById('updateTaskStatusModalLabel');

            if (status === 'resolved') {
                modalTitle.innerHTML = '<i class="bi bi-check2-circle text-success me-1"></i> Sign-Off & Complete Work Order';
                notesEl.required = true;
                star.style.display = 'inline';
                submitBtn.className = 'btn btn-gold';
                submitBtn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Sign-Off & Complete Work Order';
            } else if (status === 'on_hold') {
                modalTitle.innerHTML = '<i class="bi bi-pause-circle text-warning me-1"></i> Place Work Order On Hold';
                notesEl.required = true;
                star.style.display = 'inline';
                submitBtn.className = 'btn btn-warning';
                submitBtn.innerHTML = '<i class="bi bi-pause-circle me-1"></i> Place On Hold';
            } else {
                modalTitle.innerHTML = '<i class="bi bi-play-circle text-primary me-1"></i> Shift Work Order to In Progress';
                notesEl.required = false;
                star.style.display = 'none';
                submitBtn.className = 'btn btn-primary';
                submitBtn.innerHTML = '<i class="bi bi-play-circle me-1"></i> Start In Progress';
            }
        }
    </script>
@endpush

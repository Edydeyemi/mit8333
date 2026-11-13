@extends('layouts.app')

@section('title', 'Master Dispatch & Requests | ' . (getenv('APP_NAME') ?: 'Headworld'))

@section('header_title')
    <div>
        <h5 class="fw-bold mb-0 text-navy-900">Master Incident Dispatch Console</h5>
        <small class="text-muted">Direct technical allocation and request triage</small>
    </div>
@endsection

@section('content')
    <div class="page-content">
        <!-- Metric Overview Banner -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card-modern stat-card stat-gold p-3">
                    <div class="stat-card-header">
                        <span class="stat-card-label">Total Logged</span>
                        <div class="stat-card-icon stat-icon-gold">
                            <i class="bi bi-clipboard2-data-fill"></i>
                        </div>
                    </div>
                    <div class="stat-card-value">{{ $metrics['total'] }}</div>
                    <div class="stat-card-trend">
                        <span>All campus facilities</span>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card-modern stat-card stat-rose p-3">
                    <div class="stat-card-header">
                        <span class="stat-card-label">Pending Dispatch</span>
                        <div class="stat-card-icon stat-icon-rose">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                    <div class="stat-card-value text-danger">{{ $metrics['unassigned'] }}</div>
                    <div class="stat-card-trend">
                        <span class="text-danger">Awaiting Technician Allocation</span>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card-modern stat-card stat-amber p-3">
                    <div class="stat-card-header">
                        <span class="stat-card-label">Active Field Dispatches</span>
                        <div class="stat-card-icon stat-icon-amber">
                            <i class="bi bi-gear-wide-connected"></i>
                        </div>
                    </div>
                    <div class="stat-card-value">{{ $metrics['active_dispatches'] }}</div>
                    <div class="stat-card-trend">
                        <span>Assigned, In Progress & On Hold</span>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card-modern stat-card stat-emerald p-3">
                    <div class="stat-card-header">
                        <span class="stat-card-label">Completed & Signed Off</span>
                        <div class="stat-card-icon stat-icon-emerald">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                    </div>
                    <div class="stat-card-value text-success">{{ $metrics['resolved'] }}</div>
                    <div class="stat-card-trend">
                        <span class="text-success">Verified nominal operations</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Master Filter Bar -->
        <div class="card-modern p-3 mb-4">
            <form method="GET" action="{{ route('admin.requests.index') }}" id="adminFilterForm">
                <div class="row g-2 align-items-center">
                    <div class="col-lg-3 col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" name="search" id="adminSearchInput" class="form-control-headworld border-start-0"
                                placeholder="Search ticket, code, room..." value="{{ $filters['search'] ?? '' }}">
                        </div>
                    </div>

                    <div class="col-sm-6 col-lg-2">
                        <select name="category" class="form-select-headworld" onchange="this.form.submit()">
                            <option value="all">All Categories</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ ($filters['category'] ?? '') == (string)$cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-sm-6 col-lg-2">
                        <select name="status" class="form-select-headworld" onchange="this.form.submit()">
                            <option value="all">All Statuses</option>
                            <option value="submitted" {{ ($filters['status'] ?? '') === 'submitted' ? 'selected' : '' }}>Unassigned / Submitted</option>
                            <option value="assigned" {{ ($filters['status'] ?? '') === 'assigned' ? 'selected' : '' }}>Assigned</option>
                            <option value="in_progress" {{ ($filters['status'] ?? '') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="on_hold" {{ ($filters['status'] ?? '') === 'on_hold' ? 'selected' : '' }}>On Hold</option>
                            <option value="resolved" {{ ($filters['status'] ?? '') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <div class="col-sm-6 col-lg-2">
                        <select name="priority" class="form-select-headworld" onchange="this.form.submit()">
                            <option value="all">All Priorities</option>
                            <option value="emergency" {{ ($filters['priority'] ?? '') === 'emergency' ? 'selected' : '' }}>Emergency Only</option>
                            <option value="high" {{ ($filters['priority'] ?? '') === 'high' ? 'selected' : '' }}>High Priority</option>
                            <option value="medium" {{ ($filters['priority'] ?? '') === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="low" {{ ($filters['priority'] ?? '') === 'low' ? 'selected' : '' }}>Low</option>
                        </select>
                    </div>

                    <div class="col-sm-6 col-lg-3 d-flex gap-2">
                        <select name="building" class="form-select-headworld" onchange="this.form.submit()">
                            <option value="all">All Buildings</option>
                            @foreach ($buildings as $b)
                                <option value="{{ $b }}" {{ ($filters['building'] ?? '') === $b ? 'selected' : '' }}>
                                    {{ $b }}
                                </option>
                            @endforeach
                        </select>
                        @if (!empty($filters['search']) || !empty($filters['category']) || !empty($filters['status']) || !empty($filters['priority']) || !empty($filters['building']))
                            <a href="{{ route('admin.requests.index') }}" class="btn btn-subtle btn-sm text-nowrap d-flex align-items-center" title="Reset Filters">
                                <i class="bi bi-x-circle"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- Master Requests Table -->
        <div class="card-modern">
            <div class="card-header-headworld d-flex justify-content-between align-items-center p-3 border-bottom flex-wrap gap-2">
                <h5 class="card-title-headworld mb-0 fw-bold">
                    <i class="bi bi-list-check text-warning me-1"></i> Campus Service Requests ({{ $requests->total() }})
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.reports.export.requests', request()->all()) }}" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1">
                        <i class="bi bi-file-earmark-spreadsheet-fill"></i> Export XLSX
                    </a>
                    <span class="badge bg-secondary text-muted border">Page {{ $requests->currentPage() }} of {{ $requests->lastPage() }}</span>
                </div>
            </div>

            <div class="card-body-headworld p-0">
                <div class="table-responsive-wrapper border-0">
                    <table class="table-headworld mb-0">
                        <thead>
                            <tr>
                                <th>Ref / Tracking</th>
                                <th>Category</th>
                                <th>Issue Summary</th>
                                <th>Location</th>
                                <th>Requester</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Assigned Specialist</th>
                                <th class="text-end">Dispatch Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($requests as $r)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.requests.show', $r->slug) }}" class="fw-bold font-mono text-navy-900">
                                            {{ $r->tracking_code }}
                                        </a>
                                        <div class="small text-muted font-mono" style="font-size:0.75rem;">REQ-{{ str_pad((string)$r->id, 5, '0', STR_PAD_LEFT) }}</div>
                                    </td>
                                    <td> 
                                            {{ $r->category?->name ?: 'General' }} 
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width: 200px;" title="{{ $r->title }}">
                                            {{ $r->title }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="small fw-semibold d-block text-truncate" style="max-width: 170px;">{{ $r->building }}</span>
                                        <div class="small text-muted">{{ $r->wing_floor }} &bull; {{ $r->room_number }}</div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold">{{ $r->user?->name ?: 'Anonymous' }}</div>
                                        <small class="text-muted">{{ $r->user?->email }}</small>
                                    </td>
                                    <td>
                                        <span class="{{ $r->priority_badge_class }}">
                                            {{ ucfirst($r->priority) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="{{ $r->status_badge_class }}">
                                            <span class="status-dot"></span>{{ $r->status_label }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($r->assignedOfficer)
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="user-avatar bg-navy-900 text-gold fw-bold rounded-circle" style="width: 24px; height: 24px; font-size: 0.7rem; display: flex; align-items: center; justify-content: center;">
                                                    {{ $r->assignedOfficer->initials }}
                                                </div>
                                                <span class="small fw-semibold">{{ $r->assignedOfficer->name }}</span>
                                            </div>
                                        @else
                                            <span class="badge bg-danger bg-opacity-10 text-danger fw-bold border border-danger">
                                                <i class="bi bi-exclamation-circle me-1"></i> Unassigned
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="btn btn-sm btn-gold py-1 px-2"
                                            onclick="openAssignModal('{{ $r->slug }}', '{{ $r->tracking_code }}', '{{ $r->assigned_officer_id }}')">
                                            <i class="bi bi-person-plus"></i> {{ $r->assignedOfficer ? 'Reassign' : 'Assign' }}
                                        </button>
                                        <a href="{{ route('admin.requests.show', $r->slug) }}" class="btn btn-sm btn-subtle py-1 px-2" title="Inspect Request">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="bi bi-folder-x fs-1 d-block mb-2 text-warning opacity-50"></i>
                                        <div class="fw-bold">No Service Requests Found</div>
                                        <p class="small text-muted mb-0">Try adjusting your search criteria or filter settings.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($requests->hasPages())
                    <div class="p-3 border-top d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            Showing {{ $requests->firstItem() ?? 0 }} to {{ $requests->lastItem() ?? 0 }} of {{ $requests->total() }} entries
                        </small>
                        <div>
                            {{ $requests->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Technician Assignment Modal -->
    <div class="modal fade modal-headworld" id="assignOfficerModal" tabindex="-1" aria-labelledby="assignOfficerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="assignOfficerModalLabel">
                        <i class="bi bi-person-gear text-warning me-1"></i> Delegate to Maintenance Officer
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="assignOfficerForm" method="POST" action="">
                    @csrf
                    <div class="modal-body">
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <div class="small text-muted">Target Ticket:</div>
                            <strong class="font-mono text-navy-900 fs-6" id="modalTargetTrackingCode">---</strong>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-headworld fw-bold">Select Qualified Field Technician <span class="text-danger">*</span></label>
                            <select name="assigned_officer_id" id="officerSelect" class="form-select-headworld" required>
                                <option value="">-- Choose Field Specialist --</option>
                                @foreach ($officers as $officer)
                                    <option value="{{ $officer->id }}">
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
            } else {
                select.value = '';
            }

            const modal = new bootstrap.Modal(document.getElementById('assignOfficerModal'));
            modal.show();
        }
    </script>
@endpush


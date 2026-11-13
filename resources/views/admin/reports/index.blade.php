@extends('layouts.app')

@section('title', 'Analytics, Reports & Audit Trails | ' . (getenv('APP_NAME') ?: 'Headworld'))

@section('header_title')
    <div class="d-flex align-items-center justify-content-between w-100 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0 text-navy-900">Operational Compliance & Audit Intelligence</h5>
            <small class="text-muted">Institutional quality assurance, SLA matrices, and immutable system records</small>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-gold btn-sm d-inline-flex align-items-center gap-1"
                onclick="window.print()">
                <i class="bi bi-printer-fill"></i> Print Executive Digest
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-content">

        <!-- Report Summary Hero Banner -->
        <div class="card-modern p-4 mb-4"
            style="background: linear-gradient(135deg, var(--navy-900, #0a192f), var(--navy-850, #112240)); color: #ffffff;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <span class="badge bg-warning text-dark fw-bold mb-2 text-uppercase letter-spacing-1">Institutional
                        Audit Assessment</span>
                    <h3 class="fw-bold text-white mb-1">Campus Facility Performance Digest</h3>
                    <p class="text-white-50 mb-0 small">Reporting Period: {{ now()->format('F Y') }} &bull; Certified by
                        Office of Campus Operations</p>
                </div>
                <div class="d-flex gap-3 text-center">
                    <div class="p-2 px-3 rounded-3 bg-dark bg-opacity-50 border border-white border-opacity-10">
                        <div class="fs-4 fw-bold text-warning">{{ $overallSlaRate }}%</div>
                        <div class="small text-white-50 font-mono" style="font-size:0.7rem;">SLA COMPLIANCE</div>
                    </div>
                    <div class="p-2 px-3 rounded-3 bg-dark bg-opacity-50 border border-white border-opacity-10">
                        <div class="fs-4 fw-bold text-success">{{ $totalMonthlyRequests }}</div>
                        <div class="small text-white-50 font-mono" style="font-size:0.7rem;">MONTHLY TICKETS</div>
                    </div>
                    <div class="p-2 px-3 rounded-3 bg-dark bg-opacity-50 border border-white border-opacity-10">
                        <div class="fs-4 fw-bold text-info">{{ $activeTechnicians }}</div>
                        <div class="small text-white-50 font-mono" style="font-size:0.7rem;">ACTIVE OFFICERS</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Actions Hub -->
        <div class="card-modern p-3 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <span class="fw-bold text-navy-900"><i
                            class="bi bi-file-earmark-spreadsheet-fill text-success me-1"></i> Data Export Center
                        (XLSX)</span>
                    <small class="text-muted d-block">Download certified spreadsheets formatted for institutional
                        reporting</small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.reports.export.requests') }}"
                        class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                        <i class="bi bi-download"></i> All Requests (.xlsx)
                    </a>
                    <a href="{{ route('admin.reports.export.officers') }}"
                        class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1">
                        <i class="bi bi-download"></i> Officer KPIs (.xlsx)
                    </a>
                    <a href="{{ route('admin.reports.export.audits') }}"
                        class="btn btn-sm btn-outline-dark d-inline-flex align-items-center gap-1">
                        <i class="bi bi-download"></i> Audit Trail (.xlsx)
                    </a>
                </div>
            </div>
        </div>

        <!-- SLA Breakdown Matrix & Failure Zones -->
        <div class="row g-4 mb-4">
            <!-- Departmental SLA Breakdown -->
            <div class="col-lg-6">
                <div class="card-modern p-4 h-100">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2 text-navy-900">
                        <i class="bi bi-shield-check text-warning"></i> Departmental SLA Compliance Breakdown
                    </h5>
                    <div class="d-flex flex-column gap-3">
                        @forelse($departmentalSla as $dept)
                            <div>
                                <div class="d-flex justify-content-between small fw-semibold mb-1">
                                    <span>{{ $dept['name'] }} (Target: &le; {{ $dept['target_hours'] }}h)</span>
                                    <span class="text-{{ $dept['status_color'] }}">{{ $dept['compliance_rate'] }}%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-{{ $dept['status_color'] }}"
                                        style="width: {{ min($dept['compliance_rate'], 100) }}%;"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted small">No maintenance categories found.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- High-Frequency Campus Failure Zones -->
            <div class="col-lg-6">
                <div class="card-modern p-4 h-100">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2 text-navy-900">
                        <i class="bi bi-exclamation-triangle-fill text-danger"></i> High-Frequency Campus Failure Zones
                        (Hotspots)
                    </h5>
                    <div class="d-flex flex-column gap-3">
                        @forelse($hotspots as $spot)
                            <div class="p-3 rounded-3 bg-light border d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="fw-bold mb-1 text-navy-900">{{ $spot->building }}</h6>
                                    <small class="text-muted">{{ $spot->wing_floor }}</small>
                                </div>
                                <span class="badge bg-danger">{{ $spot->total_incidents }} Incidents / 30d</span>
                            </div>
                        @empty
                            <div class="p-3 rounded-3 bg-light border text-center text-muted small">
                                No recurrent hotspots identified in the past 30 days.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Full Immutable Audit Trail -->
        <div class="card-modern">
            <div
                class="card-header-headworld p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title-headworld mb-0 fw-bold text-navy-900">
                        <i class="bi bi-shield-lock-fill text-warning me-1"></i> Service Requests Audit Stream (Security & Integrity)
                    </h5>
                    <small class="text-muted">Cryptographic event log recording all ticket mutations, technician allocations, and status updates</small>
                </div>
                <span class="badge bg-light text-navy-900 border"><i class="bi bi-check2-circle text-success me-1"></i> Service Requests Only</span>
            </div>

            <!-- Audit Filters Form -->
            <div class="p-3 bg-light border-bottom">
                <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold mb-1">Search Details / Actor</label>
                        <input type="text" name="search" class="form-control form-control-sm"
                            placeholder="Search keywords..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">Actor</label>
                        <select name="user_id" class="form-select form-select-sm">
                            <option value="">All Actors</option>
                            @foreach($auditUsers as $u)
                                <option value="{{ $u->id }}" {{ ($filters['user_id'] ?? '') == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">Action Protocol</label>
                        <select name="action" class="form-select form-select-sm">
                            <option value="">All Actions</option>
                            <option value="created" {{ ($filters['action'] ?? '') === 'created' ? 'selected' : '' }}>Created</option>
                            <option value="updated" {{ ($filters['action'] ?? '') === 'updated' ? 'selected' : '' }}>Updated</option>
                            <option value="status_updated" {{ ($filters['action'] ?? '') === 'status_updated' ? 'selected' : '' }}>Status Updated</option>
                            <option value="assigned_technician" {{ ($filters['action'] ?? '') === 'assigned_technician' ? 'selected' : '' }}>Assigned Technician</option>
                            <option value="cancelled_request" {{ ($filters['action'] ?? '') === 'cancelled_request' ? 'selected' : '' }}>Cancelled Request</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">From Date</label>
                        <input type="date" name="from_date" class="form-control form-control-sm"
                            value="{{ $filters['from_date'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">To Date</label>
                        <input type="date" name="to_date" class="form-control form-control-sm"
                            value="{{ $filters['to_date'] ?? '' }}">
                    </div>
                    <div class="col-md-1 d-flex gap-1">
                        <button type="submit" class="btn btn-navy btn-sm w-100" title="Apply Filter">
                            <i class="bi bi-filter"></i>
                        </button>
                        <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary btn-sm"
                            title="Clear Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="card-body-headworld p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Timestamp (UTC)</th>
                                <th>Actor & Identity</th>
                                <th>Action Protocol</th>
                                <th>Audit Details & Changes</th>
                                <th class="text-end pe-3">Verification</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($auditLogs as $log)
                                <tr>
                                    <td class="ps-3 font-mono small text-muted">
                                        {{ $log->created_at?->format('Y-m-d H:i:s') }}
                                    </td>
                                    <td>
                                        <div class="fw-bold small text-navy-900">{{ $log->user?->name ?? 'System' }}</div>
                                        <div class="small text-muted font-mono" style="font-size:0.75rem;">
                                            {{ $log->user?->email ?? 'Automated Daemon' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary font-mono">
                                            {{ strtoupper(str_replace('_', ' ', $log->action)) }}
                                        </span>
                                    </td>
                                    <td class="small text-break" style="max-width: 450px;">
                                        @if(!empty($log->details))
                                            {{ $log->details }}
                                        @elseif(!empty($log->changes))
                                            <pre class="bg-light p-1 rounded font-mono mb-0"
                                                style="font-size:0.7rem; max-height:80px; overflow-y:auto;">{{ json_encode($log->changes, JSON_PRETTY_PRINT) }}</pre>
                                        @else
                                            <span class="text-muted">Entity record modified.</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        <span
                                            class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 font-mono">
                                            <i class="bi bi-patch-check-fill me-1"></i> Verified
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        No audit logs matching current filter parameters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($auditLogs->hasPages())
                    <div class="p-3 border-top d-flex justify-content-end">
                        {{ $auditLogs->links() }}
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection
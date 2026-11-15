@extends('layouts.app')

@section('title', 'Technician Field Desk | ' . (getenv('APP_NAME') ?: 'Headworld'))

@section('header_title')
    <div class="d-flex align-items-center justify-content-between w-100 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0 text-navy-900">Campus Facilities Field Desk</h5>
            <small class="text-muted">Welcome back, {{ $user->name }} &bull; Active Technician Queue</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div id="officerSyncBadge">
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 d-inline-flex align-items-center gap-1">
                    <span class="status-dot bg-success"></span> Live Syncing
                </span>
            </div>
            <a href="{{ route('facility.tasks.index') }}" class="btn btn-gold btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-kanban me-1"></i> Interactive Kanban Pipeline
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-content">
        <!-- Emergency Triage Banner Container -->
        <div id="officerEmergencyBannerContainer">
            @if ($emergencyTicket)
                <div
                    class="alert alert-danger border-0 rounded-4 shadow-sm p-4 mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-danger text-white rounded-circle p-3 d-flex align-items-center justify-content-center">
                            <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1 text-danger">Immediate Action Required: {{ $emergencyTicket->title }}</h5>
                            <p class="small text-danger mb-0 opacity-75">
                                Ticket <strong>{{ $emergencyTicket->tracking_code }}</strong> &bull; Location:
                                {{ $emergencyTicket->building }} ({{ $emergencyTicket->room_number }}).
                                SLA Target: &lt; 2 Hours.
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('facility.tasks.show', $emergencyTicket->slug) }}" class="btn btn-danger text-nowrap px-3">
                        <i class="bi bi-tools me-1"></i> Start Emergency Work
                    </a>
                </div>
            @endif
        </div>

        <!-- Metric Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card-modern stat-card stat-amber">
                    <div class="stat-card-header">
                        <span class="stat-card-label">Assigned to Me</span>
                        <div class="stat-card-icon stat-icon-amber">
                            <i class="bi bi-tools"></i>
                        </div>
                    </div>
                    <div class="stat-card-value" id="kpiMyAssigned">{{ $kpiMyAssigned }}</div>
                    <div class="stat-card-trend">
                        <span>Active tickets in your queue</span>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card-modern stat-card stat-rose">
                    <div class="stat-card-header">
                        <span class="stat-card-label">Emergency Priority</span>
                        <div class="stat-card-icon stat-icon-rose">
                            <i class="bi bi-lightning-charge-fill"></i>
                        </div>
                    </div>
                    <div class="stat-card-value text-danger" id="kpiMyEmergency">{{ $kpiMyEmergency }}</div>
                    <div class="stat-card-trend">
                        <span class="text-danger">&lt; 4 Hours Maximum SLA</span>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card-modern stat-card stat-emerald">
                    <div class="stat-card-header">
                        <span class="stat-card-label">Resolved This Month</span>
                        <div class="stat-card-icon stat-icon-emerald">
                            <i class="bi bi-check2-all"></i>
                        </div>
                    </div>
                    <div class="stat-card-value text-success" id="kpiMyResolved">{{ $kpiMyResolved }}</div>
                    <div class="stat-card-trend">
                        <span class="trend-up"><i class="bi bi-arrow-up-right"></i> Completed & Signed Off</span>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card-modern stat-card stat-gold">
                    <div class="stat-card-header">
                        <span class="stat-card-label">SLA Compliance Rate</span>
                        <div class="stat-card-icon stat-icon-gold">
                            <i class="bi bi-shield-check"></i>
                        </div>
                    </div>
                    <div class="stat-card-value text-gold" id="kpiMySla">{{ $slaComplianceRate }}%</div>
                    <div class="stat-card-trend">
                        <span class="text-success">Institutional benchmark: &gt;90%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart.js Visualizations Row -->
        <div class="row g-4 mb-4">
            <!-- Weekly Resolution Velocity -->
            <div class="col-lg-7">
                <div class="card-modern p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 text-navy-900 d-flex align-items-center gap-2">
                            <i class="bi bi-speedometer2 text-warning"></i> 7-Day Task Influx vs Resolution Velocity
                        </h5>
                        <span class="badge bg-secondary text-navy-900 border">My Weekly Throughput</span>
                    </div>
                    <div style="height: 270px; position: relative;">
                        <canvas id="chartOfficerVelocity"></canvas>
                    </div>
                </div>
            </div>

            <!-- Task Category Breakdown -->
            <div class="col-lg-5">
                <div class="card-modern p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 text-navy-900 d-flex align-items-center gap-2">
                            <i class="bi bi-pie-chart text-warning"></i> My Tasks by Specialty
                        </h5>
                        <span class="badge bg-secondary text-muted border font-mono">Assigned</span>
                    </div>
                    <div style="height: 270px; position: relative;">
                        <canvas id="chartOfficerCategories"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Priority Queue Table -->
        <div class="card-modern mb-4">
            <div class="card-header-headworld d-flex justify-content-between align-items-center p-3 border-bottom">
                <h5 class="card-title-headworld mb-0 fw-bold">
                    <i class="bi bi-clock-history text-warning me-1"></i> My Active Work Order Queue (<span id="officerQueueCountBadge">{{ $queue->count() }}</span>)
                </h5>
                <a href="{{ route('facility.tasks.index') }}" class="btn btn-subtle btn-sm">
                    <i class="bi bi-kanban me-1"></i> Switch to Kanban Board
                </a>
            </div>

            <div class="card-body-headworld p-0">
                <div class="table-responsive-wrapper border-0">
                    <table class="table-headworld mb-0">
                        <thead>
                            <tr>
                                <th>Tracking Code</th>
                                <th>Category</th>
                                <th>Issue & Location</th>
                                <th>Priority</th>
                                <th>Current Status</th>
                                <th>Submitter</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="officerQueueTbody">
                            @forelse ($queue as $item)
                                <tr>
                                    <td>
                                        <a href="{{ route('facility.tasks.show', $item->slug) }}"
                                            class="fw-bold font-mono text-navy-900">
                                            {{ $item->tracking_code }}
                                        </a>
                                        <div class="small text-muted font-mono" style="font-size:0.75rem;">
                                            REQ-{{ str_pad((string) $item->id, 5, '0', STR_PAD_LEFT) }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary text-navy-900 border">
                                            <i class="bi {{ $item->category?->icon ?: 'bi-tools' }} me-1"
                                                style="color: {{ $item->category?->color ?: '#0a192f' }};"></i>
                                            {{ $item->category?->name ?: 'General' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width:260px;"
                                            title="{{ $item->title }}">{{ $item->title }}</div>
                                        <small class="text-muted">{{ $item->building }} &bull; {{ $item->room_number }}</small>
                                    </td>
                                    <td>
                                        <span class="{{ $item->priority_badge_class }}">
                                            {{ ucfirst($item->priority) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="{{ $item->status_badge_class }}">
                                            <span class="status-dot"></span>{{ $item->status_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold">{{ $item->user?->name ?: 'Anonymous' }}</div>
                                        <small class="text-muted">{{ $item->user?->email }}</small>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('facility.tasks.show', $item->slug) }}"
                                            class="btn btn-sm btn-gold py-1 px-3">
                                            <i class="bi bi-arrow-right-circle me-1"></i> Open Task
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-check2-circle fs-1 text-success d-block mb-2"></i>
                                        <div class="fw-bold">No Pending Work Orders</div>
                                        <p class="small text-muted mb-0">Your dispatch queue is all caught up!</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Daily Route & Quick Requisition Info -->
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card-modern p-4 h-100">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-geo-alt-fill text-warning"></i> Campus Active Hotspots
                    </h5>
                    <div class="d-flex flex-column gap-3">
                        <div class="p-3 rounded-3 bg-light border d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-1">Queen's Hall (Hostel)</h6>
                                <small class="text-muted">High volume residential complaints</small>
                            </div>
                            <span class="badge bg-navy-900 text-gold">Primary Hub</span>
                        </div>

                        <div class="p-3 rounded-3 bg-light border d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-1">Engineering Complex Block A</h6>
                                <small class="text-muted">Laboratories & Workshop Facilities</small>
                            </div>
                            <span class="badge bg-secondary text-navy-900 border">Active Zone</span>
                        </div>

                        <div class="p-3 rounded-3 bg-light border d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-1">Maxwell Lecture Theatres</h6>
                                <small class="text-muted">AV, Audio & Projector Equipment</small>
                            </div>
                            <span class="badge bg-secondary text-navy-900 border">Daily Route</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card-modern p-4 h-100">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-box-seam-fill text-warning"></i> Workshop Requisitions & Depot
                    </h5>
                    <div class="p-3 rounded-3 bg-light border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="small text-navy-900">Standard Copper Elbows & Soldering Kit</strong>
                            <span class="badge bg-success">In Stock at Central Depot</span>
                        </div>
                        <small class="text-muted d-block">Available for immediate plumbing maintenance dispatch.</small>
                    </div>

                    <div class="p-3 rounded-3 bg-light border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="small text-navy-900">Cat6 Ethernet Spools & RJ45 Crimpers</strong>
                            <span class="badge bg-primary">IT Services Workshop</span>
                        </div>
                        <small class="text-muted d-block">Available for Wi-Fi AP cabling and network fixes.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Server-Side Hydrated Payload for Officer Dashboard -->
    <script type="application/json" id="officerDashboardPayload" data-poll-url="{{ route('facility.dashboard.data') }}">
        {!! json_encode($metrics) !!}
    </script>

    <!-- Chart.js v4 CDN & Modular Dashboard Controllers -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <script src="{{ asset('assets/js/dashboard-charts.js') }}"></script>
    <script src="{{ asset('assets/js/officer-dashboard.js') }}"></script>
@endpush

@extends('layouts.app')

@section('title', 'Executive Operations Command | ' . (getenv('APP_NAME') ?: 'Headworld'))

@section('header_title')
    <div class="d-flex align-items-center justify-content-between w-100 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0 text-navy-900">Executive Facility Operations Command</h5>
            <small class="text-muted">Campus-wide dispatch telemetry & institutional SLA tracking</small>
        </div>
        <div id="dashboardSyncBadge">
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 d-inline-flex align-items-center gap-1">
                <span class="status-dot bg-success"></span> Live Syncing
            </span>
        </div>
    </div>
@endsection

@push('styles')
@endpush

@section('content')
    <div class="page-content">
        <!-- Metric Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card-modern stat-card stat-gold">
                    <div class="stat-card-header">
                        <span class="stat-card-label">Total Logged Incidents</span>
                        <div class="stat-card-icon stat-icon-gold">
                            <i class="bi bi-clipboard2-data-fill"></i>
                        </div>
                    </div>
                    <div class="stat-card-value" id="adminTotalRequests">{{ $metrics['kpis']['totalRequests'] ?? 0 }}</div>
                    <div class="stat-card-trend">
                        <span>All campus facilities</span>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card-modern stat-card stat-amber">
                    <div class="stat-card-header">
                        <span class="stat-card-label">Active Field Dispatches</span>
                        <div class="stat-card-icon stat-icon-amber">
                            <i class="bi bi-gear-wide-connected"></i>
                        </div>
                    </div>
                    <div class="stat-card-value" id="adminActiveDispatches">{{ $metrics['kpis']['activeDispatches'] ?? 0 }}</div>
                    <div class="stat-card-trend">
                        <span class="trend-up"><i class="bi bi-clock"></i> Assigned & In Progress</span>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card-modern stat-card stat-rose">
                    <div class="stat-card-header">
                        <span class="stat-card-label">Emergency Critical</span>
                        <div class="stat-card-icon stat-icon-rose">
                            <i class="bi bi-exclamation-octagon-fill"></i>
                        </div>
                    </div>
                    <div class="stat-card-value text-danger" id="adminEmergencyCount">{{ $metrics['kpis']['emergencyCount'] ?? 0 }}</div>
                    <div class="stat-card-trend">
                        <span class="text-danger">&lt; 4 Hours Maximum SLA</span>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card-modern stat-card stat-emerald">
                    <div class="stat-card-header">
                        <span class="stat-card-label">SLA Compliance Index</span>
                        <div class="stat-card-icon stat-icon-emerald">
                            <i class="bi bi-shield-check"></i>
                        </div>
                    </div>
                    <div class="stat-card-value text-success" id="adminSlaRate">{{ $metrics['kpis']['slaComplianceRate'] ?? 95 }}%</div>
                    <div class="stat-card-trend">
                        <span class="text-success"><i class="bi bi-graph-up-arrow"></i> Target: &gt;90% Benchmark</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart.js Visualizations Row -->
        <div class="row g-4 mb-4">
            <!-- Line Chart: 7-Day Trend -->
            <div class="col-lg-8">
                <div class="card-modern p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 text-navy-900 d-flex align-items-center gap-2">
                            <i class="bi bi-graph-up text-warning"></i> 7-Day Incident Influx vs Resolution Velocity
                        </h5>
                        <span class="badge bg-secondary text-navy-900 border">Daily Aggregation</span>
                    </div>
                    <div style="height: 300px; position: relative;">
                        <canvas id="chartRequestsTrend"></canvas>
                    </div>
                </div>
            </div>

            <!-- Doughnut Chart: Category Distribution -->
            <div class="col-lg-4">
                <div class="card-modern p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 text-navy-900 d-flex align-items-center gap-2">
                            <i class="bi bi-pie-chart text-warning"></i> Fault Category Breakdown
                        </h5>
                        <span class="badge bg-secondary text-muted border font-mono">Real-time</span>
                    </div>
                    <div style="height: 300px; position: relative;">
                        <canvas id="chartCategoryDonut"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Technician Performance & Recent Audit Stream -->
        <div class="row g-4 mb-4">
            <!-- Bar Chart -->
            <div class="col-lg-7">
                <div class="card-modern p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-person-check-fill text-warning"></i> Lead Technician Workload & Closures
                        </h5>
                        <a href="{{ route('admin.requests.index') }}" class="btn btn-subtle btn-sm">
                            <i class="bi bi-journal-text me-1"></i> Dispatch Console
                        </a>
                    </div>
                    <div style="height: 270px; position: relative;">
                        <canvas id="chartOfficerPerf"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Audit Stream -->
            <div class="col-lg-5">
                <div class="card-modern p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-shield-shaded text-warning"></i> Real-Time Audit Log
                        </h5>
                        <span class="badge bg-secondary text-muted border">Live Stream</span>
                    </div>
                    <div class="d-flex flex-column gap-3" id="adminAuditStream">
                        @forelse ($metrics['recentAudits'] as $audit)
                            <div class="p-2 rounded-3 bg-light border small">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-navy-900">{{ $audit['actor'] }}</strong>
                                    <span class="text-muted font-mono" style="font-size:0.75rem;">{{ $audit['time_ago'] }}</span>
                                </div>
                                <div class="text-muted">{{ $audit['details'] }}</div>
                            </div>
                        @empty
                            <div class="p-3 text-center text-muted small bg-light rounded-3">
                                <i class="bi bi-shield-check fs-4 d-block mb-1 text-muted"></i>
                                No recent audit events
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Server-Side Hydrated Payload for Dashboard Controller -->
    <script type="application/json" id="adminDashboardPayload" data-poll-url="{{ route('admin.dashboard.data') }}">
        {!! json_encode($metrics) !!}
    </script>

    <!-- Chart.js v4 CDN & Modular Dashboard Controllers -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <script src="{{ asset('assets/js/dashboard-charts.js') }}"></script>
    <script src="{{ asset('assets/js/admin-dashboard.js') }}"></script>
@endpush

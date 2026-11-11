@extends('layouts.admin-layout')

@section('title', 'Admin Dashboard | ' . getenv('APP_NAME'))

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
                    <div class="stat-card-value" id="adminTotalRequests">0</div>
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
                    <div class="stat-card-value" id="adminActiveDispatches">0</div>
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
                    <div class="stat-card-value" id="adminEmergencyCount">0</div>
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
                    <div class="stat-card-value" id="adminSlaRate">94.8%</div>
                    <div class="stat-card-trend">
                        <span class="text-success"><i class="bi bi-graph-up-arrow"></i> +2.1% this
                            semester</span>
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
                            <i class="bi bi-graph-up text-warning"></i> 7-Day Incident Influx vs Resolution
                            Velocity
                        </h5>
                        <span class="badge bg-light text-muted border">Daily Aggregation</span>
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
                    </div>
                    <div style="height: 300px; position: relative;">
                        <canvas id="chartCategoryDonut"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Technician Performance & Unassigned Immediate Queue -->
        <div class="row g-4 mb-4">
            <!-- Bar Chart -->
            <div class="col-lg-7">
                <div class="card-modern p-4 h-100">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-person-check-fill text-warning"></i> Lead Technician Workload & Closures
                    </h5>
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
                        <a href="admin-reports.html" class="small text-muted text-decoration-none">View All</a>
                    </div>
                    <div class="d-flex flex-column gap-3" id="adminAuditStream">
                        <!-- Populated dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Chart.js v4 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
@endpush
@extends('layouts.app')

@section('title', 'Resident Overview | ' . (getenv('APP_NAME') ?: 'Headworld'))

@section('header_title')
    <h5 class="fw-bold mb-0 text-navy-900">Campus Resident Portal</h5>
    <small class="text-muted">{{ date('l, F j, Y') }}</small>
@endsection

@section('content')
    <div class="page-content">
        <!-- Welcome Banner -->
        <div class="card-modern p-4 mb-4"
            style="background: linear-gradient(135deg, var(--navy-900), var(--navy-850)); color: #ffffff;">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge bg-warning text-dark fw-bold mb-2 text-uppercase letter-spacing-1">Facility
                        Concierge</span>
                    <h2 class="fw-bold mb-2 text-white">Welcome back, {{ $user->first_name ?: 'Resident' }}</h2>
                    <p class="text-white-50 mb-3 max-w-600">
                        Notice an electrical fault, plumbing leak, damaged furniture, or classroom equipment issue? Lodge
                        your request now to trigger priority technical dispatch.
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('user.requests.create') }}" class="btn btn-gold">
                            <i class="bi bi-plus-circle me-1"></i> Lodge New Service Request
                        </a>
                        <a href="{{ route('user.requests.index') }}" class="btn btn-outline-light">
                            <i class="bi bi-clock-history me-1"></i> Track Past Complaints
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 d-none d-lg-flex justify-content-end">
                    <div class="text-center p-3 rounded-4 border border-warning border-opacity-25"
                        style="background: rgba(8, 14, 26, 0.4); min-width: 200px;">
                        <div class="display-6 fw-bold text-warning">{{ $activeTickets }}</div>
                        <div class="small text-white-50 text-uppercase letter-spacing-1">Active Tickets</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card-modern stat-card stat-gold">
                    <div class="stat-card-header">
                        <span class="stat-card-label">Total Logged</span>
                        <div class="stat-card-icon stat-icon-gold">
                            <i class="bi bi-clipboard2-data"></i>
                        </div>
                    </div>
                    <div class="stat-card-value">{{ $totalLogged }}</div>
                    <div class="stat-card-trend">
                        <span>All-time submitted complaints</span>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card-modern stat-card stat-amber">
                    <div class="stat-card-header">
                        <span class="stat-card-label">In Progress / Assigned</span>
                        <div class="stat-card-icon stat-icon-amber">
                            <i class="bi bi-gear-wide-connected"></i>
                        </div>
                    </div>
                    <div class="stat-card-value">{{ $inProgress }}</div>
                    <div class="stat-card-trend">
                        <span class="trend-up"><i class="bi bi-clock"></i> Active with technicians</span>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card-modern stat-card stat-emerald">
                    <div class="stat-card-header">
                        <span class="stat-card-label">Successfully Resolved</span>
                        <div class="stat-card-icon stat-icon-emerald">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                    </div>
                    <div class="stat-card-value">{{ $resolved }}</div>
                    <div class="stat-card-trend">
                        <span class="text-success"><i class="bi bi-shield-check"></i> Sign-off verified</span>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card-modern stat-card stat-rose">
                    <div class="stat-card-header">
                        <span class="stat-card-label">Emergency Priority</span>
                        <div class="stat-card-icon stat-icon-rose">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                    </div>
                    <div class="stat-card-value">{{ $emergencyCount }}</div>
                    <div class="stat-card-trend">
                        <span class="text-danger">Target SLA: &lt; 4 Hours</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Requests List -->
        <div class="card-modern mb-4">
            <div class="card-header-headworld">
                <h5 class="card-title-headworld">
                    <i class="bi bi-activity text-warning"></i> Recent Service Complaints
                </h5>
                <a href="{{ route('user.requests.index') }}" class="btn btn-subtle btn-sm">
                    View All History <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="card-body-headworld p-0">
                <div class="table-responsive-wrapper border-0">
                    <table class="table-headworld">
                        <thead>
                            <tr>
                                <th>Tracking Code</th>
                                <th>Category</th>
                                <th>Complaint Title</th>
                                <th>Location</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Assigned Technician</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentRequests as $req)
                                <tr>
                                    <td>
                                        <a href="{{ route('user.requests.show', $req->slug) }}"
                                            class="fw-bold text-navy-900 font-mono">
                                            {{ $req->tracking_code }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-navy-900 border">
                                            <i class="bi {{ $req->category?->icon ?: 'bi-tools' }} me-1"></i>
                                            {{ $req->category?->name ?: 'General' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width: 240px;"
                                            title="{{ $req->title }}">
                                            {{ $req->title }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="small text-muted">{{ $req->building }}</span>
                                        <div class="small fw-semibold text-dark">{{ $req->room_number }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $req->priority_badge_class }}">
                                            {{ ucfirst($req->priority) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $req->status_badge_class }}">
                                            {{ $req->status_label }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($req->assignedOfficer)
                                            <div class="d-flex align-items-center gap-1 small">
                                                <i class="bi bi-person-check-fill text-success"></i>
                                                <span>{{ $req->assignedOfficer->name }}</span>
                                            </div>
                                        @else
                                            <span class="badge bg-light text-muted border">Pending Dispatch</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('user.requests.show', $req->slug) }}" class="btn btn-sm btn-subtle">
                                            Details <i class="bi bi-chevron-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-clipboard2-check fs-1 text-muted d-block mb-2"></i>
                                        <div class="fw-bold mb-1">No Service Requests Found</div>
                                        <div class="small mb-3">You haven't lodged any campus maintenance tickets yet.</div>
                                        <a href="{{ route('user.requests.create') }}" class="btn btn-gold btn-sm">
                                            <i class="bi bi-plus-lg me-1"></i> Submit First Request
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Campus Maintenance Announcements & Quick Tips -->
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card-modern p-4 h-100">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-megaphone-fill text-warning"></i> Campus Facility Bulletins
                    </h5>
                    <div class="d-flex flex-column gap-3">
                        <div class="p-3 rounded-3 bg-light border">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-primary">Power Substation 2</span>
                                <small class="text-muted">Today, 06:00 AM</small>
                            </div>
                            <h6 class="fw-bold mb-1">Scheduled Electrical Testing in Queen's Hall</h6>
                            <p class="small text-muted mb-0">Emergency lighting circuits will undergo simulated load testing
                                this Saturday between 14:00 and 16:00.</p>
                        </div>

                        <div class="p-3 rounded-3 bg-light border">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-info text-dark">IT Backbone</span>
                                <small class="text-muted">Yesterday</small>
                            </div>
                            <h6 class="fw-bold mb-1">High-Density Wi-Fi 6 Upgrades in Main Library</h6>
                            <p class="small text-muted mb-0">Additional access points installed in Quiet Commons to
                                eliminate study desk dead zones.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card-modern p-4 h-100">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-shield-check text-warning"></i> Guidelines for Fast Resolution
                    </h5>
                    <ul class="list-unstyled d-flex flex-column gap-3 small mb-0">
                        <li class="d-flex gap-2">
                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            <div>
                                <strong>Attach Clear Evidence Photos:</strong> Capturing the pipe joint or circuit box helps
                                technicians pack the exact fitting before arriving.
                            </div>
                        </li>
                        <li class="d-flex gap-2">
                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            <div>
                                <strong>Specific Room Coordinates:</strong> Always note the block, wing, and door label
                                (e.g. Queen's Hall, Wing B, Rm 304).
                            </div>
                        </li>
                        <li class="d-flex gap-2">
                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            <div>
                                <strong>Flag True Emergencies:</strong> Use Emergency status strictly for active flooding,
                                electrical arcing, gas leaks, or safety hazards.
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
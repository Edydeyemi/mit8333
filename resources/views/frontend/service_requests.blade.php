@extends('layouts.app')

@section('title', 'My Request History | ' . (getenv('APP_NAME') ?: 'Headworld'))

@section('header_title')
    <h5 class="fw-bold mb-0 text-navy-900">My Maintenance Log</h5>
    <small class="text-muted">Search and review all lodged campus tickets</small>
@endsection

@section('content')
    <div class="page-content">
        <!-- Filter and Search Toolbar -->
        <div class="card-modern p-3 mb-4">
            <form method="GET" action="{{ route('user.requests.index') }}" class="row g-2 align-items-center">
                <!-- Search -->
                <div class="col-lg-3 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control-headworld border-start-0"
                            placeholder="Search by title, code (VRTS-...), or room..."
                            value="{{ request('search') }}">
                    </div>
                </div>

                <!-- Category Filter -->
                <div class="col-sm-6 col-lg-2">
                    <select name="category" class="form-select-headworld">
                        <option value="all">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="col-sm-6 col-lg-2">
                    <select name="status" class="form-select-headworld">
                        <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                        <option value="assigned" {{ request('status') === 'assigned' ? 'selected' : '' }}>Officer Assigned</option>
                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="on_hold" {{ request('status') === 'on_hold' ? 'selected' : '' }}>On Hold</option>
                        <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <!-- Priority Filter -->
                <div class="col-sm-6 col-lg-2">
                    <select name="priority" class="form-select-headworld">
                        <option value="all" {{ request('priority') === 'all' ? 'selected' : '' }}>All Priorities</option>
                        <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="emergency" {{ request('priority') === 'emergency' ? 'selected' : '' }}>Emergency</option>
                    </select>
                </div>

                <!-- Action buttons -->
                <div class="col-sm-6 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-navy flex-grow-1">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    @if (request()->hasAny(['search', 'category', 'status', 'priority']))
                        <a href="{{ route('user.requests.index') }}" class="btn btn-subtle" title="Reset Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Master Table -->
        <div class="card-modern">
            <div class="card-header-headworld">
                <h5 class="card-title-headworld">
                    <i class="bi bi-list-columns-reverse text-warning"></i> Service Complaints ({{ $requests->total() }})
                </h5>
                <a href="{{ route('user.requests.create') }}" class="btn btn-gold btn-sm d-inline-flex align-items-center gap-1">
                    <i class="bi bi-plus-lg"></i> Lodge Incident
                </a>
            </div>

            <div class="card-body-headworld p-0">
                <div class="table-responsive-wrapper border-0">
                    <table class="table-headworld">
                        <thead>
                            <tr>
                                <th>Tracking Code</th>
                                <th>Category</th>
                                <th>Issue Summary</th>
                                <th>Location Coordinates</th>
                                <th>Priority</th>
                                <th>Progress Status</th>
                                <th>Date Filed</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($requests as $req)
                                <tr>
                                    <td>
                                        <a href="{{ route('user.requests.show', $req->slug) }}" class="fw-bold text-navy-900 font-mono">
                                            {{ $req->tracking_code }}
                                        </a>
                                        @if ($req->attachments->isNotEmpty())
                                            <span class="badge bg-light text-muted border ms-1" title="{{ $req->attachments->count() }} photo(s) attached">
                                                <i class="bi bi-paperclip"></i> {{ $req->attachments->count() }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-navy-900 border">
                                            <i class="bi {{ $req->category?->icon ?: 'bi-tools' }} me-1"></i>
                                            {{ $req->category?->name ?: 'General' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width: 260px;" title="{{ $req->title }}">
                                            {{ $req->title }}
                                        </div>
                                        <div class="small text-muted text-truncate" style="max-width: 260px;">
                                            {{ Str::limit($req->description, 50) }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-dark">{{ $req->building }}</div>
                                        <div class="small text-muted">{{ $req->wing_floor }} &bull; {{ $req->room_number }}</div>
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
                                        @if ($req->assignedOfficer)
                                            <div class="small text-muted mt-1">
                                                <i class="bi bi-person-fill text-muted"></i> {{ $req->assignedOfficer->name }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="small text-dark">{{ $req->created_at->format('M d, Y') }}</div>
                                        <div class="small text-muted">{{ $req->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('user.requests.show', $req->slug) }}" class="btn btn-sm btn-subtle">
                                            View Details <i class="bi bi-chevron-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-search fs-1 text-muted d-block mb-2"></i>
                                        <div class="fw-bold mb-1">No Service Complaints Found</div>
                                        <div class="small mb-3">
                                            @if (request()->hasAny(['search', 'category', 'status', 'priority']))
                                                Try adjusting or clearing your filters to see more results.
                                            @else
                                                You have not submitted any campus maintenance requests yet.
                                            @endif
                                        </div>
                                        @if (request()->hasAny(['search', 'category', 'status', 'priority']))
                                            <a href="{{ route('user.requests.index') }}" class="btn btn-subtle btn-sm">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i> Clear Filters
                                            </a>
                                        @else
                                            <a href="{{ route('user.requests.create') }}" class="btn btn-gold btn-sm">
                                                <i class="bi bi-plus-lg me-1"></i> Lodge New Service Request
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            @if ($requests->hasPages())
                <div class="p-3 border-top d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                    <small class="text-muted">
                        Showing {{ $requests->firstItem() }} to {{ $requests->lastItem() }} of {{ $requests->total() }} records
                    </small>
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

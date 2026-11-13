<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', (getenv('APP_NAME') ?: 'Headworld') . ' | Campus Operations')</title>

    <!-- Bootstrap 5 CSS Foundation -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom Design Tokens & Styles -->
    <link rel="stylesheet" href="{{ asset('assets/css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}">

    @stack('styles')
</head>

<body>

    <!-- Top-Edge Global Page Progress Loader -->
    <div id="globalPageLoader" class="page-loader-progress"></div>

    <div class="sidebar-backdrop"></div>

    <div class="app-layout">
        @php
            $currentUser = auth()->user();
            $brandRoute = route('user.dashboard');
            $brandSubtitle = 'CAMPUS PORTAL';

            if ($currentUser?->can('reports.view') || $currentUser?->can('tasks.assign')) {
                $brandRoute = route('admin.dashboard');
                $brandSubtitle = 'FACILITY COMMAND';
            } elseif ($currentUser?->can('tasks.view')) {
                $brandRoute = route('facility.dashboard');
                $brandSubtitle = 'FIELD DESK';
            }
        @endphp

        <!-- Sidebar Navigation -->
        <aside class="app-sidebar">
            <div class="sidebar-header">
                <a href="{{ $brandRoute }}" class="brand-logo">
                    <div class="brand-crest">V</div>
                    <div>
                        <div class="brand-title">HEADWORLD</div>
                        <div class="brand-subtitle">{{ $brandSubtitle }}</div>
                    </div>
                </a>
                <button type="button" class="sidebar-close-btn" aria-label="Close Navigation">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="sidebar-nav">
                {{-- 1. Operations Command (Super Admin, Admin, Data Entry) --}}
                @canany(['reports.view', 'tasks.assign', 'categories.view', 'users.view', 'roles.view'])
                    <div class="nav-section-title">Operations Command</div>
                    @can('reports.view')
                        <a href="{{ route('admin.dashboard') }}"
                            class="nav-link-custom {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <i class="bi bi-speedometer2"></i>
                            <span>Executive Overview</span>
                        </a>
                    @endcan

                    @canany(['tasks.assign', 'tasks.reassign', 'reports.view'])
                        <a href="{{ route('admin.requests.index') }}"
                            class="nav-link-custom {{ request()->routeIs('admin.requests.*') ? 'active' : '' }}">
                            <i class="bi bi-journal-text"></i>
                            <span>Dispatch & Requests</span>
                        </a>
                    @endcan

                    @canany(['categories.view', 'categories.manage'])
                        <a href="{{ route('admin.categories.index') }}"
                            class="nav-link-custom {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                            <i class="bi bi-collection-fill"></i>
                            <span>Service Categories</span>
                        </a>
                    @endcan

                    @canany(['users.view', 'users.manage', 'officers.add', 'officers.modify'])
                        <a href="{{ route('admin.users.index') }}"
                            class="nav-link-custom {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <i class="bi bi-people-fill"></i>
                            <span>User Directory</span>
                        </a>
                    @endcan

                    @canany(['roles.view', 'roles.manage'])
                        <a href="{{ route('admin.roles.index') }}"
                            class="nav-link-custom {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                            <i class="bi bi-shield-lock-fill"></i>
                            <span>Roles & Permissions</span>
                        </a>
                    @endcan

                    @can('reports.view')
                        <a href="{{ route('admin.reports.index') }}"
                            class="nav-link-custom {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                            <i class="bi bi-file-earmark-bar-graph-fill"></i>
                            <span>Analytics & Audit</span>
                        </a>
                    @endcan

                    @canany(['reports.view', 'tasks.assign'])
                        <a href="{{ route('admin.notifications.index') }}"
                            class="nav-link-custom {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
                            <i class="bi bi-bell-fill"></i>
                            <span>Notifications Center</span>
                            <span class="nav-badge d-none" id="sidebarNotificationBadge">0</span>
                        </a>
                    @endcan
                @endcanany

                {{-- 2. Field Operations (Maintenance Officers) --}}
                @canany(['tasks.view', 'tasks.update'])
                    <div class="nav-section-title mt-3">Field Operations</div>
                    @can('tasks.view')
                        <a href="{{ route('facility.dashboard') }}"
                            class="nav-link-custom {{ request()->routeIs('facility.dashboard') ? 'active' : '' }}">
                            <i class="bi bi-speedometer"></i>
                            <span>Technician Desk</span>
                        </a>
                        <a href="{{ route('facility.tasks.index') }}"
                            class="nav-link-custom {{ request()->routeIs('facility.tasks.*') ? 'active' : '' }}">
                            <i class="bi bi-kanban"></i>
                            <span>Tasks & Kanban</span>
                        </a>
                    @endcan
                @endcanany

                {{-- 3. Requester Portal (Students, Faculty, Staff) --}}
                @canany(['requests.view', 'requests.create'])
                    <div class="nav-section-title mt-3">Resident Portal</div>
                    @can('requests.view')
                        <a href="{{ route('user.dashboard') }}"
                            class="nav-link-custom {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
                            <i class="bi bi-grid-1x2-fill"></i>
                            <span>My Overview</span>
                        </a>
                    @endcan

                    @can('requests.create')
                        <a href="{{ route('user.requests.create') }}"
                            class="nav-link-custom {{ request()->routeIs('user.requests.create') ? 'active' : '' }}">
                            <i class="bi bi-plus-circle-fill"></i>
                            <span>Submit Request</span>
                        </a>
                    @endcan

                    @can('requests.view')
                        <a href="{{ route('user.requests.index') }}"
                            class="nav-link-custom {{ request()->routeIs('user.requests.index') || request()->routeIs('user.requests.show') ? 'active' : '' }}">
                            <i class="bi bi-list-task"></i>
                            <span>My Request History</span>
                        </a>
                    @endcan
                @endcanany

                {{-- 4. Campus Portals & Public Links --}}
                <div class="nav-section-title mt-3">Campus Portals</div>
                <a href="{{ url('/') }}" class="nav-link-custom">
                    <i class="bi bi-house"></i>
                    <span>Campus Homepage</span>
                </a>
            </div>

            <div class="sidebar-footer">
                <div class="user-sidebar-card mb-3">
                    <div class="user-avatar user-sidebar-avatar bg-navy-900 text-gold fw-bold"
                        style="background-color: var(--navy-900, #0a192f); color: var(--gold-500, #d4af37);">
                        {{ $currentUser?->initials ?: 'U' }}
                    </div>
                    <div class="user-sidebar-info">
                        <div class="user-sidebar-name">{{ $currentUser?->name ?: 'Campus User' }}</div>
                        <div class="user-sidebar-role text-capitalize">
                            {{ str_replace('_', ' ', $currentUser?->role ?: 'Resident') }}
                        </div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" id="sidebarLogoutForm">
                    @csrf
                    <button type="submit"
                        class="btn btn-sm btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-box-arrow-right"></i> Sign Out
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="app-main">
            <header class="app-header">
                <div class="header-left">
                    <button type="button" class="header-toggle-btn" aria-label="Toggle Sidebar">
                        <i class="bi bi-list"></i>
                    </button>
                    @hasSection('header_title')
                        @yield('header_title')
                    @else
                        <div>
                            @if ($currentUser?->can('reports.view'))
                                <h5 class="fw-bold mb-0 text-navy-900">Campus Facility Command Center</h5>
                                <small class="text-muted">Real-time incident dispatching and SLA intelligence</small>
                            @elseif ($currentUser?->can('tasks.view'))
                                <h5 class="fw-bold mb-0 text-navy-900">Technician Field Operations</h5>
                                <small class="text-muted">Active Work Orders & Maintenance Pipeline</small>
                            @else
                                <h5 class="fw-bold mb-0 text-navy-900">Campus Resident Portal</h5>
                                <small class="text-muted">{{ date('l, F j, Y') }} &bull; Maintenance Self-Service</small>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="header-right d-flex align-items-center gap-2">
                    <!-- Notification Bell & Dropdown -->
                    <div class="dropdown" id="headerNotificationDropdown">
                        <button type="button" class="btn btn-sm btn-icon btn-subtle position-relative"
                            id="notificationDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false"
                            aria-label="View notifications">
                            <i class="bi bi-bell-fill fs-5"></i>
                            <span id="headerNotificationBadge"
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"
                                style="font-size: 0.65rem;">
                                0
                            </span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-0"
                            style="width: 340px; border-radius: 12px; overflow: hidden;"
                            aria-labelledby="notificationDropdownBtn">
                            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0 text-navy-900">
                                    <i class="bi bi-bell-fill text-warning me-1"></i> Notifications
                                </h6>
                                <button type="button" class="btn btn-xs btn-subtle text-primary"
                                    id="btnMarkAllReadDropdown">
                                    Mark all read
                                </button>
                            </div>
                            <div id="headerNotificationList" class="divide-y"
                                style="max-height: 320px; overflow-y: auto;">
                                <div class="text-center py-4 text-muted small" id="headerNotificationEmpty">
                                    <i class="bi bi-bell-slash fs-3 d-block mb-1"></i>
                                    No new notifications
                                </div>
                            </div>
                            <div class="p-2 text-center bg-light border-top">
                                <a href="{{ route('notifications.index') }}"
                                    class="small fw-semibold text-navy-900 text-decoration-none">
                                    View All Notifications <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Theme Toggle -->
                    <button type="button" class="btn btn-sm btn-icon btn-subtle theme-toggle-btn"
                        aria-label="Toggle Theme">
                        <i class="bi bi-moon-stars-fill"></i>
                    </button>

                    <!-- Contextual Quick Action Button -->
                    @if ($currentUser?->can('users.manage'))
                        <a href="{{ route('admin.users.index') }}"
                            class="btn btn-gold btn-sm d-inline-flex align-items-center gap-1">
                            <i class="bi bi-person-gear"></i> Personnel
                        </a>
                    @elseif ($currentUser?->can('tasks.view'))
                        <a href="{{ route('facility.tasks.index') }}"
                            class="btn btn-gold btn-sm d-inline-flex align-items-center gap-1">
                            <i class="bi bi-kanban"></i> Field Pipeline
                        </a>
                    @elseif ($currentUser?->can('requests.create'))
                        <a href="{{ route('user.requests.create') }}"
                            class="btn btn-gold btn-sm d-inline-flex align-items-center gap-1">
                            <i class="bi bi-plus-lg"></i> Lodge Incident
                        </a>
                    @endif
                </div>
            </header>

            @yield('content')

            <footer class="app-footer">
                <div>&copy; {{ date('Y') }} Headworld Campus Operations. All rights reserved.</div>
                <div class="d-flex gap-3">
                    @can('reports.view')
                        <a href="{{ route('admin.dashboard') }}" class="text-muted">Executive Desk</a>
                    @endcan
                    @can('tasks.view')
                        <a href="{{ route('facility.tasks.index') }}" class="text-muted">Field Tasks</a>
                    @endcan
                    @can('requests.view')
                        <a href="{{ route('user.requests.index') }}" class="text-muted">My Requests</a>
                    @endcan
                    <a href="{{ route('notifications.index') }}" class="text-muted">Alerts</a>
                </div>
            </footer>
        </main>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/toast.js') }}"></script>
    <script src="{{ asset('assets/js/theme.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    <script src="{{ asset('assets/js/password-helper.js') }}"></script>

    @stack('scripts')
</body>

</html>

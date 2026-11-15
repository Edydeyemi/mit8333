<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', (getenv('APP_NAME') ?: 'Headworld') . ' | Campus Operations')</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

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
                <a href="{{ $brandRoute }}" class="brand-logo" aria-label="HeadWorld Campus Operations">
                    <img src="{{ asset('assets/images/logo-mark.svg') }}" alt="HeadWorld Crest" class="brand-logo-mark">
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
                    @if ($currentUser?->profile_photo_url)
                        <img src="{{ $currentUser->profile_photo_url }}" alt="{{ $currentUser->name }}"
                            class="user-avatar user-sidebar-avatar rounded-circle object-fit-cover shadow-xs border"
                            id="sidebarUserAvatarImg" style="width: 40px; height: 40px;">
                    @else
                        <div class="user-avatar user-sidebar-avatar bg-navy-900 text-gold fw-bold"
                            style="background-color: var(--navy-900, #0a192f); color: var(--gold-500, #d4af37);"
                            id="sidebarUserAvatarFallback">
                            {{ $currentUser?->initials ?: 'U' }}
                        </div>
                    @endif
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
                <div class="header-left d-flex align-items-center gap-2">
                    <button type="button" class="header-toggle-btn" aria-label="Toggle Sidebar">
                        <i class="bi bi-list"></i>
                    </button>
                    <!-- Mobile / Tablet Brand Mark Header Shortcut -->
                    <a href="{{ $brandRoute }}" class="d-flex align-items-center text-decoration-none me-2 d-lg-none" aria-label="HeadWorld Home">
                        <img src="{{ asset('assets/images/logo-mark.svg') }}" alt="HeadWorld Crest" style="height: 32px; width: 32px;" class="brand-logo-mark">
                    </a>
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

                    <!-- User Profile Avatar & Actions Dropdown -->
                    <div class="dropdown" id="headerUserDropdown">
                        <button type="button" class="btn btn-subtle p-0 rounded-circle border-0 d-flex align-items-center"
                            id="userDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="User account menu"
                            style="width: 38px; height: 38px;">
                            @if ($currentUser?->profile_photo_url)
                                <img src="{{ $currentUser->profile_photo_url }}" alt="{{ $currentUser->name }}"
                                    class="rounded-circle object-fit-cover shadow-xs border border-2 border-warning"
                                    style="width: 38px; height: 38px;" id="headerUserAvatarImg">
                            @else
                                <div class="user-avatar rounded-circle d-flex align-items-center justify-content-center shadow-xs border border-2 border-warning fw-bold text-gold"
                                    style="width: 38px; height: 38px; background-color: var(--navy-900, #0a192f); color: var(--gold-500, #d4af37); font-size: 0.85rem;"
                                    id="headerUserAvatarFallback">
                                    {{ $currentUser?->initials ?: 'U' }}
                                </div>
                            @endif
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-0"
                            style="width: 280px; border-radius: 12px; overflow: hidden;"
                            aria-labelledby="userDropdownBtn">
                            <!-- Dropdown Header -->
                            <div class="p-3 bg-light border-bottom d-flex align-items-center gap-3">
                                @if ($currentUser?->profile_photo_url)
                                    <img src="{{ $currentUser->profile_photo_url }}" alt="{{ $currentUser->name }}"
                                        class="rounded-circle object-fit-cover shadow-xs border"
                                        style="width: 44px; height: 44px;" id="dropdownUserAvatarImg">
                                @else
                                    <div class="user-avatar rounded-circle d-flex align-items-center justify-content-center shadow-xs fw-bold text-gold"
                                        style="width: 44px; height: 44px; background-color: var(--navy-900, #0a192f); color: var(--gold-500, #d4af37);"
                                        id="dropdownUserAvatarFallback">
                                        {{ $currentUser?->initials ?: 'U' }}
                                    </div>
                                @endif
                                <div class="overflow-hidden">
                                    <h6 class="fw-bold mb-0 text-navy-900 text-truncate">{{ $currentUser?->name }}</h6>
                                    <div class="small text-muted text-truncate font-mono" style="font-size: 0.75rem;">{{ $currentUser?->email }}</div>
                                    <span class="badge bg-secondary font-mono mt-1 text-uppercase" style="font-size: 0.65rem;">
                                        {{ str_replace('_', ' ', $currentUser?->role ?: 'user') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Actions Menu -->
                            <div class="p-2">
                                <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2 rounded-2 small fw-semibold"
                                    data-bs-toggle="modal" data-bs-target="#avatarUpdateModal">
                                    <i class="bi bi-camera-fill text-warning fs-6"></i>
                                    <span>Update Profile Photo</span>
                                </button>
                                <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2 rounded-2 small fw-semibold"
                                    data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    <i class="bi bi-key-fill text-primary fs-6"></i>
                                    <span>Change Password</span>
                                </button>
                            </div>

                            <div class="border-top p-2 bg-light">
                                <form method="POST" action="{{ route('logout') }}" id="headerLogoutForm">
                                    @csrf
                                    <button type="submit"
                                        class="dropdown-item d-flex align-items-center gap-2 py-2 rounded-2 small fw-semibold text-danger">
                                        <i class="bi bi-box-arrow-right fs-6"></i>
                                        <span>Sign Out</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
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

    <!-- Modal: Update Profile Photo -->
    <div class="modal fade" id="avatarUpdateModal" tabindex="-1" aria-labelledby="avatarUpdateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-light border-bottom p-3">
                    <h5 class="modal-title fw-bold text-navy-900 fs-6" id="avatarUpdateModalLabel">
                        <i class="bi bi-camera-fill text-warning me-2"></i> Update Profile Picture
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="avatarUploadForm" method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body p-4 text-center">
                        <div class="position-relative d-inline-block mb-3">
                            @if ($currentUser?->profile_photo_url)
                                <img src="{{ $currentUser->profile_photo_url }}" id="avatarPreviewImg"
                                    class="rounded-circle object-fit-cover shadow border border-3 border-warning"
                                    style="width: 100px; height: 100px;" alt="Avatar Preview">
                                <div id="avatarPreviewFallback"
                                    class="user-avatar rounded-circle d-none align-items-center justify-content-center shadow border border-3 border-warning fw-bold text-gold mx-auto"
                                    style="width: 100px; height: 100px; font-size: 2rem; background-color: var(--navy-900, #0a192f);">
                                    {{ $currentUser?->initials ?: 'U' }}
                                </div>
                            @else
                                <div id="avatarPreviewFallback"
                                    class="user-avatar rounded-circle d-flex align-items-center justify-content-center shadow border border-3 border-warning fw-bold text-gold mx-auto"
                                    style="width: 100px; height: 100px; font-size: 2rem; background-color: var(--navy-900, #0a192f);">
                                    {{ $currentUser?->initials ?: 'U' }}
                                </div>
                                <img src="" id="avatarPreviewImg" class="rounded-circle object-fit-cover shadow border border-3 border-warning d-none"
                                    style="width: 100px; height: 100px;" alt="Avatar Preview">
                            @endif
                        </div>

                        <div class="mb-3 text-start">
                            <label for="avatarFileInput" class="form-label small fw-bold text-navy-900">Choose Image File</label>
                            <input class="form-control form-control-sm" type="file" id="avatarFileInput" name="avatar" accept="image/jpeg,image/png,image/jpg,image/webp" required>
                            <div class="form-text small" style="font-size: 0.75rem;">Supported formats: JPG, PNG, WEBP. Max size: 5MB.</div>
                        </div>

                        <div id="avatarUploadAlert" class="alert alert-danger py-2 small d-none text-start mb-0"></div>
                    </div>
                    <div class="modal-footer bg-light p-3 d-flex justify-content-between">
                        @if ($currentUser?->profile_photo)
                            <button type="button" class="btn btn-sm btn-outline-danger" id="btnRemoveAvatar">
                                <i class="bi bi-trash"></i> Remove
                            </button>
                        @else
                            <div></div>
                        @endif
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-subtle" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-sm btn-gold fw-semibold" id="btnSubmitAvatar">
                                <i class="bi bi-upload me-1"></i> Save Photo
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Change Password -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-light border-bottom p-3">
                    <h5 class="modal-title fw-bold text-navy-900 fs-6" id="changePasswordModalLabel">
                        <i class="bi bi-shield-lock-fill text-primary me-2"></i> Change Account Password
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="headerChangePasswordForm" method="POST" action="{{ route('profile.password.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <div id="changePasswordAlert" class="alert alert-danger py-2 small d-none mb-3"></div>

                        <div class="mb-3">
                            <label for="headerCurrentPassword" class="form-label small fw-bold text-navy-900">Current Password</label>
                            <input type="password" class="form-control form-control-sm" id="headerCurrentPassword" name="current_password" required autocomplete="current-password">
                        </div>

                        <div class="mb-3">
                            <label for="headerNewPassword" class="form-label small fw-bold text-navy-900">New Password</label>
                            <input type="password" class="form-control form-control-sm" id="headerNewPassword" name="password" required autocomplete="new-password">
                            <div class="form-text small" style="font-size: 0.72rem;">Minimum 8 characters with mixed case letters and numbers.</div>
                        </div>

                        <div class="mb-3">
                            <label for="headerConfirmPassword" class="form-label small fw-bold text-navy-900">Confirm New Password</label>
                            <input type="password" class="form-control form-control-sm" id="headerConfirmPassword" name="password_confirmation" required autocomplete="new-password">
                        </div>
                    </div>
                    <div class="modal-footer bg-light p-3">
                        <button type="button" class="btn btn-sm btn-subtle" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-navy fw-semibold" id="btnSubmitPasswordChange">
                            <i class="bi bi-check-lg me-1"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
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

@extends('layouts.app')

@section('title', 'User & Staff Directory | ' . (getenv('APP_NAME') ?: 'Headworld'))

@section('content')
    <div class="page-content">
        <!-- Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h4 class="fw-bold mb-1 text-navy-900">Campus Identity & Personnel Directory</h4>
                <p class="text-muted small mb-0">Manage campus staff, maintenance technicians, dispatchers, and
                    administrators</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-gold btn-sm d-inline-flex align-items-center gap-1"
                    data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus-fill"></i> Provision Personnel
                </button>
            </div>
        </div>

        <!-- Feedback Alerts -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle-fill me-1"></i> Please resolve the following
                    errors:</div>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Metrics Row -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card-modern stat-card stat-gold p-3">
                    <div class="stat-card-header mb-1">
                        <span class="stat-card-label">Total Accounts</span>
                        <div class="stat-card-icon stat-icon-gold">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                    <div class="stat-card-value fs-3">{{ $totalUsers }}</div>
                    <small class="text-muted">Registered campus directory</small>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card-modern stat-card stat-emerald p-3">
                    <div class="stat-card-header mb-1">
                        <span class="stat-card-label">Active Accounts</span>
                        <div class="stat-card-icon stat-icon-emerald">
                            <i class="bi bi-person-check-fill"></i>
                        </div>
                    </div>
                    <div class="stat-card-value fs-3">{{ $totalActive }}</div>
                    <small class="text-success"><i class="bi bi-shield-check"></i> Verified and enabled</small>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card-modern stat-card stat-amber p-3">
                    <div class="stat-card-header mb-1">
                        <span class="stat-card-label">Maintenance Officers</span>
                        <div class="stat-card-icon stat-icon-amber">
                            <i class="bi bi-wrench-adjustable-circle"></i>
                        </div>
                    </div>
                    <div class="stat-card-value fs-3">{{ $totalOfficers }}</div>
                    <small class="text-muted">Field technical dispatch</small>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card-modern stat-card stat-rose p-3">
                    <div class="stat-card-header mb-1">
                        <span class="stat-card-label">Operations Admins</span>
                        <div class="stat-card-icon stat-icon-rose">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                    </div>
                    <div class="stat-card-value fs-3">{{ $totalAdmins }}</div>
                    <small class="text-muted">Super & campus admins</small>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card-modern p-3 mb-4">
            <form id="searchRecords" method="GET" action="{{ route('admin.users.index') }}"
                class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="q" class="form-control-headworld border-start-0"
                            placeholder="Search by name, email, mobile, or slug..." value="{{ $search }}">
                    </div>
                </div>

                <div class="col-sm-6 col-md-3">
                    <select name="role" class="form-select-headworld"
                        onchange="document.getElementById('searchRecords').submit()">
                        <option value="all" {{ $role === 'all' ? 'selected' : '' }}>All Roles</option>
                        <option value="super_admin" {{ $role === 'super_admin' ? 'selected' : '' }}>Super Administrators
                        </option>
                        <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Campus Administrators</option>
                        <option value="officer" {{ $role === 'officer' ? 'selected' : '' }}>Maintenance Officers</option>
                        <option value="data_entry" {{ $role === 'data_entry' ? 'selected' : '' }}>Data Entry Officers</option>
                        <option value="user" {{ $role === 'user' ? 'selected' : '' }}>Students & Staff</option>
                    </select>
                </div>

                <div class="col-sm-6 col-md-2">
                    <select name="status" class="form-select-headworld"
                        onchange="document.getElementById('searchRecords').submit()">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="banned" {{ $status === 'banned' ? 'selected' : '' }}>Suspended</option>
                        <option value="unverified" {{ $status === 'unverified' ? 'selected' : '' }}>Unverified</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex justify-content-md-end gap-2">
                    <button type="submit" form="searchRecords" class="btn btn-navy btn-sm px-3">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    @if($search || $role !== 'all' || $status !== 'all')
                        <a href="{{ route('admin.users.index') }}" class="btn btn-subtle btn-sm px-2">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- User Directory Table -->
        <div class="card-modern">
            <div class="card-body-headworld p-0">
                <div class="table-responsive-wrapper border-0">
                    <table class="table-headworld">
                        <thead>
                            <tr>
                                <th>Personnel</th>
                                <th>Role Authority</th>
                                <th>Contact Phone</th>
                                <th>Status</th>
                                <th>Date Joined</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $u)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="user-avatar bg-navy-900 text-gold fw-bold rounded-circle d-flex align-items-center justify-content-center"
                                                style="width: 36px; height: 36px; font-size: 0.85rem; background-color: var(--navy-900, #0a192f); color: var(--gold-500, #d4af37);">
                                                {{ $u->initials }}
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-navy-900">{{ $u->name }}</div>
                                                <div class="small text-muted font-mono" style="font-size:0.75rem;">
                                                    {{ $u->email }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($u->role === 'super_admin')
                                            <span class="badge bg-danger bg-opacity-25 text-danger"><i
                                                    class="bi bi-shield-shaded me-1"></i> Super Admin</span>
                                        @elseif($u->role === 'admin')
                                            <span class="badge bg-warning bg-opacity-25 text-warning"><i
                                                    class="bi bi-shield me-1"></i> Campus Admin</span>
                                        @elseif($u->role === 'officer')
                                            <span class="badge bg-info bg-opacity-25 text-info"><i class="bi bi-wrench me-1"></i>
                                                Facility Officer</span>
                                        @elseif($u->role === 'data_entry')
                                            <span class="badge bg-primary bg-opacity-25 text-primary"><i
                                                    class="bi bi-pencil-square me-1"></i> Data Entry</span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-25 text-secondary"><i
                                                    class="bi bi-person me-1"></i> Student / Staff</span>
                                        @endif
                                    </td>
                                    <td class="small font-mono text-muted">
                                        {{ $u->mobile ?: 'Not registered' }}
                                    </td>
                                    <td>
                                        @if($u->status === 'active')
                                            <span class="badge bg-success bg-opacity-25 text-success">
                                                <i class="bi bi-check-circle me-1"></i> Active
                                            </span>
                                        @elseif($u->status === 'banned')
                                            <span class="badge bg-danger bg-opacity-25 text-danger">
                                                <i class="bi bi-slash-circle me-1"></i> Suspended
                                            </span>
                                        @else
                                            <span class="badge bg-warning bg-opacity-25 text-warning">
                                                <i class="bi bi-clock-history me-1"></i> Unverified
                                            </span>
                                        @endif
                                    </td>
                                    <td class="small text-muted font-mono">
                                        {{ $u->created_at ? $u->created_at->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1 align-items-center">
                                            <!-- Edit Modal Trigger -->
                                            <button type="button" class="btn btn-sm btn-subtle py-1 px-2 btn-edit-user"
                                                data-id="{{ $u->id }}" data-slug="{{ $u->slug }}"
                                                data-first-name="{{ $u->first_name }}" data-last-name="{{ $u->last_name }}"
                                                data-email="{{ $u->email }}" data-mobile="{{ $u->mobile }}"
                                                data-role="{{ $u->role }}" data-status="{{ $u->status }}" title="Edit User">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>

                                            <!-- Suspend / Activate Toggle -->
                                            @if(auth()->id() !== $u->id && $u->role !== 'super_admin')
                                                <form id="setStatus_{{ $u->id }}" method="POST"
                                                    action="{{ route('admin.users.toggle', $u) }}" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" form="setStatus_{{ $u->id }}"
                                                        class="btn btn-sm py-1 px-2 {{ $u->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                        title="{{ $u->status === 'active' ? 'Suspend User' : 'Activate User' }}">
                                                        <i
                                                            class="bi {{ $u->status === 'active' ? 'bi-shield-slash' : 'bi-shield-check' }}"></i>
                                                        {{ $u->status === 'active' ? 'Suspend' : 'Activate' }}
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-person-x fs-1 d-block mb-2"></i>
                                        No personnel accounts found matching your query.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Server-Side Pagination Bar -->
                @if($users->hasPages())
                    <div class="p-3 border-top d-flex justify-content-between align-items-center">
                        <span class="small text-muted">
                            Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} accounts
                        </span>
                        <div>
                            {{ $users->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal: Provision Backend User -->
    <div class="modal fade modal-headworld" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="addRecordForm" method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-navy-900">
                            <i class="bi bi-person-plus-fill text-warning me-2"></i> Provision Campus Personnel
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label-headworld">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control-headworld"
                                    placeholder="e.g. Marcus" required value="{{ old('first_name') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-headworld">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control-headworld" placeholder="e.g. Vance"
                                    required value="{{ old('last_name') }}">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label-headworld">University Email <span
                                        class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control-headworld"
                                    placeholder="e.g. m.vance@unilag.edu" required value="{{ old('email') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-headworld">Contact Phone Number</label>
                                <input type="tel" name="mobile" class="form-control-headworld"
                                    placeholder="e.g. +1 (555) 019-2834" value="{{ old('mobile') }}">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label-headworld">System Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-select-headworld" required>
                                    <option value="officer" {{ old('role') === 'officer' ? 'selected' : '' }}>Facility
                                        Maintenance Officer (Technician)</option>
                                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Campus Administrator
                                    </option>
                                    <option value="data_entry" {{ old('role') === 'data_entry' ? 'selected' : '' }}>Data Entry
                                        Officer</option>
                                    @if(auth()->user()?->role === 'super_admin')
                                        <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super
                                            Administrator</option>
                                    @endif
                                    <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>Standard User
                                        (Student/Staff)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-headworld">Account Initial Status <span
                                        class="text-danger">*</span></label>
                                <select name="status" class="form-select-headworld" required>
                                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active
                                        (Verified Immediately)</option>
                                    <option value="unverified" {{ old('status') === 'unverified' ? 'selected' : '' }}>
                                        Unverified (Requires verification)</option>
                                    <option value="banned" {{ old('status') === 'banned' ? 'selected' : '' }}>Suspended
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label-headworld mb-0">Initial Password <span
                                            class="text-danger">*</span></label>
                                    <button type="button" class="btn-generate-password" id="btnGenerateAddUserPassword"
                                        title="Generate a cryptographically strong random password">
                                        <i class="bi bi-magic text-warning"></i> Generate
                                    </button>
                                </div>
                                <div class="input-group">
                                    <input type="password" name="password" id="addUserPassword"
                                        class="form-control-headworld" placeholder="Min 8 chars, mixed case & numbers"
                                        required autocomplete="new-password">
                                    <button type="button" class="btn btn-password-addon" id="btnToggleAddUserPassword"
                                        aria-label="Toggle password visibility">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                </div>

                                <!-- Strength Meter -->
                                <div class="password-strength-container">
                                    <div class="password-strength-bar-wrapper">
                                        <div id="addUserStrengthBar" class="password-strength-bar"></div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small text-muted" style="font-size: 0.72rem;">Strength:</span>
                                        <span id="addUserStrengthText" class="small font-mono text-muted">No password</span>
                                    </div>
                                </div>

                                <!-- Requirements Box -->
                                <div class="password-requirements-box" id="addUserPasswordReqs">
                                    <div class="password-requirements-title">
                                        <i class="bi bi-shield-check text-warning"></i> Policy Requirements
                                    </div>
                                    <ul class="password-requirements-list">
                                        <li class="req-item unmet" data-rule="length"><i class="bi bi-circle"></i> 8+
                                            characters</li>
                                        <li class="req-item unmet" data-rule="upper"><i class="bi bi-circle"></i> Uppercase
                                            (A-Z)</li>
                                        <li class="req-item unmet" data-rule="lower"><i class="bi bi-circle"></i> Lowercase
                                            (a-z)</li>
                                        <li class="req-item unmet" data-rule="number"><i class="bi bi-circle"></i> Number
                                            (0-9)</li>
                                        <li class="req-item unmet" data-rule="symbol"><i class="bi bi-circle"></i> Special
                                            symbol</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-headworld mb-1">Confirm Password <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="password_confirmation" id="addUserConfirmPassword"
                                        class="form-control-headworld" placeholder="Repeat password" required
                                        autocomplete="new-password">
                                    <button type="button" class="btn btn-password-addon" id="btnToggleAddUserConfirm"
                                        aria-label="Toggle confirm password visibility">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                </div>
                                <div id="addUserMatchFeedback" class="password-match-box"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-subtle" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="addRecordForm" class="btn btn-gold">
                            <i class="bi bi-check-circle"></i> Provision Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Edit Personnel -->
    <div class="modal fade modal-headworld" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editUserForm" method="POST" action="javascript:void(0);">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-navy-900">
                            <i class="bi bi-pencil-square text-warning me-2"></i> Modify Personnel Profile
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label-headworld">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" id="editUserFirstName" class="form-control-headworld"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-headworld">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" id="editUserLastName" class="form-control-headworld"
                                    required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label-headworld">University Email <span
                                        class="text-danger">*</span></label>
                                <input type="email" name="email" id="editUserEmail" class="form-control-headworld" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-headworld">Contact Phone Number</label>
                                <input type="tel" name="mobile" id="editUserMobile" class="form-control-headworld">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label-headworld">System Role <span class="text-danger">*</span></label>
                                <select name="role" id="editUserRole" class="form-select-headworld" required>
                                    <option value="officer">Facility Maintenance Officer (Technician)</option>
                                    <option value="admin">Campus Administrator</option>
                                    <option value="data_entry">Data Entry Officer</option>
                                    @if(auth()->user()?->role === 'super_admin')
                                        <option value="super_admin">Super Administrator</option>
                                    @endif
                                    <option value="user">Standard User (Student/Staff)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-headworld">Status <span class="text-danger">*</span></label>
                                <select name="status" id="editUserStatus" class="form-select-headworld" required>
                                    <option value="active">Active</option>
                                    <option value="banned">Suspended</option>
                                    <option value="unverified">Unverified</option>
                                </select>
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded border mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label-headworld mb-0 fw-bold">Reset Password (Optional)</label>
                                <button type="button" class="btn-generate-password" id="btnGenerateEditUserPassword"
                                    title="Generate a cryptographically strong random password">
                                    <i class="bi bi-magic text-warning"></i> Generate
                                </button>
                            </div>
                            <small class="text-muted d-block mb-2">Leave password fields blank to keep the user's current
                                password unchanged.</small>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <input type="password" name="password" id="editUserPassword"
                                            class="form-control-headworld" placeholder="New password (optional)"
                                            autocomplete="new-password">
                                        <button type="button" class="btn btn-password-addon" id="btnToggleEditUserPassword"
                                            aria-label="Toggle password visibility">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                    </div>

                                    <!-- Strength Meter -->
                                    <div class="password-strength-container">
                                        <div class="password-strength-bar-wrapper">
                                            <div id="editUserStrengthBar" class="password-strength-bar"></div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="small text-muted" style="font-size: 0.72rem;">Strength:</span>
                                            <span id="editUserStrengthText" class="small font-mono text-muted">No
                                                password</span>
                                        </div>
                                    </div>

                                    <!-- Requirements Box -->
                                    <div class="password-requirements-box" id="editUserPasswordReqs">
                                        <div class="password-requirements-title">
                                            <i class="bi bi-shield-check text-warning"></i> Policy Requirements
                                        </div>
                                        <ul class="password-requirements-list">
                                            <li class="req-item unmet" data-rule="length"><i class="bi bi-circle"></i> 8+
                                                characters</li>
                                            <li class="req-item unmet" data-rule="upper"><i class="bi bi-circle"></i>
                                                Uppercase (A-Z)</li>
                                            <li class="req-item unmet" data-rule="lower"><i class="bi bi-circle"></i>
                                                Lowercase (a-z)</li>
                                            <li class="req-item unmet" data-rule="number"><i class="bi bi-circle"></i>
                                                Number (0-9)</li>
                                            <li class="req-item unmet" data-rule="symbol"><i class="bi bi-circle"></i>
                                                Special symbol</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-headworld mb-1">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" name="password_confirmation" id="editUserConfirmPassword"
                                            class="form-control-headworld" placeholder="Confirm new password"
                                            autocomplete="new-password">
                                        <button type="button" class="btn btn-password-addon" id="btnToggleEditUserConfirm"
                                            aria-label="Toggle confirm password visibility">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                    </div>
                                    <div id="editUserMatchFeedback" class="password-match-box"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-subtle" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="editUserForm" class="btn btn-navy">
                            <i class="bi bi-check2-circle"></i> Update Personnel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Clean up search parameters before submit to prevent empty keys (?q=&role=all) in the URL
            const searchForm = document.getElementById("searchRecords");
            if (searchForm) {
                searchForm.addEventListener("submit", function () {
                    const inputs = this.querySelectorAll("input[name], select[name]");
                    inputs.forEach(input => {
                        if (!input.value || input.value === "all") {
                            input.disabled = true;
                        }
                    });
                });
            }

            const editModalEl = document.getElementById("editUserModal");
            const editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;

            document.querySelectorAll(".btn-edit-user").forEach(btn => {
                btn.addEventListener("click", function () {
                    const slug = this.getAttribute("data-slug");
                    const firstName = this.getAttribute("data-first-name");
                    const lastName = this.getAttribute("data-last-name");
                    const email = this.getAttribute("data-email");
                    const mobile = this.getAttribute("data-mobile") || "";
                    const role = this.getAttribute("data-role");
                    const status = this.getAttribute("data-status");

                    const form = document.getElementById("editUserForm");
                    form.action = "{{ url('admin/users') }}/" + slug;

                    document.getElementById("editUserFirstName").value = firstName;
                    document.getElementById("editUserLastName").value = lastName;
                    document.getElementById("editUserEmail").value = email;
                    document.getElementById("editUserMobile").value = mobile;
                    document.getElementById("editUserRole").value = role;
                    document.getElementById("editUserStatus").value = status;

                    if (editModal) editModal.show();
                });
            });
        });
    </script>
@endpush
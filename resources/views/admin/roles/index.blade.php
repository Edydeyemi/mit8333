@extends('layouts.admin-layout')

@section('title', 'Roles & Permissions | ' . (getenv('APP_NAME') ?: 'Headworld'))

@section('content')
    <div class="page-content">
        <!-- Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h4 class="fw-bold mb-1 text-navy-900">Access Control & Role Permissions</h4>
                <p class="text-muted small mb-0">Configure operational authorities, assign granular capabilities, and manage
                    RBAC matrices</p>
            </div>
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('admin.permissions.sync') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-subtle btn-sm d-inline-flex align-items-center gap-1"
                        title="Synchronize permissions catalog from AppPermissions.php">
                        <i class="bi bi-arrow-repeat"></i> Sync Catalog
                    </button>
                </form>
                <button type="button" class="btn btn-gold btn-sm d-inline-flex align-items-center gap-2"
                    data-bs-toggle="modal" data-bs-target="#addRoleModal">
                    <i class="bi bi-shield-plus"></i> Define Role
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
            <div class="col-sm-4">
                <div class="card-modern stat-card stat-gold p-3">
                    <div class="stat-card-header mb-1">
                        <span class="stat-card-label">Defined System Roles</span>
                        <div class="stat-card-icon stat-icon-gold">
                            <i class="bi bi-person-badge-fill"></i>
                        </div>
                    </div>
                    <div class="stat-card-value fs-3">{{ $totalRoles }}</div>
                    <small class="text-muted">Core and operational roles</small>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card-modern stat-card stat-emerald p-3">
                    <div class="stat-card-header mb-1">
                        <span class="stat-card-label">Permissions in Catalog</span>
                        <div class="stat-card-icon stat-icon-emerald">
                            <i class="bi bi-key-fill"></i>
                        </div>
                    </div>
                    <div class="stat-card-value fs-3">{{ $totalPermissions }}</div>
                    <small class="text-success"><i class="bi bi-check2-circle"></i> Granular action policies</small>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card-modern stat-card stat-amber p-3">
                    <div class="stat-card-header mb-1">
                        <span class="stat-card-label">Super Admin Authority</span>
                        <div class="stat-card-icon stat-icon-amber">
                            <i class="bi bi-shield-shaded"></i>
                        </div>
                    </div>
                    <div class="stat-card-value fs-3">Omnipotent</div>
                    <small class="text-muted">Unrestricted global bypass</small>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card-modern p-3 mb-4">
            <form method="GET" action="{{ route('admin.roles.index') }}" class="row g-2 align-items-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="q" class="form-control-headworld border-start-0"
                            placeholder="Search roles by title or identifier slug..." value="{{ $search }}">
                    </div>
                </div>
                <div class="col-md-4 d-flex justify-content-md-end gap-2">
                    <button type="submit" class="btn btn-navy btn-sm px-3">
                        <i class="bi bi-search"></i> Search
                    </button>
                    @if($search)
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-subtle btn-sm px-3">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Roles Table -->
        <div class="card-modern">
            <div class="card-body-headworld p-0">
                <div class="table-responsive-wrapper border-0">
                    <table class="table-headworld">
                        <thead>
                            <tr>
                                <th>Role Title</th>
                                <th>Identifier Slug</th>
                                <th>Assigned Users</th>
                                <th>Active Permissions</th>
                                <th>Created / Managed By</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($roles as $role)
                                @php
                                    $isSystem = in_array($role->role_slug, ['super_admin', 'admin', 'officer', 'user', 'data_entry'], true);
                                    $rolePermNames = $role->permissions->pluck('name')->toArray();
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-navy-900 d-flex align-items-center gap-2">
                                            <i class="bi bi-shield-lock-fill text-warning"></i>
                                            {{ $role->name }}
                                            @if($isSystem)
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary"
                                                    style="font-size: 0.65rem;">System Core</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            class="badge bg-secondary text-navy-900 border font-mono">{{ $role->role_slug }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-25 text-primary">
                                            <i class="bi bi-people me-1"></i> {{ $role->users_count }} users
                                        </span>
                                    </td>
                                    <td>
                                        @if($role->role_slug === 'super_admin')
                                            <span class="badge bg-danger bg-opacity-25 text-danger">
                                                <i class="bi bi-infinity me-1"></i> Full Access (Omnipotent)
                                            </span>
                                        @else
                                            <span class="badge bg-info bg-opacity-25 text-navy-900">
                                                <i class="bi bi-key me-1"></i> {{ $role->permissions_count }} /
                                                {{ $totalPermissions }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="small text-muted font-mono">
                                        {{ $role->created_by ?? 'system' }}
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1 align-items-center">
                                            <!-- Edit / Assign Permissions Modal Trigger -->
                                            <button type="button" class="btn btn-sm btn-subtle py-1 px-2 btn-edit-role"
                                                data-id="{{ $role->id }}" data-slug="{{ $role->role_slug }}"
                                                data-name="{{ $role->name }}" data-permissions='@json($rolePermNames)'
                                                data-is-super="{{ $role->role_slug === 'super_admin' ? '1' : '0' }}"
                                                title="Assign Permissions & Edit Role">
                                                <i class="bi bi-sliders"></i> Permissions
                                            </button>

                                            <!-- Delete Custom Role -->
                                            @if(!$isSystem)
                                                <form method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to delete role: {{ $role->name }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-subtle text-danger py-1 px-2"
                                                        title="Delete Role">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-shield-x fs-1 d-block mb-2"></i>
                                        No roles found matching your query.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Server-Side Pagination -->
                @if($roles->hasPages())
                    <div class="p-3 border-top d-flex justify-content-between align-items-center">
                        <span class="small text-muted">
                            Showing {{ $roles->firstItem() }} to {{ $roles->lastItem() }} of {{ $roles->total() }} roles
                        </span>
                        <div>
                            {{ $roles->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal: Define New Role -->
    <div class="modal fade modal-headworld" id="addRoleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.roles.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-navy-900">
                            <i class="bi bi-shield-plus text-warning me-2"></i> Define New System Role
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label-headworld">Role Title <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control-headworld"
                                    placeholder="e.g. Electrical Maintenance Supervisor" required value="{{ old('name') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-headworld">Identifier Slug</label>
                                <input type="text" name="role_slug" class="form-control-headworld"
                                    placeholder="e.g. electrical_supervisor" value="{{ old('role_slug') }}">
                                <small class="text-muted" style="font-size: 0.75rem;">Leave empty to auto-generate from
                                    title</small>
                            </div>
                        </div>

                        <h6 class="fw-bold text-navy-900 mb-2 d-flex justify-content-between align-items-center">
                            <span>Assign Permissions</span>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-xs btn-outline-secondary"
                                    onclick="toggleAllCheckboxes('addRoleModal', true)">Select All</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary"
                                    onclick="toggleAllCheckboxes('addRoleModal', false)">Deselect All</button>
                            </div>
                        </h6>

                        <div class="border rounded p-3 bg-light" style="max-height: 380px; overflow-y: auto;">
                            @foreach($groupedPermissions as $groupName => $permissions)
                                <div class="mb-3">
                                    <div
                                        class="fw-semibold text-navy-900 mb-2 border-bottom pb-1 d-flex justify-content-between align-items-center">
                                        <span><i class="bi bi-folder2-open text-warning me-1"></i> {{ $groupName }}
                                            Policies</span>
                                        <span class="small text-muted">{{ count($permissions) }} abilities</span>
                                    </div>
                                    <div class="row g-2">
                                        @foreach($permissions as $perm)
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input perm-checkbox-add" type="checkbox"
                                                        name="permissions[]" value="{{ $perm->name }}"
                                                        id="perm_add_{{ $perm->id }}">
                                                    <label class="form-check-label small" for="perm_add_{{ $perm->id }}">
                                                        <strong class="font-mono"
                                                            style="font-size: 0.78rem;">{{ $perm->name }}</strong>
                                                        <div class="text-muted" style="font-size: 0.72rem;">
                                                            {{ $perm->description ?: 'Operational capability' }}
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-subtle" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-gold">
                            <i class="bi bi-check-circle"></i> Create & Assign Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Edit Role & Assign Permissions -->
    <div class="modal fade modal-headworld" id="editRoleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editRoleForm" method="POST" action="javascript:void(0);">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-navy-900">
                            <i class="bi bi-sliders text-warning me-2"></i> Role Permissions Assignment
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label-headworld">Role Title <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="editRoleName" class="form-control-headworld" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-headworld">Identifier Slug <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="role_slug" id="editRoleSlug" class="form-control-headworld"
                                    required>
                            </div>
                        </div>

                        <div id="superAdminNotice" class="alert alert-warning border-0 small d-none mb-3">
                            <i class="bi bi-shield-exclamation me-1"></i> The Super Administrator role holds inherent
                            omnipotent access to all capabilities.
                        </div>

                        <h6 class="fw-bold text-navy-900 mb-2 d-flex justify-content-between align-items-center">
                            <span>Assigned Permissions</span>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-xs btn-outline-secondary"
                                    onclick="toggleAllCheckboxes('editRoleModal', true)">Select All</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary"
                                    onclick="toggleAllCheckboxes('editRoleModal', false)">Deselect All</button>
                            </div>
                        </h6>

                        <div class="border rounded p-3 bg-light" style="max-height: 380px; overflow-y: auto;">
                            @foreach($groupedPermissions as $groupName => $permissions)
                                <div class="mb-3">
                                    <div
                                        class="fw-semibold text-navy-900 mb-2 border-bottom pb-1 d-flex justify-content-between align-items-center">
                                        <span><i class="bi bi-folder2-open text-warning me-1"></i> {{ $groupName }}
                                            Policies</span>
                                        <span class="small text-muted">{{ count($permissions) }} abilities</span>
                                    </div>
                                    <div class="row g-2">
                                        @foreach($permissions as $perm)
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input perm-checkbox-edit" type="checkbox"
                                                        name="permissions[]" value="{{ $perm->name }}"
                                                        id="perm_edit_{{ $perm->id }}">
                                                    <label class="form-check-label small" for="perm_edit_{{ $perm->id }}">
                                                        <strong class="font-mono"
                                                            style="font-size: 0.78rem;">{{ $perm->name }}</strong>
                                                        <div class="text-muted" style="font-size: 0.72rem;">
                                                            {{ $perm->description ?: 'Operational capability' }}
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-subtle" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-navy">
                            <i class="bi bi-check2-circle"></i> Save Permission Assignments
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function toggleAllCheckboxes(modalId, selectAll) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            const checkboxes = modal.querySelectorAll('input[type="checkbox"][name="permissions[]"]');
            checkboxes.forEach(cb => cb.checked = selectAll);
        }

        document.addEventListener("DOMContentLoaded", function () {
            // Clean up search parameters before submit
            const searchForm = document.querySelector('form[action="{{ route("admin.roles.index") }}"]');
            if (searchForm) {
                searchForm.addEventListener("submit", function () {
                    const inputs = this.querySelectorAll("input[name], select[name]");
                    inputs.forEach(input => {
                        if (!input.value) {
                            input.disabled = true;
                        }
                    });
                });
            }

            const editModalEl = document.getElementById("editRoleModal");
            const editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;

            document.querySelectorAll(".btn-edit-role").forEach(btn => {
                btn.addEventListener("click", function () {
                    const roleSlug = this.getAttribute("data-slug");
                    const roleName = this.getAttribute("data-name");
                    const isSuper = this.getAttribute("data-is-super") === "1";
                    let assigned = [];

                    try {
                        assigned = JSON.parse(this.getAttribute("data-permissions") || "[]");
                    } catch (e) {
                        assigned = [];
                    }

                    const form = document.getElementById("editRoleForm");
                    form.action = "{{ url('admin/roles') }}/" + roleSlug;

                    document.getElementById("editRoleName").value = roleName;
                    const slugInput = document.getElementById("editRoleSlug");
                    slugInput.value = roleSlug;

                    const notice = document.getElementById("superAdminNotice");
                    if (isSuper) {
                        notice.classList.remove("d-none");
                    } else {
                        notice.classList.add("d-none");
                    }

                    // Sync checkboxes
                    document.querySelectorAll(".perm-checkbox-edit").forEach(cb => {
                        cb.checked = assigned.includes(cb.value);
                    });

                    if (editModal) editModal.show();
                });
            });
        });
    </script>
@endpush
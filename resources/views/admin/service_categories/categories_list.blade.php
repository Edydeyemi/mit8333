@extends('layouts.app')

@section('title', 'Service Categories | ' . (getenv('APP_NAME') ?: 'Headworld'))

@section('content')
    <div class="page-content">
        <!-- Breadcrumb & Header Bar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h4 class="fw-bold mb-1 text-navy-900">Campus Facility Service Categories</h4>
                <p class="text-muted small mb-0">Define, organize, and manage maintenance disciplines and incident
                    classifications</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-gold btn-sm d-inline-flex align-items-center gap-2"
                    data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="bi bi-plus-circle-fill"></i> New Service Category
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

        <!-- Metric Summary Row -->
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="card-modern stat-card stat-gold p-3">
                    <div class="stat-card-header mb-1">
                        <span class="stat-card-label">Total Categories</span>
                        <div class="stat-card-icon stat-icon-gold">
                            <i class="bi bi-collection-fill"></i>
                        </div>
                    </div>
                    <div class="stat-card-value fs-3">{{ $totalCount }}</div>
                    <small class="text-muted">Master maintenance classifications</small>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card-modern stat-card stat-emerald p-3">
                    <div class="stat-card-header mb-1">
                        <span class="stat-card-label">Active Disciplines</span>
                        <div class="stat-card-icon stat-icon-emerald">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                    </div>
                    <div class="stat-card-value fs-3">{{ $activeCount }}</div>
                    <small class="text-success"><i class="bi bi-arrow-up-right"></i> Available for campus lodging</small>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card-modern stat-card stat-rose p-3">
                    <div class="stat-card-header mb-1">
                        <span class="stat-card-label">Deactivated / Inactive</span>
                        <div class="stat-card-icon stat-icon-rose">
                            <i class="bi bi-slash-circle-fill"></i>
                        </div>
                    </div>
                    <div class="stat-card-value fs-3">{{ $inactiveCount }}</div>
                    <small class="text-muted">Temporarily archived</small>
                </div>
            </div>
        </div>

        <!-- Search & Filter Controls -->
        <div class="card-modern p-3 mb-4">
            <form method="GET" action="{{ route('admin.categories.index') }}" class="row g-2 align-items-center">
                <div class="col-md-6 col-lg-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="q" class="form-control-headworld border-start-0"
                            placeholder="Search categories by name, slug or keyword..." value="{{ $search }}">
                    </div>
                </div>

                <div class="col-sm-6 col-md-3 col-lg-3">
                    <select name="status" class="form-select-headworld" onchange="this.form.submit()">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses ({{ $totalCount }})
                        </option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active Only ({{ $activeCount }})
                        </option>
                        <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive Only
                            ({{ $inactiveCount }})</option>
                    </select>
                </div>

                <div class="col-sm-6 col-md-3 col-lg-4 d-flex justify-content-md-end gap-2">
                    <button type="submit" class="btn btn-navy btn-sm px-3">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    @if($search || $status !== 'all')
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-subtle btn-sm px-3">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Categories Data Table -->
        <div class="card-modern">
            <div class="card-body-headworld p-0">
                <div class="table-responsive-wrapper border-0">
                    <table class="table-headworld">
                        <thead>
                            <tr>
                                <th style="width: 50px;">Icon</th>
                                <th>Category Name & Slug</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $cat)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-center rounded-3 bg-light text-navy-900 border"
                                            style="width: 40px; height: 40px; font-size: 1.25rem;">
                                            <i class="bi {{ $cat->icon ?: 'bi-tools' }}"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-navy-900">{{ $cat->name }}</div>
                                        <span class="badge bg-secondary text-muted font-mono border"
                                            style="font-size: 0.72rem;">{{ $cat->slug }}</span>
                                    </td>
                                    <td class="text-muted small" style="max-width: 320px;">
                                        {{ Str::limit($cat->description ?: 'No detailed scope recorded.', 90) }}
                                    </td>
                                    <td>
                                        @if($cat->is_active)
                                            <span class="badge bg-success bg-opacity-25 text-success">
                                                <i class="bi bi-check-circle me-1"></i> Active
                                            </span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-25 text-secondary">
                                                <i class="bi bi-slash-circle me-1"></i> Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td class="small text-muted font-mono">
                                        {{ $cat->created_at ? $cat->created_at->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1 align-items-center">
                                            <!-- Edit Modal Trigger -->
                                            <button type="button" class="btn btn-sm btn-subtle py-1 px-2 btn-edit-category"
                                                data-id="{{ $cat->id }}" data-slug="{{ $cat->slug }}"
                                                data-name="{{ $cat->name }}" data-icon="{{ $cat->icon }}"
                                                data-description="{{ $cat->description }}"
                                                data-active="{{ $cat->is_active ? '1' : '0' }}" title="Edit Category">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>

                                            <!-- Toggle Status Form -->
                                            <form method="POST" action="{{ route('admin.categories.toggle', $cat) }}"
                                                class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                    class="btn btn-sm py-1 px-2 {{ $cat->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                    title="{{ $cat->is_active ? 'Deactivate Category' : 'Activate Category' }}">
                                                    <i class="bi {{ $cat->is_active ? 'bi-pause-fill' : 'bi-play-fill' }}"></i>
                                                    {{ $cat->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>

                                            <!-- Delete Form -->
                                            <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}"
                                                class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this category?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-subtle text-danger py-1 px-2"
                                                    title="Delete Category">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        No service categories found matching your query.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Server-Side Pagination Bar -->
                @if($categories->hasPages())
                    <div class="p-3 border-top d-flex justify-content-between align-items-center">
                        <span class="small text-muted">
                            Showing {{ $categories->firstItem() }} to {{ $categories->lastItem() }} of
                            {{ $categories->total() }} results
                        </span>
                        <div>
                            {{ $categories->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal: Add Service Category -->
    <div class="modal fade modal-headworld" id="addCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.categories.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-navy-900">
                            <i class="bi bi-folder-plus text-warning me-2"></i> Create Service Category
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label-headworld">Category Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control-headworld"
                                placeholder="e.g. Electrical & Lighting Maintenance" required value="{{ old('name') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label-headworld">Icon Class (Bootstrap Icons)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white" id="iconPreviewAdd"><i
                                        class="bi bi-tools"></i></span>
                                <input type="text" name="icon" id="iconInputAdd" class="form-control-headworld"
                                    placeholder="bi-lightning-charge" value="{{ old('icon', 'bi-tools') }}">
                            </div>
                            <div class="mt-2 d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-xs btn-outline-secondary btn-icon-preset"
                                    data-icon="bi-lightning-charge"><i class="bi bi-lightning-charge"></i>
                                    Electricity</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary btn-icon-preset"
                                    data-icon="bi-droplet-half"><i class="bi bi-droplet-half"></i> Plumbing</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary btn-icon-preset"
                                    data-icon="bi-wifi"><i class="bi bi-wifi"></i> Network/IT</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary btn-icon-preset"
                                    data-icon="bi-door-closed"><i class="bi bi-door-closed"></i>
                                    Furniture/Carpentry</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary btn-icon-preset"
                                    data-icon="bi-buildings"><i class="bi bi-buildings"></i> Hostel Facilities</button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-headworld">Scope & Description</label>
                            <textarea name="description" class="form-control-headworld" rows="3"
                                placeholder="Outline the types of repairs, equipment, or campus systems covered under this category...">{{ old('description') }}</textarea>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1"
                                id="isActiveSwitchAdd" checked>
                            <label class="form-check-label" for="isActiveSwitchAdd">Mark as Active immediately</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-subtle" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-gold">
                            <i class="bi bi-plus-circle"></i> Create Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Edit Service Category -->
    <div class="modal fade modal-headworld" id="editCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editCategoryForm" method="POST" action="javascript:void(0);">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-navy-900">
                            <i class="bi bi-pencil-square text-warning me-2"></i> Modify Service Category
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label-headworld">Category Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="editCategoryName" class="form-control-headworld" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-headworld">Icon Class (Bootstrap Icons)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white" id="iconPreviewEdit"><i
                                        class="bi bi-tools"></i></span>
                                <input type="text" name="icon" id="editCategoryIcon" class="form-control-headworld">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-headworld">Scope & Description</label>
                            <textarea name="description" id="editCategoryDescription" class="form-control-headworld"
                                rows="3"></textarea>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1"
                                id="editCategoryActive">
                            <label class="form-check-label" for="editCategoryActive">Active Category</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-subtle" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-navy">
                            <i class="bi bi-check2-circle"></i> Save Changes
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
            // Clean up search parameters before submit
            const searchForm = document.querySelector('form[action="{{ route("admin.categories.index") }}"]');
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

            // Icon preset buttons helper
            document.querySelectorAll(".btn-icon-preset").forEach(btn => {
                btn.addEventListener("click", function () {
                    const icon = this.getAttribute("data-icon");
                    const input = document.getElementById("iconInputAdd");
                    input.value = icon;
                    const preview = document.getElementById("iconPreviewAdd");
                    if (preview) preview.innerHTML = `<i class="bi ${icon}"></i>`;
                });
            });

            const addIconInput = document.getElementById("iconInputAdd");
            if (addIconInput) {
                addIconInput.addEventListener("input", function () {
                    const preview = document.getElementById("iconPreviewAdd");
                    if (preview) preview.innerHTML = `<i class="bi ${this.value || 'bi-tools'}"></i>`;
                });
            }

            const editIconInput = document.getElementById("editCategoryIcon");
            if (editIconInput) {
                editIconInput.addEventListener("input", function () {
                    const preview = document.getElementById("iconPreviewEdit");
                    if (preview) preview.innerHTML = `<i class="bi ${this.value || 'bi-tools'}"></i>`;
                });
            }

            // Dynamic Edit Category Modal Population
            const editModalEl = document.getElementById("editCategoryModal");
            const editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;

            document.querySelectorAll(".btn-edit-category").forEach(btn => {
                btn.addEventListener("click", function () {
                    const slug = this.getAttribute("data-slug");
                    const name = this.getAttribute("data-name");
                    const icon = this.getAttribute("data-icon") || "bi-tools";
                    const description = this.getAttribute("data-description") || "";
                    const active = this.getAttribute("data-active") === "1";

                    const form = document.getElementById("editCategoryForm");
                    form.action = "{{ url('admin/service-categories') }}/" + slug;

                    document.getElementById("editCategoryName").value = name;
                    document.getElementById("editCategoryIcon").value = icon;
                    document.getElementById("editCategoryDescription").value = description;
                    document.getElementById("editCategoryActive").checked = active;

                    const preview = document.getElementById("iconPreviewEdit");
                    if (preview) preview.innerHTML = `<i class="bi ${icon}"></i>`;

                    if (editModal) editModal.show();
                });
            });
        });
    </script>
@endpush
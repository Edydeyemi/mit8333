@extends('layouts.app')

@section('title', 'Edit Service Request | ' . (getenv('APP_NAME') ?: 'Headworld'))

@section('header_title')
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('user.requests.show', $serviceRequest->slug) }}" class="btn btn-sm btn-subtle" title="Back to details">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h5 class="fw-bold mb-0 text-navy-900">Modify Request: {{ $serviceRequest->tracking_code }}</h5>
            <small class="text-muted">Update details prior to technician dispatch</small>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-content">
        <div class="row justify-content-center">
            <div class="col-xl-9 col-lg-11">
                <div class="card-modern p-4 p-md-5">
                    <div class="mb-4 pb-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <span class="badge bg-warning text-dark fw-bold mb-2">Editable (Submitted Status)</span>
                            <h3 class="fw-bold text-navy-900 mb-1">Update Maintenance Incident</h3>
                            <p class="text-muted small mb-0">Modify information before administrative triage and officer assignment.</p>
                        </div>
                        <div>
                            <span class="badge {{ $serviceRequest->status_badge_class }} fs-6 px-3 py-2">
                                <i class="bi bi-clock-history me-1"></i> {{ strtoupper($serviceRequest->status) }}
                            </span>
                        </div>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi bi-exclamation-triangle-fill fs-5 mt-1"></i>
                                <div>
                                    <strong class="d-block mb-1">Please correct the following errors:</strong>
                                    <ul class="mb-0 ps-3 small">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form id="submitComplaintForm" action="{{ route('user.requests.update', $serviceRequest->slug) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <!-- 1. Category Selection -->
                            <div class="mb-4 col-md-6">
                                <label for="categorySelect" class="form-label-headworld fs-6 mb-2">
                                    1. Category <span class="text-danger">*</span>
                                </label>
                                <select name="service_category_id" id="categorySelect" class="form-select-headworld" required>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ old('service_category_id', $serviceRequest->service_category_id) == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 2. Priority Selection -->
                            <div class="mb-4 col-md-6">
                                <label for="prioritySelect" class="form-label-headworld fs-6 mb-2">
                                    2. Urgency & Priority Level <span class="text-danger">*</span>
                                </label>
                                <select name="priority" id="prioritySelect" class="form-select-headworld" required>
                                    <option value="low" {{ old('priority', $serviceRequest->priority) === 'low' ? 'selected' : '' }}>Low (72h SLA)</option>
                                    <option value="medium" {{ old('priority', $serviceRequest->priority) === 'medium' ? 'selected' : '' }}>Medium (24h Standard SLA)</option>
                                    <option value="high" {{ old('priority', $serviceRequest->priority) === 'high' ? 'selected' : '' }}>High (12h Priority SLA)</option>
                                    <option value="emergency" {{ old('priority', $serviceRequest->priority) === 'emergency' ? 'selected' : '' }}>Emergency (4h Critical SLA)</option>
                                </select>
                            </div>
                        </div>

                        <!-- 3. Campus Location Coordinates -->
                        <div class="mb-4">
                            <label class="form-label-headworld fs-6 mb-2">
                                3. Facility Location Coordinates <span class="text-danger">*</span>
                            </label>
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label-headworld small" for="buildingSelect">Building Complex</label>
                                    <select id="buildingSelect" name="building" class="form-select-headworld" required>
                                        @php
                                            $isPredefined = in_array($serviceRequest->building, $buildings, true);
                                            $selectedBuilding = old('building', $isPredefined ? $serviceRequest->building : 'Other');
                                        @endphp
                                        @foreach ($buildings as $bld)
                                            <option value="{{ $bld }}" {{ $selectedBuilding === $bld ? 'selected' : '' }}>
                                                {{ $bld }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div id="customBuildingWrapper" class="mt-2 {{ $selectedBuilding === 'Other' ? '' : 'd-none' }}">
                                        <input type="text" id="customBuildingInput" name="custom_building"
                                            class="form-control-headworld" placeholder="Enter specific building name..."
                                            value="{{ old('custom_building', !$isPredefined ? $serviceRequest->building : '') }}">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label-headworld small" for="wingInput">Wing / Floor</label>
                                    <input type="text" id="wingInput" name="wing_floor" class="form-control-headworld"
                                        placeholder="e.g. Wing B, 3rd Floor" value="{{ old('wing_floor', $serviceRequest->wing_floor) }}" required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label-headworld small" for="roomInput">Room / Lab Number</label>
                                    <input type="text" id="roomInput" name="room_number" class="form-control-headworld"
                                        placeholder="e.g. Room 304" value="{{ old('room_number', $serviceRequest->room_number) }}" required>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Complaint Title & Description -->
                        <div class="mb-4">
                            <label class="form-label-headworld fs-6 mb-2">
                                4. Complaint Specification <span class="text-danger">*</span>
                            </label>
                            <div class="mb-3">
                                <label for="titleInput" class="form-label-headworld small">Subject / Title</label>
                                <input type="text" id="titleInput" name="title" class="form-control-headworld"
                                    placeholder="e.g. Leaking radiator valve"
                                    value="{{ old('title', $serviceRequest->title) }}" required maxlength="255">
                            </div>
                            <div>
                                <label for="descriptionInput" class="form-label-headworld small">Description</label>
                                <textarea id="descriptionInput" name="description" class="form-control-headworld" rows="4"
                                    placeholder="Give brief details about the issue..."
                                    required>{{ old('description', $serviceRequest->description) }}</textarea>
                            </div>
                        </div>

                        <!-- Existing Evidence Photos -->
                        @if ($serviceRequest->evidencePhotos->isNotEmpty())
                            <div class="mb-4 p-3 bg-light rounded-3 border">
                                <label class="form-label-headworld fs-6 mb-2">Current Attached Photos</label>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($serviceRequest->evidencePhotos as $photo)
                                        <div class="position-relative border rounded p-1 bg-white shadow-sm" style="width: 100px; height: 100px;">
                                            <img src="{{ $photo->url }}" alt="Evidence photo" class="w-100 h-100 object-fit-cover rounded">
                                        </div>
                                    @endforeach
                                </div>
                                <small class="text-muted d-block mt-2">You can upload additional photos below if required.</small>
                            </div>
                        @endif

                        <!-- 5. Additional Photo Upload Dropzone -->
                        <div class="mb-4">
                            <label class="form-label-headworld fs-6 mb-2">5. Attach Additional Photos (Optional)</label>
                            <div class="upload-dropzone" id="dropzoneArea" role="button" tabindex="0">
                                <input type="file" name="attachments[]" id="evidenceFileInput"
                                    accept="image/jpeg,image/png,image/webp" multiple style="display: none;">
                                <i class="bi bi-cloud-arrow-up dropzone-icon"></i>
                                <div class="dropzone-title">Drag & drop additional photos, or browse files</div>
                                <div class="dropzone-subtitle">Attach up to 5 additional images (JPEG, PNG, or WebP up to 10MB each)</div>
                            </div>
                            <div class="preview-grid d-flex flex-wrap gap-2 mt-3" id="evidencePreviewGrid"></div>
                        </div>

                        <!-- Form Action Buttons -->
                        <div class="d-flex flex-column-reverse flex-sm-row justify-content-end gap-2 gap-sm-3 pt-3 border-top">
                            <a href="{{ route('user.requests.show', $serviceRequest->slug) }}" class="btn btn-subtle px-4 text-center">
                                Discard Changes
                            </a>
                            <button type="submit" id="submitBtn" class="btn btn-gold px-4 py-2 fs-6 shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i> Save Updated Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/request-wizard.js') }}"></script>
@endpush

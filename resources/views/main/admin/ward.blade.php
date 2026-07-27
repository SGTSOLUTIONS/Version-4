@extends('layouts.office')

@section('title', 'Wards')
@section('page_title', 'Wards')

@section('content')

    <div class="ol-page-header">
        <div>
            <h1 class="ol-page-title">Wards</h1>
            <p class="ol-page-sub">Manage all municipal corporation Wards</p>
        </div>
    </div>

    <div class="data-toolbar d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="data-search">
            <input type="text" id="wardSearch" class="form-control" placeholder="Search by ward number or zone">
        </div>
        <div class="d-flex align-items-center gap-2">
            @if (auth()->user()->role == 'admin')
                <select id="corporationFilter" class="form-select app-select">
                    <option value="">All Corporations</option>
                    @foreach ($corporations as $corp)
                        <option value="{{ $corp->id }}">
                            {{ $corp->name }}
                        </option>
                    @endforeach
                </select>
            @endif

            <select id="zoneFilter" class="form-select app-select">
                <option value="">All Zones</option>
                @foreach ($zones as $zone)
                    <option value="{{ $zone->id }}">
                        {{ $zone->zone_name }}
                    </option>
                @endforeach
            </select>

            <select id="statusFilter" class="form-select app-select">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

            <button class="btn btn-success app-btn-sm" data-bs-toggle="modal" data-bs-target="#wardModal" id="addWardBtn">
                <i class="bi bi-building-add"></i>
                <span>Add Ward</span>
            </button>
        </div>
    </div>

    {{-- loading spinner --}}
    <div id="loadingSpinner" class="text-center py-5" style="display: none;">
        <div class="spinner-border text-success" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="card-grid" id="wardsGrid">
        <!-- Wards will be loaded here -->
    </div>

    <!-- Pagination Container -->
    <div id="paginationContainer" class="d-flex justify-content-center mt-4" style="display: none;">
        <nav>
            <ul class="pagination" id="paginationList">
                <!-- Pagination will be loaded here -->
            </ul>
        </nav>
    </div>

    <!-- Ward Modal (Add/Edit) -->
    <div class="modal fade" id="wardModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="bi bi-building-add me-2"></i>
                        Add Ward
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="wardForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="wardId">
                    <input type="hidden" name="_method" id="wardFormMethod" value="POST">
                    <div class="modal-body" style="max-height:70vh;overflow-y:auto;">

                        <!-- Basic Information -->
                        <div class="card mb-3">
                            <div class="card-header">Ward Information</div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Corporation <span class="text-danger">*</span></label>
                                        <select name="corp_id" id="f_corp_id" class="form-select" required
                                            {{ auth()->user()->role == 'commissioner' ? 'disabled' : '' }}>
                                            <option value="">Select Corporation</option>
                                            @foreach ($corporations as $corp)
                                                <option value="{{ $corp->id }}"
                                                    {{ auth()->user()->role == 'commissioner' && auth()->user()->corporation_id == $corp->id ? 'selected' : '' }}>
                                                    {{ $corp->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback" id="error-corp_id"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Zone <span class="text-danger">*</span></label>
                                        <select name="zone_id" id="f_zone_id" class="form-select" required>
                                            <option value="">Select Zone</option>
                                            @foreach ($zones as $zone)
                                                <option value="{{ $zone->id }}">
                                                    {{ $zone->zone_name }} ({{ $zone->zone_code }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback" id="error-zone_id"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Ward Number <span class="text-danger">*</span></label>
                                        <input type="text" name="ward_no" id="f_ward_no" class="form-control"
                                            placeholder="e.g., Ward 1, Ward A">
                                        <div class="invalid-feedback" id="error-ward_no"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Zone Name (Optional)</label>
                                        <input type="text" name="zone_name" id="f_zone_name" class="form-control"
                                            placeholder="e.g., East, West, North">
                                        <div class="invalid-feedback" id="error-zone_name"></div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Drone Image</label>
                                        <input type="file" name="drone_image" id="f_drone_image" class="form-control"
                                            accept="image/*">
                                        <small class="text-muted">Upload drone image of the ward (optional)</small>
                                        <div id="currentImage" style="display:none; margin-top:10px;">
                                            <img id="currentDroneImage" src="" alt="Current Image"
                                                style="max-width:100px; max-height:100px;">
                                            <br>
                                            <small>Current image</small>
                                        </div>
                                        <div class="invalid-feedback" id="error-drone_image"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Geographic Extent Information -->
                        <div class="card mb-3">
                            <div class="card-header">Geographic Extent</div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Left (Min Longitude)</label>
                                        <input type="number" step="any" name="extent_left" id="f_extent_left"
                                            class="form-control" placeholder="e.g., 72.123456">
                                        <div class="invalid-feedback" id="error-extent_left"></div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Right (Max Longitude)</label>
                                        <input type="number" step="any" name="extent_right" id="f_extent_right"
                                            class="form-control" placeholder="e.g., 72.123456">
                                        <div class="invalid-feedback" id="error-extent_right"></div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Top (Max Latitude)</label>
                                        <input type="number" step="any" name="extent_top" id="f_extent_top"
                                            class="form-control" placeholder="e.g., 18.123456">
                                        <div class="invalid-feedback" id="error-extent_top"></div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Bottom (Min Latitude)</label>
                                        <input type="number" step="any" name="extent_bottom" id="f_extent_bottom"
                                            class="form-control" placeholder="e.g., 18.123456">
                                        <div class="invalid-feedback" id="error-extent_bottom"></div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Boundary File (GeoJSON)</label>
                                        <input type="file" name="boundary_file" id="f_boundary_file"
                                            class="form-control" accept=".json,.geojson">
                                        <small class="text-muted">Upload GeoJSON (.geojson or .json) boundary file</small>
                                        <div class="invalid-feedback" id="error-boundary_file"></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Polygon File (GeoJSON)</label>
                                        <input type="file" name="polygon_file" id="f_polygon_file"
                                            class="form-control" accept=".json,.geojson">
                                        <small class="text-muted">
                                            Upload GeoJSON (.geojson or .json) polygon file
                                        </small>
                                        <div class="invalid-feedback" id="error-polygon_file"></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Road File (GeoJSON)</label>
                                        <input type="file" name="road_file" id="f_road_file" class="form-control"
                                            accept=".json,.geojson">
                                        <small class="text-muted">
                                            Upload GeoJSON (.geojson or .json) road file
                                        </small>
                                        <div class="invalid-feedback" id="error-road_file"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="card mb-3">
                            <div class="card-header">Contact Information</div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Contact Person</label>
                                        <input type="text" name="contact_person" id="f_contact_person"
                                            class="form-control">
                                        <div class="invalid-feedback" id="error-contact_person"></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Designation</label>
                                        <input type="text" name="designation" id="f_designation"
                                            class="form-control">
                                        <div class="invalid-feedback" id="error-designation"></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" id="f_phone" class="form-control">
                                        <div class="invalid-feedback" id="error-phone"></div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" id="f_email" class="form-control">
                                        <div class="invalid-feedback" id="error-email"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Address & Status -->
                        <div class="card">
                            <div class="card-header">Address & Status</div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-10">
                                        <label class="form-label">Address</label>
                                        <textarea name="address" id="f_address" rows="2" class="form-control"></textarea>
                                        <div class="invalid-feedback" id="error-address"></div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Status</label>
                                        <select name="status" id="f_status" class="form-select">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                        <div class="invalid-feedback" id="error-status"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success" id="wardSaveBtn">Save Ward</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal - Only for admin -->
    @if (auth()->user()->role == 'admin')
        <div class="modal fade" id="deleteModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
                <div class="modal-content">
                    <div class="modal-body text-center py-4">
                        <div
                            style="width:56px;height:56px;border-radius:50%;background:rgba(239,68,68,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                            <i class="bi bi-trash3" style="font-size:22px;color:#ef4444;"></i>
                        </div>
                        <h6 class="fw-bold mb-1">Delete Ward?</h6>
                        <p class="text-muted" style="font-size:0.8rem;" id="deleteWardName"></p>
                        <input type="hidden" id="deleteWardId">
                    </div>
                    <div class="modal-footer border-0 justify-content-center gap-2 pt-0">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger px-4" id="confirmDeleteBtn">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- View Ward Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-eye me-2"></i>
                        Ward Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewModalBody">
                    <!-- Ward details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .card-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
        padding: 10px 0;
    }

    .acard {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border: 1px solid #e9ecef;
        /* FIX: removed `overflow: hidden;` — it was clipping the
           "More" dropdown menu so it appeared to not open at all. */
        position: relative;
    }

    .acard:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        transform: translateY(-2px);
    }

    .acard-body {
        padding: 20px;
        /* If the card had rounded top corners from an image before,
           round the body/image individually instead of relying on
           the parent's overflow:hidden. */
        border-radius: 12px;
        overflow: visible;
    }

    .acard-meta {
        font-size: 0.8rem;
        color: #6c757d;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 5px;
    }

    .acard-meta .dot {
        width: 4px;
        height: 4px;
        background: #6c757d;
        border-radius: 50%;
        display: inline-block;
        margin: 0 5px;
    }

    .acard-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 10px;
        color: #1a1a2e;
    }

    .acard-desc {
        color: #495057;
        font-size: 0.9rem;
        margin-bottom: 4px;
    }

    .acard-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #e9ecef;
    }

    .acard-author {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .dropdown-item i {
        width: 20px;
    }

    .btn-group-actions {
        display: flex;
        gap: 8px;
        margin-top: 10px;
        flex-wrap: wrap;
        position: relative;
    }

    .btn-group-actions .btn {
        flex: 1;
        min-width: 60px;
    }

    .btn-group-actions .dropdown {
        flex: 1;
        min-width: 80px;
        position: static; /* lets the fixed-position dropdown escape any clipping ancestor */
    }

    .btn-group-actions .dropdown-toggle {
        width: 100%;
    }

    /* Fix for dropdown menu positioning */
    .dropdown-menu {
        min-width: 220px;
        z-index: 3000; /* sit above cards, modals' backdrop, etc. */
    }

    .dropdown-menu.show {
        display: block;
    }

    /* Make sure the grid itself never clips a dropdown that
       is positioned relative to a card near the grid edges */
    .card-grid {
        overflow: visible;
    }
</style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            let currentPage = 1;
            let totalPages = 1;
            let isLoading = false;
            let userRole = '{{ auth()->user()->role }}';

            // Load zones based on corporation selection in filter (admin only)
            $('#corporationFilter').on('change', function() {
                let corpId = $(this).val();
                let zoneSelect = $('#zoneFilter');

                zoneSelect.html('<option value="">All Zones</option>');
                zoneSelect.prop('disabled', true);

                if (corpId) {
                    let url = "{{ route('admin.zones.byCorporation') }}";
                    $.ajax({
                        url: url,
                        type: "GET",
                        data: {
                            corp_id: corpId
                        },
                        success: function(response) {
                            if (response.status && response.data.length > 0) {
                                $.each(response.data, function(index, zone) {
                                    zoneSelect.append(
                                        `<option value="${zone.id}">${zone.zone_name}</option>`
                                    );
                                });
                                zoneSelect.prop('disabled', false);
                            } else {
                                zoneSelect.html('<option value="">No zones found</option>');
                                zoneSelect.prop('disabled', true);
                            }
                        },
                        error: function() {
                            zoneSelect.html('<option value="">Error loading zones</option>');
                            zoneSelect.prop('disabled', true);
                        }
                    });
                } else {
                    zoneSelect.html('<option value="">All Zones</option>');
                    zoneSelect.prop('disabled', false);
                }

                loadWards(1);
            });

            // Load zones based on corporation selection in form
            $('#f_corp_id').on('change', function() {
                let corpId = $(this).val();
                let zoneSelect = $('#f_zone_id');

                zoneSelect.html('<option value="">Select Zone</option>');
                zoneSelect.prop('disabled', true);

                if (corpId) {
                    let url = "{{ route('admin.zones.byCorporation') }}";
                    $.ajax({
                        url: url,
                        type: "GET",
                        data: {
                            corp_id: corpId
                        },
                        success: function(response) {
                            if (response.status && response.data.length > 0) {
                                $.each(response.data, function(index, zone) {
                                    zoneSelect.append(
                                        `<option value="${zone.id}">${zone.zone_name} (${zone.zone_code})</option>`
                                    );
                                });
                                zoneSelect.prop('disabled', false);
                            } else {
                                zoneSelect.html('<option value="">No zones available</option>');
                                zoneSelect.prop('disabled', true);
                            }
                        },
                        error: function() {
                            zoneSelect.html('<option value="">Error loading zones</option>');
                            zoneSelect.prop('disabled', true);
                        }
                    });
                }
            });

            // Load wards with pagination
            function loadWards(page = 1) {
                if (isLoading) return;

                isLoading = true;
                $('#loadingSpinner').show();

                let search = $("#wardSearch").val();
                let status = $("#statusFilter").val();
                let zoneId = $("#zoneFilter").val();
                let corporationId = $("#corporationFilter").val();

                let url;
                if (userRole === 'commissioner') {
                    url = "{{ route('commissioner.ward.list') }}";
                } else {
                    url = "{{ route('admin.ward.list') }}";
                }

                $.ajax({
                    url: url,
                    type: "GET",
                    data: {
                        ward_no: search,
                        zone: search,
                        status: status,
                        zone_id: zoneId,
                        corp_id: corporationId,
                        page: page
                    },
                    success: function(response) {
                        if (response.status && response.data) {
                            const paginator = response.data;
                            renderCards(paginator.data);
                            renderPagination({
                                current_page: paginator.current_page,
                                last_page: paginator.last_page,
                                per_page: paginator.per_page,
                                total: paginator.total,
                                from: paginator.from,
                                to: paginator.to
                            });
                            currentPage = paginator.current_page;
                            totalPages = paginator.last_page;
                        }
                        isLoading = false;
                        $('#loadingSpinner').hide();
                        $('html, body').animate({
                            scrollTop: 0
                        }, 300);
                    },
                    error: function(xhr) {
                        showFlashMessage('Failed to load wards', 'error');
                        isLoading = false;
                        $('#loadingSpinner').hide();
                    }
                });
            }

            // Render cards
            function renderCards(wards) {
                if (!wards || wards.length === 0) {
                    $('#wardsGrid').html(`
                        <div class="text-center py-5 w-100">
                            <i class="bi bi-building fs-1 text-muted"></i>
                            <h5 class="mt-2">No Wards Found</h5>
                        </div>
                    `);
                    return;
                }

                let html = '';
                const assetBase = "{{ asset('') }}";

                $.each(wards, function(index, ward) {
                    let zoneName = '-';
                    if (ward.zone) {
                        if (typeof ward.zone === 'object') {
                            zoneName = ward.zone.zone_name || '-';
                        } else if (typeof ward.zone === 'string') {
                            zoneName = ward.zone;
                        }
                    }

                    let corporationName = '-';
                    if (ward.zone && typeof ward.zone === 'object' && ward.zone.corporation) {
                        corporationName = ward.zone.corporation.name || '-';
                    }

                    let imageUrl = ward.drone_image ?
                        assetBase + ward.drone_image :
                        assetBase + 'images/default-ward.png';

                    let badgeClass = {
                        active: 'bg-success',
                        inactive: 'bg-secondary'
                    } [ward.status] || 'bg-secondary';

                    // Create unique ID for this card's dropdown
                    let dropdownId = 'dropdown-' + ward.id;

                    html += `
                        <div class="acard">
                            <div class="acard-body">
                                <div class="acard-meta">
                                    <i class="bi bi-building"></i>
                                    ${escapeHtml(corporationName)}
                                    <span class="dot"></span>
                                    <i class="bi bi-diagram-2"></i>
                                    ${escapeHtml(zoneName)}
                                    <span class="dot"></span>
                                    <i class="bi bi-hash"></i>
                                    ${escapeHtml(ward.ward_no)}
                                </div>
                                <h3 class="acard-title">
                                    Ward ${escapeHtml(ward.ward_no)}
                                </h3>
                                ${ward.contact_person ? `
                                    <p class="acard-desc small mb-1">
                                        <i class="bi bi-person"></i> ${escapeHtml(ward.contact_person)}
                                        ${ward.designation ? ` (${escapeHtml(ward.designation)})` : ''}
                                    </p>
                                ` : ''}
                                ${ward.phone ? `
                                    <p class="acard-desc small mb-1">
                                        <i class="bi bi-telephone"></i> ${escapeHtml(ward.phone)}
                                    </p>
                                ` : ''}
                                ${ward.email ? `
                                    <p class="acard-desc small mb-1">
                                        <i class="bi bi-envelope"></i> ${escapeHtml(ward.email)}
                                    </p>
                                ` : ''}
                                <div class="acard-footer">
                                    <span class="acard-author">
                                        ${escapeHtml(ward.contact_person || 'No contact')}
                                    </span>
                                    <span class="badge ${badgeClass}">
                                        ${ward.status}
                                    </span>
                                </div>
                                ${ward.road_names && ward.road_names.length ? `
                                    <div class="mt-3">
                                        <label class="form-label small fw-bold">Road Name</label>
                                        <select class="form-select form-select-sm road-name-select" id="ward-${ward.id}"
                                                data-ward="${ward.id}">
                                            <option value="all">All Roads</option>
                                            ${ward.road_names.map(road => `
                                                <option value="${escapeHtml(road)}">
                                                    ${escapeHtml(road)}
                                                </option>
                                            `).join('')}
                                        </select>
                                    </div>
                                ` : `
                                    <div class="mt-3">
                                        <label class="form-label small fw-bold">Road Name</label>
                                        <select class="form-select form-select-sm" disabled>
                                            <option>No Roads Found</option>
                                        </select>
                                    </div>
                                `}

                                <!-- Action Buttons -->
                                <div class="btn-group-actions">
                                    <button class="btn btn-sm btn-info view-btn" data-id="${ward.id}" title="View Ward Details">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                    <button class="btn btn-sm btn-primary edit-btn" data-id="${ward.id}" title="Edit Ward">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button"
                                                id="${dropdownId}" data-bs-toggle="dropdown"
                                                data-bs-display="static"
                                                aria-expanded="false" title="More Actions">
                                            <i class="bi bi-three-dots-vertical"></i> More
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="${dropdownId}">
                                            <li>
                                                <a class="dropdown-item building-btn" data-id="${ward.id}" href="#">
                                                    <i class="bi bi-download me-2"></i> All Buildings (GeoJSON)
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item missing-building-btn" data-id="${ward.id}" href="#">
                                                    <i class="bi bi-geo-alt me-2"></i> Missing Building (GeoJSON)
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item missing-building-excel-btn" data-id="${ward.id}" href="#">
                                                    <i class="bi bi-file-earmark-excel me-2"></i> Missing Building (Excel)
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item missing-bill-btn" data-id="${ward.id}" href="#">
                                                    <i class="bi bi-receipt me-2"></i> Missing Bill (Excel)
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item missing-bill-pdf-btn" data-id="${ward.id}" href="#">
                                                    <i class="bi bi-receipt me-2"></i> Missing Bill (PDF)
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item mis-bill-download-btn" data-id="${ward.id}" href="#">
                                                    <i class="bi bi-receipt me-2"></i> MIS Bill (Excel)
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item ugd-bill-download-btn" data-id="${ward.id}" href="#">
                                                    <i class="bi bi-droplet me-2"></i> UGD Tax (Excel)
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item water-bill-download-btn" data-id="${ward.id}" href="#">
                                                    <i class="bi bi-water me-2"></i> Water Tax (Excel)
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item professional-tax-download-btn" data-id="${ward.id}" href="#">
                                                    <i class="bi bi-briefcase me-2"></i> Professional Tax (Excel)
                                                </a>
                                            </li>
                                            ${userRole === 'admin' ? `
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item text-danger delete-btn" data-id="${ward.id}" data-name="${escapeHtml(ward.ward_no)}" href="#">
                                                        <i class="bi bi-trash me-2"></i> Delete Ward
                                                    </a>
                                                </li>
                                            ` : ''}
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                $('#wardsGrid').html(html);

                // FIX: Explicitly (re)initialize every dropdown-toggle as a
                // Bootstrap Dropdown instance using Popper's `fixed` strategy.
                // This makes the menu render relative to the viewport instead
                // of its offset parent, so it can never be clipped by a card,
                // the grid, or any other ancestor — and it also guarantees
                // dropdowns work correctly after the grid HTML is replaced
                // on every AJAX reload (dynamically injected elements are
                // not auto-initialized by Bootstrap's data-api after being
                // added to the DOM).
                document.querySelectorAll('#wardsGrid .dropdown-toggle').forEach(function(el) {
                    // Dispose of any existing instance first to avoid duplicates
                    let existing = bootstrap.Dropdown.getInstance(el);
                    if (existing) {
                        existing.dispose();
                    }
                    new bootstrap.Dropdown(el, {
                        popperConfig: function(defaultConfig) {
                            return Object.assign({}, defaultConfig, {
                                strategy: 'fixed'
                            });
                        }
                    });
                });
            }

            // Render pagination
            function renderPagination(pagination) {
                if (!pagination || pagination.last_page <= 1) {
                    $('#paginationContainer').hide();
                    return;
                }

                $('#paginationContainer').show();
                let html = '';

                if (pagination.current_page > 1) {
                    html +=
                        `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page - 1}">&laquo; Previous</a></li>`;
                } else {
                    html +=
                        `<li class="page-item disabled"><a class="page-link" href="#">&laquo; Previous</a></li>`;
                }

                if (pagination.current_page > 3) {
                    html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
                    if (pagination.current_page > 4) {
                        html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`;
                    }
                }

                for (let i = Math.max(1, pagination.current_page - 2); i <= Math.min(pagination.last_page,
                        pagination.current_page + 2); i++) {
                    if (i === pagination.current_page) {
                        html +=
                            `<li class="page-item active"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                    } else {
                        html +=
                            `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                    }
                }

                if (pagination.current_page < pagination.last_page - 2) {
                    if (pagination.current_page < pagination.last_page - 3) {
                        html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`;
                    }
                    html +=
                        `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.last_page}">${pagination.last_page}</a></li>`;
                }

                if (pagination.current_page < pagination.last_page) {
                    html +=
                        `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page + 1}">Next &raquo;</a></li>`;
                } else {
                    html += `<li class="page-item disabled"><a class="page-link" href="#">Next &raquo;</a></li>`;
                }

                $('#paginationList').html(html);

                let start = pagination.from || ((pagination.current_page - 1) * pagination.per_page + 1);
                let end = pagination.to || Math.min(pagination.current_page * pagination.per_page, pagination
                    .total);
                let infoHtml =
                    `<div class="text-center text-muted mt-3">Showing ${start} to ${end} of ${pagination.total} wards</div>`;

                if ($('#paginationInfo').length === 0) {
                    $('#paginationContainer').after(`<div id="paginationInfo">${infoHtml}</div>`);
                } else {
                    $('#paginationInfo').html(infoHtml);
                }
            }

            function escapeHtml(text) {
                if (!text) return '';
                return String(text).replace(/[&<>]/g, function(m) {
                    if (m === '&') return '&amp;';
                    if (m === '<') return '&lt;';
                    if (m === '>') return '&gt;';
                    return m;
                });
            }

            // Event handlers
            $(document).on('click', '.page-link', function(e) {
                e.preventDefault();
                let page = $(this).data('page');
                if (page && !isLoading) {
                    loadWards(page);
                }
            });

            let searchTimeout;
            $('#wardSearch').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => loadWards(1), 500);
            });

            $('#statusFilter, #zoneFilter').on('change', function() {
                loadWards(1);
            });

            // Add Ward button
            $('#addWardBtn').on('click', function() {
                $('#wardForm')[0].reset();
                $('#currentImage').hide();
                $('#wardId').val('');
                $('#wardFormMethod').val('POST');
                $('#modalTitle').html('<i class="bi bi-building-add me-2"></i> Add Ward');
                $('#wardSaveBtn').html('Save Ward');
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');

                @if (auth()->user()->role == 'commissioner')
                    $('#f_corp_id').prop('disabled', true);
                    let corpId = $('#f_corp_id').val();
                    if (corpId) {
                        $('#f_zone_id').html('<option value="">Loading zones...</option>').prop('disabled', true);
                        let url = "{{ route('admin.zones.byCorporation') }}";
                        $.ajax({
                            url: url,
                            type: "GET",
                            data: {
                                corp_id: corpId
                            },
                            success: function(response) {
                                let zoneSelect = $('#f_zone_id');
                                zoneSelect.html('<option value="">Select Zone</option>');
                                if (response.status && response.data.length > 0) {
                                    $.each(response.data, function(index, zone) {
                                        zoneSelect.append(
                                            `<option value="${zone.id}">${zone.zone_name} (${zone.zone_code})</option>`
                                        );
                                    });
                                    zoneSelect.prop('disabled', false);
                                } else {
                                    zoneSelect.html('<option value="">No zones available</option>');
                                    zoneSelect.prop('disabled', true);
                                }
                            }
                        });
                    }
                @else                    $('#f_corp_id').prop('disabled', false);
                    $('#f_zone_id').html('<option value="">First select Corporation</option>').prop('disabled', true);
                @endif

                $('#wardModal').modal('show');
            });

            // Submit form with role-based URL
            $('#wardForm').on('submit', function(e) {
                e.preventDefault();
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');

                let formData = new FormData(this);
                let wardId = $('#wardId').val();
                let method = $('#wardFormMethod').val();
                let url;

                if (method === 'PUT') {
                    if (userRole === 'commissioner') {
                        url = "/commissioner/wards/" + wardId;
                    } else {
                        url = "/admin/wards/" + wardId;
                    }
                    formData.append('_method', 'PUT');
                } else {
                    if (userRole === 'commissioner') {
                        url = "/commissioner/wards";
                    } else {
                        url = "/admin/wards";
                    }
                }

                @if (auth()->user()->role == 'commissioner')
                    if (!$('#f_corp_id').val()) {
                        showFlashMessage('Corporation is required', 'error');
                        return false;
                    }
                @endif

                $('#wardSaveBtn').prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

                $.ajax({
                    url: url,
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $('#wardSaveBtn').prop('disabled', false).html('Save Ward');
                        $('#wardForm')[0].reset();
                        $('#wardModal').modal('hide');
                        showFlashMessage(response.message || 'Ward saved successfully',
                            'success');
                        loadWards(currentPage);
                    },
                    error: function(xhr) {
                        $('#wardSaveBtn').prop('disabled', false).html('Save Ward');
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function(field, messages) {
                                let fieldSelector = '[name="' + field + '"]';
                                let errorId = '#error-' + field;

                                if (field === 'zone') {
                                    fieldSelector = '[name="zone_name"]';
                                    errorId = '#error-zone_name';
                                }

                                $(fieldSelector).addClass('is-invalid');
                                $(errorId).text(messages[0]);
                            });
                            showFlashMessage('Please fix validation errors', 'error');
                        } else {
                            let errorMessage = xhr.responseJSON?.message ||
                                'Something went wrong';
                            showFlashMessage(errorMessage, 'error');
                            console.error('Server error:', xhr.responseJSON);
                        }
                    }
                });
            });

            // Edit button with role-based URL
            $(document).on('click', '.edit-btn', function() {
                let id = $(this).data('id');
                let url;

                if (userRole === 'commissioner') {
                    url = "/commissioner/wards/" + id;
                } else {
                    url = "/admin/wards/" + id;
                }

                $.ajax({
                    url: url,
                    type: "GET",
                    success: function(response) {
                        let ward = response.data;
                        $('#modalTitle').html(
                            '<i class="bi bi-pencil-square me-2"></i> Edit Ward');
                        $('#wardId').val(ward.id);

                        let corpId = null;
                        if (ward.zone && typeof ward.zone === 'object') {
                            corpId = ward.zone.corp_id;
                        }
                        $('#f_corp_id').val(corpId);

                        @if (auth()->user()->role == 'admin')
                            $('#f_corp_id').trigger('change');
                            setTimeout(() => {
                                $('#f_zone_id').val(ward.zone_id);
                            }, 500);
                        @else
                            $('#f_zone_id').val(ward.zone_id);
                            $('#f_corp_id').prop('disabled', true);
                        @endif

                        $('#f_ward_no').val(ward.ward_no);

                        let zoneNameValue = '';
                        if (ward.zone_name) {
                            zoneNameValue = ward.zone_name;
                        } else if (ward.zone) {
                            if (typeof ward.zone === 'object') {
                                zoneNameValue = ward.zone.zone_name || '';
                            } else if (typeof ward.zone === 'string') {
                                zoneNameValue = ward.zone;
                            }
                        }
                        $('#f_zone_name').val(zoneNameValue);
                        $('#f_extent_left').val(ward.extent_left);
                        $('#f_extent_right').val(ward.extent_right);
                        $('#f_extent_top').val(ward.extent_top);
                        $('#f_extent_bottom').val(ward.extent_bottom);
                        $('#f_contact_person').val(ward.contact_person);
                        $('#f_designation').val(ward.designation);
                        $('#f_phone').val(ward.phone);
                        $('#f_email').val(ward.email);
                        $('#f_address').val(ward.address);
                        $('#f_status').val(ward.status);

                        if (ward.drone_image) {
                            $('#currentDroneImage').attr('src', '/' + ward.drone_image);
                            $('#currentImage').show();
                        } else {
                            $('#currentImage').hide();
                        }

                        $('#wardFormMethod').val('PUT');
                        $('#wardSaveBtn').html('Update Ward');
                        $('#wardModal').modal('show');
                    },
                    error: function() {
                        showFlashMessage('Failed to load ward data', 'error');
                    }
                });
            });

            // Delete button - Only for admin
            @if (auth()->user()->role == 'admin')
                $(document).on('click', '.delete-btn', function(e) {
                    e.preventDefault();
                    let id = $(this).data('id');
                    let name = $(this).data('name');
                    $('#deleteWardId').val(id);
                    $('#deleteWardName').text(`This will permanently remove "${name}" and all its data.`);
                    $('#deleteModal').modal('show');
                });

                $('#confirmDeleteBtn').on('click', function() {
                    let id = $('#deleteWardId').val();
                    if (!id) return;

                    $.ajax({
                        url: "/admin/wards/" + id,
                        type: "DELETE",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            $('#deleteModal').modal('hide');
                            showFlashMessage(response.message || 'Ward deleted successfully',
                                'success');
                            loadWards(1);
                        },
                        error: function(xhr) {
                            showFlashMessage(xhr.responseJSON?.message ||
                                'Failed to delete ward', 'error');
                        }
                    });
                });
            @endif

            // View button with role-based URL
            $(document).on('click', '.view-btn', function() {
                let id = $(this).data('id');
                let url;

                if (userRole === 'commissioner') {
                    url = "/commissioner/wards/" + id;
                } else {
                    url = "/admin/wards/" + id;
                }

                $.ajax({
                    url: url,
                    type: "GET",
                    success: function(response) {
                        let ward = response.data;
                        let zoneName = '-';
                        if (ward.zone) {
                            if (typeof ward.zone === 'object') {
                                zoneName = ward.zone.zone_name || '-';
                            } else if (typeof ward.zone === 'string') {
                                zoneName = ward.zone;
                            }
                        }
                        let corporationName = '-';
                        if (ward.zone && typeof ward.zone === 'object' && ward.zone
                            .corporation) {
                            corporationName = ward.zone.corporation.name || '-';
                        }
                        let imageUrl = ward.drone_image ? "{{ asset('') }}" + ward
                            .drone_image : null;

                        let html = `
                            <div class="row">
                                <div class="col-12 text-center mb-3">
                                    ${imageUrl ? `<img src="${imageUrl}" alt="${escapeHtml(ward.ward_no)}" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;">` :
                                        `<div style="width: 150px; height: 150px; background: #e9ecef; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center;">
                                            <i class="bi bi-building fs-1 text-muted"></i>
                                        </div>`}
                                </div>
                                <div class="col-md-6"><strong>Corporation:</strong><br><p>${escapeHtml(corporationName)}</p></div>
                                <div class="col-md-6"><strong>Zone:</strong><br><p>${escapeHtml(zoneName)}</p></div>
                                <div class="col-md-6"><strong>Ward Number:</strong><br><p>${escapeHtml(ward.ward_no)}</p></div>
                                <div class="col-md-6"><strong>Zone Name:</strong><br><p>${escapeHtml(ward.zone_name || '-')}</p></div>
                                <div class="col-md-6"><strong>Contact Person:</strong><br><p>${escapeHtml(ward.contact_person || '-')}</p></div>
                                <div class="col-md-6"><strong>Designation:</strong><br><p>${escapeHtml(ward.designation || '-')}</p></div>
                                <div class="col-md-6"><strong>Phone:</strong><br><p>${escapeHtml(ward.phone || '-')}</p></div>
                                <div class="col-md-6"><strong>Email:</strong><br><p>${escapeHtml(ward.email || '-')}</p></div>
                                <div class="col-12"><strong>Address:</strong><br><p>${escapeHtml(ward.address || '-')}</p></div>
                                <div class="col-12"><strong>Status:</strong><br><p><span class="badge ${ward.status === 'active' ? 'bg-success' : 'bg-secondary'}">${ward.status}</span></p></div>
                            </div>
                        `;
                        $('#viewModalBody').html(html);
                        $('#viewModal').modal('show');
                    },
                    error: function() {
                        showFlashMessage('Failed to load ward details', 'error');
                    }
                });
            });

            // Download All Buildings (GeoJSON)
            $(document).on('click', '.building-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                let id = $(this).data('id');
                window.location.href = "/wards/" + id + "/export-all-polygons";
            });

            // Missing Building (GeoJSON)
            $(document).on('click', '.missing-building-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                let id = $(this).data('id');
                window.location.href = "/wards/" + id + "/missing-building-geojson";
            });

            // Missing Building (Excel)
            $(document).on('click', '.missing-building-excel-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                let id = $(this).data('id');
                window.location.href = "/wards/" + id + "/missing-building-excel";
            });

            // Missing Bill (Excel)
            $(document).on('click', '.missing-bill-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                let id = $(this).data('id');
                let roadname = $("#ward-" + id).val() || 'all';
                window.location.href = "/wards/" + id + "/missing-bill-excel?road_name=" +
                    encodeURIComponent(roadname);
            });

            // Missing Bill (PDF)
            $(document).on('click', '.missing-bill-pdf-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                let id = $(this).data('id');
                let roadname = $("#ward-" + id).val() || 'all';
                window.location.href = "/wards/" + id + "/missing-bill-pdf?road_name=" +
                    encodeURIComponent(roadname);
            });

            // MIS Download
            $(document).on('click', '.mis-bill-download-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                let id = $(this).data('id');
                let roadname = $("#ward-" + id).val() || 'all';
                window.location.href = "/wards/" + id + "/mis-bill-excel?road_name=" +
                    encodeURIComponent(roadname);
            });

            // UGD Tax Download
            $(document).on('click', '.ugd-bill-download-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                let id = $(this).data('id');
                let roadname = $("#ward-" + id).val() || 'all';
                window.location.href = "/wards/" + id + "/ugd-tax-excel?road_name=" +
                    encodeURIComponent(roadname);
            });

            // Water Tax Download
            $(document).on('click', '.water-bill-download-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                let id = $(this).data('id');
                let roadname = $("#ward-" + id).val() || 'all';
                window.location.href = "/wards/" + id + "/water-tax-excel?road_name=" +
                    encodeURIComponent(roadname);
            });

            // Professional Tax Download
            $(document).on('click', '.professional-tax-download-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                let id = $(this).data('id');
                let roadname = $("#ward-" + id).val() || 'all';
                window.location.href = "/wards/" + id + "/professional-tax-excel?road_name=" +
                    encodeURIComponent(roadname);
            });

            // Initial load
            loadWards(1);
        });
    </script>
@endpush

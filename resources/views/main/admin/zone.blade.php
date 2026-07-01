@extends('layouts.office')

@section('title', 'Zones')
@section('page_title', 'Zones')

@section('content')

    <div class="ol-page-header">
        <div>
            <h1 class="ol-page-title">Zones</h1>
            <p class="ol-page-sub">Manage all municipal corporation Zones</p>
        </div>
    </div>

    <div class="data-toolbar d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div class="data-search">
            <input type="text" id="zoneSearch" class="form-control" placeholder="Search by zone name">
        </div>
        <div class="d-flex align-items-center gap-2">
            <select id="statusFilter" class="form-select app-select">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

            {{-- Only show corporation filter for admin --}}
            @if(auth()->user()->role == 'admin')
                <select id="corpFilter" class="form-select app-select">
                    <option value="">All Corporations</option>
                    @foreach ($corporations as $corp)
                        <option value="{{ $corp->id }}">
                            {{ $corp->name }}
                        </option>
                    @endforeach
                </select>
            @endif

            <button class="btn btn-success app-btn-sm" data-bs-toggle="modal" data-bs-target="#zoneModal" id="addZoneBtn">
                <i class="bi bi-building-add"></i>
                <span>Add Zone</span>
            </button>
        </div>
    </div>

    {{-- loading spinner --}}
    <div id="loadingSpinner" class="text-center py-5" style="display: none;">
        <div class="spinner-border text-success" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="card-grid" id="zonesGrid">
        <!-- Zones will be loaded here -->
    </div>

    <!-- Pagination Container -->
    <div id="paginationContainer" class="d-flex justify-content-center mt-4">
        <!-- Laravel pagination links will be inserted here -->
    </div>

    <!-- Zone Modal (Add/Edit) -->
    <div class="modal fade" id="zoneModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="bi bi-building-add me-2"></i>
                        Add Zone
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="zoneForm">
                    @csrf
                    <input type="hidden" id="zoneId">
                    <input type="hidden" name="_method" id="zoneFormMethod" value="POST">

                    <div class="modal-body" style="max-height:70vh;overflow-y:auto;">

                        <!-- Basic Information -->
                        <div class="card mb-3">
                            <div class="card-header">Zone Information</div>
                            <div class="card-body">
                                <div class="row g-3">

                                    <div class="col-md-6">
                                        <label class="form-label">Corporation <span class="text-danger">*</span></label>
                                        <select name="corp_id" id="f_corp_id" class="form-select">
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
                                        <label class="form-label">Zone Name <span class="text-danger">*</span></label>
                                        <input type="text" name="zone_name" id="f_zone_name" class="form-control">
                                        <div class="invalid-feedback" id="error-zone_name"></div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Zone Code <span class="text-danger">*</span></label>
                                        <input type="text" name="zone_code" id="f_zone_code" class="form-control">
                                        <div class="invalid-feedback" id="error-zone_code"></div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Total Wards</label>
                                        <input type="number" name="total_wards" id="f_total_wards" class="form-control" min="0">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Description</label>
                                        <textarea name="description" id="f_description" rows="3" class="form-control"></textarea>
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
                                        <input type="text" name="contact_person" id="f_contact_person" class="form-control">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" id="f_phone" class="form-control">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" id="f_email" class="form-control">
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Address Information -->
                        <div class="card">
                            <div class="card-header">Address & Status</div>
                            <div class="card-body">
                                <div class="row g-3">

                                    <div class="col-md-8">
                                        <label class="form-label">Address</label>
                                        <textarea name="address" id="f_address" rows="2" class="form-control"></textarea>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label">Pincode</label>
                                        <input type="text" name="pincode" id="f_pincode" class="form-control">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label">Status</label>
                                        <select name="status" id="f_status" class="form-select">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Close
                        </button>
                        <button type="submit" class="btn btn-success" id="zoneSaveBtn">
                            Save Zone
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div style="width:56px;height:56px;border-radius:50%;background:rgba(239,68,68,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                        <i class="bi bi-trash3" style="font-size:22px;color:#ef4444;"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Delete Zone?</h6>
                    <p class="text-muted" style="font-size:0.8rem;" id="deleteZoneName"></p>
                    <input type="hidden" id="deleteZoneId">
                </div>
                <div class="modal-footer border-0 justify-content-center gap-2 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger px-4" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let currentUrl = new URL(window.location.href);
            let userRole = '{{ auth()->user()->role }}';

            // If commissioner, disable corporation selection in form
            @if(auth()->user()->role == 'commissioner')
                $('#f_corp_id').prop('disabled', true);
            @endif

            // Get the base URL for the current role
            function getBaseUrl() {
                if (userRole === 'commissioner') {
                    return '/commissioner';
                } else if (userRole === 'admin') {
                    return '/admin';
                }
                return '';
            }

            function getRouteName() {
                if (userRole === 'commissioner') {
                    return 'commissioner.zone.list';
                } else if (userRole === 'admin') {
                    return 'admin.zone.list';
                }
                return 'zone.list';
            }

            // Load zones with pagination
            function loadZones(url = null) {
                $('#loadingSpinner').show();
                $('#zonesGrid').html('');

                let search = $("#zoneSearch").val();
                let status = $("#statusFilter").val();
                let corpId = $("#corpFilter").val();

                // Build the URL with role-based routing
                let requestUrl;

                if (url) {
                    requestUrl = url;
                } else {
                    // Use the appropriate route based on role
                    if (userRole === 'commissioner') {
                        requestUrl = "{{ route('commissioner.zone.list') }}";
                    } else if (userRole === 'admin') {
                        requestUrl = "{{ route('admin.zone.list') }}";
                    } else {
                        requestUrl = '/zones/list';
                    }
                }

                $.ajax({
                    url: requestUrl,
                    type: "GET",
                    data: {
                        zone_name: search,
                        status: status,
                        corp_id: corpId
                    },
                    success: function(response) {
                        if (response.status) {
                            renderCards(response.data.data);
                            renderPagination(response.data);
                        }
                        $('#loadingSpinner').hide();
                        $('html, body').animate({
                            scrollTop: 0
                        }, 300);
                    },
                    error: function(xhr) {
                        showFlashMessage('Failed to load zones', 'error');
                        $('#loadingSpinner').hide();
                    }
                });
            }

            // Render cards
            function renderCards(zones) {
                if (!zones || zones.length === 0) {
                    $('#zonesGrid').html(`
                        <div class="text-center py-5 w-100">
                            <i class="bi bi-building fs-1 text-muted"></i>
                            <h5 class="mt-2">No Zones Found</h5>
                        </div>
                    `);
                    return;
                }

                let html = '';
                $.each(zones, function(index, zone) {
                    let badgeClass = zone.status === 'active' ? 'bg-success' : 'bg-secondary';

                    html += `
                        <div class="acard">
                            <div class="acard-body">
                                <div class="acard-meta">
                                    <i class="bi bi-building"></i>
                                    ${escapeHtml(zone.corporation?.name || '-')}
                                    <span class="dot"></span>
                                    <i class="bi bi-hash"></i>
                                    ${escapeHtml(zone.zone_code)}
                                </div>

                                <h3 class="acard-title">
                                    ${escapeHtml(zone.zone_name)}
                                </h3>

                                ${zone.total_wards ? `
                                    <div class="mb-2">
                                        <i class="bi bi-diagram-2"></i>
                                        <span>Total Wards: ${zone.total_wards}</span>
                                    </div>
                                ` : ''}

                                <p class="acard-desc">
                                    ${escapeHtml(zone.description ?? 'No description available')}
                                </p>

                                ${zone.contact_person ? `
                                    <div class="small mb-1">
                                        <i class="bi bi-person"></i> ${escapeHtml(zone.contact_person)}
                                    </div>
                                ` : ''}

                                ${zone.phone ? `
                                    <div class="small mb-1">
                                        <i class="bi bi-telephone"></i> ${escapeHtml(zone.phone)}
                                    </div>
                                ` : ''}

                                <div class="acard-footer">
                                    <span class="badge ${badgeClass}">
                                        ${zone.status}
                                    </span>
                                </div>

                                <div class="d-flex gap-2 mt-3">
                                    <button class="btn btn-info btn-sm flex-fill view-btn"
                                            data-id="${zone.id}">
                                        <i class="bi bi-eye"></i> View
                                    </button>

                                    <button class="btn btn-warning btn-sm flex-fill edit-btn"
                                            data-id="${zone.id}">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>

                                    <button class="btn btn-danger btn-sm flex-fill delete-btn"
                                            data-id="${zone.id}"
                                            data-name="${escapeHtml(zone.zone_name)}">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </div>

                            </div>
                        </div>
                    `;
                });
                $('#zonesGrid').html(html);
            }

            // Render Laravel pagination
            function renderPagination(data) {
                if (!data || data.last_page <= 1) {
                    $('#paginationContainer').hide();
                    return;
                }

                $('#paginationContainer').show();
                let html = '<nav><ul class="pagination justify-content-center">';

                // Previous button
                if (data.current_page > 1) {
                    html += `<li class="page-item">
                        <a class="page-link" href="#" data-url="${data.prev_page_url}">&laquo; Previous</a>
                    </li>`;
                } else {
                    html += `<li class="page-item disabled">
                        <a class="page-link" href="#">&laquo; Previous</a>
                    </li>`;
                }

                // Page numbers
                for (let i = 1; i <= data.last_page; i++) {
                    let url = data.path + '?page=' + i;
                    if (i === data.current_page) {
                        html += `<li class="page-item active">
                            <a class="page-link" href="#">${i}</a>
                        </li>`;
                    } else {
                        html += `<li class="page-item">
                            <a class="page-link" href="#" data-url="${url}">${i}</a>
                        </li>`;
                    }
                }

                // Next button
                if (data.current_page < data.last_page) {
                    html += `<li class="page-item">
                        <a class="page-link" href="#" data-url="${data.next_page_url}">Next &raquo;</a>
                    </li>`;
                } else {
                    html += `<li class="page-item disabled">
                        <a class="page-link" href="#">Next &raquo;</a>
                    </li>`;
                }

                html += `</ul></nav>`;
                html += `<div class="text-center text-muted mt-2">
                    Showing ${data.from} to ${data.to} of ${data.total} zones
                </div>`;

                $('#paginationContainer').html(html);
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

            // Event handlers for pagination
            $(document).on('click', '.page-link', function(e) {
                e.preventDefault();
                let url = $(this).data('url');
                if (url) {
                    loadZones(url);
                }
            });

            // Search with debounce
            let searchTimeout;
            $('#zoneSearch').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => loadZones(), 500);
            });

            // Filter changes
            $('#statusFilter, #corpFilter').on('change', function() {
                loadZones();
            });

            // Add Zone button
            $('#addZoneBtn').on('click', function() {
                $('#zoneForm')[0].reset();
                $('#zoneId').val('');
                $('#zoneFormMethod').val('POST');
                $('#modalTitle').html('<i class="bi bi-building-add me-2"></i> Add Zone');
                $('#zoneSaveBtn').html('Save Zone');
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');

                // For commissioner, keep corporation field disabled
                @if(auth()->user()->role == 'commissioner')
                    $('#f_corp_id').prop('disabled', true);
                @endif

                $('#zoneModal').modal('show');
            });

            // Submit form with role-based URL
            $('#zoneForm').on('submit', function(e) {
                e.preventDefault();
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');

                let formData = new FormData(this);
                let zoneId = $('#zoneId').val();
                let method = $('#zoneFormMethod').val();
                let url;

                // Determine the URL based on role and method
                if (method === 'PUT') {
                    // For update
                    if (userRole === 'commissioner') {
                        url = "/commissioner/zones/" + zoneId;
                    } else {
                        url = "/admin/zones/" + zoneId;
                    }
                    formData.append('_method', 'PUT');
                } else {
                    // For store
                    if (userRole === 'commissioner') {
                        url = "/commissioner/zones";
                    } else {
                        url = "/admin/zones";
                    }
                }

                $('#zoneSaveBtn').prop('disabled', true).html(
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
                        $('#zoneSaveBtn').prop('disabled', false).html('Save Zone');
                        $('#zoneForm')[0].reset();
                        $('#zoneModal').modal('hide');
                        showFlashMessage(response.message || 'Zone saved successfully', 'success');
                        loadZones();
                    },
                    error: function(xhr) {
                        $('#zoneSaveBtn').prop('disabled', false).html('Save Zone');
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function(field, messages) {
                                $('[name="' + field + '"]').addClass('is-invalid');
                                $('#error-' + field).text(messages[0]);
                            });
                            showFlashMessage('Please fix validation errors', 'error');
                        } else {
                            showFlashMessage(xhr.responseJSON?.message || 'Something went wrong', 'error');
                        }
                    }
                });
            });

            // Edit button with role-based URL
            $(document).on('click', '.edit-btn', function() {
                let id = $(this).data('id');
                let url;

                if (userRole === 'commissioner') {
                    url = "/commissioner/zones/" + id;
                } else {
                    url = "/admin/zones/" + id;
                }

                $.ajax({
                    url: url,
                    type: "GET",
                    success: function(response) {
                        let zone = response.data;
                        $('#modalTitle').html('<i class="bi bi-pencil-square me-2"></i> Edit Zone');
                        $('#zoneId').val(zone.id);
                        $('#f_corp_id').val(zone.corp_id);
                        $('#f_zone_name').val(zone.zone_name);
                        $('#f_zone_code').val(zone.zone_code);
                        $('#f_total_wards').val(zone.total_wards);
                        $('#f_description').val(zone.description);
                        $('#f_contact_person').val(zone.contact_person);
                        $('#f_phone').val(zone.phone);
                        $('#f_email').val(zone.email);
                        $('#f_address').val(zone.address);
                        $('#f_pincode').val(zone.pincode);
                        $('#f_status').val(zone.status);
                        $('#zoneFormMethod').val('PUT');
                        $('#zoneSaveBtn').html('Update Zone');

                        // // For commissioner, keep corporation field disabled
                        // @if(auth()->user()->role == 'commissioner')
                        //     $('#f_corp_id').prop('disabled', true);
                        // @endif

                        $('#zoneModal').modal('show');
                    },
                    error: function() {
                        showFlashMessage('Failed to load zone data', 'error');
                    }
                });
            });

            // Delete button with role-based URL
            $(document).on('click', '.delete-btn', function() {
                let id = $(this).data('id');
                let name = $(this).data('name');
                $('#deleteZoneId').val(id);
                $('#deleteZoneName').text(`This will permanently remove "${name}" and all its data.`);
                $('#deleteModal').modal('show');
            });

            // Confirm delete with role-based URL
            $('#confirmDeleteBtn').on('click', function() {
                let id = $('#deleteZoneId').val();
                if (!id) return;

                let url;
                if (userRole === 'commissioner') {
                    url = "/commissioner/zones/" + id;
                } else {
                    url = "/admin/zones/" + id;
                }

                $.ajax({
                    url: url,
                    type: "DELETE",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $('#deleteModal').modal('hide');
                        showFlashMessage(response.message || 'Zone deleted successfully', 'success');
                        loadZones();
                    },
                    error: function(xhr) {
                        showFlashMessage(xhr.responseJSON?.message || 'Failed to delete zone', 'error');
                    }
                });
            });

            // View button
            $(document).on('click', '.view-btn', function() {
                let id = $(this).data('id');
                showFlashMessage('View details coming soon', 'info');
            });

            // Initial load
            loadZones();
        });
    </script>
@endpush

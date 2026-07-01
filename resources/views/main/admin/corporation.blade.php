@extends('layouts.office')

@section('title', 'Corporations')
@section('page_title', 'Corporations')

@section('content')

    <div class="ol-page-header">
        <div>
            <h1 class="ol-page-title">Corporations</h1>
            <p class="ol-page-sub">Manage all municipal corporations</p>
        </div>
    </div>

    <div class="data-toolbar d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="data-search">
            <input type="text" id="corpSearch" class="form-control" placeholder="Search by corporation name">
        </div>
        <div class="d-flex align-items-center gap-2">
            <select id="statusFilter" class="form-select app-select">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
            </select>
            {{-- Only show Add button for admin --}}
            @if (auth()->user()->role == 'admin')
                <button class="btn btn-success app-btn-sm" data-bs-toggle="modal" data-bs-target="#corpModal"
                    id="addCorpBtn">
                    <i class="bi bi-building-add"></i>
                    <span>Add Corporation</span>
                </button>
            @endif
        </div>
    </div>

    {{-- loading spinner --}}
    <div id="loadingSpinner" class="text-center py-5" style="display: none;">
        <div class="spinner-border text-success" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="card-grid" id="corporationsGrid">
        <!-- Corporations will be loaded here -->
    </div>

    <!-- Pagination Container -->
    <div id="paginationContainer" class="d-flex justify-content-center mt-4" style="display: none;">
        <nav>
            <ul class="pagination" id="paginationList">
                <!-- Pagination will be loaded here -->
            </ul>
        </nav>
    </div>

    <!-- Corporation Modal (Add/Edit) -->
    <div class="modal fade" id="corpModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="bi bi-building-add me-2"></i>
                        Add Corporation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="corpForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="corpId">
                    <input type="hidden" name="_method" id="formMethod" value="POST">
                    <div class="modal-body" style="max-height:70vh;overflow-y:auto;">
                        <div class="card mb-3">
                            <div class="card-header">Basic Information</div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Corporation Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="name" id="f_name" class="form-control">
                                        <div class="invalid-feedback" id="error-name"></div>
                                    </div>
                                    @if (auth()->user()->role == 'admin')
                                        <div class="col-md-6">
                                            <label class="form-label">Corporation Code <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="code" id="f_code" class="form-control">
                                            <div class="invalid-feedback" id="error-code"></div>
                                        </div>
                                    @endif
                                    <div class="col-md-4">
                                        <label class="form-label">State <span class="text-danger">*</span></label>
                                        <input type="text" name="state" id="f_state" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">District <span class="text-danger">*</span></label>
                                        <input type="text" name="district" id="f_district" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Pincode <span class="text-danger">*</span></label>
                                        <input type="text" name="pincode" id="f_pincode" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Type</label>
                                        <select name="type" id="f_type" class="form-select">
                                            <option value="Municipal Corporation">Municipal Corporation</option>
                                            <option value="Municipality">Municipality</option>
                                            <option value="Town Panchayat">Town Panchayat</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                        <select name="status" id="f_status" class="form-select">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="suspended">Suspended</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Description <span class="text-danger">*</span></label>
                                        <textarea name="description" id="f_description" rows="3" class="form-control"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">Files & Uploads</div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Logo <span class="text-danger">*</span></label>
                                        <input type="file" name="image" id="f_image" class="form-control">
                                        <div class="invalid-feedback" id="error-image"></div>
                                        <div id="imagePreview" class="mt-2" style="display: none;">
                                            <img src="" alt="Preview" style="max-height: 100px;">
                                        </div>
                                    </div>
                                    @if (auth()->user()->role == 'admin')
                                        <div class="col-md-6">
                                            <label class="form-label">Boundary File <span
                                                    class="text-danger">*</span></label>
                                            <input type="file" name="boundary_file" id="f_boundary"
                                                class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">MIS File</label>
                                            <input type="file" name="mis_file" id="f_mis" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Water Tax File</label>
                                            <input type="file" name="water_tax_file" id="f_water"
                                                class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">UGD Tax File</label>
                                            <input type="file" name="ugd_tax_file" id="f_ugd"
                                                class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Professional Tax File</label>
                                            <input type="file" name="professional_tax_file" id="f_pt"
                                                class="form-control">
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success" id="corpSaveBtn">Save Corporation</button>
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
                    <div
                        style="width:56px;height:56px;border-radius:50%;background:rgba(239,68,68,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                        <i class="bi bi-trash3" style="font-size:22px;color:#ef4444;"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Delete Corporation?</h6>
                    <p class="text-muted" style="font-size:0.8rem;" id="deleteCorpName"></p>
                    <input type="hidden" id="deleteCorpId">
                </div>
                <div class="modal-footer border-0 justify-content-center gap-2 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger px-4" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-building me-2"></i>
                        Corporation Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewModalBody">
                    <!-- Content loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let currentPage = 1;
            let totalPages = 1;
            let isLoading = false;
            let userRole = '{{ auth()->user()->role }}';

            // Load corporations with pagination
            function loadCorporations(page = 1) {
                if (isLoading) return;

                isLoading = true;
                $('#loadingSpinner').show();

                let search = $("#corpSearch").val();
                let status = $("#statusFilter").val();
                let url;

                // Determine the URL based on role
                if (userRole === 'commissioner') {
                    url = "{{ route('commissioner.corporations.list') }}";
                } else {
                    url = "{{ route('admin.corporations.list') }}";
                }

                $.ajax({
                    url: url,
                    type: "GET",
                    data: {
                        corp_name: search,
                        status: status,
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
                        showFlashMessage('Failed to load corporations', 'error');
                        isLoading = false;
                        $('#loadingSpinner').hide();
                    }
                });
            }

            // Render cards
            function renderCards(corporations) {
                if (!corporations || corporations.length === 0) {
                    $('#corporationsGrid').html(`
                        <div class="text-center py-5 w-100">
                            <i class="bi bi-building fs-1 text-muted"></i>
                            <h5 class="mt-2">No Corporations Found</h5>
                        </div>
                    `);
                    return;
                }

                let html = '';

                $.each(corporations, function(index, corp) {
                    const assetBase = "{{ asset('') }}";

                    let imageUrl = corp.image ?
                        assetBase + corp.image :
                        assetBase + 'images/default-corp.png';

                    let badgeClass = {
                        active: 'bg-success',
                        inactive: 'bg-secondary',
                        suspended: 'bg-danger'
                    } [corp.status] || 'bg-secondary';

                    html += `
                        <div class="acard">
                            <div class="acard-img-wrap">
                                <img src="${imageUrl}"
                                    onerror="this.src='${assetBase}images/default-corp.png'">

                                <div class="acard-overlay"></div>

                                <span class="acard-tag">
                                    ${corp.type ?? 'Corporation'}
                                </span>
                            </div>

                            <div class="acard-body">

                                <div class="acard-meta">
                                    <i class="bi bi-geo-alt"></i>
                                    ${corp.state ?? '-'}
                                    <span class="dot"></span>
                                    ${corp.district ?? '-'}
                                </div>

                                <h3 class="acard-title">
                                    ${escapeHtml(corp.name)}
                                </h3>

                                <p class="acard-desc">
                                    ${escapeHtml(corp.description ?? 'No description available')}
                                </p>

                                <div class="acard-footer">
                                    <span class="acard-author">
                                        ${corp.code}
                                    </span>

                                    <span class="badge ${badgeClass}">
                                        ${corp.status}
                                    </span>
                                </div>

                                <div class="d-flex gap-2 mt-3">
                                    <button class="btn btn-info btn-sm flex-fill view-btn"
                                            data-id="${corp.id}">
                                        <i class="bi bi-eye"></i> View
                                    </button>

                                    <button class="btn btn-warning btn-sm flex-fill edit-btn"
                                            data-id="${corp.id}">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>

                                    ${userRole === 'admin' ? `
                                            <button class="btn btn-danger btn-sm flex-fill delete-btn"
                                                    data-id="${corp.id}"
                                                    data-name="${escapeHtml(corp.name)}">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        ` : ''}
                                </div>

                            </div>
                        </div>
                    `;
                });

                $('#corporationsGrid').html(html);
            }

            // Render pagination
            function renderPagination(pagination) {
                if (!pagination || pagination.last_page <= 1) {
                    $('#paginationContainer').hide();
                    return;
                }

                $('#paginationContainer').show();
                let html = '';

                // Previous
                if (pagination.current_page > 1) {
                    html +=
                        `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page - 1}">&laquo; Previous</a></li>`;
                } else {
                    html +=
                        `<li class="page-item disabled"><a class="page-link" href="#">&laquo; Previous</a></li>`;
                }

                // First page
                if (pagination.current_page > 3) {
                    html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
                    if (pagination.current_page > 4) {
                        html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`;
                    }
                }

                // Pages around current
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

                // Last page
                if (pagination.current_page < pagination.last_page - 2) {
                    if (pagination.current_page < pagination.last_page - 3) {
                        html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`;
                    }
                    html +=
                        `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.last_page}">${pagination.last_page}</a></li>`;
                }

                // Next
                if (pagination.current_page < pagination.last_page) {
                    html +=
                        `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page + 1}">Next &raquo;</a></li>`;
                } else {
                    html += `<li class="page-item disabled"><a class="page-link" href="#">Next &raquo;</a></li>`;
                }

                $('#paginationList').html(html);

                // Add info text
                let start = pagination.from || ((pagination.current_page - 1) * pagination.per_page + 1);
                let end = pagination.to || Math.min(pagination.current_page * pagination.per_page, pagination
                    .total);
                let infoHtml = `<div class="text-center text-muted mt-3">
                            Showing ${start} to ${end} of ${pagination.total} corporations
                        </div>`;

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
                    loadCorporations(page);
                }
            });

            let searchTimeout;
            $('#corpSearch').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => loadCorporations(1), 500);
            });

            $('#statusFilter').on('change', function() {
                loadCorporations(1);
            });

            // Add Corporation - Only for admin
            $('#addCorpBtn').on('click', function() {
                if (userRole !== 'admin') {
                    showFlashMessage('You do not have permission to add corporations', 'error');
                    return;
                }
                resetForm();
                $('#modalTitle').html('<i class="bi bi-building-add me-2"></i> Add Corporation');
                $('#corpSaveBtn').html('Save Corporation');
                $('#corpModal').modal('show');
            });

            function resetForm() {
                $('#corpForm')[0].reset();
                $('#corpId').val('');
                $('#formMethod').val('POST');
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');
                $('#imagePreview').hide();
            }

            // Image preview
            $('#f_image').on('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#imagePreview img').attr('src', e.target.result);
                        $('#imagePreview').show();
                    };
                    reader.readAsDataURL(file);
                } else {
                    $('#imagePreview').hide();
                }
            });

            // Submit form with role-based URL
            $('#corpForm').on('submit', function(e) {
                e.preventDefault();
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');

                let formData = new FormData(this);
                let corpId = $('#corpId').val();
                let method = $('#formMethod').val();
                let url;

                if (method === 'PUT') {
                    if (userRole === 'commissioner') {
                        url = "/commissioner/corporations/" + corpId;
                    } else {
                        url = "/admin/corporations/" + corpId;
                    }
                    formData.append('_method', 'PUT');
                } else {
                    if (userRole === 'commissioner') {
                        url = "/commissioner/corporations";
                    } else {
                        url = "/admin/corporations";
                    }
                }

                $('#corpSaveBtn').prop('disabled', true).html(
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
                        $('#corpSaveBtn').prop('disabled', false).html('Save Corporation');
                        $('#corpForm')[0].reset();
                        $('#corpModal').modal('hide');
                        showFlashMessage(response.message || 'Corporation saved successfully',
                            'success');
                        loadCorporations(currentPage);
                    },
                    error: function(xhr) {
                        $('#corpSaveBtn').prop('disabled', false).html('Save Corporation');
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function(field, messages) {
                                $('[name="' + field + '"]').addClass('is-invalid');
                                $('#error-' + field).text(messages[0]);
                            });
                            showFlashMessage('Please fix validation errors', 'error');
                        } else if (xhr.status === 403) {
                            showFlashMessage(xhr.responseJSON?.message || 'Permission denied',
                                'error');
                        } else {
                            // Show the actual error message from the server
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
                    url = "/commissioner/corporations/" + id;
                } else {
                    url = "/admin/corporations/" + id;
                }

                $.ajax({
                    url: url,
                    type: "GET",
                    success: function(response) {
                        let corp = response.data;
                        $('#modalTitle').html(
                            '<i class="bi bi-pencil-square me-2"></i> Edit Corporation');
                        $('#corpId').val(corp.id);
                        $('#f_name').val(corp.name);
                        if (userRole === 'admin') {
                            $('#f_code').val(corp.code);
                        }
                        $('#f_state').val(corp.state);
                        $('#f_district').val(corp.district);
                        $('#f_pincode').val(corp.pincode);
                        $('#f_type').val(corp.type);
                        $('#f_status').val(corp.status);
                        $('#f_description').val(corp.description);

                        // Show existing image if any
                        if (corp.image) {
                            $('#imagePreview img').attr('src', "{{ asset('') }}" + corp
                                .image);
                            $('#imagePreview').show();
                        }

                        $('#formMethod').val('PUT');
                        $('#corpSaveBtn').html('Update Corporation');

                        // Disable code field for commissioner
                        if (userRole === 'commissioner') {
                            $('#f_code').prop('disabled', true);
                        } else {
                            $('#f_code').prop('disabled', false);
                        }

                        $('#corpModal').modal('show');
                    },
                    error: function() {
                        showFlashMessage('Failed to load corporation data', 'error');
                    }
                });
            });

            // Delete button - Only for admin
            $(document).on('click', '.delete-btn', function() {
                if (userRole !== 'admin') {
                    showFlashMessage('You do not have permission to delete corporations', 'error');
                    return;
                }
                let id = $(this).data('id');
                let name = $(this).data('name');
                $('#deleteCorpId').val(id);
                $('#deleteCorpName').text(`This will permanently remove "${name}" and all its data.`);
                $('#deleteModal').modal('show');
            });

            // Confirm delete
            $('#confirmDeleteBtn').on('click', function() {
                let id = $('#deleteCorpId').val();
                if (!id) return;

                $.ajax({
                    url: "/admin/corporations/" + id,
                    type: "DELETE",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $('#deleteModal').modal('hide');
                        showFlashMessage(response.message || 'Corporation deleted successfully',
                            'success');
                        loadCorporations(1);
                    },
                    error: function(xhr) {
                        showFlashMessage(xhr.responseJSON?.message ||
                            'Failed to delete corporation', 'error');
                    }
                });
            });

            // View button
            $(document).on('click', '.view-btn', function() {
                let id = $(this).data('id');
                let url;

                if (userRole === 'commissioner') {
                    url = "/commissioner/corporations/" + id;
                } else {
                    url = "/admin/corporations/" + id;
                }

                $.ajax({
                    url: url,
                    type: "GET",
                    success: function(response) {
                        let corp = response.data;
                        let html = `
                            <div class="row">
                                <div class="col-md-4 text-center mb-3">
                                    <img src="{{ asset('') }}${corp.image || 'images/default-corp.png'}"
                                         alt="${corp.name}"
                                         style="max-height: 150px; border-radius: 8px;">
                                </div>
                                <div class="col-md-8">
                                    <h4>${escapeHtml(corp.name)}</h4>
                                    <p><strong>Code:</strong> ${corp.code}</p>
                                    <p><strong>Type:</strong> ${corp.type}</p>
                                    <p><strong>Status:</strong> ${corp.status}</p>
                                    <p><strong>State:</strong> ${corp.state || '-'}</p>
                                    <p><strong>District:</strong> ${corp.district || '-'}</p>
                                    <p><strong>Pincode:</strong> ${corp.pincode || '-'}</p>
                                    <p><strong>Description:</strong> ${escapeHtml(corp.description || 'No description')}</p>
                                </div>
                            </div>
                        `;
                        $('#viewModalBody').html(html);
                        $('#viewModal').modal('show');
                    },
                    error: function() {
                        showFlashMessage('Failed to load corporation details', 'error');
                    }
                });
            });

            // Initial load
            loadCorporations(1);
        });
    </script>
@endpush

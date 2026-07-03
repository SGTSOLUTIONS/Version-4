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
                                        <div class="invalid-feedback" id="error-state"></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">District <span class="text-danger">*</span></label>
                                        <input type="text" name="district" id="f_district" class="form-control">
                                        <div class="invalid-feedback" id="error-district"></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Pincode <span class="text-danger">*</span></label>
                                        <input type="text" name="pincode" id="f_pincode" class="form-control">
                                        <div class="invalid-feedback" id="error-pincode"></div>
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
                                        <div class="invalid-feedback" id="error-status"></div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Description <span class="text-danger">*</span></label>
                                        <textarea name="description" id="f_description" rows="3" class="form-control"></textarea>
                                        <div class="invalid-feedback" id="error-description"></div>
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
                                        <input type="file" name="image" id="f_image" class="form-control"
                                            accept="image/*">
                                        <div class="invalid-feedback" id="error-image"></div>
                                        <div id="imagePreview" class="mt-2" style="display: none;">
                                            <img src="" alt="Preview" style="max-height: 100px;">
                                            <button type="button" class="btn btn-sm btn-danger ms-2"
                                                onclick="removeImagePreview()">Remove</button>
                                        </div>
                                    </div>
                                    @if (auth()->user()->role == 'admin')
                                        <div class="col-md-6">
                                            <label class="form-label">Boundary File <span
                                                    class="text-danger">*</span></label>
                                            <input type="file" name="boundary_file" id="f_boundary"
                                                class="form-control" accept=".json,.geojson">
                                            <div class="invalid-feedback" id="error-boundary_file"></div>
                                            <small class="text-muted">Upload GeoJSON format only</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">MIS File</label>
                                            <input type="file" name="mis_file" id="f_mis" class="form-control"
                                                accept=".xlsx,.xls,.csv">
                                            <div class="invalid-feedback" id="error-mis_file"></div>
                                            <small class="text-muted">Excel/CSV file (max 100MB)</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Water Tax File</label>
                                            <input type="file" name="water_tax_file" id="f_water"
                                                class="form-control" accept=".xlsx,.xls,.csv">
                                            <div class="invalid-feedback" id="error-water_tax_file"></div>
                                            <small class="text-muted">Excel/CSV file (max 100MB)</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">UGD Tax File</label>
                                            <input type="file" name="ugd_tax_file" id="f_ugd"
                                                class="form-control" accept=".xlsx,.xls,.csv">
                                            <div class="invalid-feedback" id="error-ugd_tax_file"></div>
                                            <small class="text-muted">Excel/CSV file (max 100MB)</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Professional Tax File</label>
                                            <input type="file" name="professional_tax_file" id="f_pt"
                                                class="form-control" accept=".xlsx,.xls,.csv">
                                            <div class="invalid-feedback" id="error-professional_tax_file"></div>
                                            <small class="text-muted">Excel/CSV file (max 100MB)</small>
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

    <!-- Import Statistics Modal -->
    <div class="modal fade" id="importStatsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-bar-chart-fill me-2"></i>
                        Import Results
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="importStatsBody">
                    <!-- Dynamic content -->
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
        /* Flash Message Styles */
        .flash-message-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 450px;
            width: 100%;
        }

        .flash-message {
            padding: 16px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideInRight 0.4s ease;
            margin-bottom: 10px;
        }

        .flash-message i {
            font-size: 20px;
            flex-shrink: 0;
        }

        .flash-message .flash-content {
            flex: 1;
            font-size: 14px;
            word-break: break-word;
        }

        .flash-message .flash-close {
            background: transparent;
            border: none;
            color: inherit;
            font-size: 18px;
            cursor: pointer;
            opacity: 0.7;
            padding: 0 5px;
        }

        .flash-message .flash-close:hover {
            opacity: 1;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }

            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .flash-message.slide-out {
            animation: slideOutRight 0.4s ease forwards;
        }

        /* Card Grid Styles */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .acard {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
        }

        .acard:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        /* Card Image Styles - Fully Responsive with Cover Fit */
        .acard-img-wrap {
            position: relative;
            width: 100%;
            /* padding-bottom: 60%; */
            /* 5:3 aspect ratio - adjust as needed */
            background: #f0f2f5;
            overflow: hidden;
        }

        .acard-img-wrap img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }


        /* If you prefer a fixed height approach with better responsiveness */
        .acard-img-wrap-fixed {
            position: relative;
            height: 180px;
            background: #f0f2f5;
            overflow: hidden;
        }

        .acard-img-wrap-fixed img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        @media (max-width: 768px) {
            .acard-img-wrap-fixed {
                height: 180px;
            }
        }

        @media (max-width: 576px) {
            .acard-img-wrap-fixed {
                height: 160px;
            }
        }

        @media (max-width: 400px) {
            .acard-img-wrap-fixed {
                height: 140px;
            }
        }

        /* For images that fail to load */
        .acard-img-wrap img.error,
        .acard-img-wrap-fixed img.error {
            object-fit: contain;
            padding: 20px;
            background: #f8f9fa;
        }

        /* Optional: Center the image if it doesn't cover properly */
        .acard-img-wrap img,
        .acard-img-wrap-fixed img {
            object-position: center center;
        }

        .acard-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent 50%, rgba(0, 0, 0, 0.3));
        }

        .acard-tag {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(22, 121, 171, 0.9);
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .acard-body {
            padding: 16px 18px 18px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .acard-meta {
            font-size: 12px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 8px;
        }

        .acard-meta .dot {
            width: 3px;
            height: 3px;
            border-radius: 50%;
            background: #d1d5db;
            display: inline-block;
        }

        .acard-title {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin: 0 0 6px 0;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .acard-desc {
            font-size: 13px;
            color: #6b7280;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 12px;
            flex: 1;
        }

        .acard-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 10px;
            border-top: 1px solid #f3f4f6;
            margin-top: auto;
        }

        .acard-author {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
        }

        .acard-footer .badge {
            font-size: 11px;
            padding: 4px 12px;
            border-radius: 20px;
        }

        /* Toolbar Styles */
        .data-toolbar {
            margin: 20px 0 10px;
        }

        .data-search input {
            min-width: 250px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            padding: 8px 14px;
        }

        .app-select {
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            padding: 8px 14px;
            min-width: 140px;
        }

        .btn-success.app-btn-sm {
            background: #1679AB;
            border: none;
            border-radius: 8px;
            padding: 8px 18px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-success.app-btn-sm:hover {
            background: #125a82;
        }

        /* Invalid feedback */
        .invalid-feedback {
            font-size: 12px;
            margin-top: 4px;
        }

        .is-invalid {
            border-color: #ef4444 !important;
        }

        /* Pagination */
        .pagination .page-item.active .page-link {
            background-color: #1679AB;
            border-color: #1679AB;
            color: #fff;
        }

        .pagination .page-link {
            color: #1679AB;
            border-radius: 6px;
            margin: 0 2px;
        }

        .pagination .page-item.disabled .page-link {
            color: #9ca3af;
        }

        /* Import Stats Card */
        .import-stat-card {
            background: #f8fafc;
            border-radius: 10px;
            padding: 16px;
            text-align: center;
            transition: all 0.2s;
            border: 1px solid #e5e7eb;
        }

        .import-stat-card:hover {
            border-color: #1679AB;
            background: #f0f7ff;
        }

        .import-stat-card .stat-icon {
            font-size: 28px;
            color: #1679AB;
            margin-bottom: 8px;
        }

        .import-stat-card .stat-title {
            font-size: 13px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 6px;
        }

        .import-stat-card .stat-numbers {
            display: flex;
            justify-content: center;
            gap: 16px;
            font-size: 13px;
        }

        .import-stat-card .stat-numbers .num-inserted {
            color: #10b981;
            font-weight: 600;
        }

        .import-stat-card .stat-numbers .num-updated {
            color: #f59e0b;
            font-weight: 600;
        }

        .import-stat-card .stat-numbers .num-skipped {
            color: #ef4444;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .card-grid {
                grid-template-columns: 1fr;
            }

            .data-toolbar {
                flex-direction: column;
                align-items: stretch !important;
            }

            .data-search input {
                min-width: 100%;
            }

            .flash-message-container {
                max-width: calc(100% - 20px);
                right: 10px;
                top: 10px;
            }
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

            // =============================================
            // FLASH MESSAGE SYSTEM
            // =============================================
            function showFlashMessage(message, type = 'success', duration = 5000) {
                const colors = {
                    success: '#10b981',
                    error: '#ef4444',
                    warning: '#f59e0b',
                    info: '#3b82f6'
                };

                const icons = {
                    success: 'bi-check-circle-fill',
                    error: 'bi-exclamation-circle-fill',
                    warning: 'bi-exclamation-triangle-fill',
                    info: 'bi-info-circle-fill'
                };

                // Remove existing flash messages
                $('.flash-message-container').remove();

                const container = `
            <div class="flash-message-container">
                <div class="flash-message" style="background: ${colors[type] || colors.info}; color: white;">
                    <i class="bi ${icons[type] || icons.info}"></i>
                    <span class="flash-content">${message}</span>
                    <button class="flash-close" onclick="$(this).closest('.flash-message-container').remove()">&times;</button>
                </div>
            </div>
        `;

                $('body').append(container);

                // Auto dismiss
                if (duration > 0) {
                    setTimeout(() => {
                        const container = $('.flash-message-container');
                        if (container.length) {
                            container.find('.flash-message').addClass('slide-out');
                            setTimeout(() => container.remove(), 400);
                        }
                    }, duration);
                }
            }

            // =============================================
            // IMAGE PREVIEW
            // =============================================
            window.removeImagePreview = function() {
                $('#imagePreview').hide();
                $('#imagePreview img').attr('src', '');
                $('#f_image').val('');
            };

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

            // =============================================
            // LOAD CORPORATIONS
            // =============================================
            function loadCorporations(page = 1) {
                if (isLoading) return;

                isLoading = true;
                $('#loadingSpinner').show();

                let search = $("#corpSearch").val();
                let status = $("#statusFilter").val();
                let url;

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

            // =============================================
            // RENDER CARDS
            // =============================================
            function renderCards(corporations) {
                if (!corporations || corporations.length === 0) {
                    $('#corporationsGrid').html(`
                <div class="text-center py-5 w-100">
                    <i class="bi bi-building fs-1 text-muted"></i>
                    <h5 class="mt-2">No Corporations Found</h5>
                    <p class="text-muted">Try adjusting your search or filters</p>
                </div>
            `);
                    return;
                }

                let html = '';
                const assetBase = "{{ asset('') }}";

                $.each(corporations, function(index, corp) {
                    let imageUrl = corp.image ? assetBase + corp.image : assetBase +
                        'images/default-corp.png';

                    let badgeClass = {
                        active: 'bg-success',
                        inactive: 'bg-secondary',
                        suspended: 'bg-danger'
                    } [corp.status] || 'bg-secondary';

                    html += `
                <div class="acard">
                    <div class="acard-img-wrap">
                        <img src="${imageUrl}"
                            onerror="this.src='${assetBase}images/default-corp.png'"
                            alt="${escapeHtml(corp.name)}">
                        <div class="acard-overlay"></div>
                        <span class="acard-tag">${corp.type ?? 'Corporation'}</span>
                    </div>
                    <div class="acard-body">
                        <div class="acard-meta">
                            <i class="bi bi-geo-alt"></i>
                            ${escapeHtml(corp.state ?? '-')}
                            <span class="dot"></span>
                            ${escapeHtml(corp.district ?? '-')}
                        </div>
                        <h3 class="acard-title">${escapeHtml(corp.name)}</h3>
                        <p class="acard-desc">${escapeHtml(corp.description ?? 'No description available')}</p>
                        <div class="acard-footer">
                            <span class="acard-author">${escapeHtml(corp.code)}</span>
                            <span class="badge ${badgeClass}">${corp.status}</span>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-info btn-sm flex-fill view-btn" data-id="${corp.id}">
                                <i class="bi bi-eye"></i> View
                            </button>
                            <button class="btn btn-warning btn-sm flex-fill edit-btn" data-id="${corp.id}">
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

            // =============================================
            // RENDER PAGINATION
            // =============================================
            function renderPagination(pagination) {
                if (!pagination || pagination.last_page <= 1) {
                    $('#paginationContainer').hide();
                    $('#paginationInfo').remove();
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

                let infoHtml = `<div class="text-center text-muted mt-3" id="paginationInfo">
            Showing ${start} to ${end} of ${pagination.total} corporations
        </div>`;

                if ($('#paginationInfo').length === 0) {
                    $('#paginationContainer').after(infoHtml);
                } else {
                    $('#paginationInfo').html(infoHtml);
                }
            }

            // =============================================
            // UTILITY FUNCTIONS
            // =============================================
            function escapeHtml(text) {
                if (!text) return '';
                return String(text)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function resetForm() {
                $('#corpForm')[0].reset();
                $('#corpId').val('');
                $('#formMethod').val('POST');
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');
                $('#imagePreview').hide();
                $('#imagePreview img').attr('src', '');
                $('#f_code').prop('disabled', false);
            }

            // =============================================
            // IMPORT STATS MODAL
            // =============================================
            function showImportStats(stats) {
                let html = '<div class="row g-3">';
                let hasErrors = false;
                let hasData = false;

                const importTypes = {
                    'mis': {
                        label: 'MIS Data',
                        icon: 'bi-building',
                        color: '#1679AB'
                    },
                    'water_tax': {
                        label: 'Water Tax',
                        icon: 'bi-droplet',
                        color: '#3b82f6'
                    },
                    'ugd_tax': {
                        label: 'UGD Tax',
                        icon: 'bi-pipe',
                        color: '#8b5cf6'
                    },
                    'professional_tax': {
                        label: 'Professional Tax',
                        icon: 'bi-briefcase',
                        color: '#f59e0b'
                    }
                };

                $.each(stats, function(key, value) {
                    if (value.error) {
                        hasErrors = true;
                        html += `
                    <div class="col-12">
                        <div class="alert alert-danger mb-0">
                            <strong>${importTypes[key]?.label || key}:</strong><br>
                            ❌ ${escapeHtml(value.message)}
                        </div>
                    </div>
                `;
                    } else if (value.inserted !== undefined || value.updated !== undefined || value
                        .skipped !== undefined) {
                        hasData = true;
                        let total = (value.inserted || 0) + (value.updated || 0);
                        let color = importTypes[key]?.color || '#6b7280';

                        html += `
                    <div class="col-md-6">
                        <div class="import-stat-card">
                            <div class="stat-icon" style="color: ${color}">
                                <i class="bi ${importTypes[key]?.icon || 'bi-file-spreadsheet'}"></i>
                            </div>
                            <div class="stat-title">${importTypes[key]?.label || key}</div>
                            <div class="stat-numbers">
                                <span>Total: <strong>${total}</strong></span>
                                <span class="num-inserted">+${value.inserted || 0}</span>
                                <span class="num-updated">↻${value.updated || 0}</span>
                                ${value.skipped > 0 ? `<span class="num-skipped">⚠${value.skipped}</span>` : ''}
                            </div>
                            ${value.skipped > 0 && value.skipped_details && value.skipped_details.length > 0 ? `
                                    <div class="mt-2 text-start" style="font-size: 12px;">
                                        <button class="btn btn-sm btn-outline-danger" onclick="toggleSkippedDetails(this)">
                                            View skipped details (${value.skipped})
                                        </button>
                                        <div class="skipped-details" style="display:none; margin-top: 6px; max-height: 100px; overflow-y: auto; background: #fef2f2; padding: 8px; border-radius: 4px; font-size: 11px; color: #991b1b;">
                                            ${value.skipped_details.map(d => `Row ${d.row}: ${escapeHtml(d.reason)}`).join('<br>')}
                                            ${value.skipped > value.skipped_details.length ? `<br>... and ${value.skipped - value.skipped_details.length} more` : ''}
                                        </div>
                                    </div>
                                ` : ''}
                        </div>
                    </div>
                `;
                    }
                });

                if (!hasData && !hasErrors) {
                    html = `
                <div class="text-center py-4">
                    <i class="bi bi-check-circle" style="font-size: 48px; color: #10b981;"></i>
                    <h6 class="mt-2">No files were imported</h6>
                    <p class="text-muted">The corporation was created without any data imports.</p>
                </div>
            `;
                }

                if (hasErrors && hasData) {
                    html += `
                <div class="col-12 mt-2">
                    <div class="alert alert-warning mb-0">
                        ⚠️ Some imports had errors. Please check the details above.
                    </div>
                </div>
            `;
                }

                if (!hasErrors && hasData) {
                    html += `
                <div class="col-12 mt-2">
                    <div class="alert alert-success mb-0">
                        ✅ All imports completed successfully!
                    </div>
                </div>
            `;
                }

                html += '</div>';
                $('#importStatsBody').html(html);
                $('#importStatsModal').modal('show');
            }

            // Toggle skipped details
            window.toggleSkippedDetails = function(btn) {
                const details = $(btn).next('.skipped-details');
                details.slideToggle();
                $(btn).text(details.is(':visible') ? 'Hide skipped details' : 'View skipped details');
            };

            // =============================================
            // EVENT HANDLERS
            // =============================================
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

            // =============================================
            // ADD CORPORATION
            // =============================================
            $('#addCorpBtn').on('click', function() {
                if (userRole !== 'admin') {
                    showFlashMessage('You do not have permission to add corporations', 'error');
                    return;
                }
                resetForm();
                $('#modalTitle').html('<i class="bi bi-building-add me-2"></i> Add Corporation');
                $('#corpSaveBtn').html('Save Corporation');
                $('.modal-header').removeClass('bg-warning');
                $('#corpModal').modal('show');
            });

            // =============================================
            // FORM SUBMISSION
            // =============================================
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

                // Validate required fields before sending
                let hasError = false;
                let requiredFields = ['name', 'state', 'district', 'pincode', 'status', 'description'];
                if (userRole === 'admin' && method !== 'PUT') {
                    requiredFields.push('code');
                }
                if (method !== 'PUT') {
                    // Image is required for new corporations
                    let imageFile = $('#f_image')[0].files[0];
                    if (!imageFile) {
                        showFlashMessage('Please upload a corporation logo', 'error');
                        $('#f_image').addClass('is-invalid');
                        $('#error-image').text('Logo is required');
                        hasError = true;
                    }
                }

                if (hasError) return;

                $('#corpSaveBtn').prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
                );

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
                        $('#corpSaveBtn').prop('disabled', false).html(
                            method === 'PUT' ? 'Update Corporation' : 'Save Corporation'
                        );

                        showFlashMessage(response.message || 'Corporation saved successfully',
                            'success');

                        // Show import stats if available
                        if (response.import_stats && Object.keys(response.import_stats).length >
                            0) {
                            showImportStats(response.import_stats);
                        }

                        $('#corpForm')[0].reset();
                        $('#imagePreview').hide();
                        $('#corpModal').modal('hide');
                        loadCorporations(currentPage);
                    },
                    error: function(xhr) {
                        $('#corpSaveBtn').prop('disabled', false).html(
                            method === 'PUT' ? 'Update Corporation' : 'Save Corporation'
                        );

                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            let errorMessages = [];

                            $.each(errors, function(field, messages) {
                                let input = $('[name="' + field + '"]');
                                if (input.length) {
                                    input.addClass('is-invalid');
                                    let errorContainer = $('#error-' + field);
                                    if (errorContainer.length) {
                                        errorContainer.text(messages[0]);
                                    } else {
                                        // Fallback: find closest error container
                                        let closestError = input.closest(
                                                '.col-md-6, .col-md-4, .col-12')
                                            .find('.invalid-feedback');
                                        if (closestError.length) {
                                            closestError.text(messages[0]);
                                        }
                                    }
                                }
                                errorMessages.push(messages[0]);
                            });

                            if (errorMessages.length) {
                                showFlashMessage(errorMessages[0], 'error');
                            }
                        } else if (xhr.status === 403) {
                            showFlashMessage(xhr.responseJSON?.message || 'Permission denied',
                                'error');
                        } else {
                            let errorMessage = xhr.responseJSON?.message ||
                                'Something went wrong';
                            showFlashMessage(errorMessage, 'error');
                            console.error('Server error:', xhr.responseJSON);

                            // Show detailed error in modal
                            if (xhr.responseJSON?.errors) {
                                let detailHtml = '<div class="text-danger mt-2"><small>';
                                $.each(xhr.responseJSON.errors, function(field, msgs) {
                                    detailHtml +=
                                        `<div>${field}: ${msgs.join(', ')}</div>`;
                                });
                                detailHtml += '</small></div>';
                                $('.modal-body').append(detailHtml);
                            }
                        }
                    }
                });
            });

            // =============================================
            // EDIT BUTTON
            // =============================================
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
                        resetForm();

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
                        $('#f_type').val(corp.type || 'Municipal Corporation');
                        $('#f_status').val(corp.status);
                        $('#f_description').val(corp.description);

                        if (corp.image) {
                            let assetBase = "{{ asset('') }}";
                            $('#imagePreview img').attr('src', assetBase + corp.image);
                            $('#imagePreview').show();
                        } else {
                            $('#imagePreview').hide();
                        }

                        $('#formMethod').val('PUT');
                        $('#corpSaveBtn').html('Update Corporation');

                        if (userRole === 'commissioner') {
                            $('#f_code').prop('disabled', true);
                        } else {
                            $('#f_code').prop('disabled', false);
                        }

                        // Remove required attribute from image for edit
                        $('#f_image').prop('required', false);

                        $('#corpModal').modal('show');
                    },
                    error: function(xhr) {
                        showFlashMessage('Failed to load corporation data', 'error');
                    }
                });
            });

            // =============================================
            // DELETE BUTTON
            // =============================================
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

            $('#confirmDeleteBtn').on('click', function() {
                let id = $('#deleteCorpId').val();
                if (!id) return;

                $(this).prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Deleting...'
                );

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
                        $('#confirmDeleteBtn').prop('disabled', false).html('Delete');
                    },
                    error: function(xhr) {
                        showFlashMessage(xhr.responseJSON?.message ||
                            'Failed to delete corporation', 'error');
                        $('#confirmDeleteBtn').prop('disabled', false).html('Delete');
                    }
                });
            });

            // =============================================
            // VIEW BUTTON
            // =============================================
            $(document).on('click', '.view-btn', function() {
                let id = $(this).data('id');
                let url;

                if (userRole === 'commissioner') {
                    url = "/commissioner/corporations/" + id;
                } else {
                    url = "/admin/corporations/" + id;
                }

                $('#viewModalBody').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `);
                $('#viewModal').modal('show');

                $.ajax({
                    url: url,
                    type: "GET",
                    success: function(response) {
                        let corp = response.data;
                        let assetBase = "{{ asset('') }}";
                        let imageUrl = corp.image ? assetBase + corp.image : assetBase +
                            'images/default-corp.png';

                        let badgeClass = {
                            active: 'bg-success',
                            inactive: 'bg-secondary',
                            suspended: 'bg-danger'
                        } [corp.status] || 'bg-secondary';

                        let html = `
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <img src="${imageUrl}"
                                 alt="${escapeHtml(corp.name)}"
                                 style="max-height: 150px; border-radius: 8px; object-fit: cover;"
                                 onerror="this.src='${assetBase}images/default-corp.png'">
                        </div>
                        <div class="col-md-8">
                            <h4>${escapeHtml(corp.name)}</h4>
                            <div class="row mt-3">
                                <div class="col-sm-6">
                                    <p><strong>Code:</strong> ${escapeHtml(corp.code)}</p>
                                    <p><strong>Type:</strong> ${escapeHtml(corp.type || 'Municipal Corporation')}</p>
                                    <p><strong>Status:</strong> <span class="badge ${badgeClass}">${corp.status}</span></p>
                                </div>
                                <div class="col-sm-6">
                                    <p><strong>State:</strong> ${escapeHtml(corp.state || '-')}</p>
                                    <p><strong>District:</strong> ${escapeHtml(corp.district || '-')}</p>
                                    <p><strong>Pincode:</strong> ${escapeHtml(corp.pincode || '-')}</p>
                                </div>
                            </div>
                            <p><strong>Description:</strong></p>
                            <p class="text-muted">${escapeHtml(corp.description || 'No description')}</p>
                            ${corp.boundary_file ? `
                                    <p><strong>Boundary:</strong> <span class="text-success">✓ Uploaded</span></p>
                                ` : ''}
                        </div>
                    </div>
                `;
                        $('#viewModalBody').html(html);
                    },
                    error: function(xhr) {
                        $('#viewModalBody').html(`
                    <div class="text-center py-4 text-danger">
                        <i class="bi bi-exclamation-circle fs-1"></i>
                        <h5 class="mt-2">Failed to load corporation details</h5>
                    </div>
                `);
                    }
                });
            });

            // =============================================
            // MODAL CLEANUP
            // =============================================
            $('#corpModal').on('hidden.bs.modal', function() {
                // Reset form when modal is closed
                resetForm();
                $('#corpSaveBtn').prop('disabled', false).html('Save Corporation');
            });

            // =============================================
            // INITIAL LOAD
            // =============================================
            loadCorporations(1);
        });
    </script>
@endpush

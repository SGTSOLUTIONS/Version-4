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

    <!-- Corporation Grid -->
    <div class="container-fluid px-0">
        <div class="row g-4" id="corporationsGrid">
            <!-- Corporations loaded via AJAX -->
        </div>
    </div>

    <!-- Pagination -->
    <div id="paginationContainer" class="d-flex justify-content-center mt-4" style="display: none;">
        <nav>
            <ul class="pagination" id="paginationList"></ul>
        </nav>
    </div>

    <!-- Add/Edit Corporation Modal -->
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
                        <div id="validationErrorSummary" class="alert alert-danger" style="display: none;">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <span id="validationErrorText">Please fix the following errors:</span>
                            <ul id="validationErrorList" class="mb-0 mt-2"></ul>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header">Basic Information</div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Corporation Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" id="f_name" class="form-control">
                                        <div class="invalid-feedback" id="error-name"></div>
                                    </div>
                                    @if (auth()->user()->role == 'admin')
                                        <div class="col-md-6">
                                            <label class="form-label">Corporation Code <span class="text-danger">*</span></label>
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
                                            <label class="form-label">Boundary File <span class="text-danger">*</span></label>
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
                                            <small class="text-muted">Excel/CSV file</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Water Tax File</label>
                                            <input type="file" name="water_tax_file" id="f_water"
                                                class="form-control" accept=".xlsx,.xls,.csv">
                                            <div class="invalid-feedback" id="error-water_tax_file"></div>
                                            <small class="text-muted">Excel/CSV file</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">UGD Tax File</label>
                                            <input type="file" name="ugd_tax_file" id="f_ugd"
                                                class="form-control" accept=".xlsx,.xls,.csv">
                                            <div class="invalid-feedback" id="error-ugd_tax_file"></div>
                                            <small class="text-muted">Excel/CSV file</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Professional Tax File</label>
                                            <input type="file" name="professional_tax_file" id="f_pt"
                                                class="form-control" accept=".xlsx,.xls,.csv">
                                            <div class="invalid-feedback" id="error-professional_tax_file"></div>
                                            <small class="text-muted">Excel/CSV file</small>
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

    <!-- Import Status Modal -->
    <div class="modal fade" id="importStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-clock-history me-2"></i>
                        Import Status
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="importStatusBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="location.reload()">
                        <i class="bi bi-arrow-repeat"></i> Refresh Page
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .data-toolbar {
        margin: var(--s5) 0 var(--s3);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: var(--s3);
    }
    .data-search input {
        min-width: 250px;
        border-radius: var(--r-md);
        border: 1px solid var(--border);
        padding: var(--s2) var(--s4);
        font-size: var(--text-base);
        background: var(--bg-input);
        transition: var(--t-fast);
        color: var(--text-primary);
    }
    .data-search input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-muted);
    }
    .app-select {
        border-radius: var(--r-md);
        border: 1px solid var(--border);
        padding: var(--s2) var(--s4);
        min-width: 140px;
        font-size: var(--text-base);
        background: var(--bg-input);
        color: var(--text-primary);
        transition: var(--t-fast);
        cursor: pointer;
    }
    .app-select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-muted);
    }
    .btn-success.app-btn-sm {
        background: var(--primary);
        border: none;
        border-radius: var(--r-md);
        padding: var(--s2) var(--s5);
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: var(--s2);
        font-size: var(--text-base);
        color: var(--text-inverse);
        transition: var(--t-fast);
        cursor: pointer;
    }
    .btn-success.app-btn-sm:hover {
        background: var(--primary-hover);
        transform: translateY(-1px);
        box-shadow: var(--shadow-sm);
    }
    .acard {
        background: var(--bg-card);
        border-radius: var(--r-lg);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        transition: var(--t-slow);
        display: flex;
        flex-direction: column;
        height: 100%;
        border: 1px solid var(--border);
    }
    .acard:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary-soft);
    }
    .acard-img-wrap {
        position: relative;
        width: 100%;
        height: 220px;
        background: var(--gray-50);
        overflow: hidden;
        flex-shrink: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: var(--s3);
        box-sizing: border-box;
    }
    .acard-img-wrap img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
        transition: var(--t-slow);
    }
    .acard:hover .acard-img-wrap img {
        transform: scale(1.03);
    }
    .acard-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(to bottom, transparent 50%, rgba(15, 31, 61, 0.4));
    }
    .acard-tag {
        position: absolute;
        top: var(--s3);
        right: var(--s3);
        background: var(--primary);
        color: var(--text-inverse);
        padding: var(--s1) var(--s3);
        border-radius: var(--r-full);
        font-size: var(--text-xs);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: var(--shadow-sm);
    }
    .acard-body {
        padding: var(--s4) var(--s5) var(--s5);
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .acard-meta {
        font-size: var(--text-sm);
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: var(--s2);
        margin-bottom: var(--s2);
        font-weight: 500;
    }
    .acard-meta .dot {
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: var(--gray-300);
        display: inline-block;
    }
    .acard-title {
        font-size: var(--text-lg);
        font-weight: 700;
        color: var(--text-primary);
        margin: 0 0 var(--s2) 0;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .acard-desc {
        font-size: var(--text-base);
        color: var(--text-secondary);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        margin-bottom: var(--s3);
        flex: 1;
        line-height: 1.6;
    }
    .acard-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: var(--s3);
        border-top: 1px solid var(--border-subtle);
        margin-top: auto;
    }
    .acard-author {
        font-size: var(--text-sm);
        color: var(--text-secondary);
        font-weight: 600;
    }
    .invalid-feedback {
        font-size: var(--text-sm);
        margin-top: var(--s1);
        color: var(--danger);
        font-weight: 500;
    }
    .is-invalid {
        border-color: var(--danger) !important;
    }
    .pagination {
        display: flex;
        gap: var(--s1);
        flex-wrap: wrap;
        margin-top: var(--s4);
    }
    .pagination .page-item {
        list-style: none;
    }
    .pagination .page-item.active .page-link {
        background: var(--primary);
        border-color: var(--primary);
        color: var(--text-inverse);
        box-shadow: var(--shadow-sm);
    }
    .pagination .page-link {
        color: var(--text-secondary);
        border-radius: var(--r-md);
        margin: 0 2px;
        padding: var(--s2) var(--s4);
        border: 1px solid var(--border);
        background: var(--bg-card);
        transition: var(--t-fast);
        text-decoration: none;
        font-weight: 600;
        font-size: var(--text-base);
        display: inline-block;
    }
    .pagination .page-link:hover {
        background: var(--gray-50);
        border-color: var(--primary);
        color: var(--primary);
    }
    .spin {
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .import-stat-card {
        background: var(--bg-card);
        border-radius: var(--r-md);
        padding: var(--s4);
        text-align: center;
        transition: var(--t-slow);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-xs);
    }
    .import-stat-card:hover {
        border-color: var(--primary);
        background: var(--primary-muted);
        transform: translateY(-2px);
        box-shadow: var(--shadow-sm);
    }
    .import-stat-card .stat-icon {
        font-size: var(--text-3xl);
        color: var(--primary);
        margin-bottom: var(--s2);
        display: block;
    }
    .import-stat-card .stat-title {
        font-size: var(--text-base);
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: var(--s2);
    }
    .import-stat-card .stat-numbers {
        display: flex;
        justify-content: center;
        gap: var(--s4);
        font-size: var(--text-base);
        font-weight: 600;
    }
    .import-stat-card .stat-numbers .num-inserted {
        color: var(--success);
    }
    .import-stat-card .stat-numbers .num-updated {
        color: var(--warning);
    }
    .import-stat-card .stat-numbers .num-skipped {
        color: var(--danger);
    }
    .badge {
        font-size: var(--text-xs);
        padding: var(--s1) var(--s3);
        border-radius: var(--r-full);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .badge-success {
        background: var(--success-bg);
        color: var(--success-text);
    }
    .badge-warning {
        background: var(--warning-bg);
        color: var(--warning-text);
    }
    .badge-danger {
        background: var(--danger-bg);
        color: var(--danger-text);
    }
    .badge-info {
        background: var(--info-bg);
        color: var(--info-text);
    }
    @media (max-width: 768px) {
        .data-toolbar {
            flex-direction: column;
            align-items: stretch !important;
        }
        .data-search input {
            min-width: 100%;
        }
        .acard-img-wrap {
            height: 180px;
        }
    }
    @media (max-width: 576px) {
        .acard-img-wrap {
            height: 160px;
        }
        .acard-body {
            padding: var(--s3);
        }
        .acard-title {
            font-size: var(--text-md);
        }
        .pagination .page-link {
            padding: var(--s1) var(--s3);
            font-size: var(--text-sm);
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

        $('.flash-message-container').remove();

        const container = `
            <div class="flash-message-container" style="position:fixed;top:20px;right:20px;z-index:9999;max-width:450px;width:100%;">
                <div class="flash-message" style="padding:16px 20px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);display:flex;align-items:center;gap:12px;animation:slideInRight 0.4s ease;margin-bottom:12px;border-left:4px solid ${colors[type] || colors.info};background:white;color:#333;">
                    <i class="bi ${icons[type] || icons.info}" style="font-size:24px;color:${colors[type] || colors.info};flex-shrink:0;"></i>
                    <span class="flash-content" style="flex:1;font-size:14px;word-break:break-word;font-weight:500;">${message}</span>
                    <button class="flash-close" onclick="$(this).closest('.flash-message-container').remove()" style="background:transparent;border:none;color:#999;font-size:20px;cursor:pointer;padding:0 8px;opacity:0.7;">&times;</button>
                </div>
            </div>
        `;

        $('body').append(container);

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

    window.removeImagePreview = function() {
        $('#imagePreview').hide();
        $('#imagePreview img').attr('src', '');
        $('#f_image').val('');
        $('#f_image').removeClass('is-invalid');
        $('#error-image').text('');
    };

    $('#f_image').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview img').attr('src', e.target.result);
                $('#imagePreview').show();
                $('#f_image').removeClass('is-invalid');
                $('#error-image').text('');
            };
            reader.readAsDataURL(file);
        } else {
            $('#imagePreview').hide();
        }
    });

    function resetForm() {
        $('#corpForm')[0].reset();
        $('#corpId').val('');
        $('#formMethod').val('POST');
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $('#validationErrorSummary').hide();
        $('#validationErrorList').empty();
        $('#imagePreview').hide();
        $('#imagePreview img').attr('src', '');
        $('#f_code').prop('disabled', false);
        $('#f_image').removeAttr('required');
    }

    function showValidationErrors(errors) {
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $('#validationErrorSummary').hide();
        $('#validationErrorList').empty();

        let errorList = [];
        let fieldMap = {
            'name': 'f_name',
            'code': 'f_code',
            'state': 'f_state',
            'district': 'f_district',
            'pincode': 'f_pincode',
            'status': 'f_status',
            'description': 'f_description',
            'image': 'f_image',
            'boundary_file': 'f_boundary',
            'mis_file': 'f_mis',
            'water_tax_file': 'f_water',
            'ugd_tax_file': 'f_ugd',
            'professional_tax_file': 'f_pt'
        };

        $.each(errors, function(field, messages) {
            let fieldId = fieldMap[field] || field;
            let input = $('#' + fieldId);

            if (input.length) {
                input.addClass('is-invalid');
                let errorContainer = $('#error-' + field);
                if (errorContainer.length) {
                    errorContainer.text(messages[0]);
                }
                errorList.push(messages[0]);
            }
        });

        if (errorList.length > 0) {
            $('#validationErrorSummary').show();
            let listHtml = '';
            $.each(errorList, function(index, msg) {
                listHtml += `<li>${msg}</li>`;
            });
            $('#validationErrorList').html(listHtml);
            showFlashMessage(errorList[0], 'error', 8000);
        }
    }

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
                $('html, body').animate({ scrollTop: 0 }, 300);
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
                <div class="col-12 text-center py-5">
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
            let imageUrl = corp.image ? assetBase + corp.image : assetBase + 'images/default-corp.png';
            let badgeClass = {
                active: 'badge-success',
                inactive: 'badge-secondary',
                suspended: 'badge-danger'
            } [corp.status] || 'badge-secondary';

            html += `
                <div class="col-12 col-sm-6 col-md-4 col-xl-3">
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
                                <button class="btn btn-primary btn-sm flex-fill import-status-btn" data-id="${corp.id}">
                                    <i class="bi bi-clock-history"></i> Status
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
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page - 1}">&laquo; Previous</a></li>`;
        } else {
            html += `<li class="page-item disabled"><a class="page-link" href="#">&laquo; Previous</a></li>`;
        }

        if (pagination.current_page > 3) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
            if (pagination.current_page > 4) {
                html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`;
            }
        }

        for (let i = Math.max(1, pagination.current_page - 2); i <= Math.min(pagination.last_page, pagination.current_page + 2); i++) {
            if (i === pagination.current_page) {
                html += `<li class="page-item active"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
            } else {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
            }
        }

        if (pagination.current_page < pagination.last_page - 2) {
            if (pagination.current_page < pagination.last_page - 3) {
                html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`;
            }
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.last_page}">${pagination.last_page}</a></li>`;
        }

        if (pagination.current_page < pagination.last_page) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page + 1}">Next &raquo;</a></li>`;
        } else {
            html += `<li class="page-item disabled"><a class="page-link" href="#">Next &raquo;</a></li>`;
        }

        $('#paginationList').html(html);

        let start = pagination.from || ((pagination.current_page - 1) * pagination.per_page + 1);
        let end = pagination.to || Math.min(pagination.current_page * pagination.per_page, pagination.total);

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
    // IMPORT STATUS CHECKING
    // =============================================

    function checkImportStatus(corporationId, type = 'mis') {
        $.ajax({
            url: `/import-status/${corporationId}/${type}`,
            type: "GET",
            success: function(response) {
                let statusHtml = '';
                let statusColor = '';
                let statusIcon = '';

                switch(response.status) {
                    case 'completed':
                        statusColor = '#10b981';
                        statusIcon = 'bi-check-circle-fill';
                        statusHtml = `
                            <div class="text-center">
                                <i class="bi bi-check-circle-fill" style="font-size: 48px; color: #10b981;"></i>
                                <h5 class="mt-2">Import Completed ✅</h5>
                                <p class="text-muted">${response.message}</p>
                                <div class="row mt-3">
                                    <div class="col-4">
                                        <div class="bg-light p-2 rounded">
                                            <strong>Inserted</strong><br>
                                            <span class="text-success">${response.stats.inserted || 0}</span>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="bg-light p-2 rounded">
                                            <strong>Updated</strong><br>
                                            <span class="text-warning">${response.stats.updated || 0}</span>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="bg-light p-2 rounded">
                                            <strong>Skipped</strong><br>
                                            <span class="text-danger">${response.stats.skipped || 0}</span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-muted small mt-2">${response.timestamp}</p>
                            </div>
                        `;
                        break;

                    case 'processing':
                        statusColor = '#f59e0b';
                        statusIcon = 'bi-arrow-repeat spin';
                        let progress = response.progress || { percentage: 0 };
                        statusHtml = `
                            <div class="text-center">
                                <i class="bi bi-arrow-repeat spin" style="font-size: 48px; color: #f59e0b;"></i>
                                <h5 class="mt-2">Processing...</h5>
                                <p class="text-muted">${response.message}</p>
                                <div class="progress mt-3" style="height: 20px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                         style="width: ${progress.percentage || 0}%; background-color: #f59e0b;">
                                        ${progress.percentage || 0}%
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-4">
                                        <div class="bg-light p-2 rounded">
                                            <strong>Processed</strong><br>
                                            ${progress.processed || 0}
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="bg-light p-2 rounded">
                                            <strong>Total</strong><br>
                                            ${progress.total_rows || 0}
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="bg-light p-2 rounded">
                                            <strong>Inserted</strong><br>
                                            <span class="text-success">${progress.inserted || 0}</span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-muted small mt-2">${response.timestamp}</p>
                            </div>
                        `;
                        break;

                    case 'queued':
                        statusColor = '#3b82f6';
                        statusIcon = 'bi-clock-history';
                        statusHtml = `
                            <div class="text-center">
                                <i class="bi bi-clock-history" style="font-size: 48px; color: #3b82f6;"></i>
                                <h5 class="mt-2">Queued ⏳</h5>
                                <p class="text-muted">${response.message}</p>
                                <div class="alert alert-info mt-3">
                                    <i class="bi bi-info-circle"></i>
                                    The import is waiting for a queue worker to pick it up.
                                </div>
                                <p class="text-muted small mt-2">${response.timestamp}</p>
                                <button class="btn btn-sm btn-primary mt-2" onclick="checkImportStatus(${corporationId}, '${type}')">
                                    <i class="bi bi-arrow-repeat"></i> Refresh
                                </button>
                            </div>
                        `;
                        break;

                    case 'failed':
                        statusColor = '#ef4444';
                        statusIcon = 'bi-exclamation-circle-fill';
                        let errorMsg = response.error?.message || response.message || 'Unknown error';
                        statusHtml = `
                            <div class="text-center">
                                <i class="bi bi-exclamation-circle-fill" style="font-size: 48px; color: #ef4444;"></i>
                                <h5 class="mt-2">Import Failed ❌</h5>
                                <p class="text-danger">${errorMsg}</p>
                                <div class="alert alert-danger mt-3 text-start">
                                    <strong>Error Details:</strong><br>
                                    ${errorMsg}
                                </div>
                                <p class="text-muted small mt-2">${response.timestamp}</p>
                            </div>
                        `;
                        break;

                    case 'not_started':
                    default:
                        statusHtml = `
                            <div class="text-center">
                                <i class="bi bi-info-circle" style="font-size: 48px; color: #6b7280;"></i>
                                <h5 class="mt-2">Not Started</h5>
                                <p class="text-muted">No import has been started for this corporation.</p>
                                <p class="text-muted small">${response.timestamp || 'N/A'}</p>
                            </div>
                        `;
                        break;
                }

                $('#importStatusBody').html(`<div class="p-3">${statusHtml}</div>`);
                $('#importStatusModal').modal('show');
            },
            error: function(xhr) {
                showFlashMessage('Failed to get import status', 'error');
            }
        });
    }

    function checkAllImportStatuses(corporationId) {
        $.ajax({
            url: `/import-status-all/${corporationId}`,
            type: "GET",
            success: function(response) {
                if (response.status && response.data) {
                    let html = `<div class="row g-3">`;
                    
                    const typeLabels = {
                        'mis': 'MIS Data',
                        'water_tax': 'Water Tax',
                        'ugd_tax': 'UGD Tax',
                        'professional_tax': 'Professional Tax'
                    };

                    const statusColors = {
                        'completed': '#10b981',
                        'processing': '#f59e0b',
                        'queued': '#3b82f6',
                        'failed': '#ef4444',
                        'not_started': '#6b7280'
                    };

                    const statusIcons = {
                        'completed': 'bi-check-circle-fill',
                        'processing': 'bi-arrow-repeat spin',
                        'queued': 'bi-clock-history',
                        'failed': 'bi-exclamation-circle-fill',
                        'not_started': 'bi-info-circle'
                    };

                    $.each(response.data, function(type, status) {
                        let color = statusColors[status.status] || '#6b7280';
                        let icon = statusIcons[status.status] || 'bi-info-circle';
                        
                        html += `
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="bi ${icon}" style="font-size: 32px; color: ${color};"></i>
                                        <h6 class="mt-2">${typeLabels[type] || type}</h6>
                                        <span class="badge" style="background-color: ${color}; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px;">
                                            ${status.status.replace('_', ' ').toUpperCase()}
                                        </span>
                                        ${status.stats ? `
                                        <div class="row mt-2 small">
                                            <div class="col-4">
                                                <span class="text-success">+${status.stats.inserted || 0}</span>
                                            </div>
                                            <div class="col-4">
                                                <span class="text-warning">↻${status.stats.updated || 0}</span>
                                            </div>
                                            <div class="col-4">
                                                <span class="text-danger">⚠${status.stats.skipped || 0}</span>
                                            </div>
                                        </div>
                                        ` : ''}
                                        ${status.progress ? `
                                        <div class="progress mt-2" style="height: 6px;">
                                            <div class="progress-bar progress-bar-striped" 
                                                 style="width: ${status.progress.percentage || 0}%; background-color: ${color};">
                                            </div>
                                        </div>
                                        ` : ''}
                                        <button class="btn btn-sm btn-outline-primary mt-2" 
                                                onclick="checkImportStatus(${corporationId}, '${type}')">
                                            <i class="bi bi-eye"></i> Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    html += `
                        <div class="col-12 text-center mt-3">
                            <p class="text-muted small">
                                Last updated: ${response.timestamp || 'N/A'}
                                ${response.has_active_import ? '🔄 Active imports running' : ''}
                            </p>
                            <button class="btn btn-sm btn-secondary" onclick="checkAllImportStatuses(${corporationId})">
                                <i class="bi bi-arrow-repeat"></i> Refresh All
                            </button>
                        </div>
                    </div>`;

                    $('#importStatusBody').html(html);
                    $('#importStatusModal').modal('show');
                }
            },
            error: function(xhr) {
                showFlashMessage('Failed to get import statuses', 'error');
            }
        });
    }

    // =============================================
    // SHOW IMPORT STATS
    // =============================================

    function showImportStats(stats) {
        let html = '<div class="row g-3">';
        let hasErrors = false;
        let hasData = false;

        const importTypes = {
            'mis': { label: 'MIS Data', icon: 'bi-building', color: '#1679AB' },
            'water_tax': { label: 'Water Tax', icon: 'bi-droplet', color: '#3b82f6' },
            'ugd_tax': { label: 'UGD Tax', icon: 'bi-pipe', color: '#8b5cf6' },
            'professional_tax': { label: 'Professional Tax', icon: 'bi-briefcase', color: '#f59e0b' }
        };

        if (stats.queued) {
            html = `
                <div class="col-12 text-center py-4">
                    <i class="bi bi-clock-history" style="font-size: 48px; color: #f59e0b;"></i>
                    <h6 class="mt-2">Imports Queued</h6>
                    <p class="text-muted">Your imports have been queued for background processing.</p>
                    <p class="text-muted small">Check the Status button for progress.</p>
                </div>
            `;
            $('#importStatsBody').html(html);
            $('#importStatsModal').modal('show');
            return;
        }

        if (stats.no_files) {
            html = `
                <div class="col-12 text-center py-4">
                    <i class="bi bi-info-circle" style="font-size: 48px; color: #6b7280;"></i>
                    <h6 class="mt-2">No Files Imported</h6>
                    <p class="text-muted">${stats.message}</p>
                    <p class="text-muted small">You can import data later from the edit option.</p>
                </div>
            `;
            $('#importStatsBody').html(html);
            $('#importStatsModal').modal('show');
            return;
        }

        $.each(stats, function(key, value) {
            if (value && typeof value === 'object') {
                if (value.error) {
                    hasErrors = true;
                    html += `
                        <div class="col-12">
                            <div class="alert alert-danger mb-0">
                                <strong>${importTypes[key]?.label || key}:</strong><br>
                                ❌ ${escapeHtml(value.message || value.error)}
                            </div>
                        </div>
                    `;
                } else if (value.inserted !== undefined || value.updated !== undefined || value.skipped !== undefined) {
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
                            </div>
                        </div>
                    `;
                }
            }
        });

        if (!hasData && !hasErrors) {
            html = `
                <div class="col-12 text-center py-4">
                    <i class="bi bi-info-circle" style="font-size: 48px; color: #6b7280;"></i>
                    <h6 class="mt-2">No Data Imported</h6>
                    <p class="text-muted">No import data available.</p>
                </div>
            `;
        }

        if (hasErrors && hasData) {
            html += `
                <div class="col-12 mt-2">
                    <div class="alert alert-warning mb-0">⚠️ Some imports had errors.</div>
                </div>
            `;
        }

        if (!hasErrors && hasData) {
            html += `
                <div class="col-12 mt-2">
                    <div class="alert alert-success mb-0">✅ All imports completed successfully!</div>
                </div>
            `;
        }

        html += '</div>';
        $('#importStatsBody').html(html);
        $('#importStatsModal').modal('show');
    }

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
        $('#corpModal').modal('show');
    });

    // =============================================
    // FORM SUBMISSION
    // =============================================

    $('#corpForm').on('submit', function(e) {
        e.preventDefault();

        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $('#validationErrorSummary').hide();
        $('#validationErrorList').empty();

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

        $('#corpSaveBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

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
                $('#corpSaveBtn').prop('disabled', false).html(method === 'PUT' ? 'Update Corporation' : 'Save Corporation');
                showFlashMessage(response.message || 'Corporation saved successfully', 'success');

                if (response.import_stats) {
                    showImportStats(response.import_stats);
                }

                $('#corpForm')[0].reset();
                $('#imagePreview').hide();
                $('#corpModal').modal('hide');
                loadCorporations(currentPage);
            },
            error: function(xhr) {
                $('#corpSaveBtn').prop('disabled', false).html(method === 'PUT' ? 'Update Corporation' : 'Save Corporation');

                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    if (errors) {
                        showValidationErrors(errors);
                    }
                } else {
                    let errorMessage = xhr.responseJSON?.message || 'Something went wrong. Please try again.';
                    showFlashMessage(errorMessage, 'error');
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

                $('#modalTitle').html('<i class="bi bi-pencil-square me-2"></i> Edit Corporation');
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
                }

                $('#formMethod').val('PUT');
                $('#corpSaveBtn').html('Update Corporation');

                if (userRole === 'commissioner') {
                    $('#f_code').prop('disabled', true);
                }

                $('#f_image').removeAttr('required');
                $('#corpModal').modal('show');
            },
            error: function(xhr) {
                showFlashMessage('Failed to load corporation data', 'error');
            }
        });
    });

    // =============================================
    // STATUS BUTTON
    // =============================================

    $(document).on('click', '.import-status-btn', function() {
        let id = $(this).data('id');
        checkAllImportStatuses(id);
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
                let imageUrl = corp.image ? assetBase + corp.image : assetBase + 'images/default-corp.png';
                let badgeClass = {
                    active: 'badge-success',
                    inactive: 'badge-secondary',
                    suspended: 'badge-danger'
                } [corp.status] || 'badge-secondary';

                let html = `
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <img src="${imageUrl}" alt="${escapeHtml(corp.name)}"
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

        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Deleting...');

        $.ajax({
            url: "/admin/corporations/" + id,
            type: "DELETE",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#deleteModal').modal('hide');
                showFlashMessage(response.message || 'Corporation deleted successfully', 'success');
                loadCorporations(1);
                $('#confirmDeleteBtn').prop('disabled', false).html('Delete');
            },
            error: function(xhr) {
                showFlashMessage(xhr.responseJSON?.message || 'Failed to delete corporation', 'error');
                $('#confirmDeleteBtn').prop('disabled', false).html('Delete');
            }
        });
    });

    // =============================================
    // MODAL CLEANUP
    // =============================================

    $('#corpModal').on('hidden.bs.modal', function() {
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
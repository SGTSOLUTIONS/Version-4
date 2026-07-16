@extends('layouts.office')

@section('title', 'Usage Variation Report')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .filter-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        }

        .filter-card .form-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .filter-card .form-select,
        .filter-card .form-control {
            font-size: 0.85rem;
            border-radius: 8px;
            border-color: #e5e7eb;
        }

        .filter-card .form-select:focus,
        .filter-card .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .stat-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.2s;
            height: 100%;
        }

        .stat-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .stat-icon-blue { background: #eff6ff; color: #2563eb; }
        .stat-icon-green { background: #f0fdf4; color: #16a34a; }
        .stat-icon-red { background: #fef2f2; color: #dc2626; }
        .stat-icon-amber { background: #fffbeb; color: #d97706; }
        .stat-icon-purple { background: #f5f3ff; color: #7c3aed; }
        .stat-icon-cyan { background: #ecfeff; color: #0891b2; }
        .stat-icon-pink { background: #fdf2f8; color: #db2777; }

        .stat-label {
            font-size: 0.68rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .stat-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e293b;
            margin-top: 2px;
        }

        .stat-sub {
            font-size: 0.72rem;
            font-weight: 600;
            color: #94a3b8;
        }

        .table-container {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
            position: relative;
        }

        .table-container .table-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            background: #fafbfc;
        }

        .table-container .table-header h5 {
            margin: 0;
            font-weight: 600;
            font-size: 1rem;
        }

        .table-container .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }

        .table-container table {
            margin-bottom: 0;
        }

        .table-container table thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table-container table thead th {
            background: #f1f5f9;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #475569;
            padding: 12px 14px;
            border-bottom: 2px solid #e5e7eb;
            white-space: nowrap;
        }

        .table-container table tbody td {
            padding: 10px 14px;
            font-size: 0.85rem;
            vertical-align: middle;
        }

        .badge-match {
            background: #dcfce7;
            color: #15803d;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.7rem;
        }

        .badge-variation {
            background: #fee2e2;
            color: #b91c1c;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.7rem;
        }

        .btn-export {
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.8rem;
            padding: 6px 16px;
            transition: all 0.2s;
        }

        .btn-export-excel {
            background: #217346;
            color: white;
            border: none;
        }

        .btn-export-excel:hover {
            background: #1a5c38;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(33, 115, 70, 0.3);
        }

        .btn-export-pdf {
            background: #dc3545;
            color: white;
            border: none;
        }

        .btn-export-pdf:hover {
            background: #b02a37;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .btn-export-csv {
            background: #0d6efd;
            color: white;
            border: none;
        }

        .btn-export-csv:hover {
            background: #0b5ed7;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
        }

        .variation-progress {
            width: 80px;
            height: 6px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            display: inline-block;
            vertical-align: middle;
        }

        .variation-progress .bar {
            height: 100%;
            border-radius: 4px;
            transition: width 0.6s ease;
        }

        .variation-progress .bar-success { background: #22c55e; }
        .variation-progress .bar-danger { background: #ef4444; }
        .variation-progress .bar-warning { background: #f59e0b; }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 16px;
        }

        .empty-state h5 {
            color: #475569;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .filter-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-actions .btn {
            font-size: 0.8rem;
            padding: 6px 14px;
            border-radius: 8px;
            font-weight: 600;
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.85);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100;
            border-radius: 12px;
        }

        .loading-overlay.show {
            display: flex;
        }

        .loading-overlay .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e5e7eb;
            border-top-color: #2563eb;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .btn-filter {
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.8rem;
            padding: 6px 14px;
        }

        @media (max-width: 768px) {
            .stat-strip {
                grid-template-columns: repeat(2, 1fr);
            }
            .table-container .table-header {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-actions {
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            .stat-strip {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
<div class="container-fluid mt-4">

    <!-- ─── PAGE HEADER ─── -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="bi bi-tags text-primary me-2"></i>
                Usage Variation Report - Ward {{ $ward->ward_no }}
            </h4>
            <p class="text-muted small mb-0">
                {{ $ward->zone->zone_name ?? 'N/A' }} | {{ now()->format('l, d F Y') }}
            </p>
        </div>
        <div>
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <!-- ─── STATISTICS CARDS ─── -->
    <div class="row g-3 mb-4" id="statsContainer">
        <div class="col-xl-2 col-lg-3 col-md-4 col-6">
            <div class="stat-card">
                <div class="stat-icon stat-icon-blue"><i class="bi bi-building"></i></div>
                <div>
                    <div class="stat-label">Total Buildings</div>
                    <div class="stat-value" id="statTotal">{{ count($buildingVariations) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-3 col-md-4 col-6">
            <div class="stat-card">
                <div class="stat-icon stat-icon-green"><i class="bi bi-check2-circle"></i></div>
                <div>
                    <div class="stat-label">Usage Match</div>
                    <div class="stat-value" id="statUsageMatch">0</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-3 col-md-4 col-6">
            <div class="stat-card">
                <div class="stat-icon stat-icon-red"><i class="bi bi-x-circle"></i></div>
                <div>
                    <div class="stat-label">Usage Variation</div>
                    <div class="stat-value" id="statUsageVariation">0</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-3 col-md-4 col-6">
            <div class="stat-card">
                <div class="stat-icon stat-icon-amber"><i class="bi bi-rulers"></i></div>
                <div>
                    <div class="stat-label">Area Match</div>
                    <div class="stat-value" id="statAreaMatch">0</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-3 col-md-4 col-6">
            <div class="stat-card">
                <div class="stat-icon stat-icon-cyan"><i class="bi bi-arrows-expand"></i></div>
                <div>
                    <div class="stat-label">Area Variation</div>
                    <div class="stat-value" id="statAreaVariation">0</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-3 col-md-4 col-6">
            <div class="stat-card">
                <div class="stat-icon stat-icon-purple"><i class="bi bi-file-earmark-text"></i></div>
                <div>
                    <div class="stat-label">Filtered Results</div>
                    <div class="stat-value" id="statFiltered">{{ count($buildingVariations) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── FILTER SECTION ─── -->
    <div class="filter-card">
        <form id="filterForm">
            <div class="row g-3 align-items-end">
                <!-- Usage Status Filter -->
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label"><i class="bi bi-tags me-1"></i>Usage Status</label>
                    <select name="usage_status" id="filterUsageStatus" class="form-select form-select-sm">
                        <option value="all">All Status</option>
                        <option value="match">Match</option>
                        <option value="variation">Variation</option>
                    </select>
                </div>

                <!-- Area Status Filter -->
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label"><i class="bi bi-rulers me-1"></i>Area Status</label>
                    <select name="area_status" id="filterAreaStatus" class="form-select form-select-sm">
                        <option value="all">All Status</option>
                        <option value="match">Match</option>
                        <option value="variation">Variation</option>
                    </select>
                </div>

                <!-- GIS ID Filter -->
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label"><i class="bi bi-hash me-1"></i>GIS ID</label>
                    <input type="text" name="gisid" id="filterGisid" class="form-control form-control-sm" placeholder="Search GIS ID...">
                </div>

                <!-- Assessment Count Filter -->
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label"><i class="bi bi-file-earmark-text me-1"></i>Assessment Count</label>
                    <select name="assessment_count" id="filterAssessmentCount" class="form-select form-select-sm">
                        <option value="all">All Counts</option>
                        <option value="0">No Assessments</option>
                        <option value="1">1 Assessment</option>
                        <option value="2">2 Assessments</option>
                        <option value="3">3+ Assessments</option>
                    </select>
                </div>

                <!-- Area Variation Range -->
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label"><i class="bi bi-sliders me-1"></i>Area Variation %</label>
                    <div class="d-flex gap-2">
                        <input type="number" name="var_min" id="filterVarMin" class="form-control form-control-sm" placeholder="Min %" min="0" max="100">
                        <input type="number" name="var_max" id="filterVarMax" class="form-control form-control-sm" placeholder="Max %" min="0" max="100">
                    </div>
                </div>

                <!-- Actions -->
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary btn-filter">
                            <i class="bi bi-funnel me-1"></i> Apply
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-filter" id="resetFiltersBtn">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-filter" id="clearFiltersBtn">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Export Buttons Row -->
            <div class="row mt-3 pt-3 border-top">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="text-muted small">
                            <i class="bi bi-info-circle me-1"></i>
                            Showing <span id="visibleCount">0</span> of <span id="totalCount">{{ count($buildingVariations) }}</span> buildings
                        </span>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-export btn-export-excel" id="exportExcelBtn">
                                <i class="bi bi-file-earmark-excel me-1"></i> Excel
                            </button>
                            <button type="button" class="btn btn-export btn-export-pdf" id="exportPdfBtn">
                                <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                            </button>
                            <button type="button" class="btn btn-export btn-export-csv" id="exportCsvBtn">
                                <i class="bi bi-file-earmark-spreadsheet me-1"></i> CSV
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- ─── TABLE ─── -->
    <div class="table-container" id="tableContainer">
        <div class="loading-overlay" id="loadingOverlay">
            <div class="spinner"></div>
        </div>
        <div class="table-header">
            <h5><i class="bi bi-table me-2"></i>Variation Details</h5>
            <span class="text-muted small" id="recordCount">Total: {{ count($buildingVariations) }} records</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="variationTable">
                <thead>
                    <tr>
                        <th style="width:60px;">#</th>
                        <th>GIS ID</th>
                        <th>Building Area</th>
                        <th>Assessment Area</th>
                        <th>Area Variation</th>
                        <th>Variation %</th>
                        <th>Area Status</th>
                        <th>Usage Status</th>
                        <th>Assessments</th>
                        <th style="width:120px;">Progress</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    @forelse($buildingVariations as $variation)
                        <tr class="variation-row">
                            <td>{{ $loop->iteration }}</td>
                            <td><code>{{ $variation['gisid'] }}</code></td>
                            <td>{{ number_format($variation['building_area'], 2) }} sqft</td>
                            <td>{{ number_format($variation['assessment_area'], 2) }} sqft</td>
                            <td class="{{ $variation['area_variation'] > 0 ? 'text-danger' : ($variation['area_variation'] < 0 ? 'text-success' : 'text-muted') }}">
                                {{ $variation['area_variation'] > 0 ? '+' : '' }}{{ number_format($variation['area_variation'], 2) }}
                            </td>
                            <td>
                                {{ number_format($variation['variation_percentage'], 1) }}%
                                <div class="variation-progress">
                                    <div class="bar {{ $variation['variation_percentage'] > 10 ? 'bar-danger' : ($variation['variation_percentage'] > 5 ? 'bar-warning' : 'bar-success') }}"
                                         style="width: {{ min($variation['variation_percentage'], 100) }}%;"></div>
                                </div>
                            </td>
                            <td>
                                <span class="{{ $variation['area_status'] == 'VARIATION' ? 'badge-variation' : 'badge-match' }}">
                                    {{ $variation['area_status'] }}
                                </span>
                            </td>
                            <td>
                                <span class="{{ $variation['usage_status'] == 'VARIATION' ? 'badge-variation' : 'badge-match' }}">
                                    {{ $variation['usage_status'] }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $variation['assessment_count'] == 0 ? 'bg-secondary' : 'bg-primary' }}">
                                    {{ $variation['assessment_count'] }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="text-muted small">{{ $variation['assessment_count'] }}</span>
                                    <div class="variation-progress" style="width:40px;">
                                        <div class="bar {{ $variation['assessment_count'] > 0 ? 'bar-success' : 'bar-danger' }}"
                                             style="width: {{ min($variation['assessment_count'] * 25, 100) }}%;"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <h5>No Records Found</h5>
                                    <p>No variation data available for this ward.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {

            // ─── CSRF TOKEN ───
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // ─── LOADING STATE ───
            function showLoading() {
                $('#loadingOverlay').addClass('show');
            }

            function hideLoading() {
                $('#loadingOverlay').removeClass('show');
            }

            // ─── FETCH FILTERED DATA ───
            function fetchFilteredData() {
                showLoading();

                const formData = {
                    ward_id: {{ $ward->id }},
                    usage_status: $('#filterUsageStatus').val(),
                    area_status: $('#filterAreaStatus').val(),
                    gisid: $('#filterGisid').val(),
                    assessment_count: $('#filterAssessmentCount').val(),
                    var_min: $('#filterVarMin').val(),
                    var_max: $('#filterVarMax').val()
                };

                $.ajax({
                    url: "{{ route('variation.filter') }}",
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            updateTable(response.data);
                            updateStats(response.stats);
                            $('#visibleCount').text(response.stats.filtered);
                            $('#totalCount').text(response.stats.total);
                            $('#recordCount').text(`Total: ${response.stats.filtered} records`);
                        } else {
                            Swal.fire('Error', response.message || 'Failed to load data', 'error');
                        }
                    },
                    error: function(xhr) {
                        console.error('AJAX Error:', xhr);
                        Swal.fire('Error', 'Failed to fetch filtered data', 'error');
                    },
                    complete: function() {
                        hideLoading();
                    }
                });
            }

            // ─── UPDATE TABLE ───
            function updateTable(data) {
                const $tbody = $('#tableBody');
                $tbody.empty();

                if (!data || data.length === 0) {
                    $tbody.append(`
                        <tr>
                            <td colspan="10">
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <h5>No Matching Records</h5>
                                    <p>No buildings match your filter criteria.</p>
                                </div>
                            </td>
                        </tr>
                    `);
                    return;
                }

                data.forEach(function(item, index) {
                    const areaClass = item.area_variation > 0 ? 'text-danger' :
                                     (item.area_variation < 0 ? 'text-success' : 'text-muted');
                    const areaSign = item.area_variation > 0 ? '+' : '';

                    let progressClass = 'bar-success';
                    if (item.variation_percentage > 10) progressClass = 'bar-danger';
                    else if (item.variation_percentage > 5) progressClass = 'bar-warning';

                    const usageBadge = item.usage_status === 'VARIATION' ? 'badge-variation' : 'badge-match';
                    const areaBadge = item.area_status === 'VARIATION' ? 'badge-variation' : 'badge-match';
                    const assessmentBadge = item.assessment_count === 0 ? 'bg-secondary' : 'bg-primary';

                    $tbody.append(`
                        <tr class="variation-row">
                            <td>${index + 1}</td>
                            <td><code>${item.gisid}</code></td>
                            <td>${item.building_area.toFixed(2)} sqft</td>
                            <td>${item.assessment_area.toFixed(2)} sqft</td>
                            <td class="${areaClass}">${areaSign}${item.area_variation.toFixed(2)}</td>
                            <td>
                                ${item.variation_percentage.toFixed(1)}%
                                <div class="variation-progress">
                                    <div class="bar ${progressClass}" style="width: ${Math.min(item.variation_percentage, 100)}%;"></div>
                                </div>
                            </td>
                            <td><span class="${areaBadge}">${item.area_status}</span></td>
                            <td><span class="${usageBadge}">${item.usage_status}</span></td>
                            <td><span class="badge ${assessmentBadge}">${item.assessment_count}</span></td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="text-muted small">${item.assessment_count}</span>
                                    <div class="variation-progress" style="width:40px;">
                                        <div class="bar ${item.assessment_count > 0 ? 'bar-success' : 'bar-danger'}"
                                             style="width: ${Math.min(item.assessment_count * 25, 100)}%;"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    `);
                });
            }

            // ─── UPDATE STATISTICS ───
            function updateStats(stats) {
                if (!stats) return;
                $('#statTotal').text(stats.total);
                $('#statUsageMatch').text(stats.usage_match);
                $('#statUsageVariation').text(stats.usage_variation);
                $('#statAreaMatch').text(stats.area_match);
                $('#statAreaVariation').text(stats.area_variation);
                $('#statFiltered').text(stats.filtered);
            }

            // ─── EXPORT FUNCTIONS (Backend) ───
            function exportData(format) {
                const formData = {
                    ward_id: {{ $ward->id }},
                    usage_status: $('#filterUsageStatus').val(),
                    area_status: $('#filterAreaStatus').val(),
                    gisid: $('#filterGisid').val(),
                    assessment_count: $('#filterAssessmentCount').val(),
                    var_min: $('#filterVarMin').val(),
                    var_max: $('#filterVarMax').val(),
                    format: format
                };

                Swal.fire({
                    title: 'Exporting...',
                    text: 'Please wait while we generate your file',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: "{{ route('variation.export') }}",
                    method: 'POST',
                    data: formData,
                    xhrFields: {
                        responseType: 'blob'
                    },
                    success: function(response, status, xhr) {
                        const contentDisposition = xhr.getResponseHeader('Content-Disposition');
                        let filename = `ward_${ {{ $ward->ward_no }} }_variations.${format}`;
                        if (contentDisposition) {
                            const match = contentDisposition.match(/filename="(.+)"/);
                            if (match) filename = match[1];
                        }

                        const url = window.URL.createObjectURL(response);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        window.URL.revokeObjectURL(url);

                        Swal.fire({
                            icon: 'success',
                            title: 'Exported!',
                            text: `File exported successfully`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        Swal.fire('Export Failed', 'Error exporting data', 'error');
                    }
                });
            }

            // ─── EVENT HANDLERS ───
            // Form submit - Apply filters
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                fetchFilteredData();
            });

            // Reset filters
            $('#resetFiltersBtn').on('click', function() {
                $('#filterUsageStatus').val('all');
                $('#filterAreaStatus').val('all');
                $('#filterGisid').val('');
                $('#filterAssessmentCount').val('all');
                $('#filterVarMin').val('');
                $('#filterVarMax').val('');
                fetchFilteredData();
            });

            // Clear filters
            $('#clearFiltersBtn').on('click', function() {
                $('#filterUsageStatus').val('all');
                $('#filterAreaStatus').val('all');
                $('#filterGisid').val('');
                $('#filterAssessmentCount').val('all');
                $('#filterVarMin').val('');
                $('#filterVarMax').val('');
                fetchFilteredData();
            });

            // Export buttons
            $('#exportExcelBtn').on('click', function() { exportData('xlsx'); });
            $('#exportPdfBtn').on('click', function() { exportData('pdf'); });
            $('#exportCsvBtn').on('click', function() { exportData('csv'); });

            // Real-time GIS ID search with debounce
            let searchTimeout;
            $('#filterGisid').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(fetchFilteredData, 500);
            });

            // Enter key on number inputs
            $('#filterVarMin, #filterVarMax').on('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    fetchFilteredData();
                }
            });

            // ─── KEYBOARD SHORTCUTS ───
            $(document).on('keydown', function(e) {
                // Ctrl+Shift+E for Excel
                if (e.ctrlKey && e.shiftKey && (e.key === 'E' || e.key === 'e')) {
                    e.preventDefault();
                    exportData('xlsx');
                }
                // Ctrl+Shift+P for PDF
                if (e.ctrlKey && e.shiftKey && (e.key === 'P' || e.key === 'p')) {
                    e.preventDefault();
                    exportData('pdf');
                }
                // Ctrl+Shift+C for CSV
                if (e.ctrlKey && e.shiftKey && (e.key === 'C' || e.key === 'c')) {
                    e.preventDefault();
                    exportData('csv');
                }
                // Ctrl+Shift+R for Reset
                if (e.ctrlKey && e.shiftKey && (e.key === 'R' || e.key === 'r')) {
                    e.preventDefault();
                    $('#resetFiltersBtn').click();
                }
                // Escape to clear filters
                if (e.key === 'Escape') {
                    $('#clearFiltersBtn').click();
                }
            });

            // ─── INIT ───
            // Load initial data with AJAX
            fetchFilteredData();

            console.log('✅ Variation page ready with backend AJAX filters and export');
        });
    </script>
@endpush

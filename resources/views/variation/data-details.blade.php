@extends('layouts.office')

@section('title', 'Data Variation Report')

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

        /* ─── BADGE STYLES ─── */
        .badge-match {
            background: #dcfce7;
            color: #15803d;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-variation {
            background: #fee2e2;
            color: #b91c1c;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-partial {
            background: #fef3c7;
            color: #92400e;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-building-only {
            background: #dbeafe;
            color: #1d4ed8;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-assessment-only {
            background: #fce7f3;
            color: #be185d;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-no-data {
            background: #f1f5f9;
            color: #64748b;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-export {
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.8rem;
            padding: 8px 20px;
            transition: all 0.2s;
            border: none;
        }

        .btn-export-excel {
            background: #217346;
            color: white;
        }

        .btn-export-excel:hover {
            background: #1a5c38;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(33, 115, 70, 0.4);
        }

        .btn-export-pdf {
            background: #dc3545;
            color: white;
        }

        .btn-export-pdf:hover {
            background: #b02a37;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }

        .btn-export-csv {
            background: #0d6efd;
            color: white;
        }

        .btn-export-csv:hover {
            background: #0b5ed7;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.4);
        }

        .usage-detail-box {
            background: #f8fafc;
            border-radius: 8px;
            padding: 6px 12px;
            display: inline-block;
            font-size: 0.78rem;
            border: 1px solid #e5e7eb;
        }

        .usage-detail-box .label {
            color: #94a3b8;
            font-weight: 600;
        }

        .usage-detail-box .value {
            color: #1e293b;
            font-weight: 700;
        }

        .usage-detail-box .value.mismatch {
            color: #dc2626;
        }

        .usage-detail-box .value.match {
            color: #16a34a;
        }

        .usage-detail-box .value.null-value {
            color: #94a3b8;
            font-style: italic;
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

        .detail-modal-content {
            max-height: 500px;
            overflow-y: auto;
        }

        .detail-section {
            background: #f8fafc;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 12px;
            border-left: 4px solid #3b82f6;
        }

        .detail-section.warning {
            border-left-color: #f59e0b;
        }

        .detail-section.danger {
            border-left-color: #ef4444;
        }

        .detail-section.success {
            border-left-color: #22c55e;
        }

        .detail-section h6 {
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 8px;
        }

        .detail-section .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 0.8rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-section .detail-item:last-child {
            border-bottom: none;
        }

        .detail-section .detail-item .label {
            color: #64748b;
            font-weight: 500;
        }

        .detail-section .detail-item .value {
            font-weight: 600;
            color: #1e293b;
        }

        .export-buttons-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn-filter {
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.8rem;
            padding: 6px 14px;
        }

        @media (max-width: 768px) {
            .header-actions {
                width: 100%;
                justify-content: flex-start;
            }
            .export-buttons-group {
                width: 100%;
            }
            .export-buttons-group .btn {
                flex: 1;
            }
        }
    </style>
@endpush

@section('content')
<div class="container-fluid mt-4">

    <!-- ─── PAGE HEADER ─── -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="bi bi-tags text-primary me-2"></i>
                Data Variation Report - Ward {{ $ward->ward_no ?? 'N/A' }}
            </h4>
            <p class="text-muted small mb-0">
                Zone: {{ $zone->zone_name ?? 'N/A' }} | {{ now()->format('l, d F Y') }}
            </p>
        </div>
        <div class="header-actions">
            <!-- BACK BUTTON -->
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>

            <!-- EXPORT BUTTONS -->
            <div class="export-buttons-group">
                <a href="{{ route('variation.export', $ward->id) }}"
                   class="btn btn-export btn-export-excel btn-sm">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                </a>
                <a href="{{ route('variation.export', $ward->id) }}?format=pdf"
                   class="btn btn-export btn-export-pdf btn-sm">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                </a>
                <a href="{{ route('variation.export', $ward->id) }}?format=csv"
                   class="btn btn-export btn-export-csv btn-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
                </a>
            </div>
        </div>
    </div>

    <!-- ─── STATISTICS CARDS ─── -->
    @php
        $total = count($buildingVariations);
        $matches = 0;
        $variations = 0;
        $partialMatches = 0;
        $buildingOnly = 0;
        $assessmentOnly = 0;
        $noData = 0;
        $areaVariations = 0;

        foreach($buildingVariations as $item) {
            $status = $item['usage_comparison']['usage_status'] ?? 'NO_DATA';
            switch($status) {
                case 'MATCH': $matches++; break;
                case 'VARIATION': $variations++; break;
                case 'PARTIAL_MATCH': $partialMatches++; break;
                case 'BUILDING_ONLY': $buildingOnly++; break;
                case 'ASSESSMENT_ONLY': $assessmentOnly++; break;
                default: $noData++; break;
            }

            if (($item['area_comparison']['area_status'] ?? '') === 'VARIATION') {
                $areaVariations++;
            }
        }
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-lg-3 col-md-4 col-6">
            <div class="stat-card">
                <div class="stat-icon stat-icon-blue"><i class="bi bi-building"></i></div>
                <div>
                    <div class="stat-label">Total Buildings</div>
                    <div class="stat-value">{{ $total }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-3 col-md-4 col-6">
            <div class="stat-card">
                <div class="stat-icon stat-icon-green"><i class="bi bi-check2-circle"></i></div>
                <div>
                    <div class="stat-label">Usage Match</div>
                    <div class="stat-value">{{ $matches }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-3 col-md-4 col-6">
            <div class="stat-card">
                <div class="stat-icon stat-icon-red"><i class="bi bi-x-circle"></i></div>
                <div>
                    <div class="stat-label">Usage Variation</div>
                    <div class="stat-value">{{ $variations }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-3 col-md-4 col-6">
            <div class="stat-card">
                <div class="stat-icon stat-icon-amber"><i class="bi bi-exclamation-triangle"></i></div>
                <div>
                    <div class="stat-label">Partial Match</div>
                    <div class="stat-value">{{ $partialMatches }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-3 col-md-4 col-6">
            <div class="stat-card">
                <div class="stat-icon stat-icon-cyan"><i class="bi bi-building"></i></div>
                <div>
                    <div class="stat-label">Building Only</div>
                    <div class="stat-value">{{ $buildingOnly }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-3 col-md-4 col-6">
            <div class="stat-card">
                <div class="stat-icon stat-icon-purple"><i class="bi bi-file-earmark-text"></i></div>
                <div>
                    <div class="stat-label">Assessment Only</div>
                    <div class="stat-value">{{ $assessmentOnly }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-3 col-md-4 col-6">
            <div class="stat-card">
                <div class="stat-icon stat-icon-pink"><i class="bi bi-dash-circle"></i></div>
                <div>
                    <div class="stat-label">No Data</div>
                    <div class="stat-value">{{ $noData }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-3 col-md-4 col-6">
            <div class="stat-card">
                <div class="stat-icon stat-icon-cyan"><i class="bi bi-arrows-expand"></i></div>
                <div>
                    <div class="stat-label">Area Variation</div>
                    <div class="stat-value">{{ $areaVariations }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── FILTER SECTION ─── -->
    <div class="filter-card">
        <form id="filterForm" method="GET" action="{{ route('variation.show', $ward->id) }}">
            <div class="row g-3 align-items-end">
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label"><i class="bi bi-tags me-1"></i>Usage Status</label>
                    <select name="usage_status" id="filterUsageStatus" class="form-select form-select-sm">
                        <option value="all" {{ request('usage_status') == 'all' ? 'selected' : '' }}>All Status</option>
                        <option value="MATCH" {{ request('usage_status') == 'MATCH' ? 'selected' : '' }}>✅ Match</option>
                        <option value="VARIATION" {{ request('usage_status') == 'VARIATION' ? 'selected' : '' }}>❌ Variation</option>
                        <option value="PARTIAL_MATCH" {{ request('usage_status') == 'PARTIAL_MATCH' ? 'selected' : '' }}>⚠️ Partial Match</option>
                        <option value="BUILDING_ONLY" {{ request('usage_status') == 'BUILDING_ONLY' ? 'selected' : '' }}>🏢 Building Only</option>
                        <option value="ASSESSMENT_ONLY" {{ request('usage_status') == 'ASSESSMENT_ONLY' ? 'selected' : '' }}>📄 Assessment Only</option>
                        <option value="NO_DATA" {{ request('usage_status') == 'NO_DATA' ? 'selected' : '' }}>⬜ No Data</option>
                    </select>
                </div>

                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label"><i class="bi bi-rulers me-1"></i>Area Status</label>
                    <select name="area_status" id="filterAreaStatus" class="form-select form-select-sm">
                        <option value="all" {{ request('area_status') == 'all' ? 'selected' : '' }}>All Status</option>
                        <option value="MATCH" {{ request('area_status') == 'MATCH' ? 'selected' : '' }}>Match</option>
                        <option value="VARIATION" {{ request('area_status') == 'VARIATION' ? 'selected' : '' }}>Variation</option>
                    </select>
                </div>

                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label"><i class="bi bi-hash me-1"></i>GIS ID</label>
                    <input type="text" name="gisid" id="filterGisid" class="form-control form-control-sm"
                           placeholder="Search GIS ID..." value="{{ request('gisid') }}">
                </div>

                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label"><i class="bi bi-file-earmark-text me-1"></i>Assessment Count</label>
                    <select name="assessment_count" id="filterAssessmentCount" class="form-select form-select-sm">
                        <option value="all" {{ request('assessment_count') == 'all' ? 'selected' : '' }}>All Counts</option>
                        <option value="0" {{ request('assessment_count') == '0' ? 'selected' : '' }}>No Assessments</option>
                        <option value="1" {{ request('assessment_count') == '1' ? 'selected' : '' }}>1 Assessment</option>
                        <option value="2" {{ request('assessment_count') == '2' ? 'selected' : '' }}>2 Assessments</option>
                        <option value="3" {{ request('assessment_count') == '3' ? 'selected' : '' }}>3+ Assessments</option>
                    </select>
                </div>

                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label"><i class="bi bi-sliders me-1"></i>Area Variation %</label>
                    <div class="d-flex gap-2">
                        <input type="number" name="var_min" id="filterVarMin" class="form-control form-control-sm"
                               placeholder="Min %" min="0" max="100" value="{{ request('var_min') }}">
                        <input type="number" name="var_max" id="filterVarMax" class="form-control form-control-sm"
                               placeholder="Max %" min="0" max="100" value="{{ request('var_max') }}">
                    </div>
                </div>

                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary btn-filter">
                            <i class="bi bi-funnel me-1"></i> Apply
                        </button>
                        <a href="{{ route('variation.show', $ward->id) }}" class="btn btn-outline-secondary btn-filter">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>
                    </div>
                </div>
            </div>

            <!-- Filter Status and Export -->
            <div class="row mt-3 pt-3 border-top">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="text-muted small">
                            <i class="bi bi-info-circle me-1"></i>
                            Showing <span id="visibleCount">{{ $total }}</span> of <span id="totalCount">{{ $total }}</span> buildings
                        </span>
                        <!-- Filtered Export Button -->
                        <button type="button" class="btn btn-export btn-export-excel btn-sm" id="exportFilteredExcel">
                            <i class="bi bi-file-earmark-excel me-1"></i> Export Filtered
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- ─── TABLE ─── -->
    <div class="table-container" id="tableContainer">
        <div class="table-header">
            <h5><i class="bi bi-table me-2"></i>Data Variation Details</h5>
            <span class="text-muted small">Total: {{ $total }} records</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="variationTable">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>GIS ID</th>
                        <th>Building Usage</th>
                        <th>Assessment Usage</th>
                        <th>Usage Status</th>
                        <th>Building Area</th>
                        <th>Assessment Area</th>
                        <th>Area Variation</th>
                        <th>Area Status</th>
                        <th>Assessments</th>
                        <th style="width:100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($buildingVariations as $gisid => $variation)
                        @php
                            $buildingArea = $variation['building']['area'] ?? 0;
                            $buildingUsage = $variation['building']['usage'] ?? null;
                            $assessmentArea = $variation['assessment']['area'] ?? 0;
                            $assessmentUsage = $variation['assessment']['usage'] ?? null;
                            $assessmentCount = $variation['assessment']['count'] ?? 0;
                            $usageStatus = $variation['usage_comparison']['usage_status'] ?? 'NO_DATA';
                            $usageStatusLabel = $variation['usage_comparison']['usage_status_label'] ?? 'No Data';
                            $areaVariation = $variation['area_comparison']['area_variation'] ?? 0;
                            $areaStatus = $variation['area_comparison']['area_status'] ?? 'MATCH';
                            $variationPercentage = $variation['area_comparison']['variation_percentage'] ?? 0;
                            $allAssessmentUsages = $variation['assessment']['all_usages'] ?? [];
                            $hasMultiple = $variation['assessment']['has_multiple'] ?? false;
                            $floorCount = $variation['building']['details']['number_floor'] ?? 'N/A';
                            $basementCount = $variation['building']['details']['basement'] ?? 'N/A';

                            // Badge class
                            $badgeClass = '';
                            $icon = '';
                            switch($usageStatus) {
                                case 'MATCH': $badgeClass = 'badge-match'; $icon = 'bi-check-circle'; break;
                                case 'VARIATION': $badgeClass = 'badge-variation'; $icon = 'bi-x-circle'; break;
                                case 'PARTIAL_MATCH': $badgeClass = 'badge-partial'; $icon = 'bi-exclamation-triangle'; break;
                                case 'BUILDING_ONLY': $badgeClass = 'badge-building-only'; $icon = 'bi-building'; break;
                                case 'ASSESSMENT_ONLY': $badgeClass = 'badge-assessment-only'; $icon = 'bi-file-earmark-text'; break;
                                default: $badgeClass = 'badge-no-data'; $icon = 'bi-dash-circle'; break;
                            }

                            $isUsageVariation = $usageStatus === 'VARIATION';
                            $isPartialMatch = $usageStatus === 'PARTIAL_MATCH';
                            $isBuildingOnly = $usageStatus === 'BUILDING_ONLY';
                            $isAssessmentOnly = $usageStatus === 'ASSESSMENT_ONLY';
                            $isNoData = $usageStatus === 'NO_DATA';

                            $usageValueClass = $isUsageVariation ? 'mismatch' : ($isBuildingOnly || $isAssessmentOnly || $isNoData ? 'null-value' : 'match');

                            $assessmentDisplay = $assessmentUsage ?? 'N/A';
                            if ($isBuildingOnly) $assessmentDisplay = '— (Not Assessed)';
                            if ($isNoData) $assessmentDisplay = '— (No Data)';

                            $buildingDisplay = $buildingUsage ?? 'N/A';
                            if ($isAssessmentOnly) $buildingDisplay = '— (Not Mapped)';
                            if ($isNoData) $buildingDisplay = '— (No Data)';

                            $usageTooltip = !empty($allAssessmentUsages) ? implode(', ', $allAssessmentUsages) : 'No usages';
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <code>{{ $gisid }}</code>
                                @if($hasMultiple)
                                    <span class="badge bg-info ms-1" title="Multiple assessments">M</span>
                                @endif
                            </td>
                            <td>
                                <span class="usage-detail-box" title="Building Usage">
                                    <span class="label">Usage:</span>
                                    <span class="value {{ $usageValueClass }}">{{ $buildingDisplay }}</span>
                                </span>
                                <div class="small text-muted mt-1">
                                    <span class="badge bg-light text-dark">F: {{ $floorCount }}</span>
                                    <span class="badge bg-light text-dark">B: {{ $basementCount }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="usage-detail-box" title="Assessment Usage">
                                    <span class="label">Usage:</span>
                                    <span class="value {{ $usageValueClass }}">{{ $assessmentDisplay }}</span>
                                </span>
                                @if($isUsageVariation)
                                    <span class="badge badge-variation ms-1" title="Usage mismatch">Mismatch</span>
                                @endif
                                @if($isPartialMatch)
                                    <span class="badge badge-partial ms-1" title="Partial match">Partial</span>
                                @endif
                                @if(!empty($allAssessmentUsages) && count($allAssessmentUsages) > 1)
                                    <div class="small text-muted mt-1">
                                        <i class="bi bi-list-ul"></i>
                                        <span title="All usages: {{ $usageTooltip }}">{{ count($allAssessmentUsages) }} usages</span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="{{ $badgeClass }}" title="{{ $usageStatusLabel }}">
                                    <i class="{{ $icon }} me-1"></i>
                                    {{ $usageStatusLabel }}
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold">{{ number_format($buildingArea, 2) }}</span>
                                <span class="text-muted small">sqft</span>
                            </td>
                            <td>
                                <span class="fw-bold">{{ number_format($assessmentArea, 2) }}</span>
                                <span class="text-muted small">sqft</span>
                            </td>
                            <td class="{{ $areaVariation > 0 ? 'text-danger' : ($areaVariation < 0 ? 'text-success' : 'text-muted') }}">
                                {{ $areaVariation > 0 ? '+' : '' }}{{ number_format($areaVariation, 2) }}
                                <br>
                                <small class="text-muted">{{ number_format($variationPercentage, 1) }}%</small>
                            </td>
                            <td>
                                <span class="{{ $areaStatus == 'VARIATION' ? 'badge-variation' : 'badge-match' }}">
                                    {{ $areaStatus }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $assessmentCount == 0 ? 'bg-secondary' : 'bg-primary' }}">
                                    {{ $assessmentCount }}
                                </span>
                                @if($assessmentCount > 0)
                                    <br>
                                    <small class="text-muted">assessments</small>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <div class="variation-progress" style="width:40px;">
                                        <div class="bar {{ $assessmentCount > 0 ? 'bar-success' : 'bar-danger' }}"
                                             style="width: {{ min($assessmentCount * 25, 100) }}%;"></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-info"
                                            onclick="showDetails('{{ $gisid }}')"
                                            title="View Details">
                                        <i class="bi bi-info-circle"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11">
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

<!-- ─── DETAIL MODAL ─── -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">
                    <i class="bi bi-info-circle text-primary me-2"></i>
                    Building Details - <span id="modalGisid"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" class="btn btn-export btn-export-excel" id="exportSingleBtn">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export Single
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Store data globally
        const buildingData = @json($buildingVariations);
        let detailModal = null;

        $(document).ready(function() {
            detailModal = new bootstrap.Modal(document.getElementById('detailModal'));

            // ─── SHOW DETAILS ───
            window.showDetails = function(gisid) {
                const data = buildingData[gisid];
                if (!data) {
                    Swal.fire('Error', 'No data found for GIS ID: ' + gisid, 'error');
                    return;
                }

                $('#modalGisid').text(gisid);

                // Update export single button
                $('#exportSingleBtn').attr('href', "{{ route('variation.export', $ward->id) }}?gisid=" + gisid);

                let html = `
                    <div class="detail-modal-content">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <h6><i class="bi bi-building text-primary me-2"></i>Building Details</h6>
                                    <div class="detail-item">
                                        <span class="label">Area</span>
                                        <span class="value">${data.building.area.toFixed(2)} sqft</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Usage</span>
                                        <span class="value">${data.building.usage || 'N/A'}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Number of Floors</span>
                                        <span class="value">${data.building.details?.number_floor || 'N/A'}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Basement</span>
                                        <span class="value">${data.building.details?.basement || 'N/A'}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Percentage</span>
                                        <span class="value">${data.building.details?.percentage || 'N/A'}%</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Polygon Sqfeet</span>
                                        <span class="value">${data.building.details?.sqfeet?.toFixed(2) || 'N/A'}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <h6><i class="bi bi-file-earmark-text text-success me-2"></i>Assessment Details</h6>
                                    <div class="detail-item">
                                        <span class="label">Area</span>
                                        <span class="value">${data.assessment.area.toFixed(2)} sqft</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Usage</span>
                                        <span class="value">${data.assessment.usage || 'N/A'}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Count</span>
                                        <span class="value">${data.assessment.count}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">All Usages</span>
                                        <span class="value">${data.assessment.all_usages.join(', ') || 'None'}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Has Multiple</span>
                                        <span class="value">${data.assessment.has_multiple ? 'Yes' : 'No'}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Assessment Type</span>
                                        <span class="value">${data.assessment.details?.assessment_type_status || 'N/A'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-md-6">
                                <div class="detail-section ${data.area_comparison.area_status === 'VARIATION' ? 'warning' : 'success'}">
                                    <h6><i class="bi bi-arrows-expand text-cyan me-2"></i>Area Comparison</h6>
                                    <div class="detail-item">
                                        <span class="label">Status</span>
                                        <span class="value">
                                            <span class="${data.area_comparison.area_status === 'VARIATION' ? 'badge-variation' : 'badge-match'}">
                                                ${data.area_comparison.area_status}
                                            </span>
                                        </span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Building Area</span>
                                        <span class="value">${data.area_comparison.building_area.toFixed(2)} sqft</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Assessment Area</span>
                                        <span class="value">${data.area_comparison.assessment_area.toFixed(2)} sqft</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Variation</span>
                                        <span class="value ${data.area_comparison.area_variation > 0 ? 'text-danger' : 'text-success'}">
                                            ${data.area_comparison.area_variation > 0 ? '+' : ''}${data.area_comparison.area_variation.toFixed(2)} sqft
                                        </span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Variation %</span>
                                        <span class="value">${data.area_comparison.variation_percentage}%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-section ${data.usage_comparison.usage_status === 'VARIATION' ? 'danger' : (data.usage_comparison.usage_status === 'PARTIAL_MATCH' ? 'warning' : 'success')}">
                                    <h6><i class="bi bi-tags text-amber me-2"></i>Usage Comparison</h6>
                                    <div class="detail-item">
                                        <span class="label">Status</span>
                                        <span class="value">
                                            <span class="${data.usage_comparison.usage_badge_class}">
                                                ${data.usage_comparison.usage_status_label}
                                            </span>
                                        </span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Building Usage</span>
                                        <span class="value">${data.usage_comparison.building_usage || 'N/A'}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Assessment Usage</span>
                                        <span class="value">${data.usage_comparison.assessment_usage || 'N/A'}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">All Assessment Usages</span>
                                        <span class="value">${data.usage_comparison.all_assessment_usages.join(', ') || 'None'}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Has Mismatch</span>
                                        <span class="value">${data.usage_comparison.has_mismatch ? 'Yes' : 'No'}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Has Partial Match</span>
                                        <span class="value">${data.usage_comparison.has_partial_match ? 'Yes' : 'No'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-12">
                                <div class="detail-section">
                                    <h6><i class="bi bi-database text-secondary me-2"></i>Raw Data</h6>
                                    <pre style="background:#f8fafc;padding:12px;border-radius:8px;border:1px solid #e5e7eb;font-size:0.75rem;max-height:200px;overflow:auto;">${JSON.stringify(data.raw_data, null, 2)}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                $('#modalBody').html(html);
                detailModal.show();
            };

            // ─── EXPORT FILTERED DATA ───
            $('#exportFilteredExcel').on('click', function() {
                const form = document.getElementById('filterForm');
                const formData = new FormData(form);
                const params = new URLSearchParams(formData);

                // Show loading
                Swal.fire({
                    title: 'Exporting...',
                    text: 'Please wait while we generate your Excel file',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                // Redirect to export with filters
                window.location.href = "{{ route('data-variation.export', $ward->id) }}?" + params.toString();

                setTimeout(() => {
                    Swal.close();
                }, 2000);
            });

            // ─── EXPORT ALL BUTTONS ───
            $('.btn-export-excel, .btn-export-pdf, .btn-export-csv').on('click', function(e) {
                // Show loading for export all
                if (!$(this).hasClass('btn-sm') || $(this).attr('id') !== 'exportFilteredExcel') {
                    Swal.fire({
                        title: 'Exporting...',
                        text: 'Please wait while we generate your file',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    setTimeout(() => {
                        Swal.close();
                    }, 2000);
                }
            });

            console.log('✅ Data Variation page ready');
            console.log(`📊 Total buildings: ${Object.keys(buildingData).length}`);
        });
    </script>
@endpush

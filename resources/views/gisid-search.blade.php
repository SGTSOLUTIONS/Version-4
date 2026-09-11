@extends('layouts.office')

@section('title', 'GIS ID Search — Revenue Department')
@section('page_title', 'GIS ID Search')

@push('styles')
<style>
    .gis-search-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        padding: 24px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        margin-bottom: 24px;
    }

    .gis-search-input-wrap {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .gis-search-input {
        flex: 1;
        border-radius: 12px !important;
        border: 2px solid #e5e7eb !important;
        padding: 14px 18px !important;
        font-size: 1rem !important;
        transition: all 0.2s !important;
    }

    .gis-search-input:focus {
        border-color: #2563eb !important;
        box-shadow: 0 0 0 4px rgba(37,99,235,0.1) !important;
    }

    .gis-search-btn {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 14px 32px;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.2s;
        white-space: nowrap;
    }

    .gis-search-btn:hover {
        background: linear-gradient(135deg, #1d4ed8, #1e40af);
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(37,99,235,0.3);
    }

    .gis-search-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* Result Section */
    .result-section {
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .result-header {
        background: linear-gradient(135deg, #1e3a5f, #2563eb);
        color: white;
        padding: 18px 24px;
        border-radius: 14px 14px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .result-header h5 {
        margin: 0;
        font-weight: 700;
    }

    .gis-badge {
        background: rgba(255,255,255,0.2);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .result-body {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-top: none;
        border-radius: 0 0 14px 14px;
        padding: 24px;
    }

    /* Tabs */
    .gis-tabs {
        display: flex;
        gap: 4px;
        border-bottom: 2px solid #e5e7eb;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .gis-tab-btn {
        background: none;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 10px 20px;
        font-weight: 600;
        font-size: 0.875rem;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s;
        margin-bottom: -2px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .gis-tab-btn:hover {
        color: #2563eb;
        background: #eff6ff;
        border-radius: 8px 8px 0 0;
    }

    .gis-tab-btn.active {
        color: #2563eb;
        border-bottom-color: #2563eb;
        background: none;
    }

    .gis-tab-btn .badge {
        background: #2563eb;
        font-size: 0.65rem;
        padding: 2px 8px;
    }

    .gis-tab-pane {
        display: none;
    }

    .gis-tab-pane.active {
        display: block;
        animation: fadeIn 0.2s ease;
    }

    /* Cards inside tabs */
    .data-card {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px 18px;
        margin-bottom: 14px;
    }

    .data-card-title {
        font-size: 0.8rem;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .form-label-sm {
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .form-control-sm-custom {
        border-radius: 8px !important;
        border: 1.5px solid #e5e7eb !important;
        font-size: 0.85rem !important;
        padding: 8px 12px !important;
        transition: all 0.2s !important;
    }

    .form-control-sm-custom:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59,130,246,0.1) !important;
    }

    .save-all-btn {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 14px 36px;
        font-weight: 700;
        font-size: 1rem;
        transition: all 0.2s;
        box-shadow: 0 4px 14px rgba(34,197,94,0.3);
    }

    .save-all-btn:hover {
        background: linear-gradient(135deg, #16a34a, #15803d);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(34,197,94,0.4);
        color: white;
    }

    .save-all-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #94a3b8;
    }

    .empty-state i {
        font-size: 48px;
        margin-bottom: 12px;
        color: #cbd5e1;
    }

    .record-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #eff6ff;
        color: #2563eb;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-bottom: 12px;
    }

    .remove-record-btn {
        background: #fff1f2;
        color: #dc2626;
        border: none;
        width: 28px;
        height: 28px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .remove-record-btn:hover {
        background: #dc2626;
        color: white;
    }

    .add-record-btn {
        background: #eff6ff;
        color: #2563eb;
        border: 2px dashed #93c5fd;
        border-radius: 10px;
        padding: 10px 20px;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.2s;
        width: 100%;
    }

    .add-record-btn:hover {
        background: #dbeafe;
        border-color: #2563eb;
        color: #1d4ed8;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
    }

    .info-item {
        background: #fff;
        padding: 10px 14px;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
    }

    .info-item-label {
        font-size: 0.7rem;
        color: #94a3b8;
        font-weight: 600;
        text-transform: uppercase;
    }

    .info-item-value {
        font-size: 0.9rem;
        color: #1e293b;
        font-weight: 600;
        margin-top: 2px;
    }
</style>
@endpush

@section('content')
<div class="ol-page-header">
    <div>
        <h1 class="ol-page-title">
            <i class="bi bi-search me-2"></i>GIS ID Search
        </h1>
        <p class="ol-page-sub">
            Enter a GIS ID to view and update all related details (Building, Assessment, Water Tax, UGD, Professional Tax)
        </p>
    </div>
</div>

{{-- Search Box --}}
<div class="gis-search-card">
    <div class="gis-search-input-wrap">
        <input type="text"
               id="gisIdInput"
               class="form-control gis-search-input"
               placeholder="Enter GIS ID (e.g., 123456)"
               autocomplete="off">
        <button class="gis-search-btn" id="searchBtn">
            <i class="bi bi-search me-2"></i>Search
        </button>
    </div>
    <div id="searchError" class="text-danger mt-2" style="font-size:0.85rem; display:none;"></div>
</div>

{{-- Results --}}
<div id="resultContainer" style="display:none;">
    <div class="result-section">
        <div class="result-header">
            <div>
                <h5><i class="bi bi-geo-alt-fill me-2"></i>GIS ID: <span id="resultGisid"></span></h5>
                <small style="opacity:0.85;" id="resultMeta"></small>
            </div>
            <div>
                <span class="gis-badge" id="resultWard"></span>
            </div>
        </div>

        <div class="result-body">
            {{-- Tabs --}}
            <div class="gis-tabs">
                <button class="gis-tab-btn active" data-tab="building">
                    <i class="bi bi-building"></i> Building
                </button>
                <button class="gis-tab-btn" data-tab="assessment">
                    <i class="bi bi-clipboard-check"></i> Assessment
                    <span class="badge" id="tabCountAssessment">0</span>
                </button>
                <button class="gis-tab-btn" data-tab="water">
                    <i class="bi bi-droplet"></i> Water Tax
                    <span class="badge" id="tabCountWater">0</span>
                </button>
                <button class="gis-tab-btn" data-tab="ugd">
                    <i class="bi bi-water"></i> UGD Tax
                    <span class="badge" id="tabCountUgd">0</span>
                </button>
                <button class="gis-tab-btn" data-tab="professional">
                    <i class="bi bi-briefcase"></i> Professional Tax
                    <span class="badge" id="tabCountProfessional">0</span>
                </button>
            </div>

            <form id="updateAllForm">
                @csrf
                <input type="hidden" name="gisid" id="hiddenGisid">

                {{-- TAB: Building --}}
                <div class="gis-tab-pane active" id="tab-building">
                    <div class="data-card">
                        <div class="data-card-title">
                            <i class="bi bi-building"></i> Building Information
                        </div>
                        <div class="row g-3" id="buildingFields"></div>
                    </div>
                </div>

                {{-- TAB: Assessment --}}
                <div class="gis-tab-pane" id="tab-assessment">
                    <div id="assessmentList"></div>
                </div>

                {{-- TAB: Water Tax --}}
                <div class="gis-tab-pane" id="tab-water">
                    <div id="waterList"></div>
                </div>

                {{-- TAB: UGD Tax --}}
                <div class="gis-tab-pane" id="tab-ugd">
                    <div id="ugdList"></div>
                </div>

                {{-- TAB: Professional Tax --}}
                <div class="gis-tab-pane" id="tab-professional">
                    <div id="professionalList"></div>
                </div>

                {{-- Save All Button --}}
                <div class="text-end mt-4 pt-3 border-top">
                    <button type="submit" class="save-all-btn" id="saveAllBtn">
                        <i class="bi bi-check-circle me-2"></i>Save All Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Empty State --}}
<div id="emptyState" class="gis-search-card empty-state">
    <i class="bi bi-search"></i>
    <h5>Search for a GIS ID</h5>
    <p>Enter a GIS ID above to view all related details</p>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    let currentData = null;

    // ─── TAB SWITCHING ───
    $(document).on('click', '.gis-tab-btn', function() {
        const tab = $(this).data('tab');
        $('.gis-tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.gis-tab-pane').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });

    // ─── SEARCH ───
    $('#searchBtn').on('click', function() {
        const gisid = $('#gisIdInput').val().trim();
        if (!gisid) {
            showError('Please enter a GIS ID');
            return;
        }
        performSearch(gisid);
    });

    $('#gisIdInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#searchBtn').click();
        }
    });

    function showError(msg) {
        $('#searchError').text(msg).show();
        setTimeout(() => $('#searchError').fadeOut(), 5000);
    }

    function performSearch(gisid) {
        const $btn = $('#searchBtn');
        const originalHtml = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin me-2"></i>Searching...').prop('disabled', true);
        $('#searchError').hide();

        $.ajax({
            url: '{{ route("gisid.fetch") }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: { gisid },
            success: function(res) {
                $btn.html(originalHtml).prop('disabled', false);

                if (!res.success) {
                    showError(res.message || 'Not found');
                    $('#resultContainer').hide();
                    $('#emptyState').show();
                    return;
                }

                currentData = res.data;
                renderResults(res.data);
            },
            error: function(xhr) {
                $btn.html(originalHtml).prop('disabled', false);
                const msg = xhr.responseJSON?.message || 'Search failed';
                showError(msg);
            }
        });
    }

    function renderResults(data) {
        $('#emptyState').hide();
        $('#resultContainer').show();

        // Header
        $('#resultGisid').text(data.gisid);
        $('#resultWard').text('Ward: ' + (data.ward?.ward_no || 'N/A'));
        $('#resultMeta').text(
            (data.zone?.name || '') + ' | ' + (data.corporation?.name || '')
        );
        $('#hiddenGisid').val(data.gisid);

        // Counts
        const assessments = data.assessments || [];
        $('#tabCountAssessment').text(assessments.length);
        $('#tabCountWater').text(assessments.filter(a => a.waterTax).length);
        $('#tabCountUgd').text(assessments.filter(a => a.ugdTax).length);
        $('#tabCountProfessional').text(
            assessments.reduce((sum, a) => sum + (a.professionalTaxes?.length || 0), 0)
        );

        renderBuilding(data.building);
        renderAssessments(assessments);
    }

    // ─── BUILDING FIELDS (same as before) ───
    function renderBuilding(b) {
        if (!b) {
            $('#buildingFields').html('<div class="col-12 text-muted">No building data</div>');
            return;
        }

        const fields = [
            { label: 'Building Name', name: 'building[building_name]', value: b.building_name, type: 'text' },
            { label: 'Road Name', name: 'building[road_name]', value: b.road_name, type: 'text' },
            { label: 'Phone', name: 'building[phone]', value: b.phone, type: 'text' },
            { label: 'No. of Bills', name: 'building[number_bill]', value: b.number_bill, type: 'number' },
            { label: 'No. of Shops', name: 'building[number_shop]', value: b.number_shop, type: 'number' },
            { label: 'No. of Floors', name: 'building[number_floor]', value: b.number_floor, type: 'number' },
            { label: 'Percentage', name: 'building[percentage]', value: b.percentage, type: 'number' },
            { label: 'Basement', name: 'building[basement]', value: b.basement, type: 'number' },
            { label: 'Zonation', name: 'building[building_zone]', value: b.zone, type: 'text' },
            { label: 'Building Usage', name: 'building[building_usage]', value: b.building_usage, type: 'text' },
            { label: 'Construction Type', name: 'building[construction_type]', value: b.construction_type, type: 'text' },
            { label: 'Building Type', name: 'building[building_type]', value: b.building_type, type: 'text' },
            { label: 'UGD Status', name: 'building[ugd]', value: b.ugd, type: 'text' },
        ];

        let html = '';
        fields.forEach(f => {
            html += `
                <div class="col-md-4">
                    <div class="form-label-sm">${f.label}</div>
                    <input type="${f.type}" name="${f.name}" class="form-control form-control-sm-custom" value="${f.value || ''}">
                </div>`;
        });

        html += `
            <div class="col-md-6">
                <div class="form-label-sm">Remarks</div>
                <textarea name="building[remarks]" class="form-control form-control-sm-custom" rows="2">${b.remarks || ''}</textarea>
            </div>
            <div class="col-md-6">
                <div class="form-label-sm">Corporation Remarks</div>
                <textarea name="building[corporationremarks]" class="form-control form-control-sm-custom" rows="2">${b.corporationremarks || ''}</textarea>
            </div>
            <div class="col-12">
                <div class="form-label-sm">QC Remarks</div>
                <textarea name="building[qc_remarks]" class="form-control form-control-sm-custom" rows="2">${b.qc_remarks || ''}</textarea>
            </div>`;

        $('#buildingFields').html(html);
    }

    // ─── ASSESSMENTS (with nested Water/UGD/Professional) ───
    function renderAssessments(assessments) {
        if (!assessments.length) {
            $('#assessmentList').html('<div class="empty-state"><i class="bi bi-inbox"></i><p>No assessments found</p></div>');
            $('#waterList').html('<div class="empty-state"><i class="bi bi-droplet"></i><p>No water tax records</p></div>');
            $('#ugdList').html('<div class="empty-state"><i class="bi bi-water"></i><p>No UGD records</p></div>');
            $('#professionalList').html('<div class="empty-state"><i class="bi bi-briefcase"></i><p>No professional tax records</p></div>');
            return;
        }

        // ── ASSESSMENT TAB ──
        let assessmentHtml = '';
        assessments.forEach((a, i) => {
            const pd = a.pointData || {};
            assessmentHtml += `
                <div class="data-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="record-chip"><i class="bi bi-clipboard"></i> Assessment #${i + 1} — ${pd.assessment || 'N/A'}</span>
                        <input type="hidden" name="assessments[${i}][pointData][id]" value="${pd.id || ''}">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="form-label-sm">Assessment Type</div>
                            <input type="text" name="assessments[${i}][pointData][assessment_type]" class="form-control form-control-sm-custom" value="${pd.assessment_type || ''}">
                        </div>
                        <div class="col-md-3">
                            <div class="form-label-sm">Assessment</div>
                            <input type="text" name="assessments[${i}][pointData][assessment]" class="form-control form-control-sm-custom" value="${pd.assessment || ''}">
                        </div>
                        <div class="col-md-3">
                            <div class="form-label-sm">Old Assessment</div>
                            <input type="text" name="assessments[${i}][pointData][old_assessment]" class="form-control form-control-sm-custom" value="${pd.old_assessment || ''}">
                        </div>
                        <div class="col-md-3">
                            <div class="form-label-sm">Zonation</div>
                            <input type="text" name="assessments[${i}][pointData][zone]" class="form-control form-control-sm-custom" value="${pd.zone || ''}">
                        </div>
                        <div class="col-md-6">
                            <div class="form-label-sm">Owner Name</div>
                            <input type="text" name="assessments[${i}][pointData][owner_name]" class="form-control form-control-sm-custom" value="${pd.owner_name || ''}">
                        </div>
                        <div class="col-md-6">
                            <div class="form-label-sm">Present Owner Name</div>
                            <input type="text" name="assessments[${i}][pointData][present_owner_name]" class="form-control form-control-sm-custom" value="${pd.present_owner_name || ''}">
                        </div>
                        <div class="col-md-3">
                            <div class="form-label-sm">Phone</div>
                            <input type="text" name="assessments[${i}][pointData][phone_number]" class="form-control form-control-sm-custom" value="${pd.phone_number || ''}">
                        </div>
                        <div class="col-md-3">
                            <div class="form-label-sm">Old Door No</div>
                            <input type="text" name="assessments[${i}][pointData][old_door_no]" class="form-control form-control-sm-custom" value="${pd.old_door_no || ''}">
                        </div>
                        <div class="col-md-3">
                            <div class="form-label-sm">New Door No</div>
                            <input type="text" name="assessments[${i}][pointData][new_door_no]" class="form-control form-control-sm-custom" value="${pd.new_door_no || ''}">
                        </div>
                        <div class="col-md-3">
                            <div class="form-label-sm">Floor</div>
                            <input type="text" name="assessments[${i}][pointData][floor]" class="form-control form-control-sm-custom" value="${pd.floor || ''}">
                        </div>
                        <div class="col-md-3">
                            <div class="form-label-sm">Aadhar No</div>
                            <input type="text" name="assessments[${i}][pointData][aadhar_no]" class="form-control form-control-sm-custom" value="${pd.aadhar_no || ''}">
                        </div>
                        <div class="col-md-3">
                            <div class="form-label-sm">Ration No</div>
                            <input type="text" name="assessments[${i}][pointData][ration_no]" class="form-control form-control-sm-custom" value="${pd.ration_no || ''}">
                        </div>
                        <div class="col-md-3">
                            <div class="form-label-sm">No. of Persons</div>
                            <input type="text" name="assessments[${i}][pointData][number_persons]" class="form-control form-control-sm-custom" value="${pd.no_of_persons || ''}">
                        </div>
                        <div class="col-md-3">
                            <div class="form-label-sm">Bill Usage</div>
                            <input type="text" name="assessments[${i}][pointData][bill_usage]" class="form-control form-control-sm-custom" value="${pd.bill_usage || ''}">
                        </div>
                        <div class="col-md-3">
                            <div class="form-label-sm">EB</div>
                            <input type="text" name="assessments[${i}][pointData][eb]" class="form-control form-control-sm-custom" value="${pd.eb || ''}">
                        </div>
                        <div class="col-12">
                            <div class="form-label-sm">Remarks</div>
                            <textarea name="assessments[${i}][pointData][remarks]" class="form-control form-control-sm-custom" rows="2">${pd.remarks || ''}</textarea>
                        </div>
                    </div>
                </div>`;
        });
        $('#assessmentList').html(assessmentHtml);

        // ── WATER TAX TAB (assessment-wise) ──
        let waterHtml = '';
        assessments.forEach((a, i) => {
            const wt = a.waterTax || {};
            const pd = a.pointData || {};
            waterHtml += `
                <div class="data-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="record-chip"><i class="bi bi-droplet"></i> Assessment: ${pd.assessment || 'N/A'}</span>
                        <input type="hidden" name="assessments[${i}][waterTax][id]" value="${wt.id || ''}">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-label-sm">Water Tax No</div>
                            <input type="text" name="assessments[${i}][waterTax][watertax_no]" class="form-control form-control-sm-custom" value="${wt.watertax_no || ''}">
                        </div>
                        <div class="col-md-4">
                            <div class="form-label-sm">Old Water Tax No</div>
                            <input type="text" name="assessments[${i}][waterTax][old_watertax_no]" class="form-control form-control-sm-custom" value="${wt.old_watertax_no || ''}">
                        </div>
                        <div class="col-md-4">
                            <div class="form-label-sm">Usage</div>
                            <input type="text" name="assessments[${i}][waterTax][usage]" class="form-control form-control-sm-custom" value="${wt.usage || ''}">
                        </div>
                        <div class="col-md-4">
                            <div class="form-label-sm">DBC Type</div>
                            <input type="text" name="assessments[${i}][waterTax][DBC_type]" class="form-control form-control-sm-custom" value="${wt.DBC_type || ''}">
                        </div>
                        <div class="col-12">
                            <div class="form-label-sm">Slab Description</div>
                            <textarea name="assessments[${i}][waterTax][slab_description]" class="form-control form-control-sm-custom" rows="2">${wt.slab_description || ''}</textarea>
                        </div>
                    </div>
                </div>`;
        });
        $('#waterList').html(waterHtml || '<div class="empty-state"><i class="bi bi-droplet"></i><p>No water tax records</p></div>');

        // ── UGD TAB (assessment-wise) ──
        let ugdHtml = '';
        assessments.forEach((a, i) => {
            const ut = a.ugdTax || {};
            const pd = a.pointData || {};
            ugdHtml += `
                <div class="data-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="record-chip"><i class="bi bi-water"></i> Assessment: ${pd.assessment || 'N/A'}</span>
                        <input type="hidden" name="assessments[${i}][ugdTax][id]" value="${ut.id || ''}">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-label-sm">UGD No</div>
                            <input type="text" name="assessments[${i}][ugdTax][ugd_no]" class="form-control form-control-sm-custom" value="${ut.ugd_no || ''}">
                        </div>
                        <div class="col-md-4">
                            <div class="form-label-sm">Old UGD No</div>
                            <input type="text" name="assessments[${i}][ugdTax][old_ugd_no]" class="form-control form-control-sm-custom" value="${ut.old_ugd_no || ''}">
                        </div>
                        <div class="col-md-4">
                            <div class="form-label-sm">Usage</div>
                            <input type="text" name="assessments[${i}][ugdTax][usage]" class="form-control form-control-sm-custom" value="${ut.usage || ''}">
                        </div>
                        <div class="col-md-4">
                            <div class="form-label-sm">DBC Type</div>
                            <input type="text" name="assessments[${i}][ugdTax][DBC_type]" class="form-control form-control-sm-custom" value="${ut.DBC_type || ''}">
                        </div>
                        <div class="col-12">
                            <div class="form-label-sm">Slab Description</div>
                            <textarea name="assessments[${i}][ugdTax][slab_description]" class="form-control form-control-sm-custom" rows="2">${ut.slab_description || ''}</textarea>
                        </div>
                    </div>
                </div>`;
        });
        $('#ugdList').html(ugdHtml || '<div class="empty-state"><i class="bi bi-water"></i><p>No UGD records</p></div>');

        // ── PROFESSIONAL TAX TAB (assessment-wise, multiple per assessment) ──
        let profHtml = '';
        assessments.forEach((a, i) => {
            const pd = a.pointData || {};
            const pts = a.professionalTaxes || [];

            if (!pts.length) {
                profHtml += `
                    <div class="data-card" style="opacity:0.7;">
                        <div class="record-chip"><i class="bi bi-briefcase"></i> Assessment: ${pd.assessment || 'N/A'}</div>
                        <p class="text-muted mb-0" style="font-size:0.85rem;">No professional tax records</p>
                    </div>`;
                return;
            }

            pts.forEach((p, j) => {
                profHtml += `
                    <div class="data-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="record-chip"><i class="bi bi-briefcase"></i> Assessment: ${pd.assessment || 'N/A'} — PT #${j + 1}</span>
                            <input type="hidden" name="assessments[${i}][professionalTaxes][${j}][id]" value="${p.id || ''}">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-label-sm">PT Number</div>
                                <input type="text" name="assessments[${i}][professionalTaxes][${j}][pt_number]" class="form-control form-control-sm-custom" value="${p.pt_number || ''}">
                            </div>
                            <div class="col-md-4">
                                <div class="form-label-sm">Old PT Number</div>
                                <input type="text" name="assessments[${i}][professionalTaxes][${j}][old_pt_number]" class="form-control form-control-sm-custom" value="${p.old_pt_number || ''}">
                            </div>
                            <div class="col-md-4">
                                <div class="form-label-sm">Establishment Name</div>
                                <input type="text" name="assessments[${i}][professionalTaxes][${j}][establishment_name]" class="form-control form-control-sm-custom" value="${p.establishment_name || ''}">
                            </div>
                            <div class="col-md-4">
                                <div class="form-label-sm">Profession Type</div>
                                <input type="text" name="assessments[${i}][professionalTaxes][${j}][profession_type]" class="form-control form-control-sm-custom" value="${p.profession_type || ''}">
                            </div>
                            <div class="col-md-4">
                                <div class="form-label-sm">Trade License</div>
                                <input type="text" name="assessments[${i}][professionalTaxes][${j}][trade_license]" class="form-control form-control-sm-custom" value="${p.trade_license || ''}">
                            </div>
                            <div class="col-md-4">
                                <div class="form-label-sm">Owner Name</div>
                                <input type="text" name="assessments[${i}][professionalTaxes][${j}][owner_name]" class="form-control form-control-sm-custom" value="${p.owner_name || ''}">
                            </div>
                            <div class="col-md-4">
                                <div class="form-label-sm">Phone</div>
                                <input type="text" name="assessments[${i}][professionalTaxes][${j}][phone_number]" class="form-control form-control-sm-custom" value="${p.phone_number || ''}">
                            </div>
                            <div class="col-md-4">
                                <div class="form-label-sm">Employee Count</div>
                                <input type="text" name="assessments[${i}][professionalTaxes][${j}][employee_count]" class="form-control form-control-sm-custom" value="${p.employee_count || ''}">
                            </div>
                            <div class="col-md-4">
                                <div class="form-label-sm">Half Year Tax</div>
                                <input type="text" name="assessments[${i}][professionalTaxes][${j}][half_year_tax]" class="form-control form-control-sm-custom" value="${p.half_year_tax || ''}">
                            </div>
                            <div class="col-12">
                                <div class="form-label-sm">Remarks</div>
                                <textarea name="assessments[${i}][professionalTaxes][${j}][remarks]" class="form-control form-control-sm-custom" rows="2">${p.remarks || ''}</textarea>
                            </div>
                        </div>
                    </div>`;
            });
        });
        $('#professionalList').html(profHtml || '<div class="empty-state"><i class="bi bi-briefcase"></i><p>No professional tax records</p></div>');
    }

    // ─── SAVE ALL ───
    $('#updateAllForm').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#saveAllBtn');
        const originalHtml = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...').prop('disabled', true);

        const formData = $(this).serialize();

        $.ajax({
            url: '{{ route("gisid.updateAll") }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: formData,
            success: function(res) {
                $btn.html(originalHtml).prop('disabled', false);

                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: res.message || 'All data updated successfully!',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Error', res.message || 'Update failed', 'error');
                }
            },
            error: function(xhr) {
                $btn.html(originalHtml).prop('disabled', false);
                const msg = xhr.responseJSON?.message || 'Update failed';
                Swal.fire('Error', msg, 'error');
            }
        });
    });
});
</script>
@endpush

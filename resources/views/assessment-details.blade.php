<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Details · QR & Professional Tax</title>
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- QRCode.js library for generating QR codes client-side -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 80px;
        }
        .assessment-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .header-card {
            background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
            color: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.3);
        }
        .header-card h1 {
            font-weight: 700;
            margin-bottom: 5px;
        }
        .header-card .subtitle {
            opacity: 0.9;
            font-size: 1rem;
        }
        .badge-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .badge-status.OLD {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-status.NEW {
            background: #dcfce7;
            color: #15803d;
        }
        .badge-status.VACANT {
            background: #e5e7eb;
            color: #4b5563;
        }
        .badge-status.OTHER_WARD {
            background: #dbeafe;
            color: #1e40af;
        }
        .detail-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            border: 1px solid #e5e7eb;
        }
        .detail-section .section-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: #1e293b;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .detail-section .section-title i {
            color: #2563eb;
        }
        .info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            width: 180px;
            font-weight: 600;
            color: #64748b;
            font-size: 0.85rem;
            flex-shrink: 0;
        }
        .info-value {
            flex: 1;
            color: #1e293b;
            font-weight: 500;
            word-break: break-word;
        }
        .info-value .empty {
            color: #94a3b8;
            font-style: italic;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .professional-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px;
            border: 1px solid #e5e7eb;
            margin-bottom: 12px;
            position: relative;
        }
        .professional-card .prof-number {
            font-weight: 700;
            color: #2563eb;
            margin-bottom: 10px;
        }
        .professional-card .prof-row {
            display: flex;
            padding: 4px 0;
            font-size: 0.9rem;
        }
        .professional-card .prof-label {
            width: 140px;
            color: #64748b;
            font-weight: 600;
        }
        .professional-card .prof-value {
            flex: 1;
            color: #1e293b;
        }
        .prof-qr-container {
            position: absolute;
            top: 16px;
            right: 16px;
            background: white;
            padding: 6px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .prof-qr-container canvas,
        .prof-qr-container img {
            max-width: 100%;
            max-height: 100%;
        }
        .qr-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            border: 2px dashed #2563eb;
        }
        .qr-section img {
            max-width: 200px;
            height: auto;
        }
        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
            .info-row {
                flex-direction: column;
                padding: 8px 0;
            }
            .info-label {
                width: 100%;
                margin-bottom: 4px;
            }
            .header-card {
                padding: 20px;
            }
            .prof-qr-container {
                position: static;
                margin-top: 10px;
                width: 80px;
                height: 80px;
            }
        }
        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            padding: 12px 24px;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        @media print {
            .print-btn {
                display: none !important;
            }
            .assessment-container {
                margin: 0 !important;
                padding: 0 !important;
            }
            .detail-section {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            .prof-qr-container {
                border: 1px solid #ccc !important;
                box-shadow: none !important;
            }
        }
        .qr-placeholder {
            width: 70px;
            height: 70px;
            background: #f1f5f9;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 0.7rem;
        }
    </style>
</head>
<body>

<div class="assessment-container" id="app">

    <!-- Header -->
    <div class="header-card">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
            <div>
                <h1>Assessment Details</h1>
                <p class="subtitle">
                    <i class="bi bi-building me-1"></i>
                    {{ $corporation->name ?? 'Corporation' }} |
                    Ward {{ $ward_no ?? 'N/A' }} |
                    Zone {{ $zone->name ?? 'N/A' }}
                </p>
            </div>
            <div>
                <span class="badge-status {{ strtoupper($point_data->assessment_type ?? '') }}">
                    {{ $point_data->assessment_type ?? 'N/A' }}
                </span>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-4">
                <small class="opacity-75">Assessment Number</small>
                <div class="fw-bold fs-5">{{ $point_data->assessment ?? 'N/A' }}</div>
            </div>
            <div class="col-md-4">
                <small class="opacity-75">GIS ID</small>
                <div class="fw-bold fs-5">{{ $point_data->point_gisid ?? 'N/A' }}</div>
            </div>
            <div class="col-md-4">
                <small class="opacity-75">Ward Number</small>
                <div class="fw-bold fs-5">{{ $ward_no ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Basic Information -->
        <div class="detail-section">
            <div class="section-title"><i class="bi bi-person-vcard"></i> Basic Information</div>
            <div>
                <div class="info-row">
                    <span class="info-label">Assessment Type</span>
                    <span class="info-value">{{ $point_data->assessment_type ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Old Assessment</span>
                    <span class="info-value">{{ $point_data->old_assessment ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Zone</span>
                    <span class="info-value">{{ $point_data->zone ?? $zone->name ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Owner Name</span>
                    <span class="info-value">{{ $point_data->owner_name ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Present Owner</span>
                    <span class="info-value">{{ $point_data->present_owner_name ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone</span>
                    <span class="info-value">{{ $point_data->phone_number ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Door Numbers</span>
                    <span class="info-value">Old: {{ $point_data->old_door_no ?? 'N/A' }} | New: {{ $point_data->new_door_no ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Aadhar</span>
                    <span class="info-value">{{ $point_data->aadhar_no ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Ration No</span>
                    <span class="info-value">{{ $point_data->ration_no ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Floor</span>
                    <span class="info-value">{{ $point_data->floor ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Bill Usage</span>
                    <span class="info-value">{{ $point_data->bill_usage ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Number of Persons</span>
                    <span class="info-value">{{ $point_data->no_of_persons ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">EB Number</span>
                    <span class="info-value">{{ $point_data->eb ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Worker</span>
                    <span class="info-value">{{ $point_data->worker_name ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Remarks</span>
                    <span class="info-value">{{ $point_data->remarks ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- Building Information -->
        <div class="detail-section">
            <div class="section-title"><i class="bi bi-building"></i> Building Information</div>
            <div>
                @if($building_data)
                    <div class="info-row">
                        <span class="info-label">Building Name</span>
                        <span class="info-value">{{ $building_data->building_name ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Road Name</span>
                        <span class="info-value">{{ $building_data->road_name ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Building Usage</span>
                        <span class="info-value">{{ $building_data->building_usage ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Construction Type</span>
                        <span class="info-value">{{ $building_data->construction_type ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Building Type</span>
                        <span class="info-value">{{ $building_data->building_type ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Number of Floors</span>
                        <span class="info-value">{{ $building_data->number_floor ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Number of Shops</span>
                        <span class="info-value">{{ $building_data->number_shop ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Number of Bills</span>
                        <span class="info-value">{{ $building_data->number_bill ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Basement</span>
                        <span class="info-value">{{ $building_data->basement ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Percentage</span>
                        <span class="info-value">{{ $building_data->percentage ?? 'N/A' }}%</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">UGD Status</span>
                        <span class="info-value">{{ $building_data->ugd ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Water Connection</span>
                        <span class="info-value">{{ $building_data->water_connection ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Lift Room</span>
                        <span class="info-value">{{ $building_data->liftroom ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Head Room</span>
                        <span class="info-value">{{ $building_data->headroom ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Overhead Tank</span>
                        <span class="info-value">{{ $building_data->overhead_tank ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Rainwater Harvesting</span>
                        <span class="info-value">{{ $building_data->rainwater_harvesting ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Parking</span>
                        <span class="info-value">{{ $building_data->parking ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Ramp</span>
                        <span class="info-value">{{ $building_data->ramp ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Hoarding</span>
                        <span class="info-value">{{ $building_data->hoarding ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">CCTV</span>
                        <span class="info-value">{{ $building_data->cctv ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Cell Tower</span>
                        <span class="info-value">{{ $building_data->cell_tower ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Solar Panel</span>
                        <span class="info-value">{{ $building_data->solar_panel ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Remarks</span>
                        <span class="info-value">{{ $building_data->remarks ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Corporation Remarks</span>
                        <span class="info-value">{{ $building_data->corporationremarks ?? 'N/A' }}</span>
                    </div>
                @else
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-building fs-1 d-block mb-2"></i>
                        No building data found
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Water Tax -->
        <div class="detail-section">
            <div class="section-title"><i class="bi bi-droplet"></i> Water Tax Details</div>
            <div>
                @if($water_tax)
                    <div class="info-row">
                        <span class="info-label">Water Tax No</span>
                        <span class="info-value">{{ $water_tax->watertax_no ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Old Water Tax No</span>
                        <span class="info-value">{{ $water_tax->old_watertax_no ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Usage</span>
                        <span class="info-value">{{ $water_tax->usage ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">DBC Type</span>
                        <span class="info-value">{{ $water_tax->DBC_type ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Slab Description</span>
                        <span class="info-value">{{ $water_tax->slab_description ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Remarks</span>
                        <span class="info-value">{{ $water_tax->remarks ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Created At</span>
                        <span class="info-value">{{ isset($water_tax->created_at) ? date('d-m-Y H:i:s', strtotime($water_tax->created_at)) : 'N/A' }}</span>
                    </div>
                @else
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-droplet fs-1 d-block mb-2"></i>
                        No water tax details found
                    </div>
                @endif
            </div>
        </div>

        <!-- UGD Tax -->
        <div class="detail-section">
            <div class="section-title"><i class="bi bi-pipe"></i> UGD Tax Details</div>
            <div>
                @if($ugd_tax)
                    <div class="info-row">
                        <span class="info-label">UGD No</span>
                        <span class="info-value">{{ $ugd_tax->ugd_no ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Old UGD No</span>
                        <span class="info-value">{{ $ugd_tax->old_ugd_no ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Usage</span>
                        <span class="info-value">{{ $ugd_tax->usage ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">DBC Type</span>
                        <span class="info-value">{{ $ugd_tax->DBC_type ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Slab Description</span>
                        <span class="info-value">{{ $ugd_tax->slab_description ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Remarks</span>
                        <span class="info-value">{{ $ugd_tax->remarks ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Created At</span>
                        <span class="info-value">{{ isset($ugd_tax->created_at) ? date('d-m-Y H:i:s', strtotime($ugd_tax->created_at)) : 'N/A' }}</span>
                    </div>
                @else
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-pipe fs-1 d-block mb-2"></i>
                        No UGD tax details found
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Professional Tax Details with QR per record -->
    <div class="detail-section" id="professionalTaxSection">
        <div class="section-title">
            <i class="bi bi-briefcase"></i> Professional Tax Details
            <span class="badge bg-primary ms-2">{{ count($professional_tax ?? []) }} records</span>
        </div>
        <div id="professionalTaxContainer">
            @if($professional_tax && count($professional_tax) > 0)
                @foreach($professional_tax as $index => $pt)
                    <div class="professional-card" style="position:relative;">
                        <div class="prof-number">Professional Tax #{{ $index + 1 }}</div>
                        <div class="prof-qr-container" id="profQr_{{ $index }}">
                            <!-- QR will be injected here by JavaScript -->
                        </div>
                        <div class="prof-row">
                            <span class="prof-label">PT Number</span>
                            <span class="prof-value">{{ $pt->pt_number ?? 'N/A' }}</span>
                        </div>
                        <div class="prof-row">
                            <span class="prof-label">Old PT Number</span>
                            <span class="prof-value">{{ $pt->old_pt_number ?? 'N/A' }}</span>
                        </div>
                        <div class="prof-row">
                            <span class="prof-label">Owner Name</span>
                            <span class="prof-value">{{ $pt->owner_name ?? 'N/A' }}</span>
                        </div>
                        <div class="prof-row">
                            <span class="prof-label">Establishment</span>
                            <span class="prof-value">{{ $pt->establishment_name ?? 'N/A' }}</span>
                        </div>
                        <div class="prof-row">
                            <span class="prof-label">Profession Type</span>
                            <span class="prof-value">{{ $pt->profession_type ?? 'N/A' }}</span>
                        </div>
                        <div class="prof-row">
                            <span class="prof-label">Trade License</span>
                            <span class="prof-value">{{ $pt->trade_license ?? 'N/A' }}</span>
                        </div>
                        <div class="prof-row">
                            <span class="prof-label">Employee Count</span>
                            <span class="prof-value">{{ $pt->employee_count ?? 'N/A' }}</span>
                        </div>
                        <div class="prof-row">
                            <span class="prof-label">Half Year Tax</span>
                            <span class="prof-value">₹{{ number_format($pt->half_year_tax ?? 0, 2) }}</span>
                        </div>
                        <div class="prof-row">
                            <span class="prof-label">Remarks</span>
                            <span class="prof-value">{{ $pt->remarks ?? 'N/A' }}</span>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center text-muted py-4">
                    <i class="bi bi-briefcase fs-1 d-block mb-2"></i>
                    No professional tax details found
                </div>
            @endif
        </div>
    </div>

    <!-- Main QR Code Section (Assessment level) -->
    <div class="detail-section">
        <div class="section-title"><i class="bi bi-qr-code"></i> Assessment QR Code</div>
        <div class="row align-items-center">
            <div class="col-md-6 text-center" id="mainQrContainer">
                <div id="mainQrCode" style="display:inline-block;"></div>
            </div>
            <div class="col-md-6">
                <p class="mb-1"><strong>Ward No:</strong> <span id="qrWard">{{ $ward_no ?? 'N/A' }}</span></p>
                <p class="mb-1"><strong>Assessment ID:</strong> <span id="qrAssessmentId">{{ $point_data->id ?? 'N/A' }}</span></p>
                <p class="mb-1"><strong>Assessment Number:</strong> <span id="qrAssessmentNumber">{{ $point_data->assessment ?? 'N/A' }}</span></p>
                <p class="mb-1"><strong>GIS ID:</strong> <span id="qrGisId">{{ $point_data->point_gisid ?? 'N/A' }}</span></p>
            </div>
        </div>
    </div>
</div>

<!-- Print Button -->
<button class="btn btn-primary print-btn" onclick="window.print()">
    <i class="bi bi-printer me-2"></i> Print
</button>

<script>
    (function() {
        // Get data from Laravel backend
        const wardNo = "{{ $ward_no ?? 'N/A' }}";
        const assessmentId = "{{ $point_data->id ?? 'N/A' }}";
        const assessmentNumber = "{{ $point_data->assessment ?? 'N/A' }}";
        const gisId = "{{ $point_data->point_gisid ?? 'N/A' }}";
        const professionalTaxRecords = @json($professional_tax ?? []);

        // ----- Main Assessment QR Code -----
        const mainQrContainer = document.getElementById('mainQrCode');
        const mainQrData = JSON.stringify({
            ward_no: wardNo,
            assessment_id: assessmentId,
            assessment_number: assessmentNumber,
            gis_id: gisId
        });

        if (mainQrContainer) {
            new QRCode(mainQrContainer, {
                text: mainQrData,
                width: 200,
                height: 200,
                colorDark: "#1e3a5f",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        }

        // ----- Generate QR codes for each professional tax record -----
        if (professionalTaxRecords && professionalTaxRecords.length > 0) {
            professionalTaxRecords.forEach(function(pt, index) {
                const qrContainerId = 'profQr_' + index;
                const container = document.getElementById(qrContainerId);
                if (container) {
                    // Clear placeholder
                    container.innerHTML = '';
                    const qrData = JSON.stringify({
                        ward: wardNo,
                        assessment_id: assessmentId,
                        assessment_number: assessmentNumber,
                        pt_number: pt.pt_number || 'N/A',
                        establishment: pt.establishment_name || 'N/A'
                    });
                    // Generate QR using QRCode.js
                    new QRCode(container, {
                        text: qrData,
                        width: 60,
                        height: 60,
                        colorDark: "#1e293b",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                }
            });
        }

        // Update QR info section with actual data
        document.getElementById('qrWard').textContent = wardNo;
        document.getElementById('qrAssessmentId').textContent = assessmentId;
        document.getElementById('qrAssessmentNumber').textContent = assessmentNumber;
        document.getElementById('qrGisId').textContent = gisId;

    })();
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

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
        .badge-status.old {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-status.new {
            background: #dcfce7;
            color: #15803d;
        }
        .badge-status.vacant {
            background: #e5e7eb;
            color: #4b5563;
        }
        .badge-status.other_ward {
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
        /* QR code container inside professional card */
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
        /* small helper */
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
                    <!-- dynamic data simulation -->
                    <span id="corporationName">Corporation of Coimbatore</span> |
                    Ward <span id="wardNoDisplay">12</span> |
                    Zone <span id="zoneDisplay">East</span>
                </p>
            </div>
            <div>
                <span class="badge-status new" id="assessmentTypeBadge">New</span>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-4">
                <small class="opacity-75">Assessment Number</small>
                <div class="fw-bold fs-5" id="assessmentNumberDisplay">A-2026-001</div>
            </div>
            <div class="col-md-4">
                <small class="opacity-75">GIS ID</small>
                <div class="fw-bold fs-5" id="gisIdDisplay">GIS-1234</div>
            </div>
            <div class="col-md-4">
                <small class="opacity-75">Ward Number</small>
                <div class="fw-bold fs-5" id="wardNumberDisplay">12</div>
            </div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Basic Information -->
        <div class="detail-section">
            <div class="section-title"><i class="bi bi-person-vcard"></i> Basic Information</div>
            <div id="basicInfoContainer">
                <!-- will be filled by JS -->
            </div>
        </div>

        <!-- Building Information -->
        <div class="detail-section">
            <div class="section-title"><i class="bi bi-building"></i> Building Information</div>
            <div id="buildingInfoContainer">
                <!-- filled by JS -->
            </div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Water Tax -->
        <div class="detail-section">
            <div class="section-title"><i class="bi bi-droplet"></i> Water Tax Details</div>
            <div id="waterTaxContainer"></div>
        </div>
        <!-- UGD Tax -->
        <div class="detail-section">
            <div class="section-title"><i class="bi bi-pipe"></i> UGD Tax Details</div>
            <div id="ugdTaxContainer"></div>
        </div>
    </div>

    <!-- Professional Tax Details with QR per record -->
    <div class="detail-section" id="professionalTaxSection">
        <div class="section-title">
            <i class="bi bi-briefcase"></i> Professional Tax Details
            <span class="badge bg-primary ms-2" id="profTaxCount">3 records</span>
        </div>
        <div id="professionalTaxContainer">
            <!-- cards will be injected by JS, each with QR -->
        </div>
    </div>

    <!-- Main QR Code Section (Assessment level) -->
    <div class="detail-section">
        <div class="section-title"><i class="bi bi-qr-code"></i> Assessment QR Code</div>
        <div class="row align-items-center">
            <div class="col-md-6 text-center" id="mainQrContainer">
                <!-- QR will be rendered here -->
                <div id="mainQrCode" style="display:inline-block;"></div>
            </div>
            <div class="col-md-6">
                <p class="mb-1"><strong>Ward No:</strong> <span id="qrWard">12</span></p>
                <p class="mb-1"><strong>Assessment ID:</strong> <span id="qrAssessmentId">101</span></p>
                <p class="mb-1"><strong>Assessment Number:</strong> <span id="qrAssessmentNumber">A-2026-001</span></p>
                <p class="mb-1"><strong>GIS ID:</strong> <span id="qrGisId">GIS-1234</span></p>
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
        // ----- MOCK DATA (simulates Laravel blade variables) -----
        const pointData = {
            id: 101,
            assessment_type: 'New',
            old_assessment: 'N/A',
            zone: 'East',
            owner_name: 'R. Kumar',
            present_owner_name: 'S. Kumar',
            phone_number: '9876543210',
            old_door_no: '12/A',
            new_door_no: '12/B',
            aadhar_no: '1234-5678-9012',
            ration_no: 'TN123456',
            floor: 'Ground',
            bill_usage: 'Residential',
            no_of_persons: '4',
            eb: 'EB-202',
            worker_name: 'M. Selvam',
            remarks: 'Verified',
            point_gisid: 'GIS-1234'
        };

        const buildingData = {
            building_name: 'Surya Apartments',
            road_name: 'Main Road',
            building_usage: 'Residential',
            construction_type: 'RCC',
            building_type: 'Multi-storey',
            number_floor: '3',
            number_shop: '2',
            number_bill: '5',
            basement: 'No',
            percentage: '85',
            ugd: 'Connected',
            water_connection: 'Yes'
        };

        const waterTax = {
            watertax_no: 'WT-101',
            old_watertax_no: 'OWT-45',
            usage: 'Domestic',
            DBC_type: 'A',
            slab_description: 'Slab 2',
            remarks: 'Paid up to 2025',
            created_at: '2025-01-15 10:30:00'
        };

        const ugdTax = {
            ugd_no: 'UGD-202',
            old_ugd_no: 'OUGD-12',
            usage: 'Domestic',
            DBC_type: 'B',
            slab_description: 'Slab 1',
            remarks: 'Active',
            created_at: '2025-02-20 14:15:00'
        };

        // Professional tax records (each will have its own QR)
        const professionalTaxRecords = [
            {
                pt_number: 'PT-101',
                old_pt_number: 'OPT-01',
                establishment_name: 'Kumar Traders',
                profession_type: 'Wholesale',
                employee_count: '5',
                half_year_tax: '2500.00',
                remarks: 'Active'
            },
            {
                pt_number: 'PT-102',
                old_pt_number: 'OPT-02',
                establishment_name: 'Surya Medicals',
                profession_type: 'Retail',
                employee_count: '3',
                half_year_tax: '1500.00',
                remarks: 'Pending'
            },
            {
                pt_number: 'PT-103',
                old_pt_number: 'OPT-03',
                establishment_name: 'Ganesh Hardware',
                profession_type: 'Hardware',
                employee_count: '8',
                half_year_tax: '4200.00',
                remarks: 'Active'
            }
        ];

        const wardNo = 12;
        const assessmentNumber = 'A-2026-001';
        const zoneName = 'East';
        const corporationName = 'Corporation of Coimbatore';

        // ----- Populate header & identifiers -----
        document.getElementById('corporationName').textContent = corporationName;
        document.getElementById('wardNoDisplay').textContent = wardNo;
        document.getElementById('zoneDisplay').textContent = zoneName;
        document.getElementById('assessmentNumberDisplay').textContent = assessmentNumber;
        document.getElementById('gisIdDisplay').textContent = pointData.point_gisid;
        document.getElementById('wardNumberDisplay').textContent = wardNo;
        document.getElementById('qrWard').textContent = wardNo;
        document.getElementById('qrAssessmentId').textContent = pointData.id;
        document.getElementById('qrAssessmentNumber').textContent = assessmentNumber;
        document.getElementById('qrGisId').textContent = pointData.point_gisid;

        // set badge
        const badge = document.getElementById('assessmentTypeBadge');
        badge.textContent = pointData.assessment_type;
        badge.className = 'badge-status ' + (pointData.assessment_type === 'New' ? 'new' : 'old');

        // ----- Render Basic Info -----
        const basicContainer = document.getElementById('basicInfoContainer');
        const basicFields = [
            { label: 'Assessment Type', value: pointData.assessment_type },
            { label: 'Old Assessment', value: pointData.old_assessment },
            { label: 'Zone', value: pointData.zone },
            { label: 'Owner Name', value: pointData.owner_name },
            { label: 'Present Owner', value: pointData.present_owner_name },
            { label: 'Phone', value: pointData.phone_number },
            { label: 'Door Numbers', value: `Old: ${pointData.old_door_no} | New: ${pointData.new_door_no}` },
            { label: 'Aadhar', value: pointData.aadhar_no },
            { label: 'Ration No', value: pointData.ration_no },
            { label: 'Floor', value: pointData.floor },
            { label: 'Bill Usage', value: pointData.bill_usage },
            { label: 'Number of Persons', value: pointData.no_of_persons },
            { label: 'EB Number', value: pointData.eb },
            { label: 'Worker', value: pointData.worker_name },
            { label: 'Remarks', value: pointData.remarks }
        ];
        basicContainer.innerHTML = basicFields.map(f => `
            <div class="info-row">
                <span class="info-label">${f.label}</span>
                <span class="info-value">${f.value || 'N/A'}</span>
            </div>
        `).join('');

        // ----- Render Building Info -----
        const buildingContainer = document.getElementById('buildingInfoContainer');
        if (buildingData) {
            const bFields = [
                { label: 'Building Name', value: buildingData.building_name },
                { label: 'Road Name', value: buildingData.road_name },
                { label: 'Building Usage', value: buildingData.building_usage },
                { label: 'Construction Type', value: buildingData.construction_type },
                { label: 'Building Type', value: buildingData.building_type },
                { label: 'Number of Floors', value: buildingData.number_floor },
                { label: 'Number of Shops', value: buildingData.number_shop },
                { label: 'Number of Bills', value: buildingData.number_bill },
                { label: 'Basement', value: buildingData.basement },
                { label: 'Percentage', value: buildingData.percentage + '%' },
                { label: 'UGD Status', value: buildingData.ugd },
                { label: 'Water Connection', value: buildingData.water_connection }
            ];
            buildingContainer.innerHTML = bFields.map(f => `
                <div class="info-row">
                    <span class="info-label">${f.label}</span>
                    <span class="info-value">${f.value || 'N/A'}</span>
                </div>
            `).join('');
        } else {
            buildingContainer.innerHTML = `<div class="text-center text-muted py-4"><i class="bi bi-building fs-1 d-block mb-2"></i>No building data found</div>`;
        }

        // ----- Render Water Tax -----
        const waterContainer = document.getElementById('waterTaxContainer');
        if (waterTax) {
            const wFields = [
                { label: 'Water Tax No', value: waterTax.watertax_no },
                { label: 'Old Water Tax No', value: waterTax.old_watertax_no },
                { label: 'Usage', value: waterTax.usage },
                { label: 'DBC Type', value: waterTax.DBC_type },
                { label: 'Slab Description', value: waterTax.slab_description },
                { label: 'Remarks', value: waterTax.remarks },
                { label: 'Created At', value: waterTax.created_at ? new Date(waterTax.created_at).toLocaleString() : 'N/A' }
            ];
            waterContainer.innerHTML = wFields.map(f => `
                <div class="info-row">
                    <span class="info-label">${f.label}</span>
                    <span class="info-value">${f.value || 'N/A'}</span>
                </div>
            `).join('');
        } else {
            waterContainer.innerHTML = `<div class="text-center text-muted py-4"><i class="bi bi-droplet fs-1 d-block mb-2"></i>No water tax details found</div>`;
        }

        // ----- Render UGD Tax -----
        const ugdContainer = document.getElementById('ugdTaxContainer');
        if (ugdTax) {
            const uFields = [
                { label: 'UGD No', value: ugdTax.ugd_no },
                { label: 'Old UGD No', value: ugdTax.old_ugd_no },
                { label: 'Usage', value: ugdTax.usage },
                { label: 'DBC Type', value: ugdTax.DBC_type },
                { label: 'Slab Description', value: ugdTax.slab_description },
                { label: 'Remarks', value: ugdTax.remarks },
                { label: 'Created At', value: ugdTax.created_at ? new Date(ugdTax.created_at).toLocaleString() : 'N/A' }
            ];
            ugdContainer.innerHTML = uFields.map(f => `
                <div class="info-row">
                    <span class="info-label">${f.label}</span>
                    <span class="info-value">${f.value || 'N/A'}</span>
                </div>
            `).join('');
        } else {
            ugdContainer.innerHTML = `<div class="text-center text-muted py-4"><i class="bi bi-pipe fs-1 d-block mb-2"></i>No UGD tax details found</div>`;
        }

        // ----- Render Professional Tax with QR per record -----
        const profContainer = document.getElementById('professionalTaxContainer');
        const profCount = document.getElementById('profTaxCount');
        profCount.textContent = professionalTaxRecords.length + ' records';

        if (professionalTaxRecords.length > 0) {
            let html = '';
            professionalTaxRecords.forEach((pt, index) => {
                // build unique id for QR container
                const qrId = `profQr_${index}`;
                // data to encode in QR: assessment + pt info
                const qrData = JSON.stringify({
                    ward: wardNo,
                    assessment_id: pointData.id,
                    assessment_number: assessmentNumber,
                    pt_number: pt.pt_number,
                    establishment: pt.establishment_name
                });

                html += `
                    <div class="professional-card" style="position:relative;">
                        <div class="prof-number">Professional Tax #${index+1}</div>
                        <div class="prof-qr-container" id="${qrId}">
                            <!-- QR will be injected here -->
                        </div>
                        <div class="prof-row"><span class="prof-label">PT Number</span><span class="prof-value">${pt.pt_number || 'N/A'}</span></div>
                        <div class="prof-row"><span class="prof-label">Old PT Number</span><span class="prof-value">${pt.old_pt_number || 'N/A'}</span></div>
                        <div class="prof-row"><span class="prof-label">Establishment</span><span class="prof-value">${pt.establishment_name || 'N/A'}</span></div>
                        <div class="prof-row"><span class="prof-label">Profession Type</span><span class="prof-value">${pt.profession_type || 'N/A'}</span></div>
                        <div class="prof-row"><span class="prof-label">Employee Count</span><span class="prof-value">${pt.employee_count || 'N/A'}</span></div>
                        <div class="prof-row"><span class="prof-label">Half Year Tax</span><span class="prof-value">₹${parseFloat(pt.half_year_tax || 0).toFixed(2)}</span></div>
                        <div class="prof-row"><span class="prof-label">Remarks</span><span class="prof-value">${pt.remarks || 'N/A'}</span></div>
                    </div>
                `;
            });
            profContainer.innerHTML = html;

            // after DOM update, generate QR codes for each professional card
            professionalTaxRecords.forEach((pt, index) => {
                const qrContainerId = `profQr_${index}`;
                const container = document.getElementById(qrContainerId);
                if (container) {
                    // clear placeholder
                    container.innerHTML = '';
                    const qrData = JSON.stringify({
                        ward: wardNo,
                        assessment_id: pointData.id,
                        assessment_number: assessmentNumber,
                        pt_number: pt.pt_number,
                        establishment: pt.establishment_name
                    });
                    // generate QR using QRCode.js
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
        } else {
            profContainer.innerHTML = `<div class="text-center text-muted py-4"><i class="bi bi-briefcase fs-1 d-block mb-2"></i>No professional tax details found</div>`;
        }

        // ----- Main Assessment QR Code -----
        const mainQrContainer = document.getElementById('mainQrCode');
        const mainQrData = JSON.stringify({
            ward_no: wardNo,
            assessment_id: pointData.id,
            assessment_number: assessmentNumber,
            gis_id: pointData.point_gisid
        });
        new QRCode(mainQrContainer, {
            text: mainQrData,
            width: 200,
            height: 200,
            colorDark: "#1e3a5f",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

    })();
</script>

<!-- Bootstrap JS (optional for toggles etc) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
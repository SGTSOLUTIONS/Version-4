<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>All Assessments PDF</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #000;
            padding: 15px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h1 {
            font-size: 16px;
            margin: 0;
        }
        .header .subtitle {
            font-size: 11px;
            color: #666;
        }
        .section-title {
            background: #4472C4;
            color: #fff;
            padding: 4px 10px;
            font-weight: bold;
            font-size: 10px;
            margin-top: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        td, th {
            border: 1px solid #ccc;
            padding: 3px 5px;
            font-size: 8px;
            text-align: left;
        }
        th {
            background: #f0f0f0;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 7px;
            font-weight: bold;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        .badge-secondary {
            background: #e2e3e5;
            color: #383d41;
        }
        .footer {
            margin-top: 15px;
            border-top: 1px solid #ccc;
            padding-top: 8px;
            font-size: 7px;
            color: #666;
            text-align: center;
        }
        .signature-block {
            margin-top: 20px;
            border-top: 2px solid #333;
            padding-top: 12px;
        }
        .sig-row {
            display: table;
            width: 100%;
        }
        .sig-col {
            display: table-cell;
            text-align: center;
            padding: 0 5px;
            width: 25%;
        }
        .sig-col .sig-line {
            border-bottom: 1px solid #000;
            height: 25px;
            margin-bottom: 3px;
        }
        .sig-col .sig-label {
            font-size: 7px;
            font-weight: bold;
        }
        .sig-col .sig-sub {
            font-size: 6px;
            color: #666;
        }
        .summary-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 8px 12px;
            margin: 10px 0;
        }
        .summary-box .stat {
            display: inline-block;
            margin-right: 20px;
        }
        .summary-box .stat .num {
            font-weight: bold;
            font-size: 12px;
        }
        .summary-box .stat .lbl {
            font-size: 8px;
            color: #666;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>ALL ASSESSMENTS REPORT</h1>
        <div class="subtitle">
            Ward: {{ $ward->ward_no ?? 'N/A' }} |
            Zone: {{ $zone->zone_name ?? 'N/A' }} |
            GIS ID: {{ $gisid }}
        </div>
        <div class="subtitle">Generated: {{ $date }}</div>
    </div>

    <!-- BUILDING SUMMARY -->
    <div class="summary-box">
        <div class="stat">
            <span class="num">{{ number_format($buildingData['building']['area'] ?? 0, 2) }}</span>
            <span class="lbl">Building Area (sqft)</span>
        </div>
        <div class="stat">
            <span class="num">{{ $buildingData['building']['usage'] ?? 'N/A' }}</span>
            <span class="lbl">Building Usage</span>
        </div>
        <div class="stat">
            <span class="num">{{ $buildingData['assessment']['count'] ?? 0 }}</span>
            <span class="lbl">Total Assessments</span>
        </div>
        <div class="stat">
            <span class="num">{{ number_format($buildingData['assessment']['area'] ?? 0, 2) }}</span>
            <span class="lbl">Total Assessment Area (sqft)</span>
        </div>
        <div class="stat">
            <span class="num">{{ $buildingData['area_comparison']['variation_percentage'] ?? 0 }}%</span>
            <span class="lbl">Area Variation</span>
        </div>
    </div>

    <!-- ASSESSMENTS TABLE -->
    <div class="section-title">ASSESSMENT DETAILS</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Assessment No</th>
                <th>Assessment Type</th>
                <th>Area (sqft)</th>
                <th>QC Usage</th>
                <th>Bill Usage</th>
                <th>Owner Name</th>
                <th>Phone</th>
                <th>Door No</th>
                <th>Street</th>
                <th>MIS Assessment</th>
                <th>MIS Area</th>
                <th>MIS Tax</th>
            </tr>
        </thead>
        <tbody>
            @php $points = $buildingData['assessment']['details']['points'] ?? []; @endphp
            @forelse($points as $idx => $point)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td><strong>{{ $point['assessment'] ?? 'N/A' }}</strong></td>
                    <td>
                        <span class="badge badge-{{ strtolower($point['assessment_type'] ?? 'secondary') === 'old' ? 'warning' : (strtolower($point['assessment_type'] ?? '') === 'new' ? 'success' : 'info') }}">
                            {{ $point['assessment_type'] ?? 'N/A' }}
                        </span>
                    </td>
                    <td>{{ number_format($point['point_area'] ?? 0, 2) }}</td>
                    <td>{{ $point['qcusage'] ?? 'N/A' }}</td>
                    <td>{{ $point['bill_usage'] ?? 'N/A' }}</td>
                    <td>{{ $point['owner_name'] ?? 'N/A' }}</td>
                    <td>{{ $point['phone_number'] ?? 'N/A' }}</td>
                    <td>{{ $point['door_no'] ?? 'N/A' }}</td>
                    <td>{{ $point['street_name'] ?? 'N/A' }}</td>
                    @php $mis = $point['mis_data'] ?? []; @endphp
                    <td>{{ $mis['assessment'] ?? 'N/A' }}</td>
                    <td>{{ $mis['plot_area'] ?? 'N/A' }}</td>
                    <td>{{ $mis['half_year_tax'] ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" style="text-align: center;">No assessments found for this building</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- COMPARISON SUMMARY -->
    <div style="margin-top: 15px;">
        <div class="section-title" style="background: #7030A0;">COMPARISON SUMMARY</div>
        <table>
            <tr>
                <td style="width: 50%;"><strong>Total Building Area</strong></td>
                <td>{{ number_format($buildingData['area_comparison']['building_area'] ?? 0, 2) }} sqft</td>
            </tr>
            <tr>
                <td><strong>Total Assessment Area</strong></td>
                <td>{{ number_format($buildingData['area_comparison']['assessment_area'] ?? 0, 2) }} sqft</td>
            </tr>
            <tr>
                <td><strong>Area Variation</strong></td>
                <td class="{{ ($buildingData['area_comparison']['area_variation'] ?? 0) > 0 ? 'status-variation' : 'status-match' }}">
                    {{ ($buildingData['area_comparison']['area_variation'] ?? 0) > 0 ? '+' : '' }}{{ number_format($buildingData['area_comparison']['area_variation'] ?? 0, 2) }} sqft
                    ({{ $buildingData['area_comparison']['variation_percentage'] ?? 0 }}%)
                </td>
            </tr>
            <tr>
                <td><strong>Area Status</strong></td>
                <td>
                    <span class="badge badge-{{ ($buildingData['area_comparison']['area_status'] ?? 'MATCH') === 'VARIATION' ? 'danger' : 'success' }}">
                        {{ $buildingData['area_comparison']['area_status'] ?? 'N/A' }}
                    </span>
                </td>
            </tr>
            <tr>
                <td><strong>Usage Status</strong></td>
                <td>
                    <span class="badge badge-{{ $buildingData['usage_comparison']['usage_badge_class'] ?? 'secondary' }}">
                        {{ $buildingData['usage_comparison']['usage_status_label'] ?? 'N/A' }}
                    </span>
                </td>
            </tr>
            <tr>
                <td><strong>Building Usage</strong></td>
                <td>{{ $buildingData['usage_comparison']['building_usage'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Assessment Usage(s)</strong></td>
                <td>{{ implode(', ', $buildingData['usage_comparison']['all_assessment_usages'] ?? []) ?: 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <!-- SIGNATURE BLOCK -->
    <div class="signature-block">
        <div class="sig-row">
            <div class="sig-col">
                <div class="sig-line"></div>
                <div class="sig-label">Assessor</div>
                <div class="sig-sub">Signature with Date</div>
            </div>
            <div class="sig-col">
                <div class="sig-line"></div>
                <div class="sig-label">Assistant Revenue Officer</div>
                <div class="sig-sub">Signature with Date</div>
            </div>
            <div class="sig-col">
                <div class="sig-line"></div>
                <div class="sig-label">Zonal Officer</div>
                <div class="sig-sub">Signature with Date</div>
            </div>
            <div class="sig-col">
                <div class="sig-line"></div>
                <div class="sig-label">City Revenue Officer</div>
                <div class="sig-sub">Signature with Date</div>
            </div>
        </div>
    </div>

    <!-- SYSTEM REFERENCE -->
    <div class="footer">
        <strong>System Reference:</strong>
        GIS ID: {{ $gisid }} |
        Total Assessments: {{ count($points) }} |
        Area Variation: {{ number_format($buildingData['area_comparison']['area_variation'] ?? 0, 2) }} sqft |
        Usage Status: {{ $buildingData['usage_comparison']['usage_status_label'] ?? 'N/A' }}
    </div>

</body>
</html>

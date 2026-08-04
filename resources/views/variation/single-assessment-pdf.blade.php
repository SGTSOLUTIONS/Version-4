<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Assessment Details PDF</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #000;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0;
        }
        .header .subtitle {
            font-size: 12px;
            color: #666;
        }
        .section {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .section-title {
            background: #4472C4;
            color: #fff;
            padding: 5px 10px;
            font-weight: bold;
            font-size: 11px;
        }
        .section-title.green {
            background: #70AD47;
        }
        .section-title.orange {
            background: #ED7D31;
        }
        .section-title.purple {
            background: #7030A0;
        }
        .section-title.red {
            background: #C00000;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        td, th {
            border: 1px solid #ccc;
            padding: 4px 6px;
            font-size: 9px;
        }
        th {
            background: #f0f0f0;
            font-weight: bold;
        }
        .label {
            font-weight: bold;
            width: 40%;
        }
        .value {
            width: 60%;
        }
        .status-match {
            color: #00B050;
            font-weight: bold;
        }
        .status-variation {
            color: #FF0000;
            font-weight: bold;
        }
        .status-warning {
            color: #FF8C00;
            font-weight: bold;
        }
        .footer {
            margin-top: 20px;
            border-top: 1px solid #ccc;
            padding-top: 10px;
            font-size: 8px;
            color: #666;
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 1px 8px;
            border-radius: 12px;
            font-size: 8px;
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
        .row {
            display: table;
            width: 100%;
        }
        .col-6 {
            display: table-cell;
            width: 50%;
            padding: 0 5px;
        }
        .mis-data-table td {
            font-size: 8px;
            padding: 2px 4px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>ASSESSMENT DETAILS REPORT</h1>
        <div class="subtitle">
            Ward: {{ $ward->ward_no ?? 'N/A' }} |
            Zone: {{ $zone->zone_name ?? 'N/A' }} |
            GIS ID: {{ $gisid }} |
            Assessment: {{ $assessmentNo }}
        </div>
        <div class="subtitle">Generated: {{ $date }}</div>
    </div>

    <!-- BUILDING DETAILS -->
    <div class="section">
        <div class="section-title">BUILDING DETAILS</div>
        <table>
            <tr>
                <td class="label">GIS ID</td>
                <td class="value"><strong>{{ $gisid }}</strong></td>
            </tr>
            <tr>
                <td class="label">Building Area (sqft)</td>
                <td class="value">{{ number_format($buildingData['building']['area'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Building Usage</td>
                <td class="value">{{ $buildingData['building']['usage'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Number of Floors</td>
                <td class="value">{{ $buildingData['building']['details']['number_floor'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Basement</td>
                <td class="value">{{ $buildingData['building']['details']['basement'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Polygon Sqfeet</td>
                <td class="value">{{ number_format($buildingData['building']['details']['sqfeet'] ?? 0, 2) }}</td>
            </tr>
        </table>
    </div>

    <!-- ASSESSMENT DETAILS -->
    <div class="section">
        <div class="section-title green">ASSESSMENT DETAILS</div>
        <table>
            <tr>
                <td class="label">Assessment No</td>
                <td class="value"><strong>{{ $assessmentData['assessment'] ?? 'N/A' }}</strong></td>
            </tr>
            <tr>
                <td class="label">Assessment Type</td>
                <td class="value">
                    <span class="badge badge-{{ strtolower($assessmentData['assessment_type'] ?? 'secondary') === 'old' ? 'warning' : (strtolower($assessmentData['assessment_type'] ?? '') === 'new' ? 'success' : 'info') }}">
                        {{ $assessmentData['assessment_type'] ?? 'N/A' }}
                    </span>
                </td>
            </tr>
            <tr>
                <td class="label">Point Area (sqft)</td>
                <td class="value">{{ number_format($assessmentData['point_area'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="label">QC Usage</td>
                <td class="value">{{ $assessmentData['qcusage'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Bill Usage</td>
                <td class="value">{{ $assessmentData['bill_usage'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Owner Name</td>
                <td class="value">{{ $assessmentData['owner_name'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Phone Number</td>
                <td class="value">{{ $assessmentData['phone_number'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Door No</td>
                <td class="value">{{ $assessmentData['door_no'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Street Name</td>
                <td class="value">{{ $assessmentData['street_name'] ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <!-- MIS DATA -->
    @if (!empty($assessmentData['mis_data']))
    <div class="section">
        <div class="section-title orange">MIS DATA</div>
        <table class="mis-data-table">
            <thead>
                <tr>
                    <th>Assessment</th>
                    <th>Owner Name</th>
                    <th>Plot Area</th>
                    <th>Half Year Tax</th>
                    <th>Balance</th>
                    <th>Door No</th>
                    <th>Ward No</th>
                    <th>Road Name</th>
                    <th>Type</th>
                </tr>
            </thead>
            <tbody>
                @php $mis = $assessmentData['mis_data']; @endphp
                <tr>
                    <td>{{ $mis['assessment'] ?? 'N/A' }}</td>
                    <td>{{ $mis['owner_name'] ?? 'N/A' }}</td>
                    <td>{{ $mis['plot_area'] ?? 'N/A' }}</td>
                    <td>{{ $mis['half_year_tax'] ?? 'N/A' }}</td>
                    <td>{{ $mis['balance'] ?? 'N/A' }}</td>
                    <td>{{ $mis['new_door_no'] ?? $mis['old_door_no'] ?? 'N/A' }}</td>
                    <td>{{ $mis['ward_no'] ?? 'N/A' }}</td>
                    <td>{{ $mis['road_name'] ?? 'N/A' }}</td>
                    <td>{{ $mis['type'] ?? 'N/A' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <!-- COMPARISON DATA -->
    <div class="section">
        <div class="section-title purple">COMPARISON DATA</div>
        <table>
            <tr>
                <td class="label">Total Building Area</td>
                <td class="value">{{ number_format($buildingData['area_comparison']['building_area'] ?? 0, 2) }} sqft</td>
            </tr>
            <tr>
                <td class="label">Total Assessment Area</td>
                <td class="value">{{ number_format($buildingData['area_comparison']['assessment_area'] ?? 0, 2) }} sqft</td>
            </tr>
            <tr>
                <td class="label">Area Variation</td>
                <td class="value {{ ($buildingData['area_comparison']['area_variation'] ?? 0) > 0 ? 'status-variation' : 'status-match' }}">
                    {{ ($buildingData['area_comparison']['area_variation'] ?? 0) > 0 ? '+' : '' }}{{ number_format($buildingData['area_comparison']['area_variation'] ?? 0, 2) }} sqft
                </td>
            </tr>
            <tr>
                <td class="label">Variation Percentage</td>
                <td class="value">{{ $buildingData['area_comparison']['variation_percentage'] ?? 0 }}%</td>
            </tr>
            <tr>
                <td class="label">Area Status</td>
                <td class="value">
                    <span class="badge badge-{{ ($buildingData['area_comparison']['area_status'] ?? 'MATCH') === 'VARIATION' ? 'danger' : 'success' }}">
                        {{ $buildingData['area_comparison']['area_status'] ?? 'N/A' }}
                    </span>
                </td>
            </tr>
            <tr>
                <td class="label">Usage Status</td>
                <td class="value">
                    <span class="badge badge-{{ $buildingData['usage_comparison']['usage_badge_class'] ?? 'secondary' }}">
                        {{ $buildingData['usage_comparison']['usage_status_label'] ?? 'N/A' }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <!-- SIGNATURE BLOCK -->
    <div style="margin-top: 30px; border-top: 2px solid #333; padding-top: 15px;">
        <table style="border: none; width: 100%;">
            <tr style="border: none;">
                <td style="border: none; width: 25%; text-align: center; vertical-align: top;">
                    <div style="border-bottom: 1px solid #000; height: 30px; margin-bottom: 3px;"></div>
                    <div style="font-size: 9px; font-weight: bold;">Assessor</div>
                    <div style="font-size: 7px; color: #666;">Signature with Date</div>
                </td>
                <td style="border: none; width: 25%; text-align: center; vertical-align: top;">
                    <div style="border-bottom: 1px solid #000; height: 30px; margin-bottom: 3px;"></div>
                    <div style="font-size: 9px; font-weight: bold;">Assistant Revenue Officer</div>
                    <div style="font-size: 7px; color: #666;">Signature with Date</div>
                </td>
                <td style="border: none; width: 25%; text-align: center; vertical-align: top;">
                    <div style="border-bottom: 1px solid #000; height: 30px; margin-bottom: 3px;"></div>
                    <div style="font-size: 9px; font-weight: bold;">Zonal Officer</div>
                    <div style="font-size: 7px; color: #666;">Signature with Date</div>
                </td>
                <td style="border: none; width: 25%; text-align: center; vertical-align: top;">
                    <div style="border-bottom: 1px solid #000; height: 30px; margin-bottom: 3px;"></div>
                    <div style="font-size: 9px; font-weight: bold;">City Revenue Officer</div>
                    <div style="font-size: 7px; color: #666;">Signature with Date</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- SYSTEM REFERENCE -->
    <div class="footer">
        <strong>System Reference (for internal verification):</strong><br>
        GIS ID: {{ $gisid }} |
        Building Area: {{ number_format($buildingData['building']['area'] ?? 0, 2) }} sqft |
        Assessment Area: {{ number_format($buildingData['assessment']['area'] ?? 0, 2) }} sqft |
        Area Variation: {{ number_format($buildingData['area_comparison']['area_variation'] ?? 0, 2) }} sqft |
        Usage Status: {{ $buildingData['usage_comparison']['usage_status_label'] ?? 'N/A' }}
    </div>

</body>
</html>

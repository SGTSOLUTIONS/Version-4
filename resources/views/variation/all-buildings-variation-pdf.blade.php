<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>All Buildings Area Variation</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #000; padding: 10px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 10px; }
        .header h1 { font-size: 14px; margin: 0; }
        .header .subtitle { font-size: 9px; color: #555; }

        .summary-box { background: #f0f4ff; border: 1px solid #4472C4; border-radius: 4px; padding: 8px 12px; margin-bottom: 12px; }
        .summary-box .stat { display: inline-block; margin-right: 18px; }
        .summary-box .stat .num { font-weight: bold; font-size: 11px; color: #1A3C6E; }
        .summary-box .stat .lbl { font-size: 7px; color: #555; display: block; }

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 3px 4px; font-size: 7px; }
        th { background: #1A3C6E; color: #fff; font-weight: bold; text-align: left; }
        tr:nth-child(even) { background: #f9f9f9; }

        .badge { display: inline-block; padding: 1px 5px; border-radius: 8px; font-size: 6px; font-weight: bold; }
        .badge-success   { background: #d4edda; color: #155724; }
        .badge-danger    { background: #f8d7da; color: #721c24; }
        .badge-warning   { background: #fff3cd; color: #856404; }
        .badge-secondary { background: #e2e3e5; color: #383d41; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 12px; border-top: 1px solid #ccc; padding-top: 6px; font-size: 7px; color: #666; text-align: center; }

        .signature-block { margin-top: 25px; border-top: 2px solid #333; padding-top: 10px; }
        .sig-row { display: table; width: 100%; }
        .sig-col { display: table-cell; text-align: center; padding: 0 5px; width: 25%; }
        .sig-col .sig-line { border-bottom: 1px solid #000; height: 22px; margin-bottom: 3px; }
        .sig-col .sig-label { font-size: 7px; font-weight: bold; }
        .sig-col .sig-sub { font-size: 6px; color: #666; }
    </style>
</head>
<body>

    <div class="header">
        <h1>AREA VARIATION REPORT — ALL BUILDINGS</h1>
        <div class="subtitle">
            Ward: {{ $ward->ward_no ?? 'N/A' }} |
            Zone: {{ $zone->zone_name ?? 'N/A' }} |
            Minimum Variation: {{ number_format($summary['min_variation'], 2) }} sqft
        </div>
        <div class="subtitle">Generated: {{ $date }} {{ $time }}</div>
    </div>

    <!-- SUMMARY BOX -->
    <div class="summary-box">
        <div class="stat">
            <span class="num">{{ $summary['total_buildings'] }}</span>
            <span class="lbl">Total Buildings</span>
        </div>
        <div class="stat">
            <span class="num">{{ number_format($summary['total_building_area'], 2) }}</span>
            <span class="lbl">Total Building Area (sqft)</span>
        </div>
        <div class="stat">
            <span class="num">{{ number_format($summary['total_assessment_area'], 2) }}</span>
            <span class="lbl">Total Assessment Area (sqft)</span>
        </div>
        <div class="stat">
            <span class="num">{{ number_format($summary['total_variation'], 2) }}</span>
            <span class="lbl">Total Area Variation (sqft)</span>
        </div>
    </div>

    <!-- BUILDINGS TABLE -->
    <table>
        <thead>
            <tr>
                <th style="width:30px;">#</th>
                <th style="width:110px;">GIS ID</th>
                <th>Building Usage</th>
                <th class="text-right">Building Area</th>
                <th>Assessment Usage(s)</th>
                <th class="text-right">Assessment Area</th>
                <th class="text-right">Area Variation</th>
                <th class="text-right">Variation %</th>
                <th class="text-center">Area Status</th>
                <th class="text-center">Usage Status</th>
                <th class="text-center">Assessments</th>
            </tr>
        </thead>
        <tbody>
            @php $i = 1; @endphp
            @foreach($buildings as $gisid => $b)
                @php
                    $areaStatus  = $b['area_comparison']['area_status']  ?? 'N/A';
                    $usageStatus = $b['usage_comparison']['usage_status_label'] ?? 'N/A';
                    $usageClass  = $b['usage_comparison']['usage_badge_class'] ?? 'badge-secondary';
                    // Map badge class → pdf-safe
                    $usageClassMap = [
                        'badge-match'     => 'badge-success',
                        'badge-variation' => 'badge-danger',
                        'badge-warning'   => 'badge-warning',
                        'badge-partial'   => 'badge-warning',
                        'badge-secondary' => 'badge-secondary',
                    ];
                    $usageClass = $usageClassMap[$usageClass] ?? 'badge-secondary';

                    $assessmentUsages = $b['usage_comparison']['all_assessment_usages'] ?? [];
                @endphp
                <tr>
                    <td class="text-center">{{ $i++ }}</td>
                    <td><strong>{{ $gisid }}</strong></td>
                    <td>{{ $b['building']['usage'] ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($b['area_comparison']['building_area'] ?? 0, 2) }}</td>
                    <td>{{ implode(', ', $assessmentUsages) ?: 'N/A' }}</td>
                    <td class="text-right">{{ number_format($b['area_comparison']['assessment_area'] ?? 0, 2) }}</td>
                    <td class="text-right" style="color:#c00; font-weight:bold;">
                        +{{ number_format($b['area_comparison']['area_variation'] ?? 0, 2) }}
                    </td>
                    <td class="text-right">{{ number_format($b['area_comparison']['variation_percentage'] ?? 0, 1) }}%</td>
                    <td class="text-center">
                        <span class="badge badge-{{ $areaStatus === 'VARIATION' ? 'danger' : 'success' }}">
                            {{ $areaStatus }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $usageClass }}">{{ $usageStatus }}</span>
                    </td>
                    <td class="text-center">{{ $b['assessment']['count'] ?? 0 }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

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

    <div class="footer">
        <strong>System Reference:</strong>
        Ward {{ $ward->ward_no }} |
        Zone {{ $zone->zone_name }} |
        Buildings with Area Variation ≥ {{ number_format($summary['min_variation'], 2) }} sqft |
        Total: {{ $summary['total_buildings'] }} buildings
    </div>

</body>
</html>

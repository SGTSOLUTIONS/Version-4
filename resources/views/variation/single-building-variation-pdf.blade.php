<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $gisid }} — Area Variation</title>
    <style>
        @page { margin: 15px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #000;
        }

        /* ─── HEADER ─── */
        .header {
            text-align: center;
            border-bottom: 3px double #1A3C6E;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 16px;
            margin: 0;
            color: #1A3C6E;
            letter-spacing: 0.5px;
        }
        .header .subtitle {
            font-size: 10px;
            color: #444;
            margin-top: 4px;
        }
        .header .meta {
            font-size: 9px;
            color: #666;
            margin-top: 2px;
        }

        /* ─── BUILDING ID BOX ─── */
        .building-id-box {
            background: #1A3C6E;
            color: #fff;
            padding: 8px 14px;
            border-radius: 4px;
            margin-bottom: 10px;
            text-align: center;
        }
        .building-id-box .gis-label {
            font-size: 8px;
            color: #cbd5e1;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .building-id-box .gis-value {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-top: 2px;
        }

        /* ─── SECTION TITLES ─── */
        .section-title {
            background: #4472C4;
            color: #fff;
            padding: 4px 10px;
            font-weight: bold;
            font-size: 10px;
            margin-top: 10px;
            margin-bottom: 4px;
        }

        /* ─── TABLES ─── */
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
            vertical-align: top;
        }
        th {
            background: #f0f0f0;
            font-weight: bold;
        }

        /* ─── BADGES ─── */
        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 7px;
            font-weight: bold;
        }
        .badge-success   { background: #d4edda; color: #155724; }
        .badge-danger    { background: #f8d7da; color: #721c24; }
        .badge-warning   { background: #fff3cd; color: #856404; }
        .badge-info      { background: #d1ecf1; color: #0c5460; }
        .badge-secondary { background: #e2e3e5; color: #383d41; }

        /* ─── SUMMARY BOX ─── */
        .summary-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 8px 12px;
            margin: 8px 0;
        }
        .summary-box .stat {
            display: inline-block;
            margin-right: 16px;
        }
        .summary-box .stat .num {
            font-weight: bold;
            font-size: 11px;
            color: #1A3C6E;
        }
        .summary-box .stat .lbl {
            font-size: 7px;
            color: #666;
            display: block;
        }

        /* ─── SIGNATURE BLOCK ─── */
        .signature-block {
            margin-top: 20px;
            border-top: 2px solid #333;
            padding-top: 10px;
        }
        .sig-row { display: table; width: 100%; }
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
        .sig-col .sig-label { font-size: 7px; font-weight: bold; }
        .sig-col .sig-sub { font-size: 6px; color: #666; }

        .footer {
            margin-top: 12px;
            border-top: 1px solid #ccc;
            padding-top: 6px;
            font-size: 7px;
            color: #666;
            text-align: center;
        }

        /* ─── BUILDING IMAGE ─── */
        .building-img-wrap {
            text-align: center;
            margin-bottom: 10px;
        }
        .building-img-wrap img {
            max-width: 100%;
            max-height: 260px;
            border: 1px solid #ccc;
            padding: 3px;
        }
        .building-img-wrap .img-caption {
            font-size: 7px;
            color: #666;
            margin-top: 3px;
        }
    </style>
</head>
<body>

    {{-- ═════ HEADER ═════ --}}
    <div class="header">
        <h1>AREA VARIATION REPORT</h1>
        <div class="subtitle">
            Ward: {{ $ward->ward_no ?? 'N/A' }} &nbsp;|&nbsp;
            Zone: {{ $zone->zone_name ?? 'N/A' }}
        </div>
        <div class="meta">Generated: {{ $date }} {{ $time }}</div>
    </div>

    {{-- ═════ BUILDING ID (BIG) ═════ --}}
    <div class="building-id-box">
        <div class="gis-label">GIS ID</div>
        <div class="gis-value">{{ $gisid }}</div>
        <div class="gis-label" style="margin-top:4px;">
            Building {{ $buildingNumber }} of {{ $totalBuildings }}
        </div>
    </div>

    {{-- ═════ BUILDING IMAGE ═════ --}}
    @if(!empty($buildingImage))
        <div class="section-title">BUILDING IMAGE</div>
        <div class="building-img-wrap">
            <img src="{{ $buildingImage }}" alt="Building {{ $gisid }}" />
        </div>
    @endif

    {{-- ═════ SUMMARY ═════ --}}
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
            <span class="lbl">Assessment Area (sqft)</span>
        </div>
        <div class="stat">
            <span class="num" style="color:#c00;">
                {{ number_format($buildingData['area_comparison']['area_variation'] ?? 0, 2) }}
            </span>
            <span class="lbl">Area Variation (sqft)</span>
        </div>
        <div class="stat">
            <span class="num" style="color:#c00;">
                {{ $buildingData['area_comparison']['variation_percentage'] ?? 0 }}%
            </span>
            <span class="lbl">Variation %</span>
        </div>
    </div>

    {{-- ═════ ASSESSMENT DETAILS ═════ --}}
    <div class="section-title">ASSESSMENT DETAILS</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Assessment No</th>
                <th>Type</th>
                <th>Area (sqft)</th>
                <th>QC Usage</th>
                <th>Bill Usage</th>
                <th>Owner Name</th>
                <th>Phone</th>
                <th>Door No</th>
                <th>MIS Assessment</th>
                <th>MIS Area</th>
                <th>Half Year Tax</th>
                <th>Balance</th>
            </tr>
        </thead>
        <tbody>
            @php $points = $buildingData['assessment']['details']['points'] ?? []; @endphp
            @forelse($points as $idx => $point)
                @php
                    $mis = $point['mis_data'] ?? null;
                    if (!is_array($mis)) { $mis = []; }

                    $owner = $point['owner_name']   ?? $mis['owner_name']   ?? null;
                    $phone = $point['phone_number'] ?? $mis['phone_number'] ?? null;

                    $doorParts = array_filter([
                        $point['new_door_no'] ?? $mis['new_door_no'] ?? null,
                        $point['old_door_no'] ?? $mis['old_door_no'] ?? null,
                    ]);
                    $door = $doorParts ? implode(' / ', $doorParts) : null;

                    $halfYearTax   = $mis['half_year_tax'] ?? null;
                    $balance       = $mis['balance']       ?? null;
                    $misAssessment = $mis['assessment']    ?? null;
                    $misPlotArea   = $mis['plot_area']     ?? null;
                @endphp
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td><strong>{{ $point['assessment'] ?? 'N/A' }}</strong></td>
                    <td>
                        @php $type = strtolower($point['assessment_type'] ?? ''); @endphp
                        <span class="badge badge-{{ $type === 'old' ? 'warning' : ($type === 'new' ? 'success' : 'info') }}">
                            {{ $point['assessment_type'] ?? 'N/A' }}
                        </span>
                    </td>
                    <td>{{ number_format((float)($point['point_area'] ?? 0), 2) }}</td>
                    <td>{{ $point['qcusage']    ?? 'N/A' }}</td>
                    <td>{{ $point['bill_usage'] ?? 'N/A' }}</td>
                    <td>{{ $owner ?: 'N/A' }}</td>
                    <td>{{ $phone ?: 'N/A' }}</td>
                    <td>{{ $door  ?: 'N/A' }}</td>
                    <td>{{ $misAssessment ?: 'N/A' }}</td>
                    <td>{{ $misPlotArea !== null ? number_format((float)$misPlotArea, 2) : 'N/A' }}</td>
                    <td>{{ $halfYearTax !== null ? number_format((float)$halfYearTax, 2) : 'N/A' }}</td>
                    <td>{{ $balance     !== null ? number_format((float)$balance,     2) : 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" style="text-align: center;">No assessments found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ═════ COMPARISON SUMMARY ═════ --}}
    <div style="margin-top: 12px;">
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
                <td>
                    +{{ number_format($buildingData['area_comparison']['area_variation'] ?? 0, 2) }} sqft
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
                    @php
                        $usageClassMap = [
                            'badge-match'     => 'success',
                            'badge-variation' => 'danger',
                            'badge-warning'   => 'warning',
                            'badge-partial'   => 'warning',
                            'badge-secondary' => 'secondary',
                        ];
                        $uClass = $usageClassMap[$buildingData['usage_comparison']['usage_badge_class'] ?? 'badge-secondary'] ?? 'secondary';
                    @endphp
                    <span class="badge badge-{{ $uClass }}">
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
            <tr>
                <td><strong>Number of Floors</strong></td>
                <td>{{ $buildingData['building']['details']['number_floor'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Basement</strong></td>
                <td>{{ $buildingData['building']['details']['basement'] ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    {{-- ═════ SIGNATURES ═════ --}}
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

    {{-- ═════ FOOTER ═════ --}}
    <div class="footer">
        <strong>GIS ID:</strong> {{ $gisid }} |
        <strong>Assessments:</strong> {{ count($points) }} |
        <strong>Area Variation:</strong> {{ number_format($buildingData['area_comparison']['area_variation'] ?? 0, 2) }} sqft |
        <strong>Filter:</strong> Assessment Area &gt; 0 &amp; Variation ≥ {{ number_format($summary['min_variation'] ?? 500, 2) }} sqft
    </div>

</body>
</html>

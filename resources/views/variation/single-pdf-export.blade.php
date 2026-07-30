<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5px;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        td,
        th {
            border: 1px solid #000;
            padding: 3px 5px;
            vertical-align: top;
        }

        .no-border td,
        .no-border {
            border: none !important;
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            margin: 0;
        }

        .subtitle {
            text-align: center;
            font-size: 10px;
            margin: 2px 0;
        }

        .label {
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .small {
            font-size: 8.5px;
        }

        .box {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            text-align: center;
            font-size: 9px;
        }

        .section-title {
            font-weight: bold;
            margin-top: 8px;
            margin-bottom: 2px;
        }

        .mobile-boxes td {
            width: 18px;
            text-align: center;
            padding: 4px 2px;
        }

        h4 {
            margin: 6px 0 2px 0;
            font-size: 10px;
        }

        /* ─── SIGNATURE STYLES ─── */
        .signature-block {
            margin-top: 20px;
            border-top: 1px solid #000;
            padding-top: 15px;
        }

        .signature-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .signature-grid .sig-item {
            display: table-cell;
            text-align: center;
            vertical-align: top;
            padding: 5px 10px;
            width: 25%;
        }

        .signature-grid .sig-item .sig-title {
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .signature-grid .sig-item .sig-line {
            border-bottom: 1px solid #000;
            height: 30px;
            margin: 5px 0;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }

        .signature-grid .sig-item .sig-label {
            font-size: 8px;
            color: #666;
        }

        .signature-grid .sig-item .sig-date {
            font-size: 8px;
            color: #666;
            margin-top: 3px;
        }

        /* ─── OFFICIAL SEAL PLACEHOLDER ─── */
        .seal-placeholder {
            display: inline-block;
            border: 1px dashed #999;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            line-height: 40px;
            text-align: center;
            font-size: 6px;
            color: #999;
            margin: 5px auto;
        }

        /* ─── FOOTER SIGNATURE AREA ─── */
        .footer-signatures {
            margin-top: 25px;
            border-top: 2px solid #333;
            padding-top: 15px;
        }

        .footer-signatures .sig-row {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }

        .footer-signatures .sig-row .sig-col {
            display: table-cell;
            text-align: center;
            padding: 0 10px;
            vertical-align: top;
        }

        .footer-signatures .sig-row .sig-col .sig-box {
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 8px 12px;
            min-height: 80px;
            background: #f9f9f9;
        }

        .footer-signatures .sig-row .sig-col .sig-box .sig-name {
            font-weight: bold;
            font-size: 9px;
            margin-top: 5px;
        }

        .footer-signatures .sig-row .sig-col .sig-box .sig-designation {
            font-size: 8px;
            color: #555;
        }

        .footer-signatures .sig-row .sig-col .sig-box .sig-stamp {
            font-size: 7px;
            color: #999;
            font-style: italic;
            margin-top: 3px;
        }

        .footer-signatures .sig-row .sig-col .sig-box .sig-placeholder {
            height: 35px;
            border-bottom: 1px dashed #aaa;
            margin: 8px 0;
        }

        @media print {
            .footer-signatures .sig-row .sig-col .sig-box {
                border: 1px solid #aaa;
                background: #fff;
            }
        }

        /* ─── RESPONSIVE ─── */
        @media (max-width: 600px) {
            .signature-grid .sig-item {
                display: block;
                width: 100%;
                margin-bottom: 15px;
            }

            .footer-signatures .sig-row .sig-col {
                display: block;
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>

<body>

    <table class="no-border">
        <tr class="no-border">
            <td class="no-border" style="width:70%;">
                <p class="title">FORM 2</p>
                <p class="subtitle">[See rules 256 (2) and 257 (1)]</p>
                <p class="subtitle" style="font-weight:bold;">RETURN FOR REASSESSMENT OF PROPERTY TAX</p>
                <p class="subtitle" style="font-weight:bold;">GREATER CHENNAI CORPORATION</p>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td style="width:60%;">
                <strong>1. Zone No.</strong> {{ $zone->zone_name ?? ($zone->id ?? '') }}
                &nbsp;&nbsp; <strong>Ward No.</strong> {{ $ward->ward_no ?? '' }}
                &nbsp;&nbsp; <strong>Mobile Number</strong>{{ $data['assessment']['details']['points'][0]['phone_number'] ?? '' }}
            </td>
            <td style="width:40%;">
                <table class="mobile-boxes no-border">
                    <tr class="no-border">
                        @for ($i = 0; $i < 10; $i++)
                            <td class="no-border"><span class="box">&nbsp;</span></td>
                        @endfor
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="label">2. Property Tax Number</td>
            <td>{{ $data['assessment']['details']['points'][0]['assessment'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">3. Name of the Owner (Mandatory)</td>
            <td>{{ $data['assessment']['details']['points'][0]['owner_name'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">4. Name of the Occupier</td>
            <td>{{ $data['assessment']['details']['points'][0]['owner_name'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">5. Address:</td>
            <td style="height:40px;">&nbsp;</td>
        </tr>
        <tr>
            <td class="label">6. Communication Address (if different from the land, building, telecom tower, structure
                being assessed)</td>
            <td style="height:30px;">&nbsp;</td>
        </tr>
        <tr>
            <td class="label">7. Email address</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td class="label">8. Building Plan Approval Number, if available</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td class="label">9. Building Plan Approval Date, if available</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td class="label">10. Ownership of Land</td>
            <td>
                Central Government <span class="box"></span> &nbsp; Private <span class="box"></span><br>
                State Government <span class="box"></span> &nbsp; Municipal Corporation <span class="box"></span>
            </td>
        </tr>
        <tr>
            <td class="label">11. Ownership of Building, Land, Tower or Structure</td>
            <td>
                Central Government <span class="box"></span> &nbsp; Private <span class="box"></span><br>
                State Government <span class="box"></span> &nbsp; Municipal Corporation <span class="box"></span>
            </td>
        </tr>
        <tr>
            <td colspan="2" class="label">For Independent Building/Vacant Land/Structure:</td>
        </tr>
        <tr>
            <td class="label">12. Plot Area/Extent of Land (in sq.ft):</td>
            <td>{{ number_format($data['polygon']['sqfeet'] ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td class="label">13. Total plinth area also referred as Covered Built-up Area (in sq.ft):</td>
            <td>{{ number_format($data['building']['area'] ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td class="label">14. For Flats:<br>
                1. Total built-up area of flat including Covered common area &amp; parking area (in sq.ft.)<br>
                2. Total Land Extent<br>
                3. Undivided share of Land
            </td>
            <td>
                .............................................................<br>
                .............................................................<br>
                .............................................................
            </td>
        </tr>
        <tr>
            <td class="label">15. Document No/Date<br>Sub – Registrar Office</td>
            <td>&nbsp;</td>
        </tr>
        @php
            $usage = strtoupper(trim($data['building']['building_usage'] ?? ($data['assessment']['usage'] ?? '')));
            $mark = fn($cond) => $cond ? '[X]' : '[ ]';
        @endphp
        <tr>
            <td class="label" style="width:30%;">16. Usage</td>
            <td class="small">
                (a) {{ $mark($usage === 'RESIDENTIAL') }} Residential<br>
                (b) {{ $mark($usage === 'COMMERCIAL') }} Non-Residential (shops, offices, hotels, hospitals, etc.)<br>
                (c) {{ $mark($usage === 'INDUSTRIAL') }} Industrial Unit<br>
                (d) {{ $mark($usage === 'MIXED') }} Mixed Usage (Part residential and part non-residential)<br>
                (e) {{ $mark(false) }} Telecommunication Tower<br>
                (f) {{ $mark(false) }} Storage Structure<br>
                (g) {{ $mark($usage === 'VACANT') }} Vacant land<br>
                (h) {{ $mark(!in_array($usage, ['RESIDENTIAL', 'COMMERCIAL', 'INDUSTRIAL', 'MIXED', 'VACANT', ''])) }} Any
                other category
                &nbsp; Specify:
                {{ !in_array($usage, ['RESIDENTIAL', 'COMMERCIAL', 'INDUSTRIAL', 'MIXED', 'VACANT', '']) ? $usage : '' }}
            </td>
        </tr>
        <tr>
            <td class="label">17. Construction Type</td>
            <td>Permanent <span class="box"></span> &nbsp; Semi-permanent <span class="box"></span></td>
        </tr>
    </table>

    <h4>18. Building/Structure Measurements (in sq.ft.) as per table below:</h4>
    @php
        $floors = $data['building']['details']['number_floor'] ?? 0;
        $basement = $data['building']['details']['basement'] ?? 0;
        $sqfeet = $data['polygon']['sqfeet'] ?? 0;
        $isResidential = $usage === 'RESIDENTIAL';
        $isCommercial = in_array($usage, ['COMMERCIAL', 'INDUSTRIAL', 'MIXED']);

        $rows18 = [
            'Basement' => $basement > 0 ? $basement * $sqfeet : 0,
            'Ground Floor' => $floors >= 1 ? $sqfeet : 0,
            'I Floor' => $floors >= 2 ? $sqfeet : 0,
            'II Floor' => $floors >= 3 ? $sqfeet : 0,
            'III Floor' => $floors >= 4 ? $sqfeet : 0,
            'IV Floor' => $floors >= 5 ? $sqfeet : 0,
            'Others Floors*' => $floors > 5 ? ($floors - 5) * $sqfeet : 0,
            'Head Room' => 0,
            'Lift Room' => 0,
        ];
        $total18 = array_sum($rows18);
    @endphp
    <table>
        <tr>
            <th rowspan="2">Nature of Construction</th>
            <th colspan="2">Total Plinth area</th>
            <th colspan="2">Residential Portion</th>
            <th colspan="2">Commercial Portion #</th>
        </tr>
        <tr>
            <th>Permanent (P)</th>
            <th>Semi-Permanent (SP)</th>
            <th>Permanent (P)</th>
            <th>Semi-Permanent (SP)</th>
            <th>Permanent (P)</th>
            <th>Semi-Permanent (SP)</th>
        </tr>
        @foreach ($rows18 as $label => $val)
            <tr>
                <td>{{ $label }}</td>
                <td class="center">{{ $val > 0 ? number_format($val, 2) : '' }}</td>
                <td></td>
                <td class="center">{{ $val > 0 && $isResidential ? number_format($val, 2) : '' }}</td>
                <td></td>
                <td class="center">{{ $val > 0 && $isCommercial ? number_format($val, 2) : '' }}</td>
                <td></td>
            </tr>
        @endforeach
        <tr>
            <td class="label">Total (sq.ft.)</td>
            <td class="center label">{{ number_format($total18, 2) }}</td>
            <td></td>
            <td class="center label">{{ $isResidential ? number_format($total18, 2) : '' }}</td>
            <td></td>
            <td class="center label">{{ $isCommercial ? number_format($total18, 2) : '' }}</td>
            <td></td>
        </tr>
    </table>

    <h4 style="margin-top:10px;">19. Building/Structure Measurements before Additions/Alteration (in sq.ft.):</h4>
    <table>
        <tr>
            <th rowspan="2">Floor / Nature of Construction</th>
            <th colspan="2">Total Plinth area</th>
            <th colspan="2">Residential Portion</th>
            <th colspan="2">Commercial Portion #</th>
        </tr>
        <tr>
            <th>Permanent (P)</th>
            <th>Semi-Permanent (SP)</th>
            <th>Permanent (P)</th>
            <th>Semi-Permanent (SP)</th>
            <th>Permanent (P)</th>
            <th>Semi-Permanent (SP)</th>
        </tr>
        @foreach (['Basement', 'Ground Floor', 'I Floor', 'II Floor', 'III Floor', 'IV Floor', 'Others Floors*', 'Head Room', 'Lift Room'] as $label)
            <tr>
                <td>{{ $label }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endforeach
        <tr>
            <td class="label">Total (sq.ft.)</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </table>
    <p class="small">* Details to be given separately &nbsp;&nbsp; # Commercial portion includes non-residential,
        industrial and storage structures usage types</p>

    <table>
        <tr>
            <td class="label" style="width:40%;">20. Lease deed agreement details (telecommunication towers)</td>
            <td>
                <span class="small">
                    (a) Details of land/building where tower is erected: ______________________<br>
                    (b) Floor Location: ______________________<br>
                    (c) Date of Erection: ______________________<br>
                    (d) Area of land occupied (sq.ft.): ______________________<br>
                    (e) Date of lease deed agreement: ______________________<br>
                    (f) Period of the agreement: ______________________<br>
                    (g) Name of Service provider: ______________________<br>
                    (h) Monthly rent as per agreement (Rs.): ______________________
                </span>
            </td>
        </tr>
        <tr>
            <td class="label">21. Date on which reconstruction/alteration completed</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td class="label">22. Existing tax particulars</td>
            <td>
                1. Existing half-yearly tax Rs: ______________________<br>
                2. Tax paid up to: ______________________
            </td>
        </tr>
    </table>

    <div style="margin-top:14px;">
        <p><strong>DECLARATION</strong></p>
        <p class="small">
            I ..................................................... hereby declare that the particulars furnished above
            are true and correct to the best of my knowledge.<br>
            I further declare that the above said property is not situated in any water body or waterways or water
            catchment area.
            I am aware that if the furnished information is wrong, legal action will be initiated against me.
        </p>
        <table class="no-border" style="margin-top:20px;">
            <tr class="no-border">
                <td class="no-border" style="width:50%;">
                    Place: ______________________
                    <br><br>
                    <span style="font-size:8px; color:#666;">(Location where signed)</span>
                </td>
                <td class="no-border" style="text-align:right;">
                    <div style="border-bottom:1px solid #000; width:180px; margin-left:auto; height:25px;"></div>
                    Signature of the Applicant
                    <br>
                    <span style="font-size:8px; color:#666;">(Applicant's signature)</span>
                </td>
            </tr>
            <tr class="no-border">
                <td class="no-border">Date: {{ $date }}</td>
                <td class="no-border"></td>
            </tr>
        </table>
        <p class="small" style="margin-top:8px;">
            Note: The applicant is required to submit the following documents along with duly filled-in application:<br>
            1. Copy of the title deed/registered property documents and other related documents<br>
            2. Copy of the approved building plan, if available<br>
            3. Copy of construction completion certificate, if available<br>
            4. Copy of the current lease deed agreement (applicable in case of telecommunication towers)
        </p>
    </div>

    <div style="page-break-inside: avoid; margin-top:14px;">
        <p class="label">For Official Use — Working Sheet: Tax Calculation (Copy of the computation sheet to be given
            to the assessee):</p>
        <table>
            <tr>
                <td class="label" style="width:60%;">Annual Value of Land, Building, Storage Structure or
                    Telecommunication Towers:</td>
                <td></td>
            </tr>
            <tr>
                <td>General Purpose Tax %</td>
                <td></td>
            </tr>
            <tr>
                <td>Education tax %</td>
                <td></td>
            </tr>
            <tr>
                <td>Library cess %</td>
                <td></td>
            </tr>
            <tr>
                <td class="label">Grand Total (in Rs.):</td>
                <td></td>
            </tr>
            <tr>
                <td>Existing Property Tax Number</td>
                <td>{{ $data['assessment']['details']['points'][0]['assessment'] ?? '' }}</td>
            </tr>
            <tr>
                <td>Half-yearly tax due (in Rs.)</td>
                <td></td>
            </tr>
            <tr>
                <td>Notice date</td>
                <td></td>
            </tr>
        </table>
        <p class="small" style="margin-top:6px;">
            * Provisions related to water tax and sewerage tax shall not apply to any municipality to which the Chennai
            Metropolitan Water Supply and Sewerage Act, 1978 applies.<br>
            This property may be modified with effect from .............………………….
        </p>

        <!-- ============================================================ -->
        <!--          OFFICIAL SIGNATURE BLOCK - UPDATED WITH SPACE        -->
        <!-- ============================================================ -->
        <div class="signature-block">
            <div style="text-align:center; font-weight:bold; font-size:10px; margin-bottom:12px;">
                OFFICIAL SIGNATURES
            </div>

            <div class="signature-grid">
                <!-- 1. ASSESSOR -->
                <div class="sig-item">
                    <div class="sig-title">Assessor</div>
                    <div class="sig-placeholder" style="height:35px; border-bottom:1px solid #000; margin:5px 0;"></div>
                    <div class="sig-label">(Signature with Date)</div>
                    <div style="font-size:7px; color:#999; margin-top:2px;">
                        <span class="seal-placeholder" style="display:inline-block; border:1px dashed #999; border-radius:50%; width:35px; height:35px; line-height:35px; text-align:center; font-size:6px; color:#999;">SEAL</span>
                    </div>
                    <div style="font-size:7px; color:#999; margin-top:3px;">Name: _________________</div>
                    <div style="font-size:7px; color:#999;">Designation: _____________</div>
                    <div style="font-size:7px; color:#999;">Date: _________________</div>
                </div>

                <!-- 2. ASSISTANT REVENUE OFFICER -->
                <div class="sig-item">
                    <div class="sig-title">Assistant Revenue Officer</div>
                    <div class="sig-placeholder" style="height:35px; border-bottom:1px solid #000; margin:5px 0;"></div>
                    <div class="sig-label">(Signature with Date)</div>
                    <div style="font-size:7px; color:#999; margin-top:2px;">
                        <span class="seal-placeholder" style="display:inline-block; border:1px dashed #999; border-radius:50%; width:35px; height:35px; line-height:35px; text-align:center; font-size:6px; color:#999;">SEAL</span>
                    </div>
                    <div style="font-size:7px; color:#999; margin-top:3px;">Name: _________________</div>
                    <div style="font-size:7px; color:#999;">Designation: _____________</div>
                    <div style="font-size:7px; color:#999;">Date: _________________</div>
                </div>

                <!-- 3. ZONAL OFFICER -->
                <div class="sig-item">
                    <div class="sig-title">Zonal Officer</div>
                    <div class="sig-placeholder" style="height:35px; border-bottom:1px solid #000; margin:5px 0;"></div>
                    <div class="sig-label">(Signature with Date)</div>
                    <div style="font-size:7px; color:#999; margin-top:2px;">
                        <span class="seal-placeholder" style="display:inline-block; border:1px dashed #999; border-radius:50%; width:35px; height:35px; line-height:35px; text-align:center; font-size:6px; color:#999;">SEAL</span>
                    </div>
                    <div style="font-size:7px; color:#999; margin-top:3px;">Name: _________________</div>
                    <div style="font-size:7px; color:#999;">Designation: _____________</div>
                    <div style="font-size:7px; color:#999;">Date: _________________</div>
                </div>

                <!-- 4. CITY REVENUE OFFICER -->
                <div class="sig-item">
                    <div class="sig-title">City Revenue Officer</div>
                    <div class="sig-placeholder" style="height:35px; border-bottom:1px solid #000; margin:5px 0;"></div>
                    <div class="sig-label">(Signature with Date)</div>
                    <div style="font-size:7px; color:#999; margin-top:2px;">
                        <span class="seal-placeholder" style="display:inline-block; border:1px dashed #999; border-radius:50%; width:35px; height:35px; line-height:35px; text-align:center; font-size:6px; color:#999;">SEAL</span>
                    </div>
                    <div style="font-size:7px; color:#999; margin-top:3px;">Name: _________________</div>
                    <div style="font-size:7px; color:#999;">Designation: _____________</div>
                    <div style="font-size:7px; color:#999;">Date: _________________</div>
                </div>
            </div>

            <!-- Office Stamp / Verification -->
            <div style="text-align:center; margin-top:12px; padding-top:8px; border-top:1px solid #ddd;">
                <span style="font-size:7px; color:#888;">
                    <strong>Office Stamp:</strong>
                    <span style="border:1px solid #999; padding:2px 15px; display:inline-block; min-width:120px; height:20px;">&nbsp;</span>
                    &nbsp;&nbsp;|&nbsp;&nbsp;
                    <strong>Verification Date:</strong>
                    <span style="border-bottom:1px solid #999; padding:0 10px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                </span>
            </div>
        </div>
        <!-- ============================================================ -->
        <!--              END OFFICIAL SIGNATURE BLOCK                      -->
        <!-- ============================================================ -->
    </div>

    <!-- ============================================================ -->
    <!--              BOTTOM SIGNATURE AREA (Alternative)               -->
    <!-- ============================================================ -->
    <div class="footer-signatures">
        <div style="text-align:center; font-size:8px; color:#666; margin-bottom:8px;">
            <strong>FOR OFFICIAL USE ONLY</strong>
        </div>
        <div class="sig-row">
            <div class="sig-col">
                <div class="sig-box">
                    <div class="sig-placeholder" style="height:30px; border-bottom:1px dashed #aaa;"></div>
                    <div class="sig-name">Assessor</div>
                    <div class="sig-designation">Assessment Department</div>
                    <div class="sig-stamp">Signature &amp; Date</div>
                </div>
            </div>
            <div class="sig-col">
                <div class="sig-box">
                    <div class="sig-placeholder" style="height:30px; border-bottom:1px dashed #aaa;"></div>
                    <div class="sig-name">Assistant Revenue Officer</div>
                    <div class="sig-designation">Revenue Department</div>
                    <div class="sig-stamp">Signature &amp; Date</div>
                </div>
            </div>
            <div class="sig-col">
                <div class="sig-box">
                    <div class="sig-placeholder" style="height:30px; border-bottom:1px dashed #aaa;"></div>
                    <div class="sig-name">Zonal Officer</div>
                    <div class="sig-designation">Zonal Office</div>
                    <div class="sig-stamp">Signature &amp; Date</div>
                </div>
            </div>
            <div class="sig-col">
                <div class="sig-box">
                    <div class="sig-placeholder" style="height:30px; border-bottom:1px dashed #aaa;"></div>
                    <div class="sig-name">City Revenue Officer</div>
                    <div class="sig-designation">Revenue Department</div>
                    <div class="sig-stamp">Signature &amp; Date</div>
                </div>
            </div>
        </div>
    </div>
    <!-- ============================================================ -->

    <!-- System Reference Section -->
    <div style="margin-top:16px; border-top:1px dashed #999; padding-top:6px;" class="small">
        <strong>System Reference (GIS Comparison — for internal verification, not part of official FORM 2):</strong><br>
        GIS ID: {{ $gisid }} &nbsp;|&nbsp;
        Building Area: {{ number_format($data['building']['area'] ?? 0, 2) }} sq.ft &nbsp;|&nbsp;
        Assessment Area: {{ number_format($data['assessment']['area'] ?? 0, 2) }} sq.ft &nbsp;|&nbsp;
        Area Variation: {{ number_format($data['area_comparison']['area_variation'] ?? 0, 2) }} sq.ft
        ({{ $data['area_comparison']['variation_percentage'] ?? 0 }}%) &nbsp;|&nbsp;
        Usage Status: {{ $data['usage_comparison']['usage_status_label'] ?? 'N/A' }}
    </div>

</body>

</html>

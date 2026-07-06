@extends('layouts.office')

@section('title', 'Dashboard — Revenue Department')
@section('page_title', 'Dashboard')

@push('styles')
<style></style>
@endpush

@section('content')

{{-- ── Page header ── --}}
<div class="ol-page-header">
    <div>
        <h1 class="ol-page-title">Good {{ now()->hour < 12 ? 'Morning' : (now()->hour < 17 ? 'Afternoon' : 'Evening') }}, {{ explode(' ', auth()->user()->name ?? 'Officer')[0] }} 👋</h1>
        <p class="ol-page-sub">Here's what's happening in the revenue department today — {{ now()->format('l, d F Y') }}</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span class="ds-pill paid" style="font-size:0.65rem; padding:4px 10px;">
            <i class="bi bi-circle-fill me-1" style="font-size:6px; vertical-align:1px;"></i>Live
        </span>
        <a href="#" class="rv-submit" style="width:auto; height:38px; padding:0 1.2rem; font-size:0.8rem !important; border-radius:9px !important; display:inline-flex; align-items:center; gap:6px; animation:none;">
            <i class="bi bi-download" style="font-size:13px;"></i>
            Export Report
        </a>
    </div>
</div>

{{-- ── Stat grid ── --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="ds-stat green">
            <div class="ds-stat-icon green"><i class="bi bi-currency-rupee"></i></div>
            <div class="ds-stat-label">Total Collections</div>
            <div class="ds-stat-value">₹4.82Cr</div>
            <span class="ds-stat-change up"><i class="bi bi-arrow-up-short"></i>+8.4% vs last month</span>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ds-stat gold">
            <div class="ds-stat-icon gold"><i class="bi bi-hourglass-split"></i></div>
            <div class="ds-stat-label">Pending Demands</div>
            <div class="ds-stat-value">₹1.23Cr</div>
            <span class="ds-stat-change down"><i class="bi bi-arrow-up-short"></i>+2.1% vs last week</span>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ds-stat blue">
            <div class="ds-stat-icon blue"><i class="bi bi-people"></i></div>
            <div class="ds-stat-label">Active Taxpayers</div>
            <div class="ds-stat-value">14,832</div>
            <span class="ds-stat-change up"><i class="bi bi-arrow-up-short"></i>+124 this month</span>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ds-stat red">
            <div class="ds-stat-icon red"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="ds-stat-label">Overdue Notices</div>
            <div class="ds-stat-value">287</div>
            <span class="ds-stat-change down"><i class="bi bi-arrow-down-short"></i>-12 resolved today</span>
        </div>
    </div>
</div>

{{-- ── Row 2: Chart + Quick Actions ── --}}
<div class="row g-3 mb-4">

    {{-- Monthly collections bar chart --}}
    <div class="col-xl-5 col-lg-6">
        <div class="ds-card h-100">
            <div class="ds-card-head">
                <div class="ds-card-title">Monthly Collections — FY {{ now()->month >= 4 ? now()->year : now()->year - 1 }}–{{ now()->month >= 4 ? now()->year + 1 : now()->year }}</div>
                <span class="ds-pill paid">Cr ₹</span>
            </div>
            <div class="ds-card-body">
                @php
                    $months = ['Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar'];
                    $values = [62,74,58,81,90,77,95,88,103,71,68,82];
                    $max    = max($values);
                @endphp
                @foreach($months as $i => $m)
                <div class="ds-bar-row">
                    <span class="ds-bar-label">{{ $m }}</span>
                    <div class="ds-bar-track">
                        <div class="ds-bar-fill {{ $i % 3 === 1 ? 'gold' : ($i % 3 === 2 ? 'blue' : '') }}"
                             style="width:{{ round($values[$i] / $max * 100) }}%"
                             data-width="{{ round($values[$i] / $max * 100) }}"></div>
                    </div>
                    <span class="ds-bar-val">₹{{ $values[$i] }}L</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Quick actions + Collection split --}}
    <div class="col-xl-7 col-lg-6 d-flex flex-column gap-3">

        {{-- Quick actions --}}
        <div class="ds-card">
            <div class="ds-card-head">
                <div class="ds-card-title">Quick Actions</div>
            </div>
            <div class="ds-card-body">
                <div class="row g-2">
                    <div class="col-4 col-sm-2-custom">
                        <a href="#" class="ds-quick-btn">
                            <i class="bi bi-plus-circle"></i>
                            New Collection
                        </a>
                    </div>
                    <div class="col-4 col-sm-2-custom">
                        <a href="#" class="ds-quick-btn">
                            <i class="bi bi-file-earmark-plus"></i>
                            Issue Demand
                        </a>
                    </div>
                    <div class="col-4 col-sm-2-custom">
                        <a href="#" class="ds-quick-btn">
                            <i class="bi bi-person-plus"></i>
                            Add Taxpayer
                        </a>
                    </div>
                    <div class="col-4 col-sm-2-custom">
                        <a href="#" class="ds-quick-btn">
                            <i class="bi bi-credit-card"></i>
                            Payments
                        </a>
                    </div>
                    <div class="col-4 col-sm-2-custom">
                        <a href="#" class="ds-quick-btn">
                            <i class="bi bi-patch-check"></i>
                            Certificate
                        </a>
                    </div>
                    <div class="col-4 col-sm-2-custom">
                        <a href="#" class="ds-quick-btn">
                            <i class="bi bi-file-bar-graph"></i>
                            Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Revenue category progress --}}
        <div class="ds-card flex-grow-1">
            <div class="ds-card-head">
                <div class="ds-card-title">Revenue Category — Monthly Target</div>
                <span style="font-size:0.72rem; color:#9ca3af; font-family:var(--font-mono);">Target: ₹6Cr</span>
            </div>
            <div class="ds-card-body">
                @php
                    $categories = [
                        ['Property Tax',    78, '#10b981'],
                        ['Water Tax',       54, '#fbbf24'],
                        ['Trade Licence',   91, '#3b82f6'],
                        ['Building Plan',   43, '#a78bfa'],
                        ['Advertisement',   67, '#f97316'],
                    ];
                @endphp
                @foreach($categories as $cat)
                <div class="ds-progress-wrap">
                    <div class="ds-progress-head">
                        <span>{{ $cat[0] }}</span>
                        <span>{{ $cat[1] }}%</span>
                    </div>
                    <div class="ds-progress-bar">
                        <div class="ds-progress-fill" style="width:{{ $cat[1] }}%; background:{{ $cat[2] }};"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>
</div>

{{-- ── Row 3: Recent Transactions + Activity ── --}}
<div class="row g-3 mb-4">

    {{-- Recent transactions table --}}
    <div class="col-xl-8">
        <div class="ds-card">
            <div class="ds-card-head">
                <div class="ds-card-title">Recent Transactions</div>
                <a href="#" class="btn btn-sm"
                   style="font-size:0.72rem; color:#10b981; border:1px solid rgba(16,185,129,0.3); border-radius:7px; padding:4px 12px; background:rgba(16,185,129,0.05);">
                   View All <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div style="overflow-x:auto;">
                <table class="ds-table">
                    <thead>
                        <tr>
                            <th>Receipt No.</th>
                            <th>Taxpayer</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $transactions = [
                                ['RCP-20240601','Murugan S.','Property Tax','₹12,400','Today, 10:22','paid'],
                                ['RCP-20240600','Lakshmi D.','Water Tax','₹3,200','Today, 09:45','paid'],
                                ['RCP-20240599','Rajesh K.','Trade Licence','₹8,750','Today, 08:30','paid'],
                                ['RCP-20240598','Priya N.','Building Plan','₹45,000','Yesterday','pending'],
                                ['RCP-20240597','Anbu M.','Property Tax','₹9,600','Yesterday','overdue'],
                                ['RCP-20240596','Selvam R.','Advertisement','₹6,300','2 days ago','paid'],
                                ['RCP-20240595','Kavitha P.','Water Tax','₹2,800','2 days ago','pending'],
                            ];
                        @endphp
                        @foreach($transactions as $txn)
                        <tr>
                            <td><span style="font-family:var(--font-mono); font-size:0.72rem; color:#0a2e1a;">{{ $txn[0] }}</span></td>
                            <td><span style="font-weight:600; color:#111827;">{{ $txn[1] }}</span></td>
                            <td><span style="color:#6b7280;">{{ $txn[2] }}</span></td>
                            <td><span style="font-family:var(--font-mono); font-weight:500; color:#0a2e1a;">{{ $txn[3] }}</span></td>
                            <td><span style="color:#9ca3af; font-size:0.72rem;">{{ $txn[4] }}</span></td>
                            <td><span class="ds-pill {{ $txn[5] }}">{{ $txn[5] }}</span></td>
                            <td>
                                <a href="#" style="color:#10b981; font-size:13px;" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Activity feed --}}
    <div class="col-xl-4">
        <div class="ds-card h-100">
            <div class="ds-card-head">
                <div class="ds-card-title">Recent Activity</div>
                <span style="font-size:0.68rem; color:#9ca3af; font-family:var(--font-mono);">Live</span>
            </div>
            <div class="ds-card-body">

                <div class="ds-activity-item">
                    <div class="ds-activity-dot" style="background:rgba(16,185,129,0.1); color:#10b981;">
                        <i class="bi bi-check2"></i>
                    </div>
                    <div>
                        <div class="ds-activity-text"><strong>Payment collected</strong> — ₹12,400 from Murugan S. (Property Tax)</div>
                        <div class="ds-activity-time">2 minutes ago</div>
                    </div>
                </div>

                <div class="ds-activity-item">
                    <div class="ds-activity-dot" style="background:rgba(251,191,36,0.1); color:#d97706;">
                        <i class="bi bi-file-text"></i>
                    </div>
                    <div>
                        <div class="ds-activity-text"><strong>Demand notice issued</strong> — to 14 defaulters in Ward 7</div>
                        <div class="ds-activity-time">18 minutes ago</div>
                    </div>
                </div>

                <div class="ds-activity-item">
                    <div class="ds-activity-dot" style="background:rgba(59,130,246,0.1); color:#3b82f6;">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <div>
                        <div class="ds-activity-text"><strong>New taxpayer registered</strong> — Anitha R., Plot 42B</div>
                        <div class="ds-activity-time">1 hour ago</div>
                    </div>
                </div>

                <div class="ds-activity-item">
                    <div class="ds-activity-dot" style="background:rgba(239,68,68,0.1); color:#ef4444;">
                        <i class="bi bi-exclamation"></i>
                    </div>
                    <div>
                        <div class="ds-activity-text"><strong>Overdue escalated</strong> — ₹48,200 pending from Balan T. (90+ days)</div>
                        <div class="ds-activity-time">2 hours ago</div>
                    </div>
                </div>

                <div class="ds-activity-item">
                    <div class="ds-activity-dot" style="background:rgba(167,139,250,0.1); color:#7c3aed;">
                        <i class="bi bi-patch-check"></i>
                    </div>
                    <div>
                        <div class="ds-activity-text"><strong>NOC certificate issued</strong> — Premises 78/A, Anna Nagar</div>
                        <div class="ds-activity-time">3 hours ago</div>
                    </div>
                </div>

                <div class="ds-activity-item">
                    <div class="ds-activity-dot" style="background:rgba(16,185,129,0.1); color:#10b981;">
                        <i class="bi bi-check2"></i>
                    </div>
                    <div>
                        <div class="ds-activity-text"><strong>Batch payment processed</strong> — 32 online payments cleared (₹2.1L)</div>
                        <div class="ds-activity-time">Yesterday, 5:48 PM</div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

{{-- ── Row 4: Zone performance (FIXED) ── --}}
<div class="row g-3">
    <div class="col-12">
        <div class="ds-card">
            <div class="ds-card-head">
                <div class="ds-card-title">Zone-wise Collection Performance — {{ now()->format('F Y') }}</div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="ds-pill paid">On Track</span>
                    <a href="#" style="font-size:0.72rem; color:#10b981; text-decoration:none;">
                        Detailed View <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="ds-table">
                    <thead>
                        <tr>
                            <th>Zone</th>
                            <th>Officer</th>
                            <th>Target</th>
                            <th>Collected</th>
                            <th>Pending</th>
                            <th>Taxpayers</th>
                            <th>Achievement</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            // FIXED: Changed taxpayer counts from strings with commas to integers
                            $zones = [
                                ['North Zone','K. Selvakumar','₹1.2Cr','₹1.08Cr','₹12.4L',3241,90,'paid'],
                                ['South Zone','P. Ramachandran','₹0.9Cr','₹0.74Cr','₹16.1L',2876,82,'paid'],
                                ['East Zone','S. Meenakshi','₹1.0Cr','₹0.43Cr','₹57.2L',3102,43,'pending'],
                                ['West Zone','M. Arumugam','₹1.1Cr','₹0.98Cr','₹11.8L',2940,89,'paid'],
                                ['Central Zone','V. Vijayalakshmi','₹0.8Cr','₹0.59Cr','₹21.0L',2673,74,'pending'],
                            ];
                        @endphp
                        @foreach($zones as $z)
                        <tr>
                            <td style="font-weight:600; color:#0a2e1a;">{{ $z[0] }}</td>
                            <td style="color:#6b7280;">{{ $z[1] }}</td>
                            <td style="font-family:var(--font-mono);">{{ $z[2] }}</td>
                            <td style="font-family:var(--font-mono); font-weight:500; color:#059669;">{{ $z[3] }}</td>
                            <td style="font-family:var(--font-mono); color:#dc2626;">{{ $z[4] }}</td>
                            {{-- FIXED: Using number_format() on integer now instead of string with comma --}}
                            <td style="font-family:var(--font-mono); color:#374151;">{{ number_format($z[5]) }}</td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="flex:1; height:6px; background:#f3f4f6; border-radius:20px; overflow:hidden; min-width:60px;">
                                        <div style="height:100%; width:{{ $z[6] }}%; background:{{ $z[6] >= 80 ? '#10b981' : ($z[6] >= 60 ? '#fbbf24' : '#ef4444') }}; border-radius:20px;"></div>
                                    </div>
                                    <span style="font-family:var(--font-mono); font-size:0.72rem; color:#374151; width:32px;">{{ $z[6] }}%</span>
                                </div>
                            </td>
                            <td><span class="ds-pill {{ $z[7] }}">{{ $z[6] >= 80 ? 'on track' : ($z[6] >= 60 ? 'behind' : 'at risk') }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Animate bar fills on load
    document.addEventListener('DOMContentLoaded', function () {
        const bars = document.querySelectorAll('.ds-bar-fill[data-width]');
        bars.forEach(bar => {
            const w = bar.getAttribute('data-width');
            bar.style.width = '0%';
            setTimeout(() => { bar.style.width = w + '%'; }, 100);
        });
    });
</script>
@endpush

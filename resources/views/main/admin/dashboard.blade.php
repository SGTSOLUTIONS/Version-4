@extends('layouts.office')

@section('title', 'Dashboard — Revenue Department')
@section('page_title', 'Dashboard')

@push('styles')
<style>
/* ══════════════════════════════════════════════
   COLORFUL BRIGHT THEME — Revenue Dashboard
   ══════════════════════════════════════════════ */

/* ── Root Variables ── */
:root {
    --gradient-primary: linear-gradient(135deg, #6c5ce7, #a29bfe);
    --gradient-success: linear-gradient(135deg, #00d2d3, #55efc4);
    --gradient-warning: linear-gradient(135deg, #feca57, #fdcb6e);
    --gradient-danger: linear-gradient(135deg, #ff6b6b, #ff7675);
    --gradient-info: linear-gradient(135deg, #54a0ff, #74b9ff);
    --gradient-rainbow: linear-gradient(135deg, #ff6b6b, #feca57, #54a0ff, #00d2d3, #a29bfe);
    --shadow-colorful: 0 8px 32px rgba(108, 92, 231, 0.2);
}

/* ── Page Header ── */
.ol-page-header {
    background: linear-gradient(135deg, #faf0ff, #f0f7ff, #f0fff4);
    padding: 1.5rem 2rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    border: 2px solid #f0e8ff;
    box-shadow: 0 4px 20px rgba(157, 78, 221, 0.08);
}

.ol-page-title {
    font-size: 1.65rem;
    font-weight: 800;
    margin: 0;
    background: linear-gradient(135deg, #2d1b69, #6c5ce7, #a29bfe);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.ol-page-sub {
    color: #7c5cbf;
    margin: 4px 0 0;
    font-weight: 500;
}

.rv-submit {
    background: linear-gradient(135deg, #6c5ce7, #a29bfe);
    border: none;
    color: white !important;
    padding: 0 1.5rem;
    height: 38px;
    border-radius: 10px !important;
    font-weight: 700;
    font-size: 0.8rem !important;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.3s ease;
    text-decoration: none;
}

.rv-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba(108, 92, 231, 0.35);
    color: white !important;
}

/* ── Stat Cards ── */
.ds-stat {
    background: linear-gradient(145deg, #ffffff, #faf0ff);
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 2px 12px rgba(157, 78, 221, 0.08);
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    border: 2px solid #f0e8ff;
    position: relative;
    overflow: hidden;
    height: 100%;
}

.ds-stat::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--gradient-rainbow);
    background-size: 300% 100%;
    animation: shimmer 3s ease infinite;
}

@keyframes shimmer {
    0% { background-position: 0% 0%; }
    100% { background-position: 300% 0%; }
}

.ds-stat:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 8px 32px rgba(108, 92, 231, 0.15);
    border-color: #c4b5fd;
}

.ds-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    margin-bottom: 0.75rem;
}

.ds-stat-icon.green {
    background: rgba(0, 210, 211, 0.15);
    color: #00d2d3;
}

.ds-stat-icon.gold {
    background: rgba(254, 202, 87, 0.15);
    color: #feca57;
}

.ds-stat-icon.blue {
    background: rgba(84, 160, 255, 0.15);
    color: #54a0ff;
}

.ds-stat-icon.red {
    background: rgba(255, 107, 107, 0.15);
    color: #ff6b6b;
}

.ds-stat-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #7c5cbf;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ds-stat-value {
    font-size: 1.75rem;
    font-weight: 800;
    color: #2d1b69;
    margin: 4px 0 6px;
}

.ds-stat-change {
    font-size: 0.72rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 2px;
    padding: 2px 10px;
    border-radius: 20px;
}

.ds-stat-change.up {
    color: #00d2d3;
    background: rgba(0, 210, 211, 0.1);
}

.ds-stat-change.down {
    color: #ff6b6b;
    background: rgba(255, 107, 107, 0.1);
}

/* ── Cards ── */
.ds-card {
    background: linear-gradient(145deg, #ffffff, #faf0ff);
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 2px 12px rgba(157, 78, 221, 0.08);
    border: 2px solid #f0e8ff;
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.ds-card:hover {
    border-color: #c4b5fd;
    box-shadow: 0 8px 32px rgba(108, 92, 231, 0.12);
}

.ds-card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.ds-card-title {
    font-weight: 700;
    color: #2d1b69;
    font-size: 1rem;
}

/* ── Pills ── */
.ds-pill {
    font-size: 0.65rem;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.ds-pill.paid,
.ds-pill.on-track {
    background: linear-gradient(135deg, #55efc4, #00b894);
    color: white;
}

.ds-pill.pending {
    background: linear-gradient(135deg, #fdcb6e, #f39c12);
    color: white;
}

.ds-pill.overdue,
.ds-pill.at-risk {
    background: linear-gradient(135deg, #ff7675, #d63031);
    color: white;
}

.ds-pill.behind {
    background: linear-gradient(135deg, #fdcb6e, #f39c12);
    color: white;
}

/* ── Bar Chart ── */
.ds-bar-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
}

.ds-bar-label {
    font-size: 0.7rem;
    font-weight: 600;
    color: #7c5cbf;
    width: 32px;
    text-align: right;
}

.ds-bar-track {
    flex: 1;
    height: 8px;
    background: #f0e8ff;
    border-radius: 20px;
    overflow: hidden;
}

.ds-bar-fill {
    height: 100%;
    border-radius: 20px;
    transition: width 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
    background: linear-gradient(90deg, #6c5ce7, #a29bfe);
}

.ds-bar-fill.gold {
    background: linear-gradient(90deg, #feca57, #fdcb6e);
}

.ds-bar-fill.blue {
    background: linear-gradient(90deg, #54a0ff, #74b9ff);
}

.ds-bar-fill:not(.gold):not(.blue) {
    background: linear-gradient(90deg, #00d2d3, #55efc4);
}

.ds-bar-val {
    font-size: 0.65rem;
    font-weight: 600;
    color: #5a4a7a;
    font-family: monospace;
    width: 48px;
}

/* ── Quick Actions ── */
.ds-quick-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 0.5rem;
    border-radius: 12px;
    background: linear-gradient(145deg, #faf0ff, #ffffff);
    border: 2px solid #f0e8ff;
    color: #2d1b69;
    text-decoration: none;
    font-size: 0.72rem;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    gap: 6px;
    text-align: center;
}

.ds-quick-btn i {
    font-size: 1.3rem;
    background: var(--gradient-rainbow);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.ds-quick-btn:hover {
    transform: translateY(-3px) scale(1.05);
    border-color: #6c5ce7;
    box-shadow: 0 6px 24px rgba(108, 92, 231, 0.2);
    background: linear-gradient(145deg, #ffffff, #f0f7ff);
    color: #6c5ce7;
}

/* ── Progress Bars ── */
.ds-progress-wrap {
    margin-bottom: 12px;
}

.ds-progress-wrap:last-child {
    margin-bottom: 0;
}

.ds-progress-head {
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
    font-weight: 600;
    color: #2d1b69;
    margin-bottom: 4px;
}

.ds-progress-bar {
    height: 8px;
    background: #f0e8ff;
    border-radius: 20px;
    overflow: hidden;
}

.ds-progress-fill {
    height: 100%;
    border-radius: 20px;
    transition: width 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
}

/* ── Tables ── */
.ds-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8rem;
}

.ds-table th {
    text-align: left;
    padding: 0.6rem 0.75rem;
    font-weight: 700;
    color: #7c5cbf;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #f0e8ff;
}

.ds-table td {
    padding: 0.6rem 0.75rem;
    border-bottom: 1px solid #f5f0ff;
    color: #374151;
}

.ds-table tbody tr {
    transition: background 0.2s ease;
}

.ds-table tbody tr:hover {
    background: rgba(108, 92, 231, 0.04);
}

/* ── Activity Feed ── */
.ds-activity-item {
    display: flex;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f5f0ff;
}

.ds-activity-item:last-child {
    border-bottom: none;
}

.ds-activity-dot {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.ds-activity-text {
    font-size: 0.78rem;
    color: #374151;
    line-height: 1.4;
}

.ds-activity-text strong {
    color: #2d1b69;
}

.ds-activity-time {
    font-size: 0.65rem;
    color: #9ca3af;
    margin-top: 2px;
    font-weight: 500;
}

/* ── Responsive ── */
@media (max-width: 768px) {
    .ol-page-header {
        flex-direction: column;
        align-items: flex-start;
        padding: 1rem;
    }

    .ds-stat {
        padding: 1rem;
    }

    .ds-stat-value {
        font-size: 1.4rem;
    }

    .ds-card {
        padding: 1rem;
    }

    .ds-quick-btn {
        font-size: 0.65rem;
        padding: 0.5rem;
    }

    .ds-quick-btn i {
        font-size: 1.1rem;
    }
}

@media (max-width: 576px) {
    .col-sm-2-custom {
        flex: 0 0 50%;
        max-width: 50%;
    }
}
</style>
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
                            <td><span style="font-family:var(--font-mono); font-size:0.72rem; color:#6c5ce7;">{{ $txn[0] }}</span></td>
                            <td><span style="font-weight:600; color:#2d1b69;">{{ $txn[1] }}</span></td>
                            <td><span style="color:#7c5cbf;">{{ $txn[2] }}</span></td>
                            <td><span style="font-family:var(--font-mono); font-weight:500; color:#00b894;">{{ $txn[3] }}</span></td>
                            <td><span style="color:#9ca3af; font-size:0.72rem;">{{ $txn[4] }}</span></td>
                            <td><span class="ds-pill {{ $txn[5] }}">{{ $txn[5] }}</span></td>
                            <td>
                                <a href="#" style="color:#6c5ce7; font-size:13px; transition:0.2s;" title="View">
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
                    <div class="ds-activity-dot" style="background:rgba(0,210,211,0.15); color:#00d2d3;">
                        <i class="bi bi-check2"></i>
                    </div>
                    <div>
                        <div class="ds-activity-text"><strong>Payment collected</strong> — ₹12,400 from Murugan S. (Property Tax)</div>
                        <div class="ds-activity-time">2 minutes ago</div>
                    </div>
                </div>

                <div class="ds-activity-item">
                    <div class="ds-activity-dot" style="background:rgba(254,202,87,0.15); color:#feca57;">
                        <i class="bi bi-file-text"></i>
                    </div>
                    <div>
                        <div class="ds-activity-text"><strong>Demand notice issued</strong> — to 14 defaulters in Ward 7</div>
                        <div class="ds-activity-time">18 minutes ago</div>
                    </div>
                </div>

                <div class="ds-activity-item">
                    <div class="ds-activity-dot" style="background:rgba(84,160,255,0.15); color:#54a0ff;">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <div>
                        <div class="ds-activity-text"><strong>New taxpayer registered</strong> — Anitha R., Plot 42B</div>
                        <div class="ds-activity-time">1 hour ago</div>
                    </div>
                </div>

                <div class="ds-activity-item">
                    <div class="ds-activity-dot" style="background:rgba(255,107,107,0.15); color:#ff6b6b;">
                        <i class="bi bi-exclamation"></i>
                    </div>
                    <div>
                        <div class="ds-activity-text"><strong>Overdue escalated</strong> — ₹48,200 pending from Balan T. (90+ days)</div>
                        <div class="ds-activity-time">2 hours ago</div>
                    </div>
                </div>

                <div class="ds-activity-item">
                    <div class="ds-activity-dot" style="background:rgba(167,139,250,0.15); color:#a78bfa;">
                        <i class="bi bi-patch-check"></i>
                    </div>
                    <div>
                        <div class="ds-activity-text"><strong>NOC certificate issued</strong> — Premises 78/A, Anna Nagar</div>
                        <div class="ds-activity-time">3 hours ago</div>
                    </div>
                </div>

                <div class="ds-activity-item">
                    <div class="ds-activity-dot" style="background:rgba(0,210,211,0.15); color:#00d2d3;">
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

{{-- ── Row 4: Zone performance ── --}}
<div class="row g-3">
    <div class="col-12">
        <div class="ds-card">
            <div class="ds-card-head">
                <div class="ds-card-title">Zone-wise Collection Performance — {{ now()->format('F Y') }}</div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="ds-pill paid">On Track</span>
                    <a href="#" style="font-size:0.72rem; color:#6c5ce7; text-decoration:none; font-weight:600;">
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
                            <td style="font-weight:700; color:#2d1b69;">{{ $z[0] }}</td>
                            <td style="color:#7c5cbf;">{{ $z[1] }}</td>
                            <td style="font-family:var(--font-mono); color:#5a4a7a;">{{ $z[2] }}</td>
                            <td style="font-family:var(--font-mono); font-weight:600; color:#00d2d3;">{{ $z[3] }}</td>
                            <td style="font-family:var(--font-mono); color:#ff6b6b;">{{ $z[4] }}</td>
                            <td style="font-family:var(--font-mono); color:#6c5ce7;">{{ number_format($z[5]) }}</td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="flex:1; height:6px; background:#f0e8ff; border-radius:20px; overflow:hidden; min-width:60px;">
                                        <div style="height:100%; width:{{ $z[6] }}%; background:{{ $z[6] >= 80 ? '#00d2d3' : ($z[6] >= 60 ? '#feca57' : '#ff6b6b') }}; border-radius:20px;"></div>
                                    </div>
                                    <span style="font-family:var(--font-mono); font-size:0.72rem; font-weight:600; color:#2d1b69; width:32px;">{{ $z[6] }}%</span>
                                </div>
                            </td>
                            <td><span class="ds-pill {{ $z[6] >= 80 ? 'paid' : ($z[6] >= 60 ? 'pending' : 'overdue') }}">{{ $z[6] >= 80 ? 'on track' : ($z[6] >= 60 ? 'behind' : 'at risk') }}</span></td>
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

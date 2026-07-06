@extends('layouts.office')

@section('title', 'Commissioner Dashboard')
@section('page_title', 'Commissioner Dashboard')

@push('styles')
<style>
    /* ─── Font Variables ─── */
    :root {
        --font-mono: 'Courier New', monospace;
        --font-sans: 'Segoe UI', system-ui, -apple-system, sans-serif;
    }

    /* ─── Hierarchy Visualization ─── */
    .hierarchy-flow {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1.5rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        border-radius: 12px;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }
    .hierarchy-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: white;
        padding: 0.6rem 1.2rem;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        font-weight: 600;
        color: #0a2e1a;
        font-size: 0.9rem;
    }
    .hierarchy-item .count {
        background: #10b981;
        color: white;
        border-radius: 20px;
        padding: 0.1rem 0.7rem;
        font-size: 0.8rem;
        font-weight: 700;
    }
    .hierarchy-arrow {
        color: #10b981;
        font-size: 1.5rem;
        font-weight: 300;
    }

    /* ─── Stat Cards ─── */
    .comm-stat {
        background: white;
        border-radius: 12px;
        padding: 1.2rem 1.2rem 1rem 1.2rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        border: 1px solid rgba(0,0,0,0.04);
        transition: all 0.2s;
        height: 100%;
    }
    .comm-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    }
    .comm-stat .label {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #9ca3af;
        font-weight: 600;
    }
    .comm-stat .value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #0a2e1a;
        margin-top: 0.2rem;
        font-family: var(--font-mono);
    }
    .comm-stat .value.green { color: #10b981; }
    .comm-stat .value.red { color: #ef4444; }
    .comm-stat .value.blue { color: #3b82f6; }
    .comm-stat .value.gold { color: #f59e0b; }
    .comm-stat .value.purple { color: #8b5cf6; }
    .comm-stat .icon-wrap {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: white;
        flex-shrink: 0;
    }
    .comm-stat .icon-wrap.green { background: #10b981; }
    .comm-stat .icon-wrap.blue { background: #3b82f6; }
    .comm-stat .icon-wrap.gold { background: #f59e0b; }
    .comm-stat .icon-wrap.red { background: #ef4444; }
    .comm-stat .icon-wrap.purple { background: #8b5cf6; }
    .comm-stat .icon-wrap.teal { background: #14b8a6; }
    .comm-stat .icon-wrap.indigo { background: #6366f1; }
    .comm-stat .icon-wrap.pink { background: #ec4899; }
    .comm-stat .icon-wrap.orange { background: #f97316; }
    .comm-stat .icon-wrap.cyan { background: #06b6d4; }

    /* ─── Zone Cards ─── */
    .zone-card {
        background: white;
        border-radius: 12px;
        padding: 1.2rem;
        border: 1px solid rgba(0,0,0,0.04);
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        transition: all 0.2s;
        height: 100%;
    }
    .zone-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        border-color: #10b981;
    }
    .zone-card .zone-name {
        font-weight: 700;
        font-size: 1.05rem;
        color: #0a2e1a;
    }
    .zone-card .zone-officer {
        font-size: 0.78rem;
        color: #6b7280;
        margin-top: -0.1rem;
    }
    .zone-card .zone-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.4rem 1rem;
        margin-top: 0.7rem;
    }
    .zone-card .zone-stats span {
        font-size: 0.75rem;
        color: #6b7280;
    }
    .zone-card .zone-stats strong {
        color: #0a2e1a;
        font-weight: 600;
        font-family: var(--font-mono);
    }
    .zone-card .zone-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 0.7rem;
        padding-top: 0.7rem;
        border-top: 1px solid #f3f4f6;
    }
    .zone-card .zone-collection {
        font-weight: 700;
        color: #10b981;
        font-family: var(--font-mono);
        font-size: 1rem;
    }
    .zone-card .zone-pending {
        font-weight: 600;
        color: #ef4444;
        font-family: var(--font-mono);
        font-size: 0.85rem;
    }
    .zone-card .tax-tags {
        display: flex;
        gap: 0.3rem;
        flex-wrap: wrap;
        margin-top: 0.3rem;
    }
    .zone-card .tax-tag {
        font-size: 0.6rem;
        padding: 0.1rem 0.5rem;
        border-radius: 10px;
        background: #f3f4f6;
        color: #6b7280;
    }
    .tax-tag.water { background: #dbeafe; color: #2563eb; }
    .tax-tag.ugd { background: #fef3c7; color: #d97706; }
    .tax-tag.professional { background: #e0e7ff; color: #4f46e5; }

    /* ─── Tables ─── */
    .comm-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.82rem;
    }
    .comm-table thead th {
        background: #f9fafb;
        color: #6b7280;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.65rem;
        letter-spacing: 0.5px;
        padding: 0.7rem 0.8rem;
        border-bottom: 1px solid #e5e7eb;
        text-align: left;
    }
    .comm-table tbody td {
        padding: 0.65rem 0.8rem;
        border-bottom: 1px solid #f3f4f6;
        color: #1f2937;
    }
    .comm-table tbody tr:hover {
        background: #f9fafb;
    }
    .comm-table .badge-status {
        padding: 0.2rem 0.6rem;
        border-radius: 20px;
        font-size: 0.65rem;
        font-weight: 600;
        display: inline-block;
    }
    .badge-status.paid { background: #dcfce7; color: #059669; }
    .badge-status.pending { background: #fef3c7; color: #d97706; }
    .badge-status.overdue { background: #fee2e2; color: #dc2626; }
    .badge-status.active { background: #dbeafe; color: #2563eb; }

    .btn-view {
        color: #10b981;
        font-size: 0.85rem;
        padding: 0.2rem 0.6rem;
        border-radius: 6px;
        border: 1px solid rgba(16,185,129,0.2);
        background: rgba(16,185,129,0.05);
        transition: all 0.15s;
        text-decoration: none;
        display: inline-block;
        font-size: 0.7rem;
    }
    .btn-view:hover {
        background: #10b981;
        color: white;
        border-color: #10b981;
        text-decoration: none;
    }

    /* ─── Quick Actions ─── */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 0.6rem;
    }
    .quick-action-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 0.9rem;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        background: white;
        color: #1f2937;
        font-size: 0.78rem;
        font-weight: 500;
        transition: all 0.15s;
        text-decoration: none;
    }
    .quick-action-btn:hover {
        border-color: #10b981;
        background: #f0fdf4;
        color: #0a2e1a;
        text-decoration: none;
    }
    .quick-action-btn i {
        font-size: 1rem;
        color: #10b981;
    }

    /* ─── Performance Table ─── */
    .perf-bar {
        height: 5px;
        border-radius: 20px;
        background: #f3f4f6;
        overflow: hidden;
        min-width: 60px;
        flex: 1;
    }
    .perf-bar .fill {
        height: 100%;
        border-radius: 20px;
        transition: width 0.6s ease;
    }

    /* ─── Activity Items ─── */
    .activity-item {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        padding: 0.5rem 0.7rem;
        border-radius: 8px;
        background: #f9fafb;
        transition: background 0.15s;
    }
    .activity-item:hover {
        background: #f3f4f6;
    }
    .activity-item .act-icon {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .activity-item .act-text {
        font-size: 0.78rem;
        color: #1f2937;
        flex: 1;
    }
    .activity-item .act-text strong {
        color: #0a2e1a;
    }
    .activity-item .act-time {
        font-size: 0.65rem;
        color: #9ca3af;
        white-space: nowrap;
    }

    /* ─── Error State ─── */
    .error-state {
        text-align: center;
        padding: 3rem 1rem;
        background: white;
        border-radius: 12px;
        border: 1px solid #fee2e2;
        background: #fef2f2;
    }
    .error-state i {
        font-size: 3rem;
        color: #ef4444;
        margin-bottom: 1rem;
        display: block;
    }
    .error-state h5 {
        color: #991b1b;
        font-weight: 600;
    }
    .error-state p {
        color: #7f1d1d;
        opacity: 0.8;
    }

    /* ─── DS Card Override ─── */
    .ds-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        border: 1px solid rgba(0,0,0,0.04);
    }
    .ds-card-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.2rem;
        border-bottom: 1px solid #f3f4f6;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .ds-card-title {
        font-weight: 600;
        color: #0a2e1a;
        font-size: 0.95rem;
    }
    .ds-card-body {
        padding: 1.2rem;
    }

    .ds-pill {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        display: inline-block;
    }
    .ds-pill.paid {
        background: #dcfce7;
        color: #059669;
        border: 1px solid #10b98120;
    }

    .rv-submit {
        background: #10b981;
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .rv-submit:hover {
        background: #059669;
        color: white;
        text-decoration: none;
    }

    .ol-page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .ol-page-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #0a2e1a;
        margin: 0;
    }
    .ol-page-sub {
        color: #6b7280;
        margin: 0.2rem 0 0 0;
        font-size: 0.85rem;
    }

    /* ─── Tax Breakdown ─── */
    .tax-breakdown-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f3f4f6;
    }
    .tax-breakdown-item:last-child {
        border-bottom: none;
    }
    .tax-breakdown-item .tax-label {
        font-size: 0.78rem;
        color: #6b7280;
    }
    .tax-breakdown-item .tax-count {
        font-weight: 600;
        color: #0a2e1a;
        font-family: var(--font-mono);
        font-size: 0.9rem;
    }
    .tax-breakdown-item .tax-amount {
        font-weight: 600;
        color: #10b981;
        font-family: var(--font-mono);
        font-size: 0.9rem;
    }

    /* ─── Responsive ─── */
    @media (max-width: 768px) {
        .hierarchy-flow { gap: 0.6rem; padding: 0.8rem; }
        .hierarchy-item { font-size: 0.7rem; padding: 0.3rem 0.8rem; }
        .hierarchy-arrow { font-size: 1rem; }
        .comm-stat .value { font-size: 1.1rem; }
        .zone-card .zone-stats { grid-template-columns: 1fr; }
        .quick-actions { grid-template-columns: 1fr 1fr; }
        .ol-page-header { flex-direction: column; }
    }
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="ol-page-header">
    <div>
        <h1 class="ol-page-title">Commissioner Dashboard</h1>
        <p class="ol-page-sub">
            {{ isset($corporation) && $corporation ? $corporation->name . ' — Complete revenue hierarchy overview' : 'Revenue overview' }}
            — {{ now()->format('l, d F Y') }}
        </p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        @if(isset($corporation) && $corporation)
        <span style="font-size:0.7rem; padding:4px 12px; background:#f0fdf4; color:#10b981; border-radius:20px; border:1px solid #10b98120;">
            <i class="bi bi-building me-1"></i> {{ $corporation->name }}
        </span>
        @endif
        <span class="ds-pill paid" style="font-size:0.65rem; padding:4px 10px;">
            <i class="bi bi-circle-fill me-1" style="font-size:6px; vertical-align:1px;"></i>Live
        </span>
        <a href="#" class="rv-submit" style="width:auto; height:38px; padding:0 1.2rem; font-size:0.8rem !important; border-radius:9px !important; display:inline-flex; align-items:center; gap:6px; animation:none;">
            <i class="bi bi-download" style="font-size:13px;"></i>
            Export Report
        </a>
    </div>
</div>

@if(isset($error))
    <div class="error-state">
        <i class="bi bi-exclamation-triangle"></i>
        <h5>{{ $error }}</h5>
        <p>Please contact your administrator to assign a corporation to your account.</p>
    </div>
@else

{{-- ── Hierarchy Flow ── --}}
<div class="hierarchy-flow">
    <div class="hierarchy-item">
        <i class="bi bi-diagram-3" style="color:#10b981;"></i>
        Zones <span class="count">{{ $hierarchyStats['zones'] ?? 0 }}</span>
    </div>
    <span class="hierarchy-arrow">→</span>
    <div class="hierarchy-item">
        <i class="bi bi-grid-3x3-gap-fill" style="color:#3b82f6;"></i>
        Wards <span class="count">{{ $hierarchyStats['wards'] ?? 0 }}</span>
    </div>
    <span class="hierarchy-arrow">→</span>
    <div class="hierarchy-item">
        <i class="bi bi-building" style="color:#f59e0b;"></i>
        Buildings <span class="count">{{ isset($hierarchyStats['buildings']) ? number_format($hierarchyStats['buildings']) : '0' }}</span>
    </div>
    <span class="hierarchy-arrow">→</span>
    <div class="hierarchy-item">
        <i class="bi bi-clipboard-data" style="color:#8b5cf6;"></i>
        Assessments <span class="count">{{ isset($hierarchyStats['assessments']) ? number_format($hierarchyStats['assessments']) : '0' }}</span>
    </div>
    <span class="hierarchy-arrow">→</span>
    <div class="hierarchy-item">
        <i class="bi bi-check2-circle" style="color:#14b8a6;"></i>
        Surveyed <span class="count">{{ isset($hierarchyStats['surveyed']) ? number_format($hierarchyStats['surveyed']) : '0' }}</span>
    </div>
    <span class="hierarchy-arrow">→</span>
    <div class="hierarchy-item">
        <i class="bi bi-link-45deg" style="color:#8b5cf6;"></i>
        Connected <span class="count">{{ isset($hierarchyStats['connected']) ? number_format($hierarchyStats['connected']) : '0' }}</span>
    </div>
</div>

{{-- ── Top Statistics ── --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="comm-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="label">Total Zones</div>
                    <div class="value">{{ $stats['zones'] ?? 0 }}</div>
                </div>
                <div class="icon-wrap green"><i class="bi bi-diagram-3"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="comm-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="label">Total Wards</div>
                    <div class="value">{{ $stats['wards'] ?? 0 }}</div>
                </div>
                <div class="icon-wrap blue"><i class="bi bi-grid-3x3-gap-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="comm-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="label">Buildings</div>
                    <div class="value">{{ isset($stats['buildings']) ? number_format($stats['buildings']) : '0' }}</div>
                </div>
                <div class="icon-wrap gold"><i class="bi bi-building"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="comm-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="label">Total Assessments (MIS)</div>
                    <div class="value">{{ isset($stats['mis_count']) ? number_format($stats['mis_count']) : '0' }}</div>
                </div>
                <div class="icon-wrap purple"><i class="bi bi-clipboard-data"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="comm-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="label">Water Tax</div>
                    <div class="value">{{ isset($stats['water_tax_count']) ? number_format($stats['water_tax_count']) : '0' }}</div>
                </div>
                <div class="icon-wrap cyan"><i class="bi bi-droplet"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="comm-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="label">UGD Tax</div>
                    <div class="value">{{ isset($stats['ugd_count']) ? number_format($stats['ugd_count']) : '0' }}</div>
                </div>
                <div class="icon-wrap orange"><i class="bi bi-pipe"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="comm-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="label">Professional Tax</div>
                    <div class="value">{{ isset($stats['professional_tax_count']) ? number_format($stats['professional_tax_count']) : '0' }}</div>
                </div>
                <div class="icon-wrap indigo"><i class="bi bi-briefcase"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="comm-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="label">Property Owners</div>
                    <div class="value">{{ isset($stats['owners']) ? number_format($stats['owners']) : '0' }}</div>
                </div>
                <div class="icon-wrap teal"><i class="bi bi-people"></i></div>
            </div>
        </div>
    </div>
</div>

{{-- ── Assessment Status & Collection Stats ── --}}
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="comm-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="label">Active Assessments</div>
                    <div class="value blue">{{ isset($stats['active_assessments']) ? number_format($stats['active_assessments']) : '0' }} </div>
                </div>
                <div class="icon-wrap blue"><i class="bi bi-check-circle"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="comm-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="label">Not In Mis Assessments</div>
                    <div class="value gold">{{ isset($stats['notin_mis']) ? number_format($stats['notin_mis']) : '0' }}</div>
                </div>
                <div class="icon-wrap gold"><i class="bi bi-clock"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="comm-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="label">Overdue Assessments</div>
                    <div class="value red">{{ isset($stats['overdue_assessments']) ? number_format($stats['overdue_assessments']) : '0' }}</div>
                </div>
                <div class="icon-wrap red"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="comm-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="label">Paid Assessments</div>
                    <div class="value green">{{ isset($stats['paid_assessments']) ? number_format($stats['paid_assessments']) : '0' }}</div>
                </div>
                <div class="icon-wrap green"><i class="bi bi-check2-all"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="comm-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="label">Surveyed</div>
                    <div class="value purple">{{ isset($stats['surveyed']) ? number_format($stats['surveyed']) : '0' }}</div>
                </div>
                <div class="icon-wrap purple"><i class="bi bi-eye"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="comm-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="label">Connected</div>
                    <div class="value teal">{{ isset($stats['connected']) ? number_format($stats['connected']) : '0' }}</div>
                </div>
                <div class="icon-wrap teal"><i class="bi bi-link-45deg"></i></div>
            </div>
        </div>
    </div>
</div>

{{-- ── Collection Stats ── --}}
<div class="row g-3 mb-4">
    <div class="col-xl-4 col-lg-4 col-md-6">
        <div class="comm-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="label">Half Year Demand</div>
                    <div class="value green">{{ isset($stats['total_credits']) && $stats['total_credits'] ? '₹' . number_format($stats['total_credits']) : '₹0' }}</div>
                </div>
                <div class="icon-wrap green"><i class="bi bi-calendar-day"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-4 col-md-6">
        <div class="comm-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="label">Total Balance</div>
                    <div class="value blue">{{ isset($stats['half_year_balance']) && $stats['half_year_balance'] ? '₹' . number_format($stats['half_year_balance']) : '₹0' }}</div>
                </div>
                <div class="icon-wrap blue"><i class="bi bi-calendar-month"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-4 col-md-6">
        <div class="comm-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="label">Yearly Demand</div>
                    <div class="value gold">{{ isset($stats['year_collection']) && $stats['year_collection'] ? '₹' . number_format($stats['year_collection']) : '₹0' }}</div>
                </div>
                <div class="icon-wrap gold"><i class="bi bi-calendar-year"></i></div>
            </div>
        </div>
    </div>
</div>

{{-- ── Row: Quick Actions + Tax Breakdown ── --}}
<div class="row g-3 mb-4">
    <div class="col-xl-5">
        <div class="ds-card h-100">
            <div class="ds-card-head">
                <div class="ds-card-title">Commissioner Quick Actions</div>
            </div>
            <div class="ds-card-body">
                <div class="quick-actions">
                    <a href="{{ route('commissioner.map') ?? '#' }}" class="quick-action-btn"><i class="bi bi-map"></i> View Map</a>
                    <a href="#" class="quick-action-btn"><i class="bi bi-file-spreadsheet"></i> Collection Report</a>
                    <a href="#" class="quick-action-btn"><i class="bi bi-exclamation-triangle"></i> Pending Report</a>
                    <a href="#" class="quick-action-btn"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
                    <a href="#" class="quick-action-btn"><i class="bi bi-printer"></i> Print Report</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="ds-card h-100">
            <div class="ds-card-head">
                <div class="ds-card-title"><i class="bi bi-pie-chart me-2"></i>Tax Breakdown</div>
                <span style="font-size:0.68rem; color:#9ca3af;">{{ now()->format('F Y') }}</span>
            </div>
            <div class="ds-card-body">
                @if(isset($taxBreakdown) && count($taxBreakdown) > 0)
                    @foreach($taxBreakdown as $key => $tax)
                        <div class="tax-breakdown-item">
                            <div>
                                <span class="tax-label">
                                    @php
                                        $icons = [
                                            'mis' => '📊',
                                            'water_tax' => '💧',
                                            'ugd' => '🔧',
                                            'professional_tax' => '💼'
                                        ];
                                        $labels = [
                                            'mis' => 'MIS Assessment',
                                            'water_tax' => 'Water Tax',
                                            'ugd' => 'UGD Tax',
                                            'professional_tax' => 'Professional Tax'
                                        ];
                                    @endphp
                                    {{ $icons[$key] ?? '📋' }} {{ $labels[$key] ?? ucfirst($key) }}
                                </span>
                            </div>
                            <div class="d-flex gap-3 align-items-center">
                                <span class="tax-count">{{ number_format($tax['count']) }}</span>
                                <span class="tax-amount">{{ $tax['collection'] ? '₹' . number_format($tax['collection']) : '₹0' }}</span>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-3 text-muted">No tax data available</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Zone Performance ── --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="ds-card">
            <div class="ds-card-head">
                <div class="ds-card-title"><i class="bi bi-graph-up me-2" style="color:#10b981;"></i>Zone-wise Collection Performance</div>
                <span style="font-size:0.68rem; color:#9ca3af;">{{ now()->format('F Y') }}</span>
            </div>
            <div class="ds-card-body" style="overflow-x:auto;">
                <table class="comm-table">
                    <thead>
                        <tr>
                            <th>Zone</th>
                            <th>Target</th>
                            <th>Collected</th>
                            <th>Pending</th>
                            <th>Achievement</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($performanceZones ?? [] as $zone)
                        <tr>
                            <td style="font-weight:600; color:#0a2e1a;">{{ $zone['name'] }}</td>
                            <td style="font-family:var(--font-mono); color:#6b7280;">{{ $zone['target'] }}</td>
                            <td style="font-family:var(--font-mono); font-weight:600; color:#059669;">{{ $zone['collected'] }}</td>
                            <td style="font-family:var(--font-mono); color:#dc2626;">{{ $zone['pending'] }}</td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div class="perf-bar">
                                        <div class="fill" style="width:{{ $zone['achievement'] }}%; background:{{ $zone['achievement'] >= 80 ? '#10b981' : ($zone['achievement'] >= 60 ? '#f59e0b' : '#ef4444') }};"></div>
                                    </div>
                                    <span style="font-family:var(--font-mono); font-size:0.75rem; min-width:36px; color:#374151;">{{ $zone['achievement'] }}%</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-3 text-muted">No zones found for this corporation</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ── Zone Cards ── --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0" style="color:#0a2e1a;"><i class="bi bi-diagram-3 me-2" style="color:#10b981;"></i>Zone Overview</h6>
            <a href="{{ route('admin.zones.index') }}" style="font-size:0.78rem; color:#10b981; text-decoration:none;">View All Zones <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-3">
            @forelse($zoneData ?? [] as $zone)
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="zone-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="zone-name">{{ $zone['name'] }}</div>
                            <div class="zone-officer"><i class="bi bi-person-badge me-1"></i>{{ $zone['officer'] }}</div>
                        </div>
                        <span style="font-size:1.2rem; color:#10b981;"><i class="bi bi-building"></i></span>
                    </div>
                    <div class="zone-stats">
                        <span><i class="bi bi-grid-3x3-gap-fill me-1" style="color:#3b82f6;"></i>Wards: <strong>{{ $zone['wards'] }}</strong></span>
                        <span><i class="bi bi-building me-1" style="color:#f59e0b;"></i>Buildings: <strong>{{ number_format($zone['buildings']) }}</strong></span>
                        <span><i class="bi bi-clipboard-data me-1" style="color:#8b5cf6;"></i>Assessments: <strong>{{ number_format($zone['assessments']) }}</strong></span>
                        <span><i class="bi bi-eye me-1" style="color:#14b8a6;"></i>Surveyed: <strong>{{ number_format($zone['surveyed']) }}</strong></span>
                    </div>
                    <div class="tax-tags">
                        <span class="tax-tag water"><i class="bi bi-droplet me-1"></i>{{ $zone['water_tax'] ?? 0 }}</span>
                        <span class="tax-tag ugd"><i class="bi bi-pipe me-1"></i>{{ $zone['ugd'] ?? 0 }}</span>
                        <span class="tax-tag professional"><i class="bi bi-briefcase me-1"></i>{{ $zone['professional_tax'] ?? 0 }}</span>
                    </div>
                    <div class="zone-footer">
                        <span class="zone-collection">{{ $zone['collection'] }}</span>
                        <span class="zone-pending"><i class="bi bi-clock"></i> {{ $zone['pending'] }} pending</span>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-diagram-3 fs-2 d-block mb-2"></i>
                    No zones found for this corporation
                </div>
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- ── Tax Tables ── --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="ds-card">
            <div class="ds-card-head">
                <div class="ds-card-title"><i class="bi bi-droplet me-2" style="color:#2563eb;"></i>Water Tax</div>
                <a href="#" style="font-size:0.7rem; color:#10b981; text-decoration:none;">View All</a>
            </div>
            <div style="overflow-x:auto;">
                <table class="comm-table">
                    <thead>
                        <tr>
                            <th>Assessment No</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($waterTaxData ?? [] as $item)
                        <tr>
                            <td style="font-weight:600; font-size:0.75rem;">{{ $item['no'] }}</td>
                            <td style="font-family:var(--font-mono); font-size:0.8rem;">{{ $item['amount'] }}</td>
                            <td><span class="badge-status {{ $item['status'] }}">{{ ucfirst($item['status']) }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center py-2 text-muted" style="font-size:0.75rem;">No data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="ds-card">
            <div class="ds-card-head">
                <div class="ds-card-title"><i class="bi bi-pipe me-2" style="color:#d97706;"></i>UGD Tax</div>
                <a href="#" style="font-size:0.7rem; color:#10b981; text-decoration:none;">View All</a>
            </div>
            <div style="overflow-x:auto;">
                <table class="comm-table">
                    <thead>
                        <tr>
                            <th>Assessment No</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ugdData ?? [] as $item)
                        <tr>
                            <td style="font-weight:600; font-size:0.75rem;">{{ $item['no'] }}</td>
                            <td style="font-family:var(--font-mono); font-size:0.8rem;">{{ $item['amount'] }}</td>
                            <td><span class="badge-status {{ $item['status'] }}">{{ ucfirst($item['status']) }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center py-2 text-muted" style="font-size:0.75rem;">No data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="ds-card">
            <div class="ds-card-head">
                <div class="ds-card-title"><i class="bi bi-briefcase me-2" style="color:#4f46e5;"></i>Professional Tax</div>
                <a href="#" style="font-size:0.7rem; color:#10b981; text-decoration:none;">View All</a>
            </div>
            <div style="overflow-x:auto;">
                <table class="comm-table">
                    <thead>
                        <tr>
                            <th>Assessment No</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($professionalTaxData ?? [] as $item)
                        <tr>
                            <td style="font-weight:600; font-size:0.75rem;">{{ $item['no'] }}</td>
                            <td style="font-family:var(--font-mono); font-size:0.8rem;">{{ $item['amount'] }}</td>
                            <td><span class="badge-status {{ $item['status'] }}">{{ ucfirst($item['status']) }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center py-2 text-muted" style="font-size:0.75rem;">No data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ── Recent Activities ── --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="ds-card">
            <div class="ds-card-head">
                <div class="ds-card-title"><i class="bi bi-activity me-2" style="color:#10b981;"></i>Recent Activities</div>
                <span style="font-size:0.68rem; color:#9ca3af;">Live</span>
            </div>
            <div class="ds-card-body">
                <div class="row g-2">
                    @forelse($activities ?? [] as $activity)
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="activity-item">
                            <div class="act-icon" style="background:{{ $activity['color'] }}15; color:{{ $activity['color'] }};">
                                <i class="bi bi-{{ $activity['icon'] }}"></i>
                            </div>
                            <div class="act-text">{!! $activity['text'] !!}</div>
                            <div class="act-time">{{ $activity['time'] }}</div>
                        </div>
                    </div>
                    @empty
                    <div class="col-12">
                        <div class="text-center py-3 text-muted">No recent activities</div>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@endif

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const bars = document.querySelectorAll('.perf-bar .fill');
        bars.forEach(bar => {
            const w = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.width = w;
            }, 200);
        });
    });
</script>
<script>
    const allwardBoundary = @json($getAllwardBoundary);

      console.log(allwardBoundary);
</script>
@endpush

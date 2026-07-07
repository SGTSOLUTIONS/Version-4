@extends('layouts.office')

@section('title', 'Commissioner Dashboard')
@section('page_title', 'Commissioner Dashboard')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@700;900&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap"
        rel="stylesheet">
    <style>
        /* ══════════════════════════════════════════════
               DESIGN TOKENS — Government Revenue Portal
               ══════════════════════════════════════════════ */
        :root {
            --font-display: 'Merriweather', Georgia, serif;
            --font-body: 'Inter', 'Segoe UI', system-ui, sans-serif;
            --font-mono: 'JetBrains Mono', 'Courier New', monospace;

            --gov-green: #0f6b47;
            --gov-green-dark: #0a4530;
            --gov-green-tint: #eaf4ef;
            --gov-gold: #a9741a;
            --gov-gold-tint: #faf3e6;

            --ink-900: #0e2019;
            --ink-700: #38473f;
            --ink-500: #6b7972;
            --ink-300: #c7d0cb;

            --bg-page: #f2f4f3;
            --surface: #ffffff;
            --border: #dfe4e1;
            --border-strong: #c7d0cb;

            --status-blue: #1d4ed8;
            --status-red: #b91c1c;
            --status-gold: #a9741a;
            --status-purple: #5b21b6;
            --status-teal: #0e7c72;
        }

        body {
            background: var(--bg-page);
        }

        /* ══════════════════════════════════════════════
               OFFICIAL LETTERHEAD BAR
               ══════════════════════════════════════════════ */
        .gov-letterhead {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            background: linear-gradient(180deg, var(--gov-green-dark), var(--gov-green));
            color: #fff;
            border-radius: 10px;
            padding: 1rem 1.4rem;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
        }

        .gov-letterhead .identity {
            display: flex;
            align-items: center;
            gap: 0.9rem;
        }

        .gov-letterhead .seal {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.55);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .gov-letterhead .org-name {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.05rem;
            line-height: 1.2;
        }

        .gov-letterhead .org-sub {
            font-family: var(--font-body);
            font-size: 0.72rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.75);
            margin-top: 0.1rem;
        }

        .gov-letterhead .meta {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-family: var(--font-body);
        }

        .gov-status-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .gov-status-chip .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #6ee7b7;
            box-shadow: 0 0 0 3px rgba(110, 231, 183, 0.25);
        }

        .gov-export-btn {
            background: #fff;
            color: var(--gov-green-dark);
            border: none;
            padding: 0.5rem 1.1rem;
            border-radius: 7px;
            font-weight: 600;
            font-size: 0.78rem;
            font-family: var(--font-body);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .gov-export-btn:hover {
            background: var(--gov-gold-tint);
            color: var(--gov-green-dark);
        }

        /* ══════════════════════════════════════════════
               BREADCRUMB + PAGE TITLE
               ══════════════════════════════════════════════ */
        .gov-breadcrumb {
            font-family: var(--font-body);
            font-size: 0.72rem;
            color: var(--ink-500);
            margin-bottom: 0.4rem;
            letter-spacing: 0.02em;
        }

        .gov-breadcrumb a {
            color: var(--ink-500);
            text-decoration: none;
        }

        .gov-breadcrumb .sep {
            margin: 0 0.35rem;
            opacity: 0.5;
        }

        .gov-breadcrumb .current {
            color: var(--gov-green-dark);
            font-weight: 600;
        }

        .gov-page-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.4rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gov-green);
        }

        .gov-page-title {
            font-family: var(--font-display);
            font-size: 1.6rem;
            font-weight: 900;
            color: var(--ink-900);
            margin: 0;
        }

        .gov-page-sub {
            font-family: var(--font-body);
            color: var(--ink-500);
            margin: 0.15rem 0 0 0;
            font-size: 0.85rem;
        }

        .gov-page-date {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: var(--ink-500);
            text-align: right;
        }

        /* ══════════════════════════════════════════════
               SECTION EYEBROWS (structural labels, not decoration)
               ══════════════════════════════════════════════ */
        .gov-section {
            margin-bottom: 1.75rem;
        }

        .gov-eyebrow {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 0.7rem;
        }

        .gov-eyebrow .label {
            font-family: var(--font-body);
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--gov-green-dark);
            white-space: nowrap;
        }

        .gov-eyebrow .rule {
            flex: 1;
            height: 1px;
            background: var(--border-strong);
        }

        .gov-eyebrow .rule.gold {
            background: linear-gradient(90deg, var(--gov-gold), transparent);
        }

        /* ══════════════════════════════════════════════
               HIERARCHY LEDGER (numbered process — genuinely sequential)
               ══════════════════════════════════════════════ */
        .ledger-flow {
            display: flex;
            align-items: stretch;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
        }

        .ledger-step {
            flex: 1;
            min-width: 130px;
            padding: 0.9rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            position: relative;
            border-right: 1px solid var(--border);
        }

        .ledger-step:last-child {
            border-right: none;
        }

        .ledger-step .step-no {
            font-family: var(--font-mono);
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--gov-gold);
            letter-spacing: 0.05em;
        }

        .ledger-step .step-label {
            font-family: var(--font-body);
            font-size: 0.72rem;
            color: var(--ink-500);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .ledger-step .step-value {
            font-family: var(--font-mono);
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--ink-900);
        }

        .ledger-step::after {
            content: '';
            position: absolute;
            right: -1px;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--gov-green);
            display: none;
        }

        /* ══════════════════════════════════════════════
               KPI CARDS — flat, official, minimal shadow
               ══════════════════════════════════════════════ */
        .kpi-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-left: 3px solid var(--gov-green);
            border-radius: 8px;
            padding: 0.95rem 1.1rem;
            height: 100%;
        }

        .kpi-card.accent-blue {
            border-left-color: var(--status-blue);
        }

        .kpi-card.accent-gold {
            border-left-color: var(--status-gold);
        }

        .kpi-card.accent-red {
            border-left-color: var(--status-red);
        }

        .kpi-card.accent-purple {
            border-left-color: var(--status-purple);
        }

        .kpi-card.accent-teal {
            border-left-color: var(--status-teal);
        }

        .kpi-card .kpi-label {
            font-family: var(--font-body);
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--ink-500);
            font-weight: 600;
        }

        .kpi-card .kpi-value {
            font-family: var(--font-mono);
            font-size: 1.45rem;
            font-weight: 700;
            color: var(--ink-900);
            margin-top: 0.15rem;
        }

        .kpi-card .kpi-icon {
            float: right;
            width: 32px;
            height: 32px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gov-green-tint);
            color: var(--gov-green);
            font-size: 1rem;
        }

        /* ══════════════════════════════════════════════
               OFFICIAL CARD (containers for tables / lists)
               ══════════════════════════════════════════════ */
        .gov-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
        }

        .gov-card-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.9rem 1.15rem;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 0.5rem;
            background: linear-gradient(180deg, #fafbfa, var(--surface));
            border-radius: 10px 10px 0 0;
        }

        .gov-card-title {
            font-family: var(--font-body);
            font-weight: 700;
            color: var(--ink-900);
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .gov-card-title i {
            color: var(--gov-green);
        }

        .gov-card-meta {
            font-family: var(--font-mono);
            font-size: 0.66rem;
            color: var(--ink-500);
        }

        .gov-card-link {
            font-family: var(--font-body);
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--gov-green);
            text-decoration: none;
        }

        .gov-card-body {
            padding: 1.15rem;
        }

        /* ══════════════════════════════════════════════
               TABLES — ledger style
               ══════════════════════════════════════════════ */
        .gov-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
            font-family: var(--font-body);
        }

        .gov-table thead th {
            background: var(--gov-green-tint);
            color: var(--gov-green-dark);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.63rem;
            letter-spacing: 0.05em;
            padding: 0.6rem 0.85rem;
            border-bottom: 1px solid var(--border-strong);
            text-align: left;
        }

        .gov-table tbody td {
            padding: 0.6rem 0.85rem;
            border-bottom: 1px solid var(--border);
            color: var(--ink-700);
        }

        .gov-table tbody tr:last-child td {
            border-bottom: none;
        }

        .gov-table tbody tr:hover {
            background: #fafbfa;
        }

        .gov-table .mono {
            font-family: var(--font-mono);
        }

        .gov-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-size: 0.63rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            display: inline-block;
            border: 1px solid transparent;
        }

        .gov-badge.paid {
            background: #eaf6f0;
            color: #0a7a4a;
            border-color: #cbe9d8;
        }

        .gov-badge.pending {
            background: var(--gov-gold-tint);
            color: var(--gov-gold);
            border-color: #f0dfb8;
        }

        .gov-badge.overdue {
            background: #fdecec;
            color: var(--status-red);
            border-color: #f7cfcf;
        }

        .gov-badge.active {
            background: #eaf0fd;
            color: var(--status-blue);
            border-color: #cddbf9;
        }

        /* ══════════════════════════════════════════════
               ZONE REGISTER CARDS
               ══════════════════════════════════════════════ */
        .zone-register {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1rem 1.1rem;
            height: 100%;
            position: relative;
        }

        .zone-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--gov-green);
            border-radius: 8px 8px 0 0;
        }

        .zone-register .zone-name {
            font-family: var(--font-body);
            font-weight: 700;
            font-size: 1rem;
            color: var(--ink-900);
        }

        .zone-register .zone-officer {
            font-size: 0.74rem;
            color: var(--ink-500);
            margin-top: 0.1rem;
        }

        .zone-register .zone-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.35rem 0.8rem;
            margin: 0.75rem 0;
            padding: 0.6rem 0;
            border-top: 1px dashed var(--border-strong);
            border-bottom: 1px dashed var(--border-strong);
        }

        .zone-register .zone-stats span {
            font-size: 0.72rem;
            color: var(--ink-500);
        }

        .zone-register .zone-stats strong {
            color: var(--ink-900);
            font-family: var(--font-mono);
            font-weight: 700;
        }

        .zone-register .tax-tags {
            display: flex;
            gap: 0.3rem;
            flex-wrap: wrap;
            margin-bottom: 0.6rem;
        }

        .zone-register .tax-tag {
            font-size: 0.62rem;
            font-weight: 600;
            padding: 0.12rem 0.5rem;
            border-radius: 4px;
            border: 1px solid var(--border);
            color: var(--ink-500);
        }

        .zone-register .zone-footer {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }

        .zone-register .zone-collection {
            font-family: var(--font-mono);
            font-weight: 700;
            color: var(--gov-green);
            font-size: 1rem;
        }

        .zone-register .zone-pending {
            font-family: var(--font-mono);
            font-weight: 600;
            color: var(--status-red);
            font-size: 0.78rem;
        }

        /* ══════════════════════════════════════════════
               ACTION REGISTRY (quick actions)
               ══════════════════════════════════════════════ */
        .action-registry {
            display: flex;
            flex-direction: column;
        }

        .action-row {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.7rem 0.2rem;
            border-bottom: 1px solid var(--border);
            text-decoration: none;
            color: var(--ink-700);
            font-family: var(--font-body);
            font-size: 0.82rem;
            font-weight: 500;
        }

        .action-row:last-child {
            border-bottom: none;
        }

        .action-row:hover {
            color: var(--gov-green-dark);
            background: #fafbfa;
        }

        .action-row .num {
            font-family: var(--font-mono);
            font-size: 0.68rem;
            color: var(--gov-gold);
            width: 22px;
        }

        .action-row i {
            color: var(--gov-green);
            font-size: 0.95rem;
            width: 18px;
            text-align: center;
        }

        .action-row .arrow {
            margin-left: auto;
            color: var(--ink-300);
        }

        /* ══════════════════════════════════════════════
               PERFORMANCE BAR
               ══════════════════════════════════════════════ */
        .gov-perf-bar {
            height: 6px;
            border-radius: 3px;
            background: var(--border);
            overflow: hidden;
            min-width: 70px;
            flex: 1;
        }

        .gov-perf-bar .fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.6s ease;
        }

        /* ══════════════════════════════════════════════
               ACTIVITY LOG
               ══════════════════════════════════════════════ */
        .log-entry {
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
            padding: 0.65rem 0;
            border-bottom: 1px solid var(--border);
        }

        .log-entry:last-child {
            border-bottom: none;
        }

        .log-entry .log-icon {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.75rem;
        }

        .log-entry .log-text {
            font-size: 0.78rem;
            color: var(--ink-700);
            flex: 1;
            font-family: var(--font-body);
        }

        .log-entry .log-text strong {
            color: var(--ink-900);
        }

        .log-entry .log-time {
            font-size: 0.65rem;
            color: var(--ink-500);
            font-family: var(--font-mono);
            white-space: nowrap;
        }

        /* ══════════════════════════════════════════════
               ERROR STATE
               ══════════════════════════════════════════════ */
        .gov-error {
            text-align: center;
            padding: 3rem 1rem;
            background: #fdecec;
            border: 1px solid #f7cfcf;
            border-radius: 10px;
        }

        .gov-error i {
            font-size: 2.6rem;
            color: var(--status-red);
            margin-bottom: 1rem;
            display: block;
        }

        .gov-error h5 {
            color: #7a1414;
            font-weight: 700;
            font-family: var(--font-body);
        }

        .gov-error p {
            color: #7a1414;
            opacity: 0.85;
            font-family: var(--font-body);
        }

        /* ══════════════════════════════════════════════
               RESPONSIVE
               ══════════════════════════════════════════════ */
        @media (max-width: 768px) {
            .ledger-flow {
                flex-direction: column;
            }

            .ledger-step {
                border-right: none;
                border-bottom: 1px solid var(--border);
            }

            .gov-page-head {
                flex-direction: column;
                align-items: flex-start;
            }

            .gov-page-date {
                text-align: left;
            }
        }
    </style>
@endpush

@section('content')

    {{-- ══════════════════════════ OFFICIAL LETTERHEAD ══════════════════════════ --}}
    <div class="gov-letterhead">
        <div class="identity">
            <div class="seal"><i class="bi bi-shield-check"></i></div>
            <div>
                <div class="org-name">
                    {{ isset($corporation) && $corporation ? $corporation->name : 'Municipal Corporation' }}</div>
                <div class="org-sub">Revenue &amp; Assessment Management Portal</div>
            </div>
        </div>
        <div class="meta">
            <span class="gov-status-chip"><span class="dot"></span> System Live</span>
            <a href="#" class="gov-export-btn"><i class="bi bi-file-earmark-arrow-down"></i> Export Report</a>
        </div>
    </div>

    @if (isset($error))
        <div class="gov-error">
            <i class="bi bi-exclamation-triangle"></i>
            <h5>{{ $error }}</h5>
            <p>Please contact your administrator to assign a corporation to your account.</p>
        </div>
    @else
        {{-- ══════════════════════════ BREADCRUMB + TITLE ══════════════════════════ --}}
        <div class="gov-breadcrumb">
            <a href="#">Home</a><span class="sep">/</span><a href="#">Office</a><span
                class="sep">/</span><span class="current">Commissioner Dashboard</span>
        </div>
        <div class="gov-page-head">
            <div>
                <h1 class="gov-page-title">Executive Dashboard</h1>
                <p class="gov-page-sub">Consolidated view of zones, wards, assessments and revenue collection</p>
            </div>
            <div class="gov-page-date">
                Report generated<br>{{ now()->format('d M Y, h:i A') }}
            </div>
        </div>

        {{-- ══════════════════════════ ADMINISTRATIVE HIERARCHY LEDGER ══════════════════════════ --}}
        <div class="gov-section">
            <div class="gov-eyebrow">
                <span class="label">Administrative Hierarchy</span>
                <span class="rule"></span>
            </div>
            <div class="ledger-flow">
                <div class="ledger-step">
                    <span class="step-no">01 — JURISDICTION</span>
                    <span class="step-label">Zones</span>
                    <span class="step-value">{{ $hierarchyStats['zones'] ?? 0 }}</span>
                </div>
                <div class="ledger-step">
                    <span class="step-no">02 — JURISDICTION</span>
                    <span class="step-label">Wards</span>
                    <span class="step-value">{{ $hierarchyStats['wards'] ?? 0 }}</span>
                </div>
                <div class="ledger-step">
                    <span class="step-no">03 — INVENTORY</span>
                    <span class="step-label">Buildings</span>
                    <span
                        class="step-value">{{ isset($hierarchyStats['buildings']) ? number_format($hierarchyStats['buildings']) : '0' }}</span>
                </div>
                <div class="ledger-step">
                    <span class="step-no">04 — REGISTER</span>
                    <span class="step-label">Assessments</span>
                    <span
                        class="step-value">{{ isset($hierarchyStats['assessments']) ? number_format($hierarchyStats['assessments']) : '0' }}</span>
                </div>
                <div class="ledger-step">
                    <span class="step-no">05 — VERIFICATION</span>
                    <span class="step-label">Surveyed</span>
                    <span
                        class="step-value">{{ isset($hierarchyStats['surveyed']) ? number_format($hierarchyStats['surveyed']) : '0' }}</span>
                </div>
                <div class="ledger-step">
                    <span class="step-no">06 — VERIFICATION</span>
                    <span class="step-label">Connected</span>
                    <span
                        class="step-value">{{ isset($hierarchyStats['connected']) ? number_format($hierarchyStats['connected']) : '0' }}</span>
                </div>
            </div>
        </div>
        {{-- ══════════════════════════ WARD BOUNDARY MAP ══════════════════════════ --}}
        <div class="gov-section">
            <div class="gov-card">
                <div class="gov-card-head">
                    <div class="gov-card-title"><i class="bi bi-map"></i> Ward Boundaries</div>
                    <span class="gov-card-meta">{{ $hierarchyStats['wards'] ?? 0 }} wards mapped</span>
                </div>
                <div class="gov-card-body">
                    <div id="wardMap"
                        style="width:100%; height:460px; border-radius:6px; border:1px solid var(--border);"></div>
                </div>
            </div>
        </div>
        {{-- ══════════════════════════ REVENUE SNAPSHOT ══════════════════════════ --}}
        <div class="gov-section">
            <div class="gov-eyebrow">
                <span class="label">Revenue Snapshot</span>
                <span class="rule gold"></span>
            </div>
            <div class="row g-3">
                <div class="col-xl-4 col-md-6">
                    <div class="kpi-card">
                        <div class="kpi-icon"><i class="bi bi-calendar-day"></i></div>
                        <div class="kpi-label">Half Year Demand</div>
                        <div class="kpi-value">
                            {{ isset($stats['total_credits']) && $stats['total_credits'] ? '₹' . number_format($stats['total_credits']) : '₹0' }}
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="kpi-card accent-blue">
                        <div class="kpi-icon" style="background:#eaf0fd; color:var(--status-blue);"><i
                                class="bi bi-wallet2"></i></div>
                        <div class="kpi-label">Total Balance</div>
                        <div class="kpi-value">
                            {{ isset($stats['half_year_balance']) && $stats['half_year_balance'] ? '₹' . number_format($stats['half_year_balance']) : '₹0' }}
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="kpi-card accent-gold">
                        <div class="kpi-icon" style="background:var(--gov-gold-tint); color:var(--gov-gold);"><i
                                class="bi bi-graph-up-arrow"></i></div>
                        <div class="kpi-label">Yearly Demand</div>
                        <div class="kpi-value">
                            {{ isset($stats['year_collection']) && $stats['year_collection'] ? '₹' . number_format($stats['year_collection']) : '₹0' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════ ASSESSMENT LEDGER (tax + status counts) ══════════════════════════ --}}
        <div class="gov-section">
            <div class="gov-eyebrow">
                <span class="label">Assessment Ledger</span>
                <span class="rule"></span>
            </div>
            <div class="row g-3">
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card">
                        <div class="kpi-icon"><i class="bi bi-clipboard-data"></i></div>
                        <div class="kpi-label">Total Assessments (MIS)</div>
                        <div class="kpi-value">{{ isset($stats['mis_count']) ? number_format($stats['mis_count']) : '0' }}
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card accent-blue">
                        <div class="kpi-icon" style="background:#eaf0fd; color:var(--status-blue);"><i
                                class="bi bi-check-circle"></i></div>
                        <div class="kpi-label">Active Assessments</div>
                        <div class="kpi-value">
                            {{ isset($stats['active_assessments']) ? number_format($stats['active_assessments']) : '0' }}
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card accent-gold">
                        <div class="kpi-icon" style="background:var(--gov-gold-tint); color:var(--gov-gold);"><i
                                class="bi bi-hourglass-split"></i></div>
                        <div class="kpi-label">Not in MIS</div>
                        <div class="kpi-value">{{ isset($stats['notin_mis']) ? number_format($stats['notin_mis']) : '0' }}
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card accent-red">
                        <div class="kpi-icon" style="background:#fdecec; color:var(--status-red);"><i
                                class="bi bi-exclamation-triangle"></i></div>
                        <div class="kpi-label">Overdue Assessments</div>
                        <div class="kpi-value">
                            {{ isset($stats['overdue_assessments']) ? number_format($stats['overdue_assessments']) : '0' }}
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card">
                        <div class="kpi-icon"><i class="bi bi-check2-all"></i></div>
                        <div class="kpi-label">Paid Assessments</div>
                        <div class="kpi-value">
                            {{ isset($stats['paid_assessments']) ? number_format($stats['paid_assessments']) : '0' }}</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card accent-purple">
                        <div class="kpi-icon" style="background:#f2ecfb; color:var(--status-purple);"><i
                                class="bi bi-eye"></i></div>
                        <div class="kpi-label">Surveyed</div>
                        <div class="kpi-value">{{ isset($stats['surveyed']) ? number_format($stats['surveyed']) : '0' }}
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card accent-teal">
                        <div class="kpi-icon" style="background:#e6f5f3; color:var(--status-teal);"><i
                                class="bi bi-link-45deg"></i></div>
                        <div class="kpi-label">Connected</div>
                        <div class="kpi-value">{{ isset($stats['connected']) ? number_format($stats['connected']) : '0' }}
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card">
                        <div class="kpi-icon"><i class="bi bi-people"></i></div>
                        <div class="kpi-label">Property Owners</div>
                        <div class="kpi-value">{{ isset($stats['owners']) ? number_format($stats['owners']) : '0' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════ TAX TYPE COUNTS ══════════════════════════ --}}
        <div class="gov-section">
            <div class="gov-eyebrow">
                <span class="label">Tax Type Register</span>
                <span class="rule"></span>
            </div>
            <div class="row g-3">
                <div class="col-xl-4 col-md-6">
                    <div class="kpi-card accent-blue">
                        <div class="kpi-icon" style="background:#eaf0fd; color:var(--status-blue);"><i
                                class="bi bi-droplet"></i></div>
                        <div class="kpi-label">Water Tax</div>
                        <div class="kpi-value">
                            {{ isset($stats['water_tax_count']) ? number_format($stats['water_tax_count']) : '0' }}</div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="kpi-card accent-gold">
                        <div class="kpi-icon" style="background:var(--gov-gold-tint); color:var(--gov-gold);"><i
                                class="bi bi-pipe"></i></div>
                        <div class="kpi-label">UGD Tax</div>
                        <div class="kpi-value">{{ isset($stats['ugd_count']) ? number_format($stats['ugd_count']) : '0' }}
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="kpi-card accent-purple">
                        <div class="kpi-icon" style="background:#f2ecfb; color:var(--status-purple);"><i
                                class="bi bi-briefcase"></i></div>
                        <div class="kpi-label">Professional Tax</div>
                        <div class="kpi-value">
                            {{ isset($stats['professional_tax_count']) ? number_format($stats['professional_tax_count']) : '0' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════ QUICK ACTIONS + TAX BREAKDOWN ══════════════════════════ --}}
        <div class="gov-section">
            <div class="row g-3">
                <div class="col-xl-5">
                    <div class="gov-card h-100">
                        <div class="gov-card-head">
                            <div class="gov-card-title"><i class="bi bi-lightning-charge"></i> Quick Actions</div>
                        </div>
                        <div class="gov-card-body">
                            <div class="action-registry">
                                <a href="{{ route('commissioner.map') ?? '#' }}" class="action-row"><span
                                        class="num">01</span><i class="bi bi-map"></i> View Ward Map <span
                                        class="arrow">→</span></a>
                                <a href="#" class="action-row"><span class="num">02</span><i
                                        class="bi bi-file-spreadsheet"></i> Collection Report <span
                                        class="arrow">→</span></a>
                                <a href="#" class="action-row"><span class="num">03</span><i
                                        class="bi bi-exclamation-triangle"></i> Pending Report <span
                                        class="arrow">→</span></a>
                                <a href="#" class="action-row"><span class="num">04</span><i
                                        class="bi bi-file-earmark-excel"></i> Export to Excel <span
                                        class="arrow">→</span></a>
                                <a href="#" class="action-row"><span class="num">05</span><i
                                        class="bi bi-printer"></i> Print Report <span class="arrow">→</span></a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-7">
                    <div class="gov-card h-100">
                        <div class="gov-card-head">
                            <div class="gov-card-title"><i class="bi bi-pie-chart"></i> Tax Breakdown</div>
                            <span class="gov-card-meta">{{ now()->format('F Y') }}</span>
                        </div>
                        <div class="gov-card-body">
                            <table class="gov-table">
                                <thead>
                                    <tr>
                                        <th>Tax Category</th>
                                        <th>Count</th>
                                        <th>Collection</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($taxBreakdown ?? [] as $key => $tax)
                                        @php
                                            $labels = [
                                                'mis' => 'MIS Assessment',
                                                'water_tax' => 'Water Tax',
                                                'ugd' => 'UGD Tax',
                                                'professional_tax' => 'Professional Tax',
                                            ];
                                            $icons = [
                                                'mis' => 'clipboard-data',
                                                'water_tax' => 'droplet',
                                                'ugd' => 'pipe',
                                                'professional_tax' => 'briefcase',
                                            ];
                                        @endphp
                                        <tr>
                                            <td><i class="bi bi-{{ $icons[$key] ?? 'file-text' }} me-2"
                                                    style="color:var(--gov-green);"></i>{{ $labels[$key] ?? ucfirst($key) }}
                                            </td>
                                            <td class="mono">{{ number_format($tax['count']) }}</td>
                                            <td class="mono" style="color:var(--gov-green); font-weight:700;">
                                                {{ $tax['collection'] ? '₹' . number_format($tax['collection']) : '₹0' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-3 text-muted">No tax data available
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════ ZONE-WISE COLLECTION PERFORMANCE ══════════════════════════ --}}
        <div class="gov-section">
            <div class="gov-card">
                <div class="gov-card-head">
                    <div class="gov-card-title"><i class="bi bi-graph-up"></i> Zone-wise Collection Performance</div>
                    <span class="gov-card-meta">{{ now()->format('F Y') }}</span>
                </div>
                <div class="gov-card-body" style="overflow-x:auto; padding:0;">
                    <table class="gov-table">
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
                                    <td style="font-weight:700; color:var(--ink-900);">{{ $zone['name'] }}</td>
                                    <td class="mono">{{ $zone['target'] }}</td>
                                    <td class="mono" style="color:var(--gov-green); font-weight:700;">
                                        {{ $zone['collected'] }}</td>
                                    <td class="mono" style="color:var(--status-red);">{{ $zone['pending'] }}</td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <div class="gov-perf-bar">
                                                <div class="fill"
                                                    style="width:{{ $zone['achievement'] }}%; background:{{ $zone['achievement'] >= 80 ? '#0f6b47' : ($zone['achievement'] >= 60 ? '#a9741a' : '#b91c1c') }};">
                                                </div>
                                            </div>
                                            <span class="mono"
                                                style="font-size:0.72rem; min-width:34px;">{{ $zone['achievement'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted">No zones found for this
                                        corporation</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════ ZONE REGISTER ══════════════════════════ --}}
        <div class="gov-section">
            <div class="gov-eyebrow">
                <span class="label">Zone Register</span>
                <span class="rule"></span>
                <a href="{{ route('admin.zones.index') }}" class="gov-card-link">View All Zones →</a>
            </div>
            <div class="row g-3">
                @forelse($zoneData ?? [] as $zone)
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="zone-register">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="zone-name">{{ $zone['name'] }}</div>
                                    <div class="zone-officer"><i
                                            class="bi bi-person-badge me-1"></i>{{ $zone['officer'] }}</div>
                                </div>
                                <i class="bi bi-building" style="color:var(--gov-green); font-size:1.1rem;"></i>
                            </div>
                            <div class="zone-stats">
                                <span>Wards: <strong>{{ $zone['wards'] }}</strong></span>
                                <span>Buildings: <strong>{{ number_format($zone['buildings']) }}</strong></span>
                                <span>Assessments: <strong>{{ number_format($zone['assessments']) }}</strong></span>
                                <span>Surveyed: <strong>{{ number_format($zone['surveyed']) }}</strong></span>
                            </div>
                            <div class="tax-tags">
                                <span class="tax-tag"><i
                                        class="bi bi-droplet me-1"></i>{{ $zone['water_tax'] ?? 0 }}</span>
                                <span class="tax-tag"><i class="bi bi-pipe me-1"></i>{{ $zone['ugd'] ?? 0 }}</span>
                                <span class="tax-tag"><i
                                        class="bi bi-briefcase me-1"></i>{{ $zone['professional_tax'] ?? 0 }}</span>
                            </div>
                            <div class="zone-footer">
                                <span class="zone-collection">{{ $zone['collection'] }}</span>
                                <span class="zone-pending">{{ $zone['pending'] }} pending</span>
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

        {{-- ══════════════════════════ TAX REGISTERS ══════════════════════════ --}}
        <div class="gov-section">
            <div class="gov-eyebrow">
                <span class="label">Tax Registers — Recent Entries</span>
                <span class="rule"></span>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="gov-card">
                        <div class="gov-card-head">
                            <div class="gov-card-title"><i class="bi bi-droplet"></i> Water Tax</div>
                            <a href="#" class="gov-card-link">View All</a>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="gov-table">
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
                                            <td class="mono" style="font-size:0.78rem;">{{ $item['amount'] }}</td>
                                            <td><span
                                                    class="gov-badge {{ $item['status'] }}">{{ $item['status'] }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-2 text-muted"
                                                style="font-size:0.75rem;">No data</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="gov-card">
                        <div class="gov-card-head">
                            <div class="gov-card-title"><i class="bi bi-pipe"></i> UGD Tax</div>
                            <a href="#" class="gov-card-link">View All</a>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="gov-table">
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
                                            <td class="mono" style="font-size:0.78rem;">{{ $item['amount'] }}</td>
                                            <td><span
                                                    class="gov-badge {{ $item['status'] }}">{{ $item['status'] }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-2 text-muted"
                                                style="font-size:0.75rem;">No data</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="gov-card">
                        <div class="gov-card-head">
                            <div class="gov-card-title"><i class="bi bi-briefcase"></i> Professional Tax</div>
                            <a href="#" class="gov-card-link">View All</a>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="gov-table">
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
                                            <td class="mono" style="font-size:0.78rem;">{{ $item['amount'] }}</td>
                                            <td><span
                                                    class="gov-badge {{ $item['status'] }}">{{ $item['status'] }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-2 text-muted"
                                                style="font-size:0.75rem;">No data</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        {{-- ══════════════════════════ ACTIVITY LOG ══════════════════════════ --}}
        <div class="gov-section">
            <div class="gov-card">
                <div class="gov-card-head">
                    <div class="gov-card-title"><i class="bi bi-journal-text"></i> Recent Activity Log</div>
                    <span class="gov-status-chip"
                        style="background:var(--gov-green-tint); border-color:#cbe9d8; color:var(--gov-green-dark);"><span
                            class="dot"
                            style="background:var(--gov-green); box-shadow:0 0 0 3px rgba(15,107,71,0.15);"></span>
                        Live</span>
                </div>
                <div class="gov-card-body">
                    <div class="row g-0">
                        @forelse($activities ?? [] as $activity)
                            <div class="col-md-6">
                                <div class="log-entry" style="padding-right: 1rem;">
                                    <div class="log-icon"
                                        style="background:{{ $activity['color'] }}18; color:{{ $activity['color'] }};">
                                        <i class="bi bi-{{ $activity['icon'] }}"></i>
                                    </div>
                                    <div class="log-text">{!! $activity['text'] !!}</div>
                                    <div class="log-time">{{ $activity['time'] }}</div>
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

    @endif

@endsection

@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@v9.2.4/ol.css">
    <script src="https://cdn.jsdelivr.net/npm/ol@v9.2.4/dist/ol.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bars = document.querySelectorAll('.gov-perf-bar .fill');
            bars.forEach(bar => {
                const w = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = w;
                }, 200);
            });
        });
 // Redirect function
window.openWard = function (wardId) {
    let url = "{{ route('commissioner.ward.showmap', ':id') }}";
    url = url.replace(':id', wardId);
    window.location.href = url;
};

        const allwardBoundary = @json($getAllwardBoundary ?? []);
        const waterTaxData = @json($waterTaxData ?? []);
        console.log(waterTaxData);

        document.addEventListener('DOMContentLoaded', function() {
            const mapEl = document.getElementById('wardMap');
            if (!mapEl || !allwardBoundary || allwardBoundary.length === 0) return;

            const features = [];
            const palette = ['#0f6b47', '#1d4ed8', '#a9741a', '#5b21b6', '#b91c1c', '#0e7c72'];

            allwardBoundary.forEach((ward, idx) => {
                if (!ward.boundary || !ward.boundary.coordinates) return;
                try {
                    const geometry = new ol.geom.MultiPolygon(ward.boundary.coordinates);
                    const feature = new ol.Feature({
                        geometry,
                        ward_id: ward.ward_id,
                        ward_no: ward.ward_no
                    });
                    const color = palette[idx % palette.length];

                    feature.setStyle(new ol.style.Style({
                        fill: new ol.style.Fill({
                            color: color + '26'
                        }),
                        stroke: new ol.style.Stroke({
                            color,
                            width: 2
                        }),
                        text: new ol.style.Text({
                            text: 'Ward ' + ward.ward_no,
                            font: '600 11px Inter, sans-serif',
                            fill: new ol.style.Fill({
                                color: '#0e2019'
                            }),
                            stroke: new ol.style.Stroke({
                                color: '#fff',
                                width: 3
                            })
                        })
                    }));
                    features.push(feature);
                } catch (e) {
                    console.error('Error building geometry for ward', ward.ward_id, e);
                }
            });

            const vectorSource = new ol.source.Vector({
                features
            });
            const map = new ol.Map({
                target: 'wardMap',
                layers: [
                    new ol.layer.Tile({
                        source: new ol.source.OSM()
                    }),
                    new ol.layer.Vector({
                        source: vectorSource
                    })
                ],
                view: new ol.View({
                    center: [0, 0],
                    zoom: 2
                })
            });

            if (vectorSource.getFeatures().length > 0) {
                map.getView().fit(vectorSource.getExtent(), {
                    padding: [30, 30, 30, 30],
                    maxZoom: 18
                });
            }

            const popupEl = document.createElement('div');
            popupEl.style.cssText =
                'background:#0a4530; color:#fff; padding:6px 10px; border-radius:6px; font-size:12px; font-weight:600; font-family:Inter,sans-serif;';
            const popup = new ol.Overlay({
                element: popupEl,
                positioning: 'bottom-center',
                offset: [0, -10]
            });
            map.addOverlay(popup);

            map.on('click', function(evt) {
                const feature = map.forEachFeatureAtPixel(evt.pixel, f => f);

                if (feature) {

                    const wardNo = feature.get('ward_no');
                    const wardId = feature.get('ward_id');

                    popupEl.innerHTML = `
                            <div>
                                <p><strong>Ward No:</strong> ${wardNo}</p>

                                <button
                                    class="btn btn-primary btn-sm"
                                    onclick="openWard(${wardId})">
                                    View Ward
                                </button>
                            </div>
                        `;

                    popup.setPosition(evt.coordinate);

                } else {
                    popup.setPosition(undefined);
                }
            });


            map.on('pointermove', function(evt) {
                map.getTargetElement().style.cursor = map.hasFeatureAtPixel(evt.pixel) ? 'pointer' : '';
            });
        });
    </script>
@endpush

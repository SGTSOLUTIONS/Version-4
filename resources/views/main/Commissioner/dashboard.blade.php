@extends('layouts.office')

@section('title', 'Commissioner Dashboard')
@section('page_title', 'Commissioner Dashboard')

@push('styles')
<style>
    /* ─── Theme Colors ─── */
    :root {
        /* Dark Theme Base */
        --bg-dark: #0a0e1a;
        --bg-card: #111827;
        --bg-card-hover: #1a2332;
        --bg-card-alt: #0f1729;

        /* Text Colors */
        --text-primary: #f1f5f9;
        --text-secondary: #94a3b8;
        --text-muted: #64748b;

        /* Border */
        --border-color: #1e293b;
        --border-light: #2d3a4f;

        /* Accent Colors - Emerald Green Theme */
        --accent-green: #10b981;
        --accent-green-dark: #059669;
        --accent-green-light: #34d399;
        --accent-green-glow: rgba(16, 185, 129, 0.15);

        /* Supporting Colors */
        --accent-blue: #3b82f6;
        --accent-gold: #f59e0b;
        --accent-purple: #8b5cf6;
        --accent-red: #ef4444;
        --accent-teal: #14b8a6;
        --accent-pink: #ec4899;
        --accent-orange: #f97316;
        --accent-indigo: #6366f1;
        --accent-cyan: #06b6d4;

        /* Fonts */
        --font-mono: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace;
        --font-sans: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
    }

    /* ─── Base Dark ─── */
    body {
        background-color: var(--bg-dark);
        color: var(--text-primary);
        font-family: var(--font-sans);
    }

    /* ─── Map Container ─── */
    #map-container {
        width: 100%;
        height: 550px;
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid var(--border-color);
        background: var(--bg-card);
        position: relative;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.4);
    }
    #map-container .ol-viewport {
        border-radius: 16px;
    }

    /* OpenLayers Control Overrides */
    .ol-control button {
        background-color: var(--bg-card) !important;
        color: var(--text-primary) !important;
        border: 1px solid var(--border-color) !important;
        border-radius: 8px !important;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3) !important;
        transition: all 0.2s ease !important;
        width: 36px !important;
        height: 36px !important;
        font-size: 18px !important;
    }
    .ol-control button:hover {
        background-color: var(--bg-card-hover) !important;
        border-color: var(--accent-green) !important;
        box-shadow: 0 0 20px rgba(16, 185, 129, 0.2) !important;
    }
    .ol-control button:focus {
        outline: none !important;
    }
    .ol-zoom {
        top: 16px !important;
        left: 16px !important;
    }
    .ol-zoom .ol-zoom-in {
        border-radius: 8px 8px 0 0 !important;
    }
    .ol-zoom .ol-zoom-out {
        border-radius: 0 0 8px 8px !important;
        border-top: none !important;
    }

    .ol-scale-line {
        background: rgba(17, 24, 39, 0.9) !important;
        color: var(--text-primary) !important;
        border: 1px solid var(--border-color) !important;
        padding: 4px 12px !important;
        border-radius: 8px !important;
        font-size: 0.7rem !important;
        bottom: 20px !important;
        left: 20px !important;
        backdrop-filter: blur(8px) !important;
    }
    .ol-scale-line-inner {
        border-color: var(--text-secondary) !important;
        color: var(--text-secondary) !important;
    }

    .ol-attribution {
        background: rgba(17, 24, 39, 0.9) !important;
        color: var(--text-muted) !important;
        font-size: 0.6rem !important;
        padding: 4px 12px !important;
        border-radius: 8px !important;
        backdrop-filter: blur(8px) !important;
        border: 1px solid var(--border-color) !important;
        bottom: 20px !important;
        right: 20px !important;
    }
    .ol-attribution a {
        color: var(--accent-green) !important;
        text-decoration: none !important;
    }
    .ol-attribution a:hover {
        color: var(--accent-green-light) !important;
        text-decoration: underline !important;
    }

    /* ─── Map Legend ─── */
    .map-legend {
        position: absolute;
        bottom: 80px;
        right: 20px;
        background: rgba(17, 24, 39, 0.92);
        backdrop-filter: blur(12px);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 14px 18px;
        z-index: 10;
        min-width: 170px;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
        transition: all 0.3s ease;
    }
    .map-legend:hover {
        border-color: var(--accent-green);
        box-shadow: 0 4px 40px rgba(16, 185, 129, 0.1);
    }
    .map-legend .legend-title {
        color: var(--text-primary);
        font-weight: 600;
        font-size: 0.75rem;
        margin-bottom: 6px;
        letter-spacing: 0.5px;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 6px;
    }
    .map-legend .legend-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.7rem;
        color: var(--text-secondary);
        padding: 4px 0;
        transition: all 0.2s ease;
    }
    .map-legend .legend-item:hover {
        color: var(--text-primary);
    }
    .map-legend .legend-item .color-box {
        width: 20px;
        height: 20px;
        border-radius: 6px;
        flex-shrink: 0;
        border: 1px solid rgba(255,255,255,0.08);
        transition: all 0.3s ease;
    }
    .map-legend .legend-item:hover .color-box {
        transform: scale(1.05);
    }
    .map-legend .legend-item .color-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    /* ─── Map Loading Overlay ─── */
    .map-loading {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(10, 14, 26, 0.85);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        z-index: 5;
        color: var(--text-secondary);
        font-size: 0.9rem;
        transition: opacity 0.6s ease;
        flex-direction: column;
        gap: 16px;
    }
    .map-loading.hidden {
        opacity: 0;
        pointer-events: none;
    }
    .map-loading .spinner {
        width: 40px;
        height: 40px;
        border: 3px solid var(--border-color);
        border-top-color: var(--accent-green);
        border-radius: 50%;
        animation: spin 0.8s cubic-bezier(0.4, 0, 0.2, 1) infinite;
    }
    .map-loading .loading-text {
        font-weight: 500;
        letter-spacing: 0.5px;
    }
    .map-loading .loading-text span {
        color: var(--accent-green);
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* ─── Hierarchy Flow ─── */
    .hierarchy-flow {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1.2rem;
        padding: 1.2rem 2rem;
        background: linear-gradient(135deg, #0f1729, #111827);
        border-radius: 16px;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
    }
    .hierarchy-item {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        background: var(--bg-card);
        padding: 0.5rem 1.2rem;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        font-weight: 600;
        color: var(--text-primary);
        font-size: 0.8rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }
    .hierarchy-item:hover {
        transform: translateY(-2px);
        border-color: var(--accent-green);
        box-shadow: 0 4px 20px rgba(16,185,129,0.12);
        background: var(--bg-card-hover);
    }
    .hierarchy-item .count {
        background: var(--accent-green);
        color: #0a0e1a;
        border-radius: 20px;
        padding: 0.1rem 0.7rem;
        font-size: 0.7rem;
        font-weight: 700;
    }
    .hierarchy-arrow {
        color: var(--accent-green);
        font-size: 1.2rem;
        font-weight: 300;
        opacity: 0.5;
    }

    /* ─── Stat Cards ─── */
    .comm-stat {
        background: var(--bg-card);
        border-radius: 14px;
        padding: 1.2rem 1.2rem 1rem 1.2rem;
        border: 1px solid var(--border-color);
        transition: all 0.3s ease;
        height: 100%;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        position: relative;
        overflow: hidden;
    }
    .comm-stat::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, transparent, var(--accent-green), transparent);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .comm-stat:hover::before {
        opacity: 1;
    }
    .comm-stat:hover {
        transform: translateY(-3px);
        border-color: var(--accent-green);
        box-shadow: 0 8px 30px rgba(16,185,129,0.08);
        background: var(--bg-card-hover);
    }
    .comm-stat .label {
        font-size: 0.6rem;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: var(--text-secondary);
        font-weight: 600;
    }
    .comm-stat .value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-top: 0.2rem;
        font-family: var(--font-mono);
    }
    .comm-stat .value.green { color: var(--accent-green); }
    .comm-stat .value.red { color: var(--accent-red); }
    .comm-stat .value.blue { color: var(--accent-blue); }
    .comm-stat .value.gold { color: var(--accent-gold); }
    .comm-stat .value.purple { color: var(--accent-purple); }
    .comm-stat .value.teal { color: var(--accent-teal); }
    .comm-stat .value.pink { color: var(--accent-pink); }
    .comm-stat .value.orange { color: var(--accent-orange); }
    .comm-stat .value.indigo { color: var(--accent-indigo); }
    .comm-stat .value.cyan { color: var(--accent-cyan); }

    .comm-stat .icon-wrap {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: #0a0e1a;
        flex-shrink: 0;
        transition: all 0.3s ease;
    }
    .comm-stat:hover .icon-wrap {
        transform: scale(1.05);
    }
    .comm-stat .icon-wrap.green { background: var(--accent-green); }
    .comm-stat .icon-wrap.blue { background: var(--accent-blue); }
    .comm-stat .icon-wrap.gold { background: var(--accent-gold); }
    .comm-stat .icon-wrap.red { background: var(--accent-red); }
    .comm-stat .icon-wrap.purple { background: var(--accent-purple); }
    .comm-stat .icon-wrap.teal { background: var(--accent-teal); }
    .comm-stat .icon-wrap.indigo { background: var(--accent-indigo); }
    .comm-stat .icon-wrap.pink { background: var(--accent-pink); }
    .comm-stat .icon-wrap.orange { background: var(--accent-orange); }
    .comm-stat .icon-wrap.cyan { background: var(--accent-cyan); }

    /* ─── Zone Cards ─── */
    .zone-card {
        background: var(--bg-card);
        border-radius: 14px;
        padding: 1.2rem 1.4rem;
        border: 1px solid var(--border-color);
        transition: all 0.3s ease;
        height: 100%;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        position: relative;
        overflow: hidden;
    }
    .zone-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, transparent, var(--accent-green), transparent);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .zone-card:hover::before {
        opacity: 1;
    }
    .zone-card:hover {
        transform: translateY(-4px);
        border-color: var(--accent-green);
        box-shadow: 0 8px 30px rgba(16,185,129,0.08);
        background: var(--bg-card-hover);
    }
    .zone-card .zone-name {
        font-weight: 700;
        font-size: 1.05rem;
        color: var(--text-primary);
    }
    .zone-card .zone-officer {
        font-size: 0.75rem;
        color: var(--text-secondary);
        margin-top: -0.1rem;
    }
    .zone-card .zone-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.4rem 1rem;
        margin-top: 0.7rem;
    }
    .zone-card .zone-stats span {
        font-size: 0.72rem;
        color: var(--text-secondary);
    }
    .zone-card .zone-stats strong {
        color: var(--text-primary);
        font-weight: 600;
        font-family: var(--font-mono);
    }
    .zone-card .zone-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 0.7rem;
        padding-top: 0.7rem;
        border-top: 1px solid var(--border-color);
    }
    .zone-card .zone-collection {
        font-weight: 700;
        color: var(--accent-green);
        font-family: var(--font-mono);
        font-size: 1rem;
    }
    .zone-card .zone-pending {
        font-weight: 600;
        color: var(--accent-red);
        font-family: var(--font-mono);
        font-size: 0.8rem;
    }
    .zone-card .tax-tags {
        display: flex;
        gap: 0.3rem;
        flex-wrap: wrap;
        margin-top: 0.4rem;
    }
    .zone-card .tax-tag {
        font-size: 0.6rem;
        padding: 0.15rem 0.6rem;
        border-radius: 10px;
        color: var(--text-primary);
        background: rgba(255,255,255,0.06);
    }
    .tax-tag.water { background: rgba(59,130,246,0.15); color: var(--accent-blue); }
    .tax-tag.ugd { background: rgba(245,158,11,0.15); color: var(--accent-gold); }
    .tax-tag.professional { background: rgba(99,102,241,0.15); color: var(--accent-indigo); }

    /* ─── Tables ─── */
    .comm-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.8rem;
    }
    .comm-table thead th {
        background: rgba(255,255,255,0.03);
        color: var(--text-secondary);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.6rem;
        letter-spacing: 0.6px;
        padding: 0.7rem 0.8rem;
        border-bottom: 1px solid var(--border-color);
        text-align: left;
    }
    .comm-table tbody td {
        padding: 0.65rem 0.8rem;
        border-bottom: 1px solid rgba(255,255,255,0.03);
        color: var(--text-primary);
    }
    .comm-table tbody tr:hover {
        background: rgba(255,255,255,0.02);
    }
    .comm-table .badge-status {
        padding: 0.2rem 0.7rem;
        border-radius: 20px;
        font-size: 0.6rem;
        font-weight: 600;
        display: inline-block;
    }
    .badge-status.paid { background: rgba(16,185,129,0.15); color: var(--accent-green); }
    .badge-status.pending { background: rgba(245,158,11,0.15); color: var(--accent-gold); }
    .badge-status.overdue { background: rgba(239,68,68,0.15); color: var(--accent-red); }
    .badge-status.active { background: rgba(59,130,246,0.15); color: var(--accent-blue); }

    .btn-view {
        color: var(--accent-green);
        font-size: 0.7rem;
        padding: 0.25rem 0.8rem;
        border-radius: 8px;
        border: 1px solid rgba(16,185,129,0.25);
        background: rgba(16,185,129,0.08);
        transition: all 0.2s;
        text-decoration: none;
        display: inline-block;
        cursor: pointer;
    }
    .btn-view:hover {
        background: var(--accent-green);
        color: #0a0e1a;
        border-color: var(--accent-green);
        text-decoration: none;
        box-shadow: 0 4px 20px rgba(16,185,129,0.2);
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
        border: 1px solid var(--border-color);
        background: rgba(255,255,255,0.02);
        color: var(--text-primary);
        font-size: 0.75rem;
        font-weight: 500;
        transition: all 0.2s;
        text-decoration: none;
        cursor: pointer;
    }
    .quick-action-btn:hover {
        border-color: var(--accent-green);
        background: rgba(16,185,129,0.08);
        color: var(--accent-green);
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 4px 20px rgba(16,185,129,0.08);
    }
    .quick-action-btn i {
        font-size: 1rem;
        color: var(--accent-green);
    }

    /* ─── Performance Bar ─── */
    .perf-bar {
        height: 6px;
        border-radius: 20px;
        background: rgba(255,255,255,0.06);
        overflow: hidden;
        min-width: 60px;
        flex: 1;
    }
    .perf-bar .fill {
        height: 100%;
        border-radius: 20px;
        transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* ─── Activity Items ─── */
    .activity-item {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        padding: 0.5rem 0.8rem;
        border-radius: 8px;
        background: rgba(255,255,255,0.02);
        transition: all 0.2s;
        border: 1px solid transparent;
    }
    .activity-item:hover {
        background: rgba(255,255,255,0.04);
        border-color: var(--border-color);
    }
    .activity-item .act-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .activity-item .act-text {
        font-size: 0.75rem;
        color: var(--text-secondary);
        flex: 1;
    }
    .activity-item .act-text strong {
        color: var(--text-primary);
    }
    .activity-item .act-time {
        font-size: 0.6rem;
        color: var(--text-muted);
        white-space: nowrap;
    }

    /* ─── Error State ─── */
    .error-state {
        text-align: center;
        padding: 3rem 1rem;
        background: var(--bg-card);
        border-radius: 14px;
        border: 1px solid rgba(239,68,68,0.3);
        background: rgba(239,68,68,0.05);
    }
    .error-state i {
        font-size: 3rem;
        color: var(--accent-red);
        margin-bottom: 1rem;
        display: block;
    }
    .error-state h5 {
        color: var(--accent-red);
        font-weight: 600;
    }
    .error-state p {
        color: var(--text-secondary);
    }

    /* ─── DS Card Override ─── */
    .ds-card {
        background: var(--bg-card);
        border-radius: 14px;
        border: 1px solid var(--border-color);
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
        overflow: hidden;
    }
    .ds-card:hover {
        border-color: rgba(16,185,129,0.2);
    }
    .ds-card-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.9rem 1.2rem;
        border-bottom: 1px solid var(--border-color);
        flex-wrap: wrap;
        gap: 0.5rem;
        background: rgba(255,255,255,0.02);
    }
    .ds-card-title {
        font-weight: 600;
        color: var(--text-primary);
        font-size: 0.9rem;
    }
    .ds-card-body {
        padding: 1.2rem;
    }

    .ds-pill {
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 0.65rem;
        font-weight: 600;
        display: inline-block;
    }
    .ds-pill.paid {
        background: rgba(16,185,129,0.12);
        color: var(--accent-green);
        border: 1px solid rgba(16,185,129,0.2);
    }

    .rv-submit {
        background: var(--accent-green);
        color: #0a0e1a;
        border: none;
        padding: 8px 22px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.8rem;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
    }
    .rv-submit:hover {
        background: var(--accent-green-light);
        color: #0a0e1a;
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 4px 20px rgba(16,185,129,0.3);
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
        color: var(--text-primary);
        margin: 0;
        letter-spacing: -0.5px;
    }
    .ol-page-title span {
        color: var(--accent-green);
    }
    .ol-page-sub {
        color: var(--text-secondary);
        margin: 0.2rem 0 0 0;
        font-size: 0.82rem;
    }

    /* ─── Tax Breakdown ─── */
    .tax-breakdown-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid var(--border-color);
        transition: all 0.2s ease;
    }
    .tax-breakdown-item:last-child {
        border-bottom: none;
    }
    .tax-breakdown-item:hover {
        padding-left: 4px;
        background: rgba(255,255,255,0.02);
        border-radius: 4px;
    }
    .tax-breakdown-item .tax-label {
        font-size: 0.75rem;
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .tax-breakdown-item .tax-count {
        font-weight: 600;
        color: var(--text-primary);
        font-family: var(--font-mono);
        font-size: 0.85rem;
    }
    .tax-breakdown-item .tax-amount {
        font-weight: 600;
        color: var(--accent-green);
        font-family: var(--font-mono);
        font-size: 0.85rem;
    }

    /* ─── Responsive ─── */
    @media (max-width: 768px) {
        .hierarchy-flow { gap: 0.6rem; padding: 0.8rem; }
        .hierarchy-item { font-size: 0.65rem; padding: 0.3rem 0.8rem; }
        .hierarchy-arrow { font-size: 1rem; }
        .comm-stat .value { font-size: 1.1rem; }
        .zone-card .zone-stats { grid-template-columns: 1fr; }
        .quick-actions { grid-template-columns: 1fr 1fr; }
        .ol-page-header { flex-direction: column; }
        #map-container { height: 350px; }
        .map-legend { bottom: 70px; right: 10px; padding: 10px 14px; min-width: 130px; }
        .map-legend .legend-item { font-size: 0.6rem; }
        .map-legend .legend-item .color-box { width: 16px; height: 16px; }
        .ol-scale-line { display: none; }
    }

    @media (max-width: 480px) {
        .hierarchy-item { font-size: 0.55rem; padding: 0.2rem 0.6rem; gap: 0.3rem; }
        .hierarchy-arrow { font-size: 0.8rem; }
        .comm-stat { padding: 0.8rem; }
        .comm-stat .value { font-size: 0.95rem; }
        .ds-card-head { flex-direction: column; align-items: flex-start; }
        .quick-actions { grid-template-columns: 1fr; }
    }

    /* ─── Scrollbar ─── */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    ::-webkit-scrollbar-track {
        background: var(--bg-dark);
    }
    ::-webkit-scrollbar-thumb {
        background: var(--border-color);
        border-radius: 10px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: var(--text-muted);
    }

    /* ─── Smooth Animations ─── */
    .fade-in {
        animation: fadeIn 0.5s ease forwards;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .fade-in-delay-1 { animation-delay: 0.1s; }
    .fade-in-delay-2 { animation-delay: 0.2s; }
    .fade-in-delay-3 { animation-delay: 0.3s; }
    .fade-in-delay-4 { animation-delay: 0.4s; }
    .fade-in-delay-5 { animation-delay: 0.5s; }
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="ol-page-header fade-in">
    <div>
        <h1 class="ol-page-title">Commissioner <span>Dashboard</span></h1>
        <p class="ol-page-sub">
            {{ isset($corporation) && $corporation ? $corporation->name . ' — Complete revenue hierarchy overview' : 'Revenue overview' }}
            — {{ now()->format('l, d F Y') }}
        </p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        @if(isset($corporation) && $corporation)
        <span style="font-size:0.65rem; padding:4px 14px; background:rgba(16,185,129,0.12); color:var(--accent-green); border-radius:20px; border:1px solid rgba(16,185,129,0.2);">
            <i class="bi bi-building me-1"></i> {{ $corporation->name }}
        </span>
        @endif
        <span class="ds-pill paid" style="font-size:0.6rem; padding:4px 12px;">
            <i class="bi bi-circle-fill me-1" style="font-size:6px; vertical-align:1px; color:var(--accent-green);"></i>Live
        </span>
        <a href="#" class="rv-submit" style="width:auto; height:38px; padding:0 1.2rem; font-size:0.75rem !important; border-radius:10px !important; display:inline-flex; align-items:center; gap:6px; animation:none;">
            <i class="bi bi-download" style="font-size:13px;"></i>
            Export Report
        </a>
    </div>
</div>

@if(isset($error))
    <div class="error-state fade-in">
        <i class="bi bi-exclamation-triangle"></i>
        <h5>{{ $error }}</h5>
        <p>Please contact your administrator to assign a corporation to your account.</p>
    </div>
@else

{{-- ── Hierarchy Flow ── --}}
<div class="hierarchy-flow fade-in">
    <div class="hierarchy-item">
        <i class="bi bi-diagram-3" style="color:var(--accent-green);"></i>
        Zones <span class="count">{{ $hierarchyStats['zones'] ?? 0 }}</span>
    </div>
    <span class="hierarchy-arrow">→</span>
    <div class="hierarchy-item">
        <i class="bi bi-grid-3x3-gap-fill" style="color:var(--accent-blue);"></i>
        Wards <span class="count">{{ $hierarchyStats['wards'] ?? 0 }}</span>
    </div>
    <span class="hierarchy-arrow">→</span>
    <div class="hierarchy-item">
        <i class="bi bi-building" style="color:var(--accent-gold);"></i>
        Buildings <span class="count">{{ isset($hierarchyStats['buildings']) ? number_format($hierarchyStats['buildings']) : '0' }}</span>
    </div>
    <span class="hierarchy-arrow">→</span>
    <div class="hierarchy-item">
        <i class="bi bi-clipboard-data" style="color:var(--accent-purple);"></i>
        Assessments <span class="count">{{ isset($hierarchyStats['assessments']) ? number_format($hierarchyStats['assessments']) : '0' }}</span>
    </div>
    <span class="hierarchy-arrow">→</span>
    <div class="hierarchy-item">
        <i class="bi bi-check2-circle" style="color:var(--accent-teal);"></i>
        Surveyed <span class="count">{{ isset($hierarchyStats['surveyed']) ? number_format($hierarchyStats['surveyed']) : '0' }}</span>
    </div>
    <span class="hierarchy-arrow">→</span>
    <div class="hierarchy-item">
        <i class="bi bi-link-45deg" style="color:var(--accent-purple);"></i>
        Connected <span class="count">{{ isset($hierarchyStats['connected']) ? number_format($hierarchyStats['connected']) : '0' }}</span>
    </div>
</div>

{{-- ── MAP SECTION ── --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="ds-card fade-in">
            <div class="ds-card-head">
                <div class="ds-card-title">
                    <i class="bi bi-map me-2" style="color:var(--accent-green);"></i>
                    HTA Boundaries Map
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <span class="ds-pill paid" style="font-size:0.6rem;">
                        <i class="bi bi-geo-alt me-1"></i> {{ isset($corporation) ? $corporation->name : 'No Corporation' }}
                    </span>
                    <button class="btn-view" onclick="resetMapView()" style="cursor:pointer;">
                        <i class="bi bi-house"></i> Reset View
                    </button>
                    <button class="btn-view" onclick="toggleLegend()" style="cursor:pointer;">
                        <i class="bi bi-eye"></i> Legend
                    </button>
                </div>
            </div>
            <div class="ds-card-body" style="padding:0;">
                <div id="map-container">
                    <div class="map-loading" id="mapLoading">
                        <div class="spinner"></div>
                        <div class="loading-text">Loading <span>HTA Boundaries</span>...</div>
                    </div>
                    <div class="map-legend" id="mapLegend">
                        <div class="legend-title">📌 HTA Boundaries</div>
                        <div class="legend-item">
                            <div class="color-box" style="background:rgba(16,185,129,0.25); border:2px solid #10b981;"></div>
                            <span>Ward Boundary</span>
                        </div>
                        <div class="legend-item">
                            <div class="color-box" style="background:rgba(59,130,246,0.2); border:2px solid #3b82f6;"></div>
                            <span>Zone Boundary</span>
                        </div>
                        <div class="legend-item">
                            <div class="color-box" style="background:rgba(245,158,11,0.15); border:2px solid #f59e0b; border-style:dashed;"></div>
                            <span>Corporation Boundary</span>
                        </div>
                        <div class="legend-item">
                            <div class="color-dot" style="background:#ef4444;"></div>
                            <span>Survey Points</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Top Statistics ── --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-lg-4 col-md-6 fade-in fade-in-delay-1">
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
    <div class="col-xl-3 col-lg-4 col-md-6 fade-in fade-in-delay-2">
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
    <div class="col-xl-3 col-lg-4 col-md-6 fade-in fade-in-delay-3">
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
    <div class="col-xl-3 col-lg-4 col-md-6 fade-in fade-in-delay-4">
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
    <div class="col-xl-3 col-lg-4 col-md-6 fade-in fade-in-delay-5">
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
    <div class="col-xl-3 col-lg-4 col-md-6 fade-in fade-in-delay-1">
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
    <div class="col-xl-3 col-lg-4 col-md-6 fade-in fade-in-delay-2">
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
    <div class="col-xl-3 col-lg-4 col-md-6 fade-in fade-in-delay-3">
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
    <div class="col-xl-2 col-lg-4 col-md-6 fade-in fade-in-delay-1">
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
    <div class="col-xl-2 col-lg-4 col-md-6 fade-in fade-in-delay-2">
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
    <div class="col-xl-2 col-lg-4 col-md-6 fade-in fade-in-delay-3">
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
    <div class="col-xl-2 col-lg-4 col-md-6 fade-in fade-in-delay-4">
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
    <div class="col-xl-2 col-lg-4 col-md-6 fade-in fade-in-delay-5">
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
    <div class="col-xl-2 col-lg-4 col-md-6 fade-in fade-in-delay-1">
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
    <div class="col-xl-4 col-lg-4 col-md-6 fade-in fade-in-delay-2">
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
    <div class="col-xl-4 col-lg-4 col-md-6 fade-in fade-in-delay-3">
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
    <div class="col-xl-4 col-lg-4 col-md-6 fade-in fade-in-delay-4">
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
    <div class="col-xl-5 fade-in fade-in-delay-5">
        <div class="ds-card h-100">
            <div class="ds-card-head">
                <div class="ds-card-title"><i class="bi bi-lightning me-2" style="color:var(--accent-green);"></i>Quick Actions</div>
            </div>
            <div class="ds-card-body">
                <div class="quick-actions">
                    <button class="quick-action-btn" onclick="focusMapOnCorporation()"><i class="bi bi-map"></i> View Map</button>
                    <a href="#" class="quick-action-btn"><i class="bi bi-file-spreadsheet"></i> Collection Report</a>
                    <a href="#" class="quick-action-btn"><i class="bi bi-exclamation-triangle"></i> Pending Report</a>
                    <a href="#" class="quick-action-btn"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
                    <a href="#" class="quick-action-btn"><i class="bi bi-printer"></i> Print Report</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-7 fade-in fade-in-delay-1">
        <div class="ds-card h-100">
            <div class="ds-card-head">
                <div class="ds-card-title"><i class="bi bi-pie-chart me-2" style="color:var(--accent-green);"></i>Tax Breakdown</div>
                <span style="font-size:0.65rem; color:var(--text-muted);">{{ now()->format('F Y') }}</span>
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
                    <div class="text-center py-3 text-muted" style="color:var(--text-muted);">No tax data available</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Zone Performance ── --}}
<div class="row g-3 mb-4">
    <div class="col-12 fade-in">
        <div class="ds-card">
            <div class="ds-card-head">
                <div class="ds-card-title"><i class="bi bi-graph-up me-2" style="color:var(--accent-green);"></i>Zone-wise Collection Performance</div>
                <span style="font-size:0.65rem; color:var(--text-muted);">{{ now()->format('F Y') }}</span>
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
                            <td style="font-weight:600; color:var(--text-primary);">{{ $zone['name'] }}</td>
                            <td style="font-family:var(--font-mono); color:var(--text-secondary);">{{ $zone['target'] }}</td>
                            <td style="font-family:var(--font-mono); font-weight:600; color:var(--accent-green);">{{ $zone['collected'] }}</td>
                            <td style="font-family:var(--font-mono); color:var(--accent-red);">{{ $zone['pending'] }}</td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div class="perf-bar">
                                        <div class="fill" style="width:{{ $zone['achievement'] }}%; background:{{ $zone['achievement'] >= 80 ? 'var(--accent-green)' : ($zone['achievement'] >= 60 ? 'var(--accent-gold)' : 'var(--accent-red)') }};"></div>
                                    </div>
                                    <span style="font-family:var(--font-mono); font-size:0.75rem; min-width:36px; color:var(--text-secondary);">{{ $zone['achievement'] }}%</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-3 text-muted" style="color:var(--text-muted);">No zones found for this corporation</td>
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
            <h6 class="fw-bold mb-0" style="color:var(--text-primary);"><i class="bi bi-diagram-3 me-2" style="color:var(--accent-green);"></i>Zone Overview</h6>
            <a href="#" style="font-size:0.75rem; color:var(--accent-green); text-decoration:none; transition:all 0.2s;">View All Zones <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-3">
            @forelse($zoneData ?? [] as $zone)
            <div class="col-xl-3 col-lg-4 col-md-6 fade-in">
                <div class="zone-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="zone-name">{{ $zone['name'] }}</div>
                            <div class="zone-officer"><i class="bi bi-person-badge me-1"></i>{{ $zone['officer'] }}</div>
                        </div>
                        <span style="font-size:1.2rem; color:var(--accent-green);"><i class="bi bi-building"></i></span>
                    </div>
                    <div class="zone-stats">
                        <span><i class="bi bi-grid-3x3-gap-fill me-1" style="color:var(--accent-blue);"></i>Wards: <strong>{{ $zone['wards'] }}</strong></span>
                        <span><i class="bi bi-building me-1" style="color:var(--accent-gold);"></i>Buildings: <strong>{{ number_format($zone['buildings']) }}</strong></span>
                        <span><i class="bi bi-clipboard-data me-1" style="color:var(--accent-purple);"></i>Assessments: <strong>{{ number_format($zone['assessments']) }}</strong></span>
                        <span><i class="bi bi-eye me-1" style="color:var(--accent-teal);"></i>Surveyed: <strong>{{ number_format($zone['surveyed']) }}</strong></span>
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
                <div class="text-center py-4" style="color:var(--text-muted);">
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
    <div class="col-md-4 fade-in">
        <div class="ds-card">
            <div class="ds-card-head">
                <div class="ds-card-title"><i class="bi bi-droplet me-2" style="color:var(--accent-blue);"></i>Water Tax</div>
                <a href="#" style="font-size:0.65rem; color:var(--accent-green); text-decoration:none; transition:all 0.2s;">View All</a>
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
                            <td style="font-weight:600; font-size:0.7rem; color:var(--text-primary);">{{ $item['no'] }}</td>
                            <td style="font-family:var(--font-mono); font-size:0.75rem; color:var(--text-secondary);">{{ $item['amount'] }}</td>
                            <td><span class="badge-status {{ $item['status'] }}">{{ ucfirst($item['status']) }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center py-2" style="font-size:0.75rem; color:var(--text-muted);">No data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4 fade-in fade-in-delay-2">
        <div class="ds-card">
            <div class="ds-card-head">
                <div class="ds-card-title"><i class="bi bi-pipe me-2" style="color:var(--accent-gold);"></i>UGD Tax</div>
                <a href="#" style="font-size:0.65rem; color:var(--accent-green); text-decoration:none; transition:all 0.2s;">View All</a>
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
                            <td style="font-weight:600; font-size:0.7rem; color:var(--text-primary);">{{ $item['no'] }}</td>
                            <td style="font-family:var(--font-mono); font-size:0.75rem; color:var(--text-secondary);">{{ $item['amount'] }}</td>
                            <td><span class="badge-status {{ $item['status'] }}">{{ ucfirst($item['status']) }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center py-2" style="font-size:0.75rem; color:var(--text-muted);">No data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4 fade-in fade-in-delay-3">
        <div class="ds-card">
            <div class="ds-card-head">
                <div class="ds-card-title"><i class="bi bi-briefcase me-2" style="color:var(--accent-indigo);"></i>Professional Tax</div>
                <a href="#" style="font-size:0.65rem; color:var(--accent-green); text-decoration:none; transition:all 0.2s;">View All</a>
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
                            <td style="font-weight:600; font-size:0.7rem; color:var(--text-primary);">{{ $item['no'] }}</td>
                            <td style="font-family:var(--font-mono); font-size:0.75rem; color:var(--text-secondary);">{{ $item['amount'] }}</td>
                            <td><span class="badge-status {{ $item['status'] }}">{{ ucfirst($item['status']) }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center py-2" style="font-size:0.75rem; color:var(--text-muted);">No data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ── Recent Activities ── --}}
<div class="row g-3 mb-4">
    <div class="col-12 fade-in">
        <div class="ds-card">
            <div class="ds-card-head">
                <div class="ds-card-title"><i class="bi bi-activity me-2" style="color:var(--accent-green);"></i>Recent Activities</div>
                <span style="font-size:0.65rem; color:var(--text-muted);">Live</span>
            </div>
            <div class="ds-card-body">
                <div class="row g-2">
                    @forelse($activities ?? [] as $activity)
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="activity-item">
                            <div class="act-icon" style="background:{{ $activity['color'] }}20; color:{{ $activity['color'] }};">
                                <i class="bi bi-{{ $activity['icon'] }}"></i>
                            </div>
                            <div class="act-text">{!! $activity['text'] !!}</div>
                            <div class="act-time">{{ $activity['time'] }}</div>
                        </div>
                    </div>
                    @empty
                    <div class="col-12">
                        <div class="text-center py-3" style="color:var(--text-muted);">No recent activities</div>
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
    let map = null;
    let mapInitialized = false;
    let vectorSource = null;
    let vectorLayer = null;
    let pointLayer = null;
    let currentFeatures = [];
    let legendVisible = true;

    /**
     * Initialize the OpenLayers map
     */
    function initMap() {
        if (mapInitialized) return;

        const container = document.getElementById('map-container');
        if (!container) return;

        // Get corporation data from PHP
        const corporationId = {{ isset($corporation) ? $corporation->id : 'null' }};
        const corporationName = '{{ isset($corporation) ? addslashes($corporation->name) : '' }}';
        const boundaries = @json(isset($allwardBoundary) ? $allwardBoundary : []);

        // Create vector source for boundaries
        vectorSource = new ol.source.Vector({
            features: []
        });

        // Create vector source for points
        const pointSource = new ol.source.Vector({
            features: []
        });

        // Create vector layer for boundaries with styling
        vectorLayer = new ol.layer.Vector({
            source: vectorSource,
            style: function(feature) {
                const type = feature.get('type') || 'ward';
                let color, fillColor, strokeWidth = 2, lineDash = undefined;

                switch(type) {
                    case 'corporation':
                        color = '#f59e0b';
                        fillColor = 'rgba(245, 158, 11, 0.08)';
                        strokeWidth = 3;
                        lineDash = [8, 6];
                        break;
                    case 'zone':
                        color = '#3b82f6';
                        fillColor = 'rgba(59, 130, 246, 0.1)';
                        strokeWidth = 2.5;
                        break;
                    case 'ward':
                    default:
                        color = '#10b981';
                        fillColor = 'rgba(16, 185, 129, 0.12)';
                        strokeWidth = 2;
                        break;
                }

                return new ol.style.Style({
                    fill: new ol.style.Fill({
                        color: fillColor
                    }),
                    stroke: new ol.style.Stroke({
                        color: color,
                        width: strokeWidth,
                        lineDash: lineDash
                    }),
                    text: new ol.style.Text({
                        text: feature.get('name') || '',
                        fill: new ol.style.Fill({
                            color: '#f1f5f9'
                        }),
                        stroke: new ol.style.Stroke({
                            color: 'rgba(10, 14, 26, 0.85)',
                            width: 4
                        }),
                        font: '12px "Inter", "Segoe UI", sans-serif',
                        textAlign: 'center',
                        textBaseline: 'middle',
                        offsetY: -10
                    })
                });
            }
        });

        // Create point layer for survey points
        pointLayer = new ol.layer.Vector({
            source: pointSource,
            style: new ol.style.Style({
                image: new ol.style.Circle({
                    radius: 6,
                    fill: new ol.style.Fill({
                        color: '#ef4444'
                    }),
                    stroke: new ol.style.Stroke({
                        color: 'rgba(255,255,255,0.8)',
                        width: 2
                    })
                }),
                text: new ol.style.Text({
                    text: '•',
                    fill: new ol.style.Fill({
                        color: '#ef4444'
                    }),
                    font: '16px sans-serif'
                })
            })
        });

        // Create the map
        map = new ol.Map({
            target: 'map-container',
            layers: [
                new ol.layer.Tile({
                    source: new ol.source.OSM({
                        attributions: [
                            '© <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors'
                        ]
                    })
                }),
                vectorLayer,
                pointLayer
            ],
            view: new ol.View({
                center: ol.proj.fromLonLat([78.9629, 20.5937]), // Default center (India)
                zoom: 5,
                minZoom: 4,
                maxZoom: 20
            }),
            controls: ol.control.defaults({
                attributionOptions: {
                    collapsible: true
                }
            }).extend([
                new ol.control.ScaleLine({
                    units: 'metric'
                })
            ])
        });

        mapInitialized = true;

        // Hide loading overlay
        document.getElementById('mapLoading').classList.add('hidden');

        // Load boundaries
        if (boundaries && boundaries.length > 0) {
            loadBoundaries(boundaries);
        } else {
            // If no boundaries, try to fetch from server
            if (corporationId) {
                fetchBoundariesFromServer(corporationId);
            } else {
                addNoDataMessage();
            }
        }

        // Handle resize
        window.addEventListener('resize', function() {
            if (map) {
                setTimeout(() => map.updateSize(), 300);
            }
        });
    }

    /**
     * Load boundaries from GeoJSON data
     */
    function loadBoundaries(boundaryData) {
        try {
            const features = [];

            boundaryData.forEach((boundary, index) => {
                if (typeof boundary === 'string') {
                    try {
                        const parsed = JSON.parse(boundary);
                        if (parsed && (parsed.type === 'Polygon' || parsed.type === 'MultiPolygon')) {
                            const geom = new ol.geom.Polygon(parsed.coordinates);
                            const feature = new ol.Feature({
                                geometry: geom.transform('EPSG:4326', 'EPSG:3857'),
                                name: `Ward ${index + 1}`,
                                type: 'ward',
                                id: index + 1
                            });
                            features.push(feature);
                        } else if (parsed && parsed.type === 'Feature') {
                            const geom = new ol.format.GeoJSON().readGeometry(parsed.geometry);
                            if (geom) {
                                const feature = new ol.Feature({
                                    geometry: geom.transform('EPSG:4326', 'EPSG:3857'),
                                    name: parsed.properties?.name || `Ward ${index + 1}`,
                                    type: parsed.properties?.type || 'ward',
                                    id: parsed.properties?.id || index + 1
                                });
                                features.push(feature);
                            }
                        }
                    } catch (e) {
                        console.warn('Failed to parse boundary:', e);
                    }
                }
            });

            if (features.length > 0) {
                vectorSource.addFeatures(features);
                currentFeatures = features;

                // Fit map to show all features
                const extent = vectorSource.getExtent();
                if (extent && !isNaN(extent[0]) && !isNaN(extent[1]) &&
                    !isNaN(extent[2]) && !isNaN(extent[3])) {
                    map.getView().fit(extent, {
                        padding: [60, 60, 60, 60],
                        maxZoom: 16,
                        duration: 1000
                    });
                }
            } else {
                addNoDataMessage();
            }
        } catch (error) {
            console.error('Error loading boundaries:', error);
            addNoDataMessage();
        }
    }

    /**
     * Add a message when no data is available
     */
    function addNoDataMessage() {
        // Show a placeholder on the map
        const feature = new ol.Feature({
            geometry: new ol.geom.Point(ol.proj.fromLonLat([78.9629, 20.5937])),
            name: 'No boundary data available'
        });
        // Don't add as a real feature, just show message
        console.log('No boundary data available for this corporation');
    }

    /**
     * Fetch boundaries from server via AJAX
     */
    function fetchBoundariesFromServer(corporationId) {
        const loading = document.getElementById('mapLoading');
        loading.classList.remove('hidden');

        fetch(`/api/corporation/${corporationId}/boundaries`)
            .then(response => response.json())
            .then(data => {
                loading.classList.add('hidden');
                if (data && data.boundaries && data.boundaries.length > 0) {
                    loadBoundaries(data.boundaries);
                } else {
                    addNoDataMessage();
                }
            })
            .catch(error => {
                loading.classList.add('hidden');
                console.error('Error fetching boundaries:', error);
                addNoDataMessage();
            });
    }

    /**
     * Reset map view to show all boundaries
     */
    function resetMapView() {
        if (!map || !vectorSource) return;

        const extent = vectorSource.getExtent();
        if (extent && !isNaN(extent[0]) && !isNaN(extent[1]) &&
            !isNaN(extent[2]) && !isNaN(extent[3])) {
            map.getView().fit(extent, {
                padding: [60, 60, 60, 60],
                maxZoom: 16,
                duration: 1000
            });
        } else {
            map.getView().setCenter(ol.proj.fromLonLat([78.9629, 20.5937]));
            map.getView().setZoom(5);
        }
    }

    /**
     * Focus map on corporation boundaries
     */
    function focusMapOnCorporation() {
        resetMapView();
        return false;
    }

    /**
     * Toggle legend visibility
     */
    function toggleLegend() {
        const legend = document.getElementById('mapLegend');
        legendVisible = !legendVisible;
        legend.style.display = legendVisible ? 'block' : 'none';
    }

    // Initialize map when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for the layout to render
        setTimeout(initMap, 500);
    });

    // Re-initialize if needed after page fully loads
    window.addEventListener('load', function() {
        if (!mapInitialized) {
            setTimeout(initMap, 1000);
        }
    });
</script>
@endpush

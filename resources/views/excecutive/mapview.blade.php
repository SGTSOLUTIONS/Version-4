@extends('layouts.office')

@section('title', 'Executive GIS Dashboard')
@section('page_title', 'Executive GIS Dashboard')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@v10.2.1/ol.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cesium/1.127.0/Widgets/widgets.css" rel="stylesheet">
    <style>
        /* ─── ROOT VARIABLES ─── */
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #dbeafe;
            --success-color: #16a34a;
            --success-light: #dcfce7;
            --danger-color: #dc2626;
            --danger-light: #fee2e2;
            --warning-color: #f59e0b;
            --warning-light: #fef3c7;
            --info-color: #0891b2;
            --info-light: #ecfeff;
            --purple-color: #7c3aed;
            --purple-light: #f5f3ff;
            --pink-color: #db2777;
            --pink-light: #fdf2f8;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --radius: 12px;
            --radius-sm: 8px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--gray-50);
        }

        .ol-page-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: var(--radius);
            padding: 20px 28px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .ol-page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .ol-page-title {
            color: #ffffff;
            font-weight: 700;
            font-size: 1.4rem;
            margin: 0;
            letter-spacing: -0.02em;
            position: relative;
            z-index: 1;
        }

        .ol-page-title i {
            color: var(--primary-color);
            background: rgba(37, 99, 235, 0.2);
            padding: 8px;
            border-radius: 10px;
            margin-right: 12px;
        }

        .ol-page-sub {
            color: var(--gray-400);
            font-size: 0.85rem;
            margin: 4px 0 0 0;
            position: relative;
            z-index: 1;
        }

        .ds-pill {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .ds-pill.paid {
            background: #dcfce7;
            color: #15803d;
        }

        /* ─── MAP CARD ─── */
        .map-card {
            background: #fff;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            overflow: hidden;
            box-shadow: var(--shadow);
            position: relative;
        }

        .map-header {
            padding: 12px 18px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--gray-50);
        }

        #map {
            width: 100%;
            height: 750px;
            transition: all 0.3s ease;
            position: relative;
        }

        /* ─── MAP CONTROLS STACK ─── */
        .map-controls-stack {
            position: absolute;
            right: 20px;
            top: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 8px;
            pointer-events: auto;
        }

        /* ─── TOGGLE BUTTONS ─── */
        .layer-toggle-btn,
        .location-toggle-btn,
        .search-toggle-btn,
        .label-toggle-btn,
        .legend-toggle-btn,
        .threed-toggle-btn,
        .filter-toggle-btn {
            background: white;
            border-radius: 10px;
            padding: 10px;
            cursor: pointer;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
            font-size: 18px;
            transition: all 0.2s;
            border: 1px solid var(--gray-200);
            color: #333;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            position: relative;
            z-index: 1001;
            background: #ffffff;
        }

        .layer-toggle-btn:hover,
        .location-toggle-btn:hover,
        .search-toggle-btn:hover,
        .label-toggle-btn:hover,
        .legend-toggle-btn:hover,
        .threed-toggle-btn:hover,
        .filter-toggle-btn:hover {
            background: #f0f0f0;
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .label-toggle-btn.active-label {
            color: #0d6efd;
            background: #e3f0ff;
            border-color: #0d6efd;
        }

        .location-toggle-btn.active-location {
            color: #0d6efd;
            background: #e3f0ff;
            border-color: #0d6efd;
        }

        .threed-toggle-btn.active-3d {
            color: #0d6efd;
            background: #e3f0ff;
            border-color: #0d6efd;
        }

        .filter-toggle-btn.active-filter {
            color: #0d6efd;
            background: #e3f0ff;
            border-color: #0d6efd;
        }

        /* ─── DROPDOWNS ─── */
        .layer-dropdown,
        .location-dropdown,
        .search-dropdown,
        .filter-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 48px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            padding: 0;
            z-index: 1001;
            min-width: 240px;
            max-width: 380px;
            max-height: 500px;
            overflow-y: auto;
        }

        .layer-dropdown.active,
        .location-dropdown.active,
        .search-dropdown.active,
        .filter-dropdown.active {
            display: block;
        }

        .dropdown-header {
            padding: 8px 18px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #f8f9fa;
            border-bottom: 1px solid var(--gray-200);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .dropdown-divider {
            height: 1px;
            margin: 0;
            background: var(--gray-200);
        }

        .layer-dropdown-item,
        .location-dropdown-item {
            display: flex;
            align-items: center;
            padding: 8px 16px;
            cursor: pointer;
            transition: background 0.15s;
        }

        .layer-dropdown-item:hover,
        .location-dropdown-item:hover {
            background: #f5f5f5;
        }

        .layer-icon,
        .location-item-icon {
            width: 28px;
            font-size: 16px;
            color: #555;
        }

        .layer-name,
        .location-item-name {
            flex: 1;
            font-size: 14px;
            color: #333;
        }

        .layer-check {
            color: #ccc;
            font-size: 14px;
        }

        .layer-dropdown-item.active .layer-check {
            color: #0d6efd;
        }

        .location-item-badge {
            font-size: 11px;
            padding: 2px 10px;
            border-radius: 12px;
            background: #e9ecef;
            color: #666;
        }

        .location-item-badge.active {
            background: #0d6efd;
            color: white;
        }

        /* ─── SEARCH ─── */
        .search-tab-btn {
            border: none;
            background: transparent;
            padding: 10px 0;
            font-size: 13px;
            color: #666;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }

        .search-tab-btn.active {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
        }

        .search-results-container {
            max-height: 250px;
            overflow-y: auto;
        }

        .search-result-item {
            padding: 10px 16px;
            border-bottom: 1px solid #f0f0f0;
            cursor: default;
        }

        .search-result-item:hover {
            background: #f8f9fa;
        }

        .search-result-title {
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }

        .search-result-subtitle {
            font-size: 12px;
            color: #888;
            margin-top: 2px;
        }

        .search-result-actions {
            margin-top: 6px;
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .type-badge {
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 12px;
            margin-left: 6px;
            font-weight: 600;
        }

        .type-badge.road { background: #0dcaf0; color: #000; }
        .type-badge.parcel { background: #198754; color: #fff; }
        .type-badge.point { background: #ffc107; color: #000; }
        .type-badge.assessment { background: #0d6efd; color: #fff; }

        /* ─── FILTER ─── */
        .filter-scroll-container {
            max-height: 55vh;
            overflow-y: auto;
            padding: 0;
        }

        .filter-scroll-container::-webkit-scrollbar {
            width: 6px;
        }
        .filter-scroll-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        .filter-scroll-container::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 3px;
        }

        .filter-section {
            padding: 8px 16px;
        }

        .filter-section-header {
            font-size: 11px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .filter-actions {
            padding: 12px 16px;
            border-top: 1px solid #e9ecef;
            background: #f8f9fa;
            border-radius: 0 0 12px 12px;
        }

        .filter-stats {
            font-size: 12px;
            color: #666;
            text-align: center;
            margin-top: 6px;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            background: #f8fafc;
            border-radius: 8px;
            margin: 8px 16px;
            padding: 12px;
            font-size: 12px;
        }

        .quick-stats .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
        }

        .quick-stats .stat-item strong {
            color: #1e293b;
        }

        .quick-stats .stat-item .stat-value {
            color: #0d6efd;
            font-weight: 600;
        }

        /* ─── FULLSCREEN ─── */
        .fullscreen-btn {
            position: absolute;
            right: 20px;
            bottom: 20px;
            z-index: 1000;
            background: white;
            border-radius: 10px;
            padding: 10px;
            cursor: pointer;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
            font-size: 18px;
            transition: all 0.2s;
            border: 1px solid var(--gray-200);
            color: #333;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .fullscreen-btn:hover {
            background: #f0f0f0;
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        #map.fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 9999;
            border-radius: 0;
        }

        .map-card.fullscreen-mode {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 9998;
            border-radius: 0;
            margin: 0;
        }

        .map-card.fullscreen-mode .map-header {
            display: none;
        }

        .map-card.fullscreen-mode #map {
            height: calc(100vh - 5px);
        }

        /* ─── TOAST ─── */
        .toast-container {
            position: fixed;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            pointer-events: none;
        }

        .location-toast {
            display: none;
            background: rgba(0, 0, 0, 0.85);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            white-space: nowrap;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            transition: opacity 0.3s ease;
            pointer-events: none;
            max-width: 90%;
            text-align: center;
            backdrop-filter: blur(10px);
        }

        /* ─── MODAL STYLES ─── */
        .bld-modal-content {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0, 0, 0, .18);
        }

        .bld-modal-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
            border-bottom: none;
            padding: 18px 24px;
            color: #fff;
        }

        .bld-header-inner {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .bld-header-icon {
            width: 44px;
            height: 44px;
            background: rgba(255, 255, 255, .15);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: #fff;
            flex-shrink: 0;
        }

        .bld-modal-title {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 700;
            color: #fff;
        }

        .bld-gisid-badge {
            font-size: .72rem;
            background: rgba(255, 255, 255, .2);
            color: #fff;
            border-radius: 6px;
            padding: 2px 10px;
            display: inline-block;
            margin-top: 4px;
        }

        .bld-image-strip {
            display: flex;
            gap: 0;
            height: 200px;
            background: #0f172a;
        }

        .bld-img-wrap {
            flex: 1;
            position: relative;
            overflow: hidden;
            background: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bld-img-wrap+.bld-img-wrap {
            border-left: 3px solid #fff;
        }

        .bld-img-wrap img {
            height: 100%;
            object-fit: cover;
            transition: transform .4s ease;
        }

        .bld-img-label {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, .65));
            color: #fff;
            font-size: .78rem;
            font-weight: 600;
            padding: 18px 12px 8px;
        }

        .bld-img-wrap .bld-img-empty {
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.9rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .bld-summary-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 0;
            border-bottom: 1px solid var(--gray-200);
            background: #f8fafc;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .bld-summary-card {
            flex: 1 1 120px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-right: 1px solid var(--gray-200);
        }

        .bld-summary-card:last-child {
            border-right: none;
        }

        .bld-summary-icon {
            font-size: 1.3rem;
        }

        .bld-summary-label {
            font-size: .68rem;
            color: #000000;
            font-weight: 600;
            text-transform: uppercase;
        }

        .bld-summary-val {
            font-size: .95rem;
            font-weight: 700;
            color: #1e293b;
            margin-top: 2px;
        }

        .bld-info-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #f8fafc;
            border-radius: 10px;
            padding: 10px 14px;
            border: 1px solid var(--gray-200);
            height: 100%;
        }

        .bld-info-icon {
            font-size: 1rem;
            color: #94a3b8;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .bld-info-label {
            font-size: .68rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
        }

        .bld-info-val {
            font-size: .9rem;
            font-weight: 600;
            color: #1e293b;
            margin-top: 3px;
        }

        .bld-section-divider {
            font-size: .8rem;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: .5px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--gray-200);
            display: flex;
            align-items: center;
        }

        .bld-status-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: .7rem;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 20px;
        }

        .bld-status-tag.complete {
            background: #dcfce7;
            color: #15803d;
        }

        .bld-status-tag.partial {
            background: #fef9c3;
            color: #92400e;
        }

        .bld-status-tag.empty {
            background: #fee2e2;
            color: #b91c1c;
        }

        .bld-btn-save {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            padding: 9px 22px;
            transition: all .2s;
        }

        .bld-btn-save:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, .3);
        }

        .bld-btn-cancel {
            background: #f1f5f9;
            color: #475569;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            padding: 9px 20px;
            transition: all .2s;
        }

        .bld-btn-cancel:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .bld-modal-footer {
            background: #f8fafc;
            border-top: 1px solid var(--gray-200);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .bld-footer-status {
            font-size: .8rem;
            color: #64748b;
        }

        /* ─── POINT DATA CARDS ─── */
        .point-data-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 14px;
            padding: 16px 18px;
            margin-bottom: 12px;
            transition: box-shadow .2s, border-color .2s;
        }

        .point-data-card:hover {
            box-shadow: 0 4px 16px rgba(37, 99, 235, .1);
            border-color: #93c5fd;
        }

        .point-data-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .point-data-card-title {
            font-size: .9rem;
            font-weight: 700;
            color: #1e293b;
        }

        .point-data-card-subtitle {
            font-size: .75rem;
            color: #64748b;
            margin-top: 2px;
        }

        .point-data-card-actions {
            display: flex;
            gap: 6px;
            flex-shrink: 0;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .pdc-action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .9rem;
            cursor: pointer;
            transition: all .2s;
        }

        .pdc-qc-btn {
            background: #fef9c3;
            color: #92400e;
        }

        .pdc-qc-btn:hover {
            background: #92400e;
            color: #fff;
        }

        .pdc-field {
            background: #f8fafc;
            border-radius: 8px;
            padding: 8px 10px;
        }

        .pdc-field-label {
            font-size: .65rem;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
        }

        .pdc-field-val {
            font-size: .82rem;
            color: #1e293b;
            font-weight: 600;
            margin-top: 1px;
        }

        .pdc-field-val.empty {
            color: #cbd5e1;
            font-style: italic;
        }

        .tax-card {
            background: #f8fafc;
            border-radius: 10px;
            padding: 12px 14px;
            border: 1px solid var(--gray-200);
            margin-bottom: 8px;
            height: 100%;
        }

        .tax-card-title {
            font-size: .7rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 6px;
            border-bottom: 1px solid var(--gray-200);
            padding-bottom: 4px;
        }

        .tax-card-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
        }

        .tax-card-label {
            font-size: .7rem;
            color: #94a3b8;
        }

        .tax-card-value {
            font-size: .78rem;
            font-weight: 600;
            color: #1e293b;
        }

        .bv-variation-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            background: #f8fafc;
            border-radius: 10px;
            padding: 12px 16px;
            border: 1px solid var(--gray-200);
            margin-bottom: 12px;
        }

        .bv-variation-card {
            flex: 1;
            min-width: 120px;
        }

        .bv-variation-card .stat-label {
            font-size: .65rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
        }

        .bv-variation-card .stat-value {
            font-size: .9rem;
            font-weight: 700;
            color: #1e293b;
            margin-top: 2px;
        }

        .bv-variation-card .stat-sub {
            font-size: .7rem;
            font-weight: 600;
            color: #94a3b8;
        }

        .point-data-card-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 10px;
        }

        /* ─── RESPONSIVE ─── */
        @media (max-width: 992px) {
            .point-data-card-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            #map { height: 500px; }
            .map-controls-stack { right: 12px; top: 12px; gap: 6px; }
            .layer-toggle-btn, .location-toggle-btn, .search-toggle-btn,
            .label-toggle-btn, .legend-toggle-btn, .threed-toggle-btn,
            .filter-toggle-btn, .fullscreen-btn {
                width: 38px; height: 38px; font-size: 15px; padding: 8px; border-radius: 8px;
            }
            .bld-image-strip { height: 150px; }
            .bld-summary-card { flex: 1 1 45%; }
            .point-data-card-grid { grid-template-columns: 1fr 1fr; }
            .ol-page-header { flex-direction: column; align-items: stretch; text-align: center; }
            .ol-page-header .d-flex { justify-content: center; flex-wrap: wrap; }
        }

        @media (max-width: 480px) {
            #map { height: 400px; }
            .map-controls-stack { right: 8px; top: 8px; gap: 5px; }
            .layer-toggle-btn, .location-toggle-btn, .search-toggle-btn,
            .label-toggle-btn, .legend-toggle-btn, .threed-toggle-btn,
            .filter-toggle-btn, .fullscreen-btn {
                width: 34px; height: 34px; font-size: 13px; padding: 6px; border-radius: 6px;
            }
            .bld-summary-card { flex: 1 1 100%; border-right: none; border-bottom: 1px solid var(--gray-200); }
            .bld-summary-strip { flex-direction: column; }
            .bld-image-strip { height: 120px; flex-direction: column; }
            .bld-img-wrap+.bld-img-wrap { border-left: none; border-top: 3px solid #fff; }
            .point-data-card-grid { grid-template-columns: 1fr; }
            .point-data-card-header { flex-direction: column; gap: 8px; }
            .point-data-card-actions { justify-content: flex-start; }
            .quick-stats { grid-template-columns: 1fr 1fr; font-size: 10px; }
        }
    </style>
@endpush

@section('content')
<div class="ol-page-header">
    <div>
        <h1 class="ol-page-title"><i class="bi bi-map"></i>{{ ucfirst(auth()->user()->role) }} GIS Dashboard</h1>
        <p class="ol-page-sub">{{ now()->format('l, d F Y') }} — {{ auth()->user()->name ?? 'Executive Officer' }}</p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <span class="ds-pill paid"><i class="bi bi-circle-fill" style="font-size:8px;"></i> Live</span>
        <a href="{{ url('usage-variation/' . $ward->id) }}" class="btn btn-success btn-sm">
            <i class="bi bi-bar-chart-line me-1"></i> Usage Variation
        </a>
        <a href="{{ url('area-variation/' . $ward->id) }}" class="btn btn-info btn-sm">
            <i class="bi bi-bounding-box me-1"></i> Area Variation
        </a>
        <a href="{{ url('data-controll/' . $ward->id) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-table me-1"></i> Data Control
        </a>
        <button class="btn btn-warning btn-sm" id="threedToggleBtn">
            <i class="bi bi-box"></i> 3D
        </button>
    </div>
</div>

<div class="map-card" id="mapCard">
    <div class="map-header">
        <span class="badge bg-primary" id="activeLayerBadge">OpenStreetMap</span>
        <span class="text-muted small" id="featureCountBadge">Buildings: 0</span>
    </div>
    <div id="map"></div>
</div>

<!-- ─── MODALS ─── -->
<!-- Building View Modal -->
<div class="modal fade" id="buildingViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content bld-modal-content">
            <div class="modal-header bld-modal-header">
                <div class="bld-header-inner">
                    <div class="bld-header-icon"><i class="bi bi-building"></i></div>
                    <div>
                        <h5 class="bld-modal-title">Building Details</h5>
                        <span class="bld-gisid-badge">GIS ID: <span id="bv_gisid"></span></span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="bld-image-strip">
                <div class="bld-img-wrap" id="bv_img1_wrap">
                    <img id="bv_img1" src="" style="display:none;" onerror="this.style.display='none'; document.getElementById('bv_img1_error').style.display='flex';">
                    <div id="bv_img1_empty" class="bld-img-empty" style="display:none;"><i class="bi bi-image"></i><span>No Image</span></div>
                    <div id="bv_img1_error" class="bld-img-error" style="display:none;"><i class="bi bi-exclamation-triangle-fill"></i><span>Failed to load</span></div>
                    <div class="bld-img-label">Image 1</div>
                </div>
                <div class="bld-img-wrap" id="bv_img2_wrap">
                    <img id="bv_img2" src="" style="display:none;" onerror="this.style.display='none'; document.getElementById('bv_img2_error').style.display='flex';">
                    <div id="bv_img2_empty" class="bld-img-empty" style="display:none;"><i class="bi bi-image"></i><span>No Image</span></div>
                    <div id="bv_img2_error" class="bld-img-error" style="display:none;"><i class="bi bi-exclamation-triangle-fill"></i><span>Failed to load</span></div>
                    <div class="bld-img-label">Image 2</div>
                </div>
            </div>
            <div class="modal-body" style="max-height: 65vh; overflow-y: auto; padding: 20px 24px;">
                <!-- Summary Strip -->
                <div class="bld-summary-strip">
                    <div class="bld-summary-card">
                        <div class="bld-summary-icon">🧾</div>
                        <div>
                            <div class="bld-summary-label">Assessments</div>
                            <div class="bld-summary-val" id="bv_bills">0</div>
                        </div>
                    </div>
                    <div class="bld-summary-card">
                        <div class="bld-summary-icon">🏬</div>
                        <div>
                            <div class="bld-summary-label">Shops</div>
                            <div class="bld-summary-val" id="bv_shops">0</div>
                        </div>
                    </div>
                    <div class="bld-summary-card">
                        <div class="bld-summary-icon">🏢</div>
                        <div>
                            <div class="bld-summary-label">Floors</div>
                            <div class="bld-summary-val" id="bv_floors">0</div>
                        </div>
                    </div>
                    <div class="bld-summary-card">
                        <div class="bld-summary-icon">✅</div>
                        <div>
                            <div class="bld-summary-label">Mapped</div>
                            <div class="bld-summary-val" id="bv_mapped">0</div>
                        </div>
                    </div>
                </div>

                <div id="bv_variation_wrap" style="margin-bottom: 20px;"></div>

                <div class="bld-section-divider mb-3"><i class="bi bi-info-circle me-2"></i>Basic Information</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="bld-info-row"><i class="bi bi-geo-alt bld-info-icon"></i>
                            <div><div class="bld-info-label">Zoneation</div><div class="bld-info-val" id="bv_zone"></div></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="bld-info-row"><i class="bi bi-building bld-info-icon"></i>
                            <div><div class="bld-info-label">Building Name</div><div class="bld-info-val" id="bv_building_name"></div></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="bld-info-row"><i class="bi bi-signpost bld-info-icon"></i>
                            <div><div class="bld-info-label">Road</div><div class="bld-info-val" id="bv_road_name"></div></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="bld-info-row"><i class="bi bi-telephone bld-info-icon"></i>
                            <div><div class="bld-info-label">Phone</div><div class="bld-info-val" id="bv_phone"></div></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="bld-info-row"><i class="bi bi-tag bld-info-icon"></i>
                            <div><div class="bld-info-label">Usage</div><div class="bld-info-val" id="bv_usage"></div></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="bld-info-row"><i class="bi bi-tools bld-info-icon"></i>
                            <div><div class="bld-info-label">Construction</div><div class="bld-info-val" id="bv_construction_type"></div></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="bld-info-row"><i class="bi bi-house bld-info-icon"></i>
                            <div><div class="bld-info-label">Building Type</div><div class="bld-info-val" id="bv_building_type"></div></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="bld-info-row"><i class="bi bi-droplet bld-info-icon"></i>
                            <div><div class="bld-info-label">UGD Status</div><div class="bld-info-val" id="bv_ugd"></div></div>
                        </div>
                    </div>
                </div>

                <div class="bld-section-divider mb-3"><i class="bi bi-check2-square me-2"></i>Amenities</div>
                <div class="mb-4" id="bv_amenities" style="display: flex; flex-wrap: wrap; gap: 6px;"></div>

                <div class="bld-section-divider mb-3"><i class="bi bi-chat-text me-2"></i>Remarks</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="bld-info-row"><i class="bi bi-chat-left-text bld-info-icon"></i>
                            <div><div class="bld-info-label">General Remarks</div><div class="bld-info-val" id="bv_remarks"></div></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bld-info-row"><i class="bi bi-chat-left-text bld-info-icon"></i>
                            <div><div class="bld-info-label">Corporation Remarks</div><div class="bld-info-val" id="bv_corp_remarks"></div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bld-modal-footer">
                <span class="bld-footer-status">Read-only view</span>
                <div>
                    <button type="button" class="btn bld-btn-cancel me-2" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Close
                    </button>
                    <button type="button" class="btn bld-btn-save" id="buildingViewPointsBtn">
                        <i class="bi bi-people me-1"></i>View Assessments
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Point Details Modal -->
<div class="modal fade" id="pointDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content bld-modal-content">
            <div class="modal-header bld-modal-header">
                <div class="bld-header-inner">
                    <div class="bld-header-icon"><i class="bi bi-people"></i></div>
                    <div>
                        <h5 class="bld-modal-title">Assessment Records</h5>
                        <span class="bld-gisid-badge">GIS ID: <span id="pdGisid"></span></span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted small" id="pdBillSummary"></span>
                </div>
                <input type="text" class="form-control bld-input mb-3" id="pointDetailsSearch" placeholder="Search by assessment, owner name, or phone number...">
                <div id="pointDetailsContainer"></div>
            </div>
        </div>
    </div>
</div>

<!-- QC Modal -->
<div class="modal fade" id="qcModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bld-modal-content">
            <div class="modal-header bld-modal-header">
                <div class="bld-header-inner">
                    <div class="bld-header-icon"><i class="bi bi-clipboard-check"></i></div>
                    <h5 class="bld-modal-title">Quality Check</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="qc_point_data_id">
                <p class="text-muted small mb-3"><span id="qc_owner_display" class="fw-semibold"></span> — Assessment <span id="qc_assessment_display" class="fw-semibold"></span></p>
                <div class="mb-3">
                    <label class="bld-form-label">QC Usage</label>
                    <select class="form-select bld-input" id="qcusage">
                        <option value="">Select</option>
                        <option value="Residential">Residential</option>
                        <option value="Commercial">Commercial</option>
                        <option value="Mixed">Mixed</option>
                        <option value="Vacant">Vacant</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="bld-form-label">QC Sq.Feet</label>
                    <input type="number" min="0" class="form-control bld-input" id="qcsqfeet">
                </div>
                <div class="mb-3">
                    <label class="bld-form-label">QC Remarks</label>
                    <textarea class="form-control bld-input" id="qc_remarks" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer bld-modal-footer">
                <button type="button" class="btn bld-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn bld-btn-save" id="saveQcBtn"><i class="bi bi-save me-1"></i>Save QC</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/ol@v10.2.1/dist/ol.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cesium/1.127.0/Cesium.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/ol-cesium@2.14.0/dist/ol-cesium.min.js"></script>

    <script>
        $(document).ready(function() {
            // ─── DATA ───
            let polygons = @json($polygons ?? [], JSON_HEX_TAG);
            let lines = @json($lines ?? [], JSON_HEX_TAG);
            let points = @json($points ?? [], JSON_HEX_TAG);
            let pointDatas = @json($pointDatas ?? [], JSON_HEX_TAG);
            let polygonDatas = @json($polygonDatas ?? [], JSON_HEX_TAG);
            let buildingVariations = @json($buildingVariations ?? [], JSON_HEX_TAG);
            let ward = @json($ward ?? [], JSON_HEX_TAG);

            const usageColors = {
                'RESIDENTIAL': '#4CAF50',
                'COMMERCIAL': '#2196F3',
                'INDUSTRIAL': '#FF9800',
                'INSTITUTIONAL': '#9C27B0',
                'MIXED': '#F44336',
                'GOVERNMENT': '#607D8B',
                'VACANT': '#FFD700',
                'OTHER': '#9E9E9E'
            };

            // ─── IMAGE EXTENT ───
            let imageExtentRaw = [{{ $ward->extent_left ?? 0 }}, {{ $ward->extent_bottom ?? 0 }},
                {{ $ward->extent_right ?? 0 }}, {{ $ward->extent_top ?? 0 }}
            ];

            const isLatLon = imageExtentRaw[0] > -180 && imageExtentRaw[0] < 180 &&
                imageExtentRaw[1] > -90 && imageExtentRaw[1] < 90;

            let imageExtent;
            if (isLatLon) {
                const bl = ol.proj.fromLonLat([imageExtentRaw[0], imageExtentRaw[1]]);
                const tr = ol.proj.fromLonLat([imageExtentRaw[2], imageExtentRaw[3]]);
                imageExtent = [bl[0], bl[1], tr[0], tr[1]];
            } else {
                imageExtent = imageExtentRaw;
            }

            let droneImageURL = "{{ asset($ward->drone_image ?? '') }}";

            // ─── LAYERS ───
            const droneLayer = new ol.layer.Image({
                source: new ol.source.ImageStatic({
                    url: droneImageURL,
                    imageExtent: imageExtent,
                    imageSmoothing: false
                }),
                opacity: 0.90,
                visible: true,
                title: 'Drone View'
            });

            const osmLayer = new ol.layer.Tile({
                title: 'OpenStreetMap',
                type: 'base',
                visible: true,
                source: new ol.source.OSM()
            });

            const satelliteLayer = new ol.layer.Tile({
                title: 'Satellite',
                type: 'base',
                visible: false,
                source: new ol.source.XYZ({
                    url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                    attributions: 'Tiles &copy; Esri'
                })
            });

            const streetViewLayer = new ol.layer.Tile({
                title: 'Street View',
                type: 'base',
                visible: false,
                source: new ol.source.XYZ({
                    url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                    attributions: '&copy; OpenStreetMap'
                })
            });

            // ─── SOURCES ───
            const polygonSource = new ol.source.Vector();
            const lineSource = new ol.source.Vector();
            const positionSource = new ol.source.Vector();

            // ─── 3D MODE VARIABLES ───
            let ol3d = null;
            let is3DMode = false;
            let cesiumViewer = null;
            let cesiumInitialized = false;
            let cesiumLoading = false;

            // ─── STYLES ───
            function createPolygonStyle(feature) {
                const gisid = feature.get('gisid');
                const sqft = feature.get('sqfeet') || '0';
                const polygonData = polygonDatas.find(d => d.gisid == gisid);
                const buildingUsage = polygonData?.building_usage || feature.get('building_usage') || 'OTHER';
                const strokeColor = usageColors[buildingUsage] || '#0d6efd';
                const fillColor = polygonData ? `${strokeColor}33` : 'rgba(13, 110, 253, 0.15)';
                const showLabels = $('#labelToggleBtn').hasClass('active-label');

                let height = null;
                if (is3DMode) {
                    const floors = polygonData?.number_floor || feature.get('floors') || 1;
                    height = Math.max(floors * 3.2, 3.2);
                }

                try {
                    const styles = [
                        new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: strokeColor,
                                width: is3DMode ? 2 : 4,
                                lineJoin: 'round',
                                lineCap: 'round'
                            }),
                            fill: new ol.style.Fill({
                                color: fillColor
                            }),
                            height: height
                        })
                    ];

                    if (showLabels && !is3DMode) {
                        const centerPoint = feature.getGeometry().getInteriorPoint();
                        styles.push(new ol.style.Style({
                            geometry: centerPoint,
                            text: new ol.style.Text({
                                text: gisid + ' GISID\n' + sqft + ' SQFT',
                                font: 'bold 13px Arial',
                                fill: new ol.style.Fill({
                                    color: '#000'
                                }),
                                backgroundFill: new ol.style.Fill({
                                    color: '#fff'
                                }),
                                backgroundStroke: new ol.style.Stroke({
                                    color: '#000',
                                    width: 1
                                }),
                                padding: [4, 6, 4, 6],
                                overflow: true,
                                textAlign: 'center',
                                offsetY: 0
                            })
                        }));
                    }

                    return styles;
                } catch (e) {
                    return new ol.style.Style({
                        stroke: new ol.style.Stroke({
                            color: '#0d6efd',
                            width: 4
                        }),
                        fill: new ol.style.Fill({
                            color: 'rgba(13, 110, 253, 0.15)'
                        }),
                        height: is3DMode ? 3.2 : null
                    });
                }
            }

            function createLineStyle() {
                return new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#ff0000',
                        width: 3
                    })
                });
            }

            // ─── SEARCH INDEX ───
            let searchIndex = [];

            function buildSearchIndex() {
                searchIndex = [];
                polygons.forEach(poly => {
                    try {
                        const coords = JSON.parse(poly.coordinates);
                        searchIndex.push({
                            id: poly.gisid,
                            type: 'polygon',
                            title: `GIS ID: ${poly.gisid}`,
                            subtitle: `Area: ${poly.sqfeet || 0} sqft`,
                            assessment: poly.assessment || '',
                            old_assessment: poly.old_assessment || '',
                            owner_name: poly.owner_name || '',
                            phone_number: poly.phone_number || '',
                            coordinates: coords,
                            geometryType: 'polygon',
                            searchText: `${poly.gisid} ${poly.assessment || ''} ${poly.old_assessment || ''} ${poly.owner_name || ''} ${poly.phone_number || ''} ${poly.sqfeet || ''}`.toLowerCase()
                        });
                    } catch (e) { console.error('Error indexing polygon:', e); }
                });

                lines.forEach(line => {
                    try {
                        const coords = JSON.parse(line.coordinates);
                        searchIndex.push({
                            id: line.gisid,
                            type: 'line',
                            title: `Road: ${line.road_name || line.gisid}`,
                            subtitle: `GIS ID: ${line.gisid}`,
                            road_name: line.road_name || '',
                            coordinates: coords,
                            geometryType: 'line',
                            searchText: `${line.gisid} ${line.road_name || ''}`.toLowerCase()
                        });
                    } catch (e) { console.error('Error indexing line:', e); }
                });

                points.forEach(point => {
                    try {
                        let coords = JSON.parse(point.coordinates);
                        searchIndex.push({
                            id: point.gisid,
                            type: 'point',
                            title: `GIS ID: ${point.gisid}`,
                            subtitle: 'Point Location',
                            coordinates: coords,
                            geometryType: 'point',
                            searchText: `${point.gisid} point`.toLowerCase()
                        });
                    } catch (e) { console.error('Error parsing point:', e); }
                });

                pointDatas.forEach(pd => {
                    try {
                        let pointGisid = pd.point_gisid || '';
                        searchIndex.push({
                            id: pointGisid,
                            type: 'pointdata',
                            title: `Assessment: ${pd.assessment || 'N/A'}`,
                            subtitle: `GIS ID: ${pointGisid} | Owner: ${pd.owner_name || 'N/A'}`,
                            assessment: pd.assessment || '',
                            point_gisid: pointGisid,
                            owner_name: pd.owner_name || '',
                            phone_number: pd.phone_number || '',
                            geometryType: 'point',
                            searchText: `${pointGisid} ${pd.assessment || ''} ${pd.owner_name || ''} ${pd.phone_number || ''}`.toLowerCase()
                        });
                    } catch (e) { console.error('Error indexing point data:', e); }
                });

                console.log('📊 Search Index Built:', searchIndex.length, 'items');
            }

            // ─── LOAD SOURCES ───
            function loadPolygonSource() {
                polygonSource.clear();
                polygons.forEach(poly => {
                    try {
                        let coords = JSON.parse(poly.coordinates);
                        const polygonData = polygonDatas.find(d => d.gisid == poly.gisid);
                        const feature = new ol.Feature({
                            geometry: new ol.geom.Polygon([coords]),
                            gisid: poly.gisid,
                            type: 'polygon',
                            sqfeet: poly.sqfeet || '0',
                            assessment: poly.assessment || '',
                            old_assessment: poly.old_assessment || '',
                            owner_name: poly.owner_name || '',
                            phone_number: poly.phone_number || '',
                            floors: polygonData?.number_floor || 0,
                            originalData: poly
                        });
                        feature.setId(poly.gisid);
                        polygonSource.addFeature(feature);
                    } catch (e) { console.error('polygon parse error:', e); }
                });
                console.log('📊 Polygons loaded:', polygonSource.getFeatures().length);
                updateFeatureCount();
                updateQuickStats();
            }

            function loadLineSource() {
                lineSource.clear();
                lines.forEach(line => {
                    try {
                        let coords = JSON.parse(line.coordinates);
                        let geometry;
                        if (Array.isArray(coords) && coords.length > 0) {
                            if (Array.isArray(coords[0]) && Array.isArray(coords[0][0])) {
                                geometry = new ol.geom.MultiLineString(coords);
                            } else if (Array.isArray(coords[0]) && typeof coords[0][0] === 'number') {
                                geometry = new ol.geom.LineString(coords);
                            } else {
                                geometry = new ol.geom.MultiLineString(coords);
                            }
                        }

                        if (geometry) {
                            const feature = new ol.Feature({
                                geometry: geometry,
                                gisid: line.gisid,
                                type: 'line',
                                road_name: line.road_name || '',
                                originalData: line
                            });
                            feature.setId(line.gisid);
                            lineSource.addFeature(feature);
                        }
                    } catch (e) { console.error('line parse error:', e); }
                });
                console.log('📊 Lines loaded:', lineSource.getFeatures().length);
            }

            function updateFeatureCount() {
                const count = polygonSource.getFeatures().length;
                $('#featureCountBadge').text(`Buildings: ${count}`);
            }

            function updateQuickStats() {
                $('#statTotal').text(polygons.length);
                $('#statSurveyed').text(polygonDatas.length);
                $('#statUnsurveyed').text(polygons.length - polygonDatas.length);
                const variationCount = Object.values(buildingVariations).filter(v => v.usage_status === 'VARIATION').length;
                $('#statVariation').text(variationCount);
            }

            loadPolygonSource();
            loadLineSource();
            buildSearchIndex();

            // ─── CREATE LAYERS ───
            const polygonLayer = new ol.layer.Vector({
                source: polygonSource,
                style: createPolygonStyle,
                visible: true,
                title: 'Polygons'
            });

            const lineLayer = new ol.layer.Vector({
                source: lineSource,
                style: createLineStyle,
                visible: true,
                title: 'Lines'
            });

            const positionLayer = new ol.layer.Vector({
                source: positionSource,
                visible: true,
                zIndex: 100
            });

            // ─── CREATE MAP ───
            const map = new ol.Map({
                target: 'map',
                layers: [osmLayer, satelliteLayer, streetViewLayer, droneLayer, polygonLayer, lineLayer, positionLayer],
                view: new ol.View({
                    center: ol.extent.getCenter(imageExtent),
                    zoom: 18
                })
            });

            // ─── GET MAP CONTAINER ───
            const $mapContainer = $('#map');
            $mapContainer.append(`<div class="map-controls-stack" id="mapControlsStack"></div>`);
            const $stack = $('#mapControlsStack');

            // ─── CONTROLS ───
            // Filter Toggle
            $stack.append(`
                <div class="custom-filter-toggle">
                    <button class="filter-toggle-btn" id="filterToggleBtn" title="Toggle Filters">
                        <i class="bi bi-funnel"></i>
                    </button>
                    <div class="filter-dropdown" id="filterDropdown">
                        <div class="dropdown-header">🔍 Filter Features</div>
                        <div class="filter-scroll-container">
                            <div class="filter-section">
                                <div class="filter-section-header">Building Usage</div>
                                <select class="form-select form-select-sm" id="usageFilter">
                                    <option value="all">All</option>
                                    <option value="RESIDENTIAL">Residential</option>
                                    <option value="COMMERCIAL">Commercial</option>
                                    <option value="INDUSTRIAL">Industrial</option>
                                    <option value="INSTITUTIONAL">Institutional</option>
                                    <option value="MIXED">Mixed</option>
                                    <option value="GOVERNMENT">Government</option>
                                    <option value="VACANT">Vacant</option>
                                    <option value="OTHER">Other</option>
                                </select>
                            </div>
                            <div class="dropdown-divider"></div>
                            <div class="filter-section">
                                <div class="filter-section-header">Usage Variation</div>
                                <select class="form-select form-select-sm" id="usageVariationFilter">
                                    <option value="all">All Buildings</option>
                                    <option value="match">Matching Only</option>
                                    <option value="variation">With Variation Only</option>
                                    <option value="unmapped">Unmapped Buildings</option>
                                </select>
                            </div>
                            <div class="dropdown-divider"></div>
                            <div class="filter-section">
                                <div class="filter-section-header">Area Variation</div>
                                <select class="form-select form-select-sm" id="areaVariationFilter">
                                    <option value="all">All Buildings</option>
                                    <option value="match">Matching Only</option>
                                    <option value="variation">With Variation Only</option>
                                </select>
                            </div>
                            <div class="dropdown-divider"></div>
                            <div class="filter-section">
                                <div class="filter-section-header">Construction Type</div>
                                <select class="form-select form-select-sm" id="constructionFilter">
                                    <option value="all">All</option>
                                    <option value="PERMANENT">Permanent</option>
                                    <option value="SEMI_PERMANENT">Semi Permanent</option>
                                    <option value="VACANT_LAND">Vacant Land</option>
                                    <option value="SHED">Shed</option>
                                    <option value="TEMPORARY">Temporary</option>
                                    <option value="UNDER_CONSTRUCTION">Under Construction</option>
                                </select>
                            </div>
                            <div class="dropdown-divider"></div>
                            <div class="quick-stats" id="quickStats">
                                <div class="stat-item"><strong>Total:</strong> <span class="stat-value" id="statTotal">0</span></div>
                                <div class="stat-item"><strong>Surveyed:</strong> <span class="stat-value" id="statSurveyed">0</span></div>
                                <div class="stat-item"><strong>Unsurveyed:</strong> <span class="stat-value" id="statUnsurveyed">0</span></div>
                                <div class="stat-item"><strong>With Variation:</strong> <span class="stat-value" id="statVariation">0</span></div>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <div class="filter-actions">
                            <button class="btn btn-primary btn-sm w-100" id="applyFiltersBtn">
                                <i class="bi bi-check-circle"></i> Apply Filters
                            </button>
                            <button class="btn btn-outline-secondary btn-sm w-100 mt-2" id="resetFiltersBtn">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset All
                            </button>
                            <div class="filter-stats mt-2" id="filterStats">
                                <span>Showing: <strong id="visibleCount">0</strong> of <strong id="totalCount">0</strong> features</span>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            // Layer Switcher
            $stack.append(`
                <div class="custom-layer-switcher">
                    <button class="layer-toggle-btn" id="layerToggleBtn"><i class="bi bi-layers"></i></button>
                    <div class="layer-dropdown" id="layerDropdown">
                        <div class="dropdown-header">Base Maps</div>
                        <div class="layer-dropdown-item active" data-layer-type="base" data-layer="OpenStreetMap">
                            <div class="layer-icon"><i class="bi bi-map"></i></div>
                            <div class="layer-name">OpenStreetMap</div>
                            <div class="layer-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                        <div class="layer-dropdown-item" data-layer-type="base" data-layer="Satellite">
                            <div class="layer-icon"><i class="bi bi-satellite"></i></div>
                            <div class="layer-name">Satellite</div>
                            <div class="layer-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                        <div class="layer-dropdown-item" data-layer-type="base" data-layer="Street View">
                            <div class="layer-icon"><i class="bi bi-signpost-2"></i></div>
                            <div class="layer-name">Street View</div>
                            <div class="layer-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-header">Overlays</div>
                        <div class="layer-dropdown-item active" data-layer-type="overlay" data-layer="Drone View">
                            <div class="layer-icon"><i class="bi bi-camera-drone"></i></div>
                            <div class="layer-name">Drone View</div>
                            <div class="layer-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-header">Vector Layers</div>
                        <div class="layer-dropdown-item active" data-layer-type="vector" data-layer="Polygons">
                            <div class="layer-icon"><i class="bi bi-pentagon"></i></div>
                            <div class="layer-name">Polygons</div>
                            <div class="layer-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                        <div class="layer-dropdown-item active" data-layer-type="vector" data-layer="Lines">
                            <div class="layer-icon"><i class="bi bi-vector-pen"></i></div>
                            <div class="layer-name">Lines</div>
                            <div class="layer-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                    </div>
                </div>
            `);

            // Location Switcher
            $stack.append(`
                <div class="custom-location-switcher">
                    <button class="location-toggle-btn" id="locationToggleBtn"><i class="bi bi-geo-alt"></i></button>
                    <div class="location-dropdown" id="locationDropdown">
                        <div class="dropdown-header">Location Tools</div>
                        <div class="location-dropdown-item" id="liveLocationItem">
                            <div class="location-item-icon"><i class="bi bi-crosshair2"></i></div>
                            <div class="location-item-name">Live Location</div>
                            <div class="location-item-badge" id="liveLocationBadge">OFF</div>
                        </div>
                        <div class="location-dropdown-item" id="zoomToExtentItem">
                            <div class="location-item-icon"><i class="bi bi-arrows-angle-expand"></i></div>
                            <div class="location-item-name">Zoom to Extent</div>
                        </div>
                    </div>
                </div>
            `);

            // Search
            $stack.append(`
                <div class="custom-search-switcher">
                    <button class="search-toggle-btn" id="searchToggleBtn"><i class="bi bi-search"></i></button>
                    <div class="search-dropdown" id="searchDropdown">
                        <div class="p-3">
                            <input type="text" id="gisSearchInput" class="form-control" placeholder="Search by GIS ID, Assessment, Owner...">
                        </div>
                        <div id="searchResults" class="search-results-container"></div>
                    </div>
                </div>
            `);

            // Label Toggle
            $stack.append(`
                <div class="custom-label-toggle">
                    <button class="label-toggle-btn active-label" id="labelToggleBtn" title="Toggle Labels">
                        <i class="bi bi-fonts"></i>
                    </button>
                </div>
            `);

            // Legend Toggle
            $stack.append(`
                <div class="custom-legend-toggle">
                    <button class="legend-toggle-btn" id="legendToggleBtn" title="Toggle Legend">
                        <i class="bi bi-list-ul"></i>
                    </button>
                </div>
            `);

            // Fullscreen
            $mapContainer.append(`
                <button class="fullscreen-btn" id="fullscreenBtn">
                    <i class="bi bi-arrows-fullscreen"></i>
                </button>
            `);

            // ─── 3D IMPLEMENTATION ───
            function initCesium() {
                if (cesiumInitialized) return true;
                if (cesiumLoading) return false;

                cesiumLoading = true;

                try {
                    if (typeof Cesium === 'undefined') {
                        console.error('❌ Cesium library not loaded');
                        showToast('⚠️ 3D library loading... Please wait and try again.', 3000);
                        cesiumLoading = false;
                        return false;
                    }

                    if (typeof olcs === 'undefined') {
                        console.error('❌ ol-cesium library not loaded');
                        showToast('⚠️ 3D library not available. Please refresh the page.', 3000);
                        cesiumLoading = false;
                        return false;
                    }

                    Cesium.Ion.defaultAccessToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiI1ZDQ3MmI5MC04ZjY4LTQyMjMtODA4Ni1jZmVjZTI1NDI1ODAiLCJpZCI6MjU5MDQ1LCJpYXQiOjE3Mzc2MjE5Nzd9.FqIhYpDCCR-sxsN_Cu5qXrVvKqG8OxOYzuDdxMUVh2Y';

                    ol3d = new olcs.OLCesium({
                        map: map,
                        target: 'map'
                    });

                    cesiumViewer = ol3d.getCesiumScene();

                    if (cesiumViewer) {
                        cesiumViewer.skyBox.show = true;
                        cesiumViewer.backgroundColor = new Cesium.Color(0.1, 0.15, 0.2, 1);
                    }

                    ol3d.setEnabled(false);

                    cesiumInitialized = true;
                    cesiumLoading = false;
                    console.log('✅ OLCesium initialized successfully');
                    return true;

                } catch (error) {
                    console.error('❌ OLCesium initialization failed:', error);
                    cesiumLoading = false;
                    showToast('⚠️ 3D mode error: ' + error.message, 4000);
                    return false;
                }
            }

            function toggle3DMode() {
                const threedBtn = $('#threedToggleBtn');

                if (cesiumLoading) {
                    showToast('⏳ 3D is loading, please wait...', 2000);
                    return;
                }

                if (!cesiumInitialized) {
                    const success = initCesium();
                    if (!success) {
                        showToast('❌ Failed to initialize 3D mode', 3000);
                        return;
                    }
                }

                if (!ol3d) {
                    showToast('❌ 3D engine not available', 3000);
                    return;
                }

                try {
                    if (!is3DMode) {
                        showToast('🌍 Switching to 3D mode...', 2000);
                        ol3d.setEnabled(true);

                        const extent = imageExtent;
                        const center = ol.extent.getCenter(extent);
                        const centerLonLat = ol.proj.toLonLat(center);

                        if (cesiumViewer) {
                            cesiumViewer.camera.flyTo({
                                destination: Cesium.Cartesian3.fromDegrees(
                                    centerLonLat[0],
                                    centerLonLat[1],
                                    150
                                ),
                                orientation: {
                                    heading: Cesium.Math.toRadians(0),
                                    pitch: Cesium.Math.toRadians(-45),
                                    roll: 0
                                },
                                duration: 2.0
                            });
                        }

                        is3DMode = true;
                        threedBtn.addClass('active-3d');
                        threedBtn.html('<i class="bi bi-box-fill"></i> 3D');

                        polygonLayer.getSource().forEachFeature(function(feature) {
                            feature.changed();
                        });
                        polygonLayer.changed();

                        showToast('🌍 3D mode activated! Buildings extruded.', 3000);

                    } else {
                        showToast('🗺️ Switching back to 2D...', 2000);
                        ol3d.setEnabled(false);

                        is3DMode = false;
                        threedBtn.removeClass('active-3d');
                        threedBtn.html('<i class="bi bi-box"></i> 3D');

                        map.getView().fit(imageExtent, {
                            padding: [50, 50, 50, 50],
                            duration: 1000
                        });

                        polygonLayer.getSource().forEachFeature(function(feature) {
                            feature.changed();
                        });
                        polygonLayer.changed();

                        showToast('🗺️ 2D mode restored', 2000);
                    }
                } catch (error) {
                    console.error('❌ Toggle 3D failed:', error);
                    showToast('⚠️ 3D toggle failed: ' + error.message, 3000);
                    if (ol3d) {
                        try { ol3d.setEnabled(false); } catch (e) {}
                    }
                    is3DMode = false;
                    threedBtn.removeClass('active-3d');
                    threedBtn.html('<i class="bi bi-box"></i> 3D');
                }
            }

            // ─── EVENT HANDLERS ───

            // 3D Toggle Button (both header and map control)
            $(document).on('click', '#threedToggleBtn', function(e) {
                e.stopPropagation();
                toggle3DMode();
            });

            // Layer Toggle
            $(document).on('click', '.layer-toggle-btn', function(e) {
                e.stopPropagation();
                $('.layer-dropdown').toggleClass('active');
                $('.location-dropdown').removeClass('active');
                $('.search-dropdown').removeClass('active');
                $('#filterDropdown').removeClass('active');
            });

            // Location Toggle
            $(document).on('click', '.location-toggle-btn', function(e) {
                e.stopPropagation();
                $('.location-dropdown').toggleClass('active');
                $('.layer-dropdown').removeClass('active');
                $('.search-dropdown').removeClass('active');
                $('#filterDropdown').removeClass('active');
            });

            // Search Toggle
            $(document).on('click', '.search-toggle-btn', function(e) {
                e.stopPropagation();
                $('.search-dropdown').toggleClass('active');
                $('.layer-dropdown').removeClass('active');
                $('.location-dropdown').removeClass('active');
                $('#filterDropdown').removeClass('active');
            });

            // Filter Toggle
            $(document).on('click', '#filterToggleBtn', function(e) {
                e.stopPropagation();
                $('#filterDropdown').toggleClass('active');
                $(this).toggleClass('active-filter');
                $('.layer-dropdown').removeClass('active');
                $('.location-dropdown').removeClass('active');
                $('.search-dropdown').removeClass('active');
                if ($('#filterDropdown').hasClass('active')) {
                    updateFilterStats();
                }
            });

            // Close dropdowns on outside click
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.custom-layer-switcher').length) {
                    $('.layer-dropdown').removeClass('active');
                }
                if (!$(e.target).closest('.custom-location-switcher').length) {
                    $('.location-dropdown').removeClass('active');
                }
                if (!$(e.target).closest('.custom-search-switcher').length) {
                    $('.search-dropdown').removeClass('active');
                }
                if (!$(e.target).closest('.custom-filter-toggle').length) {
                    $('#filterDropdown').removeClass('active');
                    $('#filterToggleBtn').removeClass('active-filter');
                }
            });

            // Layer Dropdown Items
            $(document).on('click', '.layer-dropdown-item', function(e) {
                e.stopPropagation();
                const layerType = $(this).data('layer-type');
                const layerTitle = $(this).data('layer');

                if (layerType === 'base') {
                    let layer;
                    if (layerTitle === 'OpenStreetMap') layer = osmLayer;
                    else if (layerTitle === 'Satellite') layer = satelliteLayer;
                    else if (layerTitle === 'Street View') layer = streetViewLayer;

                    if (layer) {
                        [osmLayer, satelliteLayer, streetViewLayer].forEach(l => l.setVisible(l === layer));
                        $('#activeLayerBadge').text(layerTitle);
                        $('.layer-dropdown-item[data-layer-type="base"]').removeClass('active');
                        $(this).addClass('active');
                    }
                } else if (layerTitle === 'Drone View') {
                    const visible = !droneLayer.getVisible();
                    droneLayer.setVisible(visible);
                    $(this).toggleClass('active', visible);
                } else if (layerType === 'vector') {
                    let layer;
                    if (layerTitle === 'Polygons') layer = polygonLayer;
                    else if (layerTitle === 'Lines') layer = lineLayer;

                    if (layer) {
                        const visible = !layer.getVisible();
                        layer.setVisible(visible);
                        $(this).toggleClass('active', visible);
                    }
                }
            });

            // Label Toggle
            $('#labelToggleBtn').on('click', function() {
                $(this).toggleClass('active-label');
                polygonLayer.setStyle(createPolygonStyle);
                polygonLayer.changed();
            });

            // Legend Toggle
            $('#legendToggleBtn').on('click', function() {
                const usageLegend = Object.entries(usageColors).map(([usage, color]) => `
                    <div style="display:flex;align-items:center;margin-bottom:8px;">
                        <span style="display:inline-block;width:20px;height:20px;background:${color};border:2px solid #fff;border-radius:4px;margin-right:10px;box-shadow:0 0 2px rgba(0,0,0,0.4);"></span>
                        <strong>${usage}</strong>
                    </div>
                `).join('');

                Swal.fire({
                    title: 'Building Usage Legend',
                    width: 500,
                    html: `
                        <div style="text-align:left;font-size:14px;">
                            <h6 style="margin-bottom:10px;color:#198754;">Building Usage Colors</h6>
                            ${usageLegend}
                            <hr style="margin:15px 0;">
                            <div style="display:flex;align-items:center;margin-bottom:8px;">
                                <span style="display:inline-block;width:20px;height:20px;background:rgba(13,110,253,0.15);border-radius:4px;border:2px solid #0d6efd;margin-right:10px;"></span>
                                Polygon (Building)
                            </div>
                            <div style="display:flex;align-items:center;margin-bottom:8px;">
                                <span style="display:inline-block;width:20px;height:4px;background:#ff0000;border-radius:2px;margin-right:10px;"></span>
                                Lines (Roads)
                            </div>
                        </div>
                    `,
                    icon: 'info',
                    confirmButtonText: 'Close',
                    confirmButtonColor: '#0d6efd'
                });
            });

            // ─── LOCATION ───
            $('#zoomToExtentItem').on('click', function() {
                map.getView().fit(imageExtent, {
                    padding: [50, 50, 50, 50],
                    duration: 1000,
                    maxZoom: 20
                });
                showToast('📍 Zoomed to ward extent', 2000);
                $('.location-dropdown').removeClass('active');
            });

            let watchId = null;
            let isLiveLocation = false;

            $('#liveLocationItem').on('click', function() {
                if (!navigator.geolocation) {
                    showToast('❌ Geolocation not supported');
                    return;
                }

                isLiveLocation = !isLiveLocation;
                const $badge = $('#liveLocationBadge');
                const $btn = $('#locationToggleBtn');

                if (isLiveLocation) {
                    $badge.text('ON').addClass('active');
                    $btn.addClass('active-location');
                    showToast('📍 Getting your location...', 2000);

                    navigator.geolocation.getCurrentPosition(
                        function(pos) {
                            const projected = ol.proj.fromLonLat([pos.coords.longitude, pos.coords.latitude]);

                            const positionFeature = new ol.Feature({
                                geometry: new ol.geom.Point(projected)
                            });
                            positionFeature.setStyle(new ol.style.Style({
                                image: new ol.style.Circle({
                                    radius: 12,
                                    fill: new ol.style.Fill({ color: '#0d6efd' }),
                                    stroke: new ol.style.Stroke({ color: '#ffffff', width: 3 })
                                })
                            }));
                            positionSource.clear();
                            positionSource.addFeature(positionFeature);

                            map.getView().animate({
                                center: projected,
                                zoom: 19,
                                duration: 500
                            });

                            showToast('📍 Live location activated', 2000);

                            if (!watchId) {
                                watchId = navigator.geolocation.watchPosition(
                                    function(newPos) {
                                        const p = ol.proj.fromLonLat([newPos.coords.longitude, newPos.coords.latitude]);
                                        positionSource.clear();
                                        const pf = new ol.Feature({
                                            geometry: new ol.geom.Point(p)
                                        });
                                        pf.setStyle(new ol.style.Style({
                                            image: new ol.style.Circle({
                                                radius: 12,
                                                fill: new ol.style.Fill({ color: '#0d6efd' }),
                                                stroke: new ol.style.Stroke({ color: '#ffffff', width: 3 })
                                            })
                                        }));
                                        positionSource.addFeature(pf);
                                    },
                                    function(error) { console.error('Watch error:', error); },
                                    { enableHighAccuracy: true, timeout: 15000, maximumAge: 30000 }
                                );
                            }
                        },
                        function(error) {
                            isLiveLocation = false;
                            $badge.text('OFF').removeClass('active');
                            $btn.removeClass('active-location');
                            showToast('❌ Could not get location: ' + error.message, 4000);
                        },
                        { enableHighAccuracy: true, timeout: 10000 }
                    );
                } else {
                    $badge.text('OFF').removeClass('active');
                    $btn.removeClass('active-location');
                    showToast('📍 Live location deactivated', 2000);
                    if (watchId) {
                        navigator.geolocation.clearWatch(watchId);
                        watchId = null;
                    }
                    positionSource.clear();
                }
                $('.location-dropdown').removeClass('active');
            });

            // ─── SEARCH ───
            $('#gisSearchInput').on('keyup', function() {
                const value = $(this).val();
                if (!value || value.length < 1) {
                    $('#searchResults').html('');
                    return;
                }
                const results = searchGIS(value);
                let html = '';
                if (!results.length) {
                    html = '<div class="p-3 text-center text-muted">No results found</div>';
                } else {
                    results.slice(0, 10).forEach(item => {
                        const displayTitle = item.type === 'pointdata' ?
                            `${item.title} | Assessment: ${item.assessment}` : item.title;
                        const displaySubtitle = item.type === 'pointdata' ?
                            `Point GIS ID: ${item.point_gisid || 'N/A'}${item.owner_name ? ' | Owner: ' + item.owner_name : ''}` :
                            item.subtitle;
                        const icon = item.geometryType === 'point' ? 'geo-alt' :
                            item.geometryType === 'polygon' ? 'pentagon' : 'vector-pen';

                        let badgeClass = '';
                        let badgeText = '';
                        if (item.type === 'line') { badgeClass = 'road'; badgeText = 'Road'; }
                        else if (item.type === 'polygon') { badgeClass = 'parcel'; badgeText = 'Building'; }
                        else if (item.type === 'point') { badgeClass = 'point'; badgeText = 'Point'; }
                        else if (item.type === 'pointdata') { badgeClass = 'assessment'; badgeText = 'Assessment'; }

                        html += `
                            <div class="search-result-item" data-id="${item.id}" data-type="${item.type}">
                                <div class="search-result-title">
                                    <i class="bi bi-${icon} me-2"></i>${displayTitle}
                                    <span class="type-badge ${badgeClass}">${badgeText}</span>
                                </div>
                                <div class="search-result-subtitle">${displaySubtitle}</div>
                                <div class="search-result-actions">
                                    <button class="btn btn-sm btn-success zoom-btn" data-id="${item.id}" data-type="${item.type}">Zoom</button>
                                    <button class="btn btn-sm btn-primary view-btn" data-id="${item.id}" data-type="${item.type}">View</button>
                                </div>
                            </div>`;
                    });
                }
                $('#searchResults').html(html);
            });

            $(document).on('click', '.zoom-btn', function(e) {
                e.stopPropagation();
                const id = $(this).data('id');
                let item = searchIndex.find(i => i.id == id);
                if (item) {
                    const features = polygonSource.getFeatures().filter(f => f.get('gisid') == id);
                    if (features.length > 0) {
                        const coords = ol.extent.getCenter(features[0].getGeometry().getExtent());
                        map.getView().animate({ center: coords, zoom: 20, duration: 1000 });
                        showToast(`📍 Zoomed to GIS ID: ${id}`, 2000);
                    } else {
                        showToast(`⚠️ No location found for GIS ID: ${id}`, 3000);
                    }
                }
                $('.search-dropdown').removeClass('active');
                $('#gisSearchInput').val('');
                $('#searchResults').html('');
            });

            $(document).on('click', '.view-btn', function(e) {
                e.stopPropagation();
                const id = $(this).data('id');
                const polygonData = polygonDatas.find(d => d.gisid == id);
                if (polygonData) {
                    showBuildingView(polygonData);
                } else {
                    showToast(`📋 Feature ID: ${id}`, 2000);
                }
                $('.search-dropdown').removeClass('active');
                $('#gisSearchInput').val('');
                $('#searchResults').html('');
            });

            function searchGIS(value) {
                const v = value.toString().toLowerCase().trim();
                if (!v) return [];
                return searchIndex.filter(item =>
                    (item.id && item.id.toString().toLowerCase().includes(v)) ||
                    (item.assessment && item.assessment.toString().toLowerCase().includes(v)) ||
                    (item.old_assessment && item.old_assessment.toString().toLowerCase().includes(v)) ||
                    (item.owner_name && item.owner_name.toString().toLowerCase().includes(v)) ||
                    (item.phone_number && item.phone_number.toString().toLowerCase().includes(v)) ||
                    (item.title && item.title.toLowerCase().includes(v)) ||
                    (item.subtitle && item.subtitle.toLowerCase().includes(v))
                );
            }

            // ─── FILTER FUNCTIONS ───
            function updateFilterStats() {
                const total = polygonSource.getFeatures().length;
                $('#visibleCount').text(total);
                $('#totalCount').text(polygons.length);
                $('#filterStats').html(`Showing: <strong>${total}</strong> of <strong>${polygons.length}</strong> features`);
            }

            function applyFilters() {
                const selectedUsage = $('#usageFilter').val();
                const usageVariation = $('#usageVariationFilter').val();
                const areaVariation = $('#areaVariationFilter').val();
                const selectedConstruction = $('#constructionFilter').val();

                let filteredGisids = new Set(polygons.map(p => p.gisid));

                // Usage Filter
                if (selectedUsage !== 'all') {
                    const usageGisids = new Set(polygonDatas.filter(d => d.building_usage === selectedUsage).map(d => d.gisid));
                    filteredGisids = new Set([...filteredGisids].filter(g => usageGisids.has(g)));
                }

                // Usage Variation Filter
                if (usageVariation !== 'all') {
                    let variationGisids = new Set();
                    if (usageVariation === 'match') {
                        variationGisids = new Set(Object.values(buildingVariations).filter(v => v.usage_status === 'MATCH').map(v => v.gisid));
                    } else if (usageVariation === 'variation') {
                        variationGisids = new Set(Object.values(buildingVariations).filter(v => v.usage_status === 'VARIATION').map(v => v.gisid));
                    } else if (usageVariation === 'unmapped') {
                        const mappedGisids = new Set(polygonDatas.map(d => d.gisid));
                        variationGisids = new Set(polygons.filter(p => !mappedGisids.has(p.gisid)).map(p => p.gisid));
                    }
                    filteredGisids = new Set([...filteredGisids].filter(g => variationGisids.has(g)));
                }

                // Area Variation Filter
                if (areaVariation !== 'all') {
                    let areaGisids = new Set();
                    if (areaVariation === 'match') {
                        areaGisids = new Set(Object.values(buildingVariations).filter(v => v.area_status === 'MATCH').map(v => v.gisid));
                    } else if (areaVariation === 'variation') {
                        areaGisids = new Set(Object.values(buildingVariations).filter(v => v.area_status === 'VARIATION').map(v => v.gisid));
                    }
                    filteredGisids = new Set([...filteredGisids].filter(g => areaGisids.has(g)));
                }

                // Construction Filter
                if (selectedConstruction !== 'all') {
                    const constructionGisids = new Set(polygonDatas.filter(d => d.construction_type === selectedConstruction).map(d => d.gisid));
                    filteredGisids = new Set([...filteredGisids].filter(g => constructionGisids.has(g)));
                }

                // Apply filters to polygon source
                polygonSource.clear();
                polygons.forEach(poly => {
                    if (filteredGisids.has(poly.gisid)) {
                        try {
                            let coords = JSON.parse(poly.coordinates);
                            const feature = new ol.Feature({
                                geometry: new ol.geom.Polygon([coords]),
                                gisid: poly.gisid,
                                type: 'polygon',
                                sqfeet: poly.sqfeet || '0',
                                assessment: poly.assessment || '',
                                old_assessment: poly.old_assessment || '',
                                owner_name: poly.owner_name || '',
                                phone_number: poly.phone_number || '',
                                floors: polygonDatas.find(d => d.gisid === poly.gisid)?.number_floor || 0,
                                originalData: poly
                            });
                            feature.setId(poly.gisid);
                            feature.setStyle(createPolygonStyle(feature));
                            polygonSource.addFeature(feature);
                        } catch (e) { console.error('polygon parse error:', e); }
                    }
                });

                const visibleCount = polygonSource.getFeatures().length;
                $('#visibleCount').text(visibleCount);
                $('#totalCount').text(polygons.length);
                $('#filterStats').html(`Showing: <strong>${visibleCount}</strong> of <strong>${polygons.length}</strong> features`);
                $('#featureCountBadge').text(`Buildings: ${visibleCount}`);
                polygonLayer.changed();
                polygonSource.changed();
                showToast(`🔍 Filter applied: ${visibleCount} visible`, 2000);
            }

            function resetAllFilters() {
                $('#usageFilter, #constructionFilter, #usageVariationFilter, #areaVariationFilter').val('all');
                polygonSource.clear();
                polygons.forEach(poly => {
                    try {
                        let coords = JSON.parse(poly.coordinates);
                        const feature = new ol.Feature({
                            geometry: new ol.geom.Polygon([coords]),
                            gisid: poly.gisid,
                            type: 'polygon',
                            sqfeet: poly.sqfeet || '0',
                            assessment: poly.assessment || '',
                            old_assessment: poly.old_assessment || '',
                            owner_name: poly.owner_name || '',
                            phone_number: poly.phone_number || '',
                            floors: polygonDatas.find(d => d.gisid === poly.gisid)?.number_floor || 0,
                            originalData: poly
                        });
                        feature.setId(poly.gisid);
                        feature.setStyle(createPolygonStyle(feature));
                        polygonSource.addFeature(feature);
                    } catch (e) { console.error('polygon parse error:', e); }
                });
                const allFeatures = polygonSource.getFeatures().length;
                $('#visibleCount').text(allFeatures);
                $('#totalCount').text(allFeatures);
                $('#filterStats').html(`Showing: <strong>${allFeatures}</strong> of <strong>${allFeatures}</strong> features`);
                $('#featureCountBadge').text(`Buildings: ${allFeatures}`);
                polygonLayer.changed();
                polygonSource.changed();
                showToast('🔄 All filters reset', 2000);
            }

            $('#applyFiltersBtn').on('click', applyFilters);
            $('#resetFiltersBtn').on('click', resetAllFilters);

            // ─── FULLSCREEN ───
            let isFullscreen = false;
            $('#fullscreenBtn').on('click', function() {
                const $card = $('#mapCard');
                const $container = $('#map');
                const $btn = $(this);

                if (!isFullscreen) {
                    $card.addClass('fullscreen-mode');
                    $container.addClass('fullscreen');
                    $btn.html('<i class="bi bi-fullscreen-exit"></i>');
                    isFullscreen = true;
                } else {
                    $card.removeClass('fullscreen-mode');
                    $container.removeClass('fullscreen');
                    $btn.html('<i class="bi bi-arrows-fullscreen"></i>');
                    isFullscreen = false;
                }

                setTimeout(function() {
                    map.updateSize();
                    if (ol3d && is3DMode) {
                        try {
                            ol3d.getCesiumScene().render();
                        } catch (e) {}
                    }
                }, 150);
            });

            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && isFullscreen) {
                    $('#fullscreenBtn').click();
                }
            });

            // ─── TOAST ───
            function showToast(message, duration = 3000) {
                $('#locationToast').remove();
                if (!$('.toast-container').length) {
                    $('body').append('<div class="toast-container"></div>');
                }
                const $toast = $('<div id="locationToast" class="location-toast">' + message + '</div>');
                $('.toast-container').append($toast);
                $toast.css({ 'display': 'block', 'opacity': 0, 'transform': 'translateX(-50%) translateY(10px)' });
                setTimeout(function() {
                    $toast.css({ 'opacity': 1, 'transform': 'translateX(-50%) translateY(0)' });
                }, 50);
                clearTimeout($toast.data('timeout'));
                $toast.data('timeout', setTimeout(function() {
                    $toast.css({ 'opacity': 0, 'transform': 'translateX(-50%) translateY(10px)' });
                    setTimeout(function() { $toast.remove(); }, 300);
                }, duration));
            }

            // ─── BUILDING VIEW ───
            function showBuildingView(item) {
                $('#bv_gisid').text(item.gisid || '-');
                $('#bv_zone').text(item.zone || item.building_zone || '-');
                $('#bv_building_name').text(item.building_name || '-');
                $('#bv_road_name').text(item.road_name || '-');
                $('#bv_phone').text(item.phone || '-');
                $('#bv_usage').text(item.building_usage || '-');
                $('#bv_construction_type').text(item.construction_type || '-');
                $('#bv_building_type').text(item.building_type || '-');
                $('#bv_ugd').text(item.ugd || '-');
                $('#bv_bills').text(item.number_bill || 0);
                $('#bv_shops').text(item.number_shop || 0);
                $('#bv_floors').text(item.number_floor || 0);
                const mappedCount = pointDatas.filter(pd => pd.point_gisid == item.gisid).length;
                $('#bv_mapped').text(mappedCount);

                // Variation
                const variation = buildingVariations[item.gisid];
                if (variation) {
                    const areaBadgeClass = variation.area_status === 'MATCH' ? 'complete' : 'empty';
                    const usageBadgeClass = variation.usage_status === 'MATCH' ? 'complete' : 'empty';
                    $('#bv_variation_wrap').html(`
                        <div class="bv-variation-strip">
                            <div class="bv-variation-card">
                                <div class="stat-label">Building Area</div>
                                <div class="stat-value">${variation.building_area} <span class="stat-sub">sqft</span></div>
                            </div>
                            <div class="bv-variation-card">
                                <div class="stat-label">Assessment Area</div>
                                <div class="stat-value">${variation.assessment_area} <span class="stat-sub">sqft</span></div>
                            </div>
                            <div class="bv-variation-card">
                                <div class="stat-label">Area Variation</div>
                                <div class="stat-value">${variation.area_variation} <span class="stat-sub">(${variation.variation_percentage}%)</span></div>
                                <span class="bld-status-tag ${areaBadgeClass}">${variation.area_status}</span>
                            </div>
                            <div class="bv-variation-card">
                                <div class="stat-label">Usage Check</div>
                                <span class="bld-status-tag ${usageBadgeClass}">${variation.usage_status}</span>
                            </div>
                        </div>
                    `);
                } else {
                    $('#bv_variation_wrap').html('');
                }

                // Amenities
                const amenities = [
                    ['Lift Room', item.liftroom],
                    ['Head Room', item.headroom],
                    ['Overhead Tank', item.overhead_tank],
                    ['Rainwater Harvesting', item.rainwater_harvesting],
                    ['Parking', item.parking],
                    ['Ramp', item.ramp],
                    ['Hoarding', item.hoarding],
                    ['CCTV', item.cctv],
                    ['Cell Tower', item.cell_tower],
                    ['Solar Panel', item.solar_panel],
                    ['Water Connection', item.water_connection]
                ];
                let amenHtml = '';
                let hasAmenities = false;
                amenities.forEach(([label, val]) => {
                    if (val === 'Yes' || val === true || val === 1) {
                        hasAmenities = true;
                        amenHtml += `<span class="bld-status-tag complete"><i class="bi bi-check-circle"></i> ${label}</span>`;
                    }
                });
                $('#bv_amenities').html(hasAmenities ? amenHtml : '<span class="text-muted small">No amenities recorded</span>');

                // Remarks
                $('#bv_remarks').text(item.remarks || '—');
                $('#bv_corp_remarks').text(item.corporationremarks || '—');

                // Images
                const assetUrl = window.assetUrl || "{{ asset('') }}";

                function loadImage(imgId, emptyId, errorId, imagePath) {
                    const $img = $('#' + imgId);
                    const $empty = $('#' + emptyId);
                    const $error = $('#' + errorId);

                    if (imagePath) {
                        const fullPath = imagePath.startsWith('http') ? imagePath : assetUrl + '/' + imagePath.replace(/^\/+/, '');
                        $img.attr('src', fullPath).show();
                        $empty.hide();
                        $error.hide();
                        $img.off('error').on('error', function() {
                            $(this).hide();
                            $empty.hide();
                            $error.show();
                        });
                        $img.off('load').on('load', function() {
                            $(this).show();
                            $empty.hide();
                            $error.hide();
                        });
                    } else {
                        $img.hide();
                        $empty.show();
                        $error.hide();
                    }
                }

                loadImage('bv_img1', 'bv_img1_empty', 'bv_img1_error', item.image);
                loadImage('bv_img2', 'bv_img2_empty', 'bv_img2_error', item.image2);

                $('#buildingViewPointsBtn').off('click').on('click', function() {
                    bootstrap.Modal.getInstance(document.getElementById('buildingViewModal')).hide();
                    openPointDetails(item.gisid);
                });

                const modal = new bootstrap.Modal(document.getElementById('buildingViewModal'));
                modal.show();
            }

            // ─── POINT DETAILS ───
            function openPointDetails(gisid) {
                $('#pdGisid').text(gisid);
                $('#pointDetailsSearch').val('');

                const records = pointDatas.filter(pd => pd.point_gisid == gisid);
                renderPointDetails(records);
                const building = polygonDatas.find(p => p.gisid == gisid);
                const billCount = building ? (building.number_bill || 0) : 0;
                $('#pdBillSummary').text(`${records.length} of ${billCount} bills mapped`);

                const modal = new bootstrap.Modal(document.getElementById('pointDetailsModal'));
                modal.show();
            }

            function renderPointDetails(records) {
                if (!records || !records.length) {
                    $('#pointDetailsContainer').html('<div class="text-center text-muted py-4"><i class="bi bi-inbox fs-2"></i><p class="mt-2 mb-0">No assessment records found</p></div>');
                    return;
                }

                const v = (val) => (val === null || val === undefined || val === '') ? '<span class="text-muted">-</span>' : val;

                let html = '';
                records.forEach(record => {
                    const pd = record;
                    const qcFilled = [pd.qcusage, pd.qcsqfeet, pd.qc_remarks].filter(val => val !== null && val !== '' && val !== undefined).length;
                    const qcClass = qcFilled === 3 ? 'complete' : qcFilled === 0 ? 'empty' : 'partial';
                    const qcLabel = qcFilled === 3 ? 'QC Complete' : qcFilled === 0 ? 'QC Pending' : 'QC Partial';

                    html += `
                        <div class="point-data-card" data-id="${pd.id}">
                            <div class="point-data-card-header">
                                <div>
                                    <div class="point-data-card-title">${v(pd.owner_name)}</div>
                                    <div class="point-data-card-subtitle">Assessment: ${v(pd.assessment)} • Door: ${v(pd.new_door_no || pd.old_door_no)}</div>
                                </div>
                                <div class="point-data-card-actions">
                                    <span class="bld-status-tag ${qcClass}" style="margin-right:6px;">${qcLabel}</span>
                                    <button class="pdc-action-btn pdc-qc-btn" title="Quality Check" data-id="${pd.id}" data-qc-btn><i class="bi bi-clipboard-check"></i></button>
                                </div>
                            </div>

                            <div class="tax-card-title mt-2"><i class="bi bi-person-badge me-1"></i>Assessment Details</div>
                            <div class="point-data-card-grid">
                                <div class="pdc-field"><div class="pdc-field-label">Assessment Type</div><div class="pdc-field-val">${v(pd.assessment_type)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Old Assessment</div><div class="pdc-field-val">${v(pd.old_assessment)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Bill Usage</div><div class="pdc-field-val">${v(pd.bill_usage)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Plot Area</div><div class="pdc-field-val">${v(pd.plot_area)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Phone</div><div class="pdc-field-val">${v(pd.phone_number)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Floor</div><div class="pdc-field-val">${v(pd.floor)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Half Year Tax</div><div class="pdc-field-val">${v(pd.halfyeartax)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Balance</div><div class="pdc-field-val">${v(pd.balance)}</div></div>
                            </div>

                            <div class="tax-card-title mt-2"><i class="bi bi-clipboard-check me-1"></i>QC Details</div>
                            <div class="point-data-card-grid">
                                <div class="pdc-field"><div class="pdc-field-label">QC Usage</div><div class="pdc-field-val ${!pd.qcusage ? 'empty' : ''}">${v(pd.qcusage)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">QC Sq.Feet</div><div class="pdc-field-val ${!pd.qcsqfeet ? 'empty' : ''}">${v(pd.qcsqfeet)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">QC By</div><div class="pdc-field-val">${v(pd.qc_name)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">QC Remarks</div><div class="pdc-field-val">${v(pd.qc_remarks)}</div></div>
                            </div>
                        </div>`;
                });

                $('#pointDetailsContainer').html(html);

                $('#pointDetailsSearch').off('input').on('input', function() {
                    const searchVal = $(this).val().toLowerCase();
                    if (!searchVal) { renderPointDetails(records); return; }
                    const filtered = records.filter(record => {
                        return (record.assessment || '').toString().toLowerCase().includes(searchVal) ||
                            (record.owner_name || '').toLowerCase().includes(searchVal) ||
                            (record.phone_number || '').toString().toLowerCase().includes(searchVal);
                    });
                    renderPointDetails(filtered);
                });
            }

            // ─── QC MODAL ───
            function openQcModal(id) {
                const record = pointDatas.find(r => r.id == id);
                if (!record) { showToast('Could not find this assessment record.', 3000); return; }
                $('#qc_point_data_id').val(id);
                $('#qc_owner_display').text(record.owner_name || '');
                $('#qc_assessment_display').text(record.assessment || '');
                $('#qcusage').val(record.qcusage || '');
                $('#qcsqfeet').val(record.qcsqfeet || '');
                $('#qc_remarks').val(record.qc_remarks || '');
                const modal = new bootstrap.Modal(document.getElementById('qcModal'));
                modal.show();
            }

            $(document).on('click', '.pdc-qc-btn', function() {
                openQcModal($(this).data('id'));
            });

            $('#saveQcBtn').on('click', function() {
                const id = $('#qc_point_data_id').val();
                const $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

                $.ajax({
                    url: `/point-data/${id}/qc`,
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        qcusage: $('#qcusage').val(),
                        qcsqfeet: $('#qcsqfeet').val(),
                        qc_remarks: $('#qc_remarks').val(),
                        ward_id: {{ $ward->id }}
                    },
                    success: function(res) {
                        const idx = pointDatas.findIndex(p => p.id == id);
                        if (idx > -1) pointDatas[idx] = res.point_data;
                        $('#qcModal').modal('hide');
                        showToast('QC data saved successfully!', 3000);
                        const gisid = $('#pdGisid').text();
                        if (gisid) {
                            const records = pointDatas.filter(pd => pd.point_gisid == gisid);
                            renderPointDetails(records);
                        }
                    },
                    error: function(xhr) {
                        showToast(xhr.responseJSON?.message || 'Failed to save QC data.', 4000);
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i>Save QC');
                    }
                });
            });

            // ─── MAP CLICK HANDLER ───
            const selectInteraction = new ol.interaction.Select({
                layers: [polygonLayer, lineLayer],
                style: new ol.style.Style({
                    stroke: new ol.style.Stroke({ color: '#0066cc', width: 3, lineDash: [4, 4] }),
                    fill: new ol.style.Fill({ color: 'rgba(0,102,204,0.1)' })
                })
            });

            selectInteraction.on('select', function(e) {
                if (e.selected.length > 0) {
                    const feature = e.selected[0];
                    const gisid = feature.get('gisid');
                    const polygonData = polygonDatas.find(d => d.gisid == gisid);
                    if (polygonData) {
                        showBuildingView(polygonData);
                    } else {
                        showToast(`📍 Feature GIS ID: ${gisid}`, 2000);
                    }
                    setTimeout(() => selectInteraction.getFeatures().clear(), 100);
                }
            });

            map.addInteraction(selectInteraction);

            // ─── INIT ───
            setTimeout(updateFilterStats, 500);

            // Check 3D libraries
            setTimeout(function() {
                if (typeof olcs !== 'undefined' && typeof Cesium !== 'undefined') {
                    console.log('✅ 3D libraries loaded successfully');
                    $('#threedToggleBtn').show();
                } else {
                    console.warn('⚠️ 3D libraries not fully loaded');
                    $('#threedToggleBtn').attr('title', '3D mode unavailable');
                }
            }, 2000);

            console.log('✅ GIS Dashboard initialized successfully!');
            console.log('📊 Search Index Size:', searchIndex.length);
            console.log('📊 Polygons:', polygons.length);
            console.log('📊 Lines:', lines.length);
            console.log('📊 Point Data:', pointDatas.length);
        });
    </script>
@endpush

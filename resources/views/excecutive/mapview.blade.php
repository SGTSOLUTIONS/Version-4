@extends('layouts.office')

@section('title', 'Executive GIS Dashboard')
@section('page_title', 'Executive GIS Dashboard')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@latest/ol.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cesium.com/downloads/cesiumjs/releases/1.127/Build/Cesium/Widgets/widgets.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

    <style>
        * {
            box-sizing: border-box;
        }

        .dropdown-header {
            padding: 8px 18px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #f8f9fa;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .dropdown-divider {
            height: 1px;
            margin: 0;
            background: #e5e7eb;
        }

        .map-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
            position: relative;
        }

        .map-header {
            padding: 14px 18px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .map-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
        }

        #map {
            width: 100%;
            height: 800px;
            transition: all 0.3s ease;
            position: relative;
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

        .map-card.fullscreen-mode #map {
            height: calc(100vh - 5px);
        }

        #cesiumContainer {
            display: none;
            width: 100%;
            height: 800px;
            position: relative;
        }

        .map-card.fullscreen-mode #cesiumContainer {
            height: calc(100vh - 5px);
        }

        /* ─── Controls ─── */
        .custom-layer-switcher {
            position: absolute;
            right: 30px;
            top: 20px;
            z-index: 1000;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        }

        .custom-location-switcher {
            position: absolute;
            right: 30px;
            top: 74px;
            z-index: 1000;
        }

        .custom-search-switcher {
            position: absolute;
            right: 30px;
            top: 130px;
            z-index: 1000;
        }

        .custom-edit-toggle {
            position: absolute;
            right: 30px;
            top: 190px;
            z-index: 1000;
        }

        .custom-label-toggle {
            position: absolute;
            right: 30px;
            top: 246px;
            z-index: 1000;
        }

        .custom-legend-toggle {
            position: absolute;
            right: 30px;
            top: 302px;
            z-index: 1000;
        }

        .custom-3d-toggle {
            position: absolute;
            right: 30px;
            top: 358px;
            z-index: 1000;
            display: block;
        }

        .fullscreen-btn {
            position: absolute;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            width: 44px;
            height: 44px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 1px solid #e5e7eb;
            color: #1e293b;
            font-size: 1.2rem;
            transition: all 0.2s ease;
        }

        .fullscreen-btn:hover {
            background: #f8fafc;
            transform: scale(1.02);
        }

        .cesium-info-box {
            position: absolute;
            top: 12px;
            left: 12px;
            z-index: 999;
            background: rgba(15, 23, 42, 0.85);
            color: white;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 12px;
            max-width: 260px;
            line-height: 1.5;
        }

        /* ─── Ward Navigation ─── */
        .ward-navigation .btn {
            border-radius: 8px;
            font-weight: 600;
            padding: 6px 16px;
            transition: all 0.2s ease;
        }

        .ward-navigation .btn-primary {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none;
        }

        .ward-navigation .btn-primary:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .ward-navigation .btn-outline-secondary:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
        }

        .ward-navigation .badge.bg-primary {
            background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
        }

        #wardProgress {
            font-weight: 600;
            font-size: 0.8rem;
            padding: 4px 12px;
            min-width: 50px;
            text-align: center;
        }

        /* ─── Filter Section ─── */
        .filter-section {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            padding: 16px 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        }

        .filter-section .form-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .filter-section .form-select,
        .filter-section .form-control {
            font-size: 0.85rem;
            border-radius: 8px;
            border-color: #e5e7eb;
        }

        .filter-section .form-select:focus,
        .filter-section .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* ─── Export Button Styles ─── */
        .export-btn-group {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .export-btn-group .btn {
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.75rem;
            padding: 5px 12px;
            transition: all 0.2s ease;
        }

        .export-btn-group .btn-excel {
            background: #217346;
            color: white;
            border: none;
        }

        .export-btn-group .btn-excel:hover {
            background: #1a5c38;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(33, 115, 70, 0.3);
        }

        .export-btn-group .btn-pdf {
            background: #dc3545;
            color: white;
            border: none;
        }

        .export-btn-group .btn-pdf:hover {
            background: #b02a37;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .filter-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-actions .divider {
            width: 1px;
            height: 30px;
            background: #e5e7eb;
            margin: 0 4px;
        }

        /* ─── Usage Legend ─── */
        .usage-legend {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            padding: 10px 16px;
            margin-bottom: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        }

        .usage-legend .legend-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 6px;
            background: #f8fafc;
            transition: all 0.2s;
            cursor: default;
        }

        .usage-legend .legend-item:hover {
            background: #f1f5f9;
        }

        .usage-legend .color-dot {
            width: 14px;
            height: 14px;
            border-radius: 4px;
            flex-shrink: 0;
        }

        .usage-legend .legend-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #1e293b;
        }

        .usage-legend .legend-count {
            font-size: 0.65rem;
            background: #e2e8f0;
            color: #64748b;
            padding: 1px 7px;
            border-radius: 10px;
            font-weight: 700;
        }

        /* ─── Stat Strip ─── */
        .stat-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 18px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            transition: box-shadow .2s, transform .2s;
        }

        .stat-card:hover {
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        .stat-icon-blue { background: #eff6ff; color: #2563eb; }
        .stat-icon-green { background: #f0fdf4; color: #16a34a; }
        .stat-icon-purple { background: #f5f3ff; color: #7c3aed; }
        .stat-icon-amber { background: #fffbeb; color: #d97706; }
        .stat-icon-red { background: #fef2f2; color: #dc2626; }
        .stat-icon-cyan { background: #ecfeff; color: #0891b2; }
        .stat-icon-pink { background: #fdf2f8; color: #db2777; }

        .stat-label {
            font-size: .68rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .4px;
            line-height: 1;
        }

        .stat-value {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1e293b;
            margin-top: 3px;
        }

        .stat-sub {
            font-size: .72rem;
            font-weight: 600;
            color: #94a3b8;
        }

        /* ─── Layer Controls ─── */
        .layer-toggle-btn,
        .location-toggle-btn,
        .search-toggle-btn,
        .edit-toggle-btn,
        .label-toggle-btn,
        .legend-toggle-btn,
        .threed-toggle-btn {
            width: 44px;
            height: 44px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid #e5e7eb;
            color: #1e293b;
            font-size: 1.2rem;
        }

        .layer-toggle-btn:hover,
        .location-toggle-btn:hover,
        .search-toggle-btn:hover,
        .edit-toggle-btn:hover,
        .label-toggle-btn:hover,
        .legend-toggle-btn:hover,
        .threed-toggle-btn:hover,
        .fullscreen-btn:hover {
            background: #f8fafc;
            transform: scale(1.02);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .label-toggle-btn.active-label {
            background: #eff6ff;
            border-color: #3b82f6;
            color: #2563eb;
        }

        .legend-toggle-btn.active-legend {
            background: #eff6ff;
            border-color: #3b82f6;
            color: #2563eb;
        }

        .location-toggle-btn.active-location,
        .search-toggle-btn.active-search,
        .edit-toggle-btn.active-edit {
            background: #eff6ff;
            border-color: #3b82f6;
            color: #2563eb;
        }

        .threed-toggle-btn.active-3d {
            background: #eff6ff;
            border-color: #3b82f6;
            color: #2563eb;
        }

        .layer-dropdown,
        .location-dropdown,
        .search-dropdown,
        .edit-dropdown {
            position: absolute;
            top: 52px;
            right: 0;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s ease;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            max-height: 0;
            min-width: 240px;
        }

        .layer-dropdown.show,
        .location-dropdown.show,
        .search-dropdown.show,
        .edit-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            max-height: 500px;
            overflow-y: auto !important;
            overflow-x: hidden;
        }

        .layer-dropdown-item,
        .location-dropdown-item,
        .edit-dropdown-item {
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: background 0.15s;
            border-left: 3px solid transparent;
            background: white;
        }

        .layer-dropdown-item:hover,
        .location-dropdown-item:hover,
        .edit-dropdown-item:hover {
            background: #f8fafc;
        }

        .layer-dropdown-item.active,
        .location-dropdown-item.active,
        .edit-dropdown-item.active {
            background: #eff6ff;
            border-left-color: #3b82f6;
        }

        .layer-check {
            margin-left: auto;
            color: #3b82f6;
            opacity: 0;
        }

        .layer-dropdown-item.active .layer-check {
            opacity: 1;
        }

        .location-item-badge {
            font-size: 0.65rem;
            padding: 2px 7px;
            border-radius: 20px;
            background: #e2e8f0;
            color: #64748b;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .location-dropdown-item.active .location-item-badge {
            background: #dbeafe;
            color: #2563eb;
        }

        .track-pulse {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4);
            animation: pulse-ring 1.4s infinite;
        }

        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.5); }
            70% { box-shadow: 0 0 0 7px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }

        .location-toast {
            position: absolute;
            bottom: 74px;
            left: 50%;
            transform: translateX(-50%) translateY(10px);
            background: rgba(15, 23, 42, 0.88);
            color: white;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 500;
            white-space: nowrap;
            z-index: 1001;
            opacity: 0;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .location-toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        /* ─── Search ─── */
        .search-result-item {
            padding: 10px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: #1e293b;
        }

        .search-result-subtitle {
            font-size: 0.7rem;
            color: #64748b;
        }

        .search-results-container {
            max-height: 300px;
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* ── Edit Controls ── */
        .edit-controls {
            position: absolute;
            bottom: 120px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1001;
            background: white;
            padding: 12px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            display: none;
            align-items: center;
            gap: 12px;
            border: 1px solid #e5e7eb;
            animation: slideUp 0.3s ease;
        }

        .edit-controls.show {
            display: flex;
        }

        .edit-controls .btn {
            padding: 6px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .edit-controls .btn-save {
            background: #22c55e;
            color: white;
            border: none;
        }

        .edit-controls .btn-save:hover {
            background: #16a34a;
        }

        .edit-controls .btn-cancel {
            background: #e5e7eb;
            color: #374151;
            border: none;
        }

        .edit-controls .btn-cancel:hover {
            background: #d1d5db;
        }

        .split-action-btn {
            position: absolute;
            bottom: 120px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1001;
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
            display: none;
            align-items: center;
            gap: 10px;
            border: none;
            animation: slideUp 0.3s ease;
        }

        .split-action-btn.show {
            display: flex;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateX(-50%) translateY(20px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }

        .split-action-btn .close-btn {
            font-size: 1.2rem;
            opacity: 0.7;
            cursor: pointer;
            padding: 0 5px;
            transition: opacity 0.2s;
        }

        .split-action-btn .close-btn:hover {
            opacity: 1;
        }

        .delete-type-btn {
            flex: 1;
            padding: 10px 14px;
            border-radius: 10px;
            border: 2px solid #e5e7eb;
            background: #f8fafc;
            cursor: pointer;
            text-align: center;
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748b;
            transition: all 0.2s;
            user-select: none;
        }

        .delete-type-btn:hover {
            border-color: #3b82f6;
            background: #eff6ff;
            color: #2563eb;
        }

        .delete-type-btn.active {
            border-color: #3b82f6 !important;
            background: #eff6ff !important;
            color: #2563eb !important;
        }

        .draw-mode {
            cursor: crosshair !important;
        }

        .split-mode {
            cursor: crosshair !important;
        }

        .edit-mode {
            cursor: pointer !important;
        }

        /* ── Modal Styles ── */
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
            letter-spacing: .3px;
        }

        .bld-gisid-badge {
            font-size: .72rem;
            background: rgba(255, 255, 255, .2);
            color: #fff;
            border-radius: 6px;
            padding: 2px 10px;
            display: inline-block;
            margin-top: 4px;
            letter-spacing: .4px;
        }

        .bld-image-strip {
            display: flex;
            gap: 0;
            height: 220px;
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
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .4s ease;
        }

        .bld-img-wrap:hover img {
            transform: scale(1.04);
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
            letter-spacing: .3px;
        }

        .bld-img-wrap .bld-img-empty {
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.9rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .bld-img-wrap .bld-img-empty i {
            font-size: 2rem;
            opacity: 0.5;
        }

        .bld-img-wrap .bld-img-error {
            color: #ef4444;
            font-size: 0.85rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .bld-summary-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 0;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
        }

        .bld-summary-card {
            flex: 1 1 120px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-right: 1px solid #e5e7eb;
        }

        .bld-summary-card:last-child {
            border-right: none;
        }

        .bld-summary-icon {
            font-size: 1.3rem;
            line-height: 1;
        }

        .bld-summary-label {
            font-size: .68rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .4px;
            line-height: 1;
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
            border: 1px solid #e5e7eb;
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
            letter-spacing: .4px;
            line-height: 1;
        }

        .bld-info-val {
            font-size: .9rem;
            font-weight: 600;
            color: #1e293b;
            margin-top: 3px;
            word-break: break-word;
        }

        .bld-section-divider {
            font-size: .8rem;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: .5px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
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
            letter-spacing: .3px;
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
            font-size: .875rem;
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
            font-size: .875rem;
            transition: all .2s;
        }

        .bld-btn-cancel:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .bld-modal-footer {
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .bld-footer-status {
            font-size: .8rem;
            color: #64748b;
        }

        .bld-empty-state {
            text-align: center;
            padding: 40px 20px;
        }

        /* ── Point Data Cards ── */
        .point-data-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 16px 18px;
            margin-bottom: 12px;
            position: relative;
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

        .pdc-edit-btn {
            background: #eff6ff;
            color: #2563eb;
        }

        .pdc-edit-btn:hover {
            background: #2563eb;
            color: #fff;
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
            letter-spacing: .3px;
        }

        .pdc-field-val {
            font-size: .82rem;
            color: #1e293b;
            font-weight: 600;
            margin-top: 1px;
            word-break: break-word;
        }

        .pdc-field-val.empty {
            color: #cbd5e1;
            font-style: italic;
        }

        /* ── Tax Cards ── */
        .tax-card {
            background: #f8fafc;
            border-radius: 10px;
            padding: 12px 14px;
            border: 1px solid #e5e7eb;
            margin-bottom: 8px;
            height: 100%;
        }

        .tax-card-title {
            font-size: .7rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .3px;
            margin-bottom: 6px;
            border-bottom: 1px solid #e5e7eb;
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

        /* ── Infrastructure Legend ── */
        .infrastructure-legend {
            position: absolute;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            padding: 12px 16px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.15);
            max-height: 400px;
            overflow-y: auto;
            min-width: 180px;
            max-width: 240px;
            font-size: 12px;
            border: 1px solid #e5e7eb;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s;
            pointer-events: none;
        }

        .infrastructure-legend.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            pointer-events: auto;
        }

        .infrastructure-legend .legend-title {
            font-weight: 700;
            margin-bottom: 10px;
            font-size: 14px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .infrastructure-legend .legend-item {
            display: flex;
            align-items: center;
            padding: 4px 0;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .infrastructure-legend .legend-item .color-dot {
            display: inline-block;
            width: 14px;
            height: 14px;
            border-radius: 3px;
            margin-right: 10px;
            flex-shrink: 0;
        }

        .infrastructure-legend .legend-item .count-badge {
            background: #f3f4f6;
            padding: 1px 8px;
            border-radius: 12px;
            font-size: 11px;
            color: #6b7280;
            margin-left: auto;
        }

        .infrastructure-legend .legend-item.inactive {
            opacity: 0.4;
        }

        .infrastructure-legend .toggle-all-btn {
            background: #f1f5f9;
            border: none;
            border-radius: 6px;
            padding: 2px 10px;
            font-size: 11px;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s;
        }

        .infrastructure-legend .toggle-all-btn:hover {
            background: #e2e8f0;
        }

        .infra-prop-table {
            width: 100%;
            font-size: 0.85rem;
        }

        .infra-prop-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #f1f5f9;
        }

        .infra-prop-table .label-cell {
            font-weight: 600;
            color: #64748b;
            width: 40%;
        }

        .infra-prop-table .value-cell {
            color: #1e293b;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            #map { height: 600px; }
            .bld-image-strip { height: 150px; }
            .bld-summary-card { flex: 1 1 45%; }
            .point-data-card-grid { grid-template-columns: 1fr 1fr; }
            .bld-modal-footer { flex-direction: column; gap: 10px; }
            .point-data-card-header { flex-direction: column; gap: 8px; }
            .point-data-card-actions { justify-content: flex-start; }

            .custom-layer-switcher,
            .custom-location-switcher,
            .custom-search-switcher,
            .custom-edit-toggle,
            .custom-label-toggle,
            .custom-legend-toggle,
            .custom-3d-toggle {
                right: 10px;
            }

            .custom-layer-switcher { top: 10px; }
            .custom-location-switcher { top: 58px; }
            .custom-search-switcher { top: 106px; }
            .custom-edit-toggle { top: 154px; }
            .custom-label-toggle { top: 202px; }
            .custom-legend-toggle { top: 250px; }
            .custom-3d-toggle { top: 298px; }

            .layer-toggle-btn,
            .location-toggle-btn,
            .search-toggle-btn,
            .edit-toggle-btn,
            .label-toggle-btn,
            .legend-toggle-btn,
            .fullscreen-btn,
            .threed-toggle-btn {
                width: 38px;
                height: 38px;
                font-size: 1rem;
                border-radius: 10px;
            }

            .search-dropdown { width: min(320px, calc(100vw - 40px)); }
            .infrastructure-legend {
                left: 10px;
                right: 10px;
                bottom: 10px;
                width: auto;
                max-width: none;
                min-width: 0;
                max-height: 45vh;
            }
            .stat-strip { grid-template-columns: repeat(2, 1fr); }
            .filter-section .row>div { margin-bottom: 8px; }
            .filter-actions { flex-direction: column; align-items: stretch; }
            .filter-actions .divider { display: none; }
            .export-btn-group { justify-content: center; }
        }

        @media (max-width: 480px) {
            .infrastructure-legend { max-height: 40vh; font-size: 11px; }
            .infrastructure-legend .legend-title { font-size: 13px; }
            .stat-strip { grid-template-columns: 1fr; }
        }

        @media (max-width: 992px) {
            .stat-strip { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
@endpush

@section('content')
    <div class="ol-page-header">
        <div>
            <h1 class="ol-page-title">Executive GIS Dashboard</h1>
            <p class="ol-page-sub">{{ now()->format('l, d F Y') }} — {{ auth()->user()->name ?? 'Executive Officer' }}</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="ds-pill paid"><i class="bi bi-circle-fill" style="font-size:8px;"></i> Live</span>
        </div>
    </div>

    <!-- ─── WARD NAVIGATION ─── -->
    <div class="ward-navigation mb-3">
        <div class="d-flex align-items-center justify-content-between p-3 bg-white rounded-3 border" style="border-color: #e5e7eb !important;">
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary rounded-pill px-3 py-2" style="font-size:0.85rem;">
                        <i class="bi bi-geo-alt me-1"></i> Ward {{ $ward->ward_no }}
                    </span>
                    <span class="text-muted small"><i class="bi bi-building me-1"></i> {{ $ward->zone->zone_name ?? 'N/A' }}</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('commissioner.map', ['id' => $ward->id + 1]) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-arrow-right-circle me-1"></i> Next Ward
                </a>
                <span class="badge bg-light text-dark ms-1" id="wardProgress">
                    <span id="currentWardIndex">1</span>/<span id="totalWardsCount">1</span>
                </span>
            </div>
        </div>
    </div>

    <!-- ─── WARD ANALYTICS STRIP ─── -->
    <div class="stat-strip">
        <div class="stat-card"><div class="stat-icon stat-icon-blue"><i class="bi bi-building"></i></div><div><div class="stat-label">Total Buildings</div><div class="stat-value">{{ $analytics['total_buildings'] ?? 0 }}</div></div></div>
        <div class="stat-card"><div class="stat-icon stat-icon-green"><i class="bi bi-check2-circle"></i></div><div><div class="stat-label">Surveyed Buildings</div><div class="stat-value">{{ $analytics['surveyed_buildings'] ?? 0 }}</div></div></div>
        <div class="stat-card"><div class="stat-icon stat-icon-purple"><i class="bi bi-graph-up"></i></div><div><div class="stat-label">Survey Progress</div><div class="stat-value">{{ $analytics['survey_percentage'] ?? 0 }}%</div></div></div>
        <div class="stat-card"><div class="stat-icon stat-icon-cyan"><i class="bi bi-file-earmark-text"></i></div><div><div class="stat-label">Assessments Mapped</div><div class="stat-value">{{ $analytics['total_surveyed_assessments'] ?? 0 }}</div></div></div>
        <div class="stat-card"><div class="stat-icon stat-icon-amber"><i class="bi bi-rulers"></i></div><div><div class="stat-label">Area Variation</div><div class="stat-value">{{ $analytics['area_variation_count'] ?? 0 }} <span class="stat-sub">({{ $analytics['area_variation_percentage'] ?? 0 }}%)</span></div></div></div>
        <div class="stat-card"><div class="stat-icon stat-icon-red"><i class="bi bi-tags"></i></div><div><div class="stat-label">Usage Variation</div><div class="stat-value">{{ $analytics['usage_variation_count'] ?? 0 }} <span class="stat-sub">({{ $analytics['usage_variation_percentage'] ?? 0 }}%)</span></div></div></div>
        <div class="stat-card"><div class="stat-icon stat-icon-blue"><i class="bi bi-bounding-box"></i></div><div><div class="stat-label">Total Building Area</div><div class="stat-value">{{ number_format($analytics['total_building_area'] ?? 0, 0) }} <span class="stat-sub">sqft</span></div></div></div>
        <div class="stat-card"><div class="stat-icon stat-icon-green"><i class="bi bi-clipboard-data"></i></div><div><div class="stat-label">Total Assessment Area</div><div class="stat-value">{{ number_format($analytics['total_assessment_area'] ?? 0, 0) }} <span class="stat-sub">sqft</span></div></div></div>
    </div>

    <!-- ─── FILTER SECTION ─── -->
    <div class="filter-section">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label"><i class="bi bi-tags me-1"></i>Usage Filter</label>
                <select id="usageFilter" class="form-select form-select-sm">
                    <option value="all">All Usages</option>
                    @if (isset($availableUsages) && count($availableUsages) > 0)
                        @foreach ($availableUsages as $usage)
                            <option value="{{ $usage }}">{{ ucfirst(strtolower($usage)) }}</option>
                        @endforeach
                    @else
                        <option value="RESIDENTIAL">Residential</option>
                        <option value="COMMERCIAL">Commercial</option>
                        <option value="INDUSTRIAL">Industrial</option>
                        <option value="INSTITUTIONAL">Institutional</option>
                        <option value="MIXED">Mixed</option>
                        <option value="GOVERNMENT">Government</option>
                        <option value="VACANT">Vacant</option>
                        <option value="OTHER">Other</option>
                    @endif
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label"><i class="bi bi-rulers me-1"></i>Area Range (sqft)</label>
                <div class="d-flex gap-2">
                    <input type="number" id="areaMin" class="form-control form-control-sm" placeholder="Min" value="{{ $areaStats['min'] ?? 0 }}" min="0">
                    <input type="number" id="areaMax" class="form-control form-control-sm" placeholder="Max" value="{{ $areaStats['max'] ?? 0 }}" min="0">
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label"><i class="bi bi-arrow-left-right me-1"></i>Usage Variation</label>
                <select id="usageVariationFilter" class="form-select form-select-sm">
                    <option value="all">All Buildings</option>
                    <option value="match">Matching Only</option>
                    <option value="variation">With Variation Only</option>
                </select>
            </div>

            <div class="col-md-3">
                <div class="filter-actions">
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-primary btn-sm" id="applyFiltersBtn"><i class="bi bi-funnel me-1"></i>Apply</button>
                        <button class="btn btn-outline-secondary btn-sm" id="resetFiltersBtn" title="Reset filters"><i class="bi bi-arrow-counterclockwise"></i></button>
                        <button class="btn btn-outline-danger btn-sm" id="clearFiltersBtn" title="Clear all filters"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="divider"></div>
                    <div class="export-btn-group">
                        <button class="btn btn-excel btn-sm" id="exportExcelBtn" title="Export to Excel"><i class="bi bi-file-earmark-excel me-1"></i>Excel</button>
                        <button class="btn btn-pdf btn-sm" id="exportPdfBtn" title="Export to PDF"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</button>
                    </div>
                </div>
                <div class="mt-1 text-end">
                    <span class="text-muted small" id="buildingCountDisplay">Showing: {{ count($buildingData['buildings'] ?? []) }} buildings</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── USAGE LEGEND ─── -->
    <div class="usage-legend">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="fw-semibold small me-2"><i class="bi bi-palette me-1"></i>Usage Legend:</span>
            @if (isset($buildingData['usage_colors']) && isset($buildingData['usage_counts']))
                @foreach ($buildingData['usage_colors'] as $usage => $color)
                    @if (in_array($usage, $availableUsages ?? []))
                        <span class="legend-item">
                            <span class="color-dot" style="background:{{ $color }};"></span>
                            <span class="legend-label">{{ ucfirst(strtolower($usage)) }}</span>
                            <span class="legend-count">{{ $buildingData['usage_counts'][$usage] ?? 0 }}</span>
                        </span>
                    @endif
                @endforeach
            @else
                <span class="legend-item"><span class="color-dot" style="background:#4CAF50;"></span><span class="legend-label">Residential</span><span class="legend-count">0</span></span>
                <span class="legend-item"><span class="color-dot" style="background:#2196F3;"></span><span class="legend-label">Commercial</span><span class="legend-count">0</span></span>
                <span class="legend-item"><span class="color-dot" style="background:#FF9800;"></span><span class="legend-label">Industrial</span><span class="legend-count">0</span></span>
                <span class="legend-item"><span class="color-dot" style="background:#9C27B0;"></span><span class="legend-label">Institutional</span><span class="legend-count">0</span></span>
                <span class="legend-item"><span class="color-dot" style="background:#F44336;"></span><span class="legend-label">Mixed</span><span class="legend-count">0</span></span>
                <span class="legend-item"><span class="color-dot" style="background:#607D8B;"></span><span class="legend-label">Government</span><span class="legend-count">0</span></span>
                <span class="legend-item" style="background:#FFFBEB; border:1px solid #FCD34D;"><span class="color-dot" style="background:#FFD700;"></span><span class="legend-label">Vacant</span><span class="legend-count">0</span></span>
                <span class="legend-item"><span class="color-dot" style="background:#9E9E9E;"></span><span class="legend-label">Other</span><span class="legend-count">0</span></span>
            @endif
        </div>
    </div>

    <!-- ─── MAP ─── -->
    <div class="map-card" id="mapCard">
        <div class="map-header">
            <h5 class="map-title"><i class="bi bi-geo-alt-fill text-primary me-2"></i>Executive GIS Dashboard</h5>
            <span class="badge bg-primary" id="activeLayerBadge">OpenStreetMap</span>
        </div>
        <div id="map"></div>
        <div id="cesiumContainer"></div>
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
                <div class="bld-summary-strip">
                    <div class="bld-summary-card"><div class="bld-summary-icon">🧾</div><div><div class="bld-summary-label">Bills</div><div class="bld-summary-val" id="bv_bills">0</div></div></div>
                    <div class="bld-summary-card"><div class="bld-summary-icon">🏬</div><div><div class="bld-summary-label">Shops</div><div class="bld-summary-val" id="bv_shops">0</div></div></div>
                    <div class="bld-summary-card"><div class="bld-summary-icon">🏢</div><div><div class="bld-summary-label">Floors</div><div class="bld-summary-val" id="bv_floors">0</div></div></div>
                    <div class="bld-summary-card"><div class="bld-summary-icon">✅</div><div><div class="bld-summary-label">Mapped</div><div class="bld-summary-val" id="bv_mapped">0</div></div></div>
                </div>
                <div class="p-3 pb-0" id="bv_variation_wrap"></div>
                <div class="modal-body p-4">
                    <div class="bld-section-divider mb-3"><i class="bi bi-info-circle me-2"></i>Basic Information</div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3"><div class="bld-info-row"><i class="bi bi-geo-alt bld-info-icon"></i><div><div class="bld-info-label">Zone</div><div class="bld-info-val" id="bv_zone"></div></div></div></div>
                        <div class="col-md-3"><div class="bld-info-row"><i class="bi bi-building bld-info-icon"></i><div><div class="bld-info-label">Building Name</div><div class="bld-info-val" id="bv_building_name"></div></div></div></div>
                        <div class="col-md-3"><div class="bld-info-row"><i class="bi bi-signpost bld-info-icon"></i><div><div class="bld-info-label">Road</div><div class="bld-info-val" id="bv_road_name"></div></div></div></div>
                        <div class="col-md-3"><div class="bld-info-row"><i class="bi bi-telephone bld-info-icon"></i><div><div class="bld-info-label">Phone</div><div class="bld-info-val" id="bv_phone"></div></div></div></div>
                        <div class="col-md-3"><div class="bld-info-row"><i class="bi bi-tag bld-info-icon"></i><div><div class="bld-info-label">Usage</div><div class="bld-info-val" id="bv_usage"></div></div></div></div>
                        <div class="col-md-3"><div class="bld-info-row"><i class="bi bi-tools bld-info-icon"></i><div><div class="bld-info-label">Construction</div><div class="bld-info-val" id="bv_construction_type"></div></div></div></div>
                        <div class="col-md-3"><div class="bld-info-row"><i class="bi bi-house bld-info-icon"></i><div><div class="bld-info-label">Building Type</div><div class="bld-info-val" id="bv_building_type"></div></div></div></div>
                        <div class="col-md-3"><div class="bld-info-row"><i class="bi bi-droplet bld-info-icon"></i><div><div class="bld-info-label">UGD Status</div><div class="bld-info-val" id="bv_ugd"></div></div></div></div>
                    </div>
                    <div class="bld-section-divider mb-3"><i class="bi bi-check2-square me-2"></i>Amenities</div>
                    <div class="mb-4" id="bv_amenities"></div>
                    <div class="bld-section-divider mb-3"><i class="bi bi-chat-text me-2"></i>Remarks</div>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="bld-info-row"><i class="bi bi-chat-left-text bld-info-icon"></i><div><div class="bld-info-label">General Remarks</div><div class="bld-info-val" id="bv_remarks"></div></div></div></div>
                        <div class="col-md-6"><div class="bld-info-row"><i class="bi bi-chat-left-text bld-info-icon"></i><div><div class="bld-info-label">Corporation Remarks</div><div class="bld-info-val" id="bv_corp_remarks"></div></div></div></div>
                    </div>
                </div>
                <div class="modal-footer bld-modal-footer">
                    <span class="bld-footer-status">Read-only view</span>
                    <div><button type="button" class="btn bld-btn-cancel me-2" id="buildingViewPointsBtn"><i class="bi bi-geo-alt me-1"></i>View Assessments</button></div>
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
                        <div><h5 class="bld-modal-title">Assessment Records</h5><span class="bld-gisid-badge">GIS ID: <span id="pdGisid"></span></span></div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3"><span class="text-muted small" id="pdBillSummary"></span></div>
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

    <!-- Line Details Modal -->
    <div class="modal fade" id="lineDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Line/Road Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <form id="lineDetailsForm">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">GIS ID</label><input type="text" class="form-control" id="line_gisid" name="gisid" readonly></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Road Name</label><input type="text" class="form-control" id="line_road_name" name="road_name"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>

    <!-- Delete Feature Modal -->
    <div class="modal fade" id="deleteFeatureModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
                <div class="modal-header" style="background:#fff3f3; border-bottom:1px solid #fecdd3; border-radius:16px 16px 0 0; padding:16px 24px;">
                    <h5 class="modal-title" style="color:#dc2626; font-weight:700; margin:0;"><i class="bi bi-trash3-fill me-2"></i>Delete Feature</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-4" style="font-size:0.875rem; line-height:1.5;">Choose the feature type and enter its GIS ID to permanently remove it.</p>
                    <div class="mb-4">
                        <label class="form-label fw-semibold mb-2">Feature Type</label>
                        <div class="d-flex gap-2">
                            <div class="delete-type-btn active" data-type="polygon"><i class="bi bi-pentagon me-1"></i>Polygon</div>
                            <div class="delete-type-btn" data-type="line"><i class="bi bi-vector-pen me-1"></i>Line</div>
                            <div class="delete-type-btn" data-type="point"><i class="bi bi-geo-alt me-1"></i>Point</div>
                        </div>
                        <input type="hidden" id="deleteFeatureType" value="polygon">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold mb-2">GIS ID</label>
                        <input type="text" id="deleteGisId" class="form-control" placeholder="Enter GIS ID…" style="border-radius:10px; border:1.5px solid #e5e7eb; padding:10px 14px; font-size:0.9rem;">
                        <div id="deleteGisError" class="text-danger mt-1" style="font-size:0.8rem; display:none;"></div>
                    </div>
                    <div id="deleteConfirmBox" style="display:none; background:#fff3f3; border:1px solid #fecdd3; border-radius:10px; padding:12px 14px;">
                        <p class="mb-0" style="font-size:0.82rem; color:#dc2626;"><i class="bi bi-exclamation-triangle-fill me-1"></i>This will <strong>permanently delete</strong> this feature and cannot be undone.</p>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9; border-radius:0 0 16px 16px; padding:14px 24px;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:10px; font-weight:600; padding:8px 20px;">Cancel</button>
                    <button type="button" id="confirmDeleteBtn" class="btn btn-danger" style="border-radius:10px; font-weight:600; padding:8px 24px; min-width:120px;"><i class="bi bi-trash3 me-1"></i>Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Infrastructure Properties Modal -->
    <div class="modal fade" id="infraPropertiesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bld-modal-content">
                <div class="modal-header bld-modal-header">
                    <div class="bld-header-inner">
                        <div class="bld-header-icon"><i class="bi bi-map"></i></div>
                        <div><h5 class="bld-modal-title">Infrastructure Properties</h5><span class="bld-gisid-badge">Type: <span id="infraType"></span></span></div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4"><div id="infraPropertiesContent"></div></div>
                <div class="modal-footer bld-modal-footer">
                    <span class="bld-footer-status">Infrastructure feature details</span>
                    <button type="button" class="btn bld-btn-cancel" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/ol@latest/dist/ol.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
    <script src="https://cesium.com/downloads/cesiumjs/releases/1.127/Build/Cesium/Cesium.js"></script>

    <script>
        $(document).ready(function() {
            // ─── DATA ───
            let polygons = @json($polygons ?? [], JSON_HEX_TAG);
            let lines = @json($lines ?? [], JSON_HEX_TAG);
            let points = @json($points ?? [], JSON_HEX_TAG);
            let pointDatas = @json($pointDatas ?? [], JSON_HEX_TAG);
            let polygonDatas = @json($polygonDatas ?? [], JSON_HEX_TAG);
            let ward = @json($ward ?? [], JSON_HEX_TAG);
            let searchIndex = [];
            let currentPointGisid = null;
            let currentPointRecords = [];
            let analytics = @json($analytics ?? [], JSON_HEX_TAG);
            let buildingVariations = @json($buildingVariations ?? [], JSON_HEX_TAG);

            // ─── DRONE IMAGE ───
            let droneImageURL = "{{ asset($ward->drone_image ?? '') }}";
            let imageExtentRaw = [{{ $ward->extent_left ?? 0 }}, {{ $ward->extent_bottom ?? 0 }},
                {{ $ward->extent_right ?? 0 }}, {{ $ward->extent_top ?? 0 }}
            ];

            const isLatLon = imageExtentRaw[0] > -180 && imageExtentRaw[0] < 180 &&
                imageExtentRaw[1] > -90 && imageExtentRaw[1] < 90;

            let buildingData = @json($buildingData ?? [], JSON_HEX_TAG);
            let allBuildings = buildingData.buildings || [];
            let usageCounts = buildingData.usage_counts || {};

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

            // ─── CESIUM 3D WITH DRONE ───
            let cesiumViewer = null;
            let cesiumBuildingEntities = [];
            let is3DActive = false;
            let droneImageryLayer = null;

            function ringToLonLatFlatArray(ringCoords) {
                const flat = [];
                ringCoords.forEach(c => {
                    const lonlat = ol.proj.toLonLat(c);
                    flat.push(lonlat[0], lonlat[1]);
                });
                return flat;
            }

            function addDroneImageryTo3D() {
                if (!cesiumViewer) return;

                if (droneImageryLayer) {
                    cesiumViewer.imageryLayers.remove(droneImageryLayer);
                    droneImageryLayer = null;
                }

                if (!droneImageURL || droneImageURL === "{{ asset('') }}") {
                    console.log('No drone image available');
                    return;
                }

                if (!isLatLon) {
                    console.warn('Drone extent is not lon/lat — cannot place in 3D globe.');
                    return;
                }

                const rectangle = Cesium.Rectangle.fromDegrees(
                    imageExtentRaw[0],
                    imageExtentRaw[1],
                    imageExtentRaw[2],
                    imageExtentRaw[3]
                );

                try {
                    const provider = new Cesium.SingleTileImageryProvider({
                        url: droneImageURL,
                        rectangle: rectangle
                    });

                    droneImageryLayer = cesiumViewer.imageryLayers.addImageryProvider(provider);
                    droneImageryLayer.alpha = 0.95;
                    droneImageryLayer.show = true;

                    console.log('✅ Drone image added to 3D view');

                    const west = imageExtentRaw[0];
                    const south = imageExtentRaw[1];
                    const east = imageExtentRaw[2];
                    const north = imageExtentRaw[3];
                    const centerLon = (west + east) / 2;
                    const centerLat = (south + north) / 2;

                    cesiumViewer.camera.flyTo({
                        destination: Cesium.Cartesian3.fromDegrees(centerLon, centerLat, 500),
                        orientation: {
                            heading: Cesium.Math.toRadians(0),
                            pitch: Cesium.Math.toRadians(-45),
                            roll: 0
                        },
                        duration: 2
                    });

                    showToast('🛩️ Drone image loaded in 3D view', 3000);
                } catch (error) {
                    console.error('Error loading drone image in 3D:', error);
                }
            }

            function refreshCesiumBuildings() {
                if (!cesiumViewer) return;

                addDroneImageryTo3D();

                cesiumBuildingEntities.forEach(e => cesiumViewer.entities.remove(e));
                cesiumBuildingEntities = [];

                const filteredFeatures = buildingSource.getFeatures();
                filteredFeatures.forEach(feature => {
                    try {
                        const gisid = feature.get('gisid');
                        const coords = feature.getGeometry().getCoordinates()[0];
                        const flat = ringToLonLatFlatArray(coords);
                        if (flat.length < 6) return;

                        const polygonData = polygonDatas.find(d => d.gisid == gisid);
                        const floors = polygonData?.number_floor ? parseInt(polygonData.number_floor) || 1 : 1;
                        const height = Math.max(1, floors) * 3;
                        const isMapped = !!polygonData;
                        const usage = feature.get('usage') || 'OTHER';
                        const color = usageColors[usage] || '#9E9E9E';

                        const entity = cesiumViewer.entities.add({
                            name: 'Building ' + gisid,
                            polygon: {
                                hierarchy: Cesium.Cartesian3.fromDegreesArray(flat),
                                height: 0,
                                extrudedHeight: height,
                                material: Cesium.Color.fromCssColorString(color).withAlpha(0.75),
                                outline: true,
                                outlineColor: Cesium.Color.WHITE,
                                outlineWidth: 1
                            },
                            description: `
                                <b>GIS ID:</b> ${gisid}<br>
                                <b>Area:</b> ${feature.get('sqfeet') || 0} sqft<br>
                                <b>Floors:</b> ${floors}<br>
                                <b>Usage:</b> ${usage}<br>
                                <b>Status:</b> ${isMapped ? 'Mapped' : 'Unmapped'}
                            `
                        });

                        cesiumBuildingEntities.push(entity);
                    } catch (e) {
                        console.error('Cesium build error:', e);
                    }
                });

                if (cesiumBuildingEntities.length) {
                    cesiumViewer.zoomTo(cesiumViewer.entities);
                }
            }

            function init3DViewer() {
                if (cesiumViewer) return cesiumViewer;

                window.CESIUM_BASE_URL = 'https://cesium.com/downloads/cesiumjs/releases/1.127/Build/Cesium/';

                cesiumViewer = new Cesium.Viewer('cesiumContainer', {
                    animation: false,
                    timeline: false,
                    geocoder: false,
                    homeButton: false,
                    sceneModePicker: false,
                    navigationHelpButton: false,
                    baseLayerPicker: false,
                    fullscreenButton: false,
                    imageryProvider: new Cesium.OpenStreetMapImageryProvider({
                        url: 'https://a.tile.openstreetmap.org/'
                    }),
                    terrainProvider: new Cesium.EllipsoidTerrainProvider()
                });

                cesiumViewer.container.insertAdjacentHTML('beforeend',
                    '<div class="cesium-info-box">🧊 3D view with drone overlay — switch back to 2D to edit</div>'
                );

                setTimeout(() => { addDroneImageryTo3D(); }, 1000);

                window.cesiumViewer = cesiumViewer;
                return cesiumViewer;
            }

            function toggle3DView() {
                is3DActive = !is3DActive;
                window.is3DActive = is3DActive;
                $('#threeDToggleBtn').toggleClass('active-3d', is3DActive);
                $('#threeDToggleBtn i').toggleClass('bi-box', !is3DActive).toggleClass('bi-badge-3d', is3DActive);

                if (is3DActive) {
                    disableAllInteractions();
                    setNoneMode();
                    $('#map').hide();
                    $('#cesiumContainer').show();
                    init3DViewer();
                    setTimeout(() => { refreshCesiumBuildings(); }, 500);
                    showToast('🧊 3D View with Drone Overlay', 2500);
                } else {
                    $('#cesiumContainer').hide();
                    $('#map').show();
                    setTimeout(() => map.updateSize(), 100);
                    showToast('🗺️ Back to 2D editable view', 1500);
                }
            }

            // ─── EXPORT FUNCTIONS ───
            function getExportData() {
                const features = buildingSource.getFeatures();
                const exportData = [];
                features.forEach(feature => {
                    const gisid = feature.get('gisid');
                    const variation = buildingVariations[gisid] || {};
                    exportData.push({
                        'GIS ID': gisid || '',
                        'Usage': feature.get('usage') || 'OTHER',
                        'Area (sqft)': feature.get('sqfeet') || 0,
                        'Building Area': variation.building_area || 0,
                        'Assessment Area': variation.assessment_area || 0,
                        'Area Variation': variation.area_variation || 0,
                        'Variation %': variation.variation_percentage || 0,
                        'Area Status': variation.area_status || 'N/A',
                        'Usage Status': variation.usage_status || 'N/A'
                    });
                });
                return exportData;
            }

            function exportToExcel() {
                const data = getExportData();
                if (data.length === 0) {
                    Swal.fire('No Data', 'No buildings to export', 'warning');
                    return;
                }
                Swal.fire({ title: 'Exporting to Excel...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                try {
                    const wb = XLSX.utils.book_new();
                    const ws = XLSX.utils.json_to_sheet(data);
                    ws['!cols'] = [
                        { wch: 12 }, { wch: 15 }, { wch: 14 }, { wch: 16 },
                        { wch: 18 }, { wch: 16 }, { wch: 14 }, { wch: 14 }, { wch: 14 }
                    ];
                    XLSX.utils.book_append_sheet(wb, ws, 'Buildings');
                    const filename = `ward_${ward.ward_no}_buildings_${new Date().toISOString().slice(0,10)}.xlsx`;
                    XLSX.writeFile(wb, filename);
                    Swal.fire({ icon: 'success', title: 'Export Successful', text: `Exported ${data.length} buildings`, timer: 2000, showConfirmButton: false });
                } catch (error) {
                    Swal.fire('Export Failed', 'Error exporting to Excel', 'error');
                }
            }

            function exportToPdf() {
                const data = getExportData();
                if (data.length === 0) {
                    Swal.fire('No Data', 'No buildings to export', 'warning');
                    return;
                }
                Swal.fire({ title: 'Generating PDF...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                try {
                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF('landscape', 'mm', 'a4');
                    doc.setFontSize(16);
                    doc.text(`Ward ${ward.ward_no} - Building Variations Report`, 14, 20);
                    doc.setFontSize(10);
                    doc.text(`Generated: ${new Date().toLocaleString()}`, 14, 28);
                    doc.text(`Total Buildings: ${data.length}`, 14, 34);

                    const tableData = data.map(item => [
                        item['GIS ID'], item['Usage'], String(item['Area (sqft)']),
                        String(item['Building Area']), String(item['Assessment Area']),
                        String(item['Area Variation']), String(item['Variation %']),
                        item['Area Status'], item['Usage Status']
                    ]);

                    doc.autoTable({
                        head: [['GIS ID', 'Usage', 'Area', 'Building Area', 'Assessment Area',
                                'Area Variation', 'Variation %', 'Area Status', 'Usage Status']],
                        body: tableData,
                        startY: 40,
                        styles: { fontSize: 7, cellPadding: 1.5 },
                        headStyles: { fillColor: [37, 99, 235], fontSize: 8, fontStyle: 'bold' },
                        columnStyles: {
                            0: { cellWidth: 20 }, 1: { cellWidth: 22 }, 2: { cellWidth: 18 },
                            3: { cellWidth: 22 }, 4: { cellWidth: 24 }, 5: { cellWidth: 22 },
                            6: { cellWidth: 18 }, 7: { cellWidth: 20 }, 8: { cellWidth: 20 }
                        }
                    });

                    const finalY = doc.lastAutoTable.finalY + 10;
                    doc.setFontSize(10);
                    doc.text(`Summary:`, 14, finalY);
                    doc.setFontSize(9);

                    const areaVariations = data.filter(d => d['Area Status'] === 'VARIATION').length;
                    const usageVariations = data.filter(d => d['Usage Status'] === 'VARIATION').length;

                    doc.text(`• Area Variations: ${areaVariations} (${Math.round(areaVariations/data.length*100)}%)`, 14, finalY + 7);
                    doc.text(`• Usage Variations: ${usageVariations} (${Math.round(usageVariations/data.length*100)}%)`, 14, finalY + 14);

                    const filename = `ward_${ward.ward_no}_variations_${new Date().toISOString().slice(0,10)}.pdf`;
                    doc.save(filename);
                    Swal.fire({ icon: 'success', title: 'Export Successful', text: `Exported ${data.length} buildings`, timer: 2000, showConfirmButton: false });
                } catch (error) {
                    Swal.fire('Export Failed', 'Error exporting to PDF', 'error');
                }
            }

            // ─── FILTER FUNCTIONS ───
            let currentFilteredBuildings = allBuildings;

            function filterBuildings() {
                const selectedUsage = $('#usageFilter').val();
                const minArea = parseFloat($('#areaMin').val()) || 0;
                const maxArea = parseFloat($('#areaMax').val()) || Infinity;
                const variationFilter = $('#usageVariationFilter').val();

                let filtered = allBuildings.filter(building => {
                    if (selectedUsage !== 'all' && building.usage !== selectedUsage) return false;
                    const area = parseFloat(building.sqfeet || 0);
                    if (area < minArea || area > maxArea) return false;
                    if (variationFilter === 'match') {
                        const variation = buildingVariations[building.gisid];
                        if (!variation || variation.usage_status !== 'MATCH') return false;
                    } else if (variationFilter === 'variation') {
                        const variation = buildingVariations[building.gisid];
                        if (!variation || variation.usage_status !== 'VARIATION') return false;
                    }
                    return true;
                });

                currentFilteredBuildings = filtered;
                $('#buildingCountDisplay').text(`Showing: ${filtered.length} buildings`);
                loadBuildingsWithColors(filtered);
                updateLegendCounts(filtered);

                if (is3DActive) {
                    setTimeout(refreshCesiumBuildings, 300);
                }

                showToast(filtered.length === allBuildings.length ? '📊 Showing all buildings' :
                    `✅ Showing ${filtered.length} buildings with applied filters`, 2000);
            }

            function resetFilters() {
                $('#usageFilter').val('all');
                $('#areaMin').val({{ $areaStats['min'] ?? 0 }});
                $('#areaMax').val({{ $areaStats['max'] ?? 0 }});
                $('#usageVariationFilter').val('all');
                currentFilteredBuildings = allBuildings;
                $('#buildingCountDisplay').text(`Showing: ${allBuildings.length} buildings`);
                loadBuildingsWithColors(allBuildings);
                updateLegendCounts(allBuildings);
                if (is3DActive) setTimeout(refreshCesiumBuildings, 300);
                showToast('🔄 Reset all filters - showing all buildings', 2000);
            }

            function clearFilters() {
                $('#usageFilter').val('all');
                $('#areaMin').val('');
                $('#areaMax').val('');
                $('#usageVariationFilter').val('all');
                currentFilteredBuildings = allBuildings;
                $('#buildingCountDisplay').text(`Showing: ${allBuildings.length} buildings`);
                loadBuildingsWithColors(allBuildings);
                updateLegendCounts(allBuildings);
                if (is3DActive) setTimeout(refreshCesiumBuildings, 300);
                showToast('🗑️ Filters cleared - showing all buildings', 2000);
            }

            function updateLegendCounts(filtered) {
                const counts = {};
                filtered.forEach(b => { counts[b.usage] = (counts[b.usage] || 0) + 1; });
                $('.usage-legend .legend-count').each(function() {
                    const parentText = $(this).closest('.legend-item').find('.legend-label').text().toUpperCase();
                    $(this).text(counts[parentText] || 0);
                });
            }

            // ─── BUILDING SOURCE ───
            const buildingSource = new ol.source.Vector();

            function loadBuildingsWithColors(filteredBuildings) {
                buildingSource.clear();
                const dataToLoad = filteredBuildings || allBuildings;
                dataToLoad.forEach(building => {
                    try {
                        const coords = building.coordinates;
                        if (!coords || coords.length < 3) return;
                        const feature = new ol.Feature({
                            geometry: new ol.geom.Polygon([coords]),
                            gisid: building.gisid,
                            usage: building.usage,
                            color: building.color,
                            sqfeet: building.sqfeet,
                            type: 'Building',
                            originalData: building
                        });
                        feature.setId(building.gisid);
                        const color = building.color || '#9E9E9E';
                        feature.setStyle(new ol.style.Style({
                            fill: new ol.style.Fill({ color: color + '66' }),
                            stroke: new ol.style.Stroke({ color: color, width: 2.5 })
                        }));
                        buildingSource.addFeature(feature);
                    } catch (e) { console.error('Building parse error:', e); }
                });
                currentFilteredBuildings = dataToLoad;
            }

            // ─── BUILDING LAYER ───
            const buildingLayer = new ol.layer.Vector({
                source: buildingSource,
                visible: true,
                title: 'Buildings',
                zIndex: 10
            });

            // ─── MAP ───
            let imageExtent;
            if (isLatLon) {
                const bl = ol.proj.fromLonLat([imageExtentRaw[0], imageExtentRaw[1]]);
                const tr = ol.proj.fromLonLat([imageExtentRaw[2], imageExtentRaw[3]]);
                imageExtent = [bl[0], bl[1], tr[0], tr[1]];
            } else {
                imageExtent = imageExtentRaw;
            }

            const map = new ol.Map({
                target: 'map',
                layers: [
                    new ol.layer.Tile({ title: 'OpenStreetMap', type: 'base', visible: true, source: new ol.source.OSM() }),
                    new ol.layer.Tile({
                        title: 'Satellite', type: 'base', visible: false,
                        source: new ol.source.XYZ({ url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}' })
                    }),
                    new ol.layer.Tile({
                        title: 'Street View', type: 'base', visible: false,
                        source: new ol.source.XYZ({ url: 'https://{a-c}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png' })
                    }),
                    buildingLayer
                ],
                view: new ol.View({
                    center: ol.extent.getCenter(imageExtent),
                    zoom: 18
                })
            });

            // ─── UI INJECTION ───
            const $mapContainer = $('#map');

            $mapContainer.append(`
                <div class="custom-layer-switcher">
                    <div class="layer-toggle-btn"><i class="bi bi-layers"></i></div>
                    <div class="layer-dropdown">
                        <div class="dropdown-header">Base Maps</div>
                        <div class="layer-dropdown-item active" data-layer-type="base" data-layer="OpenStreetMap"><div class="layer-icon"><i class="bi bi-map"></i></div><div class="layer-name">OpenStreetMap</div><div class="layer-check"><i class="bi bi-check-lg"></i></div></div>
                        <div class="layer-dropdown-item" data-layer-type="base" data-layer="Satellite"><div class="layer-icon"><i class="bi bi-satellite"></i></div><div class="layer-name">Satellite</div><div class="layer-check"><i class="bi bi-check-lg"></i></div></div>
                        <div class="layer-dropdown-item" data-layer-type="base" data-layer="Street View"><div class="layer-icon"><i class="bi bi-signpost-2"></i></div><div class="layer-name">Street View</div><div class="layer-check"><i class="bi bi-check-lg"></i></div></div>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-header">Vector Layers</div>
                        <div class="layer-dropdown-item active" data-layer-type="vector" data-layer="Buildings"><div class="layer-icon"><i class="bi bi-building"></i></div><div class="layer-name">Buildings</div><div class="layer-check"><i class="bi bi-check-lg"></i></div></div>
                    </div>
                </div>
            `);

            $mapContainer.append(`
                <div class="custom-label-toggle">
                    <div class="label-toggle-btn active-label" id="labelToggleBtn" title="Toggle Labels"><i class="bi bi-fonts"></i></div>
                </div>
            `);

            $mapContainer.append(`
                <div class="custom-legend-toggle">
                    <div class="legend-toggle-btn" id="legendToggleBtn" title="Toggle Infrastructure Legend"><i class="bi bi-list-ul"></i></div>
                </div>
            `);

            $mapContainer.append(`
                <div class="custom-location-switcher">
                    <div class="location-toggle-btn" id="locationToggleBtn"><i class="bi bi-geo-alt"></i></div>
                    <div class="location-dropdown" id="locationDropdown">
                        <div class="dropdown-header">Location Tools</div>
                        <div class="location-dropdown-item" id="liveLocationItem" data-action="live"><div class="location-item-icon"><i class="bi bi-crosshair2"></i></div><div class="location-item-name">Live Location</div><div class="location-item-badge" id="liveLocationBadge">OFF</div></div>
                        <div class="location-dropdown-item" id="trackMeItem" data-action="track"><div class="location-item-icon"><i class="bi bi-broadcast"></i></div><div class="location-item-name">Track Me</div><div class="location-item-badge" id="trackMeBadge">OFF</div></div>
                        <div class="location-dropdown-item" id="clearRouteItem"><div class="location-item-icon"><i class="bi bi-x-circle"></i></div><div class="location-item-name">Clear Route</div></div>
                    </div>
                </div>
                <div class="location-toast" id="locationToast"></div>
            `);

            $mapContainer.append(`
                <div class="custom-search-switcher">
                    <div class="search-toggle-btn" id="searchToggleBtn"><i class="bi bi-search"></i></div>
                    <div class="search-dropdown" id="searchDropdown">
                        <div class="d-flex border-bottom">
                            <button type="button" class="btn btn-sm flex-fill search-tab-btn active" data-tab="quick">Quick Search</button>
                            <button type="button" class="btn btn-sm flex-fill search-tab-btn" data-tab="filter">Filter</button>
                        </div>
                        <div class="search-tab-pane" id="quickSearchTab">
                            <div class="p-3"><input type="text" id="gisSearchInput" class="form-control" placeholder="Search by GIS ID or Assessment..."></div>
                            <div id="searchResults" class="search-results-container"></div>
                        </div>
                        <div class="search-tab-pane" id="filterTab" style="display:none;">
                            <div class="p-3 pb-2">
                                <input type="text" id="filterAssessment" class="form-control mb-2" placeholder="Assessment">
                                <input type="text" id="filterOldAssessment" class="form-control mb-2" placeholder="Old Assessment">
                                <input type="text" id="filterOwnerName" class="form-control mb-2" placeholder="Owner Name">
                                <input type="text" id="filterPhoneNumber" class="form-control mb-2" placeholder="Phone Number">
                                <button class="btn btn-primary btn-sm w-100" id="applyFilterBtn">Search</button>
                            </div>
                            <div id="filterResults" class="search-results-container"></div>
                        </div>
                    </div>
                </div>
            `);

            $mapContainer.append(`
                <div class="custom-edit-toggle">
                    <div class="edit-toggle-btn" id="editToggleBtn"><i class="bi bi-pencil-square"></i></div>
                    <div class="edit-dropdown" id="editDropdown">
                        <div class="dropdown-header">🔧 Modes</div>
                        <div class="edit-dropdown-item active" data-tool="none"><div class="edit-icon"><i class="bi bi-eye"></i></div><div class="edit-name">View Only</div><div class="edit-check"><i class="bi bi-check-lg"></i></div></div>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-header">✏️ Edit</div>
                        <div class="edit-dropdown-item" data-tool="editPolygon"><div class="edit-icon"><i class="bi bi-pencil"></i></div><div class="edit-name">Edit Polygon</div><div class="edit-check"><i class="bi bi-check-lg"></i></div></div>
                        <div class="edit-dropdown-item" data-tool="movePolygon"><div class="edit-icon"><i class="bi bi-arrows-move"></i></div><div class="edit-name">Move Polygon</div><div class="edit-check"><i class="bi bi-check-lg"></i></div></div>
                        <div class="edit-dropdown-item" data-tool="split"><div class="edit-icon"><i class="bi bi-scissors"></i></div><div class="edit-name">Split Polygon</div><div class="edit-check"><i class="bi bi-check-lg"></i></div></div>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-header">✏️ Drawing</div>
                        <div class="edit-dropdown-item" data-tool="drawPolygon"><div class="edit-icon"><i class="bi bi-pentagon"></i></div><div class="edit-name">Draw Polygon</div><div class="edit-check"><i class="bi bi-check-lg"></i></div></div>
                        <div class="edit-dropdown-item" data-tool="drawLine"><div class="edit-icon"><i class="bi bi-vector-pen"></i></div><div class="edit-name">Draw Line</div><div class="edit-check"><i class="bi bi-check-lg"></i></div></div>
                        <div class="edit-dropdown-item" data-tool="drawPoint"><div class="edit-icon"><i class="bi bi-geo-alt"></i></div><div class="edit-name">Draw Point</div><div class="edit-check"><i class="bi bi-check-lg"></i></div></div>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-header">🗑️ Delete</div>
                        <div class="edit-dropdown-item" data-tool="delete"><div class="edit-icon"><i class="bi bi-trash3"></i></div><div class="edit-name">Delete Feature</div><div class="edit-check"><i class="bi bi-check-lg"></i></div></div>
                    </div>
                </div>
            `);

            $mapContainer.append(`
                <div class="custom-3d-toggle">
                    <div class="threed-toggle-btn" id="threeDToggleBtn" title="Toggle 3D View"><i class="bi bi-box"></i></div>
                </div>
            `);

            $mapContainer.append(`<div class="fullscreen-btn" id="fullscreenBtn"><i class="bi bi-arrows-fullscreen"></i></div>`);

            // ─── EVENT HANDLERS ───
            $(document).on('click', '#threeDToggleBtn', function(e) {
                e.stopPropagation();
                toggle3DView();
            });

            $(document).on('click', '#exportExcelBtn', exportToExcel);
            $(document).on('click', '#exportPdfBtn', exportToPdf);
            $(document).on('click', '#applyFiltersBtn', filterBuildings);
            $(document).on('click', '#resetFiltersBtn', resetFilters);
            $(document).on('click', '#clearFiltersBtn', clearFilters);

            // ─── TOAST ───
            function showToast(msg, duration = 2500) {
                const $t = $('#locationToast');
                if ($t.length) {
                    $t.text(msg).addClass('show');
                    clearTimeout($t.data('timeout'));
                    $t.data('timeout', setTimeout(() => $t.removeClass('show'), duration));
                }
            }

            // ─── VIEW MODE ───
            let selectInteraction = null;

            function setNoneMode() {
                if (selectInteraction) {
                    map.removeInteraction(selectInteraction);
                    selectInteraction = null;
                }
            }

            function disableAllInteractions() {}

            // ─── FULLSCREEN ───
            let isFullscreen = false;
            $(document).on('click', '#fullscreenBtn', function() {
                const $icon = $(this).find('i');
                const $card = $('#mapCard');
                const $container = $('#map');

                if (!isFullscreen) {
                    $card.addClass('fullscreen-mode');
                    $container.addClass('fullscreen');
                    $icon.removeClass('bi-arrows-fullscreen').addClass('bi-fullscreen-exit');
                    isFullscreen = true;
                } else {
                    $card.removeClass('fullscreen-mode');
                    $container.removeClass('fullscreen');
                    $icon.removeClass('bi-fullscreen-exit').addClass('bi-arrows-fullscreen');
                    isFullscreen = false;
                }

                setTimeout(function() {
                    map.updateSize();
                    if (window.is3DActive && window.cesiumViewer) {
                        window.cesiumViewer.resize();
                    }
                }, 150);
            });

            // ─── INIT ───
            loadBuildingsWithColors(allBuildings);
            $('#buildingCountDisplay').text(`Showing: ${allBuildings.length} buildings`);
            setNoneMode();
            showToast('✅ Dashboard ready with 3D drone view & export options', 3000);

            console.log('✅ Executive GIS Dashboard ready with 3D drone view, filters & export');
        });
    </script>
@endpush

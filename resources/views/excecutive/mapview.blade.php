@extends('layouts.office')

@section('title', 'Executive GIS Dashboard')
@section('page_title', 'Executive GIS Dashboard')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@latest/ol.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        /* ─── Layout ─── */
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

        /* ─── Controls ─── */
        .custom-layer-switcher,
        .custom-location-switcher,
        .custom-search-switcher,
        .custom-edit-toggle {
            position: absolute;
            right: 30px;
            z-index: 1000;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        }

        .custom-layer-switcher {
            top: 20px;
        }

        .custom-location-switcher {
            top: 74px;
        }

        .custom-search-switcher {
            top: 130px;
        }

        .custom-edit-toggle {
            top: 190px;
        }

        .layer-toggle-btn,
        .location-toggle-btn,
        .search-toggle-btn,
        .edit-toggle-btn,
        .fullscreen-btn {
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
        }

        .layer-toggle-btn:hover,
        .location-toggle-btn:hover,
        .search-toggle-btn:hover,
        .edit-toggle-btn:hover,
        .fullscreen-btn:hover {
            background: #f8fafc;
            transform: scale(1.02);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .location-toggle-btn.active-location,
        .search-toggle-btn.active-search,
        .edit-toggle-btn.active-edit {
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
            max-height: 400px;
            overflow-y: auto !important;
            overflow-x: hidden;
        }

        .layer-dropdown,
        .location-dropdown {
            min-width: 200px;
        }

        .search-dropdown {
            width: 320px;
        }

        .edit-dropdown {
            min-width: 250px;
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
        .edit-dropdown-item:hover,
        .search-result-item:hover {
            background: #f8fafc;
        }

        .layer-dropdown-item.active,
        .location-dropdown-item.active,
        .edit-dropdown-item.active {
            background: #eff6ff;
            border-left-color: #3b82f6;
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
            0% {
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.5);
            }

            70% {
                box-shadow: 0 0 0 7px rgba(34, 197, 94, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
            }
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

        .fullscreen-btn {
            position: absolute;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
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
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
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

        /* ── Building View ── */
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

        @media (max-width: 768px) {
            #map {
                height: 600px;
            }

            .bld-image-strip {
                height: 150px;
            }

            .bld-summary-card {
                flex: 1 1 45%;
            }

            .point-data-card-grid {
                grid-template-columns: 1fr 1fr;
            }

            .bld-modal-footer {
                flex-direction: column;
                gap: 10px;
            }

            .point-data-card-header {
                flex-direction: column;
                gap: 8px;
            }

            .point-data-card-actions {
                justify-content: flex-start;
            }
        }

        /* ─── Ward Analytics Strip ─── */
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

        .stat-icon-blue {
            background: #eff6ff;
            color: #2563eb;
        }

        .stat-icon-green {
            background: #f0fdf4;
            color: #16a34a;
        }

        .stat-icon-purple {
            background: #f5f3ff;
            color: #7c3aed;
        }

        .stat-icon-amber {
            background: #fffbeb;
            color: #d97706;
        }

        .stat-icon-red {
            background: #fef2f2;
            color: #dc2626;
        }

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

        @media (max-width: 992px) {
            .stat-strip {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .stat-strip {
                grid-template-columns: 1fr;
            }
        }

        /* ─── Per-building variation badge in modal ─── */
        .bv-variation-strip {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .bv-variation-card {
            flex: 1 1 150px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 14px;
        }

        .bv-variation-card .stat-label {
            margin-bottom: 4px;
        }

        .bv-variation-card .bld-status-tag {
            margin-top: 6px;
        }
    </style>
@endpush

@section('content')
    <div class="ol-page-header">
        <div>
            <h1 class="ol-page-title">
                Executive GIS Dashboard
            </h1>
            <p class="ol-page-sub">
                {{ now()->format('l, d F Y') }} — {{ auth()->user()->name ?? 'Executive Officer' }}
            </p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="ds-pill paid">
                <i class="bi bi-circle-fill" style="font-size:8px;"></i>
                Live
            </span>
        </div>
    </div>

    <!-- ─── WARD ANALYTICS STRIP ─── -->
    <div class="stat-strip">
        <div class="stat-card">
            <div class="stat-icon stat-icon-blue"><i class="bi bi-building"></i></div>
            <div>
                <div class="stat-label">Total Buildings</div>
                <div class="stat-value">{{ $analytics['total_buildings'] }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-green"><i class="bi bi-check2-circle"></i></div>
            <div>
                <div class="stat-label">Surveyed Buildings</div>
                <div class="stat-value">{{ $analytics['surveyed_buildings'] }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-purple"><i class="bi bi-graph-up"></i></div>
            <div>
                <div class="stat-label">Survey Progress</div>
                <div class="stat-value">{{ $analytics['survey_percentage'] }}%</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-blue"><i class="bi bi-file-earmark-text"></i></div>
            <div>
                <div class="stat-label">Assessments Mapped</div>
                <div class="stat-value">{{ $analytics['total_surveyed_assessments'] }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-amber"><i class="bi bi-rulers"></i></div>
            <div>
                <div class="stat-label">Area Variation</div>
                <div class="stat-value">{{ $analytics['area_variation_count'] }}
                    <span class="stat-sub">({{ $analytics['area_variation_percentage'] }}%)</span>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-red"><i class="bi bi-tags"></i></div>
            <div>
                <div class="stat-label">Usage Variation</div>
                <div class="stat-value">{{ $analytics['usage_variation_count'] }}
                    <span class="stat-sub">({{ $analytics['usage_variation_percentage'] }}%)</span>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-blue"><i class="bi bi-bounding-box"></i></div>
            <div>
                <div class="stat-label">Total Building Area</div>
                <div class="stat-value">{{ number_format($analytics['total_building_area'], 0) }} <span
                        class="stat-sub">sqft</span></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-green"><i class="bi bi-clipboard-data"></i></div>
            <div>
                <div class="stat-label">Total Assessment Area</div>
                <div class="stat-value">{{ number_format($analytics['total_assessment_area'], 0) }} <span
                        class="stat-sub">sqft</span></div>
            </div>
        </div>
    </div>

    <div class="map-card" id="mapCard">
        <div class="map-header">
            <h5 class="map-title">
                <i class="bi bi-geo-alt-fill text-primary me-2"></i>
                Executive GIS Dashboard
            </h5>
            <span class="badge bg-primary" id="activeLayerBadge">OpenStreetMap</span>
        </div>
        <div id="map"></div>
    </div>

    <!-- ============================================================ -->
    <!-- BUILDING VIEW MODAL (READ-ONLY)                               -->
    <!-- ============================================================ -->
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
                    <div class="bld-img-wrap">
                        <img id="bv_img1" src="" style="display:none;">
                        <div id="bv_img1_empty"
                            class="d-flex align-items-center justify-content-center h-100 text-white-50">No Image</div>
                        <div class="bld-img-label">Image 1</div>
                    </div>
                    <div class="bld-img-wrap">
                        <img id="bv_img2" src="" style="display:none;">
                        <div id="bv_img2_empty"
                            class="d-flex align-items-center justify-content-center h-100 text-white-50">No Image</div>
                        <div class="bld-img-label">Image 2</div>
                    </div>
                </div>

                <div class="bld-summary-strip">
                    <div class="bld-summary-card">
                        <div class="bld-summary-icon">🧾</div>
                        <div>
                            <div class="bld-summary-label">Bills</div>
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
                <div class="p-3 pb-0" id="bv_variation_wrap"></div>
                <div class="modal-body p-4">
                    <div class="bld-section-divider mb-3"><i class="bi bi-info-circle me-2"></i>Basic Information</div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="bld-info-row"><i class="bi bi-geo-alt bld-info-icon"></i>
                                <div>
                                    <div class="bld-info-label">Zone</div>
                                    <div class="bld-info-val" id="bv_zone"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="bld-info-row"><i class="bi bi-building bld-info-icon"></i>
                                <div>
                                    <div class="bld-info-label">Building Name</div>
                                    <div class="bld-info-val" id="bv_building_name"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="bld-info-row"><i class="bi bi-signpost bld-info-icon"></i>
                                <div>
                                    <div class="bld-info-label">Road</div>
                                    <div class="bld-info-val" id="bv_road_name"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="bld-info-row"><i class="bi bi-telephone bld-info-icon"></i>
                                <div>
                                    <div class="bld-info-label">Phone</div>
                                    <div class="bld-info-val" id="bv_phone"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="bld-info-row"><i class="bi bi-tag bld-info-icon"></i>
                                <div>
                                    <div class="bld-info-label">Usage</div>
                                    <div class="bld-info-val" id="bv_usage"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="bld-info-row"><i class="bi bi-tools bld-info-icon"></i>
                                <div>
                                    <div class="bld-info-label">Construction</div>
                                    <div class="bld-info-val" id="bv_construction_type"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="bld-info-row"><i class="bi bi-house bld-info-icon"></i>
                                <div>
                                    <div class="bld-info-label">Building Type</div>
                                    <div class="bld-info-val" id="bv_building_type"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="bld-info-row"><i class="bi bi-droplet bld-info-icon"></i>
                                <div>
                                    <div class="bld-info-label">UGD Status</div>
                                    <div class="bld-info-val" id="bv_ugd"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bld-section-divider mb-3"><i class="bi bi-check2-square me-2"></i>Amenities</div>
                    <div class="mb-4" id="bv_amenities"></div>

                    <div class="bld-section-divider mb-3"><i class="bi bi-chat-text me-2"></i>Remarks</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="bld-info-row"><i class="bi bi-chat-left-text bld-info-icon"></i>
                                <div>
                                    <div class="bld-info-label">General Remarks</div>
                                    <div class="bld-info-val" id="bv_remarks"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bld-info-row"><i class="bi bi-chat-left-text bld-info-icon"></i>
                                <div>
                                    <div class="bld-info-label">Corporation Remarks</div>
                                    <div class="bld-info-val" id="bv_corp_remarks"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bld-modal-footer">
                    <span class="bld-footer-status">Read-only view</span>
                    <div>
                        <button type="button" class="btn bld-btn-cancel me-2" id="buildingViewPointsBtn">
                            <i class="bi bi-geo-alt me-1"></i>View Assessments
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- POINT DETAILS MODAL (READ-ONLY)                               -->
    <!-- ============================================================ -->
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
                    <input type="text" class="form-control bld-input mb-3" id="pointDetailsSearch"
                        placeholder="Search by assessment, owner name, or phone number...">
                    <div id="pointDetailsContainer"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- QC MODAL                                                     -->
    <!-- ============================================================ -->
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
                    <p class="text-muted small mb-3">
                        <span id="qc_owner_display" class="fw-semibold"></span> — Assessment
                        <span id="qc_assessment_display" class="fw-semibold"></span>
                    </p>
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
                    <button type="button" class="btn bld-btn-save" id="saveQcBtn">
                        <i class="bi bi-save me-1"></i>Save QC
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- LINE DETAILS MODAL                                           -->
    <!-- ============================================================ -->
    <div class="modal fade" id="lineDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Line/Road Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="lineDetailsForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">GIS ID</label>
                                <input type="text" class="form-control" id="line_gisid" name="gisid" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Road Name</label>
                                <input type="text" class="form-control" id="line_road_name" name="road_name">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- DELETE FEATURE MODAL                                         -->
    <!-- ============================================================ -->
    <div class="modal fade" id="deleteFeatureModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
                <div class="modal-header"
                    style="background:#fff3f3; border-bottom:1px solid #fecdd3; border-radius:16px 16px 0 0; padding:16px 24px;">
                    <h5 class="modal-title" style="color:#dc2626; font-weight:700; margin:0;">
                        <i class="bi bi-trash3-fill me-2"></i>Delete Feature
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-4" style="font-size:0.875rem; line-height:1.5;">
                        Choose the feature type and enter its GIS ID to permanently remove it.
                    </p>
                    <div class="mb-4">
                        <label class="form-label fw-semibold mb-2">Feature Type</label>
                        <div class="d-flex gap-2">
                            <div class="delete-type-btn active" data-type="polygon">
                                <i class="bi bi-pentagon me-1"></i>Polygon
                            </div>
                            <div class="delete-type-btn" data-type="line">
                                <i class="bi bi-vector-pen me-1"></i>Line
                            </div>
                            <div class="delete-type-btn" data-type="point">
                                <i class="bi bi-geo-alt me-1"></i>Point
                            </div>
                        </div>
                        <input type="hidden" id="deleteFeatureType" value="polygon">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold mb-2">GIS ID</label>
                        <input type="text" id="deleteGisId" class="form-control" placeholder="Enter GIS ID…"
                            style="border-radius:10px; border:1.5px solid #e5e7eb; padding:10px 14px; font-size:0.9rem;">
                        <div id="deleteGisError" class="text-danger mt-1" style="font-size:0.8rem; display:none;"></div>
                    </div>
                    <div id="deleteConfirmBox"
                        style="display:none; background:#fff3f3; border:1px solid #fecdd3; border-radius:10px; padding:12px 14px;">
                        <p class="mb-0" style="font-size:0.82rem; color:#dc2626;">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            This will <strong>permanently delete</strong> this feature and cannot be undone.
                        </p>
                    </div>
                </div>
                <div class="modal-footer"
                    style="border-top:1px solid #f1f5f9; border-radius:0 0 16px 16px; padding:14px 24px;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"
                        style="border-radius:10px; font-weight:600; padding:8px 20px;">Cancel</button>
                    <button type="button" id="confirmDeleteBtn" class="btn btn-danger"
                        style="border-radius:10px; font-weight:600; padding:8px 24px; min-width:120px;">
                        <i class="bi bi-trash3 me-1"></i>Delete
                    </button>
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
                    imageExtent,
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

            const streetLayer = new ol.layer.Tile({
                title: 'Street View',
                type: 'base',
                visible: false,
                source: new ol.source.XYZ({
                    url: 'https://{a-c}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png',
                    attributions: '&copy; OpenStreetMap Contributors'
                })
            });

            // ─── STYLES ───
            function createPolygonStyle(feature) {
                const gisid = feature.get('gisid');
                const sqft = feature.get('sqfeet') || '0';
                const polygonData = polygonDatas.find(d => d.gisid == gisid);
                const color = polygonData ? 'red' : 'blue';
                const centerPoint = feature.getGeometry().getInteriorPoint();

                return [
                    new ol.style.Style({
                        stroke: new ol.style.Stroke({
                            color,
                            width: 4,
                            lineJoin: 'round',
                            lineCap: 'round'
                        }),
                        fill: new ol.style.Fill({
                            color: 'rgba(0,0,255,0.1)'
                        })
                    }),
                    new ol.style.Style({
                        geometry: centerPoint,
                        text: new ol.style.Text({
                            text: sqft + ' SQFT',
                            font: 'bold 14px Arial',
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
                    })
                ];
            }

            function createLineStyle(feature) {
                const roadName = feature.get('road_name');
                const styles = [
                    new ol.style.Style({
                        stroke: new ol.style.Stroke({
                            color: 'yellow',
                            width: 4,
                            lineJoin: 'round',
                            lineCap: 'round'
                        })
                    })
                ];
                if (roadName) {
                    styles.push(new ol.style.Style({
                        text: new ol.style.Text({
                            text: String(roadName),
                            font: 'bold 14px Calibri, sans-serif',
                            placement: 'line',
                            overflow: true,
                            fill: new ol.style.Fill({
                                color: '#000'
                            }),
                            stroke: new ol.style.Stroke({
                                color: '#fff',
                                width: 3
                            }),
                            repeat: 400
                        })
                    }));
                }
                return styles;
            }

            function createPointStyle(feature) {
                const gisid = feature.get('gisid');
                const pointCount = pointDatas.filter(d => d.point_gisid == gisid).length;
                const polygonData = polygonDatas.find(d => d.gisid == gisid);
                let color = 'blue';
                if (polygonData) {
                    color = pointCount > 0 ? (polygonData.number_bill == pointCount ? 'green' : 'red') : 'blue';
                }
                return new ol.style.Style({
                    image: new ol.style.Circle({
                        radius: 8,
                        fill: new ol.style.Fill({
                            color
                        }),
                        stroke: new ol.style.Stroke({
                            color,
                            width: 2
                        })
                    }),
                    text: new ol.style.Text({
                        text: gisid ? String(gisid) : '',
                        scale: 1.3,
                        offsetY: -15,
                        fill: new ol.style.Fill({
                            color: '#000'
                        }),
                        stroke: new ol.style.Stroke({
                            color: '#fff',
                            width: 3
                        })
                    })
                });
            }

            // ─── SOURCES ───
            const polygonSource = new ol.source.Vector();
            const lineSource = new ol.source.Vector();
            const pointSource = new ol.source.Vector();

            function loadPolygonsToSource() {
                polygonSource.clear();
                polygons.forEach(poly => {
                    try {
                        let coords = JSON.parse(poly.coordinates);
                        const feature = new ol.Feature({
                            geometry: new ol.geom.Polygon([coords]),
                            gisid: poly.gisid,
                            type: 'Polygon',
                            sqfeet: poly.sqfeet || '0',
                            originalData: poly
                        });
                        feature.setId(poly.gisid);
                        polygonSource.addFeature(feature);
                    } catch (e) {
                        console.error('Polygon parse error:', e);
                    }
                });
            }

            function loadLinesToSource() {
                lineSource.clear();
                lines.forEach(l => {
                    try {
                        let coords = typeof l.coordinates === 'string' ? JSON.parse(l.coordinates) : l
                            .coordinates;
                        while (coords.length === 1 && Array.isArray(coords[0]) && Array.isArray(coords[0][
                                0
                            ])) {
                            coords = coords[0];
                        }
                        const isValid = coords.length >= 2 && coords.every(c =>
                            Array.isArray(c) && c.length >= 2 &&
                            typeof c[0] === 'number' && typeof c[1] === 'number' &&
                            isFinite(c[0]) && isFinite(c[1])
                        );
                        if (!isValid) {
                            console.warn('Skipping invalid line coords for gisid:', l.gisid);
                            return;
                        }

                        const feature = new ol.Feature({
                            geometry: new ol.geom.LineString(coords),
                            gisid: l.gisid,
                            type: 'LineString',
                            road_name: l.road_name || null,
                            originalData: l
                        });
                        feature.setId(l.gisid);
                        lineSource.addFeature(feature);
                    } catch (e) {
                        console.error('Line parse error for gisid:', l.gisid, e);
                    }
                });
            }

            function loadPointsToSource() {
                pointSource.clear();
                points.forEach(p => {
                    try {
                        let coords = JSON.parse(p.coordinates);
                        const feature = new ol.Feature({
                            geometry: new ol.geom.Point(coords),
                            gisid: p.gisid,
                            type: 'Point',
                            originalData: p
                        });
                        feature.setId(p.gisid);
                        pointSource.addFeature(feature);
                    } catch (e) {
                        console.error('Point parse error:', e);
                    }
                });
            }

            function reloadAllSources() {
                loadPolygonsToSource();
                loadLinesToSource();
                loadPointsToSource();
                buildSearchIndex();
            }

            loadPolygonsToSource();
            loadLinesToSource();
            loadPointsToSource();

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
                title: 'Lines',
                renderBuffer: 200
            });

            const pointLayer = new ol.layer.Vector({
                source: pointSource,
                style: createPointStyle,
                visible: true,
                title: 'Points'
            });

            // ─── LIVE LOCATION ───
            const liveLocationSource = new ol.source.Vector();
            const liveLocationLayer = new ol.layer.Vector({
                source: liveLocationSource,
                visible: true,
                title: 'Live Location',
                zIndex: 999
            });

            let watchId = null,
                locationFeature = null,
                accuracyFeature = null;
            let liveActive = false,
                trackActive = false;
            let currentLocation = null;

            function updateLiveMarker(lon, lat, accuracy) {
                const coords = ol.proj.fromLonLat([lon, lat]);
                currentLocation = {
                    lon,
                    lat
                };
                if (!locationFeature) {
                    locationFeature = new ol.Feature({
                        geometry: new ol.geom.Point(coords)
                    });
                    locationFeature.setStyle(new ol.style.Style({
                        image: new ol.style.Circle({
                            radius: 10,
                            fill: new ol.style.Fill({
                                color: '#3b82f6'
                            }),
                            stroke: new ol.style.Stroke({
                                color: '#fff',
                                width: 3
                            })
                        })
                    }));
                    accuracyFeature = new ol.Feature({
                        geometry: new ol.geom.Circle(coords, accuracy || 10)
                    });
                    accuracyFeature.setStyle(new ol.style.Style({
                        fill: new ol.style.Fill({
                            color: 'rgba(59,130,246,0.10)'
                        }),
                        stroke: new ol.style.Stroke({
                            color: 'rgba(59,130,246,0.35)',
                            width: 1.5
                        })
                    }));
                    liveLocationSource.addFeature(accuracyFeature);
                    liveLocationSource.addFeature(locationFeature);
                } else {
                    locationFeature.getGeometry().setCoordinates(coords);
                    accuracyFeature.getGeometry().setCenter(coords);
                    accuracyFeature.getGeometry().setRadius(accuracy || 10);
                }
            }

            function clearLiveMarker() {
                liveLocationSource.clear();
                locationFeature = null;
                accuracyFeature = null;
                currentLocation = null;
            }

            function onPosition(position) {
                const {
                    longitude,
                    latitude,
                    accuracy
                } = position.coords;
                updateLiveMarker(longitude, latitude, accuracy);
                if (trackActive) {
                    map.getView().animate({
                        center: ol.proj.fromLonLat([longitude, latitude]),
                        duration: 400
                    });
                }
            }

            function startWatching() {
                if (!navigator.geolocation) {
                    showToast('⚠️ Geolocation not supported', 3000);
                    return false;
                }
                if (watchId !== null) return true;

                navigator.geolocation.getCurrentPosition(
                    function(pos) {
                        onPosition(pos);
                        showToast('📍 Location acquired', 2000);
                    },
                    function(error) {
                        let msg = 'Could not get location: ';
                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                msg += 'Please allow location access';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                msg += 'GPS signal weak';
                                break;
                            case error.TIMEOUT:
                                msg += 'Request timed out';
                                break;
                        }
                        showToast(msg, 3000);
                    }, {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0
                    }
                );

                watchId = navigator.geolocation.watchPosition(
                    onPosition,
                    function(error) {
                        let msg = 'Location error: ';
                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                msg += 'Please enable permissions.';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                msg += 'Location unavailable.';
                                break;
                            case error.TIMEOUT:
                                msg += 'Request timed out.';
                                break;
                            default:
                                msg += 'Unknown error.';
                        }
                        showToast(msg, 3000);
                    }, {
                        enableHighAccuracy: true,
                        maximumAge: 10000,
                        timeout: 15000
                    }
                );
                return true;
            }

            function stopWatching() {
                if (watchId !== null) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                }
            }

            function syncLocationUI() {
                const $liveItem = $('#liveLocationItem');
                const $trackItem = $('#trackMeItem');
                if (liveActive) {
                    $liveItem.addClass('active');
                    $('#liveLocationBadge').text('ON');
                } else {
                    $liveItem.removeClass('active');
                    $('#liveLocationBadge').text('OFF');
                }
                if (trackActive) {
                    $trackItem.addClass('active');
                    $('#trackMeBadge').html('<span class="track-pulse"></span> ON');
                } else {
                    $trackItem.removeClass('active');
                    $('#trackMeBadge').text('OFF');
                }
                const anyActive = liveActive || trackActive;
                $('#locationToggleBtn').toggleClass('active-location', anyActive);
                $('#locationToggleBtn i').toggleClass('bi-geo-alt-fill', anyActive).toggleClass('bi-geo-alt', !
                    anyActive);
            }

            // ─── MAP ───
            const map = new ol.Map({
                target: 'map',
                layers: [osmLayer, satelliteLayer, streetLayer, droneLayer, polygonLayer, pointLayer,
                    lineLayer, liveLocationLayer
                ],
                view: new ol.View({
                    center: ol.extent.getCenter(imageExtent),
                    zoom: 18
                })
            });

            // ─── INTERACTIONS ───
            let drawInteraction = null,
                selectInteraction = null;
            let routeLayer = null;
            let selectedFeatureForSplit = null;
            let modifyInteraction = null;
            let translateInteraction = null;
            let selectedFeatureForEdit = null;
            let originalGeometry = null;

            const tempDrawSource = new ol.source.Vector();
            const tempDrawLayer = new ol.layer.Vector({
                source: tempDrawSource,
                style: new ol.style.Style({
                    fill: new ol.style.Fill({
                        color: 'rgba(255,0,0,0.2)'
                    }),
                    stroke: new ol.style.Stroke({
                        color: '#ff0000',
                        width: 3
                    }),
                    image: new ol.style.Circle({
                        radius: 7,
                        fill: new ol.style.Fill({
                            color: '#ff0000'
                        })
                    })
                })
            });
            map.addLayer(tempDrawLayer);

            // ─── Toast ───
            function showToast(msg, duration = 2500) {
                const $t = $('#locationToast');
                $t.text(msg).addClass('show');
                clearTimeout($t.data('timeout'));
                $t.data('timeout', setTimeout(() => $t.removeClass('show'), duration));
            }

            function showFlashMessage(message, type = 'info') {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                let icon = 'info';
                if (type === 'success') icon = 'success';
                else if (type === 'error') icon = 'error';
                else if (type === 'warning') icon = 'warning';
                Toast.fire({
                    icon,
                    title: message
                });
            }

            // ─── Disable interactions ───
            function disableAllInteractions() {
                if (selectedFeatureForSplit) {
                    selectedFeatureForSplit.setStyle(null);
                    selectedFeatureForSplit = null;
                }
                if (selectedFeatureForEdit) {
                    selectedFeatureForEdit.setStyle(null);
                    selectedFeatureForEdit = null;
                    originalGeometry = null;
                }
                if (drawInteraction) {
                    map.removeInteraction(drawInteraction);
                    drawInteraction = null;
                }
                if (selectInteraction) {
                    map.removeInteraction(selectInteraction);
                    selectInteraction = null;
                }
                if (modifyInteraction) {
                    map.removeInteraction(modifyInteraction);
                    modifyInteraction = null;
                }
                if (translateInteraction) {
                    map.removeInteraction(translateInteraction);
                    translateInteraction = null;
                }
                tempDrawSource.clear();
                map.getTargetElement().classList.remove('draw-mode', 'split-mode', 'edit-mode');
                hideSplitButton();
                hideEditControls();
            }

            function clearDrawInteraction() {
                if (drawInteraction) {
                    map.removeInteraction(drawInteraction);
                    drawInteraction = null;
                }
                tempDrawSource.clear();
            }

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

                // ─── AREA / USAGE VARIATION FOR THIS BUILDING ───
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
                amenities.forEach(([label, val]) => {
                    if (val === 'Yes') {
                        amenHtml +=
                            `<span class="bld-status-tag complete me-1"><i class="bi bi-check-circle"></i> ${label}</span>`;
                    }
                });
                $('#bv_amenities').html(amenHtml || '<span class="text-muted small">No amenities recorded</span>');

                $('#bv_remarks').text(item.remarks || '—');
                $('#bv_corp_remarks').text(item.corporationremarks || '—');

                const assetUrl = window.assetUrl || "{{ asset('') }}";
                if (item.image) {
                    $('#bv_img1').attr('src', item.image.startsWith('http') ? item.image : assetUrl + item.image)
                        .show();
                    $('#bv_img1_empty').hide();
                } else {
                    $('#bv_img1').hide();
                    $('#bv_img1_empty').show();
                }
                if (item.image2) {
                    $('#bv_img2').attr('src', item.image2.startsWith('http') ? item.image2 : assetUrl + item.image2)
                        .show();
                    $('#bv_img2_empty').hide();
                } else {
                    $('#bv_img2').hide();
                    $('#bv_img2_empty').show();
                }

                $('#buildingViewPointsBtn').off('click').on('click', function() {
                    bootstrap.Modal.getInstance(document.getElementById('buildingViewModal')).hide();
                    openPointDetails(item.gisid);
                });

                const modal = new bootstrap.Modal(document.getElementById('buildingViewModal'));
                modal.show();
            }

            // ─── GET POINT DATA WITH WATER, UGD, PROFESSIONAL ───
            function getPointDataWithDetails(gisid, callback) {
                $.ajax({
                    url: '/commissioner/get-point-details',
                    method: 'GET',
                    data: {
                        gisid: gisid,
                        ward_id: {{ $ward->id }}
                    },
                    success: function(res) {
                        if (res.status) {
                            callback(res.data);
                        } else {
                            showFlashMessage('Failed to load assessment data', 'error');
                            callback([]);
                        }
                    },
                    error: function() {
                        showFlashMessage('Failed to load assessment data', 'error');
                        callback([]);
                    }
                });
            }

            // ─── POINT DETAILS ───
            function openPointDetails(gisid) {
                currentPointGisid = gisid;
                $('#pointDetailsSearch').val('');
                $('#pdGisid').text(gisid);

                getPointDataWithDetails(gisid, function(data) {
                    currentPointRecords = data;
                    renderPointDetails(data);
                    const building = polygonDatas.find(p => p.gisid == gisid);
                    const billCount = building ? (building.number_bill || 0) : 0;
                    $('#pdBillSummary').text(`${data.length} of ${billCount} bills mapped`);
                });

                const modal = new bootstrap.Modal(document.getElementById('pointDetailsModal'));
                modal.show();
            }

            function renderPointDetails(records) {
                if (!records || !records.length) {
                    $('#pointDetailsContainer').html(
                        '<div class="bld-empty-state text-muted"><i class="bi bi-inbox fs-2"></i><p class="mt-2 mb-0">No assessment records found</p></div>'
                    );
                    return;
                }

                // helper to safely print a value
                const v = (val) => (val === null || val === undefined || val === '') ?
                    '<span class="text-muted">-</span>' : val;

                let html = '';
                records.forEach(record => {
                    const pd = record.point || {};
                    const mis = record.mis || {};
                    const wt = record.water_tax || {};
                    const ugd = record.ugd_tax || {};
                    const ptList = record.professional_tax || [];

                    const qcFilled = [pd.qcusage, pd.qcsqfeet, pd.qc_remarks]
                        .filter(val => val !== null && val !== '' && val !== undefined).length;
                    const qcClass = qcFilled === 3 ? 'complete' : qcFilled === 0 ? 'empty' : 'partial';
                    const qcLabel = qcFilled === 3 ? 'QC Complete' : qcFilled === 0 ? 'QC Pending' :
                        'QC Partial';

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

                            <!-- ── POINT / ASSESSMENT DETAILS ── -->
                            <div class="tax-card-title mt-2"><i class="bi bi-person-badge me-1"></i>Point / Assessment Details</div>
                            <div class="point-data-card-grid" style="display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-bottom:10px;">
                                <div class="pdc-field"><div class="pdc-field-label">Assessment Type</div><div class="pdc-field-val">${v(pd.assessment_type)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Old Assessment</div><div class="pdc-field-val">${v(pd.old_assessment)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Worker Name</div><div class="pdc-field-val">${v(pd.worker_name)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Present Owner</div><div class="pdc-field-val">${v(pd.present_owner_name)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">EB Number</div><div class="pdc-field-val">${v(pd.eb)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Floor</div><div class="pdc-field-val">${v(pd.floor)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Bill Usage</div><div class="pdc-field-val">${v(pd.bill_usage)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Aadhar No</div><div class="pdc-field-val">${v(pd.aadhar_no)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Ration No</div><div class="pdc-field-val">${v(pd.ration_no)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Phone</div><div class="pdc-field-val">${v(pd.phone_number)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Old Door No</div><div class="pdc-field-val">${v(pd.old_door_no)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">New Door No</div><div class="pdc-field-val">${v(pd.new_door_no)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Plot Area</div><div class="pdc-field-val">${v(pd.plot_area)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Water Tax No</div><div class="pdc-field-val">${v(pd.water_tax)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Half Year Tax</div><div class="pdc-field-val">${v(pd.halfyeartax)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Balance</div><div class="pdc-field-val">${v(pd.balance)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">No. of Persons</div><div class="pdc-field-val">${v(pd.no_of_persons)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Zone</div><div class="pdc-field-val">${v(pd.zone)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Remarks</div><div class="pdc-field-val">${v(pd.remarks)}</div></div>
                            </div>

                            <!-- ── QC DETAILS ── -->
                            <div class="tax-card-title mt-2"><i class="bi bi-clipboard-check me-1"></i>QC Details</div>
                            <div class="point-data-card-grid" style="display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-bottom:10px;">
                                <div class="pdc-field"><div class="pdc-field-label">QC Usage</div><div class="pdc-field-val ${!pd.qcusage ? 'empty' : ''}">${v(pd.qcusage)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">QC Sq.Feet</div><div class="pdc-field-val ${!pd.qcsqfeet ? 'empty' : ''}">${v(pd.qcsqfeet)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">QC By</div><div class="pdc-field-val">${v(pd.qc_name)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">QC Remarks</div><div class="pdc-field-val">${v(pd.qc_remarks)}</div></div>
                            </div>

                            <!-- ── MIS / TAX SUMMARY ── -->
                            <div class="row mt-2 g-2">
                                <div class="col-md-3">
                                    <div class="tax-card">
                                        <div class="tax-card-title"><i class="bi bi-database me-1"></i>MIS Record</div>
                                        <div class="tax-card-row"><span class="tax-card-label">Road</span><span class="tax-card-value">${v(mis.road_name)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Ward No</span><span class="tax-card-value">${v(mis.ward_no)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Plot Area</span><span class="tax-card-value">${v(mis.plot_area)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Half Yr Tax</span><span class="tax-card-value">${v(mis.half_year_tax)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Balance</span><span class="tax-card-value">${v(mis.balance)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Usage</span><span class="tax-card-value">${v(mis.usage)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Type</span><span class="tax-card-value">${v(mis.type)}</span></div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="tax-card">
                                        <div class="tax-card-title"><i class="bi bi-droplet me-1"></i>Water Tax</div>
                                        <div class="tax-card-row"><span class="tax-card-label">Number</span><span class="tax-card-value">${v(wt.watertax_no)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Old Number</span><span class="tax-card-value">${v(wt.old_watertax_no)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Road</span><span class="tax-card-value">${v(wt.road_name)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Slab Rate</span><span class="tax-card-value">${v(wt.slab_rate)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Balance</span><span class="tax-card-value">${v(wt.balance)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Usage</span><span class="tax-card-value">${v(wt.usage)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Slab Desc</span><span class="tax-card-value">${v(wt.slab_description)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">DBC Type</span><span class="tax-card-value">${v(wt.DBC_type)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Phone</span><span class="tax-card-value">${v(wt.phone_number)}</span></div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="tax-card">
                                        <div class="tax-card-title"><i class="bi bi-pipe me-1"></i>UGD Tax</div>
                                        <div class="tax-card-row"><span class="tax-card-label">Number</span><span class="tax-card-value">${v(ugd.ugd_no)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Old Number</span><span class="tax-card-value">${v(ugd.old_ugd_no)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Owner</span><span class="tax-card-value">${v(ugd.owner_name)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Slab Rate</span><span class="tax-card-value">${v(ugd.slab_rate)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Balance</span><span class="tax-card-value">${v(ugd.balance)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Tax Year</span><span class="tax-card-value">${v(ugd.tax_year)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Tax Amount</span><span class="tax-card-value">${v(ugd.ugd_tax_amount)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Tax Due</span><span class="tax-card-value">${v(ugd.ugd_tax_due)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Tax Paid</span><span class="tax-card-value">${v(ugd.ugd_tax_paid)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Paid Date</span><span class="tax-card-value">${v(ugd.ugd_tax_paid_date)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Payment Mode</span><span class="tax-card-value">${v(ugd.payment_mode)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Receipt No</span><span class="tax-card-value">${v(ugd.receipt_number)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Due Date</span><span class="tax-card-value">${v(ugd.due_date)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Status</span><span class="tax-card-value">${v(ugd.status)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Remarks</span><span class="tax-card-value">${v(ugd.remarks)}</span></div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="tax-card">
                                        <div class="tax-card-title"><i class="bi bi-briefcase me-1"></i>Professional Tax (${ptList.length})</div>
                                        ${ptList.length ? ptList.map(pt => `
                                                                    <div style="border-bottom:1px dashed #e5e7eb; padding:6px 0; margin-bottom:4px;">
                                                                        <div class="tax-card-row"><span class="tax-card-label">PT No</span><span class="tax-card-value">${v(pt.pt_number)}</span></div>
                                                                        <div class="tax-card-row"><span class="tax-card-label">Old PT No</span><span class="tax-card-value">${v(pt.old_pt_number)}</span></div>
                                                                        <div class="tax-card-row"><span class="tax-card-label">Establishment</span><span class="tax-card-value">${v(pt.establishment_name)}</span></div>
                                                                        <div class="tax-card-row"><span class="tax-card-label">Profession</span><span class="tax-card-value">${v(pt.profession_type)}</span></div>
                                                                        <div class="tax-card-row"><span class="tax-card-label">Employees</span><span class="tax-card-value">${v(pt.employee_count)}</span></div>
                                                                        <div class="tax-card-row"><span class="tax-card-label">Half Yr Tax</span><span class="tax-card-value">${v(pt.half_year_tax)}</span></div>
                                                                        <div class="tax-card-row"><span class="tax-card-label">Arrears</span><span class="tax-card-value">${v(pt.arrears)}</span></div>
                                                                        <div class="tax-card-row"><span class="tax-card-label">Penalty</span><span class="tax-card-value">${v(pt.penalty)}</span></div>
                                                                        <div class="tax-card-row"><span class="tax-card-label">Balance</span><span class="tax-card-value">${v(pt.balance)}</span></div>
                                                                        <div class="tax-card-row"><span class="tax-card-label">Status</span><span class="tax-card-value">${v(pt.payment_status)}</span></div>
                                                                        <div class="tax-card-row"><span class="tax-card-label">Remarks</span><span class="tax-card-value">${v(pt.remarks)}</span></div>
                                                                    </div>
                                                                `).join('') : '<div class="tax-card-row"><span class="tax-card-label">No records</span></div>'}
                                    </div>
                                </div>
                            </div>
                        </div>`;
                });

                $('#pointDetailsContainer').html(html);

                $('#pointDetailsSearch').off('input').on('input', function() {
                    const searchVal = $(this).val().toLowerCase();
                    if (!searchVal) {
                        renderPointDetails(records);
                        return;
                    }
                    const filtered = records.filter(record => {
                        const pd = record.point || {};
                        return (pd.assessment || '').toString().toLowerCase().includes(searchVal) ||
                            (pd.owner_name || '').toLowerCase().includes(searchVal) ||
                            (pd.phone_number || '').toString().toLowerCase().includes(searchVal);
                    });
                    renderPointDetails(filtered);
                });
            }

            function openQcModal(id) {
                const record = currentPointRecords.find(r => r.point && r.point.id == id);
                const pd = record ? record.point : null;
                if (!pd) {
                    showFlashMessage('Could not find this assessment record.', 'error');
                    return;
                }
                $('#qc_point_data_id').val(id);
                $('#qc_owner_display').text(pd.owner_name || '');
                $('#qc_assessment_display').text(pd.assessment || '');
                $('#qcusage').val(pd.qcusage || '');
                $('#qcsqfeet').val(pd.qcsqfeet || '');
                $('#qc_remarks').val(pd.qc_remarks || '');
                const modal = new bootstrap.Modal(document.getElementById('qcModal'));
                modal.show();
            }
            // update the save handler so the in-memory records refresh too
            $(document).on('click', '#saveQcBtn', function() {
                const id = $('#qc_point_data_id').val();
                const $btn = $(this).prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span> Saving...');

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
                        showFlashMessage('QC data saved successfully!', 'success');

                        if (currentPointGisid) {
                            getPointDataWithDetails(currentPointGisid, function(data) {
                                currentPointRecords =
                                    data; // <-- NEW, keeps modal list in sync
                                renderPointDetails(data);
                            });
                        }
                    },
                    error: function(xhr) {
                        showFlashMessage(xhr.responseJSON?.message || 'Failed to save QC data.',
                            'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(
                            '<i class="bi bi-save me-1"></i>Save QC');
                    }
                });
            });

            $(document).on('click', '.pdc-qc-btn', function() {
                openQcModal($(this).data('id'));
            });

            // ─── CLICK HANDLERS ───
            function pointClick(feature) {
                const gisid = feature.get('gisid');
                let building = polygonDatas.find(polygondata => polygondata.gisid == gisid);

                if (building) {
                    openPointDetails(gisid);
                } else {
                    showFlashMessage('No building data found for this point', 'warning');
                }
            }

            function polygonClick(feature) {
                const gisid = feature.get('gisid');
                let building = polygonDatas.find(polygondata => polygondata.gisid == gisid);

                if (building) {
                    showBuildingView(building);
                } else {
                    showFlashMessage('No building data found for this GIS ID', 'warning');
                }
            }

            function lineClick(feature) {
                const gisid = feature.get('gisid');
                const roadName = feature.get('road_name') || '';
                $('#line_gisid').val(gisid || '');
                $('#line_road_name').val(roadName || '');
                const modal = new bootstrap.Modal(document.getElementById('lineDetailsModal'));
                modal.show();
            }

            function showFeatureDetails(feature) {
                if (!feature) return;
                const type = feature.get('type');
                switch (type) {
                    case 'Point':
                        pointClick(feature);
                        break;
                    case 'Polygon':
                        polygonClick(feature);
                        break;
                    case 'LineString':
                        lineClick(feature);
                        break;
                }
            }

            // ─── VIEW MODE ───
            function setNoneMode() {
                disableAllInteractions();
                hideSplitButton();
                hideEditControls();

                const viewInter = new ol.interaction.Select({
                    layers: [polygonLayer, lineLayer, pointLayer],
                    style: new ol.style.Style({
                        stroke: new ol.style.Stroke({
                            color: '#0066cc',
                            width: 2,
                            lineDash: [4, 4]
                        }),
                        fill: new ol.style.Fill({
                            color: 'rgba(0,102,204,0.05)'
                        }),
                        image: new ol.style.Circle({
                            radius: 6,
                            fill: new ol.style.Fill({
                                color: '#0066cc'
                            }),
                            stroke: new ol.style.Stroke({
                                color: '#fff',
                                width: 2
                            })
                        })
                    })
                });

                viewInter.on('select', function(e) {
                    if (e.selected.length > 0) {
                        showFeatureDetails(e.selected[0]);
                        setTimeout(() => viewInter.getFeatures().clear(), 100);
                    }
                });

                map.addInteraction(viewInter);
                selectInteraction = viewInter;
                showToast('👁️ Click on features to view details', 2000);
            }

            // ─── SEARCH ───
            function buildSearchIndex() {
                searchIndex = [];
                polygons.forEach(poly => {
                    try {
                        searchIndex.push({
                            id: poly.gisid,
                            type: 'polygon',
                            title: `GIS ID: ${poly.gisid}`,
                            subtitle: `Building (${poly.sqfeet || 0} sqft)`,
                            coordinates: JSON.parse(poly.coordinates),
                            geometryType: 'polygon',
                            searchText: `${poly.gisid} ${poly.sqfeet} building`
                        });
                    } catch (e) {
                        console.error('Error parsing polygon:', e);
                    }
                });
                lines.forEach(line => {
                    try {
                        const coords = typeof line.coordinates === 'string' ? JSON.parse(line.coordinates) :
                            line.coordinates;
                        searchIndex.push({
                            id: line.gisid,
                            type: 'line',
                            title: line.road_name || `GIS ID: ${line.gisid}`,
                            subtitle: `Road (GIS ID: ${line.gisid})`,
                            coordinates: coords,
                            geometryType: 'line',
                            searchText: `${line.gisid} ${line.road_name || ''} road`
                        });
                    } catch (e) {
                        console.error('Error parsing line:', e);
                    }
                });
                points.forEach(point => {
                    try {
                        searchIndex.push({
                            id: point.gisid,
                            type: 'point',
                            title: `GIS ID: ${point.gisid}`,
                            subtitle: 'Point Location',
                            coordinates: JSON.parse(point.coordinates),
                            geometryType: 'point',
                            searchText: `${point.gisid} point`
                        });
                    } catch (e) {
                        console.error('Error parsing point:', e);
                    }
                });
                pointDatas.forEach(pd => {
                    try {
                        searchIndex.push({
                            id: pd.id,
                            point_gisid: pd.point_gisid,
                            type: 'pointdata',
                            title: `Assessment: ${pd.assessment}`,
                            subtitle: `GIS ID: ${pd.point_gisid} | Owner: ${pd.owner_name}`,
                            coordinates: JSON.parse(pd.coordinates || '[]'),
                            geometryType: 'point',
                            assessment: pd.assessment,
                            searchText: `${pd.gisid} ${pd.assessment} ${pd.owner_name} ${pd.phone_number}`
                        });
                    } catch (e) {
                        console.error('Error parsing pointData:', e);
                    }
                });
            }

            function searchGIS(value) {
                const v = value.toString().toLowerCase().trim();
                if (!v) return [];
                return searchIndex.filter(item =>
                    (item.id && item.id.toString().toLowerCase().includes(v)) ||
                    (item.assessment && item.assessment.toString().toLowerCase().includes(v)) ||
                    (item.title && item.title.toLowerCase().includes(v)) ||
                    (item.subtitle && item.subtitle.toLowerCase().includes(v)) ||
                    (item.point_gisid && item.point_gisid.toString().toLowerCase().includes(v))
                );
            }

            function zoomToFeature(gisid) {
                const coords = getCoordsByGisId(gisid);
                if (!coords) {
                    showToast(`⚠️ No point found for GIS ID: ${gisid}`, 3000);
                    return;
                }
                map.getView().animate({
                    center: coords,
                    zoom: 22,
                    duration: 1000
                });
            }

            function getCoordsByGisId(gisid) {
                const point = points.find(p => p.gisid && p.gisid.toString() === gisid.toString());
                if (!point) return null;
                try {
                    const coords = typeof point.coordinates === 'string' ?
                        JSON.parse(point.coordinates) :
                        point.coordinates;
                    return coords;
                } catch (e) {
                    console.error('Coord parse error for gisid:', gisid, e);
                    return null;
                }
            }

            // ─── ROUTING ───
            function getCurrentLocation(callback) {
                if (!navigator.geolocation) {
                    Swal.fire('Error', 'Geolocation not supported', 'error');
                    callback(null);
                    return false;
                }

                if (currentLocation) {
                    callback(currentLocation);
                    return true;
                }

                navigator.geolocation.getCurrentPosition(
                    function(pos) {
                        currentLocation = {
                            lon: pos.coords.longitude,
                            lat: pos.coords.latitude
                        };
                        callback(currentLocation);
                    },
                    function(error) {
                        Swal.fire('Location Error', 'Could not get your location', 'error');
                        callback(null);
                    }, {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0
                    }
                );
                return true;
            }

            function getRoute(startLon, startLat, endLon, endLat) {
                const url =
                    `https://router.project-osrm.org/route/v1/driving/${startLon},${startLat};${endLon},${endLat}?overview=full&geometries=geojson`;
                showToast('🗺️ Calculating route...', 2000);
                fetch(url).then(r => r.json()).then(data => {
                    if (!data.routes || !data.routes.length) {
                        Swal.fire('Error', 'No route found', 'error');
                        return;
                    }
                    const routeCoords = data.routes[0].geometry.coordinates.map(c => ol.proj.fromLonLat(c));
                    const routeFeature = new ol.Feature({
                        geometry: new ol.geom.LineString(routeCoords)
                    });
                    if (routeLayer) map.removeLayer(routeLayer);
                    routeLayer = new ol.layer.Vector({
                        source: new ol.source.Vector({
                            features: [routeFeature]
                        }),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#0066ff',
                                width: 5,
                                lineDash: [10, 5]
                            })
                        })
                    });
                    map.addLayer(routeLayer);
                    const ext = routeFeature.getGeometry().getExtent();
                    if (ext && ext[0] !== ext[2]) map.getView().fit(ext, {
                        padding: [50, 50, 50, 50],
                        duration: 1000
                    });
                    const dist = (data.routes[0].distance / 1000).toFixed(2);
                    const dur = Math.round(data.routes[0].duration / 60);
                    showToast(`✅ Route found! Distance: ${dist}km, Time: ${dur}min`, 4000);
                }).catch(() => {
                    Swal.fire('Error', 'Failed to calculate route', 'error');
                });
            }

            function getDirectionToFeature(feature) {
                getCurrentLocation(function(loc) {
                    if (!loc) return;
                    const coords = getCoordsByGisId(feature.id);
                    if (!coords) {
                        Swal.fire('Error', `No coordinates for GIS ID: ${feature.id}`, 'error');
                        return;
                    }
                    const lonLat = ol.proj.toLonLat(coords);
                    getRoute(loc.lon, loc.lat, lonLat[0], lonLat[1]);
                });
            }

            function clearRoute() {
                if (routeLayer) {
                    map.removeLayer(routeLayer);
                    routeLayer = null;
                    showToast('🗑️ Route cleared', 2000);
                }
            }

            // ─── LAYER UTILS ───
            const $mapContainer = $('#map');
            const $mapCard = $('#mapCard');
            const $activeLayerBadge = $('#activeLayerBadge');

            function getActiveBaseLayerTitle() {
                return [osmLayer, satelliteLayer, streetLayer].find(l => l.getVisible())?.get('title') ||
                    'OpenStreetMap';
            }

            function updateLayerUI() {
                const activeTitle = getActiveBaseLayerTitle();
                const droneVisible = droneLayer.getVisible();
                $activeLayerBadge.text(droneVisible ? activeTitle + ' + Drone' : activeTitle);
                $('.layer-dropdown-item[data-layer-type="base"]').removeClass('active');
                $(`.layer-dropdown-item[data-layer="${activeTitle}"]`).addClass('active');
                const droneItem = $('.layer-dropdown-item[data-layer="Drone View"]');
                droneVisible ? droneItem.addClass('active') : droneItem.removeClass('active');
            }

            function switchBaseLayer(selectedLayer) {
                [osmLayer, satelliteLayer, streetLayer].forEach(l => l.setVisible(l === selectedLayer));
                updateLayerUI();
            }

            function toggleDroneLayer() {
                droneLayer.setVisible(!droneLayer.getVisible());
                updateLayerUI();
            }

            // ─── UI INJECTION ───
            $mapContainer.append(`
                <div class="custom-layer-switcher">
                    <div class="layer-toggle-btn"><i class="bi bi-layers"></i></div>
                    <div class="layer-dropdown">
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
                        <div class="layer-dropdown-item" data-layer-type="overlay" data-layer="Drone View">
                            <div class="layer-icon"><i class="bi bi-camera-drone"></i></div>
                            <div class="layer-name">Drone View</div>
                            <div class="layer-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                    </div>
                </div>
            `);

            $mapContainer.append(`
                <div class="custom-location-switcher">
                    <div class="location-toggle-btn" id="locationToggleBtn"><i class="bi bi-geo-alt"></i></div>
                    <div class="location-dropdown" id="locationDropdown">
                        <div class="dropdown-header">Location Tools</div>
                        <div class="location-dropdown-item" id="liveLocationItem" data-action="live">
                            <div class="location-item-icon"><i class="bi bi-crosshair2"></i></div>
                            <div class="location-item-name">Live Location</div>
                            <div class="location-item-badge" id="liveLocationBadge">OFF</div>
                        </div>
                        <div class="location-dropdown-item" id="trackMeItem" data-action="track">
                            <div class="location-item-icon"><i class="bi bi-broadcast"></i></div>
                            <div class="location-item-name">Track Me</div>
                            <div class="location-item-badge" id="trackMeBadge">OFF</div>
                        </div>
                        <div class="location-dropdown-item" id="clearRouteItem">
                            <div class="location-item-icon"><i class="bi bi-x-circle"></i></div>
                            <div class="location-item-name">Clear Route</div>
                        </div>
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
                            <div class="p-3">
                                <input type="text" id="gisSearchInput" class="form-control" placeholder="Search by GIS ID or Assessment...">
                            </div>
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
                        <div class="edit-dropdown-item active" data-tool="none">
                            <div class="edit-icon"><i class="bi bi-eye"></i></div>
                            <div class="edit-name">View Only</div>
                            <div class="edit-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-header">✏️ Edit</div>
                        <div class="edit-dropdown-item" data-tool="editPolygon">
                            <div class="edit-icon"><i class="bi bi-pencil"></i></div>
                            <div class="edit-name">Edit Polygon</div>
                            <div class="edit-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                        <div class="edit-dropdown-item" data-tool="movePolygon">
                            <div class="edit-icon"><i class="bi bi-arrows-move"></i></div>
                            <div class="edit-name">Move Polygon</div>
                            <div class="edit-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                        <div class="edit-dropdown-item" data-tool="split">
                            <div class="edit-icon"><i class="bi bi-scissors"></i></div>
                            <div class="edit-name">Split Polygon</div>
                            <div class="edit-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-header">✏️ Drawing</div>
                        <div class="edit-dropdown-item" data-tool="drawPolygon">
                            <div class="edit-icon"><i class="bi bi-pentagon"></i></div>
                            <div class="edit-name">Draw Polygon</div>
                            <div class="edit-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                        <div class="edit-dropdown-item" data-tool="drawLine">
                            <div class="edit-icon"><i class="bi bi-vector-pen"></i></div>
                            <div class="edit-name">Draw Line</div>
                            <div class="edit-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                        <div class="edit-dropdown-item" data-tool="drawPoint">
                            <div class="edit-icon"><i class="bi bi-geo-alt"></i></div>
                            <div class="edit-name">Draw Point</div>
                            <div class="edit-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-header">🗑️ Delete</div>
                        <div class="edit-dropdown-item" data-tool="delete">
                            <div class="edit-icon"><i class="bi bi-trash3"></i></div>
                            <div class="edit-name">Delete Feature</div>
                            <div class="edit-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                    </div>
                </div>
            `);

            $mapContainer.append(
                `<div class="fullscreen-btn" id="fullscreenBtn"><i class="bi bi-arrows-fullscreen"></i></div>`);

            // ─── EVENT HANDLERS ───

            // Edit toggle
            $(document).on('click', '#editToggleBtn', function(e) {
                e.stopPropagation();
                $('#editDropdown').toggleClass('show');
                $(this).toggleClass('active-edit');
                $('#locationDropdown').removeClass('show');
                $('.layer-dropdown').removeClass('show');
                $('#searchDropdown').removeClass('show');
                $('#searchToggleBtn').removeClass('active-search');
            });

            $(document).on('click', '.edit-dropdown-item', function(e) {
                e.stopPropagation();
                const tool = $(this).data('tool');

                if (tool === 'delete') {
                    $('#deleteGisId').val('');
                    $('#deleteGisError').hide().text('');
                    $('#deleteConfirmBox').hide();
                    $('.delete-type-btn').removeClass('active');
                    $('.delete-type-btn[data-type="polygon"]').addClass('active');
                    $('#deleteFeatureType').val('polygon');
                    $('#editDropdown').removeClass('show');
                    $('#editToggleBtn').removeClass('active-edit');
                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteFeatureModal'));
                    deleteModal.show();
                    return;
                }

                $('.edit-dropdown-item').removeClass('active');
                $(this).addClass('active');

                switch (tool) {
                    case 'none':
                        setNoneMode();
                        break;
                    case 'editPolygon':
                        setEditPolygonMode();
                        break;
                    case 'movePolygon':
                        setMovePolygonMode();
                        break;
                    case 'split':
                        setSplitMode();
                        break;
                    case 'drawPolygon':
                        startDrawing('Polygon');
                        break;
                    case 'drawLine':
                        startDrawing('LineString');
                        break;
                    case 'drawPoint':
                        startDrawing('Point');
                        break;
                }

                $('#editDropdown').removeClass('show');
                $('#editToggleBtn').removeClass('active-edit');
            });

            // ─── EDIT POLYGON MODE ───
            function setEditPolygonMode() {
                disableAllInteractions();
                hideSplitButton();
                hideEditControls();
                selectedFeatureForEdit = null;
                originalGeometry = null;

                map.getTargetElement().classList.add('edit-mode');

                const editSelect = new ol.interaction.Select({
                    layers: [polygonLayer],
                    style: new ol.style.Style({
                        stroke: new ol.style.Stroke({
                            color: '#2563eb',
                            width: 4
                        }),
                        fill: new ol.style.Fill({
                            color: 'rgba(37,99,235,0.2)'
                        })
                    })
                });

                editSelect.on('select', function(e) {
                    if (selectedFeatureForEdit) {
                        selectedFeatureForEdit.setStyle(null);
                        selectedFeatureForEdit = null;
                        originalGeometry = null;
                        hideEditControls();
                    }

                    if (e.selected.length > 0) {
                        const feature = e.selected[0];
                        if (feature.get('type') !== 'Polygon') {
                            showToast('⚠️ Please select a Polygon', 2000);
                            return;
                        }

                        selectedFeatureForEdit = feature;
                        originalGeometry = feature.getGeometry().clone();

                        feature.setStyle(new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#2563eb',
                                width: 5
                            }),
                            fill: new ol.style.Fill({
                                color: 'rgba(37,99,235,0.3)'
                            })
                        }));

                        showEditControls(feature);
                        showToast(`✏️ Editing Polygon (ID: ${feature.get('gisid')})`, 2000);
                    }
                });

                map.addInteraction(editSelect);
                selectInteraction = editSelect;
                showToast('✏️ Click a polygon to edit', 2000);
            }

            // ─── Edit Controls ───
            function showEditControls(feature) {
                hideEditControls();
                const gisid = feature.get('gisid');
                const $controls = $(`
                    <div class="edit-controls show" id="editControls">
                        <span class="fw-semibold" style="font-size:0.85rem;">
                            <i class="bi bi-pencil-square me-1"></i>Editing: ${gisid}
                        </span>
                        <button class="btn btn-save" id="saveEditBtn">
                            <i class="bi bi-check-lg"></i> Save
                        </button>
                        <button class="btn btn-cancel" id="cancelEditBtn">
                            <i class="bi bi-x-lg"></i> Cancel
                        </button>
                    </div>
                `);
                $('#map').append($controls);

                modifyInteraction = new ol.interaction.Modify({
                    source: polygonSource,
                    features: new ol.Collection([feature])
                });
                map.addInteraction(modifyInteraction);

                $('#saveEditBtn').on('click', function() {
                    if (!selectedFeatureForEdit) return;
                    saveEditedFeature(selectedFeatureForEdit);
                });

                $('#cancelEditBtn').on('click', function() {
                    if (selectedFeatureForEdit && originalGeometry) {
                        selectedFeatureForEdit.setGeometry(originalGeometry);
                    }
                    cancelEdit();
                });
            }

            function hideEditControls() {
                $('#editControls').remove();
                if (modifyInteraction) {
                    map.removeInteraction(modifyInteraction);
                    modifyInteraction = null;
                }
            }

            function cancelEdit() {
                if (selectedFeatureForEdit) {
                    selectedFeatureForEdit.setStyle(null);
                    selectedFeatureForEdit = null;
                    originalGeometry = null;
                }
                hideEditControls();
                if (selectInteraction) {
                    selectInteraction.getFeatures().clear();
                }
                showToast('❌ Edit cancelled', 2000);
                setNoneMode();
            }

            function saveEditedFeature(feature) {
                if (!feature) return;

                const gisid = feature.get('gisid');
                const geometry = feature.getGeometry();
                const coordinates = geometry.getCoordinates();

                $.ajax({
                    url: '/update-polygon',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        gisid: gisid,
                        coordinates: JSON.stringify(coordinates),
                        sqfeet: feature.get('sqfeet') || '0'
                    },
                    success: function(response) {
                        Swal.fire('Success!', 'Polygon updated successfully', 'success');
                        polygons = response.data.polygons ?? polygons;
                        points = response.data.points ?? points;
                        lines = response.data.lines ?? lines;
                        reloadAllSources();
                        disableAllInteractions();
                        clearDrawInteraction();
                        selectedFeatureForEdit.setStyle(null);
                        selectedFeatureForEdit = null;
                        originalGeometry = null;
                        hideEditControls();
                        showToast('✅ Polygon updated!', 2000);
                        setNoneMode();
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Failed to update polygon', 'error');
                        cancelEdit();
                    }
                });
            }

            // ─── MOVE MODE ───
            function setMovePolygonMode() {
                disableAllInteractions();
                hideSplitButton();
                hideEditControls();
                selectedFeatureForEdit = null;

                map.getTargetElement().classList.add('edit-mode');

                const moveSelect = new ol.interaction.Select({
                    layers: [polygonLayer],
                    style: new ol.style.Style({
                        stroke: new ol.style.Stroke({
                            color: '#f59e0b',
                            width: 4
                        }),
                        fill: new ol.style.Fill({
                            color: 'rgba(245,158,11,0.2)'
                        })
                    })
                });

                moveSelect.on('select', function(e) {
                    if (selectedFeatureForEdit) {
                        selectedFeatureForEdit.setStyle(null);
                        selectedFeatureForEdit = null;
                        hideEditControls();
                    }

                    if (e.selected.length > 0) {
                        const feature = e.selected[0];
                        if (feature.get('type') !== 'Polygon') {
                            showToast('⚠️ Please select a Polygon', 2000);
                            return;
                        }

                        selectedFeatureForEdit = feature;
                        originalGeometry = feature.getGeometry().clone();

                        feature.setStyle(new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#f59e0b',
                                width: 5
                            }),
                            fill: new ol.style.Fill({
                                color: 'rgba(245,158,11,0.3)'
                            })
                        }));

                        translateInteraction = new ol.interaction.Translate({
                            features: new ol.Collection([feature])
                        });

                        translateInteraction.on('translateend', function() {
                            showMoveControls(feature);
                        });

                        map.addInteraction(translateInteraction);
                        showToast(`↕️ Moving Polygon (ID: ${feature.get('gisid')})`, 2000);
                        showMoveControls(feature);
                    }
                });

                map.addInteraction(moveSelect);
                selectInteraction = moveSelect;
                showToast('↕️ Click a polygon to move it', 2000);
            }

            function showMoveControls(feature) {
                hideEditControls();
                const gisid = feature.get('gisid');
                const $controls = $(`
                    <div class="edit-controls show" id="editControls">
                        <span class="fw-semibold" style="font-size:0.85rem;">
                            <i class="bi bi-arrows-move me-1"></i>Moving: ${gisid}
                        </span>
                        <button class="btn btn-save" id="saveMoveBtn">
                            <i class="bi bi-check-lg"></i> Save
                        </button>
                        <button class="btn btn-cancel" id="cancelMoveBtn">
                            <i class="bi bi-x-lg"></i> Cancel
                        </button>
                    </div>
                `);
                $('#map').append($controls);

                $('#saveMoveBtn').on('click', function() {
                    if (!selectedFeatureForEdit) return;
                    saveMovedFeature(selectedFeatureForEdit);
                });

                $('#cancelMoveBtn').on('click', function() {
                    if (selectedFeatureForEdit && originalGeometry) {
                        selectedFeatureForEdit.setGeometry(originalGeometry);
                    }
                    cancelMove();
                });
            }

            function cancelMove() {
                if (selectedFeatureForEdit) {
                    selectedFeatureForEdit.setStyle(null);
                    selectedFeatureForEdit = null;
                    originalGeometry = null;
                }
                hideEditControls();
                if (translateInteraction) {
                    map.removeInteraction(translateInteraction);
                    translateInteraction = null;
                }
                if (selectInteraction) {
                    selectInteraction.getFeatures().clear();
                }
                showToast('❌ Move cancelled', 2000);
                setNoneMode();
            }

            function saveMovedFeature(feature) {
                if (!feature) return;

                const gisid = feature.get('gisid');
                const geometry = feature.getGeometry();
                const coordinates = geometry.getCoordinates();

                $.ajax({
                    url: '/update-polygon',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        gisid: gisid,
                        coordinates: JSON.stringify(coordinates),
                        sqfeet: feature.get('sqfeet') || '0'
                    },
                    success: function(response) {
                        Swal.fire('Success!', 'Polygon moved successfully', 'success');
                        polygons = response.data.polygons ?? polygons;
                        points = response.data.points ?? points;
                        lines = response.data.lines ?? lines;
                        reloadAllSources();
                        disableAllInteractions();
                        clearDrawInteraction();
                        selectedFeatureForEdit.setStyle(null);
                        selectedFeatureForEdit = null;
                        originalGeometry = null;
                        hideEditControls();
                        if (translateInteraction) {
                            map.removeInteraction(translateInteraction);
                            translateInteraction = null;
                        }
                        showToast('✅ Polygon moved!', 2000);
                        setNoneMode();
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Failed to move polygon', 'error');
                        cancelMove();
                    }
                });
            }

            // ─── SPLIT MODE ───
            function setSplitMode() {
                disableAllInteractions();
                hideSplitButton();
                selectedFeatureForSplit = null;

                const splitInter = new ol.interaction.Select({
                    layers: [polygonLayer],
                    style: null
                });

                splitInter.on('select', function(e) {
                    if (selectedFeatureForSplit) {
                        selectedFeatureForSplit.setStyle(null);
                        selectedFeatureForSplit = null;
                        hideSplitButton();
                    }
                    if (e.selected.length > 0) {
                        const feature = e.selected[0];
                        feature.setStyle(new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#dc3545',
                                width: 5
                            }),
                            fill: new ol.style.Fill({
                                color: 'rgba(220,53,69,0.3)'
                            })
                        }));
                        selectedFeatureForSplit = feature;
                        showToast(`✂️ Polygon selected (ID: ${feature.get('gisid')})`, 3000);
                        showSplitButton(feature);
                    }
                });

                map.addInteraction(splitInter);
                selectInteraction = splitInter;
                showToast('✂️ Click a polygon to split', 2000);
            }

            function showSplitButton(feature) {
                hideSplitButton();
                const gisid = feature.get('gisid');
                const $btn = $(`
                    <div class="split-action-btn show" id="splitActionBtn">
                        <i class="bi bi-scissors"></i>
                        Split Polygon (ID: ${gisid})
                        <span class="close-btn">✕</span>
                    </div>
                `);
                $('#map').append($btn);

                $btn.on('click', function(e) {
                    if (!$(e.target).hasClass('close-btn')) {
                        if (selectedFeatureForSplit) performSplit(selectedFeatureForSplit);
                    }
                });

                $btn.find('.close-btn').on('click', function(e) {
                    e.stopPropagation();
                    if (selectedFeatureForSplit) {
                        selectedFeatureForSplit.setStyle(null);
                        selectedFeatureForSplit = null;
                        if (selectInteraction) selectInteraction.getFeatures().clear();
                    }
                    hideSplitButton();
                    showToast('Split cancelled', 2000);
                });
            }

            function hideSplitButton() {
                $('#splitActionBtn').remove();
            }

            function performSplit(feature) {
                if (!feature || feature.get('type') !== 'Polygon') {
                    Swal.fire('Error', 'Please select a polygon first', 'error');
                    return;
                }

                disableAllInteractions();
                clearDrawInteraction();
                hideSplitButton();

                const splitLineSource = new ol.source.Vector();
                const splitDraw = new ol.interaction.Draw({
                    source: splitLineSource,
                    type: 'LineString'
                });
                map.addInteraction(splitDraw);
                showToast('✂️ Draw a line across the polygon', 3000);

                splitDraw.on('drawend', function(e) {
                    const polygonCoords = feature.getGeometry().getCoordinates();
                    const lineCoords = e.feature.getGeometry().getCoordinates();
                    const gisid = feature.get('gisid');

                    $.ajax({
                        url: '/polygon-split',
                        type: 'POST',
                        data: {
                            polygon: JSON.stringify(polygonCoords),
                            splitLine: JSON.stringify(lineCoords),
                            gisid,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire('Success!', 'Polygon split successfully', 'success');
                            map.removeInteraction(splitDraw);
                            splitLineSource.clear();
                            if (selectedFeatureForSplit) {
                                selectedFeatureForSplit.setStyle(null);
                                selectedFeatureForSplit = null;
                            }
                            hideSplitButton();
                            polygons = response.polygons ?? polygons;
                            points = response.points ?? points;
                            reloadAllSources();
                            disableAllInteractions();
                            clearDrawInteraction();
                            showToast('✅ Split complete', 2000);
                            setNoneMode();
                        },
                        error: function(xhr) {
                            Swal.fire('Error', 'Failed to split polygon', 'error');
                            map.removeInteraction(splitDraw);
                            splitLineSource.clear();
                            setNoneMode();
                        }
                    });
                });
            }

            // ─── DRAW ───
            function startDrawing(type) {
                disableAllInteractions();
                clearDrawInteraction();
                hideSplitButton();

                const geometryType = {
                    'Polygon': 'Polygon',
                    'LineString': 'LineString',
                    'Point': 'Point'
                } [type];
                if (!geometryType) return;

                map.getTargetElement().classList.add('draw-mode');

                drawInteraction = new ol.interaction.Draw({
                    source: tempDrawSource,
                    type: geometryType,
                    style: new ol.style.Style({
                        fill: new ol.style.Fill({
                            color: 'rgba(0,255,0,0.2)'
                        }),
                        stroke: new ol.style.Stroke({
                            color: '#00ff00',
                            width: 3
                        }),
                        image: new ol.style.Circle({
                            radius: 7,
                            fill: new ol.style.Fill({
                                color: '#00ff00'
                            })
                        })
                    })
                });

                drawInteraction.on('drawend', function(e) {
                    saveFeature(e.feature, type);
                });
                map.addInteraction(drawInteraction);
                showToast(`✏️ Drawing ${type}`, 2000);
            }

            function saveFeature(feature, type) {
                $.ajax({
                    url: '/save-feature',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        layer_type: type,
                        feature: JSON.stringify(feature.getGeometry().getCoordinates())
                    },
                    success: function(response) {
                        polygons = response.data.polygons ?? polygons;
                        points = response.data.points ?? points;
                        lines = response.data.lines ?? lines;
                        reloadAllSources();
                        disableAllInteractions();
                        clearDrawInteraction();
                        Swal.fire('Success', 'Feature saved successfully', 'success');
                        setNoneMode();
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Error saving feature', 'error');
                        setNoneMode();
                    }
                });
            }

            // ─── LAYER EVENTS ───
            $(document).on('click', '.layer-toggle-btn', function(e) {
                e.stopPropagation();
                $('.layer-dropdown').toggleClass('show');
                $('#locationDropdown').removeClass('show');
                $('#searchDropdown').removeClass('show');
                $('#editDropdown').removeClass('show');
                $('#searchToggleBtn').removeClass('active-search');
                $('#editToggleBtn').removeClass('active-edit');
            });

            $(document).on('click', '.layer-dropdown-item', function(e) {
                e.stopPropagation();
                const layerType = $(this).data('layer-type');
                const layerTitle = $(this).data('layer');
                if (layerType === 'base') {
                    switchBaseLayer(
                        layerTitle === 'Satellite' ? satelliteLayer :
                        layerTitle === 'Street View' ? streetLayer : osmLayer
                    );
                    $('.layer-dropdown').removeClass('show');
                } else if (layerTitle === 'Drone View') {
                    toggleDroneLayer();
                }
            });

            // ─── LOCATION EVENTS ───
            $(document).on('click', '#locationToggleBtn', function(e) {
                e.stopPropagation();
                $('#locationDropdown').toggleClass('show');
                $('.layer-dropdown').removeClass('show');
                $('#searchDropdown').removeClass('show');
                $('#editDropdown').removeClass('show');
                $('#searchToggleBtn').removeClass('active-search');
                $('#editToggleBtn').removeClass('active-edit');
            });

            $(document).on('click', '#liveLocationItem', function(e) {
                e.stopPropagation();
                if (!liveActive) {
                    liveActive = true;
                    startWatching();
                    showToast('📍 Live location enabled', 2000);
                } else {
                    liveActive = false;
                    trackActive = false;
                    stopWatching();
                    clearLiveMarker();
                    showToast('📍 Live location disabled', 2000);
                }
                syncLocationUI();
                $('#locationDropdown').removeClass('show');
            });

            $(document).on('click', '#trackMeItem', function(e) {
                e.stopPropagation();
                if (!trackActive) {
                    trackActive = true;
                    liveActive = true;
                    startWatching();
                    showToast('📍 Tracking enabled', 2000);
                } else {
                    trackActive = false;
                    if (!liveActive) {
                        stopWatching();
                        clearLiveMarker();
                    }
                    showToast('📍 Tracking disabled', 2000);
                }
                syncLocationUI();
                $('#locationDropdown').removeClass('show');
            });

            $(document).on('click', '#clearRouteItem', function(e) {
                e.stopPropagation();
                clearRoute();
                $('#locationDropdown').removeClass('show');
            });

            // ─── SEARCH EVENTS ───
            $(document).on('click', '#searchToggleBtn', function(e) {
                e.stopPropagation();
                $('#searchDropdown').toggleClass('show');
                $(this).toggleClass('active-search');
                $('#locationDropdown').removeClass('show');
                $('.layer-dropdown').removeClass('show');
                $('#editDropdown').removeClass('show');
                $('#editToggleBtn').removeClass('active-edit');
                if ($('#searchDropdown').hasClass('show')) setTimeout(() => $('#gisSearchInput').focus(),
                    100);
            });

            $(document).on('click', '.search-tab-btn', function() {
                $('.search-tab-btn').removeClass('active');
                $(this).addClass('active');
                const tab = $(this).data('tab');
                $('#quickSearchTab').toggle(tab === 'quick');
                $('#filterTab').toggle(tab === 'filter');
            });

            $(document).on('keyup', '#gisSearchInput', function() {
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
                            `${item.title} | Owner: ${item.subtitle.split('|')[1] || ''}` : item
                            .title;
                        const displaySubtitle = item.type === 'pointdata' ?
                            `GIS ID: ${item.point_gisid || 'N/A'}` : item.subtitle;
                        const icon = item.geometryType === 'point' ? 'geo-alt' :
                            item.geometryType === 'polygon' ? 'pentagon' : 'vector-pen';

                        html += `
                            <div class="search-result-item" data-id="${item.id}" data-type="${item.type}">
                                <div class="search-result-title"><i class="bi bi-${icon} me-2"></i>${displayTitle}</div>
                                <div class="search-result-subtitle">${displaySubtitle}</div>
                                <div class="mt-2 d-flex gap-2">
                                    <button class="btn btn-sm btn-success zoom-btn" data-id="${item.id}" data-type="${item.type}">Zoom</button>
                                    <button class="btn btn-sm btn-primary direction-btn" data-id="${item.id}" data-type="${item.type}">Direction</button>
                                </div>
                            </div>`;
                    });
                }
                $('#searchResults').html(html);
            });

            // ─── FILTER SEARCH ───
            $('#applyFilterBtn').on('click', function() {
                $.get('/point-data/filter', {
                    assessment: $('#filterAssessment').val(),
                    old_assessment: $('#filterOldAssessment').val(),
                    owner_name: $('#filterOwnerName').val(),
                    phone_number: $('#filterPhoneNumber').val()
                }, function(res) {
                    let html = '';
                    (res.data || []).forEach(pd => {
                        html += `
                            <div class="search-result-item">
                                <div class="search-result-title">${pd.owner_name || 'N/A'} — ${pd.assessment || 'N/A'}</div>
                                <div class="search-result-subtitle">GIS ID: ${pd.point_gisid || 'N/A'} | Phone: ${pd.phone_number || 'N/A'}</div>
                            </div>`;
                    });
                    $('#filterResults').html(html ||
                        '<div class="p-2 text-muted">No matches</div>');
                });
            });

            $(document).on('click', '.zoom-btn', function(e) {
                e.stopPropagation();
                const id = $(this).data('id');
                const type = $(this).data('type');
                const item = searchIndex.find(f => f.id == id && f.type === type);
                if (item) {
                    zoomToFeature(item.id);
                    $('#searchDropdown').removeClass('show');
                    $('#searchToggleBtn').removeClass('active-search');
                    $('#gisSearchInput').val('');
                    $('#searchResults').html('');
                }
            });

            $(document).on('click', '.direction-btn', function(e) {
                e.stopPropagation();
                const id = $(this).data('id');
                const type = $(this).data('type');
                const item = searchIndex.find(f => f.id == id && f.type === type);
                if (item) {
                    getDirectionToFeature(item);
                    $('#searchDropdown').removeClass('show');
                    $('#searchToggleBtn').removeClass('active-search');
                    $('#gisSearchInput').val('');
                    $('#searchResults').html('');
                }
            });

            // ─── FULLSCREEN ───
            let isFullscreen = false;
            $(document).on('click', '#fullscreenBtn', function() {
                const $icon = $(this).find('i');
                if (!isFullscreen) {
                    $mapCard.addClass('fullscreen-mode');
                    $mapContainer.addClass('fullscreen');
                    $icon.removeClass('bi-arrows-fullscreen').addClass('bi-fullscreen-exit');
                    isFullscreen = true;
                } else {
                    $mapCard.removeClass('fullscreen-mode');
                    $mapContainer.removeClass('fullscreen');
                    $icon.removeClass('bi-fullscreen-exit').addClass('bi-arrows-fullscreen');
                    isFullscreen = false;
                }
                setTimeout(() => map.updateSize(), 100);
            });

            // ─── DELETE MODAL ───
            $(document).on('click', '.delete-type-btn', function() {
                $('.delete-type-btn').removeClass('active');
                $(this).addClass('active');
                $('#deleteFeatureType').val($(this).data('type'));
                $('#deleteGisError').hide();
                $('#deleteConfirmBox').hide();
                $('#deleteGisId').val('');
            });

            $(document).on('input', '#deleteGisId', function() {
                if ($(this).val().trim().length > 0) {
                    $('#deleteConfirmBox').show();
                    $('#deleteGisError').hide();
                } else {
                    $('#deleteConfirmBox').hide();
                }
            });

            $(document).on('click', '#confirmDeleteBtn', function() {
                const type = $('#deleteFeatureType').val();
                const gisid = $('#deleteGisId').val().trim();

                if (!gisid) {
                    $('#deleteGisError').text('Please enter a GIS ID.').show();
                    return;
                }

                const $btn = $(this);
                $btn.html('<span class="spinner-border spinner-border-sm me-1"></span>Deleting…').prop(
                    'disabled', true);

                $.ajax({
                    url: '/delete-feature',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        type,
                        gisid
                    },
                    success: function(response) {
                        const deleteModal = bootstrap.Modal.getInstance(document.getElementById(
                            'deleteFeatureModal'));
                        if (deleteModal) deleteModal.hide();
                        $btn.html('<i class="bi bi-trash3 me-1"></i>Delete').prop('disabled',
                            false);

                        polygons = response.data.polygons ?? polygons;
                        points = response.data.points ?? points;
                        lines = response.data.lines ?? lines;

                        reloadAllSources();
                        disableAllInteractions();
                        setNoneMode();

                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: `${type.charAt(0).toUpperCase() + type.slice(1)} (GIS ID: ${gisid}) deleted.`,
                            timer: 2500,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        $btn.html('<i class="bi bi-trash3 me-1"></i>Delete').prop('disabled',
                            false);
                        const msg = xhr.responseJSON?.message ||
                            `No ${type} found with GIS ID: ${gisid}`;
                        $('#deleteGisError').text(msg).show();
                    }
                });
            });

            // ─── CLOSE DROPDOWNS ───
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.custom-layer-switcher').length) $('.layer-dropdown').removeClass(
                    'show');
                if (!$(e.target).closest('.custom-location-switcher').length) $('#locationDropdown')
                    .removeClass('show');
                if (!$(e.target).closest('.custom-search-switcher').length) {
                    $('#searchDropdown').removeClass('show');
                    $('#searchToggleBtn').removeClass('active-search');
                }
                if (!$(e.target).closest('.custom-edit-toggle').length) {
                    $('#editDropdown').removeClass('show');
                    $('#editToggleBtn').removeClass('active-edit');
                }
            });

            // ─── INIT ───
            buildSearchIndex();
            updateLayerUI();
            setNoneMode();
            syncLocationUI();

            if (!droneImageURL || droneImageURL === "{{ asset('') }}") {
                droneLayer.setVisible(false);
            }

            // Add to your existing JavaScript

            class InfrastructureManager {
                constructor(map) {
                    this.map = map;
                    this.layers = {};
                    this.infrastructureData = null;
                    this.wardId = null;
                    this.colors = {
                        'Road': '#FF6B6B',
                        'Road Junction': '#FFB74D',
                        'Bus Stop': '#FFA726',
                        'Traffic Signal': '#F44336',
                        'Bridge': '#8D6E63',
                        'Drainage Line': '#4FC3F7',
                        'Storm Water Line': '#4DD0E1',
                        'Sewer Line': '#9575CD',
                        'Water Supply Line': '#4DB6AC',
                        'Waterbody': '#29B6F6',
                        'Canal': '#0288D1',
                        'Culvert': '#00897B',
                        'Fire Hydrant': '#EF5350',
                        'Water Valve': '#81C784',
                        'Street Light': '#FFD54F',
                        'Electric Pole': '#FF8A65',
                        'Street Manhole': '#A1887F',
                        'Transformer': '#AB47BC',
                        'Building': '#78909C',
                        'Boundary Wall': '#795548',
                        'Park': '#66BB6A',
                        'Playground': '#AED581',
                        'Cemetery': '#A1887F',
                        'Tree': '#388E3C'
                    };
                }

                async loadInfrastructure(wardId) {
                    this.wardId = wardId;

                    try {
                        // Fetch summary first
                        const summaryResponse = await fetch(`/infrastructure/summary/${wardId}`);
                        const summaryData = await summaryResponse.json();

                        if (summaryData.success) {
                            console.log('📊 Infrastructure Summary:', summaryData.summary);
                            this.updateDashboard(summaryData.summary);
                        }

                        // Fetch full data
                        const dataResponse = await fetch(`/infrastructure/data/${wardId}`);
                        const data = await dataResponse.json();

                        if (data.success) {
                            this.infrastructureData = data.data;
                            this.displayInfrastructure(data.data);
                            return true;
                        }

                        return false;

                    } catch (error) {
                        console.error('Failed to load infrastructure:', error);
                        return false;
                    }
                }

                displayInfrastructure(geojson) {
                    if (!geojson || !geojson.features) {
                        console.warn('No infrastructure data to display');
                        return;
                    }

                    // Group features by type
                    const grouped = {};
                    geojson.features.forEach(feature => {
                        const type = feature.properties.type || 'Unknown';
                        if (!grouped[type]) grouped[type] = [];
                        grouped[type].push(feature);
                    });

                    // Create layers for each type
                    Object.keys(grouped).forEach(type => {
                        this.createLayer(type, grouped[type]);
                    });

                    // Add legend
                    this.addLegend(grouped);

                    // Fit map to features
                    this.fitToFeatures(geojson.features);
                }

                createLayer(type, features) {
                    const color = this.colors[type] || '#999999';
                    const source = new ol.source.Vector();

                    features.forEach(feature => {
                        const geometry = this.createGeometry(feature.geometry);
                        if (geometry) {
                            const olFeature = new ol.Feature({
                                geometry: geometry,
                                type: type,
                                name: feature.properties.name || '',
                                osm_id: feature.properties.osm_id || '',
                                properties: feature.properties
                            });

                            // Add popup content
                            olFeature.set('popupContent', this.createPopupContent(feature));
                            source.addFeature(olFeature);
                        }
                    });

                    const layer = new ol.layer.Vector({
                        source: source,
                        style: this.createStyle(type, color),
                        title: type,
                        visible: true
                    });

                    this.map.addLayer(layer);
                    this.layers[type] = layer;

                    // Update feature count in legend
                    this.updateLegendCount(type, features.length);
                }

                createGeometry(geometry) {
                    if (!geometry) return null;

                    try {
                        if (geometry.type === 'Point') {
                            return new ol.geom.Point(ol.proj.fromLonLat(geometry.coordinates));
                        } else if (geometry.type === 'LineString') {
                            const coords = geometry.coordinates.map(c => ol.proj.fromLonLat(c));
                            return new ol.geom.LineString(coords);
                        } else if (geometry.type === 'Polygon') {
                            const coords = geometry.coordinates[0].map(c => ol.proj.fromLonLat(c));
                            return new ol.geom.Polygon([coords]);
                        } else if (geometry.type === 'MultiPolygon') {
                            const polygons = geometry.coordinates.map(poly => {
                                return poly[0].map(c => ol.proj.fromLonLat(c));
                            });
                            return new ol.geom.MultiPolygon(polygons);
                        }
                    } catch (e) {
                        console.error('Error creating geometry:', e);
                        return null;
                    }
                }

                createStyle(type, color) {
                    return function(feature) {
                        const geometry = feature.getGeometry();
                        const geometryType = geometry.getType();

                        if (geometryType === 'Point') {
                            return new ol.style.Style({
                                image: new ol.style.Circle({
                                    radius: 8,
                                    fill: new ol.style.Fill({
                                        color: color
                                    }),
                                    stroke: new ol.style.Stroke({
                                        color: '#ffffff',
                                        width: 2
                                    })
                                }),
                                text: new ol.style.Text({
                                    text: feature.get('name') || '',
                                    font: '12px Arial',
                                    offsetY: -15,
                                    fill: new ol.style.Fill({
                                        color: '#000000'
                                    }),
                                    stroke: new ol.style.Stroke({
                                        color: '#ffffff',
                                        width: 2
                                    })
                                })
                            });
                        } else if (geometryType === 'LineString') {
                            return new ol.style.Style({
                                stroke: new ol.style.Stroke({
                                    color: color,
                                    width: 4
                                })
                            });
                        } else if (geometryType === 'Polygon' || geometryType === 'MultiPolygon') {
                            return new ol.style.Style({
                                fill: new ol.style.Fill({
                                    color: color + '33'
                                }),
                                stroke: new ol.style.Stroke({
                                    color: color,
                                    width: 2
                                })
                            });
                        }
                    };
                }

                createPopupContent(feature) {
                    const props = feature.properties;
                    let content = `
            <div style="min-width:200px; max-width:300px;">
                <strong style="font-size:16px;">${props.type || 'Feature'}</strong>
        `;

                    if (props.name) {
                        content += `<br><strong>Name:</strong> ${props.name}`;
                    }

                    if (props.osm_id) {
                        content += `<br><small style="color:#999;">OSM ID: ${props.osm_id}</small>`;
                    }

                    // Add type-specific properties
                    if (props.type === 'Building') {
                        if (props.building_type) content += `<br><strong>Type:</strong> ${props.building_type}`;
                        if (props.levels) content += `<br><strong>Levels:</strong> ${props.levels}`;
                        if (props.height) content += `<br><strong>Height:</strong> ${props.height}m`;
                    } else if (props.type === 'Road') {
                        if (props.road_type) content += `<br><strong>Road Type:</strong> ${props.road_type}`;
                        if (props.speed_limit) content +=
                            `<br><strong>Speed Limit:</strong> ${props.speed_limit}`;
                    } else if (props.type === 'Waterbody') {
                        if (props.water_type) content += `<br><strong>Type:</strong> ${props.water_type}`;
                    }

                    content += `</div>`;
                    return content;
                }

                addLegend(grouped) {
                    // Remove existing legend
                    const existing = document.querySelector('.infrastructure-legend');
                    if (existing) existing.remove();

                    const legend = document.createElement('div');
                    legend.className = 'infrastructure-legend';
                    legend.style.cssText = `
            position: absolute;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            background: rgba(255,255,255,0.95);
            padding: 12px 16px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.15);
            max-height: 400px;
            overflow-y: auto;
            min-width: 180px;
            font-size: 12px;
        `;

                    const title = document.createElement('div');
                    title.style.cssText =
                        'font-weight:700; margin-bottom:10px; font-size:14px; border-bottom:1px solid #e5e7eb; padding-bottom:6px;';
                    title.textContent = '🏗️ Infrastructure';
                    legend.appendChild(title);

                    Object.keys(grouped).sort().forEach(type => {
                        const count = grouped[type].length;
                        const color = this.colors[type] || '#999999';

                        const item = document.createElement('div');
                        item.style.cssText = `
                display: flex;
                align-items: center;
                padding: 4px 0;
                cursor: pointer;
                transition: opacity 0.2s;
            `;

                        const colorBox = document.createElement('span');
                        colorBox.style.cssText = `
                display: inline-block;
                width: 14px;
                height: 14px;
                background: ${color};
                border-radius: 3px;
                margin-right: 10px;
                flex-shrink: 0;
            `;

                        const label = document.createElement('span');
                        label.textContent = type;
                        label.style.cssText = 'flex:1; font-size:12px;';

                        const countBadge = document.createElement('span');
                        countBadge.className = 'legend-count';
                        countBadge.textContent = count;
                        countBadge.style.cssText = `
                background: #f3f4f6;
                padding: 1px 8px;
                border-radius: 12px;
                font-size: 11px;
                color: #6b7280;
            `;

                        item.appendChild(colorBox);
                        item.appendChild(label);
                        item.appendChild(countBadge);

                        // Toggle layer visibility on click
                        item.addEventListener('click', () => {
                            const layer = this.layers[type];
                            if (layer) {
                                const visible = layer.getVisible();
                                layer.setVisible(!visible);
                                item.style.opacity = !visible ? '1' : '0.5';
                            }
                        });

                        legend.appendChild(item);
                    });

                    document.getElementById('map').appendChild(legend);
                }

                updateLegendCount(type, count) {
                    const items = document.querySelectorAll('.infrastructure-legend div');
                    items.forEach(item => {
                        const label = item.querySelector('span:nth-child(2)');
                        const badge = item.querySelector('.legend-count');
                        if (label && badge && label.textContent === type) {
                            badge.textContent = count;
                        }
                    });
                }

                updateDashboard(summary) {
                    // Update stats dashboard
                    const statsContainer = document.querySelector('.stat-strip');
                    if (!statsContainer) return;

                    // Add infrastructure stats
                    const totalFeatures = summary.total_features || 0;
                    const featureTypes = Object.keys(summary.feature_counts || {}).length;

                    // Find or create stat card for infrastructure
                    let infraCard = statsContainer.querySelector('.stat-card[data-type="infrastructure"]');
                    if (!infraCard) {
                        infraCard = document.createElement('div');
                        infraCard.className = 'stat-card';
                        infraCard.setAttribute('data-type', 'infrastructure');
                        statsContainer.appendChild(infraCard);
                    }

                    infraCard.innerHTML = `
            <div class="stat-icon stat-icon-purple"><i class="bi bi-map"></i></div>
            <div>
                <div class="stat-label">Infrastructure Features</div>
                <div class="stat-value">${totalFeatures}</div>
                <div class="stat-sub">${featureTypes} types</div>
            </div>
        `;
                }

                fitToFeatures(features) {
                    if (!features || features.length === 0) return;

                    const extent = [Infinity, Infinity, -Infinity, -Infinity];

                    features.forEach(feature => {
                        const geom = feature.geometry;
                        if (!geom) return;

                        let coords = [];
                        if (geom.type === 'Point') {
                            coords = [geom.coordinates];
                        } else if (geom.type === 'LineString') {
                            coords = geom.coordinates;
                        } else if (geom.type === 'Polygon') {
                            coords = geom.coordinates[0];
                        }

                        coords.forEach(c => {
                            const [lon, lat] = c;
                            if (lon < extent[0]) extent[0] = lon;
                            if (lat < extent[1]) extent[1] = lat;
                            if (lon > extent[2]) extent[2] = lon;
                            if (lat > extent[3]) extent[3] = lat;
                        });
                    });

                    if (extent[0] !== Infinity) {
                        const olExtent = ol.proj.transformExtent(extent, 'EPSG:4326', 'EPSG:3857');
                        this.map.getView().fit(olExtent, {
                            padding: [50, 50, 50, 50],
                            duration: 1000,
                            maxZoom: 18
                        });
                    }
                }
            }

            // Initialize infrastructure manager
            $(document).ready(function() {
                const wardId = {{ $ward->id }};
                const infraManager = new InfrastructureManager(map);
                infraManager.loadInfrastructure(wardId);
            });
            console.log('✅ Executive GIS Dashboard ready');
        });
    </script>
@endpush

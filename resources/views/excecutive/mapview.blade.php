@extends('layouts.office')

@section('title', 'Executive GIS Dashboard')
@section('page_title', 'Executive GIS Dashboard')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@latest/ol.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cesium.com/downloads/cesiumjs/releases/1.127/Build/Cesium/Widgets/widgets.css" rel="stylesheet" />
    <style>
        /* ─── Base Styles ─── */
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
            background: #f8f9fa;
            position: relative;
            z-index: 100;
        }

        #map {
            width: 100%;
            height: 800px;
            transition: all 0.3s ease;
            position: relative;
        }

        #cesiumContainer {
            width: 100%;
            height: 800px;
            position: relative;
            display: none;
        }

        .cesium-widget {
            position: relative !important;
        }

        .cesium-widget canvas {
            position: relative !important;
            z-index: 1 !important;
        }

        .cesium-viewer {
            position: relative !important;
        }

        .cesium-viewer-toolbar {
            z-index: 100 !important;
        }

        .cesium-viewer-bottom {
            z-index: 100 !important;
        }

        .map-controls-stack {
            position: absolute;
            right: 20px;
            top: 20px;
            z-index: 10000 !important;
            display: flex;
            flex-direction: column;
            gap: 8px;
            pointer-events: auto;
        }

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
            border: 1px solid #e5e7eb;
            color: #333;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            position: relative;
            z-index: 10001;
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

        .location-toggle-btn.tracking {
            color: #dc3545;
            background: #fde8e8;
            border-color: #dc3545;
            animation: pulse 1.5s infinite;
        }

        .filter-toggle-btn.active-filter {
            color: #0d6efd;
            background: #e3f0ff;
            border-color: #0d6efd;
        }

        .threed-toggle-btn.active-3d {
            color: #0d6efd;
            background: #e3f0ff;
            border-color: #0d6efd;
            animation: pulse 1.5s infinite;
        }

        .custom-threed-toggle {
            position: relative;
            z-index: 1001;
        }

        .threed-toggle-btn .bi-box-fill {
            color: #0d6efd;
        }

        #zoomToGLBBtn {
            background: linear-gradient(135deg, #FF6B35, #F7931E) !important;
            border-color: #FF6B35 !important;
            color: white !important;
        }

        #zoomToGLBBtn:hover {
            background: linear-gradient(135deg, #E55A2B, #E07E1A) !important;
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
        }

        #zoomToGLBBtn.active-glb {
            background: linear-gradient(135deg, #00B4FF, #0D6EFD) !important;
            border-color: #0D6EFD !important;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }

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
            z-index: 10002 !important;
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

        .layer-dropdown {
            min-width: 220px;
        }

        .location-dropdown {
            min-width: 200px;
        }

        .search-dropdown {
            min-width: 320px;
        }

        .filter-dropdown {
            min-width: 350px;
            max-height: 80vh;
            overflow: hidden;
        }

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

        .filter-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #999;
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

        .filter-range {
            padding: 4px 0;
        }

        .range-inputs {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }

        .range-inputs input[type="number"] {
            width: 80px;
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 12px;
        }

        .range-separator {
            color: #999;
            font-size: 12px;
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

        .filter-stats strong {
            color: #0d6efd;
        }

        .text-muted {
            font-size: 10px;
            color: #6c757d;
            display: block;
            margin-top: 2px;
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

        .location-item-badge.tracking {
            background: #dc3545;
            color: white;
        }

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

        .search-tab-btn:hover {
            background: #f5f5f5;
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

        .type-badge.road {
            background: #0dcaf0;
            color: #000;
        }

        .type-badge.parcel {
            background: #198754;
            color: #fff;
        }

        .type-badge.point {
            background: #ffc107;
            color: #000;
        }

        .type-badge.assessment {
            background: #0d6efd;
            color: #fff;
        }

        .filter-field-group {
            margin-bottom: 10px;
        }

        .filter-field-group label {
            font-size: 11px;
            color: #666;
            font-weight: 600;
            display: block;
            margin-bottom: 3px;
        }

        .filter-field-group input {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
        }

        .filter-field-group input:focus {
            outline: none;
            border-color: #0d6efd;
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.1);
        }

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
            -webkit-backdrop-filter: blur(10px);
        }

        .fullscreen-btn {
            position: absolute;
            right: 20px;
            bottom: 20px;
            z-index: 10001 !important;
            background: white;
            border-radius: 10px;
            padding: 10px;
            cursor: pointer;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
            font-size: 18px;
            transition: all 0.2s;
            border: 1px solid #e5e7eb;
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

        .map-card.fullscreen-mode #map,
        .map-card.fullscreen-mode #cesiumContainer {
            height: calc(100vh - 5px);
        }

        .map-card.fullscreen-mode .map-controls-stack {
            z-index: 100000 !important;
        }

        /* ─── Modal Styles ─── */
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
            color: #000000;
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

        .bv-variation-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            background: #f8fafc;
            border-radius: 10px;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
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
            letter-spacing: .3px;
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

        @media (max-width: 768px) {

            #map,
            #cesiumContainer {
                height: 500px;
            }

            .map-controls-stack {
                right: 12px;
                top: 12px;
                gap: 6px;
            }

            .layer-toggle-btn,
            .location-toggle-btn,
            .search-toggle-btn,
            .label-toggle-btn,
            .legend-toggle-btn,
            .threed-toggle-btn,
            .filter-toggle-btn {
                width: 38px;
                height: 38px;
                font-size: 15px;
                padding: 8px;
                border-radius: 8px;
            }

            .fullscreen-btn {
                width: 38px;
                height: 38px;
                font-size: 15px;
                padding: 8px;
                right: 12px;
                bottom: 12px;
            }

            .layer-dropdown,
            .location-dropdown {
                min-width: 180px;
            }

            .search-dropdown {
                min-width: 280px;
                right: -10px;
            }

            .filter-dropdown {
                min-width: 300px;
                right: -10px;
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

            .quick-stats {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 480px) {

            #map,
            #cesiumContainer {
                height: 400px;
            }

            .map-controls-stack {
                right: 8px;
                top: 8px;
                gap: 5px;
            }

            .layer-toggle-btn,
            .location-toggle-btn,
            .search-toggle-btn,
            .label-toggle-btn,
            .legend-toggle-btn,
            .threed-toggle-btn,
            .filter-toggle-btn {
                width: 34px;
                height: 34px;
                font-size: 13px;
                padding: 6px;
                border-radius: 6px;
            }

            .fullscreen-btn {
                width: 34px;
                height: 34px;
                font-size: 13px;
                padding: 6px;
                right: 8px;
                bottom: 8px;
            }

            .layer-dropdown,
            .location-dropdown {
                min-width: 160px;
                right: -5px;
            }

            .search-dropdown {
                min-width: 240px;
                right: -15px;
            }

            .filter-dropdown {
                min-width: 260px;
                right: -15px;
                max-height: 70vh;
            }

            .bld-summary-card {
                flex: 1 1 100%;
                border-right: none;
                border-bottom: 1px solid #e5e7eb;
            }

            .bld-summary-strip {
                flex-direction: column;
            }

            .bld-image-strip {
                height: 120px;
                flex-direction: column;
            }

            .bld-img-wrap+.bld-img-wrap {
                border-left: none;
                border-top: 3px solid #fff;
            }

            .point-data-card-grid {
                grid-template-columns: 1fr;
            }

            .point-data-card-header {
                flex-direction: column;
                gap: 8px;
            }

            .point-data-card-actions {
                justify-content: flex-start;
            }

            .search-result-actions {
                flex-direction: column;
                gap: 4px;
            }

            .search-result-actions .btn-sm {
                width: 100%;
            }

            .quick-stats {
                grid-template-columns: 1fr 1fr;
                font-size: 10px;
            }
        }

        @media (hover: none) and (pointer: coarse) {

            .layer-toggle-btn,
            .location-toggle-btn,
            .search-toggle-btn,
            .label-toggle-btn,
            .legend-toggle-btn,
            .threed-toggle-btn,
            .filter-toggle-btn {
                min-height: 44px;
                min-width: 44px;
            }

            .fullscreen-btn {
                min-height: 44px;
                min-width: 44px;
            }

            .search-result-actions .btn-sm {
                min-height: 34px;
                font-size: 12px;
                padding: 4px 12px;
            }
        }
    </style>
@endpush
@section('content')
    <div class="ol-page-header">
        <div>
            <h1 class="ol-page-title">{{ ucfirst(auth()->user()->role) }} GIS Dashboard</h1>
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
            <a href="{{ url('data-controll/' . $ward->id) }}" class="btn btn-info btn-sm">
                <i class="bi bi-bounding-box me-1"></i> Data Control
            </a>
        </div>
    </div>

    <div class="map-card" id="mapCard">
        <div class="map-header">
            <span class="badge" id="activeLayerBadge"
                style="background: linear-gradient(135deg,#6C5CE7,#FF3E9A);">OpenStreetMap</span>
            <span class="text-muted small" id="featureCountBadge">Buildings: 0</span>
        </div>
        <div id="map"></div>
        <div id="cesiumContainer"></div>
    </div>

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
                        <img id="bv_img1" src="" style="display:none;"
                            onerror="this.style.display='none'; document.getElementById('bv_img1_error').style.display='flex';">
                        <div id="bv_img1_empty" class="bld-img-empty" style="display:none;"><i
                                class="bi bi-image"></i><span>No Image</span></div>
                        <div id="bv_img1_error" class="bld-img-error" style="display:none;"><i
                                class="bi bi-exclamation-triangle-fill"></i><span>Failed to load</span></div>
                        <div class="bld-img-label">Image 1</div>
                    </div>
                    <div class="bld-img-wrap" id="bv_img2_wrap">
                        <img id="bv_img2" src="" style="display:none;"
                            onerror="this.style.display='none'; document.getElementById('bv_img2_error').style.display='flex';">
                        <div id="bv_img2_empty" class="bld-img-empty" style="display:none;"><i
                                class="bi bi-image"></i><span>No Image</span></div>
                        <div id="bv_img2_error" class="bld-img-error" style="display:none;"><i
                                class="bi bi-exclamation-triangle-fill"></i><span>Failed to load</span></div>
                        <div class="bld-img-label">Image 2</div>
                    </div>
                </div>
                <div class="modal-body" style="max-height: 65vh; overflow-y: auto; padding: 20px 24px;">
                    <div class="bld-summary-strip"
                        style="display: flex; flex-wrap: wrap; gap: 0; border-bottom: 2px solid #ECE7FF; background: #F5F3FF; border-radius: 12px; margin-bottom: 20px;">
                        <div class="bld-summary-card"
                            style="flex: 1 1 120px; padding: 14px 16px; display: flex; align-items: center; gap: 10px; border-right: 1px solid #ECE7FF;">
                            <div class="bld-summary-icon">🧾</div>
                            <div>
                                <div class="bld-summary-label">Assessments</div>
                                <div class="bld-summary-val" id="bv_bills">0</div>
                            </div>
                        </div>
                        <div class="bld-summary-card"
                            style="flex: 1 1 120px; padding: 14px 16px; display: flex; align-items: center; gap: 10px; border-right: 1px solid #ECE7FF;">
                            <div class="bld-summary-icon">🏬</div>
                            <div>
                                <div class="bld-summary-label">Shops</div>
                                <div class="bld-summary-val" id="bv_shops">0</div>
                            </div>
                        </div>
                        <div class="bld-summary-card"
                            style="flex: 1 1 120px; padding: 14px 16px; display: flex; align-items: center; gap: 10px; border-right: 1px solid #ECE7FF;">
                            <div class="bld-summary-icon">🏢</div>
                            <div>
                                <div class="bld-summary-label">Floors</div>
                                <div class="bld-summary-val" id="bv_floors">0</div>
                            </div>
                        </div>
                        <div class="bld-summary-card"
                            style="flex: 1 1 120px; padding: 14px 16px; display: flex; align-items: center; gap: 10px;">
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
                                <div>
                                    <div class="bld-info-label">Zoneation</div>
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
                    <div class="mb-4" id="bv_amenities" style="display: flex; flex-wrap: wrap; gap: 6px;"></div>
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
                    <input type="text" class="form-control bld-input mb-3" id="pointDetailsSearch"
                        placeholder="Search by assessment, owner name, or phone number...">
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
                    <p class="text-muted small mb-3"><span id="qc_owner_display" class="fw-semibold"></span> — Assessment
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
                    <button type="button" class="btn bld-btn-save" id="saveQcBtn"><i class="bi bi-save me-1"></i>Save
                        QC</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        // 4. SEARCH SWITCHER
        $stack.append(
            '<div class="custom-search-switcher">' +
            '<button class="search-toggle-btn" id="searchToggleBtn" aria-label="Toggle search"><i class="bi bi-search"></i></button>' +
            '<div class="search-dropdown" id="searchDropdown">' +
            '<div class="d-flex border-bottom">' +
            '<button type="button" class="btn btn-sm flex-fill search-tab-btn active" data-tab="quick">Quick Search</button>' +
            '<button type="button" class="btn btn-sm flex-fill search-tab-btn" data-tab="filter">Filter</button>' +
            '</div>' +
            '<div class="search-tab-pane" id="quickSearchTab">' +
            '<div class="p-3">' +
            '<input type="text" id="gisSearchInput" class="form-control" placeholder="Search by GIS ID, Assessment, Owner...">' +
            '</div>' +
            '<div id="searchResults" class="search-results-container"></div>' +
            '</div>' +
            '<div class="search-tab-pane" id="filterTab" style="display:none;">' +
            '<div class="p-3">' +
            '<div class="filter-field-group">' +
            '<label>Assessment Number</label>' +
            '<input type="text" id="filterAssessment" class="form-control" placeholder="Enter assessment number...">' +
            '</div>' +
            '<div class="filter-field-group">' +
            '<label>Old Assessment</label>' +
            '<input type="text" id="filterOldAssessment" class="form-control" placeholder="Enter old assessment...">' +
            '</div>' +
            '<div class="filter-field-group">' +
            '<label>Owner Name</label>' +
            '<input type="text" id="filterOwnerName" class="form-control" placeholder="Enter owner name...">' +
            '</div>' +
            '<div class="filter-field-group">' +
            '<label>Phone Number</label>' +
            '<input type="text" id="filterPhoneNumber" class="form-control" placeholder="Enter phone number...">' +
            '</div>' +
            '<button class="btn btn-primary btn-sm w-100 mt-2" id="applyFilterBtn" style="background: linear-gradient(135deg,#0f6b47,#1a8a5a); border: none;">' +
            '<i class="bi bi-search me-1"></i>Search' +
            '</button>' +
            '</div>' +
            '<div id="filterResults" class="search-results-container"></div>' +
            '</div>' +
            '</div>' +
            '</div>'
        );

        // 5. LABEL TOGGLE
        $stack.append(
            '<div class="custom-label-toggle">' +
            '<button class="label-toggle-btn active-label" id="labelToggleBtn" title="Toggle Labels" aria-label="Toggle labels">' +
            '<i class="bi bi-fonts"></i>' +
            '</button>' +
            '</div>'
        );

        // 6. LEGEND TOGGLE
        $stack.append(
            '<div class="custom-legend-toggle">' +
            '<button class="legend-toggle-btn" id="legendToggleBtn" title="Toggle Legend" aria-label="Toggle legend">' +
            '<i class="bi bi-list-ul"></i>' +
            '</button>' +
            '</div>'
        );

        // 7. 3D TOGGLE BUTTON
        $stack.append(
            '<div class="custom-threed-toggle">' +
            '<button class="threed-toggle-btn" id="threedToggleBtn" title="Toggle 3D View" aria-label="Toggle 3D view">' +
            '<i class="bi bi-box"></i>' +
            '</button>' +
            '</div>'
        );

        // 7.5 ZOOM TO GLB MODEL BUTTON
        $stack.append(
            '<div class="custom-glb-toggle">' +
            '<button class="threed-toggle-btn" id="zoomToGLBBtn" title="Zoom to 3D Model" aria-label="Zoom to 3D model" style="background: #ff6b35; color: white; border-color: #ff6b35;">' +
            '<i class="bi bi-box-arrow-in-down"></i>' +
            '</button>' +
            '</div>'
        );

        // 8. FULLSCREEN BUTTON
        $stack.append(
            '<button class="fullscreen-btn" id="fullscreenBtn" aria-label="Toggle fullscreen">' +
            '<i class="bi bi-arrows-fullscreen"></i>' +
            '</button>'
        );

        // ─── HELPER FUNCTIONS ───

        function showToast(message, duration) {
            duration = duration || 3000;
            $('#locationToast').remove();
            if (!$('.toast-container').length) {
                $('body').append('<div class="toast-container"></div>');
            }
            var $toast = $('<div id="locationToast" class="location-toast">' + message + '</div>');
            $('.toast-container').append($toast);
            $toast.css({
                'display': 'block',
                'opacity': 0,
                'transform': 'translateX(-50%) translateY(10px)'
            });
            setTimeout(function() {
                $toast.css({
                    'opacity': 1,
                    'transform': 'translateX(-50%) translateY(0)'
                });
            }, 50);
            clearTimeout($toast.data('timeout'));
            $toast.data('timeout', setTimeout(function() {
                $toast.css({
                    'opacity': 0,
                    'transform': 'translateX(-50%) translateY(10px)'
                });
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            }, duration));
        }

        function switchBaseLayer(layer) {
            [osmLayer, satelliteLayer, streetViewLayer].forEach(function(l) {
                l.setVisible(l === layer);
            });
            var layerName = layer.get('title') || 'Layer';
            $('#activeLayerBadge').text(layerName);
            $('.layer-dropdown-item[data-layer-type="base"]').removeClass('active');
            $('.layer-dropdown-item[data-layer="' + layerName + '"]').addClass('active');
        }

        function toggleDroneLayer() {
            var visible = !droneLayer.getVisible();
            droneLayer.setVisible(visible);
            return visible;
        }

        function zoomToExtent() {
            map.getView().fit(imageExtent, {
                padding: [50, 50, 50, 50],
                duration: 1000,
                maxZoom: 20
            });
            showToast('Zoomed to ward extent', 2000);
        }

        function searchGIS(value) {
            var v = value.toString().toLowerCase().trim();
            if (!v) return [];
            return searchIndex.filter(function(item) {
                return (item.id && item.id.toString().toLowerCase().includes(v)) ||
                    (item.assessment && item.assessment.toString().toLowerCase().includes(v)) ||
                    (item.old_assessment && item.old_assessment.toString().toLowerCase().includes(v)) ||
                    (item.owner_name && item.owner_name.toString().toLowerCase().includes(v)) ||
                    (item.phone_number && item.phone_number.toString().toLowerCase().includes(v)) ||
                    (item.title && item.title.toLowerCase().includes(v)) ||
                    (item.subtitle && item.subtitle.toLowerCase().includes(v)) ||
                    (item.point_gisid && item.point_gisid.toString().toLowerCase().includes(v));
            });
        }

        function zoomToFeature(item) {
            if (!item) {
                showToast('Invalid item', 3000);
                return;
            }
            var coords = null;
            var gisid = item.id || item.point_gisid;
            var features = polygonSource.getFeatures().filter(function(f) {
                return f.get('gisid') == gisid;
            });
            if (features.length > 0) {
                coords = ol.extent.getCenter(features[0].getGeometry().getExtent());
            }
            if (!coords) {
                var lineFeatures = lineSource.getFeatures().filter(function(f) {
                    return f.get('gisid') == gisid;
                });
                if (lineFeatures.length > 0) {
                    coords = ol.extent.getCenter(lineFeatures[0].getGeometry().getExtent());
                }
            }
            if (!coords) {
                var point = points.find(function(p) {
                    return p.gisid == gisid;
                });
                if (point) {
                    try {
                        var c = JSON.parse(point.coordinates);
                        coords = ol.proj.fromLonLat(c);
                    } catch (e) {}
                }
            }
            if (!coords) {
                showToast('No location found for GIS ID: ' + gisid, 3000);
                return;
            }
            map.getView().animate({
                center: coords,
                zoom: 20,
                duration: 1000
            });
        }

        // ─── CESIUM 3D VIEWER ───

        async function initCesiumViewer() {
            if (cesiumViewer) {
                cesiumViewer.forceResize();
                cesiumViewer.scene.render();
                return cesiumViewer;
            }

            if (typeof Cesium === 'undefined') {
                showToast('Cesium library failed to load.', 4000);
                return null;
            }

            try {
                Cesium.Ion.defaultAccessToken = CESIUM_ION_TOKEN;

                cesiumViewer = new Cesium.Viewer("cesiumContainer", {
                    baseLayerPicker: false,
                    geocoder: false,
                    homeButton: false,
                    sceneModePicker: false,
                    navigationHelpButton: false,
                    animation: false,
                    timeline: false,
                    fullscreenButton: false,
                    infoBox: false,
                    selectionIndicator: false,
                    shadows: true
                });

                // Add drone image to Cesium
                if (droneImageURL && droneImageURL !== '') {
                    try {
                        var westSouth = ol.proj.transform([imageExtent[0], imageExtent[1]], 'EPSG:3857', 'EPSG:4326');
                        var eastNorth = ol.proj.transform([imageExtent[2], imageExtent[3]], 'EPSG:3857', 'EPSG:4326');

                        var provider = new Cesium.SingleTileImageryProvider({
                            url: droneImageURL,
                            rectangle: Cesium.Rectangle.fromDegrees(
                                westSouth[0], westSouth[1], eastNorth[0], eastNorth[1]
                            )
                        });

                        var droneLayer = new Cesium.ImageryLayer(provider, {
                            alpha: 0.80,
                            brightness: 1.0,
                            contrast: 1.0,
                            show: true
                        });

                        cesiumViewer.imageryLayers.add(droneLayer);
                        console.log('Drone image added to Cesium 3D view');
                    } catch (e) {
                        console.error('Error adding drone image to Cesium:', e);
                    }
                }

                // Build Cesium buildings from polygon source
                var features = polygonSource.getFeatures();
                features.forEach(function(feature) {
                    try {
                        var poly = feature.get('originalData');
                        if (!poly) return;
                        var ring = JSON.parse(poly.coordinates);
                        if (!Array.isArray(ring) || ring.length < 3) return;

                        var lonLatFlat = [];
                        ring.forEach(function(pt) {
                            var lonLat = ol.proj.transform(pt, 'EPSG:3857', 'EPSG:4326');
                            lonLatFlat.push(lonLat[0], lonLat[1]);
                        });

                        var gisid = poly.gisid;
                        var polygonData = polygonDatas.find(function(d) {
                            return d.gisid == gisid;
                        });
                        var usage = polygonData?.building_usage || 'OTHER';
                        var colorHex = usageColors[usage] || '#0f6b47';
                        var floors = parseInt(polygonData?.number_floor) || 0;
                        var height = Math.max((floors + 1) * 3.2, 3.2);

                        var entity = cesiumViewer.entities.add({
                            gisid: gisid,
                            polygon: {
                                hierarchy: Cesium.Cartesian3.fromDegreesArray(lonLatFlat),
                                extrudedHeight: height,
                                height: 0,
                                material: Cesium.Color.fromCssColorString(colorHex).withAlpha(0.8),
                                outline: true,
                                outlineColor: Cesium.Color.WHITE,
                                heightReference: Cesium.HeightReference.CLAMP_TO_GROUND
                            }
                        });
                        cesiumBuildingEntities.push(entity);
                    } catch (e) {
                        console.error('Cesium polygon build error:', e);
                    }
                });

                // Build Cesium boundary
                var boundaryFeatures = boundarySource.getFeatures();
                if (boundaryFeatures.length > 0) {
                    var boundaryFeature = boundaryFeatures[0];
                    var geometry = boundaryFeature.getGeometry();
                    if (geometry) {
                        var coords = geometry.getCoordinates();
                        var flatCoords = [];
                        if (Array.isArray(coords) && coords.length > 0) {
                            var rings = coords[0] || coords;
                            rings.forEach(function(ring) {
                                if (Array.isArray(ring) && ring.length > 0) {
                                    ring.forEach(function(pt) {
                                        if (Array.isArray(pt) && pt.length >= 2) {
                                            var lonLat = ol.proj.transform(pt, 'EPSG:3857',
                                            'EPSG:4326');
                                            flatCoords.push(lonLat[0], lonLat[1]);
                                        }
                                    });
                                }
                            });
                        }
                        if (flatCoords.length >= 6) {
                            var entity = cesiumViewer.entities.add({
                                name: 'Ward Boundary',
                                polygon: {
                                    hierarchy: Cesium.Cartesian3.fromDegreesArray(flatCoords),
                                    material: Cesium.Color.fromCssColorString('#FF9F1C').withAlpha(0.12),
                                    outline: true,
                                    outlineColor: Cesium.Color.fromCssColorString('#FF9F1C'),
                                    outlineWidth: 3,
                                    height: 0,
                                    heightReference: Cesium.HeightReference.CLAMP_TO_GROUND
                                }
                            });
                            cesiumBuildingEntities.push(entity);
                        }
                    }
                }

                console.log('Cesium 3D viewer initialized successfully!');
                return cesiumViewer;

            } catch (e) {
                console.error('Cesium init error:', e);
                showToast("3D initialization failed: " + e.message, 4000);
                cesiumViewer = null;
                return null;
            }
        }

        async function toggle3DMode() {
            var $threedBtn = $('#threedToggleBtn');

            if (!is3DMode) {
                showToast('Loading 3D view...', 2000);

                try {
                    var viewer = await initCesiumViewer();

                    if (!viewer) {
                        showToast('Failed to initialize 3D view', 3000);
                        return;
                    }

                    $('#map').hide();
                    $('#cesiumContainer').show();

                    setTimeout(function() {
                        if (cesiumViewer) {
                            cesiumViewer.forceResize();
                            cesiumViewer.scene.render();
                        }
                    }, 300);

                    is3DMode = true;
                    $threedBtn.addClass('active-3d').html('<i class="bi bi-box-fill"></i>');

                    setTimeout(function() {
                        showToast('3D mode activated', 3000);
                    }, 1000);

                } catch (e) {
                    console.error('Error toggling 3D mode:', e);
                    showToast('Failed to switch to 3D mode', 4000);
                }

            } else {
                $('#cesiumContainer').hide();
                $('#map').show();
                map.updateSize();

                is3DMode = false;
                $threedBtn.removeClass('active-3d').html('<i class="bi bi-box"></i>');
                showToast('2D mode restored', 2000);
            }
        }

        // ─── GET POINT DATA ───
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
                        showToast('Failed to load assessment data', 3000);
                        callback([]);
                    }
                },
                error: function() {
                    showToast('Failed to load assessment data', 3000);
                    callback([]);
                }
            });
        }

        // ─── SHOW BUILDING VIEW ───
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

            var mappedCount = pointDatas.filter(function(pd) {
                return pd.point_gisid == item.gisid;
            }).length;
            $('#bv_mapped').text(mappedCount);

            var variation = buildingVariations[item.gisid];
            if (variation) {
                var areaBadgeClass = variation.area_status === 'MATCH' ? 'complete' : 'empty';
                var usageBadgeClass = variation.usage_status === 'MATCH' ? 'complete' : 'empty';
                $('#bv_variation_wrap').html(
                    '<div class="bv-variation-strip">' +
                    '<div class="bv-variation-card">' +
                    '<div class="stat-label">Building Area</div>' +
                    '<div class="stat-value">' + variation.building_area + ' <span class="stat-sub">sqft</span></div>' +
                    '</div>' +
                    '<div class="bv-variation-card">' +
                    '<div class="stat-label">Assessment Area</div>' +
                    '<div class="stat-value">' + variation.assessment_area +
                    ' <span class="stat-sub">sqft</span></div>' +
                    '</div>' +
                    '<div class="bv-variation-card">' +
                    '<div class="stat-label">Area Variation</div>' +
                    '<div class="stat-value">' + variation.area_variation + ' <span class="stat-sub">(' + variation
                    .variation_percentage + '%)</span></div>' +
                    '<span class="bld-status-tag ' + areaBadgeClass + '">' + variation.area_status + '</span>' +
                    '</div>' +
                    '<div class="bv-variation-card">' +
                    '<div class="stat-label">Usage Check</div>' +
                    '<span class="bld-status-tag ' + usageBadgeClass + '">' + variation.usage_status + '</span>' +
                    '</div>' +
                    '</div>'
                );
            } else {
                $('#bv_variation_wrap').html('');
            }

            var amenities = [
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
            var amenHtml = '';
            var hasAmenities = false;
            amenities.forEach(function(arr) {
                var label = arr[0];
                var val = arr[1];
                if (val === 'Yes' || val === true || val === 1) {
                    hasAmenities = true;
                    amenHtml += '<span class="bld-status-tag complete"><i class="bi bi-check-circle"></i> ' +
                        label + '</span>';
                }
            });
            $('#bv_amenities').html(hasAmenities ? amenHtml :
            '<span class="text-muted small">No amenities recorded</span>');

            $('#bv_remarks').text(item.remarks || '—');
            $('#bv_corp_remarks').text(item.corporationremarks || '—');

            var assetUrl = window.assetUrl || "{{ asset('') }}";

            function loadImage(imgId, emptyId, errorId, imagePath) {
                var $img = $('#' + imgId);
                var $empty = $('#' + emptyId);
                var $error = $('#' + errorId);

                if (imagePath) {
                    var fullPath = imagePath.startsWith('http') ? imagePath : assetUrl + '/' + imagePath.replace(/^\/+/,
                    '');
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

            var modal = new bootstrap.Modal(document.getElementById('buildingViewModal'));
            modal.show();
        }

        // ─── OPEN POINT DETAILS ───
        function openPointDetails(gisid) {
            currentPointGisid = gisid;
            $('#pointDetailsSearch').val('');
            $('#pdGisid').text(gisid);

            getPointDataWithDetails(gisid, function(data) {
                currentPointRecords = data;
                renderPointDetails(data);
                var building = polygonDatas.find(function(p) {
                    return p.gisid == gisid;
                });
                var billCount = building ? (building.number_bill || 0) : 0;
                $('#pdBillSummary').text(data.length + ' of ' + billCount + ' bills mapped');
            });

            var modal = new bootstrap.Modal(document.getElementById('pointDetailsModal'));
            modal.show();
        }

        // ─── RENDER POINT DETAILS ───
        function renderPointDetails(records) {
            if (!records || !records.length) {
                $('#pointDetailsContainer').html(
                    '<div class="bld-empty-state text-muted"><i class="bi bi-inbox fs-2"></i><p class="mt-2 mb-0">No assessment records found</p></div>'
                );
                return;
            }

            var v = function(val) {
                return (val === null || val === undefined || val === '') ? '<span class="text-muted">-</span>' : val;
            };

            var html = '';
            records.forEach(function(record) {
                var pd = record.point || {};
                var mis = record.mis || {};
                var wt = record.water_tax || {};
                var ugd = record.ugd_tax || {};
                var ptList = record.professional_tax || [];

                var qcFilled = [pd.qcusage, pd.qcsqfeet, pd.qc_remarks].filter(function(val) {
                    return val !== null && val !== '' && val !== undefined;
                }).length;
                var qcClass = qcFilled === 3 ? 'complete' : qcFilled === 0 ? 'empty' : 'partial';
                var qcLabel = qcFilled === 3 ? 'QC Complete' : qcFilled === 0 ? 'QC Pending' : 'QC Partial';

                html +=
                    '<div class="point-data-card" data-id="' + pd.id + '">' +
                    '<div class="point-data-card-header">' +
                    '<div>' +
                    '<div class="point-data-card-title">' + v(pd.owner_name) + '</div>' +
                    '<div class="point-data-card-subtitle">Assessment: ' + v(pd.assessment) + ' • Door: ' + v(pd
                        .new_door_no || pd.old_door_no) + '</div>' +
                    '</div>' +
                    '<div class="point-data-card-actions">' +
                    '<span class="bld-status-tag ' + qcClass + '" style="margin-right:6px;">' + qcLabel +
                    '</span>' +
                    '<button class="pdc-action-btn pdc-qc-btn" title="Quality Check" data-id="' + pd.id +
                    '" data-qc-btn><i class="bi bi-clipboard-check"></i></button>' +
                    '</div>' +
                    '</div>' +
                    '<div class="tax-card-title mt-2"><i class="bi bi-person-badge me-1"></i>Point / Assessment Details</div>' +
                    '<div class="point-data-card-grid" style="display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-bottom:10px;">' +
                    '<div class="pdc-field"><div class="pdc-field-label">Assessment Type</div><div class="pdc-field-val">' +
                    v(pd.assessment_type) + '</div></div>' +
                    '<div class="pdc-field"><div class="pdc-field-label">Old Assessment</div><div class="pdc-field-val">' +
                    v(pd.old_assessment) + '</div></div>' +
                    '<div class="pdc-field"><div class="pdc-field-label">Worker Name</div><div class="pdc-field-val">' +
                    v(pd.worker_name) + '</div></div>' +
                    '<div class="pdc-field"><div class="pdc-field-label">Present Owner</div><div class="pdc-field-val">' +
                    v(pd.present_owner_name) + '</div></div>' +
                    '<div class="pdc-field"><div class="pdc-field-label">EB Number</div><div class="pdc-field-val">' +
                    v(pd.eb) + '</div></div>' +
                    '<div class="pdc-field"><div class="pdc-field-label">Floor</div><div class="pdc-field-val">' +
                    v(pd.floor) + '</div></div>' +
                    '<div class="pdc-field"><div class="pdc-field-label">Bill Usage</div><div class="pdc-field-val">' +
                    v(pd.bill_usage) + '</div></div>' +
                    '<div class="pdc-field"><div class="pdc-field-label">Aadhar No</div><div class="pdc-field-val">' +
                    v(pd.aadhar_no) + '</div></div>' +
                    '<div class="pdc-field"><div class="pdc-field-label">Ration No</div><div class="pdc-field-val">' +
                    v(pd.ration_no) + '</div></div>' +
                    '<div class="pdc-field"><div class="pdc-field-label">Phone</div><div class="pdc-field-val">' +
                    v(pd.phone_number) + '</div></div>' +
                    '<div class="pdc-field"><div class="pdc-field-label">Old Door No</div><div class="pdc-field-val">' +
                    v(pd.old_door_no) + '</div></div>' +
                    '<div class="pdc-field"><div class="pdc-field-label">New Door No</div><div class="pdc-field-val">' +
                    v(pd.new_door_no) + '</div></div>' +
                    '<div class="pdc-field"><div class="pdc-field-label">Plot Area</div><div class="pdc-field-val">' +
                    v(pd.plot_area) + '</div></div>' +
                    '<div class="pdc-field"><div class="pdc-field-label">Water Tax No</div><div class="pdc-field-val">' +
                    v(pd.water_tax) + '</div></div>' +
                    '<div class="pdc-field"><div class="pdc-field-label">Half Year Tax</div><div class="pdc-field-val">' +
                    v(pd.halfyeartax) + '</div></div>' +
                    '<div class="pdc-field"><div class="pdc-field-label">Balance</div><div class="pdc-field-val">' +
                    v(pd.balance) + '</div></div>' +
                    '<div class="pdc-field"><div class="pdc-field-label">No. of Persons</div><div class="pdc-field-val">' +
                    v(pd.no_of_persons) + '</div></div>' +
                    '<div class="pdc-field"><div class="pdc-field-label">Zone</div><div class="pdc-field-val">' + v(
                        pd.zone) + '</div></div>' +
                    '<div class="pdc-field"><div class="pdc-field-label">Remarks</div><div class="pdc-field-val">' +
                    v(pd.remarks) + '</div></div>' +
                    '</div>' +
                    '<div class="tax-card-title mt-2"><i class="bi bi-clipboard-check me-1"></i>QC Details</div>' +
                    '<div class="point-data-card-grid" style="display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-bottom:10px;">' +
                    '<div class="pdc-field"><div class="pdc-field-label">QC Usage</div><div class="pdc-field-val ' +
                    (!pd.qcusage ? 'empty' : '') + '">' + v(pd.qcusage) + '</div></div>' +
                    '<div class="pdc-field"><div class="pdc-field-label">QC Sq.Feet</div><div class="pdc-field-val ' +
                    (!pd.qcsqfeet ? 'empty' : '') + '">' + v(pd.qcsqfeet) + '</div></div>' +
                    '<div class="pdc-field"><div class="pdc-field-label">QC By</div><div class="pdc-field-val">' +
                    v(pd.qc_name) + '</div></div>' +
                    '<div class="pdc-field"><div class="pdc-field-label">QC Remarks</div><div class="pdc-field-val">' +
                    v(pd.qc_remarks) + '</div></div>' +
                    '</div>' +
                    '<div class="row mt-2 g-2">' +
                    '<div class="col-md-4">' +
                    '<div class="tax-card">' +
                    '<div class="tax-card-title"><i class="bi bi-database me-1"></i>MIS Record</div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Road</span><span class="tax-card-value">' +
                    v(mis.road_name) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Ward No</span><span class="tax-card-value">' +
                    v(mis.ward_no) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Plot Area</span><span class="tax-card-value">' +
                    v(mis.plot_area) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Half Yr Tax</span><span class="tax-card-value">' +
                    v(mis.half_year_tax) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Balance</span><span class="tax-card-value">' +
                    v(mis.balance) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Usage</span><span class="tax-card-value">' +
                    v(mis.usage) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Type</span><span class="tax-card-value">' +
                    v(mis.type) + '</span></div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-md-4">' +
                    '<div class="tax-card">' +
                    '<div class="tax-card-title"><i class="bi bi-droplet me-1"></i>Water Tax</div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Number</span><span class="tax-card-value">' +
                    v(wt.watertax_no) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Old Number</span><span class="tax-card-value">' +
                    v(wt.old_watertax_no) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Road</span><span class="tax-card-value">' +
                    v(wt.road_name) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Slab Rate</span><span class="tax-card-value">' +
                    v(wt.slab_rate) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Balance</span><span class="tax-card-value">' +
                    v(wt.balance) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Usage</span><span class="tax-card-value">' +
                    v(wt.usage) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Slab Desc</span><span class="tax-card-value">' +
                    v(wt.slab_description) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">DBC Type</span><span class="tax-card-value">' +
                    v(wt.DBC_type) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Phone</span><span class="tax-card-value">' +
                    v(wt.phone_number) + '</span></div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-md-4">' +
                    '<div class="tax-card">' +
                    '<div class="tax-card-title"><i class="bi bi-pipe me-1"></i>UGD Tax</div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Number</span><span class="tax-card-value">' +
                    v(ugd.ugd_no) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Old Number</span><span class="tax-card-value">' +
                    v(ugd.old_ugd_no) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Owner</span><span class="tax-card-value">' +
                    v(ugd.owner_name) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Slab Rate</span><span class="tax-card-value">' +
                    v(ugd.slab_rate) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Balance</span><span class="tax-card-value">' +
                    v(ugd.balance) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Tax Year</span><span class="tax-card-value">' +
                    v(ugd.tax_year) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Tax Amount</span><span class="tax-card-value">' +
                    v(ugd.ugd_tax_amount) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Tax Due</span><span class="tax-card-value">' +
                    v(ugd.ugd_tax_due) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Tax Paid</span><span class="tax-card-value">' +
                    v(ugd.ugd_tax_paid) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Paid Date</span><span class="tax-card-value">' +
                    v(ugd.ugd_tax_paid_date) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Payment Mode</span><span class="tax-card-value">' +
                    v(ugd.payment_mode) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Receipt No</span><span class="tax-card-value">' +
                    v(ugd.receipt_number) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Due Date</span><span class="tax-card-value">' +
                    v(ugd.due_date) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Status</span><span class="tax-card-value">' +
                    v(ugd.status) + '</span></div>' +
                    '<div class="tax-card-row"><span class="tax-card-label">Remarks</span><span class="tax-card-value">' +
                    v(ugd.remarks) + '</span></div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';

                if (ptList.length) {
                    html +=
                        '<div class="row mt-2 g-2">' +
                        '<div class="col-12">' +
                        '<div class="tax-card">' +
                        '<div class="tax-card-title"><i class="bi bi-briefcase me-1"></i>Professional Tax (' +
                        ptList.length + ')</div>';
                    ptList.forEach(function(pt) {
                        html +=
                            '<div style="border-bottom:1px dashed #d7e8df; padding:6px 0; margin-bottom:4px;">' +
                            '<div class="tax-card-row"><span class="tax-card-label">PT No</span><span class="tax-card-value">' +
                            v(pt.pt_number) + '</span></div>' +
                            '<div class="tax-card-row"><span class="tax-card-label">Old PT No</span><span class="tax-card-value">' +
                            v(pt.old_pt_number) + '</span></div>' +
                            '<div class="tax-card-row"><span class="tax-card-label">Establishment</span><span class="tax-card-value">' +
                            v(pt.establishment_name) + '</span></div>' +
                            '<div class="tax-card-row"><span class="tax-card-label">Profession</span><span class="tax-card-value">' +
                            v(pt.profession_type) + '</span></div>' +
                            '<div class="tax-card-row"><span class="tax-card-label">Employees</span><span class="tax-card-value">' +
                            v(pt.employee_count) + '</span></div>' +
                            '<div class="tax-card-row"><span class="tax-card-label">Half Yr Tax</span><span class="tax-card-value">' +
                            v(pt.half_year_tax) + '</span></div>' +
                            '<div class="tax-card-row"><span class="tax-card-label">Arrears</span><span class="tax-card-value">' +
                            v(pt.arrears) + '</span></div>' +
                            '<div class="tax-card-row"><span class="tax-card-label">Penalty</span><span class="tax-card-value">' +
                            v(pt.penalty) + '</span></div>' +
                            '<div class="tax-card-row"><span class="tax-card-label">Balance</span><span class="tax-card-value">' +
                            v(pt.balance) + '</span></div>' +
                            '<div class="tax-card-row"><span class="tax-card-label">Status</span><span class="tax-card-value">' +
                            v(pt.payment_status) + '</span></div>' +
                            '<div class="tax-card-row"><span class="tax-card-label">Remarks</span><span class="tax-card-value">' +
                            v(pt.remarks) + '</span></div>' +
                            '</div>';
                    });
                    html += '</div></div></div>';
                }

                html += '</div>';
            });

            $('#pointDetailsContainer').html(html);

            $('#pointDetailsSearch').off('input').on('input', function() {
                var searchVal = $(this).val().toLowerCase();
                if (!searchVal) {
                    renderPointDetails(records);
                    return;
                }
                var filtered = records.filter(function(record) {
                    var pd = record.point || {};
                    return (pd.assessment || '').toString().toLowerCase().includes(searchVal) ||
                        (pd.owner_name || '').toLowerCase().includes(searchVal) ||
                        (pd.phone_number || '').toString().toLowerCase().includes(searchVal);
                });
                renderPointDetails(filtered);
            });
        }

        // ─── QC MODAL ───
        function openQcModal(id) {
            var record = currentPointRecords.find(function(r) {
                return r.point && r.point.id == id;
            });
            var pd = record ? record.point : null;
            if (!pd) {
                showToast('Could not find this assessment record.', 3000);
                return;
            }
            $('#qc_point_data_id').val(id);
            $('#qc_owner_display').text(pd.owner_name || '');
            $('#qc_assessment_display').text(pd.assessment || '');
            $('#qcusage').val(pd.qcusage || '');
            $('#qcsqfeet').val(pd.qcsqfeet || '');
            $('#qc_remarks').val(pd.qc_remarks || '');
            var modal = new bootstrap.Modal(document.getElementById('qcModal'));
            modal.show();
        }

        $(document).on('click', '.pdc-qc-btn', function() {
            openQcModal($(this).data('id'));
        });

        $('#saveQcBtn').on('click', function() {
            var id = $('#qc_point_data_id').val();
            var $btn = $(this).prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm"></span> Saving...');

            $.ajax({
                url: '/point-data/' + id + '/qc',
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    qcusage: $('#qcusage').val(),
                    qcsqfeet: $('#qcsqfeet').val(),
                    qc_remarks: $('#qc_remarks').val(),
                    ward_id: {{ $ward->id }}
                },
                success: function(res) {
                    var idx = pointDatas.findIndex(function(p) {
                        return p.id == id;
                    });
                    if (idx > -1) pointDatas[idx] = res.point_data;

                    $('#qcModal').modal('hide');
                    showToast('QC data saved successfully!', 3000);

                    if (currentPointGisid) {
                        getPointDataWithDetails(currentPointGisid, function(data) {
                            currentPointRecords = data;
                            renderPointDetails(data);
                        });
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
        function showFeatureDetails(feature) {
            if (!feature) return;
            var gisid = feature.get('gisid');
            if (!gisid) return;

            var polygonData = polygonDatas.find(function(d) {
                return d.gisid == gisid;
            });
            if (polygonData) {
                showBuildingView(polygonData);
                return;
            }

            var lineData = lines.find(function(l) {
                return l.gisid == gisid;
            });
            if (lineData) {
                showToast('Road: ' + (lineData.road_name || 'N/A') + ' (GIS ID: ' + gisid + ')', 3000);
                return;
            }

            var pointRecords = pointDatas.filter(function(pd) {
                return pd.point_gisid == gisid;
            });
            if (pointRecords.length > 0) {
                openPointDetails(gisid);
                return;
            }

            showToast('Feature GIS ID: ' + gisid, 2000);
        }

        // ─── SELECT INTERACTION ───
        var selectInteraction = new ol.interaction.Select({
            layers: [polygonLayer, lineLayer],
            style: new ol.style.Style({
                stroke: new ol.style.Stroke({
                    color: '#00B4FF',
                    width: 3,
                    lineDash: [4, 4]
                }),
                fill: new ol.style.Fill({
                    color: 'rgba(0,180,255,0.15)'
                })
            })
        });

        selectInteraction.on('select', function(e) {
            if (e.selected.length > 0) {
                showFeatureDetails(e.selected[0]);
                setTimeout(function() {
                    selectInteraction.getFeatures().clear();
                }, 100);
            }
        });

        map.addInteraction(selectInteraction);

        // ─── EVENT HANDLERS ───

        $(document).on('click', '#threedToggleBtn', function(e) {
            e.stopPropagation();
            toggle3DMode();
        });

        $(document).on('click', '#zoomToGLBBtn', function(e) {
            e.stopPropagation();
            if (is3DMode) {
                showToast('Zooming to 3D model', 2000);
            } else {
                showToast('Please activate 3D mode first', 2000);
            }
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
            var layerType = $(this).data('layer-type');
            var layerTitle = $(this).data('layer');

            if (layerType === 'base') {
                var layer;
                if (layerTitle === 'OpenStreetMap') layer = osmLayer;
                else if (layerTitle === 'Satellite') layer = satelliteLayer;
                else if (layerTitle === 'Street View') layer = streetViewLayer;

                if (layer) {
                    switchBaseLayer(layer);
                }
            } else if (layerTitle === 'Drone View') {
                var visible = toggleDroneLayer();
                $(this).toggleClass('active', visible);
            } else if (layerType === 'vector') {
                var layer;
                if (layerTitle === 'Polygons') layer = polygonLayer;
                else if (layerTitle === 'Lines') layer = lineLayer;
                else if (layerTitle === 'Boundary') layer = boundaryLayer;

                if (layer) {
                    var visible = !layer.getVisible();
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
            var usageLegend = '';
            Object.keys(usageColors).forEach(function(usage) {
                var color = usageColors[usage];
                usageLegend += '<div style="display:flex;align-items:center;margin-bottom:8px;">' +
                    '<span style="display:inline-block;width:20px;height:20px;background:' + color +
                    ';border:2px solid #fff;border-radius:6px;margin-right:10px;box-shadow:0 0 4px rgba(0,0,0,0.25);"></span>' +
                    '<strong>' + usage + '</strong>' +
                    '</div>';
            });

            Swal.fire({
                title: 'Building Usage Legend',
                width: 500,
                html: '<div style="text-align:left;font-size:14px;"><h6 style="margin-bottom:10px;color:#0f6b47;">Building Usage Colors</h6>' +
                    usageLegend + '</div>',
                icon: 'info',
                confirmButtonText: 'Close',
                confirmButtonColor: '#0f6b47'
            });
        });

        // ─── LOCATION EVENT HANDLERS ───

        $('#zoomToExtentItem').on('click', function() {
            zoomToExtent();
            $('.location-dropdown').removeClass('active');
        });

        $('#liveLocationItem').on('click', function() {
            if (!navigator.geolocation) {
                showToast('Geolocation not supported');
                return;
            }

            isLiveLocation = !isLiveLocation;
            var $badge = $('#liveLocationBadge');
            var $btn = $('#locationToggleBtn');

            if (isLiveLocation) {
                $badge.text('ON').addClass('active');
                $btn.addClass('active-location');
                showToast('Getting your location...', 2000);

                navigator.geolocation.getCurrentPosition(
                    function(pos) {
                        var lon = pos.coords.longitude;
                        var lat = pos.coords.latitude;
                        var projected = ol.proj.fromLonLat([lon, lat]);
                        currentPosition = projected;
                        currentLocation = {
                            lon: lon,
                            lat: lat
                        };

                        if (!positionFeature) {
                            positionFeature = new ol.Feature({
                                geometry: new ol.geom.Point(projected)
                            });
                            positionFeature.setStyle(createPositionStyle());
                            positionLayer.getSource().addFeature(positionFeature);
                        } else {
                            positionFeature.getGeometry().setCoordinates(projected);
                        }

                        showToast('Live location activated', 2000);

                        if (!watchId) {
                            watchId = navigator.geolocation.watchPosition(
                                function(newPos) {
                                    var p = ol.proj.fromLonLat([newPos.coords.longitude, newPos.coords
                                        .latitude
                                    ]);
                                    currentPosition = p;
                                    currentLocation = {
                                        lon: newPos.coords.longitude,
                                        lat: newPos.coords.latitude
                                    };
                                    if (positionFeature) {
                                        positionFeature.getGeometry().setCoordinates(p);
                                    }
                                },
                                function(error) {
                                    console.error('Watch error:', error);
                                }, {
                                    enableHighAccuracy: true,
                                    timeout: 15000,
                                    maximumAge: 30000
                                }
                            );
                        }
                    },
                    function(error) {
                        isLiveLocation = false;
                        $badge.text('OFF').removeClass('active');
                        $btn.removeClass('active-location');
                        showToast('Could not get location: ' + error.message, 4000);
                    }, {
                        enableHighAccuracy: true,
                        timeout: 10000
                    }
                );
            } else {
                $badge.text('OFF').removeClass('active');
                $btn.removeClass('active-location');
                showToast('Live location deactivated', 2000);
                if (watchId && !isTracking) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                }
                if (positionFeature) {
                    positionLayer.getSource().removeFeature(positionFeature);
                    positionFeature = null;
                }
            }
            $('.location-dropdown').removeClass('active');
        });

        $('#trackMeItem').on('click', function() {
            if (!navigator.geolocation) {
                showToast('Geolocation not supported');
                return;
            }

            if (!isTracking) {
                isTracking = true;
                routePoints = [];
                var $badge = $('#trackMeBadge');
                var $btn = $('#locationToggleBtn');
                $badge.text('ON').addClass('tracking');
                $btn.addClass('tracking');
                showToast('Starting tracking...', 2000);

                navigator.geolocation.getCurrentPosition(
                    function(pos) {
                        var lon = pos.coords.longitude;
                        var lat = pos.coords.latitude;
                        var projected = ol.proj.fromLonLat([lon, lat]);
                        currentPosition = projected;
                        currentLocation = {
                            lon: lon,
                            lat: lat
                        };

                        if (!positionFeature) {
                            positionFeature = new ol.Feature({
                                geometry: new ol.geom.Point(projected)
                            });
                            positionFeature.setStyle(createPositionStyle());
                            positionLayer.getSource().addFeature(positionFeature);
                        } else {
                            positionFeature.getGeometry().setCoordinates(projected);
                        }

                        map.getView().animate({
                            center: projected,
                            zoom: 19,
                            duration: 500
                        });

                        showToast('Tracking started - auto-centering', 3000);

                        if (trackInterval) {
                            clearInterval(trackInterval);
                        }

                        if (!watchId) {
                            watchId = navigator.geolocation.watchPosition(
                                function(newPos) {
                                    var p = ol.proj.fromLonLat([newPos.coords.longitude, newPos.coords
                                        .latitude
                                    ]);
                                    currentPosition = p;
                                    currentLocation = {
                                        lon: newPos.coords.longitude,
                                        lat: newPos.coords.latitude
                                    };
                                    if (positionFeature) {
                                        positionFeature.getGeometry().setCoordinates(p);
                                    }
                                    routePoints.push(p);
                                    updateRouteLine();
                                    map.getView().animate({
                                        center: p,
                                        zoom: 19,
                                        duration: 500
                                    });
                                },
                                function(error) {
                                    console.error('Track error:', error);
                                }, {
                                    enableHighAccuracy: true,
                                    timeout: 15000,
                                    maximumAge: 30000
                                }
                            );
                        }

                        trackInterval = setInterval(function() {
                            if (currentPosition && isTracking) {
                                map.getView().animate({
                                    center: currentPosition,
                                    zoom: 19,
                                    duration: 500
                                });
                            }
                        }, 2000);

                        routePoints.push(projected);

                    },
                    function(error) {
                        isTracking = false;
                        $badge.text('OFF').removeClass('tracking');
                        $btn.removeClass('tracking');
                        showToast('Could not get location: ' + error.message, 4000);
                    }, {
                        enableHighAccuracy: true,
                        timeout: 10000
                    }
                );
            } else {
                isTracking = false;
                var $badge = $('#trackMeBadge');
                var $btn = $('#locationToggleBtn');
                $badge.text('OFF').removeClass('tracking');
                $btn.removeClass('tracking');
                showToast('Tracking stopped', 2000);

                if (trackInterval) {
                    clearInterval(trackInterval);
                    trackInterval = null;
                }

                if (watchId && !isLiveLocation) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                }
            }
            $('.location-dropdown').removeClass('active');
        });

        $('#clearRouteItem').on('click', function() {
            if (routeLine) {
                routeLayer.getSource().removeFeature(routeLine);
                routeLine = null;
            }
            routeLayer.getSource().clear();
            routePoints = [];
            if (destinationMarker) {
                destinationLayer.getSource().removeFeature(destinationMarker);
                destinationMarker = null;
            }
            if (positionFeature) {
                positionLayer.getSource().removeFeature(positionFeature);
                positionFeature = null;
            }
            if (watchId) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
            isTracking = false;
            isLiveLocation = false;
            $('#liveLocationBadge').text('OFF').removeClass('active');
            $('#trackMeBadge').text('OFF').removeClass('tracking');
            $('#locationToggleBtn').removeClass('active-location tracking');
            showToast('Cleared all location data', 2000);
            $('.location-dropdown').removeClass('active');
        });

        function updateRouteLine() {
            if (routePoints.length < 2) return;

            if (!routeLine) {
                routeLine = new ol.Feature({
                    geometry: new ol.geom.LineString(routePoints)
                });
                routeLine.setStyle(new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#FF3E5F',
                        width: 4,
                        lineDash: [8, 6]
                    })
                }));
                routeLayer.getSource().addFeature(routeLine);
            } else {
                routeLine.getGeometry().setCoordinates(routePoints);
            }
        }

        // ─── SEARCH EVENT HANDLERS ───

        $('#gisSearchInput').on('keyup', function() {
            var value = $(this).val();
            if (!value || value.length < 1) {
                $('#searchResults').html('');
                return;
            }
            var results = searchGIS(value);
            var html = '';
            if (!results.length) {
                html = '<div class="p-3 text-center text-muted">No results found</div>';
            } else {
                results.slice(0, 10).forEach(function(item) {
                    var displayTitle = item.type === 'pointdata' ? item.title + ' | Assessment: ' + item
                        .assessment : item.title;
                    var displaySubtitle = item.type === 'pointdata' ?
                        'Point GIS ID: ' + (item.point_gisid || 'N/A') + (item.owner_name ? ' | Owner: ' +
                            item.owner_name : '') :
                        item.subtitle;
                    var icon = item.geometryType === 'point' ? 'geo-alt' : item.geometryType === 'polygon' ?
                        'pentagon' : 'vector-pen';

                    var badgeClass = '';
                    var badgeText = '';
                    if (item.type === 'line') {
                        badgeClass = 'road';
                        badgeText = 'Road';
                    } else if (item.type === 'polygon') {
                        badgeClass = 'parcel';
                        badgeText = 'Building';
                    } else if (item.type === 'point') {
                        badgeClass = 'point';
                        badgeText = 'Point';
                    } else if (item.type === 'pointdata') {
                        badgeClass = 'assessment';
                        badgeText = 'Assessment';
                    }

                    html +=
                        '<div class="search-result-item" data-id="' + item.id + '" data-type="' + item
                        .type + '">' +
                        '<div class="search-result-title">' +
                        '<i class="bi bi-' + icon + ' me-2"></i>' + displayTitle +
                        '<span class="type-badge ' + badgeClass + '">' + badgeText + '</span>' +
                        '</div>' +
                        '<div class="search-result-subtitle">' + displaySubtitle + '</div>' +
                        '<div class="search-result-actions">' +
                        '<button class="btn btn-sm btn-success zoom-btn" data-id="' + item.id +
                        '" data-type="' + item.type + '">Zoom</button>' +
                        '<button class="btn btn-sm btn-primary view-btn" data-id="' + item.id +
                        '" data-type="' + item.type +
                        '" style="background: linear-gradient(135deg,#0f6b47,#1a8a5a); border: none;">View</button>' +
                        '</div>' +
                        '</div>';
                });
            }
            $('#searchResults').html(html);
        });

        $(document).on('click', '.zoom-btn', function(e) {
            e.stopPropagation();
            var id = $(this).data('id');
            var type = $(this).data('type');

            var item = searchIndex.find(function(i) {
                return i.id == id && i.type === type;
            });
            if (!item) {
                item = searchIndex.find(function(i) {
                    return i.point_gisid == id;
                });
            }
            if (!item) {
                item = searchIndex.find(function(i) {
                    return i.id == id;
                });
            }
            if (item) {
                zoomToFeature(item);
            } else {
                showToast('Could not find feature with ID: ' + id, 3000);
            }
            $('.search-dropdown').removeClass('active');
            $('#gisSearchInput').val('');
            $('#searchResults').html('');
        });

        $(document).on('click', '.view-btn', function(e) {
            e.stopPropagation();
            var id = $(this).data('id');
            var type = $(this).data('type');

            var item = searchIndex.find(function(i) {
                return i.id == id && i.type === type;
            });
            if (!item) {
                item = searchIndex.find(function(i) {
                    return i.point_gisid == id;
                });
            }
            if (!item) {
                item = searchIndex.find(function(i) {
                    return i.id == id;
                });
            }

            if (!item) {
                showToast('Could not find feature with ID: ' + id, 3000);
                return;
            }

            var polygonData = polygonDatas.find(function(d) {
                return d.gisid == item.id;
            });
            if (polygonData) {
                showBuildingView(polygonData);
                $('.search-dropdown').removeClass('active');
                $('#gisSearchInput').val('');
                $('#searchResults').html('');
                return;
            }

            var lineData = lines.find(function(l) {
                return l.gisid == item.id;
            });
            if (lineData) {
                showToast('Road: ' + (lineData.road_name || 'N/A') + ' (GIS ID: ' + item.id + ')', 3000);
                $('.search-dropdown').removeClass('active');
                $('#gisSearchInput').val('');
                $('#searchResults').html('');
                return;
            }

            var pointRecords = pointDatas.filter(function(pd) {
                return pd.point_gisid == item.id;
            });
            if (pointRecords.length > 0) {
                openPointDetails(item.id);
                $('.search-dropdown').removeClass('active');
                $('#gisSearchInput').val('');
                $('#searchResults').html('');
                return;
            }

            showToast('Feature: ' + item.title + ' (ID: ' + item.id + ')', 3000);
            $('.search-dropdown').removeClass('active');
            $('#gisSearchInput').val('');
            $('#searchResults').html('');
        });

        $('.search-tab-btn').on('click', function() {
            var tab = $(this).data('tab');
            $('.search-tab-btn').removeClass('active');
            $(this).addClass('active');

            if (tab === 'quick') {
                $('#quickSearchTab').show();
                $('#filterTab').hide();
            } else {
                $('#quickSearchTab').hide();
                $('#filterTab').show();
            }
        });

        // ─── FILTER FUNCTIONS ───

        function updateFilterStats() {
            var total = polygonSource.getFeatures().length;
            $('#visibleCount').text(total);
            $('#totalCount').text(polygons.length);
            $('#filterStats').html('Showing: <strong>' + total + '</strong> of <strong>' + polygons.length +
                '</strong> features');
            updateFeatureCount();
            updateQuickStats();
        }

        function applyFilters() {
            var selectedUsage = $('#usageFilter').val();
            var usageVariation = $('#usageVariationFilter').val();
            var areaVariation = $('#areaVariationFilter').val();
            var selectedZone = $('#zoneFilter').val();
            var selectedConstruction = $('#constructionFilter').val();
            var selectedAssessmentInfo = $('#assessmentinfoFilter').val();
            var selectedBuildingType = $('#buildingTypeFilter').val();
            var selectedAmenities = $('#amenitiesFilter').val() || [];
            var selectedUgd = $('#ugdFilter').val();
            var selectedSurveyStatus = $('#surveyStatusFilter').val();
            var selectedAssessmentCount = $('#assessmentCountFilter').val();
            var selectedFloor = $('#floorFilter').val();
            var selectedShop = $('#shopFilter').val();
            var minArea = parseInt($('#minArea').val()) || 0;
            var maxArea = parseInt($('#maxArea').val()) || 0;

            var allUsageSelected = selectedUsage === 'all';
            var allZonesSelected = selectedZone === 'all';
            var allConstructionSelected = selectedConstruction === 'all';
            var allBuildingTypesSelected = selectedBuildingType === 'all';
            var allUgdSelected = selectedUgd === 'all';
            var allSurveyStatusSelected = selectedSurveyStatus === 'all';
            var allAssessmentCountSelected = selectedAssessmentCount === 'all';
            var allFloorSelected = selectedFloor === 'all';
            var allShopSelected = selectedShop === 'all';
            var allUsageVariationSelected = usageVariation === 'all';
            var allAreaVariationSelected = areaVariation === 'all';
            var noAmenitiesSelected = selectedAmenities.length === 0;
            var areaDefault = minArea === 0 && maxArea === 0;

            var anyFilterActive = !allUsageSelected || !allZonesSelected || !allConstructionSelected ||
                !allBuildingTypesSelected || !allUgdSelected || !allSurveyStatusSelected ||
                !allAssessmentCountSelected || !allFloorSelected || !allShopSelected ||
                !allUsageVariationSelected || !allAreaVariationSelected ||
                !noAmenitiesSelected || !areaDefault;

            if (!anyFilterActive) {
                resetAllFilters(true);
                showToast('All filters reset - showing all features', 2000);
                return;
            }

            var usageGisids = null;
            if (!allUsageSelected) {
                usageGisids = new Set(
                    polygonDatas.filter(function(d) {
                        return d.building_usage === selectedUsage;
                    }).map(function(d) {
                        return d.gisid;
                    })
                );
            }

            var usageVariationGisids = null;
            if (!allUsageVariationSelected) {
                if (usageVariation === 'match') {
                    usageVariationGisids = new Set(
                        Object.values(buildingVariations).filter(function(v) {
                            return v.usage_status === 'MATCH';
                        }).map(function(v) {
                            return v.gisid;
                        })
                    );
                } else if (usageVariation === 'variation') {
                    usageVariationGisids = new Set(
                        Object.values(buildingVariations).filter(function(v) {
                            return v.usage_status === 'VARIATION';
                        }).map(function(v) {
                            return v.gisid;
                        })
                    );
                } else if (usageVariation === 'unmapped') {
                    var variationGisids = new Set(Object.keys(buildingVariations));
                    usageVariationGisids = new Set(
                        polygons.filter(function(p) {
                            return !variationGisids.has(p.gisid);
                        }).map(function(p) {
                            return p.gisid;
                        })
                    );
                }
            }

            var areaVariationGisids = null;
            if (!allAreaVariationSelected) {
                if (areaVariation === 'match') {
                    areaVariationGisids = new Set(
                        Object.values(buildingVariations).filter(function(v) {
                            return v.area_status === 'MATCH';
                        }).map(function(v) {
                            return v.gisid;
                        })
                    );
                } else if (areaVariation === 'variation') {
                    areaVariationGisids = new Set(
                        Object.values(buildingVariations).filter(function(v) {
                            return v.area_status === 'VARIATION';
                        }).map(function(v) {
                            return v.gisid;
                        })
                    );
                } else if (areaVariation === 'high_variation') {
                    areaVariationGisids = new Set(
                        Object.values(buildingVariations).filter(function(v) {
                            return parseFloat(v.variation_percentage) > 20;
                        }).map(function(v) {
                            return v.gisid;
                        })
                    );
                } else if (areaVariation === 'low_variation') {
                    areaVariationGisids = new Set(
                        Object.values(buildingVariations).filter(function(v) {
                            return parseFloat(v.variation_percentage) < 5 && parseFloat(v.variation_percentage) > 0;
                        }).map(function(v) {
                            return v.gisid;
                        })
                    );
                }
            }

            var zoneGisids = null;
            if (!allZonesSelected) {
                zoneGisids = new Set(
                    polygonDatas.filter(function(d) {
                        return (d.zone || d.building_zone) === selectedZone;
                    }).map(function(d) {
                        return d.gisid;
                    })
                );
            }

            var constructionGisids = null;
            if (!allConstructionSelected) {
                constructionGisids = new Set(
                    polygonDatas.filter(function(d) {
                        return d.construction_type === selectedConstruction;
                    }).map(function(d) {
                        return d.gisid;
                    })
                );
            }

            var buildingTypeGisids = null;
            if (!allBuildingTypesSelected) {
                buildingTypeGisids = new Set(
                    polygonDatas.filter(function(d) {
                        return d.building_type === selectedBuildingType;
                    }).map(function(d) {
                        return d.gisid;
                    })
                );
            }

            var amenitiesGisids = null;
            if (!noAmenitiesSelected) {
                amenitiesGisids = new Set(
                    polygonDatas.filter(function(d) {
                        return selectedAmenities.every(function(amenity) {
                            var value = d[amenity];
                            return value === 'Yes' || value === true || value === 1 || (typeof value ===
                                'string' && value.toLowerCase() === 'yes');
                        });
                    }).map(function(d) {
                        return d.gisid;
                    })
                );
            }

            var ugdGisids = null;
            if (!allUgdSelected) {
                ugdGisids = new Set(
                    polygonDatas.filter(function(d) {
                        return d.ugd === selectedUgd;
                    }).map(function(d) {
                        return d.gisid;
                    })
                );
            }

            var surveyStatusGisids = null;
            if (!allSurveyStatusSelected) {
                if (selectedSurveyStatus === 'surveyed') {
                    surveyStatusGisids = new Set(polygonDatas.map(function(d) {
                        return d.gisid;
                    }));
                } else if (selectedSurveyStatus === 'not_surveyed') {
                    var surveyedGisids = new Set(polygonDatas.map(function(d) {
                        return d.gisid;
                    }));
                    surveyStatusGisids = new Set(
                        polygons.filter(function(p) {
                            return !surveyedGisids.has(p.gisid);
                        }).map(function(p) {
                            return p.gisid;
                        })
                    );
                } else if (selectedSurveyStatus === 'partially_surveyed') {
                    surveyStatusGisids = new Set(
                        polygonDatas.filter(function(d) {
                            var pointCount = pointDatas.filter(function(pd) {
                                return pd.point_gisid === d.gisid;
                            }).length;
                            return pointCount > 0 && pointCount < (d.number_bill || 0);
                        }).map(function(d) {
                            return d.gisid;
                        })
                    );
                }
            }

            var assessmentCountGisids = null;
            if (!allAssessmentCountSelected) {
                var gisidPointCount = {};
                pointDatas.forEach(function(pd) {
                    gisidPointCount[pd.point_gisid] = (gisidPointCount[pd.point_gisid] || 0) + 1;
                });

                assessmentCountGisids = new Set(
                    polygons.filter(function(p) {
                        var count = gisidPointCount[p.gisid] || 0;
                        if (selectedAssessmentCount === 'zero') return count === 0;
                        if (selectedAssessmentCount === 'one') return count === 1;
                        if (selectedAssessmentCount === 'two') return count === 2;
                        if (selectedAssessmentCount === 'three_plus') return count >= 3;
                        return false;
                    }).map(function(p) {
                        return p.gisid;
                    })
                );
            }

            var floorGisids = null;
            if (!allFloorSelected) {
                floorGisids = new Set(
                    polygonDatas.filter(function(d) {
                        var floors = parseInt(d.number_floor) || 0;
                        if (selectedFloor === '0') return floors === 0;
                        if (selectedFloor === '1') return floors === 1;
                        if (selectedFloor === '2') return floors === 2;
                        if (selectedFloor === '3') return floors === 3;
                        if (selectedFloor === '4') return floors >= 4;
                        return false;
                    }).map(function(d) {
                        return d.gisid;
                    })
                );
            }

            var shopGisids = null;
            if (!allShopSelected) {
                shopGisids = new Set(
                    polygonDatas.filter(function(d) {
                        var shops = parseInt(d.number_shop) || 0;
                        if (selectedShop === '0') return shops === 0;
                        if (selectedShop === '1') return shops === 1;
                        if (selectedShop === '2') return shops === 2;
                        if (selectedShop === '3') return shops >= 3;
                        return false;
                    }).map(function(d) {
                        return d.gisid;
                    })
                );
            }

            var areaGisids = null;
            if (!areaDefault) {
                areaGisids = new Set(
                    polygons.filter(function(p) {
                        var area = parseFloat(p.sqfeet) || 0;
                        return area >= minArea && area <= maxArea;
                    }).map(function(p) {
                        return p.gisid;
                    })
                );
            }

            var allFilterSets = [
                usageGisids, usageVariationGisids, areaVariationGisids,
                zoneGisids, constructionGisids, buildingTypeGisids,
                amenitiesGisids, ugdGisids, surveyStatusGisids,
                assessmentCountGisids, floorGisids, shopGisids, areaGisids
            ].filter(function(set) {
                return set !== null;
            });

            var finalGisids = new Set(polygons.map(function(p) {
                return p.gisid;
            }));

            allFilterSets.forEach(function(filterSet) {
                if (filterSet.size > 0) {
                    finalGisids = new Set([...finalGisids].filter(function(gisid) {
                        return filterSet.has(gisid);
                    }));
                } else {
                    finalGisids = new Set();
                }
            });

            polygonSource.clear();

            polygons.forEach(function(poly) {
                if (finalGisids.has(poly.gisid)) {
                    try {
                        var coords = JSON.parse(poly.coordinates);
                        var buildingData = polygonDatas.find(function(d) {
                            return d.gisid === poly.gisid;
                        });
                        var feature = new ol.Feature({
                            geometry: new ol.geom.Polygon([coords]),
                            gisid: poly.gisid,
                            type: 'polygon',
                            sqfeet: poly.sqfeet || '0',
                            assessment: poly.assessment || '',
                            old_assessment: poly.old_assessment || '',
                            owner_name: poly.owner_name || '',
                            phone_number: poly.phone_number || '',
                            floors: buildingData?.number_floor || 0,
                            originalData: poly
                        });
                        feature.setId(poly.gisid);
                        feature.setStyle(createPolygonStyle(feature));
                        polygonSource.addFeature(feature);
                    } catch (e) {
                        console.error('polygon parse error:', e);
                    }
                }
            });

            var visibleCount = polygonSource.getFeatures().length;
            var total = polygons.length;

            $('#visibleCount').text(visibleCount);
            $('#totalCount').text(total);
            $('#filterStats').html('Showing: <strong>' + visibleCount + '</strong> of <strong>' + total +
                '</strong> features');
            $('#featureCountBadge').text('Buildings: ' + visibleCount);

            $('#statTotal').text(total);
            $('#statSurveyed').text(polygonDatas.length);
            $('#statUnsurveyed').text(total - polygonDatas.length);
            $('#statVariation').text(Object.values(buildingVariations).filter(function(v) {
                return v.usage_status === 'VARIATION';
            }).length);

            polygonLayer.changed();
            polygonSource.changed();

            var hiddenCount = total - visibleCount;
            if (hiddenCount > 0) {
                showToast('Filter applied: ' + visibleCount + ' visible, ' + hiddenCount + ' hidden', 3000);
            } else if (visibleCount === 0) {
                showToast('No features match the selected filters', 3000);
            } else {
                showToast(visibleCount + ' features match the selected filters', 2000);
            }

            if (is3DMode) {
                // Rebuild Cesium buildings after filter
                if (cesiumViewer) {
                    cesiumBuildingEntities.forEach(function(e) {
                        try {
                            cesiumViewer.entities.remove(e);
                        } catch (e) {}
                    });
                    cesiumBuildingEntities = [];

                    var features = polygonSource.getFeatures();
                    features.forEach(function(feature) {
                        try {
                            var poly = feature.get('originalData');
                            if (!poly) return;
                            var ring = JSON.parse(poly.coordinates);
                            if (!Array.isArray(ring) || ring.length < 3) return;

                            var lonLatFlat = [];
                            ring.forEach(function(pt) {
                                var lonLat = ol.proj.transform(pt, 'EPSG:3857', 'EPSG:4326');
                                lonLatFlat.push(lonLat[0], lonLat[1]);
                            });

                            var gisid = poly.gisid;
                            var polygonData = polygonDatas.find(function(d) {
                                return d.gisid == gisid;
                            });
                            var usage = polygonData?.building_usage || 'OTHER';
                            var colorHex = usageColors[usage] || '#0f6b47';
                            var floors = parseInt(polygonData?.number_floor) || 0;
                            var height = Math.max((floors + 1) * 3.2, 3.2);

                            var entity = cesiumViewer.entities.add({
                                gisid: gisid,
                                polygon: {
                                    hierarchy: Cesium.Cartesian3.fromDegreesArray(lonLatFlat),
                                    extrudedHeight: height,
                                    height: 0,
                                    material: Cesium.Color.fromCssColorString(colorHex).withAlpha(0.8),
                                    outline: true,
                                    outlineColor: Cesium.Color.WHITE,
                                    heightReference: Cesium.HeightReference.CLAMP_TO_GROUND
                                }
                            });
                            cesiumBuildingEntities.push(entity);
                        } catch (e) {
                            console.error('Cesium rebuild error:', e);
                        }
                    });
                }
            }
        }

        function resetAllFilters(silent) {
            silent = silent || false;
            $('#usageFilter, #zoneFilter, #constructionFilter, #buildingTypeFilter, #ugdFilter, #surveyStatusFilter, #assessmentinfoFilter')
                .val('all');
            $('#usageVariationFilter, #areaVariationFilter, #assessmentCountFilter, #floorFilter, #shopFilter').val('all');
            $('#amenitiesFilter').val([]);
            $('#minArea').val(0);
            $('#maxArea').val(0);

            polygonSource.clear();
            polygons.forEach(function(poly) {
                try {
                    var coords = JSON.parse(poly.coordinates);
                    var buildingData = polygonDatas.find(function(d) {
                        return d.gisid === poly.gisid;
                    });
                    var feature = new ol.Feature({
                        geometry: new ol.geom.Polygon([coords]),
                        gisid: poly.gisid,
                        type: 'polygon',
                        sqfeet: poly.sqfeet || '0',
                        assessment: poly.assessment || '',
                        old_assessment: poly.old_assessment || '',
                        owner_name: poly.owner_name || '',
                        phone_number: poly.phone_number || '',
                        floors: buildingData?.number_floor || 0,
                        originalData: poly
                    });
                    feature.setId(poly.gisid);
                    feature.setStyle(createPolygonStyle(feature));
                    polygonSource.addFeature(feature);
                } catch (e) {
                    console.error('polygon parse error:', e);
                }
            });

            var allFeatures = polygonSource.getFeatures();
            $('#visibleCount').text(allFeatures.length);
            $('#totalCount').text(allFeatures.length);
            $('#filterStats').html('Showing: <strong>' + allFeatures.length + '</strong> of <strong>' + allFeatures.length +
                '</strong> features');
            $('#featureCountBadge').text('Buildings: ' + allFeatures.length);

            $('#statTotal').text(polygons.length);
            $('#statSurveyed').text(polygonDatas.length);
            $('#statUnsurveyed').text(polygons.length - polygonDatas.length);
            $('#statVariation').text(Object.values(buildingVariations).filter(function(v) {
                return v.usage_status === 'VARIATION';
            }).length);

            polygonLayer.changed();
            polygonSource.changed();

            if (!silent) {
                showToast('All filters reset - all features visible', 2000);
            }

            if (is3DMode) {
                if (cesiumViewer) {
                    cesiumBuildingEntities.forEach(function(e) {
                        try {
                            cesiumViewer.entities.remove(e);
                        } catch (e) {}
                    });
                    cesiumBuildingEntities = [];

                    var features = polygonSource.getFeatures();
                    features.forEach(function(feature) {
                        try {
                            var poly = feature.get('originalData');
                            if (!poly) return;
                            var ring = JSON.parse(poly.coordinates);
                            if (!Array.isArray(ring) || ring.length < 3) return;

                            var lonLatFlat = [];
                            ring.forEach(function(pt) {
                                var lonLat = ol.proj.transform(pt, 'EPSG:3857', 'EPSG:4326');
                                lonLatFlat.push(lonLat[0], lonLat[1]);
                            });

                            var gisid = poly.gisid;
                            var polygonData = polygonDatas.find(function(d) {
                                return d.gisid == gisid;
                            });
                            var usage = polygonData?.building_usage || 'OTHER';
                            var colorHex = usageColors[usage] || '#0f6b47';
                            var floors = parseInt(polygonData?.number_floor) || 0;
                            var height = Math.max((floors + 1) * 3.2, 3.2);

                            var entity = cesiumViewer.entities.add({
                                gisid: gisid,
                                polygon: {
                                    hierarchy: Cesium.Cartesian3.fromDegreesArray(lonLatFlat),
                                    extrudedHeight: height,
                                    height: 0,
                                    material: Cesium.Color.fromCssColorString(colorHex).withAlpha(0.8),
                                    outline: true,
                                    outlineColor: Cesium.Color.WHITE,
                                    heightReference: Cesium.HeightReference.CLAMP_TO_GROUND
                                }
                            });
                            cesiumBuildingEntities.push(entity);
                        } catch (e) {
                            console.error('Cesium rebuild error:', e);
                        }
                    });
                }
            }
        }

        $('#applyFiltersBtn').on('click', function() {
            applyFilters();
        });

        $('#resetFiltersBtn').on('click', function() {
            resetAllFilters(false);
        });

        $('#minArea').on('change', function() {
            var val = parseInt($(this).val()) || 0;
            var maxVal = parseInt($('#maxArea').val()) || 0;
            if (val > maxVal) {
                val = maxVal;
                $(this).val(val);
            }
        });

        $('#maxArea').on('change', function() {
            var val = parseInt($(this).val()) || 0;
            var minVal = parseInt($('#minArea').val()) || 0;
            if (val < minVal) {
                val = minVal;
                $(this).val(val);
            }
        });

        // ─── FILTER SEARCH ───

        $('#applyFilterBtn').on('click', function() {
            var assessment = $('#filterAssessment').val().toLowerCase().trim();
            var oldAssessment = $('#filterOldAssessment').val().toLowerCase().trim();
            var ownerName = $('#filterOwnerName').val().toLowerCase().trim();
            var phoneNumber = $('#filterPhoneNumber').val().toLowerCase().trim();

            if (!assessment && !oldAssessment && !ownerName && !phoneNumber) {
                showToast('Please enter at least one filter criteria', 3000);
                return;
            }

            var matches = searchIndex.filter(function(item) {
                var match = true;

                if (assessment) {
                    var itemAssessment = (item.assessment || '').toString().toLowerCase();
                    match = match && itemAssessment.includes(assessment);
                }
                if (oldAssessment) {
                    var itemOldAssessment = (item.old_assessment || '').toString().toLowerCase();
                    match = match && itemOldAssessment.includes(oldAssessment);
                }
                if (ownerName) {
                    var itemOwner = (item.owner_name || '').toString().toLowerCase();
                    match = match && itemOwner.includes(ownerName);
                }
                if (phoneNumber) {
                    var itemPhone = (item.phone_number || '').toString().toLowerCase();
                    match = match && itemPhone.includes(phoneNumber);
                }

                return match;
            });

            var results = $('#filterResults');

            if (matches.length === 0) {
                results.html('<div class="p-3 text-center text-muted">No matching records found</div>');
                showToast('No results found', 2000);
                return;
            }

            var html = '<div class="dropdown-header">Results (' + matches.length + ' found)</div>';
            matches.slice(0, 15).forEach(function(item) {
                var icon = item.geometryType === 'polygon' ? 'pentagon' : item.geometryType === 'line' ?
                    'vector-pen' : 'geo-alt';
                var details = [];
                if (item.assessment) details.push('Assess: ' + item.assessment);
                if (item.owner_name) details.push('Owner: ' + item.owner_name);
                if (item.phone_number) details.push('Phone: ' + item.phone_number);

                var badgeClass = '';
                var badgeText = '';
                if (item.type === 'line') {
                    badgeClass = 'road';
                    badgeText = 'Road';
                } else if (item.type === 'polygon') {
                    badgeClass = 'parcel';
                    badgeText = 'Building';
                } else if (item.type === 'point') {
                    badgeClass = 'point';
                    badgeText = 'Point';
                } else if (item.type === 'pointdata') {
                    badgeClass = 'assessment';
                    badgeText = 'Assessment';
                }

                html +=
                    '<div class="search-result-item" data-id="' + item.id + '" data-type="' + item.type +
                    '">' +
                    '<div class="search-result-title">' +
                    '<i class="bi bi-' + icon + ' me-2"></i>' + item.title +
                    '<span class="type-badge ' + badgeClass + '">' + badgeText + '</span>' +
                    '</div>' +
                    '<div class="search-result-subtitle">' + item.subtitle + '</div>' +
                    (details.length ? '<div class="search-result-subtitle" style="color:#5f7d70;">' +
                        details.join(' | ') + '</div>' : '') +
                    '<div class="search-result-actions">' +
                    '<button class="btn btn-sm btn-success zoom-btn" data-id="' + item.id +
                    '" data-type="' + item.type + '">Zoom</button>' +
                    '<button class="btn btn-sm btn-primary view-btn" data-id="' + item.id +
                    '" data-type="' + item.type +
                    '" style="background: linear-gradient(135deg,#0f6b47,#1a8a5a); border: none;">View</button>' +
                    '</div>' +
                    '</div>';
            });
            results.html(html);
            showToast('Found ' + matches.length + ' results', 2000);
        });

        $('#filterAssessment, #filterOldAssessment, #filterOwnerName, #filterPhoneNumber').on('keypress', function(e) {
            if (e.which === 13) {
                $('#applyFilterBtn').click();
            }
        });

        $('#gisSearchInput').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $(this).trigger('keyup');
                var firstResult = $('.search-result-item').first();
                if (firstResult.length) {
                    firstResult.click();
                }
            }
        });

        // ─── FULLSCREEN ───
        var isFullscreen = false;

        $('#fullscreenBtn').on('click', function() {
            var $card = $('#mapCard');
            var $btn = $(this);

            if (!isFullscreen) {
                $card.addClass('fullscreen-mode');
                $('#map').addClass('fullscreen');
                $btn.html('<i class="bi bi-fullscreen-exit"></i>');
                isFullscreen = true;
            } else {
                $card.removeClass('fullscreen-mode');
                $('#map').removeClass('fullscreen');
                $btn.html('<i class="bi bi-arrows-fullscreen"></i>');
                isFullscreen = false;
            }

            setTimeout(function() {
                map.updateSize();
                if (cesiumViewer) cesiumViewer.resize();
            }, 150);
        });

        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && isFullscreen) {
                $('#fullscreenBtn').click();
            }
        });

        // ─── INIT ───
        setTimeout(updateFilterStats, 500);

        console.log('GIS Dashboard initialized successfully!');
        console.log('Search Index Size:', searchIndex.length);
        console.log('Polygons:', polygons.length);
        console.log('Lines:', lines.length);
        console.log('Point Data:', pointDatas.length);
        console.log('3D mode ready - Click the cube button to activate');

        setTimeout(function() {
        showToast('Click on any building to view details', 4000);
        }, 1000);

        });
    </script>
@endpush

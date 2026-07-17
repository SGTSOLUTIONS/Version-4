    @extends('layouts.office')

    @section('title', 'Dashboard — Revenue Department')
    @section('page_title', 'Dashboard')

    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@latest/ol.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

        <style>
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

            .layer-toggle-btn {
                font-size: 1.4rem;
            }

            .location-toggle-btn {
                font-size: 1.4rem;
            }

            .search-toggle-btn {
                font-size: 1.2rem;
            }

            .edit-toggle-btn {
                font-size: 1.3rem;
            }

            .fullscreen-btn {
                font-size: 1.3rem;
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

            .edit-dropdown::-webkit-scrollbar,
            .layer-dropdown::-webkit-scrollbar,
            .location-dropdown::-webkit-scrollbar,
            .search-dropdown::-webkit-scrollbar {
                width: 6px;
            }

            .edit-dropdown::-webkit-scrollbar-thumb,
            .layer-dropdown::-webkit-scrollbar-thumb,
            .location-dropdown::-webkit-scrollbar-thumb,
            .search-dropdown::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 10px;
            }

            .edit-dropdown::-webkit-scrollbar-thumb:hover,
            .layer-dropdown::-webkit-scrollbar-thumb:hover,
            .location-dropdown::-webkit-scrollbar-thumb:hover,
            .search-dropdown::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
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

            .edit-dropdown-item[data-tool="delete"]:hover {
                background: #fff3f3;
                border-left-color: #dc2626;
            }

            .edit-dropdown-item[data-tool="delete"] .edit-icon,
            .edit-dropdown-item[data-tool="delete"] .edit-name {
                color: #dc2626;
            }

            .layer-icon,
            .location-item-icon,
            .edit-icon {
                width: 20px;
                text-align: center;
                font-size: 1rem;
                color: #64748b;
            }

            .layer-name,
            .location-item-name,
            .edit-name {
                flex: 1;
                font-size: 0.85rem;
                color: #334155;
            }

            .layer-check,
            .edit-check {
                color: #3b82f6;
                font-size: 0.9rem;
                opacity: 0;
                transition: opacity 0.15s;
            }

            .layer-dropdown-item.active .layer-check,
            .edit-dropdown-item.active .edit-check {
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

            .search-results-container::-webkit-scrollbar {
                width: 6px;
            }

            .search-results-container::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 10px;
            }

            .search-results-container::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
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

            .fullscreen-btn {
                position: absolute;
                bottom: 20px;
                right: 20px;
                z-index: 1000;
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

            @media (max-width: 768px) {
                #map {
                    height: 600px;
                }

                .ol-page-title {
                    font-size: 1.4rem;
                }

                .map-header {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 10px;
                }

                .layer-toggle-btn,
                .fullscreen-btn,
                .location-toggle-btn,
                .search-toggle-btn,
                .edit-toggle-btn {
                    width: 40px;
                    height: 40px;
                    font-size: 1.2rem;
                }

                .layer-dropdown,
                .location-dropdown {
                    min-width: 180px;
                }

                .search-dropdown {
                    width: 280px;
                }

                .edit-dropdown {
                    min-width: 200px;
                }
            }

            /* ── Modal shell ── */
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

            /* ── Image strip ── */
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

            .bld-img-wrap.bld-img-error img {
                opacity: .35;
                filter: grayscale(1);
            }

            /* ── Summary strip ── */
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

            /* ── Tabs ── */
            .bld-tabs {
                border-bottom: 2px solid #e5e7eb;
                gap: 4px;
            }

            .bld-tab-btn {
                background: none;
                border: none;
                border-bottom: 3px solid transparent;
                border-radius: 0;
                padding: 10px 18px;
                font-size: .875rem;
                font-weight: 600;
                color: #64748b;
                cursor: pointer;
                transition: all .2s;
                margin-bottom: -2px;
                display: flex;
                align-items: center;
                gap: 4px;
            }

            .bld-tab-btn:hover {
                color: #2563eb;
                background: #eff6ff;
                border-radius: 8px 8px 0 0;
            }

            .bld-tab-btn.active {
                color: #2563eb;
                border-bottom-color: #2563eb;
                background: none;
            }

            .bld-tab-badge {
                background: #2563eb;
                color: #fff;
                font-size: .65rem;
                font-weight: 700;
                padding: 1px 6px;
                border-radius: 20px;
                min-width: 18px;
                text-align: center;
            }

            /* ── Info rows ── */
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

            /* ── Section dividers ── */
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

            /* ── Point data cards ── */
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

            .pdc-delete-btn {
                background: #fff1f2;
                color: #dc2626;
            }

            .pdc-delete-btn:hover {
                background: #dc2626;
                color: #fff;
            }

            .point-data-card-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 8px;
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

            /* ── Form elements ── */
            .bld-form-label {
                font-size: .8rem;
                font-weight: 600;
                color: #374151;
                margin-bottom: 4px;
            }

            .bld-input {
                border-radius: 10px !important;
                border: 1.5px solid #e5e7eb !important;
                font-size: .875rem !important;
                padding: 9px 12px !important;
                transition: border-color .2s, box-shadow .2s !important;
            }

            .bld-input:focus {
                border-color: #3b82f6 !important;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, .12) !important;
            }

            /* ── Buttons ── */
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

            /* ── Modal footer ── */
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

            /* ── Empty state ── */
            .bld-empty-state {
                text-align: center;
                padding: 40px 20px;
            }

            /* ── Progress bar ── */
            .bld-progress-wrap {
                background: #e2e8f0;
                border-radius: 20px;
                height: 6px;
                overflow: hidden;
                margin-top: 4px;
            }

            .bld-progress-bar {
                height: 100%;
                border-radius: 20px;
                background: linear-gradient(90deg, #22c55e, #16a34a);
                transition: width .5s ease;
            }

            /* ── Status tags ── */
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

            @media (max-width: 768px) {
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
            }
        </style>
    @endpush

    @section('content')
        <div class="ol-page-header">
            <div>
                <h1 class="ol-page-title">
                    Good {{ now()->hour < 12 ? 'Morning' : (now()->hour < 17 ? 'Afternoon' : 'Evening') }},
                    {{ explode(' ', auth()->user()->name ?? 'Officer')[0] }} 👋
                </h1>
                <p class="ol-page-sub">
                    Here's what's happening in the revenue department today —
                    {{ now()->format('l, d F Y') }}
                </p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <span class="ds-pill paid">
                    <i class="bi bi-circle-fill" style="font-size:8px;"></i>
                    Live
                </span>
            </div>
        </div>

        <div class="map-card" id="mapCard">
            <div class="map-header">
                <h5 class="map-title">
                    <i class="bi bi-geo-alt-fill text-primary me-2"></i>
                    Revenue GIS Dashboard
                </h5>
                <span class="badge bg-primary" id="activeLayerBadge">OpenStreetMap</span>
            </div>
            <div id="map"></div>
        </div>

        {{-- Delete Feature Modal --}}
        <div class="modal fade" id="deleteFeatureModal" tabindex="-1" aria-labelledby="deleteModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content"
                    style="border-radius:16px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
                    <div class="modal-header"
                        style="background:#fff3f3; border-bottom:1px solid #fecdd3; border-radius:16px 16px 0 0; padding:16px 24px;">
                        <h5 class="modal-title" id="deleteModalLabel" style="color:#dc2626; font-weight:700; margin:0;">
                            <i class="bi bi-trash3-fill me-2"></i>Delete Feature
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-muted mb-4" style="font-size:0.875rem; line-height:1.5;">
                            Choose the feature type and enter its GIS ID to permanently remove it from the map.
                        </p>
                        <div class="mb-4">
                            <label class="form-label fw-semibold mb-2" style="font-size:0.85rem; color:#374151;">Feature
                                Type</label>
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
                            <label for="deleteGisId" class="form-label fw-semibold mb-2"
                                style="font-size:0.85rem; color:#374151;">GIS ID</label>
                            <input type="text" id="deleteGisId" class="form-control" placeholder="Enter GIS ID…"
                                style="border-radius:10px; border:1.5px solid #e5e7eb; padding:10px 14px; font-size:0.9rem;"
                                autocomplete="off">
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
                            style="border-radius:10px; font-weight:600; padding:8px 20px;">
                            Cancel
                        </button>
                        <button type="button" id="confirmDeleteBtn" class="btn btn-danger"
                            style="border-radius:10px; font-weight:600; padding:8px 24px; min-width:120px;">
                            <i class="bi bi-trash3 me-1"></i>Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="buildingDataModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header"
                        style="background: linear-gradient(135deg, #1e293b, #0f172a); color: white; border-bottom: none;">
                        <h5 class="modal-title"><i class="fas fa-building me-2"></i>Building </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="buildingForm" enctype="multipart/form-data">
                        @csrf

                        <div class="modal-body" style="max-height: 70vh; overflow-y: auto; background: #f8fafc;">
                            <div class="card mb-4">
                                <div class="card-header"
                                    style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white;">
                                    <h6 class="mb-0"><i class="fas fa-image me-2"></i>Building Images</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="fw-bold mb-2"><i class="fas fa-camera me-1"></i>Image 1</label>
                                            <div class="border rounded p-3"
                                                style="background: #ffffff; min-height: 220px;">
                                                <img id="buildingImagePreview" src=""
                                                    alt="Building Image Preview" class="img-fluid"
                                                    style="display: none; max-height: 200px; width: 100%; object-fit: contain; border-radius: 8px;">
                                                <div id="noImagePlaceholder" class="text-center text-muted"
                                                    style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 180px;">
                                                    <i class="fas fa-cloud-upload-alt fa-3x mb-2"
                                                        style="color: #cbd5e1;"></i>
                                                    <p>No image selected</p>
                                                </div>
                                            </div>
                                            <div class="mt-2"><label class="btn btn-outline-primary btn-sm w-100"><i
                                                        class="fas fa-upload me-1"></i> Choose Image<input type="file"
                                                        name="image" id="building_image" accept="image/*"
                                                        style="display: none;"></label></div>
                                            <div id="building_image_error" class="error-message text-danger small mt-1">
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="fw-bold mb-2"><i class="fas fa-camera me-1"></i>Image 2</label>
                                            <div class="border rounded p-3"
                                                style="background: #ffffff; min-height: 220px;">
                                                <img id="buildingImagePreview2" src=""
                                                    alt="Building Image Preview 2" class="img-fluid"
                                                    style="display: none; max-height: 200px; width: 100%; object-fit: contain; border-radius: 8px;">
                                                <div id="noImagePlaceholder2" class="text-center text-muted"
                                                    style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 180px;">
                                                    <i class="fas fa-cloud-upload-alt fa-3x mb-2"
                                                        style="color: #cbd5e1;"></i>
                                                    <p>No image selected</p>
                                                </div>
                                            </div>
                                            <div class="mt-2"><label class="btn btn-outline-primary btn-sm w-100"><i
                                                        class="fas fa-upload me-1"></i> Choose Image<input type="file"
                                                        name="image2" id="building_image2" accept="image/*"
                                                        style="display: none;"></label></div>
                                            <div id="building_image2_error" class="error-message text-danger small mt-1">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card mb-4">
                                <div class="card-header"
                                    style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-3"><label class="form-label">GIS ID</label><input
                                                type="text" class="form-control" name="building_gisid"
                                                id="building_gisid" value="" readonly>
                                            <div id="building_gisid_error" class="error-message text-danger small"></div>
                                        </div>
                                        <div class="col-md-3 mb-3"><label class="form-label">Zone</label><select
                                                class="form-select" name="building_zone" id="building_zone">
                                                <option value="">Select Zone</option>
                                                <option value="ZONE-A">ZONE-A</option>
                                                <option value="ZONE-B">ZONE-B</option>
                                                <option value="ZONE-C">ZONE-C</option>
                                                <option value="ZONE-D">ZONE-D</option>
                                                <option value="ZONE-E">ZONE-E</option>
                                            </select>
                                            <div id="building_zone_error" class="error-message text-danger small"></div>
                                        </div>
                                        <div class="col-md-3 mb-3"><label class="form-label">Number of Bills</label><input
                                                type="number" class="form-control" name="number_bill" id="number_bill"
                                                min="0">
                                            <div id="number_bill_error" class="error-message text-danger small"></div>
                                        </div>
                                        <div class="col-md-3 mb-3"><label class="form-label">Number of Shops</label><input
                                                type="number" class="form-control" name="number_shop" id="number_shop"
                                                min="0">
                                            <div id="number_shop_error" class="error-message text-danger small"></div>
                                        </div>
                                        <div class="col-md-3 mb-3"><label class="form-label">Number of
                                                Floors</label><input type="number" class="form-control"
                                                name="number_floor" id="number_floor" min="0">
                                            <div id="number_floor_error" class="error-message text-danger small"></div>
                                        </div>
                                        <div class="col-md-3 mb-3"><label class="form-label">Percentage</label><select
                                                class="form-select" name="percentage" id="percentage">
                                                <option value="">Select Percentage</option>
                                                <option value="10">10%</option>
                                                <option value="20">20%</option>
                                                <option value="30">30%</option>
                                                <option value="40">40%</option>
                                                <option value="50">50%</option>
                                                <option value="60">60%</option>
                                                <option value="70">70%</option>
                                                <option value="80">80%</option>
                                                <option value="85">85%</option>
                                                <option value="90">90%</option>
                                                <option value="100">100%</option>
                                            </select>
                                            <div id="percentage_error" class="error-message text-danger small"></div>
                                        </div>
                                        <div class="col-md-6 mb-3"><label class="form-label">Building Name</label><input
                                                type="text" class="form-control" name="building_name"
                                                id="building_name" placeholder="Enter building name">
                                            <div id="building_name_error" class="error-message text-danger small"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Road Name</label>
                                            <select class="form-select" id="road_name" name="road_name">
                                                <option value="">Select Road Name</option>

                                                @foreach ($uniqueRoadNames as $road)
                                                    <option value="{{ $road }}">
                                                        {{ $road }}
                                                    </option>
                                                @endforeach

                                            </select>

                                            <div id="road_name_error" class="error-message text-danger small"></div>
                                        </div>
                                        <div class="col-md-6 mb-3"><label class="form-label">Phone Number</label><input
                                                type="tel" class="form-control" name="phone" id="phone_building"
                                                placeholder="10-digit mobile number" maxlength="10">
                                            <div id="phone_building_error" class="error-message text-danger small"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card mb-4">
                                <div class="card-header"
                                    style="background: linear-gradient(135deg, #28a745, #20c997); color: white;">
                                    <h6 class="mb-0"><i class="fas fa-building me-2"></i>Building Details</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3"><label class="form-label">Building Usage</label><select
                                                class="form-select" name="building_usage" id="building_usage">
                                                <option value="">Select Usage</option>
                                                <option value="RESIDENTIAL">Residential</option>
                                                <option value="COMMERCIAL">Commercial</option>
                                                <option value="INDUSTRIAL">Industrial</option>
                                                <option value="INSTITUTIONAL">Institutional</option>
                                                <option value="MIXED">Mixed</option>
                                                <option value="GOVERNMENT">Government</option>
                                                <option value="VACANT">Vacant</option>
                                            </select>
                                            <div id="building_usage_error" class="error-message text-danger small"></div>
                                        </div>
                                        <div class="col-md-4 mb-3"><label class="form-label">Construction
                                                Type</label><select class="form-select" name="construction_type"
                                                id="construction_type">
                                                <option value="">Select Type</option>
                                                <option value="PERMANENT">Permanent</option>
                                                <option value="SEMI_PERMANENT">Semi Permanent</option>
                                                <option value="VACANT_LAND">Vacant Land</option>
                                                <option value="SHED">Shed</option>
                                                <option value="CAR_SHED">Car Shed</option>
                                                <option value="TEMPORARY">Temporary</option>
                                            </select>
                                            <div id="construction_type_error" class="error-message text-danger small">
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3"><label class="form-label">Building Type</label><select
                                                class="form-select" name="building_type" id="building_type">
                                                <option value="">Select Type</option>
                                                <option value="Independent">Independent</option>
                                                <option value="Flat">Flat</option>
                                                <option value="Kalyana_Mandapam">Kalyana Mandapam</option>
                                                <option value="Hotel">Hotel</option>
                                                <option value="Cinema_Theatre">Cinema Theatre</option>
                                                <option value="Central_Government_Building">Central Government Building
                                                </option>
                                                <option value="State_Government_Building">State Government Building
                                                </option>
                                                <option value="Municipality_Corporation">Municipality / Corporation
                                                </option>
                                                <option value="Educational_Institution">Educational Institution</option>
                                                <option value="Hospital">Hospital</option>
                                                <option value="Commercial_Complex">Commercial Complex</option>
                                                <option value="Shop">Shop</option>
                                                <option value="Office">Office</option>
                                                <option value="Temple">Temple</option>
                                                <option value="Mosque">Mosque</option>
                                                <option value="Church">Church</option>
                                                <option value="Amma_Unavagam">Amma Unavagam</option>
                                                <option value="Public_Toilet">Public Toilet</option>
                                                <option value="Vacant Land">Vacant Land</option>
                                                <option value="Under Construction">Under Construction</option>
                                                <option value="Others">Others</option>
                                            </select>
                                            <div id="building_type_error" class="error-message text-danger small"></div>
                                        </div>
                                        <div class="col-md-4 mb-3"><label class="form-label">UGD Status</label><select
                                                class="form-select" name="ugd" id="ugd">
                                                <option value="">Select Status</option>
                                                <option value="No_Connection">No Connection</option>
                                                <option value="Manhole_Available_but_Connection_Not_Given_to_House">Manhole
                                                    Available but Connection Not Given</option>
                                                <option value="Stage_1_Completed">Stage 1 Completed</option>
                                                <option value="Stage_1_2_Completed">Stage 1 & 2 Completed</option>
                                                <option value="Stage_1_2_Completed_but_Not_Connected">Stage 1 & 2 Completed
                                                    but Not Connected</option>
                                                <option value="Stage_1_2_3_Completed">Stage 1, 2 & 3 Completed</option>
                                                <option value="Direct_Connection_Given">Direct Connection Given</option>
                                                <option value="1_UGD_Connection_-_3_Stage_Completed">1 UGD Connection - 3
                                                    Stage Completed</option>
                                                <option value="2_UGD_Connection_-_3_Stage_Completed">2 UGD Connection - 3
                                                    Stage Completed</option>
                                            </select>
                                            <div id="ugd_error" class="error-message text-danger small"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card mb-4">
                                <div class="card-header"
                                    style="background: linear-gradient(135deg, #ffc107, #ff9800); color: #333;">
                                    <h6 class="mb-0"><i class="fas fa-umbrella me-2"></i>Amenities</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-3"><label class="form-label">Lift Room</label><select
                                                class="form-select" name="liftroom" id="liftroom">
                                                <option value="No">No</option>
                                                <option value="Yes">Yes</option>
                                            </select>
                                            <div id="liftroom_error" class="error-message text-danger small"></div>
                                        </div>
                                        <div class="col-md-3 mb-3"><label class="form-label">Head Room</label><select
                                                class="form-select" name="headroom" id="headroom">
                                                <option value="No">No</option>
                                                <option value="Yes">Yes</option>
                                            </select>
                                            <div id="headroom_error" class="error-message text-danger small"></div>
                                        </div>
                                        <div class="col-md-3 mb-3"><label class="form-label">Overhead Tank</label><select
                                                class="form-select" name="overhead_tank" id="overhead_tank">
                                                <option value="No">No</option>
                                                <option value="Yes">Yes</option>
                                            </select>
                                            <div id="overhead_tank_error" class="error-message text-danger small"></div>
                                        </div>
                                        <div class="col-md-3 mb-3"><label class="form-label">Rainwater
                                                Harvesting</label><select class="form-select" name="rainwater_harvesting"
                                                id="rainwater_harvesting">
                                                <option value="No">No</option>
                                                <option value="Yes">Yes</option>
                                            </select>
                                            <div id="rainwater_harvesting_error" class="error-message text-danger small">
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3"><label class="form-label">Parking</label><select
                                                class="form-select" name="parking" id="parking">
                                                <option value="No">No</option>
                                                <option value="Yes">Yes</option>
                                            </select>
                                            <div id="parking_error" class="error-message text-danger small"></div>
                                        </div>
                                        <div class="col-md-3 mb-3"><label class="form-label">Ramp</label><select
                                                class="form-select" name="ramp" id="ramp">
                                                <option value="No">No</option>
                                                <option value="Yes">Yes</option>
                                            </select>
                                            <div id="ramp_error" class="error-message text-danger small"></div>
                                        </div>
                                        <div class="col-md-3 mb-3"><label class="form-label">Hoarding</label><select
                                                class="form-select" name="hoarding" id="hoarding">
                                                <option value="No">No</option>
                                                <option value="Yes">Yes</option>
                                            </select>
                                            <div id="hoarding_error" class="error-message text-danger small"></div>
                                        </div>
                                        <div class="col-md-3 mb-3"><label class="form-label">CCTV</label><select
                                                class="form-select" name="cctv" id="cctv">
                                                <option value="No">No</option>
                                                <option value="Yes">Yes</option>
                                            </select>
                                            <div id="cctv_error" class="error-message text-danger small"></div>
                                        </div>
                                        <div class="col-md-3 mb-3"><label class="form-label">Cell Tower</label><select
                                                class="form-select" name="cell_tower" id="cell_tower">
                                                <option value="No">No</option>
                                                <option value="Yes">Yes</option>
                                            </select>
                                            <div id="cell_tower_error" class="error-message text-danger small"></div>
                                        </div>
                                        <div class="col-md-3 mb-3"><label class="form-label">Solar Panel</label><select
                                                class="form-select" name="solar_panel" id="solar_panel">
                                                <option value="No">No</option>
                                                <option value="Yes">Yes</option>
                                            </select>
                                            <div id="solar_panel_error" class="error-message text-danger small"></div>
                                        </div>
                                        <div class="col-md-3 mb-3"><label class="form-label">Basement</label><input
                                                type="number" class="form-control" name="basement" id="basement"
                                                min="0" placeholder="Number of basements">
                                            <div id="basement_error" class="error-message text-danger small"></div>
                                        </div>
                                        <div class="col-md-3 mb-3"><label class="form-label">Water
                                                Connection</label><select class="form-select" name="water_connection"
                                                id="water_connection">
                                                <option value="No">No</option>
                                                <option value="Yes">Yes</option>
                                            </select>
                                            <div id="water_connection_error" class="error-message text-danger small">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card mb-4">
                                <div class="card-header"
                                    style="background: linear-gradient(135deg, #6c757d, #5a6268); color: white;">
                                    <h6 class="mb-0"><i class="fas fa-comment me-2"></i>Remarks</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3"><label class="form-label">General Remarks</label>
                                            <textarea class="form-control" name="remarks" id="remarks_building" rows="3"
                                                placeholder="Enter general remarks..."></textarea>
                                            <div id="remarks_building_error" class="error-message text-danger small">
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3"><label class="form-label">Corporation Remarks</label>
                                            <textarea class="form-control" name="corporationremarks" id="corporationremarks" rows="3"
                                                placeholder="Enter corporation remarks..."></textarea>
                                            <div id="corporationremarks_error" class="error-message text-danger small">
                                            </div>
                                        </div>
                                        <div class="col-md-12 mb-3"><label class="form-label">QC Remarks</label>
                                            <textarea class="form-control" name="qc_remarks" id="qc_remarks" rows="2" placeholder="Enter QC remarks..."></textarea>
                                            <div id="qc_remarks_error" class="error-message text-danger small"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0;">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i
                                    class="fas fa-times me-2"></i>Close</button>
                            <button type="submit" class="btn btn-primary" id="buildingsubmitBtn"><i
                                    class="fas fa-save me-2"></i>Save Building Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ── Edit Point Data Modal ── --}}
        <div class="modal fade" id="pointDetailsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content bld-modal-content">
                    <div class="modal-header bld-modal-header"
                        style="background: linear-gradient(135deg,#2563eb,#1d4ed8);">
                        <div class="bld-header-inner">
                            <div class="bld-header-icon"><i class="bi bi-pencil-square"></i></div>
                            <div>
                                <h5 class="modal-title bld-modal-title">Edit Point Data</h5>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">

                        <form id="pointDetailsForm" class="needs-validation" novalidate>
                            @csrf


                            <!-- Tabs -->
                            <ul class="nav nav-tabs mb-3" id="pointDetailsTabs" role="tablist">

                                <li class="nav-item">
                                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#basic-tab"
                                        type="button">
                                        Basic Info
                                    </button>
                                </li>

                                <li class="nav-item">
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#water-tab"
                                        type="button">
                                        Water Tax
                                    </button>
                                </li>

                                <li class="nav-item">
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#ugd-tab"
                                        type="button">
                                        UGD Tax
                                    </button>
                                </li>

                                <li class="nav-item">
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pt-tab"
                                        type="button">
                                        Professional Tax
                                    </button>
                                </li>

                            </ul>

                            <div class="tab-content">

                                <!-- BASIC INFO -->
                                <div class="tab-pane fade show active" id="basic-tab">
                                    <input type="text" class="form-control" id="point_gisid" name="point_gisid"
                                        hidden>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="assessment_type" class="form-label">
                                                Assessment Type <span class="text-danger">*</span>
                                            </label>

                                            <select class="form-control" id="assessment_type" name="assessment_type"
                                                required>
                                                <option value="">-- Select Assessment Type --</option>
                                                <option value="OLD">OLD</option>
                                                <option value="NEW">NEW</option>
                                                <option value="VACANT">VACANT</option>
                                                <option value="OTHER_WARD">OTHER WARD</option>
                                            </select>

                                            <div class="invalid-feedback">
                                                Please select assessment type.
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="assessment" class="form-label">Assessment <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="assessment" name="assessment"
                                                required>
                                            <div class="invalid-feedback">Please enter assessment number.</div>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="old_assessment" class="form-label">Old Assessment</label>
                                            <input type="text" class="form-control" id="old_assessment"
                                                name="old_assessment">
                                            <div class="invalid-feedback">Please enter old assessment number.</div>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="zone" class="form-label">Zone <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="zone" name="zone"
                                                required>
                                            <div class="invalid-feedback">Please enter zone.</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="owner_name" class="form-label">Owner Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="owner_name" name="owner_name"
                                                required>
                                            <div class="invalid-feedback">Please enter owner name.</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="present_owner_name" class="form-label">Present Owner Name</label>
                                            <input type="text" class="form-control" id="present_owner_name"
                                                name="present_owner_name">
                                            <div class="invalid-feedback">Please enter present owner name.</div>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="phone_number" class="form-label">Phone Number <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="phone_number"
                                                name="phone_number" pattern="[0-9]{10}" required>
                                            <div class="invalid-feedback">Please enter a valid 10-digit phone number.</div>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="old_door_no" class="form-label">Old Door No</label>
                                            <input type="text" class="form-control" id="old_door_no"
                                                name="old_door_no">
                                            <div class="invalid-feedback">Please enter old door number.</div>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="new_door_no" class="form-label">New Door No <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="new_door_no"
                                                name="new_door_no" required>
                                            <div class="invalid-feedback">Please enter new door number.</div>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="aadhar_no" class="form-label">Aadhar No</label>
                                            <input type="text" class="form-control" id="aadhar_no" name="aadhar_no"
                                                pattern="[0-9]{12}">
                                            <div class="invalid-feedback">Please enter a valid 12-digit Aadhar number.
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="ration_no" class="form-label">Ration No</label>
                                            <input type="text" class="form-control" id="ration_no" name="ration_no">
                                            <div class="invalid-feedback">Please enter ration number.</div>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="floor" class="form-label">Floor <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="floor" name="floor"
                                                required>
                                            <div class="invalid-feedback">Please enter floor number.</div>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="number_persons" class="form-label">Number Persons</label>
                                            <input type="number" class="form-control" id="number_persons"
                                                name="number_persons" min="1">
                                            <div class="invalid-feedback">Please enter a valid number (minimum 1).</div>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="bill_usage" class="form-label">Bill Usage <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select" id="bill_usage" name="bill_usage" required>
                                                <option value="">Select</option>
                                                <option value="Residential">Residential</option>
                                                <option value="Commercial">Commercial</option>
                                            </select>
                                            <div class="invalid-feedback">Please select bill usage type.</div>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="eb" class="form-label">EB</label>
                                            <input type="text" class="form-control" id="eb" name="eb">
                                            <div class="invalid-feedback">Please enter EB number.</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="worker_name" class="form-label">Worker Name</label>
                                            <input type="text" class="form-control" id="worker_name"
                                                name="worker_name">
                                            <div class="invalid-feedback">Please enter worker name.</div>
                                        </div>

                                        <div class="col-md-12">
                                            <label for="remarks" class="form-label">Remarks</label>
                                            <textarea class="form-control" id="remarks" name="remarks" rows="2"></textarea>
                                            <div class="invalid-feedback">Please enter remarks.</div>
                                        </div>

                                    </div>

                                </div>

                                <!-- WATER TAX -->
                                <div class="tab-pane fade" id="water-tab">

                                    <div class="row g-3">

                                        <div class="col-md-4">
                                            <label for="watertax_no" class="form-label">Water Tax No <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="watertax_no"
                                                name="watertax_no" required>
                                            <div class="invalid-feedback">Please enter water tax number.</div>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="old_watertax_no" class="form-label">Old Water Tax No</label>
                                            <input type="text" class="form-control" id="old_watertax_no"
                                                name="old_watertax_no">
                                            <div class="invalid-feedback">Please enter old water tax number.</div>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="water_usage" class="form-label">Water Usage <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select" id="water_usage" name="water_usage" required>
                                                <option value="">Select</option>
                                                <option value="Domestic">Domestic</option>
                                                <option value="Commercial">Commercial</option>
                                                <option value="Industrial">Industrial</option>
                                                <option value="Institutional">Institutional</option>
                                            </select>
                                            <div class="invalid-feedback">Please select water usage type.</div>
                                        </div>





                                        <div class="col-md-4">
                                            <label for="water_DBC_type" class="form-label">Water DBC Type</label>
                                            <input type="text" class="form-control" id="water_DBC_type"
                                                name="water_DBC_type">
                                            <div class="invalid-feedback">Please enter DBC type.</div>
                                        </div>

                                        <div class="col-md-12">
                                            <label for="water_slab_description" class="form-label">Water Slab
                                                Description</label>
                                            <textarea class="form-control" id="water_slab_description" name="water_slab_description" rows="2"></textarea>
                                            <div class="invalid-feedback">Please enter slab description.</div>
                                        </div>

                                    </div>

                                </div>

                                <!-- UGD TAX -->
                                <div class="tab-pane fade" id="ugd-tab">

                                    <div class="row g-3">

                                        <div class="col-md-4">
                                            <label for="ugd_no" class="form-label">UGD No <span
                                                    class="text-danger">*</span></label>
                                            <input class="form-control" id="ugd_no" name="ugd_no" required>
                                            <div class="invalid-feedback">Please enter UGD number.</div>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="old_ugd_no" class="form-label">Old UGD No</label>
                                            <input class="form-control" id="old_ugd_no" name="old_ugd_no">
                                            <div class="invalid-feedback">Please enter old UGD number.</div>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="ugd_usage" class="form-label">UGD Usage <span
                                                    class="text-danger">*</span></label>
                                            <input class="form-control" id="ugd_usage" name="ugd_usage" required>
                                            <div class="invalid-feedback">Please enter UGD usage.</div>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="ugd_DBC_type" class="form-label">UGD DBC Type</label>
                                            <input class="form-control" id="ugd_DBC_type" name="ugd_DBC_type">
                                            <div class="invalid-feedback">Please enter DBC type.</div>
                                        </div>

                                        <div class="col-md-8">
                                            <label for="ugd_slab_description" class="form-label">UGD Slab
                                                Description</label>
                                            <textarea class="form-control" id="ugd_slab_description" name="ugd_slab_description" rows="2"></textarea>
                                            <div class="invalid-feedback">Please enter slab description.</div>
                                        </div>

                                    </div>

                                </div>

                                <!-- PROFESSIONAL TAX -->
                                <div class="tab-pane fade" id="pt-tab">

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">Professional Tax</h5>

                                        <button type="button" class="btn btn-primary btn-sm" id="addProfessionalBtn">
                                            <i class="bi bi-plus-circle"></i>
                                            Add Professional Tax
                                        </button>
                                    </div>

                                    <div id="professionalContainer"></div>

                                </div>

                            </div>

                            <!-- Hidden fields for form validation tracking -->
                            <input type="hidden" id="validationTrigger" name="validationTrigger" value="false">

                        </form>
                    </div>
                    <div class="modal-footer bld-modal-footer">
                        <button type="button" class="btn bld-btn-save" id="savePointDetails">
                            <i class="bi bi-save me-1"></i>Update Point Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
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
                                    <label for="line_gisid" class="form-label">GIS ID <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="line_gisid" name="gisid" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="line_road_name" class="form-label">Road Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="line_road_name" name="road_name"
                                        required>
                                </div>
                            </div>


                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="saveLineDetails">
                            <i class="fas fa-save"></i> Save Changes
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

                // ─── MODAL CACHE ───
                const deleteModalEl = document.getElementById('deleteFeatureModal');
                const deleteModal = new bootstrap.Modal(deleteModalEl, {
                    backdrop: true,
                    keyboard: true
                });

                // ─── DATA ───
                let polygons = @json($polygons ?? [], JSON_HEX_TAG);
                let lines = @json($lines ?? [], JSON_HEX_TAG);
                let points = @json($points ?? [], JSON_HEX_TAG);
                let pointDatas = @json($pointDatas ?? [], JSON_HEX_TAG);
                let polygonDatas = @json($polygonDatas ?? [], JSON_HEX_TAG);
                let ward = @json($ward ?? [], JSON_HEX_TAG);
                const misData = @json($misData);
                // pt Dynamic add
                let ptIndex = 0;
                console.log(misData);
                let searchIndex = [];

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

                    // Find building data for this GIS ID
                    const buildingData = polygonDatas.find(d => d.gisid == gisid);

                    // Count points that have this GIS ID as point_gisid
                    const pointCount = pointDatas.filter(d => d.point_gisid == gisid).length;

                    let color = 'blue'; // Default color

                    if (buildingData) {
                        const expectedBills = parseInt(buildingData.number_bill) || 0;

                        if (pointCount === 0) {
                            color = 'blue'; // No points mapped
                        } else if (pointCount >= expectedBills) {
                            color = 'green'; // All bills are mapped (or more)
                        } else {
                            color = 'red'; // Some bills missing
                        }
                    }

                    return new ol.style.Style({
                        image: new ol.style.Circle({
                            radius: 8,
                            fill: new ol.style.Fill({
                                color: color
                            }),
                            stroke: new ol.style.Stroke({
                                color: color,
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
                        showToast('⚠️ Geolocation is not supported by your browser', 3000);
                        return false;
                    }
                    if (watchId !== null) return true;

                    navigator.geolocation.getCurrentPosition(
                        function(pos) {
                            onPosition(pos);
                            showToast('📍 Location acquired', 2000);
                        },
                        function(error) {
                            let msg = 'Could not get initial location: ';
                            switch (error.code) {
                                case error.PERMISSION_DENIED:
                                    msg += 'Please allow location access';
                                    break;
                                case error.POSITION_UNAVAILABLE:
                                    msg += 'GPS signal weak';
                                    break;
                                case error.TIMEOUT:
                                    msg += 'Request timed out - trying again';
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
                            let msg = 'Location tracking error: ';
                            switch (error.code) {
                                case error.PERMISSION_DENIED:
                                    msg += 'Please enable location permissions.';
                                    break;
                                case error.POSITION_UNAVAILABLE:
                                    msg += 'Location information unavailable.';
                                    break;
                                case error.TIMEOUT:
                                    msg += 'Location request timed out.';
                                    break;
                                default:
                                    msg += 'Unknown error occurred.';
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

                // ─── INTERACTIONS & MODE STATE ───
                let drawInteraction = null,
                    selectInteraction = null,
                    currentDrawType = null;
                let selectedFeature = null,
                    routeLayer = null,
                    currentMode = 'none';
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

                // ─── Disable ALL interactions ───
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
                    currentDrawType = null;
                }

                // ─── Feature details popup ───
                function showFeatureDetails(feature) {
                    if (!feature) return;
                    const gisid = feature.get('gisid');
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

                function lineClick(feature) {
                    console.log('Line clicked:', feature.getProperties());
                    const gisid = feature.get('gisid');
                    const roadName = feature.get('road_name') || feature.get('name') || '';
                    const modal = new bootstrap.Modal(document.getElementById('lineDetailsModal'));
                    modal.show();
                    populateLineForm(gisid, roadName);
                }

                function populateLineForm(gisid, roadName) {
                    // Set basic form fields
                    console.log(roadName)
                    $('#line_gisid').val(gisid || '');
                    $('#line_road_name').val(roadName || '');
                    // Clear any existing validation errors
                    $('.is-invalid').removeClass('is-invalid');
                    $('.invalid-feedback').remove();
                }

                // Save Line Data
                $('#saveLineDetails').on('click', function() {
                    const formData = new FormData(document.getElementById('lineDetailsForm'));

                    // Show saving indicator
                    $(this).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
                    $(this).prop('disabled', true);
                    formData.append('_token', $('input[name="_token"]').val());

                    // Validate required fields
                    const gisid = $('#line_gisid').val();
                    const roadName = $('#line_road_name').val();

                    if (!gisid || !roadName) {
                        showFlashMessage('GISID and Road Name are required fields', 'error');
                        $(this).html('<i class="fas fa-save"></i> Save Changes');
                        $(this).prop('disabled', false);
                        return;
                    }

                    $.ajax({
                        url: '/line-data',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            showFlashMessage('Line data saved successfully!', 'success');
                            console.log(response.lines);
                            lines = response.lines;
                            $('#lineDetailsModal').modal('hide');
                            // Refresh map features if needed
                            reloadAllSources();
                        },
                        error: function(xhr) {
                            console.error('Error saving line data:', xhr);
                            if (xhr.responseJSON && xhr.responseJSON.errors) {
                                // Display validation errors
                                const errors = xhr.responseJSON.errors;
                                Object.keys(errors).forEach(key => {
                                    const field = $(`#line_${key}`);
                                    if (field.length) {
                                        field.addClass('is-invalid');
                                        field.after(
                                            `<div class="invalid-feedback">${errors[key][0]}</div>`
                                        );
                                    }
                                });
                            } else {
                                showFlashMessage('Failed to save line data. Please try again.',
                                    'error');
                            }
                        },
                        complete: function() {
                            $('#saveLineDetails').html('<i class="fas fa-save"></i> Save Changes');
                            $('#saveLineDetails').prop('disabled', false);
                        }
                    });
                });

                function pointClick(feature) {
                    console.log('Point clicked:', feature.getProperties());

                    const gisid = feature.get('gisid');

                    // Find building data by gisid
                    let building = polygonDatas.find(polygondata => polygondata.gisid == gisid);

                    if (building) {
                        let buildingBillCount = building['number_bill'] || 0;

                        let point = pointDatas.filter(pointdata => pointdata.point_gisid == gisid);

                        let pointDataCount = point.length;

                        if (buildingBillCount > pointDataCount) {
                            // Show modal
                            const modal = new bootstrap.Modal(document.getElementById('pointDetailsModal'));
                            modal.show();
                            $('#pointDetailsTabs button:first').tab('show');



                            populatePointForm(gisid);



                        } else {
                            // Use custom flash message instead of toastr
                            showFlashMessage('All bills are already mapped for this point (Bills: ' +
                                buildingBillCount +
                                ', Mapped: ' + pointDataCount + ')', 'info');
                            console.log('No new bills to map for gisid:', gisid);
                        }

                    } else {
                        console.warn('No building found for gisid:', gisid);
                        // Use custom flash message instead of toastr
                        showFlashMessage('No building data found for this point', 'error');
                    }
                }

                function populatePointForm(gisid) {
                    ptIndex = 0
                    console.log(gisid)
                    $('#point_gisid').val(gisid || '');
                    $('#building_data_id').val('');
                    $('#assessment').val('');
                    $('#old_assessment').val('');
                    $('#zone').val('');
                    $('#owner_name').val('');
                    $('#present_owner_name').val('');
                    $('#phone_number').val('');
                    $('#old_door_no').val('');
                    $('#new_door_no').val('');
                    $('#aadhar_no').val('');
                    $('#ration_no').val('');
                    $('#floor').val('');
                    $('#number_persons').val('');
                    $('#bill_usage').val('');
                    $('#eb').val('');
                    $('#worker_name').val('');
                    $('#remarks').val('');

                    // Water Tax Tab - Clear all fields
                    $('#watertax_no').val('');
                    $('#old_watertax_no').val('');
                    $('#water_usage').val(''); // For select dropdown, this will select the first empty option
                    $('#water_slab_rate').val('');
                    $('#water_balance').val('');
                    $('#water_slab_description').val('');
                    $('#water_DBC_type').val(''); // For select dropdown, this will select the first empty option

                    // UGD Tax Tab - Clear all fields
                    $('#ugd_no').val('');
                    $('#old_ugd_no').val('');
                    $('#ugd_usage').val('');
                    $('#ugd_slab_description').val('');
                    $('#ugd_DBC_type').val('');

                    // Professional Tax Tab - Clear all fields
                    $('#pt_number').val('');
                    $('#old_pt_number').val('');
                    $('#establishment_name').val('');
                    $('#profession_type').val('');
                    $('#employee_count').val('');
                    $('#half_year_tax').val('');
                    $('#pt_remarks').val('');

                    // Reset to first tab
                    $('#pointDetailsTabs button:first').tab('show');
                    $('#professionalContainer').empty();

                }

                // Save Point Data
                $('#savePointDetails').on('click', function() {
                    const $form = $('#pointDetailsForm');
                    const editId = $form.attr('data-edit-id');
                    const formData = new FormData(document.getElementById('pointDetailsForm'));
                    formData.append('_token', $('input[name="_token"]').val());

                    const $btn = $(this).html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop(
                        'disabled', true);

                    if (editId) {
                        formData.append('_method', 'PUT');
                        $.ajax({
                            url: `/point-data/${editId}`,
                            method: 'POST', // Laravel method spoofing
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function() {
                                showFlashMessage('Point data updated successfully!', 'success');
                                $('#pointDetailsModal').modal('hide');
                                $form.removeAttr('data-edit-id');
                                reloadAllSources();
                            },
                            error: function(xhr) {
                                showFlashMessage(xhr.responseJSON?.message || 'Update failed.',
                                    'error');
                            },
                            complete: () => $btn.html(
                                '<i class="bi bi-save me-1"></i>Update Point Data').prop('disabled',
                                false)
                        });
                    } else {
                        $.ajax({
                            url: '/point-data',
                            method: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function() {
                                showFlashMessage('Point data saved successfully!', 'success');
                                $('#pointDetailsModal').modal('hide');
                                reloadAllSources();
                            },
                            error: function() {
                                showFlashMessage('Failed to save point data.', 'error');
                            },
                            complete: () => $btn.html(
                                '<i class="bi bi-save me-1"></i>Update Point Data').prop('disabled',
                                false)
                        });
                    }
                });

                // ─── Polygon Click ───
                function polygonClick(feature) {
                    const gisid = feature.get('gisid');
                    let building = polygonDatas.find(polygondata => polygondata.gisid == gisid);
                    console.log('Selected building:', building);

                    if (building) {
                        populateBuildingForm(building);
                    } else {
                        resetBuildingForm(gisid);
                    }

                    const modal = new bootstrap.Modal(document.getElementById('buildingDataModal'));
                    modal.show();
                }

                function populateBuildingForm(item) {
                    // Fix: Use item.gisid instead of gsiid
                    $("#building_gisid").val(item.gisid || "");
                    $("#number_bill").val(item.number_bill || "");
                    $("#number_shop").val(item.number_shop || "");
                    $("#number_floor").val(item.number_floor || "");
                    $("#building_name").val(item.building_name || "");
                    $("#road_name").val(item.road_name || "");
                    $("#phone_building").val(item.phone || "");
                    $("#building_zone").val(item.zone || item.building_zone || "");
                    $("#percentage").val(item.percentage || "");
                    $("#building_usage").val(item.building_usage || "");
                    $("#construction_type").val(item.construction_type || "");
                    $("#building_type").val(item.building_type || "");
                    $("#ugd").val(item.ugd || "");
                    $("#liftroom").val(item.liftroom || "No");
                    $("#headroom").val(item.headroom || "No");
                    $("#overhead_tank").val(item.overhead_tank || "No");
                    $("#rainwater_harvesting").val(item.rainwater_harvesting || "No");
                    $("#parking").val(item.parking || "No");
                    $("#ramp").val(item.ramp || "No");
                    $("#hoarding").val(item.hoarding || "No");
                    $("#cctv").val(item.cctv || "No");
                    $("#cell_tower").val(item.cell_tower || "No");
                    $("#solar_panel").val(item.solar_panel || "No");
                    $("#basement").val(item.basement || "");
                    $("#water_connection").val(item.water_connection || "No");
                    $("#remarks_building").val(item.remarks || "");
                    $("#corporationremarks").val(item.corporationremarks || "");
                    $("#qc_remarks").val(item.qc_remarks || "");

                    // Image handling
                    const assetUrl = window.assetUrl || "{{ asset('') }}";

                    if (item.image && item.image !== "") {
                        const imageUrl = item.image.startsWith('http') ? item.image : assetUrl + item.image;
                        $("#buildingImagePreview").attr("src", imageUrl).show();
                        $("#noImagePlaceholder").hide();
                    } else {
                        $("#buildingImagePreview").hide();
                        $("#noImagePlaceholder").show();
                    }

                    if (item.image2 && item.image2 !== "") {
                        const imageUrl2 = item.image2.startsWith('http') ? item.image2 : assetUrl + item.image2;
                        $("#buildingImagePreview2").attr("src", imageUrl2).show();
                        $("#noImagePlaceholder2").hide();
                    } else {
                        $("#buildingImagePreview2").hide();
                        $("#noImagePlaceholder2").show();
                    }
                }

                function resetBuildingForm(gisid) {
                    // Clear all fields
                    $("#building_gisid").val(gisid || "");
                    $("#number_bill").val("");
                    $("#number_shop").val("");
                    $("#number_floor").val("");
                    $("#building_name").val("");
                    $("#phone_building").val("");
                    $("#building_zone").val("");
                    $("#percentage").val("");
                    $("#building_usage").val("");
                    $("#construction_type").val("");
                    $("#building_type").val("");
                    $("#ugd").val("");
                    $("#liftroom").val("No");
                    $("#headroom").val("No");
                    $("#overhead_tank").val("No");
                    $("#rainwater_harvesting").val("No");
                    $("#parking").val("No");
                    $("#ramp").val("No");
                    $("#hoarding").val("No");
                    $("#cctv").val("No");
                    $("#cell_tower").val("No");
                    $("#solar_panel").val("No");
                    $("#basement").val("");
                    $("#water_connection").val("No");
                    $("#remarks_building").val("");
                    $("#corporationremarks").val("");
                    $("#qc_remarks").val("");

                    // Reset images
                    $("#buildingImagePreview").hide().attr("src", "");
                    $("#buildingImagePreview2").hide().attr("src", "");
                    $("#noImagePlaceholder").show();
                    $("#noImagePlaceholder2").show();
                    $("#building_image").val("");
                    $("#building_image2").val("");

                    // Clear errors
                    $(".error-message").html("");
                    $(".is-invalid").removeClass("is-invalid");

                    // Reset submit button
                    $("#buildingsubmitBtn").prop('disabled', false).html(
                        '<i class="fas fa-save me-2"></i>Save Building Data');
                }

                // ─── Image Preview ───
                $(document).ready(function() {
                    // Image 1 preview
                    $("#building_image").on('change', function(e) {
                        const file = this.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                $("#buildingImagePreview").attr('src', e.target.result).show();
                                $("#noImagePlaceholder").hide();
                            };
                            reader.readAsDataURL(file);
                        } else {
                            $("#buildingImagePreview").hide();
                            $("#noImagePlaceholder").show();
                        }
                    });

                    // Image 2 preview
                    $("#building_image2").on('change', function(e) {
                        const file = this.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                $("#buildingImagePreview2").attr('src', e.target.result).show();
                                $("#noImagePlaceholder2").hide();
                            };
                            reader.readAsDataURL(file);
                        } else {
                            $("#buildingImagePreview2").hide();
                            $("#noImagePlaceholder2").show();
                        }
                    });
                });

                // ─── Form Submission ───
                $(document).ready(function() {
                    $('#buildingForm').on('submit', function(e) {
                        e.preventDefault();

                        // Clear previous errors
                        $(".error-message").html("");
                        $(".is-invalid").removeClass("is-invalid");



                        // Show loading state
                        // const submitBtn = $("#buildingsubmitBtn");
                        // submitBtn.prop('disabled', true).html(
                        //     '<i class="fas fa-spinner fa-spin me-2"></i>Saving...');

                        // Prepare form data
                        const formData = new FormData(this);

                        // Add action type (create or update)
                        const gisid = $("#building_gisid").val();
                        formData.append('action', gisid ? 'update' : 'create');
                        formData.append('_token', $('input[name="_token"]').val());
                        alert("hi");
                        // Send AJAX request
                        $.ajax({
                            url: '/buildings/save',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                if (response.success) {
                                    // Show success message
                                    showFlashMessage('Building data saved successfully!',
                                        'success');
                                    if (response) {
                                        polygonDatas = response.polygonDatas ??
                                            polygonDatas;


                                        reloadAllSources();
                                    }
                                    setTimeout(() => {
                                        const modal = bootstrap.Modal.getInstance(
                                            document.getElementById(
                                                'buildingDataModal'));
                                        if (modal) modal.hide();
                                    }, 1500);
                                } else {
                                    showFlashMessage(response.message ||
                                        'Error saving datas', 'error');
                                }
                            },
                            error: function(xhr) {
                                let errorMessage = 'An error occurred while saving.';
                                if (xhr.status === 422) {
                                    // Validation errors
                                    const errors = xhr.responseJSON.errors;
                                    if (errors) {
                                        // Display validation errors
                                        $.each(errors, function(field, messages) {
                                            const errorContainer = $(
                                                `#${field}_error`);
                                            if (errorContainer.length) {
                                                errorContainer.html(messages[0]);
                                                $(`#${field}`).addClass(
                                                    'is-invalid');
                                            }
                                        });
                                        errorMessage = 'Please fix the validation errors.';
                                    }
                                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }

                                showNotification(errorMessage, 'error');
                            },
                            complete: function() {
                                // Reset button
                                submitBtn.prop('disabled', false).html(
                                    '<i class="fas fa-save me-2"></i>Save Building Data'
                                );
                            }
                        });
                    });
                });


                // ─── NONE MODE ───
                function setNoneMode() {
                    currentMode = 'none';
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
                    showToast('👁️ View Mode: Click on features to see details', 2000);
                }

                // ─── EDIT POLYGON MODE ───
                function setEditPolygonMode() {
                    currentMode = 'edit';
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
                            $('.edit-dropdown-item').removeClass('active');
                            $('.edit-dropdown').removeClass('show');
                            $('#editToggleBtn').removeClass('active-edit');
                            reloadAllSources();
                            disableAllInteractions();
                            clearDrawInteraction();
                            selectedFeatureForEdit.setStyle(null);
                            selectedFeatureForEdit = null;
                            originalGeometry = null;
                            hideEditControls();

                            $('.edit-dropdown-item').removeClass('active');
                            $('.edit-dropdown').removeClass('show');
                            $('#editToggleBtn').removeClass('active-edit');

                            showToast('✅ Polygon updated!', 2000);
                            setNoneMode();
                        },
                        error: function(xhr) {
                            console.error('Update error:', xhr);
                            Swal.fire('Error', 'Failed to update polygon', 'error');
                            cancelEdit();
                        }
                    });
                }

                // ─── MOVE POLYGON MODE ───
                function setMovePolygonMode() {
                    currentMode = 'move';
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
                            $('.edit-dropdown-item').removeClass('active');
                            $('.edit-dropdown').removeClass('show');
                            $('#editToggleBtn').removeClass('active-edit');
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

                            $('.edit-dropdown-item').removeClass('active');
                            $('.edit-dropdown').removeClass('show');
                            $('#editToggleBtn').removeClass('active-edit');

                            showToast('✅ Polygon moved!', 2000);
                            setNoneMode();
                        },
                        error: function(xhr) {
                            console.error('Move error:', xhr);
                            Swal.fire('Error', 'Failed to move polygon', 'error');
                            cancelMove();
                        }
                    });
                }

                // ─── SPLIT MODE ───
                function setSplitMode() {
                    currentMode = 'split';
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
                    showToast('✂️ Split Mode: Click a polygon to select it', 2000);
                }

                // ─── Split button ───
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

                // ─── Perform split ───
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
                                console.error('Split error:', xhr);
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
                    currentDrawType = type;
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
                            $('.edit-dropdown-item').removeClass('active');
                            $('.edit-dropdown').removeClass('show');
                            $('#editToggleBtn').removeClass('active-edit');
                            reloadAllSources();
                            disableAllInteractions();
                            clearDrawInteraction();
                            Swal.fire('Success', 'Feature saved successfully', 'success');
                            setNoneMode();
                        },
                        error: function(xhr) {
                            console.log(xhr.responseText);
                            Swal.fire('Error', 'Error saving feature', 'error');
                            setNoneMode();
                        }
                    });
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
                                searchText: `${poly.gisid} ${poly.sqfeet} building polygon`
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
                                id: pd.gisid,
                                point_gisid: pd.point_gisid,
                                type: 'pointdata',
                                title: `GIS ID: ${pd.gisid}`,
                                subtitle: `Assessment: ${pd.assessment} | Point GIS ID: ${pd.point_gisid}`,
                                coordinates: JSON.parse(pd.coordinates),
                                geometryType: 'point',
                                assessment: pd.assessment,
                                searchText: `${pd.gisid} ${pd.assessment} ${pd.point_gisid} assessment point`
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
                        return coords; // already in map projection (EPSG:3857)
                    } catch (e) {
                        console.error('Coord parse error for gisid:', gisid, e);
                        return null;
                    }
                }

                // ─── LOCATION & ROUTING ───
                function getCurrentLocation(callback) {
                    if (!navigator.geolocation) {
                        Swal.fire('Error', 'Geolocation is not supported by your browser', 'error');
                        callback(null);
                        return false;
                    }

                    showToast('📍 Getting your location...', 2000);

                    if (currentLocation) {
                        callback(currentLocation);
                        return true;
                    }

                    navigator.geolocation.getCurrentPosition(
                        function(pos) {
                            const loc = {
                                lon: pos.coords.longitude,
                                lat: pos.coords.latitude
                            };
                            currentLocation = loc;
                            callback(loc);
                        },
                        function(error) {
                            let msg = 'Unable to get your location. ';
                            switch (error.code) {
                                case error.PERMISSION_DENIED:
                                    msg += 'Please enable location permissions in your browser.';
                                    break;
                                case error.POSITION_UNAVAILABLE:
                                    msg += 'GPS signal is weak. Try moving to an open area.';
                                    break;
                                case error.TIMEOUT:
                                    msg += 'Request timed out. Please try again.';
                                    break;
                                default:
                                    msg += 'An unknown error occurred.';
                            }
                            Swal.fire('Location Error', msg, 'error');
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
                            Swal.fire('Error', 'No route found between these points', 'error');
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
                    }).catch(err => {
                        console.error('Route error:', err);
                        Swal.fire('Error', 'Failed to calculate route. Please check your internet connection.',
                            'error');
                    });
                }

                function getDirectionToFeature(feature) {
                    getCurrentLocation(function(loc) {
                        if (!loc) {
                            if (navigator.geolocation) {
                                showToast('🔄 Retrying location...', 2000);
                                navigator.geolocation.getCurrentPosition(
                                    function(pos) {
                                        const newLoc = {
                                            lon: pos.coords.longitude,
                                            lat: pos.coords.latitude
                                        };
                                        currentLocation = newLoc;
                                        calculateDirection(newLoc, feature);
                                    },
                                    function() {
                                        Swal.fire('Location Error',
                                            'Could not get your location. Please enable GPS and try again.',
                                            'error');
                                    }, {
                                        enableHighAccuracy: true,
                                        timeout: 10000
                                    }
                                );
                            }
                            return;
                        }
                        calculateDirection(loc, feature);
                    });
                }


                // ─── FIX: calculateDirection — always use point coords by gisid ───
                function calculateDirection(loc, feature) {
                    if (!loc) return;

                    // Always resolve via matching point in the points array
                    const coords = getCoordsByGisId(feature.id);
                    if (!coords) {
                        Swal.fire('Error', `No point coordinates found for GIS ID: ${feature.id}`, 'error');
                        return;
                    }

                    // coords are in EPSG:3857 — convert to WGS84 lon/lat for OSRM
                    const lonLat = ol.proj.toLonLat(coords);
                    const destLon = lonLat[0];
                    const destLat = lonLat[1];

                    if (destLon < -180 || destLon > 180 || destLat < -90 || destLat > 90) {
                        Swal.fire('Error', 'Converted coordinates are out of valid range. Check your data projection.',
                            'error');
                        return;
                    }

                    console.log(`Routing to GIS ID ${feature.id}: lon=${destLon}, lat=${destLat}`);
                    getRoute(loc.lon, loc.lat, destLon, destLat);
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
                                <button type="button" class="btn btn-sm flex-fill search-tab-btn active" data-tab="quick">
                                    Quick Search
                                </button>
                                <button type="button" class="btn btn-sm flex-fill search-tab-btn" data-tab="filter">
                                    Filter
                                </button>
                            </div>

                            <!-- Quick Search Tab -->
                            <div class="search-tab-pane" id="quickSearchTab">
                                <div class="p-3">
                                    <input type="text" id="gisSearchInput" class="form-control" placeholder="Search by GIS ID or Assessment...">
                                </div>
                                <div id="searchResults" class="search-results-container"></div>
                            </div>

                            <!-- Filter Tab -->
                            <div class="search-tab-pane" id="filterTab" style="display:none;">
                                <div class="p-3 pb-2">
                                    <input type="text" id="filterAssessment" class="form-control mb-2" placeholder="Assessment">
                                    <input type="text" id="filterOldAssessment" class="form-control mb-2" placeholder="Old Assessment">
                                    <input type="text" id="filterOwnerName" class="form-control mb-2" placeholder="Owner Name">
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
                            <div class="edit-name">None (View Only)</div>
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

                // Edit dropdown items
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

                // Layer toggle
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

                // Location toggle
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
                        showToast('📍 Tracking mode enabled - map will follow you', 2000);
                    } else {
                        trackActive = false;
                        if (!liveActive) {
                            stopWatching();
                            clearLiveMarker();
                        }
                        showToast('📍 Tracking mode disabled', 2000);
                    }
                    syncLocationUI();
                    $('#locationDropdown').removeClass('show');
                });

                $(document).on('click', '#clearRouteItem', function(e) {
                    e.stopPropagation();
                    clearRoute();
                    $('#locationDropdown').removeClass('show');
                });

                // Search toggle
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
                                `${item.title} | Assessment: ${item.assessment}` : item.title;
                            const displaySubtitle = item.type === 'pointdata' ?
                                `Point GIS ID: ${item.point_gisid || 'N/A'}` : item.subtitle;
                            const icon = item.geometryType === 'point' ? 'geo-alt' :
                                item.geometryType === 'polygon' ? 'pentagon' : 'vector-pen';

                            const editBtn = item.type === 'pointdata' ?
                                `<button class="btn btn-sm btn-warning edit-btn" data-id="${item.id}"><i class="bi bi-pencil"></i> Edit</button>` :
                                '';

                            html += `
                            <div class="search-result-item" data-id="${item.id}" data-type="${item.type}">
                                <div class="search-result-title"><i class="bi bi-${icon} me-2"></i>${displayTitle}</div>
                                <div class="search-result-subtitle">${displaySubtitle}</div>
                                <div class="mt-2 d-flex gap-2">
                                    <button class="btn btn-sm btn-success zoom-btn" data-id="${item.id}" data-type="${item.type}">Zoom</button>
                                    <button class="btn btn-sm btn-primary direction-btn" data-id="${item.id}" data-type="${item.type}">Direction</button>
                                    ${editBtn}
                                </div>
                            </div>`;
                        });
                    }
                    $('#searchResults').html(html);
                });

                $(document).on('click', '.zoom-btn', function(e) {
                    e.stopPropagation();
                    const id = $(this).data('id'),
                        type = $(this).data('type');
                    if (searchIndex.find(f => f.id == id && f.type === type)) {
                        zoomToFeature(id);
                        $('#searchDropdown').removeClass('show');
                        $('#searchToggleBtn').removeClass('active-search');
                        $('#gisSearchInput').val('');
                        $('#searchResults').html('');
                    }
                });

                $(document).on('click', '.direction-btn', function(e) {
                    e.stopPropagation();
                    const id = $(this).data('id'),
                        type = $(this).data('type');
                    const feature = searchIndex.find(f => f.id == id && f.type === type);
                    if (feature) {
                        getDirectionToFeature(feature);
                        $('#searchDropdown').removeClass('show');
                        $('#searchToggleBtn').removeClass('active-search');
                        $('#gisSearchInput').val('');
                        $('#searchResults').html('');
                    }
                });

                // Close dropdowns on outside click
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

                // Fullscreen
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

                // ─── DELETE MODAL HANDLERS ───
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
                    $btn.html(
                        '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Deleting…'
                    ).prop('disabled', true);

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
                            deleteModal.hide();
                            $btn.html('<i class="bi bi-trash3 me-1"></i>Delete').prop('disabled',
                                false);

                            // ✅ FIXED: Properly update from response data
                            polygons = response.data.polygons ?? polygons;
                            points = response.data.points ?? points;
                            lines = response.data.lines ?? lines;

                            reloadAllSources();
                            disableAllInteractions();
                            setNoneMode();

                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: `${type.charAt(0).toUpperCase() + type.slice(1)} (GIS ID: ${gisid}) deleted successfully.`,
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

                // ─── INIT ───
                buildSearchIndex();
                updateLayerUI();
                setNoneMode();
                syncLocationUI();

                if (!droneImageURL || droneImageURL === "{{ asset('') }}") {
                    droneLayer.setVisible(false);
                }

                console.log('✅ GIS Dashboard ready — Simplified and fixed!');



                function fillFields(record) {
                    $('#old_assessment').val(record.old_assessment || '');
                    $('#zone').val(record.zone || '');
                    $('#owner_name').val(record.owner_name || '');
                    $('#phone_number').val(record.phone_number || '');
                    $('#old_door_no').val(record.old_door_no || '');
                    $('#new_door_no').val(record.new_door_no || '');
                    $('#half_year_tax').val(record.half_year_tax || '');

                    $('#watertax_no').val(record.watertax_no || '');
                    $('#old_watertax_no').val(record.old_watertax_no || '');

                    $('#ugd_no').val(record.ugd_no || '');
                    $('#old_ugd_no').val(record.old_ugd_no || '');

                    $('#pt_number').val(record.pt_number || '');
                    $('#old_pt_number').val(record.old_pt_number || '');
                }

                function clearFields() {
                    $('#old_assessment,#zone,#owner_name,#phone_number,#old_door_no,#new_door_no,#half_year_tax,#watertax_no,#old_watertax_no,#ugd_no,#old_ugd_no,#pt_number,#old_pt_number')
                        .val('');
                }

                // Assessment Search
                $('#assessment').on('input', function() {
                    const value = $(this).val().trim();

                    if (value.length < 3) {
                        clearFields();
                        return;
                    }

                    const record = misData.find(item =>
                        item.assessment?.startsWith(value)
                    );

                    if (record) {
                        fillFields(record);
                    } else {
                        clearFields();
                    }
                });

                // Old Assessment Search
                $('#old_assessment').on('input', function() {
                    const value = $(this).val().trim();

                    if (value.length < 3) {
                        clearFields();
                        return;
                    }

                    const record = misData.find(item =>
                        item.old_assessment?.startsWith(value)
                    );

                    if (record) {
                        fillFields(record);

                        // Replace assessment with matching assessment number
                        $('#assessment').val(record.assessment || '');
                    } else {
                        clearFields();
                    }
                });


                $('#addProfessionalBtn').click(function() {

                    let html = `
                        <div class="card mb-3 professional-card">
                            <div class="card-header d-flex justify-content-between">

                                <strong>Professional Tax #${ptIndex + 1}</strong>

                                <button type="button"
                                    class="btn btn-danger btn-sm removeProfessional">
                                    Remove
                                </button>

                            </div>

                            <div class="card-body">

                                <div class="row g-3">

                                    <div class="col-md-4">
                                        <label>PT Number</label>
                                        <input class="form-control"
                                            name="professional[${ptIndex}][pt_number]">
                                    </div>

                                    <div class="col-md-4">
                                        <label>Old PT Number</label>
                                        <input class="form-control"
                                            name="professional[${ptIndex}][old_pt_number]">
                                    </div>

                                    <div class="col-md-4">
                                        <label>Establishment Name</label>
                                        <input class="form-control"
                                            name="professional[${ptIndex}][establishment_name]">
                                    </div>

                                    <div class="col-md-4">
                                        <label>Profession Type</label>
                                        <input class="form-control"
                                            name="professional[${ptIndex}][profession_type]">
                                    </div>

                                    <div class="col-md-4">
                                        <label>Employee Count</label>
                                        <input type="number"
                                            class="form-control"
                                            name="professional[${ptIndex}][employee_count]">
                                    </div>

                                    <div class="col-md-4">
                                        <label>Half Year Tax</label>
                                        <input type="number"
                                            class="form-control"
                                            name="professional[${ptIndex}][half_year_tax]">
                                    </div>

                                    <div class="col-md-12">
                                        <label>Remarks</label>
                                        <textarea class="form-control"
                                            name="professional[${ptIndex}][pt_remarks]"></textarea>
                                    </div>

                                </div>

                            </div>
                        </div>
                        `;

                    $('#professionalContainer').append(html);

                    ptIndex++;
                });

                $(document).on('click', '.removeProfessional', function() {
                    $(this).closest('.professional-card').remove();
                });
                $(document).on('click', '.edit-btn', function(e) {
                    e.stopPropagation();
                    const id = $(this).data('id');
                    loadPointDataForEdit(id);
                    $('#searchDropdown').removeClass('show');
                    $('#searchToggleBtn').removeClass('active-search');
                });

                function loadPointDataForEdit(id) {
                    $.ajax({
                        url: `/point-data/${id}`,
                        method: 'GET',
                        success: function(res) {
                            if (!res.success) {
                                showFlashMessage(res.message, 'error');
                                return;
                            }

                            const pd = res.point_data,
                                wt = res.water_tax,
                                ugd = res.ugd_tax,
                                pts = res.professional;

                            const modal = new bootstrap.Modal(document.getElementById('pointDetailsModal'));
                            modal.show();
                            $('#pointDetailsTabs button:first').tab('show');

                            // mark edit mode
                            $('#pointDetailsForm').attr('data-edit-id', pd.id);
                            $('#point_gisid').val(pd.point_gisid);

                            // basic
                            $('#assessment_type').val(pd.assessment_type);
                            $('#assessment').val(pd.assessment);
                            $('#old_assessment').val(pd.old_assessment);
                            $('#owner_name').val(pd.owner_name);
                            $('#present_owner_name').val(pd.present_owner_name);
                            $('#phone_number').val(pd.phone_number);
                            $('#old_door_no').val(pd.old_door_no);
                            $('#new_door_no').val(pd.new_door_no);
                            $('#aadhar_no').val(pd.aadhar_no);
                            $('#ration_no').val(pd.ration_no);
                            $('#floor').val(pd.floor);
                            $('#number_persons').val(pd.no_of_persons);
                            $('#bill_usage').val(pd.bill_usage);
                            $('#eb').val(pd.eb);
                            $('#remarks').val(pd.remarks);

                            // water
                            if (wt) {
                                $('#watertax_no').val(wt.watertax_no);
                                $('#old_watertax_no').val(wt.old_watertax_no);
                                $('#water_usage').val(wt.usage);
                                $('#water_DBC_type').val(wt.DBC_type);
                                $('#water_slab_description').val(wt.slab_description);
                            }

                            // ugd
                            if (ugd) {
                                $('#ugd_no').val(ugd.ugd_no);
                                $('#old_ugd_no').val(ugd.old_ugd_no);
                                $('#ugd_usage').val(ugd.usage);
                                $('#ugd_DBC_type').val(ugd.DBC_type);
                                $('#ugd_slab_description').val(ugd.slab_description);
                            }

                            // professional tax cards
                            $('#professionalContainer').empty();
                            $('#removedProfessionalWrap').remove();
                            ptIndex = 0;
                            (pts || []).forEach(p => addProfessionalCard(p));
                        },
                        error: function() {
                            showFlashMessage('Failed to load record for editing.', 'error');
                        }
                    });
                }

                function addProfessionalCard(data = {}) {
                    const idx = ptIndex;
                    const html = `
        <div class="card mb-3 professional-card" data-index="${idx}">
            <div class="card-header d-flex justify-content-between">
                <strong>Professional Tax #${idx + 1}</strong>
                <button type="button" class="btn btn-danger btn-sm removeProfessional">Remove</button>
            </div>
            <div class="card-body">
                <input type="hidden" name="professional[${idx}][id]" value="${data.id || ''}">
                <div class="row g-3">
                    <div class="col-md-4"><label>PT Number</label>
                        <input class="form-control" name="professional[${idx}][pt_number]" value="${data.pt_number || ''}"></div>
                    <div class="col-md-4"><label>Old PT Number</label>
                        <input class="form-control" name="professional[${idx}][old_pt_number]" value="${data.old_pt_number || ''}"></div>
                    <div class="col-md-4"><label>Establishment Name</label>
                        <input class="form-control" name="professional[${idx}][establishment_name]" value="${data.establishment_name || ''}"></div>
                    <div class="col-md-4"><label>Profession Type</label>
                        <input class="form-control" name="professional[${idx}][profession_type]" value="${data.profession_type || ''}"></div>
                    <div class="col-md-4"><label>Employee Count</label>
                        <input type="number" class="form-control" name="professional[${idx}][employee_count]" value="${data.employee_count || ''}"></div>
                    <div class="col-md-4"><label>Half Year Tax</label>
                        <input type="number" class="form-control" name="professional[${idx}][half_year_tax]" value="${data.half_year_tax || ''}"></div>
                    <div class="col-md-12"><label>Remarks</label>
                        <textarea class="form-control" name="professional[${idx}][pt_remarks]">${data.remarks || ''}</textarea></div>
                </div>
            </div>
        </div>`;
                    $('#professionalContainer').append(html);
                    ptIndex++;
                }

                $('#addProfessionalBtn').off('click').on('click', function() {
                    addProfessionalCard();
                });

                $(document).on('click', '.removeProfessional', function() {
                    const $card = $(this).closest('.professional-card');
                    const existingId = $card.find('input[name$="[id]"]').val();
                    if (existingId) {
                        if (!$('#removedProfessionalWrap').length) {
                            $('#pointDetailsForm').append('<div id="removedProfessionalWrap"></div>');
                        }
                        $('#removedProfessionalWrap').append(
                            `<input type="hidden" name="removed_professional_ids[]" value="${existingId}">`
                        );
                    }
                    $card.remove();
                });
                $('#applyFilterBtn').on('click', function() {
                    $.get('/point-data/filter', {
                        assessment: $('#filterAssessment').val(),
                        old_assessment: $('#filterOldAssessment').val(),
                        owner_name: $('#filterOwnerName').val()
                    }, function(res) {
                        let html = '';
                        (res.data || []).forEach(pd => {
                            html += `
                <div class="search-result-item">
                    <div class="search-result-title">${pd.owner_name} — ${pd.assessment}</div>
                    <div class="search-result-subtitle">GIS ID: ${pd.point_gisid}</div>
                    <button class="btn btn-sm btn-warning edit-btn mt-1" data-id="${pd.id}">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                </div>`;
                        });
                        $('#filterResults').html(html ||
                            '<div class="p-2 text-muted">No matches</div>');
                    });
                });
                $(document).on('click', '.search-tab-btn', function() {
                    $('.search-tab-btn').removeClass('active');
                    $(this).addClass('active');
                    const tab = $(this).data('tab');
                    $('#quickSearchTab').toggle(tab === 'quick');
                    $('#filterTab').toggle(tab === 'filter');
                });
                if (!$(e.target).closest('.custom-search-switcher').length) {
                    $('#searchDropdown').removeClass('show');
                    $('#searchToggleBtn').removeClass('active-search');
                }
            });
        </script>
    @endpush

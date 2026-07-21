@extends('layouts.office')

@section('title', 'Executive GIS Dashboard')
@section('page_title', 'Executive GIS Dashboard')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@latest/ol.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cesium.com/downloads/cesiumjs/releases/1.127/Build/Cesium/Widgets/widgets.css" rel="stylesheet" />
    <style>
        /* Map Container Styles */
        #map {
            width: 100%;
            height: 800px;
            transition: all 0.3s ease;
            position: relative;
        }

        /* Map Card Styles */
        .map-card {
            position: relative;
        }

        /* Fullscreen Styles */
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

        /* Hide all controls in fullscreen mode */
        .map-card.fullscreen-mode .custom-layer-switcher,
        .map-card.fullscreen-mode .custom-location-switcher,
        .map-card.fullscreen-mode .custom-search-switcher,
        .map-card.fullscreen-mode .custom-label-toggle,
        .map-card.fullscreen-mode .custom-legend-toggle,
        .map-card.fullscreen-mode .custom-3d-toggle,
        .map-card.fullscreen-mode .fullscreen-btn {
            display: none !important;
        }

        /* Show only fullscreen exit button in fullscreen mode */
        .map-card.fullscreen-mode .fullscreen-btn-exit {
            display: block !important;
        }

        /* Common Control Styles - All inside map */
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
            top: 128px;
            z-index: 1000;
        }

        .custom-label-toggle {
            position: absolute;
            right: 30px;
            top: 182px;
            z-index: 1000;
        }

        .custom-legend-toggle {
            position: absolute;
            right: 30px;
            top: 236px;
            z-index: 1000;
        }

        .custom-3d-toggle {
            position: absolute;
            right: 30px;
            top: 290px;
            z-index: 1000;
        }

        /* Fullscreen Button - Inside map */
        .fullscreen-btn {
            position: absolute;
            right: 30px;
            bottom: 30px;
            z-index: 1000;
            background: white;
            border-radius: 8px;
            padding: 10px 12px;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
            font-size: 18px;
            transition: all 0.2s;
        }

        .fullscreen-btn:hover {
            background: #f0f0f0;
            transform: scale(1.05);
        }

        /* Fullscreen Exit Button - Hidden by default */
        .fullscreen-btn-exit {
            display: none;
            position: absolute;
            right: 30px;
            bottom: 30px;
            z-index: 1000;
            background: white;
            border-radius: 8px;
            padding: 10px 12px;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
            font-size: 18px;
            transition: all 0.2s;
        }

        .fullscreen-btn-exit:hover {
            background: #f0f0f0;
            transform: scale(1.05);
        }

        /* Toggle Button Styles */
        .layer-toggle-btn,
        .location-toggle-btn,
        .search-toggle-btn,
        .label-toggle-btn,
        .legend-toggle-btn,
        .threed-toggle-btn {
            background: white;
            border-radius: 8px;
            padding: 10px 12px;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
            font-size: 20px;
            transition: all 0.2s;
        }

        .layer-toggle-btn:hover,
        .location-toggle-btn:hover,
        .search-toggle-btn:hover,
        .label-toggle-btn:hover,
        .legend-toggle-btn:hover,
        .threed-toggle-btn:hover {
            background: #f0f0f0;
            transform: scale(1.05);
        }

        .label-toggle-btn.active-label {
            color: #0d6efd;
        }

        .threed-toggle-btn.active-3d {
            color: #0d6efd;
        }

        /* Dropdown Styles */
        .layer-dropdown,
        .location-dropdown,
        .search-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 52px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            padding: 8px 0;
            max-height: 500px;
            overflow-y: auto;
        }

        .layer-dropdown {
            width: 260px;
        }

        .location-dropdown {
            width: 240px;
        }

        .search-dropdown {
            width: 340px;
        }

        .layer-dropdown.active,
        .location-dropdown.active,
        .search-dropdown.active {
            display: block;
        }

        .dropdown-header {
            padding: 8px 16px;
            font-weight: 600;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .dropdown-divider {
            height: 1px;
            background: #e9ecef;
            margin: 4px 0;
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
            padding: 2px 8px;
            border-radius: 12px;
            background: #e9ecef;
            color: #666;
        }

        .location-item-badge.active {
            background: #0d6efd;
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
            max-height: 200px;
            overflow-y: auto;
        }

        .location-toast {
            display: none;
            position: absolute;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            z-index: 1000;
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
    <div class="map-card" id="mapCard">
        <div class="map-header">
            <h5 class="map-title"><i class="bi bi-geo-alt-fill text-primary me-2"></i>Executive GIS Dashboard</h5>
            <span class="badge bg-primary" id="activeLayerBadge">OpenStreetMap</span>
        </div>
        <div id="map"></div>
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

            // ─── BUILDING DATA FOR USAGE COLORS ───
            let buildingData = @json($buildingData ?? [], JSON_HEX_TAG);
            let allBuildings = buildingData.buildings || [];
            let usageCounts = buildingData.usage_counts || {};

            // ─── USAGE COLOR MAP ───
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

            let imageExtentRaw = [{{ $ward->extent_left ?? 0 }}, {{ $ward->extent_bottom ?? 0 }},
                {{ $ward->extent_right ?? 0 }}, {{ $ward->extent_top ?? 0 }}
            ];

            // ─── CHECK COORDINATE SYSTEM ───
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

            // ─── SOURCES ───
            const polygonSource = new ol.source.Vector();
            const lineSource = new ol.source.Vector();
            const pointSource = new ol.source.Vector();

            function createPolygonStyle(feature) {
                const gisid = feature.get('gisid');
                const sqft = feature.get('sqfeet') || '0';
                const polygonData = polygonDatas.find(d => d.gisid == gisid);
                const color = polygonData ? 'red' : 'blue';
                const centerPoint = feature.getGeometry().getInteriorPoint();

                const styles = [
                    new ol.style.Style({
                        stroke: new ol.style.Stroke({
                            color: color,
                            width: 4,
                            lineJoin: 'round',
                            lineCap: 'round'
                        }),
                    })
                ];

                styles.push(new ol.style.Style({
                    geometry: centerPoint,
                    text: new ol.style.Text({
                        text: gisid + ' GISID\n' + sqft + ' SQFT',
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
                }));

                return styles;
            }

            function loadPolygonSource() {
                polygonSource.clear();

                polygons.forEach(poly => {
                    try {
                        let coords = JSON.parse(poly.coordinates);

                        const feature = new ol.Feature({
                            geometry: new ol.geom.Polygon([coords]),
                            gisid: poly.gisid,
                            type: 'polygon',
                            sqfeet: poly.sqfeet || '0',
                            orginalData: poly
                        });

                        feature.setId(poly.gisid);
                        polygonSource.addFeature(feature);

                    } catch (e) {
                        console.error('polygon parse error:', e);
                    }
                });
            }
            loadPolygonSource();

            const polygonLayer = new ol.layer.Vector({
                source: polygonSource,
                style: createPolygonStyle,
                visible: true,
                title: 'Polygons'
            });

            // ─── CREATE MAP ───
            const map = new ol.Map({
                target: 'map',
                layers: [osmLayer, satelliteLayer, droneLayer, polygonLayer],
                view: new ol.View({
                    center: ol.extent.getCenter(imageExtent),
                    zoom: 18
                })
            });

            // ─── GET MAP CONTAINER ───
            const $mapContainer = $('#map');

            // ─── ADD ALL CONTROLS INSIDE MAP ───

            // 1. LAYER SWITCHER
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
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-header">Vector Layers</div>
                        <div class="layer-dropdown-item" data-layer-type="vector" data-layer="Polygons">
                            <div class="layer-icon"><i class="bi bi-pentagon"></i></div>
                            <div class="layer-name">Polygons</div>
                            <div class="layer-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                        <div class="layer-dropdown-item" data-layer-type="vector" data-layer="Lines">
                            <div class="layer-icon"><i class="bi bi-vector-pen"></i></div>
                            <div class="layer-name">Lines</div>
                            <div class="layer-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                        <div class="layer-dropdown-item" data-layer-type="vector" data-layer="Points">
                            <div class="layer-icon"><i class="bi bi-geo-alt"></i></div>
                            <div class="layer-name">Points</div>
                            <div class="layer-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                        <div class="layer-dropdown-item active" data-layer-type="vector" data-layer="Buildings">
                            <div class="layer-icon"><i class="bi bi-building"></i></div>
                            <div class="layer-name">Buildings</div>
                            <div class="layer-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                    </div>
                </div>
            `);

            // 2. LABEL TOGGLE
            $mapContainer.append(`
                <div class="custom-label-toggle">
                    <div class="label-toggle-btn active-label" id="labelToggleBtn" title="Toggle Labels">
                        <i class="bi bi-fonts"></i>
                    </div>
                </div>
            `);

            // 3. LEGEND TOGGLE
            $mapContainer.append(`
                <div class="custom-legend-toggle">
                    <div class="legend-toggle-btn" id="legendToggleBtn" title="Toggle Infrastructure Legend">
                        <i class="bi bi-list-ul"></i>
                    </div>
                </div>
            `);

            // 4. LOCATION SWITCHER
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

            // 5. SEARCH SWITCHER
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

            // 6. 3D TOGGLE
            $mapContainer.append(`
                <div class="custom-3d-toggle">
                    <div class="threed-toggle-btn" id="threeDToggleBtn" title="Toggle 3D View">
                        <i class="bi bi-box"></i>
                    </div>
                </div>
            `);

            // 7. FULLSCREEN BUTTON (Inside map)
            $mapContainer.append(`
                <div class="fullscreen-btn" id="fullscreenBtn">
                    <i class="bi bi-arrows-fullscreen"></i>
                </div>
                <div class="fullscreen-btn-exit" id="fullscreenExitBtn" style="display:none;">
                    <i class="bi bi-fullscreen-exit"></i>
                </div>
            `);

            // ─── TOGGLE DROPDOWN FUNCTIONALITY ───
            $(document).on('click', '.layer-toggle-btn', function(e) {
                e.stopPropagation();
                $('.layer-dropdown').toggleClass('active');
                $('.location-dropdown').removeClass('active');
                $('.search-dropdown').removeClass('active');
            });

            $(document).on('click', '.location-toggle-btn', function(e) {
                e.stopPropagation();
                $('.location-dropdown').toggleClass('active');
                $('.layer-dropdown').removeClass('active');
                $('.search-dropdown').removeClass('active');
            });

            $(document).on('click', '.search-toggle-btn', function(e) {
                e.stopPropagation();
                $('.search-dropdown').toggleClass('active');
                $('.layer-dropdown').removeClass('active');
                $('.location-dropdown').removeClass('active');
            });

            // Close dropdowns when clicking outside
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
            });

            // ─── FULLSCREEN TOGGLE ───
            let isFullscreen = false;

            // Enter Fullscreen
            $(document).on('click', '#fullscreenBtn', function() {
                const $card = $('#mapCard');
                const $container = $('#map');
                const $btn = $(this);
                const $exitBtn = $('#fullscreenExitBtn');

                $card.addClass('fullscreen-mode');
                $container.addClass('fullscreen');
                $btn.hide();
                $exitBtn.show();
                isFullscreen = true;

                setTimeout(function() {
                    map.updateSize();
                    if (window.is3DActive && window.cesiumViewer) {
                        window.cesiumViewer.resize();
                    }
                }, 150);
            });

            // Exit Fullscreen
            $(document).on('click', '#fullscreenExitBtn', function() {
                const $card = $('#mapCard');
                const $container = $('#map');
                const $btn = $('#fullscreenBtn');
                const $exitBtn = $(this);

                $card.removeClass('fullscreen-mode');
                $container.removeClass('fullscreen');
                $exitBtn.hide();
                $btn.show();
                isFullscreen = false;

                setTimeout(function() {
                    map.updateSize();
                    if (window.is3DActive && window.cesiumViewer) {
                        window.cesiumViewer.resize();
                    }
                }, 150);
            });

            // Escape key to exit fullscreen
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && isFullscreen) {
                    $('#fullscreenExitBtn').click();
                }
            });

            console.log('GIS Dashboard initialized successfully!');
            console.log('All controls are inside the map container');
        });
    </script>
@endpush

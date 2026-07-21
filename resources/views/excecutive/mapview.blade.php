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
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .map-header {
            padding: 16px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .map-title {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #1a1a2e;
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

        .map-card.fullscreen-mode .map-header {
            display: none;
        }

        .map-card.fullscreen-mode #map {
            height: calc(100vh - 5px);
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
            border: none;
            color: #333;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
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

        .location-toggle-btn.active-location {
            color: #0d6efd;
        }

        .location-toggle-btn.tracking {
            color: #dc3545;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
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
            min-width: 200px;
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

        /* OL Override */
        .ol-viewport {
            border-radius: 0 0 12px 12px;
        }

        .search-result-item .result-detail {
            font-size: 12px;
            color: #666;
        }

        .direction-controls {
            position: absolute;
            bottom: 100px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            background: white;
            border-radius: 12px;
            padding: 12px 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
            display: none;
            min-width: 300px;
        }

        .direction-controls.active {
            display: block;
        }

        .direction-controls .route-info {
            font-size: 13px;
            color: #333;
            margin-bottom: 8px;
        }

        .direction-controls .btn-close-route {
            position: absolute;
            top: 8px;
            right: 8px;
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
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

            // ─── LOCATION TRACKING VARIABLES ───
            let watchId = null;
            let currentPosition = null;
            let currentPositionFeature = null;
            let trackingMarker = null;
            let routeLine = null;
            let routePoints = [];
            let isTracking = false;
            let isLiveLocation = false;
            let destinationFeature = null;
            let routeDirections = null;

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
                        fill: new ol.style.Fill({
                            color: 'rgba(255, 0, 0, 0.1)'
                        })
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

            function createLineStyle(feature) {
                return new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#ff0000',
                        width: 3
                    })
                });
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
                            assessment: poly.assessment || '',
                            old_assessment: poly.old_assessment || '',
                            owner_name: poly.owner_name || '',
                            phone_number: poly.phone_number || '',
                            orginalData: poly
                        });

                        feature.setId(poly.gisid);
                        polygonSource.addFeature(feature);

                    } catch (e) {
                        console.error('polygon parse error:', e);
                    }
                });
            }

            function loadLineSource() {
                lineSource.clear();

                lines.forEach(line => {
                    try {
                        let coords = JSON.parse(line.coordinates);

                        const feature = new ol.Feature({
                            geometry: new ol.geom.MultiLineString(coords),
                            gisid: line.gisid,
                            type: 'multLineString',
                            road_name: line.road_name || '0',
                            orginalData: line
                        });

                        feature.setId(line.gisid);
                        lineSource.addFeature(feature);

                    } catch (e) {
                        console.error('line parse error:', e);
                    }
                });
            }

            function loadPointSource() {
                pointSource.clear();

                points.forEach(point => {
                    try {
                        let coords = JSON.parse(point.coordinates);
                        const lonLat = [coords[0], coords[1]];
                        const projected = ol.proj.fromLonLat(lonLat);

                        const feature = new ol.Feature({
                            geometry: new ol.geom.Point(projected),
                            gisid: point.gisid,
                            type: 'point',
                            orginalData: point
                        });

                        feature.setId(point.gisid);
                        pointSource.addFeature(feature);

                    } catch (e) {
                        console.error('point parse error:', e);
                    }
                });
            }

            loadPolygonSource();
            loadLineSource();
            loadPointSource();

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

            const pointLayer = new ol.layer.Vector({
                source: pointSource,
                visible: true,
                title: 'Points'
            });

            // ─── CREATE MAP ───
            const map = new ol.Map({
                target: 'map',
                layers: [osmLayer, satelliteLayer, droneLayer, polygonLayer, lineLayer, pointLayer],
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
                    <button class="layer-toggle-btn"><i class="bi bi-layers"></i></button>
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
                        <div class="layer-dropdown-item active" data-layer-type="vector" data-layer="Points">
                            <div class="layer-icon"><i class="bi bi-geo-alt"></i></div>
                            <div class="layer-name">Points</div>
                            <div class="layer-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                    </div>
                </div>
            `);

            // 2. LABEL TOGGLE
            $mapContainer.append(`
                <div class="custom-label-toggle">
                    <button class="label-toggle-btn active-label" id="labelToggleBtn" title="Toggle Labels">
                        <i class="bi bi-fonts"></i>
                    </button>
                </div>
            `);

            // 3. LEGEND TOGGLE
            $mapContainer.append(`
                <div class="custom-legend-toggle">
                    <button class="legend-toggle-btn" id="legendToggleBtn" title="Toggle Infrastructure Legend">
                        <i class="bi bi-list-ul"></i>
                    </button>
                </div>
            `);

            // 4. LOCATION SWITCHER
            $mapContainer.append(`
                <div class="custom-location-switcher">
                    <button class="location-toggle-btn" id="locationToggleBtn"><i class="bi bi-geo-alt"></i></button>
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
                        <div class="location-dropdown-item" id="getDirectionsItem">
                            <div class="location-item-icon"><i class="bi bi-map"></i></div>
                            <div class="location-item-name">Get Directions</div>
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
                    <button class="search-toggle-btn" id="searchToggleBtn"><i class="bi bi-search"></i></button>
                    <div class="search-dropdown" id="searchDropdown">
                        <div class="d-flex border-bottom">
                            <button type="button" class="btn btn-sm flex-fill search-tab-btn active" data-tab="quick">Quick Search</button>
                            <button type="button" class="btn btn-sm flex-fill search-tab-btn" data-tab="filter">Filter</button>
                        </div>
                        <div class="search-tab-pane" id="quickSearchTab">
                            <div class="p-3">
                                <input type="text" id="gisSearchInput" class="form-control" placeholder="Search by GIS ID, Assessment, Owner...">
                            </div>
                            <div id="searchResults" class="search-results-container"></div>
                        </div>
                        <div class="search-tab-pane" id="filterTab" style="display:none;">
                            <div class="p-3 pb-2">
                                <input type="text" id="filterAssessment" class="form-control mb-2" placeholder="Assessment Number">
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
                    <button class="threed-toggle-btn" id="threeDToggleBtn" title="Toggle 3D View">
                        <i class="bi bi-box"></i>
                    </button>
                </div>
            `);

            // 7. FULLSCREEN BUTTON (Inside map)
            $mapContainer.append(`
                <button class="fullscreen-btn" id="fullscreenBtn">
                    <i class="bi bi-arrows-fullscreen"></i>
                </button>
                <button class="fullscreen-btn-exit" id="fullscreenExitBtn" style="display:none;">
                    <i class="bi bi-fullscreen-exit"></i>
                </button>
            `);

            // 8. DIRECTION CONTROLS
            $mapContainer.append(`
                <div class="direction-controls" id="directionControls">
                    <button class="btn-close-route" id="closeRouteBtn">&times;</button>
                    <div class="route-info" id="routeInfo">Getting directions...</div>
                    <div id="routeSteps" style="max-height:150px;overflow-y:auto;font-size:12px;"></div>
                </div>
            `);

            // ─── FUNCTIONS ───

            // Show Toast Message
            function showToast(message, duration = 3000) {
                const $toast = $('#locationToast');
                $toast.text(message).show();
                clearTimeout($toast.data('timeout'));
                $toast.data('timeout', setTimeout(() => $toast.fadeOut(), duration));
            }

            // Switch Base Layer
            function switchBaseLayer(layer) {
                [osmLayer, satelliteLayer].forEach(l => {
                    l.setVisible(l === layer);
                });
                $('#activeLayerBadge').text(layer.get('title') || 'Layer');
            }

            // Toggle Drone Layer
            function toggleDroneLayer() {
                const visible = !droneLayer.getVisible();
                droneLayer.setVisible(visible);
                return visible;
            }

            // Create Position Marker
            function createPositionMarker(coordinates, color = '#0d6efd') {
                return new ol.Feature({
                    geometry: new ol.geom.Point(coordinates)
                });
            }

            // Update Position
            function updatePosition(position) {
                const coords = [position.coords.longitude, position.coords.latitude];
                const projected = ol.proj.fromLonLat(coords);
                currentPosition = projected;

                if (!currentPositionFeature) {
                    currentPositionFeature = new ol.Feature({
                        geometry: new ol.geom.Point(projected)
                    });
                    currentPositionFeature.setStyle(new ol.style.Style({
                        image: new ol.style.Circle({
                            radius: 10,
                            fill: new ol.style.Fill({
                                color: '#0d6efd'
                            }),
                            stroke: new ol.style.Stroke({
                                color: '#ffffff',
                                width: 3
                            })
                        })
                    }));
                    const layer = new ol.layer.Vector({
                        source: new ol.source.Vector({
                            features: [currentPositionFeature]
                        })
                    });
                    map.addLayer(layer);
                } else {
                    currentPositionFeature.getGeometry().setCoordinates(projected);
                }

                if (isLiveLocation) {
                    map.getView().animate({
                        center: projected,
                        zoom: 19,
                        duration: 1000
                    });
                }

                // Update route if tracking
                if (isTracking) {
                    routePoints.push(projected);
                    updateRouteLine();
                }
            }

            // Update Route Line
            function updateRouteLine() {
                if (routePoints.length < 2) return;

                if (!routeLine) {
                    routeLine = new ol.Feature({
                        geometry: new ol.geom.LineString(routePoints)
                    });
                    routeLine.setStyle(new ol.style.Style({
                        stroke: new ol.style.Stroke({
                            color: '#dc3545',
                            width: 4,
                            lineDash: [5, 5]
                        })
                    }));
                    const layer = new ol.layer.Vector({
                        source: new ol.source.Vector({
                            features: [routeLine]
                        })
                    });
                    map.addLayer(layer);
                } else {
                    routeLine.getGeometry().setCoordinates(routePoints);
                }
            }

            // Calculate Distance and Directions
            function calculateDirections(from, to) {
                const fromLonLat = ol.proj.toLonLat(from);
                const toLonLat = ol.proj.toLonLat(to);

                const fromPoint = turf.point(fromLonLat);
                const toPoint = turf.point(toLonLat);

                const distance = turf.distance(fromPoint, toPoint, { units: 'kilometers' });
                const bearing = turf.bearing(fromPoint, toPoint);

                return {
                    distance: distance,
                    bearing: bearing,
                    from: fromLonLat,
                    to: toLonLat
                };
            }

            // Show Directions
            function showDirections(from, to) {
                const directions = calculateDirections(from, to);
                const $controls = $('#directionControls');
                const $info = $('#routeInfo');
                const $steps = $('#routeSteps');

                $controls.addClass('active');
                $info.html(`
                    <strong>Route Info:</strong><br>
                    Distance: ${directions.distance.toFixed(2)} km<br>
                    Bearing: ${directions.bearing.toFixed(1)}°
                `);

                let stepsHtml = '<div class="dropdown-header mt-2">Directions</div>';
                stepsHtml += `
                    <div style="padding:4px 0;">1. Head ${directions.bearing.toFixed(0)}° for ${directions.distance.toFixed(2)} km</div>
                    <div style="padding:4px 0;color:#666;font-size:11px;">Destination: ${directions.to.join(', ')}</div>
                `;
                $steps.html(stepsHtml);

                // Draw route line
                if (routeLine) {
                    const routeCoords = [from, to];
                    routeLine.getGeometry().setCoordinates(routeCoords);
                } else {
                    routeLine = new ol.Feature({
                        geometry: new ol.geom.LineString([from, to])
                    });
                    routeLine.setStyle(new ol.style.Style({
                        stroke: new ol.style.Stroke({
                            color: '#0d6efd',
                            width: 4
                        })
                    }));
                    const layer = new ol.layer.Vector({
                        source: new ol.source.Vector({
                            features: [routeLine]
                        })
                    });
                    map.addLayer(layer);
                }

                // Add destination marker
                if (destinationFeature) {
                    destinationFeature.getGeometry().setCoordinates(to);
                } else {
                    destinationFeature = new ol.Feature({
                        geometry: new ol.geom.Point(to)
                    });
                    destinationFeature.setStyle(new ol.style.Style({
                        image: new ol.style.Circle({
                            radius: 12,
                            fill: new ol.style.Fill({
                                color: '#dc3545'
                            }),
                            stroke: new ol.style.Stroke({
                                color: '#ffffff',
                                width: 3
                            })
                        })
                    }));
                    const layer = new ol.layer.Vector({
                        source: new ol.source.Vector({
                            features: [destinationFeature]
                        })
                    });
                    map.addLayer(layer);
                }

                // Zoom to show both points
                const extent = ol.extent.boundingExtent([from, to]);
                const paddedExtent = ol.extent.buffer(extent, 500);
                map.getView().fit(paddedExtent, {
                    duration: 1000,
                    padding: [50, 50, 50, 50]
                });

                showToast('Directions calculated successfully!');
            }

            // ─── EVENT HANDLERS ───

            // Toggle Dropdowns
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

            // Layer Dropdown Items
            $(document).on('click', '.layer-dropdown-item', function(e) {
                e.stopPropagation();
                const layerType = $(this).data('layer-type');
                const layerTitle = $(this).data('layer');

                if (layerType === 'base') {
                    let layer;
                    if (layerTitle === 'OpenStreetMap') layer = osmLayer;
                    else if (layerTitle === 'Satellite') layer = satelliteLayer;

                    if (layer) {
                        switchBaseLayer(layer);
                        $('.layer-dropdown-item[data-layer-type="base"]').removeClass('active');
                        $(this).addClass('active');
                    }
                } else if (layerTitle === 'Drone View') {
                    const visible = toggleDroneLayer();
                    $(this).toggleClass('active', visible);
                } else if (layerType === 'vector') {
                    let layer;
                    if (layerTitle === 'Polygons') layer = polygonLayer;
                    else if (layerTitle === 'Lines') layer = lineLayer;
                    else if (layerTitle === 'Points') layer = pointLayer;

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
                Swal.fire({
                    title: 'Infrastructure Legend',
                    html: `
                        <div style="text-align:left;">
                            <div><span style="display:inline-block;width:20px;height:20px;background:rgba(255,0,0,0.1);border:2px solid red;margin-right:10px;"></span> Polygons</div>
                            <div><span style="display:inline-block;width:20px;height:20px;background:transparent;border:3px solid #ff0000;margin-right:10px;"></span> Lines</div>
                            <div><span style="display:inline-block;width:20px;height:20px;background:#ff0000;border-radius:50%;margin-right:10px;"></span> Points</div>
                            <div><span style="display:inline-block;width:20px;height:20px;background:rgba(255,0,0,0.1);border:2px solid #0d6efd;margin-right:10px;"></span> Drone View</div>
                            <div class="dropdown-divider"></div>
                            <div><span style="display:inline-block;width:20px;height:20px;background:#0d6efd;border-radius:50%;border:2px solid white;margin-right:10px;"></span> Current Location</div>
                            <div><span style="display:inline-block;width:20px;height:20px;background:#dc3545;border-radius:50%;border:2px solid white;margin-right:10px;"></span> Destination</div>
                            <div><span style="display:inline-block;width:20px;height:4px;background:#0d6efd;margin-right:10px;"></span> Route</div>
                        </div>
                    `,
                    icon: 'info',
                    confirmButtonText: 'Close'
                });
            });

            // 3D Toggle
            $('#threeDToggleBtn').on('click', function() {
                $(this).toggleClass('active-3d');
                Swal.fire({
                    title: '3D View',
                    text: '3D functionality requires Cesium integration. This is a placeholder.',
                    icon: 'info',
                    confirmButtonText: 'OK'
                });
            });

            // ─── LOCATION TRACKING ───

            // Live Location
            $('#liveLocationItem').on('click', function() {
                if (!navigator.geolocation) {
                    showToast('Geolocation is not supported by your browser');
                    return;
                }

                isLiveLocation = !isLiveLocation;
                const $badge = $('#liveLocationBadge');
                const $btn = $('#locationToggleBtn');

                if (isLiveLocation) {
                    $badge.text('ON').addClass('active');
                    $btn.addClass('active-location');
                    showToast('Live location activated');

                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            updatePosition(pos);
                            if (watchId === null) {
                                watchId = navigator.geolocation.watchPosition(
                                    (newPos) => updatePosition(newPos),
                                    (error) => {
                                        console.error('Geolocation error:', error);
                                        showToast('Error getting location: ' + error.message);
                                    }, {
                                        enableHighAccuracy: true,
                                        timeout: 10000,
                                        maximumAge: 0
                                    }
                                );
                            }
                        },
                        (error) => {
                            console.error('Geolocation error:', error);
                            showToast('Error getting location: ' + error.message);
                            isLiveLocation = false;
                            $badge.text('OFF').removeClass('active');
                            $btn.removeClass('active-location');
                        }, {
                            enableHighAccuracy: true,
                            timeout: 10000
                        }
                    );
                } else {
                    $badge.text('OFF').removeClass('active');
                    $btn.removeClass('active-location');
                    showToast('Live location deactivated');
                    if (watchId !== null) {
                        navigator.geolocation.clearWatch(watchId);
                        watchId = null;
                    }
                }
            });

            // Track Me
            $('#trackMeItem').on('click', function() {
                if (!navigator.geolocation) {
                    showToast('Geolocation is not supported by your browser');
                    return;
                }

                isTracking = !isTracking;
                const $badge = $('#trackMeBadge');
                const $btn = $('#locationToggleBtn');

                if (isTracking) {
                    $badge.text('ON').addClass('tracking');
                    $btn.addClass('tracking');
                    routePoints = [];
                    showToast('Tracking started');

                    // Ensure we have current position
                    if (!currentPosition) {
                        navigator.geolocation.getCurrentPosition(
                            (pos) => {
                                updatePosition(pos);
                            },
                            (error) => {
                                console.error('Geolocation error:', error);
                                showToast('Error getting location: ' + error.message);
                                isTracking = false;
                                $badge.text('OFF').removeClass('tracking');
                                $btn.removeClass('tracking');
                            }, {
                                enableHighAccuracy: true,
                                timeout: 10000
                            }
                        );
                    }
                } else {
                    $badge.text('OFF').removeClass('tracking');
                    $btn.removeClass('tracking');
                    showToast('Tracking stopped');
                }
            });

            // Get Directions
            $('#getDirectionsItem').on('click', function() {
                if (!currentPosition) {
                    showToast('Please enable Live Location first');
                    return;
                }

                // Show a prompt to select destination
                Swal.fire({
                    title: 'Get Directions',
                    text: 'Click on a polygon on the map to set destination, or enter GIS ID:',
                    input: 'text',
                    inputPlaceholder: 'Enter GIS ID',
                    inputAttributes: {
                        'aria-label': 'Enter GIS ID'
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Find',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        const gisid = result.value.trim();
                        const polygon = polygons.find(p => p.gisid == gisid);
                        if (polygon) {
                            try {
                                const coords = JSON.parse(polygon.coordinates);
                                const center = ol.extent.getCenter(new ol.extent.boundingExtent(coords));
                                showDirections(currentPosition, center);
                            } catch (e) {
                                showToast('Error finding polygon');
                            }
                        } else {
                            showToast('No polygon found with GIS ID: ' + gisid);
                        }
                    }
                });

                // Also allow clicking on map
                showToast('Click on the map to set destination, or use the dialog');
                const clickHandler = function(e) {
                    const coords = e.coordinate;
                    // Check if clicked on a polygon
                    const features = map.getFeaturesAtPixel(e.pixel, {
                        hitTolerance: 10,
                        layers: [polygonLayer]
                    });

                    if (features && features.length > 0) {
                        const feature = features[0];
                        const geom = feature.getGeometry();
                        if (geom.getType() === 'Polygon') {
                            const center = geom.getInteriorPoint().getCoordinates();
                            showDirections(currentPosition, center);
                            map.un('click', clickHandler);
                        }
                    }
                };
                map.once('click', clickHandler);
            });

            // Clear Route
            $('#clearRouteItem').on('click', function() {
                // Clear route line
                if (routeLine) {
                    const source = routeLine.getSource ? routeLine.getSource() : null;
                    if (source) {
                        source.removeFeature(routeLine);
                    }
                    routeLine = null;
                }

                // Clear destination marker
                if (destinationFeature) {
                    const source = destinationFeature.getSource ? destinationFeature.getSource() : null;
                    if (source) {
                        source.removeFeature(destinationFeature);
                    }
                    destinationFeature = null;
                }

                // Clear route points
                routePoints = [];

                // Hide direction controls
                $('#directionControls').removeClass('active');
                $('#routeSteps').empty();

                showToast('Route cleared');
            });

            // Close route button
            $('#closeRouteBtn').on('click', function() {
                $('#clearRouteItem').click();
            });

            // ─── FULLSCREEN ───
            let isFullscreen = false;

            $('#fullscreenBtn').on('click', function() {
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
                }, 150);
            });

            $('#fullscreenExitBtn').on('click', function() {
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
                }, 150);
            });

            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && isFullscreen) {
                    $('#fullscreenExitBtn').click();
                }
            });

            // ─── SEARCH FUNCTIONALITY ───

            // Quick Search - Search by GIS ID, Assessment, Owner, Phone
            $('#gisSearchInput').on('input', function() {
                const query = $(this).val().toLowerCase().trim();
                const results = $('#searchResults');

                if (query.length < 2) {
                    results.empty();
                    return;
                }

                // Search in polygons with multiple fields
                const matches = polygons.filter(p => {
                    const searchable = [
                        p.gisid,
                        p.assessment,
                        p.old_assessment,
                        p.owner_name,
                        p.phone_number
                    ].filter(Boolean).map(String);

                    return searchable.some(field =>
                        field.toLowerCase().includes(query)
                    );
                });

                if (matches.length > 0) {
                    let html = '<div class="dropdown-header">Results (' + matches.length + ')</div>';
                    matches.slice(0, 15).forEach(m => {
                        const details = [];
                        if (m.assessment) details.push('Assess: ' + m.assessment);
                        if (m.owner_name) details.push('Owner: ' + m.owner_name);
                        if (m.phone_number) details.push('Phone: ' + m.phone_number);

                        html += `
                            <div class="layer-dropdown-item search-result-item" data-gisid="${m.gisid}" data-coords='${m.coordinates}'>
                                <div class="layer-icon"><i class="bi bi-pentagon"></i></div>
                                <div class="layer-name">
                                    GISID: ${m.gisid}
                                    ${details.length > 0 ? `<div class="result-detail">${details.join(' | ')}</div>` : ''}
                                </div>
                            </div>
                        `;
                    });
                    results.html(html);
                } else {
                    results.html('<div class="p-3 text-muted text-center">No results found</div>');
                }
            });

            // Search result click - zoom to polygon
            $(document).on('click', '.search-result-item', function() {
                const gisid = $(this).data('gisid');
                const polygon = polygons.find(p => p.gisid == gisid);
                if (polygon) {
                    try {
                        const coords = JSON.parse(polygon.coordinates);
                        const extent = ol.extent.boundingExtent(coords);
                        const center = ol.extent.getCenter(extent);
                        map.getView().animate({
                            center: center,
                            zoom: 20,
                            duration: 1000
                        });
                        $('.search-dropdown').removeClass('active');
                        $('#gisSearchInput').val('');
                        $('#searchResults').empty();
                        showToast('Zoomed to GISID: ' + gisid);
                    } catch (e) {
                        console.error('Error zooming to polygon:', e);
                        showToast('Error zooming to polygon');
                    }
                }
            });

            // Search tabs
            $('.search-tab-btn').on('click', function() {
                const tab = $(this).data('tab');
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

            // Filter Search - Advanced filtering
            $('#applyFilterBtn').on('click', function() {
                const assessment = $('#filterAssessment').val().toLowerCase().trim();
                const oldAssessment = $('#filterOldAssessment').val().toLowerCase().trim();
                const ownerName = $('#filterOwnerName').val().toLowerCase().trim();
                const phoneNumber = $('#filterPhoneNumber').val().toLowerCase().trim();

                // Check if any filter is applied
                if (!assessment && !oldAssessment && !ownerName && !phoneNumber) {
                    showToast('Please enter at least one filter criteria');
                    return;
                }

                let matches = polygons.filter(p => {
                    let match = true;

                    if (assessment) {
                        const pAssessment = (p.assessment || '').toString().toLowerCase();
                        match = match && pAssessment.includes(assessment);
                    }
                    if (oldAssessment) {
                        const pOldAssessment = (p.old_assessment || '').toString().toLowerCase();
                        match = match && pOldAssessment.includes(oldAssessment);
                    }
                    if (ownerName) {
                        const pOwnerName = (p.owner_name || '').toString().toLowerCase();
                        match = match && pOwnerName.includes(ownerName);
                    }
                    if (phoneNumber) {
                        const pPhoneNumber = (p.phone_number || '').toString().toLowerCase();
                        match = match && pPhoneNumber.includes(phoneNumber);
                    }

                    return match;
                });

                const results = $('#filterResults');
                if (matches.length > 0) {
                    let html = '<div class="dropdown-header">Results (' + matches.length + ' found)</div>';
                    matches.slice(0, 15).forEach(m => {
                        const details = [];
                        if (m.assessment) details.push('Assess: ' + m.assessment);
                        if (m.owner_name) details.push('Owner: ' + m.owner_name);
                        if (m.phone_number) details.push('Phone: ' + m.phone_number);

                        html += `
                            <div class="layer-dropdown-item search-result-item" data-gisid="${m.gisid}" data-coords='${m.coordinates}'>
                                <div class="layer-icon"><i class="bi bi-pentagon"></i></div>
                                <div class="layer-name">
                                    GISID: ${m.gisid}
                                    ${details.length > 0 ? `<div class="result-detail">${details.join(' | ')}</div>` : ''}
                                </div>
                            </div>
                        `;
                    });
                    results.html(html);
                    showToast('Found ' + matches.length + ' results');
                } else {
                    results.html('<div class="p-3 text-muted text-center">No matching records found</div>');
                    showToast('No results found');
                }
            });

            // Enter key for filter search
            $('#filterAssessment, #filterOldAssessment, #filterOwnerName, #filterPhoneNumber').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#applyFilterBtn').click();
                }
            });

            // Enter key for quick search
            $('#gisSearchInput').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    // Trigger search
                    $(this).trigger('input');
                    // If there's a result, click the first one
                    const firstResult = $('.search-result-item').first();
                    if (firstResult.length) {
                        firstResult.click();
                    }
                }
            });

            console.log('GIS Dashboard initialized successfully!');
            console.log('Polygons loaded:', polygons.length);
            console.log('Lines loaded:', lines.length);
            console.log('Points loaded:', points.length);
        });
    </script>
@endpush

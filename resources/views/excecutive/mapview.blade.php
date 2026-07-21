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
            border: none;
            color: #333;
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
            border: none;
            color: #333;
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

        /* Search Results Styles */
        .search-result-item {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.15s;
        }

        .search-result-item:hover {
            background: #f8f9fa;
        }

        .search-result-title {
            font-weight: 500;
            color: #333;
        }

        .search-result-subtitle {
            font-size: 12px;
            color: #888;
            margin-top: 2px;
        }

        /* OL Override */
        .ol-viewport {
            border-radius: 0 0 12px 12px;
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
            const pointSource = new ol.source.Vector();

            // ─── STYLES ───
            function createPolygonStyle(feature) {
                const gisid = feature.get('gisid');
                const sqft = feature.get('sqfeet') || '0';
                const polygonData = polygonDatas.find(d => d.gisid == gisid);
                const color = polygonData ? 'red' : 'blue';

                try {
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

                    // Add labels if label toggle is active
                    if ($('#labelToggleBtn').hasClass('active-label')) {
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
                    }

                    return styles;
                } catch (e) {
                    return new ol.style.Style({
                        stroke: new ol.style.Stroke({
                            color: 'blue',
                            width: 4
                        })
                    });
                }
            }

            function createLineStyle(feature) {
                return new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#ff0000',
                        width: 3
                    })
                });
            }

            // ─── LOAD SOURCES ───
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
                            originalData: poly
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
                        // Handle different coordinate formats
                        let geometry;
                        if (Array.isArray(coords) && coords.length > 0) {
                            if (Array.isArray(coords[0]) && Array.isArray(coords[0][0])) {
                                // MultiLineString format
                                geometry = new ol.geom.MultiLineString(coords);
                            } else if (Array.isArray(coords[0]) && typeof coords[0][0] === 'number') {
                                // LineString format
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
                                road_name: line.road_name || '0',
                                originalData: line
                            });
                            feature.setId(line.gisid);
                            lineSource.addFeature(feature);
                        }
                    } catch (e) {
                        console.error('line parse error:', e);
                    }
                });
            }

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
                        const coords = typeof line.coordinates === 'string' ? JSON.parse(line.coordinates) : line
                            .coordinates;
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

            // ─── CREATE MAP ───
            const map = new ol.Map({
                target: 'map',
                layers: [osmLayer, satelliteLayer, streetViewLayer, droneLayer, polygonLayer, lineLayer],
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

            // ─── FUNCTIONS ───

            // Switch Base Layer
            function switchBaseLayer(layer) {
                [osmLayer, satelliteLayer, streetViewLayer].forEach(l => {
                    l.setVisible(l === layer);
                });
                const layerName = layer.get('title') || 'Layer';
                $('#activeLayerBadge').text(layerName);

                // Update dropdown
                $('.layer-dropdown-item[data-layer-type="base"]').removeClass('active');
                $('.layer-dropdown-item[data-layer="' + layerName + '"]').addClass('active');
            }

            // Toggle Drone Layer
            function toggleDroneLayer() {
                const visible = !droneLayer.getVisible();
                droneLayer.setVisible(visible);
                return visible;
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
                    else if (layerTitle === 'Street View') layer = streetViewLayer;

                    if (layer) {
                        switchBaseLayer(layer);
                    }
                } else if (layerTitle === 'Drone View') {
                    const visible = toggleDroneLayer();
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
                Swal.fire({
                    title: 'Infrastructure Legend',
                    html: `
                        <div style="text-align:left;">
                            <div><span style="display:inline-block;width:20px;height:20px;background:rgba(255,0,0,0.1);border:2px solid blue;margin-right:10px;"></span> Polygons (Land Parcels)</div>
                            <div><span style="display:inline-block;width:20px;height:4px;background:#ff0000;border-radius:2px;margin-right:10px;"></span> Lines (Roads)</div>
                            <div><span style="display:inline-block;width:20px;height:20px;background:rgba(0,0,0,0.3);border-radius:4px;margin-right:10px;"></span> Drone View</div>
                            <hr>
                            <div class="text-muted small">Click on features for more details</div>
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
                    text: '3D functionality requires Cesium integration.',
                    icon: 'info',
                    confirmButtonText: 'OK'
                });
            });

            // Fullscreen Toggle
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

            // Escape key to exit fullscreen
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && isFullscreen) {
                    $('#fullscreenExitBtn').click();
                }
            });

            // ─── SEARCH FUNCTIONALITY ───
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
                            `${item.title} | Owner: ${item.subtitle.split('|')[1] || ''}` : item.title;
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

            // Zoom to search result
            $(document).on('click', '.zoom-btn', function(e) {
                e.stopPropagation();
                const id = $(this).data('id');
                const type = $(this).data('type');
                zoomToFeature(id, type);
            });

            function zoomToFeature(id, type) {
                let feature = null;
                if (type === 'polygon' || type === 'polygon_data') {
                    polygonSource.getFeatures().forEach(f => {
                        if (f.get('gisid') == id) feature = f;
                    });
                } else if (type === 'line') {
                    lineSource.getFeatures().forEach(f => {
                        if (f.get('gisid') == id) feature = f;
                    });
                }

                if (feature) {
                    const extent = feature.getGeometry().getExtent();
                    map.getView().fit(extent, { padding: [50, 50, 50, 50], maxZoom: 20 });
                    $('.search-dropdown').removeClass('active');
                    $('#gisSearchInput').val('');
                    $('#searchResults').empty();
                }
            }

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

            // Filter search
            $('#applyFilterBtn').on('click', function() {
                // Show all polygon results for demo
                let html = '';
                polygons.slice(0, 10).forEach(poly => {
                    html += `
                        <div class="search-result-item">
                            <div class="search-result-title">GIS ID: ${poly.gisid}</div>
                            <div class="search-result-subtitle">Area: ${poly.sqfeet || 0} sqft</div>
                        </div>`;
                });
                $('#filterResults').html(html || '<div class="p-2 text-muted">No matches</div>');
            });

            // ─── LOCATION FUNCTIONALITY ───
            let watchId = null;
            let isTracking = false;

            $('#liveLocationItem').on('click', function() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            const coords = ol.proj.fromLonLat([position.coords.longitude, position.coords
                                .latitude
                            ]);
                            map.getView().setCenter(coords);
                            map.getView().setZoom(20);
                            $('#liveLocationBadge').text('ON').addClass('active');
                            $('#locationToast').text('📍 Location found!').fadeIn().delay(3000).fadeOut();
                        },
                        function(error) {
                            $('#locationToast').text('❌ Location error: ' + error.message).fadeIn().delay(
                            3000).fadeOut();
                        }
                    );
                } else {
                    $('#locationToast').text('❌ Geolocation not supported').fadeIn().delay(3000).fadeOut();
                }
                $('.location-dropdown').removeClass('active');
            });

            $('#trackMeItem').on('click', function() {
                if (!isTracking) {
                    if (navigator.geolocation) {
                        watchId = navigator.geolocation.watchPosition(
                            function(position) {
                                const coords = ol.proj.fromLonLat([position.coords.longitude, position.coords
                                    .latitude
                                ]);
                                map.getView().setCenter(coords);
                                map.getView().setZoom(20);
                                $('#trackMeBadge').text('ON').addClass('active');
                            },
                            function(error) {
                                $('#locationToast').text('❌ Tracking error: ' + error.message).fadeIn()
                                    .delay(3000).fadeOut();
                            }
                        );
                        isTracking = true;
                        $('#trackMeBadge').text('ON').addClass('active');
                        $('#locationToast').text('📍 Tracking started!').fadeIn().delay(3000).fadeOut();
                    }
                } else {
                    if (watchId) {
                        navigator.geolocation.clearWatch(watchId);
                        watchId = null;
                    }
                    isTracking = false;
                    $('#trackMeBadge').text('OFF').removeClass('active');
                    $('#locationToast').text('⏹️ Tracking stopped').fadeIn().delay(3000).fadeOut();
                }
                $('.location-dropdown').removeClass('active');
            });

            $('#clearRouteItem').on('click', function() {
                if (watchId) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                }
                isTracking = false;
                $('#trackMeBadge').text('OFF').removeClass('active');
                $('#liveLocationBadge').text('OFF').removeClass('active');
                $('#locationToast').text('🧹 Cleared all location data').fadeIn().delay(3000).fadeOut();
                $('.location-dropdown').removeClass('active');
            });

            console.log('✅ GIS Dashboard initialized successfully!');
            console.log('📊 Polygons loaded:', polygonSource.getFeatures().length);
            console.log('📊 Lines loaded:', lineSource.getFeatures().length);
            console.log('📊 Search index built:', searchIndex.length);
        });
    </script>
@endpush

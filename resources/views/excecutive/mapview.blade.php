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
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
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
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
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
            z-index: 1001;
        }

        .layer-dropdown {
            width: 260px;
        }

        .location-dropdown {
            width: 240px;
        }

        .search-dropdown {
            width: 380px;
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
            max-height: 250px;
            overflow-y: auto;
        }

        .location-toast {
            display: none;
            position: absolute;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.85);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            z-index: 1000;
            white-space: nowrap;
        }

        /* Search Results Styles */
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
        }

        .search-result-actions .btn-sm {
            padding: 2px 10px;
            font-size: 11px;
        }

        /* OL Override */
        .ol-viewport {
            border-radius: 0 0 12px 12px;
        }

        .direction-controls {
            position: absolute;
            bottom: 100px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            display: none;
            min-width: 300px;
            max-width: 400px;
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
            right: 12px;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #999;
        }

        .direction-controls .btn-close-route:hover {
            color: #333;
        }

        #routeSteps {
            max-height: 150px;
            overflow-y: auto;
            font-size: 12px;
        }

        #routeSteps .step-item {
            padding: 4px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        #routeSteps .step-item:last-child {
            border-bottom: none;
        }

        .filter-field-group {
            margin-bottom: 8px;
        }

        .filter-field-group label {
            font-size: 11px;
            color: #666;
            font-weight: 600;
            display: block;
            margin-bottom: 2px;
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
            let analytics = @json($analytics ?? [], JSON_HEX_TAG);
            let buildingVariations = @json($buildingVariations ?? [], JSON_HEX_TAG);

            // ─── BUILDING DATA ───
            let buildingData = @json($buildingData ?? [], JSON_HEX_TAG);
            let allBuildings = buildingData.buildings || [];
            let usageCounts = buildingData.usage_counts || {};

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

            // ─── SEARCH INDEX ───
            let searchIndex = [];

            // ─── LOCATION TRACKING VARIABLES ───
            let watchId = null;
            let isTracking = false;
            let isLiveLocation = false;
            let currentPosition = null;
            let positionFeature = null;
            let positionLayer = null;
            let routeLine = null;
            let routeLayer = null;
            let routePoints = [];
            let destinationMarker = null;
            let destinationLayer = null;

            // ─── STYLES ───
            function createPolygonStyle(feature) {
                const gisid = feature.get('gisid');
                const sqft = feature.get('sqfeet') || '0';
                const polygonData = polygonDatas.find(d => d.gisid == gisid);
                const color = polygonData ? 'red' : 'blue';
                const showLabels = $('#labelToggleBtn').hasClass('active-label');

                try {
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

                    if (showLabels) {
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

            function createPositionStyle() {
                return new ol.style.Style({
                    image: new ol.style.Circle({
                        radius: 12,
                        fill: new ol.style.Fill({
                            color: '#0d6efd'
                        }),
                        stroke: new ol.style.Stroke({
                            color: '#ffffff',
                            width: 3
                        })
                    }),
                    text: new ol.style.Text({
                        text: '📍 You',
                        font: 'bold 12px Arial',
                        fill: new ol.style.Fill({
                            color: '#000'
                        }),
                        backgroundFill: new ol.style.Fill({
                            color: '#fff'
                        }),
                        backgroundStroke: new ol.style.Stroke({
                            color: '#ccc',
                            width: 1
                        }),
                        padding: [2, 6, 2, 6],
                        offsetY: -18,
                        textAlign: 'center'
                    })
                });
            }

            function createDestinationStyle() {
                return new ol.style.Style({
                    image: new ol.style.Circle({
                        radius: 10,
                        fill: new ol.style.Fill({
                            color: '#dc3545'
                        }),
                        stroke: new ol.style.Stroke({
                            color: '#ffffff',
                            width: 3
                        })
                    }),
                    text: new ol.style.Text({
                        text: '📍 Destination',
                        font: 'bold 12px Arial',
                        fill: new ol.style.Fill({
                            color: '#000'
                        }),
                        backgroundFill: new ol.style.Fill({
                            color: '#fff'
                        }),
                        backgroundStroke: new ol.style.Stroke({
                            color: '#ccc',
                            width: 1
                        }),
                        padding: [2, 6, 2, 6],
                        offsetY: -18,
                        textAlign: 'center'
                    })
                });
            }

            // ─── BUILD SEARCH INDEX ───
            // NOTE: point / pointdata coordinates are stored in the DB already
            // projected to EPSG:3857 (same as the Surveyor dashboard). They must
            // NOT be passed through ol.proj.fromLonLat() again - doing so is what
            // caused the "wrong place" zoom bug in this file.
            function buildSearchIndex() {
                searchIndex = [];

                // Add polygons
                polygons.forEach(poly => {
                    try {
                        const coords = JSON.parse(poly.coordinates);
                        const extent = ol.extent.boundingExtent(coords);
                        const center = ol.extent.getCenter(extent);
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
                            center: center,
                            geometryType: 'polygon',
                            searchText: `${poly.gisid} ${poly.assessment || ''} ${poly.old_assessment || ''} ${poly.owner_name || ''} ${poly.phone_number || ''} ${poly.sqfeet || ''}`
                                .toLowerCase()
                        });
                    } catch (e) {
                        console.error('Error indexing polygon:', e);
                    }
                });

                // Add lines
                lines.forEach(line => {
                    try {
                        const coords = JSON.parse(line.coordinates);
                        let center = null;
                        try {
                            const flatCoords = coords.flat(2);
                            if (flatCoords && flatCoords.length > 0) {
                                const extent = ol.extent.boundingExtent(flatCoords);
                                center = ol.extent.getCenter(extent);
                            }
                        } catch (e) {
                            // Skip center calculation for lines
                        }
                        searchIndex.push({
                            id: line.gisid,
                            type: 'line',
                            title: `Road: ${line.road_name || line.gisid}`,
                            subtitle: `GIS ID: ${line.gisid}`,
                            road_name: line.road_name || '',
                            coordinates: coords,
                            center: center,
                            geometryType: 'line',
                            searchText: `${line.gisid} ${line.road_name || ''}`.toLowerCase()
                        });
                    } catch (e) {
                        console.error('Error indexing line:', e);
                    }
                });

                // Add points - FIXED: coordinates are already EPSG:3857, use as-is
                points.forEach(point => {
                    try {
                        let coords = JSON.parse(point.coordinates);
                        let center = null;

                        if (Array.isArray(coords) && coords.length === 2 &&
                            typeof coords[0] === 'number' && typeof coords[1] === 'number') {
                            // Already projected (EPSG:3857) - same as Surveyor dashboard.
                            // Do NOT run through ol.proj.fromLonLat() again.
                            center = coords;
                        }

                        searchIndex.push({
                            id: point.gisid,
                            type: 'point',
                            title: `GIS ID: ${point.gisid}`,
                            subtitle: 'Point Location',
                            coordinates: coords,
                            center: center,
                            geometryType: 'point',
                            searchText: `${point.gisid} point`.toLowerCase()
                        });
                    } catch (e) {
                        console.error('Error parsing point:', e);
                    }
                });

                // Add point data - FIXED: coordinates are already EPSG:3857, use as-is
                pointDatas.forEach(pd => {
                    try {
                        let coords = [];
                        let center = null;

                        // Parse coordinates from the stored JSON string
                        if (pd.coordinates) {
                            coords = JSON.parse(pd.coordinates);
                            if (Array.isArray(coords) && coords.length === 2 &&
                                typeof coords[0] === 'number' && typeof coords[1] === 'number') {
                                // Already projected (EPSG:3857) - same as Surveyor dashboard.
                                // Do NOT run through ol.proj.fromLonLat() again.
                                center = coords;
                            }
                        }

                        let pointGisid = pd.point_gisid || '';

                        searchIndex.push({
                            id: pd.id,
                            type: 'pointdata',
                            title: `Assessment: ${pd.assessment || 'N/A'}`,
                            subtitle: `GIS ID: ${pointGisid} | Owner: ${pd.owner_name || 'N/A'}`,
                            assessment: pd.assessment || '',
                            point_gisid: pointGisid,
                            owner_name: pd.owner_name || '',
                            phone_number: pd.phone_number || '',
                            coordinates: coords,
                            center: center,
                            geometryType: 'point',
                            searchText: `${pointGisid} ${pd.assessment || ''} ${pd.owner_name || ''} ${pd.phone_number || ''}`
                                .toLowerCase()
                        });
                    } catch (e) {
                        console.error('Error indexing point data:', e);
                    }
                });

                console.log('📊 Search Index Built:', searchIndex.length, 'items');
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
                            assessment: poly.assessment || '',
                            old_assessment: poly.old_assessment || '',
                            owner_name: poly.owner_name || '',
                            phone_number: poly.phone_number || '',
                            originalData: poly
                        });
                        feature.setId(poly.gisid);
                        polygonSource.addFeature(feature);
                    } catch (e) {
                        console.error('polygon parse error:', e);
                    }
                });
                console.log('📊 Polygons loaded:', polygonSource.getFeatures().length);
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
                    } catch (e) {
                        console.error('line parse error:', e);
                    }
                });
                console.log('📊 Lines loaded:', lineSource.getFeatures().length);
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

            // ─── CREATE POSITION LAYERS ───
            positionLayer = new ol.layer.Vector({
                source: new ol.source.Vector(),
                visible: true,
                zIndex: 100
            });

            routeLayer = new ol.layer.Vector({
                source: new ol.source.Vector(),
                visible: true,
                zIndex: 99
            });

            destinationLayer = new ol.layer.Vector({
                source: new ol.source.Vector(),
                visible: true,
                zIndex: 100
            });

            // ─── CREATE MAP ───
            const map = new ol.Map({
                target: 'map',
                layers: [osmLayer, satelliteLayer, streetViewLayer, droneLayer, polygonLayer, lineLayer,
                    positionLayer, routeLayer, destinationLayer
                ],
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
                        <div class="location-dropdown-item" id="liveLocationItem">
                            <div class="location-item-icon"><i class="bi bi-crosshair2"></i></div>
                            <div class="location-item-name">Live Location</div>
                            <div class="location-item-badge" id="liveLocationBadge">OFF</div>
                        </div>
                        <div class="location-dropdown-item" id="trackMeItem">
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
                                <input type="text" id="gisSearchInput" class="form-control" placeholder="Search by GIS ID, Assessment, Owner...">
                            </div>
                            <div id="searchResults" class="search-results-container"></div>
                        </div>
                        <div class="search-tab-pane" id="filterTab" style="display:none;">
                            <div class="p-3">
                                <div class="filter-field-group">
                                    <label>Assessment Number</label>
                                    <input type="text" id="filterAssessment" class="form-control" placeholder="Enter assessment number...">
                                </div>
                                <div class="filter-field-group">
                                    <label>Old Assessment</label>
                                    <input type="text" id="filterOldAssessment" class="form-control" placeholder="Enter old assessment...">
                                </div>
                                <div class="filter-field-group">
                                    <label>Owner Name</label>
                                    <input type="text" id="filterOwnerName" class="form-control" placeholder="Enter owner name...">
                                </div>
                                <div class="filter-field-group">
                                    <label>Phone Number</label>
                                    <input type="text" id="filterPhoneNumber" class="form-control" placeholder="Enter phone number...">
                                </div>
                                <button class="btn btn-primary btn-sm w-100 mt-2" id="applyFilterBtn">
                                    <i class="bi bi-search me-1"></i>Search
                                </button>
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

            // 7. FULLSCREEN BUTTON
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
                    <div id="routeSteps"></div>
                </div>
            `);

            // ─── FUNCTIONS ───

            // Show Toast
            function showToast(message, duration = 3000) {
                const $toast = $('#locationToast');
                $toast.text(message).fadeIn(200);
                clearTimeout($toast.data('timeout'));
                $toast.data('timeout', setTimeout(() => $toast.fadeOut(300), duration));
            }

            // Switch Base Layer
            function switchBaseLayer(layer) {
                [osmLayer, satelliteLayer, streetViewLayer].forEach(l => {
                    l.setVisible(l === layer);
                });
                const layerName = layer.get('title') || 'Layer';
                $('#activeLayerBadge').text(layerName);

                $('.layer-dropdown-item[data-layer-type="base"]').removeClass('active');
                $('.layer-dropdown-item[data-layer="' + layerName + '"]').addClass('active');
            }

            // Toggle Drone Layer
            function toggleDroneLayer() {
                const visible = !droneLayer.getVisible();
                droneLayer.setVisible(visible);
                return visible;
            }

            // Update Position
            function updatePosition(position) {
                const coords = [position.coords.longitude, position.coords.latitude];
                const projected = ol.proj.fromLonLat(coords);
                currentPosition = projected;

                if (!positionFeature) {
                    positionFeature = new ol.Feature({
                        geometry: new ol.geom.Point(projected)
                    });
                    positionFeature.setStyle(createPositionStyle());
                    positionLayer.getSource().addFeature(positionFeature);
                } else {
                    positionFeature.getGeometry().setCoordinates(projected);
                }

                if (isLiveLocation) {
                    map.getView().animate({
                        center: projected,
                        zoom: 19,
                        duration: 1000
                    });
                }

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
                            lineDash: [8, 6]
                        })
                    }));
                    routeLayer.getSource().addFeature(routeLine);
                } else {
                    routeLine.getGeometry().setCoordinates(routePoints);
                }
            }

            // ─── ZOOM TO FEATURE - FIXED FOR CORRECT PROJECTED COORDINATES ───
            // All point / pointdata coordinates in this project's DB are stored
            // already in EPSG:3857 (map projection), exactly like the Surveyor
            // dashboard. This function must treat them as already-projected and
            // must never re-run them through ol.proj.fromLonLat().
            function zoomToFeature(item) {
                try {
                    let center = null;
                    let zoomLevel = 20;
                    let found = false;

                    console.log('🔍 Zooming to item:', item);

                    // Case 1: Item has center property (from search index)
                    if (item.center) {
                        center = item.center;
                        found = true;
                        console.log('✅ Using center from search index:', center);
                    }
                    // Case 2: Item is polygon with coordinates
                    else if (item.type === 'polygon' && item.coordinates && Array.isArray(item.coordinates)) {
                        try {
                            const extent = ol.extent.boundingExtent(item.coordinates);
                            center = ol.extent.getCenter(extent);
                            found = true;
                            console.log('✅ Using polygon center:', center);
                        } catch (e) {
                            console.error('Error getting polygon center:', e);
                        }
                    }
                    // Case 3: Item is line with coordinates
                    else if (item.type === 'line' && item.coordinates) {
                        try {
                            const flatCoords = item.coordinates.flat(2);
                            if (flatCoords && flatCoords.length > 0) {
                                const extent = ol.extent.boundingExtent(flatCoords);
                                center = ol.extent.getCenter(extent);
                                found = true;
                                console.log('✅ Using line center:', center);
                            }
                        } catch (e) {
                            console.error('Error getting line center:', e);
                        }
                    }
                    // Case 4: Item is point (from points array) - coordinates already EPSG:3857
                    else if (item.type === 'point' && item.coordinates) {
                        try {
                            let coords = item.coordinates;
                            if (Array.isArray(coords) && coords.length === 2 &&
                                typeof coords[0] === 'number' && typeof coords[1] === 'number') {
                                center = coords; // already projected - use as-is
                                found = true;
                                zoomLevel = 21;
                                console.log('✅ Using point coordinates (already EPSG:3857):', center);
                            }
                        } catch (e) {
                            console.error('Error reading point coordinates:', e);
                        }
                    }
                    // Case 5: Item is pointdata - coordinates already EPSG:3857
                    else if (item.type === 'pointdata' && item.coordinates && item.coordinates.length === 2) {
                        try {
                            if (typeof item.coordinates[0] === 'number' && typeof item.coordinates[1] === 'number') {
                                center = item.coordinates; // already projected - use as-is
                                found = true;
                                zoomLevel = 21;
                                console.log('✅ Using pointdata coordinates (already EPSG:3857):', center);
                            }
                        } catch (e) {
                            console.error('Error reading pointdata coordinates:', e);
                        }
                    }
                    // Case 6: Try to find by GISID in polygonSource or original data
                    else if (item.id) {
                        // Check in polygon source
                        const features = polygonSource.getFeatures();
                        for (let f of features) {
                            if (f.get('gisid') == item.id) {
                                try {
                                    const geom = f.getGeometry();
                                    if (geom) {
                                        const extent = geom.getExtent();
                                        center = ol.extent.getCenter(extent);
                                        found = true;
                                        console.log('✅ Using feature from polygonSource:', center);
                                        break;
                                    }
                                } catch (e) {
                                    console.error('Error getting feature geometry:', e);
                                }
                            }
                        }

                        // If not found in polygonSource, check points array
                        // (coordinates already EPSG:3857 - use as-is)
                        if (!found && points) {
                            const pointData = points.find(p => p.gisid == item.id);
                            if (pointData) {
                                try {
                                    let coords = JSON.parse(pointData.coordinates);
                                    if (Array.isArray(coords) && coords.length === 2 &&
                                        typeof coords[0] === 'number' && typeof coords[1] === 'number') {
                                        center = coords; // already projected - use as-is
                                        found = true;
                                        zoomLevel = 21;
                                        console.log('✅ Using point from points array (EPSG:3857):', center);
                                    }
                                } catch (e) {
                                    console.error('Error parsing point data:', e);
                                }
                            }
                        }

                        // If not found, check pointDatas
                        // (coordinates already EPSG:3857 - use as-is)
                        if (!found && pointDatas) {
                            const pointData = pointDatas.find(p => p.point_gisid == item.id || p.id == item.id);
                            if (pointData && pointData.coordinates) {
                                try {
                                    let coords = JSON.parse(pointData.coordinates);
                                    if (Array.isArray(coords) && coords.length === 2 &&
                                        typeof coords[0] === 'number' && typeof coords[1] === 'number') {
                                        center = coords; // already projected - use as-is
                                        found = true;
                                        zoomLevel = 21;
                                        console.log('✅ Using point from pointDatas (EPSG:3857):', center);
                                    }
                                } catch (e) {
                                    console.error('Error parsing pointData:', e);
                                }
                            }
                        }
                    }

                    // If we found a center, zoom to it
                    if (found && center) {
                        // Validate center coordinates
                        if (Array.isArray(center) && center.length === 2 &&
                            !isNaN(center[0]) && !isNaN(center[1])) {

                            map.getView().animate({
                                center: center,
                                zoom: zoomLevel,
                                duration: 1000
                            });

                            // Show success message with details
                            let displayName = item.title || `GISID: ${item.id}`;
                            showToast(`📍 Zoomed to: ${displayName}`);
                            return true;
                        } else {
                            console.error('Invalid center coordinates:', center);
                            showToast('❌ Invalid coordinates for this item');
                            return false;
                        }
                    } else {
                        showToast('❌ Cannot zoom to this item - no coordinates found');
                        console.log('❌ Item details:', item);
                        return false;
                    }
                } catch (e) {
                    console.error('❌ Error in zoomToFeature:', e);
                    showToast('❌ Error zooming to item');
                    return false;
                }
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
                            <div><span style="display:inline-block;width:20px;height:20px;background:#0d6efd;border-radius:50%;border:2px solid white;margin-right:10px;"></span> Current Location</div>
                            <div><span style="display:inline-block;width:20px;height:4px;background:#dc3545;border-radius:2px;margin-right:10px;"></span> Track Route</div>
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

            // ─── LOCATION FUNCTIONALITY ───

            // Live Location
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
                    showToast('📍 Live location activated');

                    navigator.geolocation.getCurrentPosition(
                        function(pos) {
                            updatePosition(pos);
                            if (!watchId) {
                                watchId = navigator.geolocation.watchPosition(
                                    function(newPos) {
                                        updatePosition(newPos);
                                    },
                                    function(error) {
                                        console.error('Watch error:', error);
                                        showToast('❌ Location error: ' + error.message);
                                    }, {
                                        enableHighAccuracy: true,
                                        timeout: 10000,
                                        maximumAge: 0
                                    }
                                );
                            }
                        },
                        function(error) {
                            console.error('Get position error:', error);
                            showToast('❌ Error getting location: ' + error.message);
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
                    showToast('📍 Live location deactivated');
                    if (watchId) {
                        navigator.geolocation.clearWatch(watchId);
                        watchId = null;
                    }
                }
                $('.location-dropdown').removeClass('active');
            });

            // Track Me
            $('#trackMeItem').on('click', function() {
                if (!navigator.geolocation) {
                    showToast('❌ Geolocation not supported');
                    return;
                }

                if (!isTracking) {
                    isTracking = true;
                    routePoints = [];
                    const $badge = $('#trackMeBadge');
                    const $btn = $('#locationToggleBtn');
                    $badge.text('ON').addClass('tracking');
                    $btn.addClass('tracking');
                    showToast('📍 Tracking started');

                    navigator.geolocation.getCurrentPosition(
                        function(pos) {
                            updatePosition(pos);
                            if (!watchId) {
                                watchId = navigator.geolocation.watchPosition(
                                    function(newPos) {
                                        updatePosition(newPos);
                                    },
                                    function(error) {
                                        console.error('Track error:', error);
                                        showToast('❌ Tracking error: ' + error.message);
                                    }, {
                                        enableHighAccuracy: true,
                                        timeout: 10000,
                                        maximumAge: 0
                                    }
                                );
                            }
                        },
                        function(error) {
                            console.error('Get position error:', error);
                            showToast('❌ Error starting tracking: ' + error.message);
                            isTracking = false;
                            $badge.text('OFF').removeClass('tracking');
                            $btn.removeClass('tracking');
                        }, {
                            enableHighAccuracy: true,
                            timeout: 10000
                        }
                    );
                } else {
                    isTracking = false;
                    const $badge = $('#trackMeBadge');
                    const $btn = $('#locationToggleBtn');
                    $badge.text('OFF').removeClass('tracking');
                    $btn.removeClass('tracking');
                    showToast('⏹️ Tracking stopped');
                    if (watchId && !isLiveLocation) {
                        navigator.geolocation.clearWatch(watchId);
                        watchId = null;
                    }
                }
                $('.location-dropdown').removeClass('active');
            });

            // Clear Route
            $('#clearRouteItem').on('click', function() {
                if (watchId && !isLiveLocation && !isTracking) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                }

                // Clear route line
                if (routeLine) {
                    routeLayer.getSource().removeFeature(routeLine);
                    routeLine = null;
                }
                routePoints = [];

                // Clear position
                if (positionFeature) {
                    positionLayer.getSource().removeFeature(positionFeature);
                    positionFeature = null;
                }

                // Reset states
                isLiveLocation = false;
                isTracking = false;
                $('#liveLocationBadge').text('OFF').removeClass('active');
                $('#trackMeBadge').text('OFF').removeClass('tracking');
                $('#locationToggleBtn').removeClass('active-location tracking');

                // Clear destination
                if (destinationMarker) {
                    destinationLayer.getSource().removeFeature(destinationMarker);
                    destinationMarker = null;
                }

                $('#directionControls').removeClass('active');
                showToast('🧹 Cleared all location data');
                $('.location-dropdown').removeClass('active');
            });

            // ─── SEARCH FUNCTIONALITY ───

            // Quick Search
            $('#gisSearchInput').on('keyup', function() {
                const value = $(this).val().toLowerCase().trim();
                const results = $('#searchResults');

                if (!value || value.length < 1) {
                    results.html('');
                    return;
                }

                const matches = searchIndex.filter(item =>
                    item.searchText && item.searchText.includes(value)
                );

                if (!matches.length) {
                    results.html('<div class="p-3 text-center text-muted">No results found</div>');
                    return;
                }

                let html = '<div class="dropdown-header">Results (' + matches.length + ')</div>';
                matches.slice(0, 15).forEach(item => {
                    const icon = item.geometryType === 'polygon' ? 'pentagon' :
                        item.geometryType === 'line' ? 'vector-pen' : 'geo-alt';
                    const details = [];
                    if (item.assessment) details.push('Assess: ' + item.assessment);
                    if (item.owner_name) details.push('Owner: ' + item.owner_name);
                    if (item.phone_number) details.push('Phone: ' + item.phone_number);

                    html += `
                        <div class="search-result-item" data-id="${item.id}" data-type="${item.type}">
                            <div class="search-result-title"><i class="bi bi-${icon} me-2"></i>${item.title}</div>
                            <div class="search-result-subtitle">${item.subtitle}</div>
                            ${details.length ? '<div class="search-result-subtitle" style="color:#666;">' + details.join(' | ') + '</div>' : ''}
                            <div class="search-result-actions">
                                <button class="btn btn-sm btn-success zoom-btn" data-id="${item.id}" data-type="${item.type}">Zoom</button>
                            </div>
                        </div>
                    `;
                });
                results.html(html);
            });

            // Zoom button in search results
            $(document).on('click', '.zoom-btn', function(e) {
                e.stopPropagation();
                const id = $(this).data('id');
                const type = $(this).data('type');
                const item = searchIndex.find(i => i.id == id && i.type === type);
                if (item) {
                    zoomToFeature(item);
                } else {
                    showToast('❌ Item not found in search index');
                }
            });

            // Search result item click
            $(document).on('click', '.search-result-item', function() {
                const id = $(this).data('id');
                const type = $(this).data('type');
                const item = searchIndex.find(i => i.id == id && i.type === type);
                if (item) {
                    zoomToFeature(item);
                    $('.search-dropdown').removeClass('active');
                    $('#gisSearchInput').val('');
                    $('#searchResults').html('');
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

            // ─── FILTER FUNCTIONALITY ───

            // Filter Search - Uses searchIndex with multiple criteria
            $('#applyFilterBtn').on('click', function() {
                const assessment = $('#filterAssessment').val().toLowerCase().trim();
                const oldAssessment = $('#filterOldAssessment').val().toLowerCase().trim();
                const ownerName = $('#filterOwnerName').val().toLowerCase().trim();
                const phoneNumber = $('#filterPhoneNumber').val().toLowerCase().trim();

                // Check if any filter is applied
                if (!assessment && !oldAssessment && !ownerName && !phoneNumber) {
                    showToast('⚠️ Please enter at least one filter criteria');
                    return;
                }

                // Filter using searchIndex
                let matches = searchIndex.filter(item => {
                    let match = true;

                    if (assessment) {
                        const itemAssessment = (item.assessment || '').toString().toLowerCase();
                        match = match && itemAssessment.includes(assessment);
                    }
                    if (oldAssessment) {
                        const itemOldAssessment = (item.old_assessment || '').toString()
                            .toLowerCase();
                        match = match && itemOldAssessment.includes(oldAssessment);
                    }
                    if (ownerName) {
                        const itemOwner = (item.owner_name || '').toString().toLowerCase();
                        match = match && itemOwner.includes(ownerName);
                    }
                    if (phoneNumber) {
                        const itemPhone = (item.phone_number || '').toString().toLowerCase();
                        match = match && itemPhone.includes(phoneNumber);
                    }

                    return match;
                });

                const results = $('#filterResults');

                if (matches.length === 0) {
                    results.html('<div class="p-3 text-center text-muted">No matching records found</div>');
                    showToast('❌ No results found');
                    return;
                }

                let html = '<div class="dropdown-header">Results (' + matches.length + ' found)</div>';
                matches.slice(0, 15).forEach(item => {
                    const icon = item.geometryType === 'polygon' ? 'pentagon' :
                        item.geometryType === 'line' ? 'vector-pen' : 'geo-alt';
                    const details = [];
                    if (item.assessment) details.push('Assess: ' + item.assessment);
                    if (item.owner_name) details.push('Owner: ' + item.owner_name);
                    if (item.phone_number) details.push('Phone: ' + item.phone_number);

                    html += `
                        <div class="search-result-item" data-id="${item.id}" data-type="${item.type}">
                            <div class="search-result-title"><i class="bi bi-${icon} me-2"></i>${item.title}</div>
                            <div class="search-result-subtitle">${item.subtitle}</div>
                            ${details.length ? '<div class="search-result-subtitle" style="color:#666;">' + details.join(' | ') + '</div>' : ''}
                            <div class="search-result-actions">
                                <button class="btn btn-sm btn-success zoom-btn" data-id="${item.id}" data-type="${item.type}">Zoom</button>
                            </div>
                        </div>
                    `;
                });
                results.html(html);
                showToast('✅ Found ' + matches.length + ' results');
            });

            // Enter key for filter search
            $('#filterAssessment, #filterOldAssessment, #filterOwnerName, #filterPhoneNumber').on('keypress',
                function(e) {
                    if (e.which === 13) {
                        $('#applyFilterBtn').click();
                    }
                });

            // Enter key for quick search
            $('#gisSearchInput').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $(this).trigger('keyup');
                    const firstResult = $('.search-result-item').first();
                    if (firstResult.length) {
                        firstResult.click();
                    }
                }
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

            console.log('✅ GIS Dashboard initialized successfully!');
            console.log('📊 Search Index Size:', searchIndex.length);
            console.log('📊 Polygons:', polygons.length);
            console.log('📊 Lines:', lines.length);
            console.log('📊 Point Data:', pointDatas.length);
        });
    </script>
@endpush

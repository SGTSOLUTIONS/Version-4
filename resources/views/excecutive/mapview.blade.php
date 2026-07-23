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

        /* Control Stack */
        .map-controls-stack {
            position: absolute;
            right: 30px;
            top: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        }

        .map-controls-stack>div {
            position: relative;
        }

        /* Toggle Button Styles */
        .layer-toggle-btn,
        .location-toggle-btn,
        .search-toggle-btn,
        .label-toggle-btn,
        .legend-toggle-btn,
        .threed-toggle-btn,
        .filter-toggle-btn {
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
        .threed-toggle-btn:hover,
        .filter-toggle-btn:hover {
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

        .filter-toggle-btn.active-filter {
            color: #0d6efd;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        /* Dropdown Styles */
        .layer-dropdown,
        .location-dropdown,
        .search-dropdown,
        .filter-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 0px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            padding: 8px 0;
            max-height: 500px;
            overflow-y: auto;
            z-index: 1001;
        }

        .layer-dropdown { width: 260px; }
        .location-dropdown { width: 240px; }
        .search-dropdown { width: 380px; }
        .filter-dropdown {
            width: 380px;
            max-height: 90vh;
            padding: 12px 0;
        }

        .layer-dropdown.active,
        .location-dropdown.active,
        .search-dropdown.active,
        .filter-dropdown.active {
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

        /* Fullscreen Buttons */
        .fullscreen-btn,
        .fullscreen-btn-exit {
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

        .fullscreen-btn-exit {
            display: none;
        }

        .fullscreen-btn:hover,
        .fullscreen-btn-exit:hover {
            background: #f0f0f0;
            transform: scale(1.05);
        }

        /* Direction Controls */
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

        /* Filter Styles */
        .filter-section {
            padding: 8px 16px;
        }

        .filter-section-header {
            font-size: 11px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .filter-options {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        .filter-options.scrollable-options {
            max-height: 100px;
            overflow-y: auto;
            display: block;
        }

        .filter-options.scrollable-options .filter-option {
            display: inline-flex;
            width: 48%;
            margin: 2px 0;
        }

        .filter-option {
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .filter-option:hover {
            background: #f0f8ff;
        }

        .filter-option input[type="checkbox"] {
            width: 14px;
            height: 14px;
            cursor: pointer;
            accent-color: #0d6efd;
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

        .filter-range .form-range {
            height: 6px;
            width: 100%;
        }

        .filter-range .form-range::-webkit-slider-thumb {
            background: #0d6efd;
        }

        .amenities-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2px;
        }

        .filter-actions {
            padding: 12px 16px;
            border-top: 1px solid #e9ecef;
            background: #f8f9fa;
        }

        .filter-stats {
            font-size: 12px;
            color: #666;
            text-align: center;
            margin-top: 6px;
        }

        /* Type Badge Styles */
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

        /* Filter Field Group */
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

        /* Scrollbar Styles */
        .filter-dropdown::-webkit-scrollbar,
        .scrollable-options::-webkit-scrollbar {
            width: 4px;
        }

        .filter-dropdown::-webkit-scrollbar-thumb,
        .scrollable-options::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 2px;
        }

        .filter-dropdown::-webkit-scrollbar-thumb:hover,
        .scrollable-options::-webkit-scrollbar-thumb:hover {
            background: #999;
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

            // ─── SEARCH INDEX ───
            let searchIndex = [];

            // ─── LOCATION TRACKING VARIABLES ───
            let watchId = null;
            let isTracking = false;
            let isLiveLocation = false;
            let currentPosition = null;
            let currentLocation = null;
            let positionFeature = null;
            let positionLayer = null;
            let routeLine = null;
            let routeLayer = null;
            let routePoints = [];
            let destinationMarker = null;
            let destinationLayer = null;
            let trackInterval = null;

            // ─── STYLES ───
            function createPolygonStyle(feature) {
                const gisid = feature.get('gisid');
                const sqft = feature.get('sqfeet') || '0';
                const polygonData = polygonDatas.find(d => d.gisid == gisid);

                const isFlagged = !!polygonData;
                const strokeColor = isFlagged ? '#dc3545' : '#0d6efd';
                const fillColor = isFlagged ? 'rgba(220, 53, 69, 0.15)' : 'rgba(13, 110, 253, 0.15)';

                const showLabels = $('#labelToggleBtn').hasClass('active-label');

                try {
                    const styles = [
                        new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: strokeColor,
                                width: 4,
                                lineJoin: 'round',
                                lineCap: 'round'
                            }),
                            fill: new ol.style.Fill({
                                color: fillColor
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
                            color: '#0d6efd',
                            width: 4
                        }),
                        fill: new ol.style.Fill({
                            color: 'rgba(13, 110, 253, 0.15)'
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
                            searchText: `${poly.gisid} ${poly.assessment || ''} ${poly.old_assessment || ''} ${poly.owner_name || ''} ${poly.phone_number || ''} ${poly.sqfeet || ''}`
                                .toLowerCase()
                        });
                    } catch (e) {
                        console.error('Error indexing polygon:', e);
                    }
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
                    } catch (e) {
                        console.error('Error indexing line:', e);
                    }
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
                    } catch (e) {
                        console.error('Error parsing point:', e);
                    }
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
            $mapContainer.append(`<div class="map-controls-stack" id="mapControlsStack"></div>`);
            const $stack = $('#mapControlsStack');

            // ─── CONTROLS INJECTION ───

            // 1. LAYER SWITCHER
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

            // 2. LOCATION SWITCHER
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
                        <div class="location-dropdown-item" id="trackMeItem">
                            <div class="location-item-icon"><i class="bi bi-broadcast"></i></div>
                            <div class="location-item-name">Track Me</div>
                            <div class="location-item-badge" id="trackMeBadge">OFF</div>
                        </div>
                        <div class="location-dropdown-item" id="zoomToExtentItem">
                            <div class="location-item-icon"><i class="bi bi-arrows-angle-expand"></i></div>
                            <div class="location-item-name">Zoom to Extent</div>
                        </div>
                        <div class="location-dropdown-item" id="clearRouteItem">
                            <div class="location-item-icon"><i class="bi bi-x-circle"></i></div>
                            <div class="location-item-name">Clear Route</div>
                        </div>
                    </div>
                </div>
                <div class="location-toast" id="locationToast"></div>
            `);

            // 3. SEARCH SWITCHER
            $stack.append(`
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

            // 4. LABEL TOGGLE
            $stack.append(`
                <div class="custom-label-toggle">
                    <button class="label-toggle-btn active-label" id="labelToggleBtn" title="Toggle Labels">
                        <i class="bi bi-fonts"></i>
                    </button>
                </div>
            `);

            // 5. LEGEND TOGGLE
            $stack.append(`
                <div class="custom-legend-toggle">
                    <button class="legend-toggle-btn" id="legendToggleBtn" title="Toggle Infrastructure Legend">
                        <i class="bi bi-list-ul"></i>
                    </button>
                </div>
            `);

            // 6. 3D TOGGLE
            $stack.append(`
                <div class="custom-3d-toggle">
                    <button class="threed-toggle-btn" id="threeDToggleBtn" title="Toggle 3D View">
                        <i class="bi bi-box"></i>
                    </button>
                </div>
            `);

            // 7. FILTER TOGGLE
            $stack.append(`
                <div class="custom-filter-toggle">
                    <button class="filter-toggle-btn" id="filterToggleBtn" title="Toggle Filters">
                        <i class="bi bi-funnel"></i>
                    </button>
                    <div class="filter-dropdown" id="filterDropdown">
                        <div class="dropdown-header">🔍 Filter Features</div>

                        <!-- Building Usage Filter -->
                        <div class="filter-section">
                            <div class="filter-section-header">Building Usage</div>
                            <div class="filter-options" id="usageFilter">
                                <label class="filter-option"><input type="checkbox" value="RESIDENTIAL" checked> Residential</label>
                                <label class="filter-option"><input type="checkbox" value="COMMERCIAL" checked> Commercial</label>
                                <label class="filter-option"><input type="checkbox" value="INDUSTRIAL" checked> Industrial</label>
                                <label class="filter-option"><input type="checkbox" value="INSTITUTIONAL" checked> Institutional</label>
                                <label class="filter-option"><input type="checkbox" value="MIXED" checked> Mixed</label>
                                <label class="filter-option"><input type="checkbox" value="GOVERNMENT" checked> Government</label>
                                <label class="filter-option"><input type="checkbox" value="VACANT" checked> Vacant</label>
                            </div>
                        </div>

                        <div class="dropdown-divider"></div>

                        <!-- Area Range Filter -->
                        <div class="filter-section">
                            <div class="filter-section-header">Area Range (sqft)</div>
                            <div class="filter-range">
                                <div class="range-inputs">
                                    <input type="number" id="minArea" class="form-control form-control-sm" placeholder="Min" value="0">
                                    <span class="range-separator">to</span>
                                    <input type="number" id="maxArea" class="form-control form-control-sm" placeholder="Max" value="10000">
                                </div>
                                <input type="range" id="areaRange" class="form-range" min="0" max="10000" step="100" value="5000">
                            </div>
                        </div>

                        <div class="dropdown-divider"></div>

                        <!-- Zonation Filter -->
                        <div class="filter-section">
                            <div class="filter-section-header">Zonation</div>
                            <div class="filter-options" id="zoneFilter">
                                <label class="filter-option"><input type="checkbox" value="ZONE-A" checked> Zone A</label>
                                <label class="filter-option"><input type="checkbox" value="ZONE-B" checked> Zone B</label>
                                <label class="filter-option"><input type="checkbox" value="ZONE-C" checked> Zone C</label>
                                <label class="filter-option"><input type="checkbox" value="ZONE-D" checked> Zone D</label>
                                <label class="filter-option"><input type="checkbox" value="ZONE-E" checked> Zone E</label>
                            </div>
                        </div>

                        <div class="dropdown-divider"></div>

                        <!-- Construction Type Filter -->
                        <div class="filter-section">
                            <div class="filter-section-header">Construction Type</div>
                            <div class="filter-options" id="constructionFilter">
                                <label class="filter-option"><input type="checkbox" value="PERMANENT" checked> Permanent</label>
                                <label class="filter-option"><input type="checkbox" value="SEMI_PERMANENT" checked> Semi Permanent</label>
                                <label class="filter-option"><input type="checkbox" value="VACANT_LAND" checked> Vacant Land</label>
                                <label class="filter-option"><input type="checkbox" value="SHED" checked> Shed</label>
                                <label class="filter-option"><input type="checkbox" value="CAR_SHED" checked> Car Shed</label>
                                <label class="filter-option"><input type="checkbox" value="TEMPORARY" checked> Temporary</label>
                            </div>
                        </div>

                        <div class="dropdown-divider"></div>

                        <!-- Building Type Filter -->
                        <div class="filter-section">
                            <div class="filter-section-header">Building Type</div>
                            <div class="filter-options scrollable-options" id="buildingTypeFilter">
                                <label class="filter-option"><input type="checkbox" value="Independent" checked> Independent</label>
                                <label class="filter-option"><input type="checkbox" value="Flat" checked> Flat</label>
                                <label class="filter-option"><input type="checkbox" value="Kalyana_Mandapam" checked> Kalyana Mandapam</label>
                                <label class="filter-option"><input type="checkbox" value="Hotel" checked> Hotel</label>
                                <label class="filter-option"><input type="checkbox" value="Cinema_Theatre" checked> Cinema Theatre</label>
                                <label class="filter-option"><input type="checkbox" value="Central_Government_Building" checked> Central Govt</label>
                                <label class="filter-option"><input type="checkbox" value="State_Government_Building" checked> State Govt</label>
                                <label class="filter-option"><input type="checkbox" value="Municipality_Corporation" checked> Municipality</label>
                                <label class="filter-option"><input type="checkbox" value="Educational_Institution" checked> Educational</label>
                                <label class="filter-option"><input type="checkbox" value="Hospital" checked> Hospital</label>
                                <label class="filter-option"><input type="checkbox" value="Commercial_Complex" checked> Commercial Complex</label>
                                <label class="filter-option"><input type="checkbox" value="Shop" checked> Shop</label>
                                <label class="filter-option"><input type="checkbox" value="Office" checked> Office</label>
                                <label class="filter-option"><input type="checkbox" value="Temple" checked> Temple</label>
                                <label class="filter-option"><input type="checkbox" value="Mosque" checked> Mosque</label>
                                <label class="filter-option"><input type="checkbox" value="Church" checked> Church</label>
                                <label class="filter-option"><input type="checkbox" value="Amma_Unavagam" checked> Amma Unavagam</label>
                                <label class="filter-option"><input type="checkbox" value="Public_Toilet" checked> Public Toilet</label>
                                <label class="filter-option"><input type="checkbox" value="Vacant Land" checked> Vacant Land</label>
                                <label class="filter-option"><input type="checkbox" value="Under Construction" checked> Under Construction</label>
                                <label class="filter-option"><input type="checkbox" value="Others" checked> Others</label>
                            </div>
                        </div>

                        <div class="dropdown-divider"></div>

                        <!-- Amenities Filters -->
                        <div class="filter-section">
                            <div class="filter-section-header">Amenities</div>
                            <div class="filter-options amenities-grid" id="amenitiesFilter">
                                <label class="filter-option"><input type="checkbox" value="liftroom" checked> Lift</label>
                                <label class="filter-option"><input type="checkbox" value="headroom" checked> Head Room</label>
                                <label class="filter-option"><input type="checkbox" value="overhead_tank" checked> Overhead Tank</label>
                                <label class="filter-option"><input type="checkbox" value="rainwater_harvesting" checked> Rainwater Harvesting</label>
                                <label class="filter-option"><input type="checkbox" value="parking" checked> Parking</label>
                                <label class="filter-option"><input type="checkbox" value="ramp" checked> Ramp</label>
                                <label class="filter-option"><input type="checkbox" value="hoarding" checked> Hoarding</label>
                                <label class="filter-option"><input type="checkbox" value="cctv" checked> CCTV</label>
                                <label class="filter-option"><input type="checkbox" value="cell_tower" checked> Cell Tower</label>
                                <label class="filter-option"><input type="checkbox" value="solar_panel" checked> Solar Panel</label>
                                <label class="filter-option"><input type="checkbox" value="water_connection" checked> Water Connection</label>
                            </div>
                        </div>

                        <div class="dropdown-divider"></div>

                        <!-- UGD Status Filter -->
                        <div class="filter-section">
                            <div class="filter-section-header">UGD Status</div>
                            <div class="filter-options scrollable-options" id="ugdFilter">
                                <label class="filter-option"><input type="checkbox" value="No_Connection" checked> No Connection</label>
                                <label class="filter-option"><input type="checkbox" value="Manhole_Available_but_Connection_Not_Given_to_House" checked> Manhole Available</label>
                                <label class="filter-option"><input type="checkbox" value="Stage_1_Completed" checked> Stage 1 Completed</label>
                                <label class="filter-option"><input type="checkbox" value="Stage_1_2_Completed" checked> Stage 1 & 2 Completed</label>
                                <label class="filter-option"><input type="checkbox" value="Stage_1_2_Completed_but_Not_Connected" checked> Stage 1 & 2 Not Connected</label>
                                <label class="filter-option"><input type="checkbox" value="Stage_1_2_3_Completed" checked> Stage 1,2 & 3 Completed</label>
                                <label class="filter-option"><input type="checkbox" value="Direct_Connection_Given" checked> Direct Connection</label>
                                <label class="filter-option"><input type="checkbox" value="1_UGD_Connection_-_3_Stage_Completed" checked> 1 UGD - 3 Stage</label>
                                <label class="filter-option"><input type="checkbox" value="2_UGD_Connection_-_3_Stage_Completed" checked> 2 UGD - 3 Stage</label>
                            </div>
                        </div>

                        <div class="dropdown-divider"></div>

                        <!-- Filter Actions -->
                        <div class="filter-actions">
                            <button class="btn btn-primary btn-sm w-100" id="applyFiltersBtn">
                                <i class="bi bi-check-circle"></i> Apply Filters
                            </button>
                            <button class="btn btn-outline-secondary btn-sm w-100 mt-2" id="resetFiltersBtn">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset All
                            </button>
                            <div class="filter-stats mt-2" id="filterStats">
                                <span>Showing: <strong id="visibleCount">0</strong> features</span>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            // 8. FULLSCREEN BUTTONS
            $mapContainer.append(`
                <button class="fullscreen-btn" id="fullscreenBtn">
                    <i class="bi bi-arrows-fullscreen"></i>
                </button>
                <button class="fullscreen-btn-exit" id="fullscreenExitBtn" style="display:none;">
                    <i class="bi bi-fullscreen-exit"></i>
                </button>
            `);

            // 9. DIRECTION CONTROLS
            $mapContainer.append(`
                <div class="direction-controls" id="directionControls">
                    <button class="btn-close-route" id="closeRouteBtn">&times;</button>
                    <div class="route-info" id="routeInfo">Getting directions...</div>
                    <div id="routeSteps"></div>
                </div>
            `);

            // ─── HELPER FUNCTIONS ───

            function showToast(message, duration = 3000) {
                const $toast = $('#locationToast');
                $toast.text(message).css({
                    'display': 'block',
                    'opacity': 0,
                    'transform': 'translateX(-50%) translateY(10px)'
                }).animate({
                    opacity: 1,
                    transform: 'translateX(-50%) translateY(0)'
                }, 200);

                clearTimeout($toast.data('timeout'));
                $toast.data('timeout', setTimeout(() => {
                    $toast.animate({
                        opacity: 0,
                        transform: 'translateX(-50%) translateY(10px)'
                    }, 200, function() {
                        $(this).css('display', 'none');
                    });
                }, duration));
            }

            function switchBaseLayer(layer) {
                [osmLayer, satelliteLayer, streetViewLayer].forEach(l => {
                    l.setVisible(l === layer);
                });
                const layerName = layer.get('title') || 'Layer';
                $('#activeLayerBadge').text(layerName);

                $('.layer-dropdown-item[data-layer-type="base"]').removeClass('active');
                $('.layer-dropdown-item[data-layer="' + layerName + '"]').addClass('active');
            }

            function toggleDroneLayer() {
                const visible = !droneLayer.getVisible();
                droneLayer.setVisible(visible);
                return visible;
            }

            function getCoordsByGisId(gisid, type = null) {
                if (!gisid) return null;

                if (type === 'line') {
                    const lineFeatures = lineSource.getFeatures().filter(f => f.get('gisid') == gisid);
                    if (lineFeatures.length > 0) {
                        try {
                            return ol.extent.getCenter(lineFeatures[0].getGeometry().getExtent());
                        } catch (e) {
                            console.error('getCoordsByGisId: line extent error', e);
                        }
                    }
                }

                if (type === 'polygon') {
                    const polyFeatures = polygonSource.getFeatures().filter(f => f.get('gisid') == gisid);
                    if (polyFeatures.length > 0) {
                        try {
                            return ol.extent.getCenter(polyFeatures[0].getGeometry().getExtent());
                        } catch (e) {
                            console.error('getCoordsByGisId: polygon extent error', e);
                        }
                    }
                }

                const point = points.find(p => p.gisid == gisid);
                if (point) {
                    try {
                        const coords = JSON.parse(point.coordinates);
                        if (Array.isArray(coords) && coords.length === 2) {
                            let lon = coords[0],
                                lat = coords[1];
                            if (coords[0] >= -90 && coords[0] <= 90 && coords[1] >= -180 && coords[1] <= 180) {
                                lon = coords[1];
                                lat = coords[0];
                            }
                            return ol.proj.fromLonLat([lon, lat]);
                        }
                    } catch (e) {
                        console.error('getCoordsByGisId: point parse error', e);
                    }
                }

                const pd = pointDatas.find(p => p.point_gisid == gisid || p.id == gisid);
                if (pd && pd.coordinates) {
                    try {
                        const coords = JSON.parse(pd.coordinates);
                        if (Array.isArray(coords) && coords.length === 2) {
                            let lon = coords[0],
                                lat = coords[1];
                            if (coords[0] >= -90 && coords[0] <= 90 && coords[1] >= -180 && coords[1] <= 180) {
                                lon = coords[1];
                                lat = coords[0];
                            }
                            return ol.proj.fromLonLat([lon, lat]);
                        }
                    } catch (e) {
                        console.error('getCoordsByGisId: pointData parse error', e);
                    }
                }

                if (type !== 'line') {
                    const polyFeatures = polygonSource.getFeatures().filter(f => f.get('gisid') == gisid);
                    if (polyFeatures.length > 0) {
                        try {
                            return ol.extent.getCenter(polyFeatures[0].getGeometry().getExtent());
                        } catch (e) {
                            console.error('getCoordsByGisId: polygon extent error', e);
                        }
                    }
                }

                if (type !== 'polygon') {
                    const lineFeatures = lineSource.getFeatures().filter(f => f.get('gisid') == gisid);
                    if (lineFeatures.length > 0) {
                        try {
                            return ol.extent.getCenter(lineFeatures[0].getGeometry().getExtent());
                        } catch (e) {
                            console.error('getCoordsByGisId: line extent error', e);
                        }
                    }
                }

                return null;
            }

            function zoomToFeature(item) {
                if (!item) {
                    showToast('❌ Invalid item', 3000);
                    return;
                }

                let coords = null;
                const gisid = item.id || item.point_gisid;

                if (item.type === 'line') {
                    const feature = lineSource.getFeatureById(gisid);
                    if (feature) {
                        coords = ol.extent.getCenter(feature.getGeometry().getExtent());
                    } else {
                        const features = lineSource.getFeatures().filter(f => f.get('gisid') == gisid);
                        if (features.length > 0) {
                            coords = ol.extent.getCenter(features[0].getGeometry().getExtent());
                        }
                    }

                    if (!coords) {
                        const polyFeatures = polygonSource.getFeatures().filter(f => f.get('gisid') == gisid);
                        if (polyFeatures.length > 0) {
                            coords = ol.extent.getCenter(polyFeatures[0].getGeometry().getExtent());
                        }
                    }
                } else {
                    const polyFeatures = polygonSource.getFeatures().filter(f => f.get('gisid') == gisid);
                    if (polyFeatures.length > 0) {
                        coords = ol.extent.getCenter(polyFeatures[0].getGeometry().getExtent());
                    }
                }

                if (!coords) {
                    showToast(`⚠️ No location found for GIS ID: ${gisid}`, 3000);
                    return;
                }

                map.getView().animate({
                    center: coords,
                    zoom: 20,
                    duration: 1000
                });
            }

            function zoomToExtent() {
                map.getView().fit(imageExtent, {
                    padding: [50, 50, 50, 50],
                    duration: 1000,
                    maxZoom: 20
                });
                showToast('📍 Zoomed to ward extent', 2000);
            }

            function updatePosition(position, autoCenter = false) {
                const lon = position.coords.longitude;
                const lat = position.coords.latitude;
                const projected = ol.proj.fromLonLat([lon, lat]);
                currentPosition = projected;
                currentLocation = {
                    lon,
                    lat
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

                if (autoCenter && isTracking) {
                    map.getView().animate({
                        center: projected,
                        zoom: 19,
                        duration: 500
                    });
                }

                if (isTracking) {
                    routePoints.push(projected);
                    updateRouteLine();
                }
            }

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

            function getCurrentLocation(callback) {
                if (currentLocation) {
                    callback(currentLocation);
                    return;
                }
                if (!navigator.geolocation) {
                    callback(null);
                    return;
                }
                navigator.geolocation.getCurrentPosition(
                    function(pos) {
                        currentLocation = {
                            lon: pos.coords.longitude,
                            lat: pos.coords.latitude
                        };
                        callback(currentLocation);
                    },
                    function() {
                        callback(null);
                    }, {
                        enableHighAccuracy: true,
                        timeout: 10000
                    }
                );
            }

            function getDirectionToFeature(feature) {
                if (!feature) {
                    showToast('❌ Invalid feature for directions', 3000);
                    return;
                }

                getCurrentLocation(function(loc) {
                    if (!loc) {
                        Swal.fire('Location Error',
                            'Could not get your location. Please enable GPS and try again.',
                            'error');
                        return;
                    }
                    calculateDirection(loc, feature);
                });
            }

            function calculateDirection(loc, feature) {
                if (!loc || !feature) {
                    showToast('❌ Missing location or feature data', 3000);
                    return;
                }

                const gisid = feature.id || feature.point_gisid;
                if (!gisid) {
                    Swal.fire('Error', 'No GIS ID found for this feature', 'error');
                    return;
                }

                const coords = getCoordsByGisId(gisid, feature.type);
                if (!coords) {
                    Swal.fire('Error', `No coordinates found for GIS ID: ${gisid}`, 'error');
                    return;
                }

                const lonLat = ol.proj.toLonLat(coords);
                const destLon = lonLat[0];
                const destLat = lonLat[1];

                if (destLon < -180 || destLon > 180 || destLat < -90 || destLat > 90) {
                    Swal.fire('Error', 'Converted coordinates are out of valid range. Check your data projection.',
                        'error');
                    return;
                }

                console.log(`Routing to GIS ID ${gisid} (${feature.type}): lon=${destLon}, lat=${destLat}`);
                getRoute(loc.lon, loc.lat, destLon, destLat);
            }

            function getRoute(startLon, startLat, endLon, endLat) {
                $('#directionControls').addClass('active');
                $('#routeInfo').text('Getting directions...');
                $('#routeSteps').html('');

                const url = `https://router.project-osrm.org/route/v1/driving/` +
                    `${startLon},${startLat};${endLon},${endLat}` +
                    `?overview=full&geometries=geojson&steps=true`;

                fetch(url)
                    .then(res => {
                        if (!res.ok) {
                            throw new Error(`HTTP error! status: ${res.status}`);
                        }
                        return res.json();
                    })
                    .then(data => {
                        if (!data.routes || !data.routes.length) {
                            $('#routeInfo').text('No route could be found.');
                            return;
                        }

                        const route = data.routes[0];
                        const km = (route.distance / 1000).toFixed(2);
                        const mins = Math.round(route.duration / 60);
                        $('#routeInfo').html(`<strong>${km} km</strong> · about ${mins} min`);

                        routeLayer.getSource().clear();
                        destinationLayer.getSource().clear();

                        const lineCoords = route.geometry.coordinates.map(c => ol.proj.fromLonLat(c));
                        const routeFeature = new ol.Feature({
                            geometry: new ol.geom.LineString(lineCoords)
                        });
                        routeFeature.setStyle(new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#0d6efd',
                                width: 5
                            })
                        }));
                        routeLayer.getSource().addFeature(routeFeature);

                        const destMarker = new ol.Feature({
                            geometry: new ol.geom.Point(ol.proj.fromLonLat([endLon, endLat]))
                        });
                        destMarker.setStyle(createDestinationStyle());
                        destinationLayer.getSource().addFeature(destMarker);

                        map.getView().fit(new ol.geom.LineString(lineCoords).getExtent(), {
                            padding: [80, 80, 80, 80],
                            duration: 800,
                            maxZoom: 19
                        });

                        let stepsHtml = '';
                        const legs = route.legs || [];
                        legs.forEach(leg => {
                            (leg.steps || []).forEach(step => {
                                const instruction = step.name ?
                                    `${step.maneuver.type} onto ${step.name}` :
                                    step.maneuver.type;
                                const dist = Math.round(step.distance);
                                stepsHtml +=
                                    `<div class="step-item">${instruction} — ${dist} m</div>`;
                            });
                        });
                        $('#routeSteps').html(stepsHtml ||
                            '<div class="step-item">No turn-by-turn details available.</div>');
                    })
                    .catch(err => {
                        console.error('getRoute error:', err);
                        $('#routeInfo').text('Error getting directions. Please try again.');
                        showToast('❌ Route error: ' + err.message, 4000);
                    });
            }

            function clearAllRouteState() {
                if (trackInterval) {
                    clearInterval(trackInterval);
                    trackInterval = null;
                }

                if (watchId && !isLiveLocation && !isTracking) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                }

                if (routeLine) {
                    routeLayer.getSource().removeFeature(routeLine);
                    routeLine = null;
                }
                routeLayer.getSource().clear();
                routePoints = [];

                if (positionFeature) {
                    positionLayer.getSource().removeFeature(positionFeature);
                    positionFeature = null;
                }

                isLiveLocation = false;
                isTracking = false;
                $('#liveLocationBadge').text('OFF').removeClass('active');
                $('#trackMeBadge').text('OFF').removeClass('tracking');
                $('#locationToggleBtn').removeClass('active-location tracking');

                if (destinationMarker) {
                    destinationLayer.getSource().removeFeature(destinationMarker);
                    destinationMarker = null;
                }

                $('#directionControls').removeClass('active');
            }

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
                    (item.subtitle && item.subtitle.toLowerCase().includes(v)) ||
                    (item.point_gisid && item.point_gisid.toString().toLowerCase().includes(v))
                );
            }

            // ─── FILTER FUNCTIONS ───

            function getSelectedCheckboxValues(containerSelector) {
                const values = [];
                $(containerSelector).find('input[type="checkbox"]:checked').each(function() {
                    values.push($(this).val());
                });
                return values;
            }

            function applyFilters() {
                const selectedUsage = getSelectedCheckboxValues('#usageFilter');
                const selectedZones = getSelectedCheckboxValues('#zoneFilter');
                const selectedConstruction = getSelectedCheckboxValues('#constructionFilter');
                const selectedBuildingTypes = getSelectedCheckboxValues('#buildingTypeFilter');
                const selectedAmenities = getSelectedCheckboxValues('#amenitiesFilter');
                const selectedUgd = getSelectedCheckboxValues('#ugdFilter');
                const minArea = parseInt($('#minArea').val()) || 0;
                const maxArea = parseInt($('#maxArea').val()) || 10000;

                const allUsageSelected = selectedUsage.length === 7;
                const allZonesSelected = selectedZones.length === 5;
                const allConstructionSelected = selectedConstruction.length === 6;
                const allBuildingTypesSelected = selectedBuildingTypes.length === 21;
                const allAmenitiesSelected = selectedAmenities.length === 11;
                const allUgdSelected = selectedUgd.length === 9;
                const areaDefault = minArea === 0 && maxArea === 10000;

                const anyFilterActive = !allUsageSelected || !allZonesSelected || !allConstructionSelected ||
                    !allBuildingTypesSelected || !allAmenitiesSelected || !allUgdSelected || !areaDefault;

                if (!anyFilterActive) {
                    resetAllFilters(true);
                    showToast('ℹ️ All filters reset - showing all features', 2000);
                    return;
                }

                const allFeatures = polygonSource.getFeatures();
                let visibleCount = 0;

                allFeatures.forEach(feature => {
                    const gisid = feature.get('gisid');
                    const sqfeet = parseFloat(feature.get('sqfeet')) || 0;
                    const buildingData = polygonDatas.find(d => d.gisid == gisid);

                    let passesFilters = true;

                    if (!(sqfeet >= minArea && sqfeet <= maxArea)) {
                        passesFilters = false;
                    }

                    if (passesFilters && buildingData) {
                        const usage = buildingData.building_usage || '';
                        if (selectedUsage.length > 0 && !selectedUsage.includes(usage)) {
                            passesFilters = false;
                        }
                    } else if (passesFilters && !buildingData) {
                        if (selectedUsage.length < 7) {
                            passesFilters = false;
                        }
                    }

                    if (passesFilters && buildingData) {
                        const zone = buildingData.zone || buildingData.building_zone || '';
                        if (selectedZones.length > 0 && !selectedZones.includes(zone)) {
                            passesFilters = false;
                        }
                    } else if (passesFilters && !buildingData) {
                        if (selectedZones.length < 5) {
                            passesFilters = false;
                        }
                    }

                    if (passesFilters && buildingData) {
                        const constructionType = buildingData.construction_type || '';
                        if (selectedConstruction.length > 0 && !selectedConstruction.includes(constructionType)) {
                            passesFilters = false;
                        }
                    } else if (passesFilters && !buildingData) {
                        if (selectedConstruction.length < 6) {
                            passesFilters = false;
                        }
                    }

                    if (passesFilters && buildingData) {
                        const buildingType = buildingData.building_type || '';
                        if (selectedBuildingTypes.length > 0 && !selectedBuildingTypes.includes(buildingType)) {
                            passesFilters = false;
                        }
                    } else if (passesFilters && !buildingData) {
                        if (selectedBuildingTypes.length < 21) {
                            passesFilters = false;
                        }
                    }

                    if (passesFilters && buildingData && selectedAmenities.length > 0) {
                        const hasAllAmenities = selectedAmenities.every(amenity => {
                            const value = buildingData[amenity];
                            return value === 'Yes' || value === true || value === 1 ||
                                (typeof value === 'string' && value.toLowerCase() === 'yes');
                        });
                        if (!hasAllAmenities) {
                            passesFilters = false;
                        }
                    } else if (passesFilters && !buildingData) {
                        if (selectedAmenities.length < 11) {
                            passesFilters = false;
                        }
                    }

                    if (passesFilters && buildingData) {
                        const ugdStatus = buildingData.ugd || '';
                        if (selectedUgd.length > 0 && !selectedUgd.includes(ugdStatus)) {
                            passesFilters = false;
                        }
                    } else if (passesFilters && !buildingData) {
                        if (selectedUgd.length < 9) {
                            passesFilters = false;
                        }
                    }

                    if (passesFilters) {
                        feature.setStyle(createPolygonStyle(feature));
                        visibleCount++;
                    } else {
                        feature.setStyle(new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: 'rgba(200,200,200,0.2)',
                                width: 1
                            }),
                            fill: new ol.style.Fill({
                                color: 'rgba(200,200,200,0.05)'
                            })
                        }));
                    }
                });

                $('#visibleCount').text(visibleCount);
                const total = allFeatures.length;
                $('#filterStats').html(`Showing: <strong>${visibleCount}</strong> of <strong>${total}</strong> features`);

                polygonLayer.changed();
                polygonSource.changed();

                const hiddenCount = total - visibleCount;
                if (hiddenCount > 0) {
                    showToast(`🔍 Filter applied: ${visibleCount} visible, ${hiddenCount} hidden`, 3000);
                } else {
                    showToast(`✅ All ${visibleCount} features match the selected filters`, 2000);
                }
            }

            function resetAllFilters(silent = false) {
                $('#usageFilter, #zoneFilter, #constructionFilter, #buildingTypeFilter, #amenitiesFilter, #ugdFilter')
                    .find('input[type="checkbox"]')
                    .prop('checked', true);

                $('#minArea').val(0);
                $('#maxArea').val(10000);
                $('#areaRange').val(5000);

                const allFeatures = polygonSource.getFeatures();
                allFeatures.forEach(feature => {
                    feature.setStyle(createPolygonStyle(feature));
                });

                $('#visibleCount').text(allFeatures.length);
                $('#filterStats').html(`Showing: <strong>${allFeatures.length}</strong> of <strong>${allFeatures.length}</strong> features`);

                polygonLayer.changed();
                polygonSource.changed();

                if (!silent) {
                    showToast('🔄 All filters reset - all features visible', 2000);
                }
            }

            function updateFilterStats() {
                const total = polygonSource.getFeatures().length;
                let visible = 0;
                polygonSource.getFeatures().forEach(f => {
                    const style = f.getStyle();
                    if (style && typeof style === 'function') {
                        const appliedStyle = style(f);
                        if (appliedStyle && appliedStyle.getStroke &&
                            appliedStyle.getStroke() &&
                            appliedStyle.getStroke().getColor() !== 'rgba(200,200,200,0.2)') {
                            visible++;
                        }
                    } else {
                        visible++;
                    }
                });
                $('#visibleCount').text(visible);
                $('#filterStats').html(`Showing: <strong>${visible}</strong> of <strong>${total}</strong> features`);
            }

            // ─── EVENT HANDLERS ───

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
                            <div><span style="display:inline-block;width:20px;height:20px;background:rgba(13,110,253,0.15);border:2px solid #0d6efd;margin-right:10px;"></span> Polygons (Land Parcels)</div>
                            <div><span style="display:inline-block;width:20px;height:20px;background:rgba(220,53,69,0.15);border:2px solid #dc3545;margin-right:10px;"></span> Flagged Parcels</div>
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

            // ─── LOCATION EVENT HANDLERS ───

            $('#zoomToExtentItem').on('click', function() {
                zoomToExtent();
                $('.location-dropdown').removeClass('active');
            });

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
                    showToast('📍 Live location activated - showing position', 2000);

                    navigator.geolocation.getCurrentPosition(
                        function(pos) {
                            updatePosition(pos, false);
                            if (!watchId) {
                                watchId = navigator.geolocation.watchPosition(
                                    function(newPos) {
                                        updatePosition(newPos, false);
                                    },
                                    function(error) {
                                        console.error('Watch error:', error);
                                        showToast('❌ Location error: ' + error.message, 3000);
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
                            showToast('❌ Error getting location: ' + error.message, 3000);
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
                    showToast('📍 Live location deactivated', 2000);
                    if (watchId && !isTracking) {
                        navigator.geolocation.clearWatch(watchId);
                        watchId = null;
                    }
                }
                $('.location-dropdown').removeClass('active');
            });

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
                    showToast('📍 Tracking started - auto-centering every 2 seconds', 3000);

                    if (trackInterval) {
                        clearInterval(trackInterval);
                        trackInterval = null;
                    }

                    navigator.geolocation.getCurrentPosition(
                        function(pos) {
                            updatePosition(pos, true);

                            if (!watchId) {
                                watchId = navigator.geolocation.watchPosition(
                                    function(newPos) {
                                        updatePosition(newPos, true);
                                    },
                                    function(error) {
                                        console.error('Track error:', error);
                                        showToast('❌ Tracking error: ' + error.message, 3000);
                                    }, {
                                        enableHighAccuracy: true,
                                        timeout: 10000,
                                        maximumAge: 0
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

                        },
                        function(error) {
                            console.error('Get position error:', error);
                            showToast('❌ Error starting tracking: ' + error.message, 3000);
                            isTracking = false;
                            $badge.text('OFF').removeClass('tracking');
                            $btn.removeClass('tracking');
                            if (trackInterval) {
                                clearInterval(trackInterval);
                                trackInterval = null;
                            }
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
                    showToast('⏹️ Tracking stopped', 2000);

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
                clearAllRouteState();
                showToast('🧹 Cleared all location data', 2000);
                $('.location-dropdown').removeClass('active');
            });

            $(document).on('click', '#closeRouteBtn', function() {
                routeLayer.getSource().clear();
                if (destinationMarker) {
                    destinationLayer.getSource().removeFeature(destinationMarker);
                    destinationMarker = null;
                }
                $('#directionControls').removeClass('active');
            });

            // ─── SEARCH EVENT HANDLERS ───

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
                        if (item.type === 'line') {
                            badgeClass = 'road';
                            badgeText = 'Road';
                        } else if (item.type === 'polygon') {
                            badgeClass = 'parcel';
                            badgeText = 'Parcel';
                        } else if (item.type === 'point') {
                            badgeClass = 'point';
                            badgeText = 'Point';
                        } else if (item.type === 'pointdata') {
                            badgeClass = 'assessment';
                            badgeText = 'Assessment';
                        }

                        const editBtn = item.type === 'pointdata' ?
                            `<button class="btn btn-sm btn-warning edit-btn" data-id="${item.id}"><i class="bi bi-pencil"></i> Edit</button>` :
                            '';

                        html += `
                            <div class="search-result-item" data-id="${item.id}" data-type="${item.type}">
                                <div class="search-result-title">
                                    <i class="bi bi-${icon} me-2"></i>${displayTitle}
                                    <span class="type-badge ${badgeClass}">${badgeText}</span>
                                </div>
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

            $(document).on('click', '.search-result-item', function() {
                const id = $(this).data('id');
                const type = $(this).data('type');
                const item = searchIndex.find(i => i.id == id && i.type === type);
                if (item) {
                    zoomToFeature(item);
                    $('.search-dropdown').removeClass('active');
                    $('#gisSearchInput').val('');
                    $('#searchResults').html('');
                } else {
                    showToast('❌ Could not find item to zoom to', 3000);
                }
            });

            $(document).on('click', '.zoom-btn', function(e) {
                e.stopPropagation();
                const id = $(this).data('id');
                const type = $(this).data('type');

                let item = searchIndex.find(i => i.id == id && i.type === type);
                if (!item) {
                    item = searchIndex.find(i => i.point_gisid == id);
                }
                if (!item) {
                    item = searchIndex.find(i => i.id == id);
                }
                if (item) {
                    zoomToFeature(item);
                } else {
                    showToast(`❌ Could not find feature with ID: ${id}`, 3000);
                }
                $('.search-dropdown').removeClass('active');
                $('#gisSearchInput').val('');
                $('#searchResults').html('');
            });

            $(document).on('click', '.direction-btn', function(e) {
                e.stopPropagation();
                const id = $(this).data('id');
                const type = $(this).data('type');

                let item = searchIndex.find(i => i.id == id && i.type === type);
                if (!item) {
                    item = searchIndex.find(i => i.point_gisid == id);
                }
                if (!item) {
                    item = searchIndex.find(i => i.id == id);
                }

                if (item) {
                    getDirectionToFeature(item);
                    $('.search-dropdown').removeClass('active');
                    $('#gisSearchInput').val('');
                    $('#searchResults').html('');
                } else {
                    showToast(`❌ Could not find feature with ID: ${id} for directions`, 3000);
                }
            });

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

            // ─── FILTER EVENT HANDLERS ───

            $('#applyFiltersBtn').on('click', function() {
                applyFilters();
            });

            $('#resetFiltersBtn').on('click', function() {
                resetAllFilters(false);
            });

            $('#areaRange').on('input', function() {
                const val = parseInt($(this).val());
                const maxVal = parseInt($('#maxArea').val());
                if (val > maxVal) {
                    $(this).val(maxVal);
                }
                $('#minArea').val($(this).val());
            });

            $('#minArea').on('change', function() {
                let val = parseInt($(this).val()) || 0;
                const maxVal = parseInt($('#maxArea').val()) || 10000;
                if (val > maxVal) {
                    val = maxVal;
                    $(this).val(val);
                }
                $('#areaRange').val(val);
            });

            $('#maxArea').on('change', function() {
                let val = parseInt($(this).val()) || 10000;
                const minVal = parseInt($('#minArea').val()) || 0;
                if (val < minVal) {
                    val = minVal;
                    $(this).val(val);
                }
                const sliderVal = parseInt($('#areaRange').val());
                if (sliderVal > val) {
                    $('#areaRange').val(val);
                }
            });

            // ─── FILTER SEARCH EVENT HANDLERS ───

            $('#applyFilterBtn').on('click', function() {
                const assessment = $('#filterAssessment').val().toLowerCase().trim();
                const oldAssessment = $('#filterOldAssessment').val().toLowerCase().trim();
                const ownerName = $('#filterOwnerName').val().toLowerCase().trim();
                const phoneNumber = $('#filterPhoneNumber').val().toLowerCase().trim();

                if (!assessment && !oldAssessment && !ownerName && !phoneNumber) {
                    showToast('⚠️ Please enter at least one filter criteria', 3000);
                    return;
                }

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
                    showToast('❌ No results found', 2000);
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

                    let badgeClass = '';
                    let badgeText = '';
                    if (item.type === 'line') {
                        badgeClass = 'road';
                        badgeText = 'Road';
                    } else if (item.type === 'polygon') {
                        badgeClass = 'parcel';
                        badgeText = 'Parcel';
                    } else if (item.type === 'point') {
                        badgeClass = 'point';
                        badgeText = 'Point';
                    } else if (item.type === 'pointdata') {
                        badgeClass = 'assessment';
                        badgeText = 'Assessment';
                    }

                    html += `
                        <div class="search-result-item" data-id="${item.id}" data-type="${item.type}">
                            <div class="search-result-title">
                                <i class="bi bi-${icon} me-2"></i>${item.title}
                                <span class="type-badge ${badgeClass}">${badgeText}</span>
                            </div>
                            <div class="search-result-subtitle">${item.subtitle}</div>
                            ${details.length ? '<div class="search-result-subtitle" style="color:#666;">' + details.join(' | ') + '</div>' : ''}
                            <div class="search-result-actions">
                                <button class="btn btn-sm btn-success zoom-btn" data-id="${item.id}" data-type="${item.type}">Zoom</button>
                                <button class="btn btn-sm btn-primary direction-btn" data-id="${item.id}" data-type="${item.type}">Direction</button>
                            </div>
                        </div>
                    `;
                });
                results.html(html);
                showToast('✅ Found ' + matches.length + ' results', 2000);
            });

            $('#filterAssessment, #filterOldAssessment, #filterOwnerName, #filterPhoneNumber').on('keypress',
                function(e) {
                    if (e.which === 13) {
                        $('#applyFilterBtn').click();
                    }
                });

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

            // ─── INITIALIZE ───
            setTimeout(updateFilterStats, 500);

            console.log('✅ GIS Dashboard initialized successfully!');
            console.log('📊 Search Index Size:', searchIndex.length);
            console.log('📊 Polygons:', polygons.length);
            console.log('📊 Lines:', lines.length);
            console.log('📊 Point Data:', pointDatas.length);
        });
    </script>
@endpush

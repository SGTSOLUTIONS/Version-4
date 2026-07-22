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

       $(document).ready(function () {
    // =========================================================
    // DATA
    // =========================================================
    let polygons = @json($polygons ?? [], JSON_HEX_TAG);
    let lines = @json($lines ?? [], JSON_HEX_TAG);
    let points = @json($points ?? [], JSON_HEX_TAG);
    let pointDatas = @json($pointDatas ?? [], JSON_HEX_TAG);
    let polygonDatas = @json($polygonDatas ?? [], JSON_HEX_TAG);
    let ward = @json($ward ?? [], JSON_HEX_TAG);
    let analytics = @json($analytics ?? [], JSON_HEX_TAG);
    let buildingVariations = @json($buildingVariations ?? [], JSON_HEX_TAG);
    let buildingData = @json($buildingData ?? [], JSON_HEX_TAG);

    let allBuildings = buildingData.buildings || [];
    let usageCounts = buildingData.usage_counts || {};

    // =========================================================
    // STATE
    // =========================================================
    let searchIndex = [];
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
    let directionRouteFeature = null;
    let highlightLayer = null;
    let highlightSource = new ol.source.Vector();

    // =========================================================
    // EXTENT / PROJECTION
    // =========================================================
    let imageExtentRaw = [
        {{ $ward->extent_left ?? 0 }},
        {{ $ward->extent_bottom ?? 0 }},
        {{ $ward->extent_right ?? 0 }},
        {{ $ward->extent_top ?? 0 }}
    ];

    const isLatLon =
        imageExtentRaw[0] > -180 && imageExtentRaw[0] < 180 &&
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

    // =========================================================
    // HELPERS
    // =========================================================
    function safeJsonParse(value, fallback = null) {
        try {
            if (typeof value === 'string') return JSON.parse(value);
            return value;
        } catch (e) {
            return fallback;
        }
    }

    function isValidNumber(n) {
        return typeof n === 'number' && !isNaN(n) && isFinite(n);
    }

    function normalizeLonLat(coords) {
        if (!Array.isArray(coords) || coords.length < 2) return null;

        let a = Number(coords[0]);
        let b = Number(coords[1]);

        if (!isValidNumber(a) || !isValidNumber(b)) return null;

        // lon,lat
        if (a >= -180 && a <= 180 && b >= -90 && b <= 90) {
            return [a, b];
        }

        // lat,lon
        if (a >= -90 && a <= 90 && b >= -180 && b <= 180) {
            return [b, a];
        }

        return null;
    }

    function toProjectedPoint(coords) {
        const lonLat = normalizeLonLat(coords);
        if (!lonLat) return null;
        return ol.proj.fromLonLat(lonLat);
    }

    function getPointExtent(projectedPoint) {
        if (!projectedPoint) return null;
        return [projectedPoint[0], projectedPoint[1], projectedPoint[0], projectedPoint[1]];
    }

    function escapeHtml(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function showToast(message, duration = 3000) {
        const $toast = $('#locationToast');
        $toast.stop(true, true).text(message).fadeIn(200);

        const oldTimeout = $toast.data('timeout');
        if (oldTimeout) clearTimeout(oldTimeout);

        const newTimeout = setTimeout(() => {
            $toast.fadeOut(300);
        }, duration);

        $toast.data('timeout', newTimeout);
    }

    function formatDistance(meters) {
        if (!isValidNumber(meters)) return '-';
        if (meters < 1000) return `${Math.round(meters)} m`;
        return `${(meters / 1000).toFixed(2)} km`;
    }

    function formatDuration(seconds) {
        if (!isValidNumber(seconds)) return '-';
        const minutes = Math.round(seconds / 60);

        if (minutes < 60) return `${minutes} mins`;

        const hours = Math.floor(minutes / 60);
        const remaining = minutes % 60;
        return `${hours} hr ${remaining} mins`;
    }

    function uniqBy(arr, key) {
        const seen = new Set();
        return arr.filter(item => {
            const v = item[key];
            if (seen.has(v)) return false;
            seen.add(v);
            return true;
        });
    }

    // =========================================================
    // STYLES
    // =========================================================
    function createPolygonStyle(feature) {
        const gisid = feature.get('gisid') || '';
        const sqft = feature.get('sqfeet') || '0';
        const polygonData = polygonDatas.find(d => String(d.gisid) === String(gisid));
        const color = polygonData ? 'red' : 'blue';
        const showLabels = $('#labelToggleBtn').hasClass('active-label');

        const styles = [
            new ol.style.Style({
                stroke: new ol.style.Stroke({
                    color: color,
                    width: 4,
                    lineJoin: 'round',
                    lineCap: 'round'
                }),
                fill: new ol.style.Fill({
                    color: color === 'red' ? 'rgba(255,0,0,0.10)' : 'rgba(0,123,255,0.10)'
                })
            })
        ];

        if (showLabels) {
            try {
                const centerPoint = feature.getGeometry().getInteriorPoint();

                styles.push(new ol.style.Style({
                    geometry: centerPoint,
                    text: new ol.style.Text({
                        text: `${gisid}\n${sqft} SQFT`,
                        font: 'bold 13px Arial',
                        fill: new ol.style.Fill({ color: '#000' }),
                        backgroundFill: new ol.style.Fill({ color: '#fff' }),
                        backgroundStroke: new ol.style.Stroke({ color: '#000', width: 1 }),
                        padding: [4, 6, 4, 6],
                        overflow: true,
                        textAlign: 'center'
                    })
                }));
            } catch (e) {}
        }

        return styles;
    }

    function createLineStyle() {
        return new ol.style.Style({
            stroke: new ol.style.Stroke({
                color: '#ff0000',
                width: 3
            })
        });
    }

    function createPointStyle(label = 'Point') {
        return new ol.style.Style({
            image: new ol.style.Circle({
                radius: 7,
                fill: new ol.style.Fill({ color: '#198754' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 2 })
            }),
            text: new ol.style.Text({
                text: String(label),
                font: 'bold 11px Arial',
                fill: new ol.style.Fill({ color: '#111' }),
                backgroundFill: new ol.style.Fill({ color: '#fff' }),
                backgroundStroke: new ol.style.Stroke({ color: '#ccc', width: 1 }),
                padding: [2, 4, 2, 4],
                offsetY: -16
            })
        });
    }

    function createPositionStyle() {
        return new ol.style.Style({
            image: new ol.style.Circle({
                radius: 12,
                fill: new ol.style.Fill({ color: '#0d6efd' }),
                stroke: new ol.style.Stroke({ color: '#ffffff', width: 3 })
            }),
            text: new ol.style.Text({
                text: 'You',
                font: 'bold 12px Arial',
                fill: new ol.style.Fill({ color: '#000' }),
                backgroundFill: new ol.style.Fill({ color: '#fff' }),
                backgroundStroke: new ol.style.Stroke({ color: '#ccc', width: 1 }),
                padding: [2, 6, 2, 6],
                offsetY: -18,
                textAlign: 'center'
            })
        });
    }

    function createDestinationStyle(label = 'Destination') {
        return new ol.style.Style({
            image: new ol.style.Circle({
                radius: 10,
                fill: new ol.style.Fill({ color: '#dc3545' }),
                stroke: new ol.style.Stroke({ color: '#ffffff', width: 3 })
            }),
            text: new ol.style.Text({
                text: String(label),
                font: 'bold 12px Arial',
                fill: new ol.style.Fill({ color: '#000' }),
                backgroundFill: new ol.style.Fill({ color: '#fff' }),
                backgroundStroke: new ol.style.Stroke({ color: '#ccc', width: 1 }),
                padding: [2, 6, 2, 6],
                offsetY: -18,
                textAlign: 'center'
            })
        });
    }

    function createHighlightStyle(geometryType = 'point') {
        if (geometryType === 'polygon') {
            return new ol.style.Style({
                stroke: new ol.style.Stroke({
                    color: '#ffc107',
                    width: 5
                }),
                fill: new ol.style.Fill({
                    color: 'rgba(255,193,7,0.18)'
                })
            });
        }

        if (geometryType === 'line') {
            return new ol.style.Style({
                stroke: new ol.style.Stroke({
                    color: '#ffc107',
                    width: 6
                })
            });
        }

        return new ol.style.Style({
            image: new ol.style.Circle({
                radius: 10,
                fill: new ol.style.Fill({ color: '#ffc107' }),
                stroke: new ol.style.Stroke({ color: '#000', width: 2 })
            })
        });
    }

    // =========================================================
    // SOURCES
    // =========================================================
    const polygonSource = new ol.source.Vector();
    const lineSource = new ol.source.Vector();
    const pointSource = new ol.source.Vector();

    function loadPolygonSource() {
        polygonSource.clear();

        polygons.forEach(poly => {
            try {
                let coords = safeJsonParse(poly.coordinates, []);
                if (!Array.isArray(coords) || !coords.length) return;

                const feature = new ol.Feature({
                    geometry: new ol.geom.Polygon(coords),
                    gisid: poly.gisid,
                    type: 'polygon',
                    sqfeet: poly.sqfeet || 0,
                    assessment: poly.assessment || '',
                    old_assessment: poly.old_assessment || '',
                    owner_name: poly.owner_name || '',
                    phone_number: poly.phone_number || '',
                    originalData: poly
                });

                feature.setId(String(poly.gisid));
                polygonSource.addFeature(feature);
            } catch (e) {
                console.error('polygon parse error', e);
            }
        });
    }

    function loadLineSource() {
        lineSource.clear();

        lines.forEach(line => {
            try {
                let coords = safeJsonParse(line.coordinates, []);
                let geometry = null;

                if (Array.isArray(coords) && coords.length > 0) {
                    if (Array.isArray(coords[0]) && Array.isArray(coords[0][0])) {
                        geometry = new ol.geom.MultiLineString(coords);
                    } else if (Array.isArray(coords[0]) && typeof coords[0][0] === 'number') {
                        geometry = new ol.geom.LineString(coords);
                    }
                }

                if (!geometry) return;

                const feature = new ol.Feature({
                    geometry: geometry,
                    gisid: line.gisid,
                    type: 'line',
                    road_name: line.road_name || '',
                    originalData: line
                });

                feature.setId(String(line.gisid));
                lineSource.addFeature(feature);
            } catch (e) {
                console.error('line parse error', e);
            }
        });
    }

    function loadPointSource() {
        pointSource.clear();

        points.forEach(point => {
            try {
                const rawCoords = safeJsonParse(point.coordinates, null);
                const projected = toProjectedPoint(rawCoords);
                if (!projected) return;

                const feature = new ol.Feature({
                    geometry: new ol.geom.Point(projected),
                    gisid: point.gisid,
                    type: 'point',
                    originalData: point
                });

                feature.setId(`point-${point.gisid}`);
                feature.setStyle(createPointStyle(point.gisid));
                pointSource.addFeature(feature);
            } catch (e) {
                console.error('point parse error', e);
            }
        });

        pointDatas.forEach(pd => {
            try {
                const rawCoords = safeJsonParse(pd.coordinates, null);
                const projected = toProjectedPoint(rawCoords);
                if (!projected) return;

                const pointGisid = pd.point_gisid || pd.gisid || pd.id;

                const feature = new ol.Feature({
                    geometry: new ol.geom.Point(projected),
                    gisid: pointGisid,
                    point_data_id: pd.id,
                    type: 'pointdata',
                    assessment: pd.assessment || '',
                    old_assessment: pd.old_assessment || '',
                    owner_name: pd.owner_name || '',
                    phone_number: pd.phone_number || '',
                    originalData: pd
                });

                feature.setId(`pointdata-${pointGisid}-${pd.id}`);
                feature.setStyle(createPointStyle(pointGisid));
                pointSource.addFeature(feature);
            } catch (e) {
                console.error('pointdata parse error', e);
            }
        });
    }

    loadPolygonSource();
    loadLineSource();
    loadPointSource();

    // =========================================================
    // LAYERS
    // =========================================================
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
            attributions: 'Tiles © Esri'
        })
    });

    const streetViewLayer = new ol.layer.Tile({
        title: 'Street View',
        type: 'base',
        visible: false,
        source: new ol.source.XYZ({
            url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
            attributions: '© OpenStreetMap'
        })
    });

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

    highlightLayer = new ol.layer.Vector({
        source: highlightSource,
        visible: true,
        zIndex: 110
    });

    positionLayer = new ol.layer.Vector({
        source: new ol.source.Vector(),
        visible: true,
        zIndex: 120
    });

    routeLayer = new ol.layer.Vector({
        source: new ol.source.Vector(),
        visible: true,
        zIndex: 119
    });

    destinationLayer = new ol.layer.Vector({
        source: new ol.source.Vector(),
        visible: true,
        zIndex: 121
    });

    // =========================================================
    // MAP
    // =========================================================
    const map = new ol.Map({
        target: 'map',
        layers: [
            osmLayer,
            satelliteLayer,
            streetViewLayer,
            droneLayer,
            polygonLayer,
            lineLayer,
            pointLayer,
            highlightLayer,
            routeLayer,
            positionLayer,
            destinationLayer
        ],
        view: new ol.View({
            center: ol.extent.getCenter(imageExtent),
            zoom: 18
        })
    });

    const $mapContainer = $('#map');

    // =========================================================
    // UI APPEND
    // =========================================================
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
                <div class="layer-dropdown-item active" data-layer-type="vector" data-layer="Points">
                    <div class="layer-icon"><i class="bi bi-geo-alt"></i></div>
                    <div class="layer-name">Points</div>
                    <div class="layer-check"><i class="bi bi-check-lg"></i></div>
                </div>
            </div>
        </div>
    `);

    $mapContainer.append(`
        <div class="custom-label-toggle">
            <button class="label-toggle-btn active-label" id="labelToggleBtn" title="Toggle Labels">
                <i class="bi bi-fonts"></i>
            </button>
        </div>
    `);

    $mapContainer.append(`
        <div class="custom-legend-toggle">
            <button class="legend-toggle-btn" id="legendToggleBtn" title="Toggle Infrastructure Legend">
                <i class="bi bi-list-ul"></i>
            </button>
        </div>
    `);

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
                    <div id="searchSuggestions" class="search-results-container"></div>
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

    $mapContainer.append(`
        <div class="custom-3d-toggle">
            <button class="threed-toggle-btn" id="threeDToggleBtn" title="Toggle 3D View">
                <i class="bi bi-box"></i>
            </button>
        </div>
    `);

    $mapContainer.append(`
        <button class="fullscreen-btn" id="fullscreenBtn">
            <i class="bi bi-arrows-fullscreen"></i>
        </button>
        <button class="fullscreen-btn-exit" id="fullscreenExitBtn" style="display:none;">
            <i class="bi bi-fullscreen-exit"></i>
        </button>
    `);

    $mapContainer.append(`
        <div class="direction-controls" id="directionControls">
            <button class="btn-close-route" id="closeRouteBtn">&times;</button>
            <div class="route-info" id="routeInfo">Getting directions...</div>
            <div id="routeSteps"></div>
        </div>
    `);

    // =========================================================
    // BUILD SEARCH INDEX
    // =========================================================
    function buildSearchIndex() {
        searchIndex = [];

        polygons.forEach(poly => {
            try {
                const feature = polygonSource.getFeatureById(String(poly.gisid));
                const extent = feature ? feature.getGeometry().getExtent() : null;
                const center = extent ? ol.extent.getCenter(extent) : null;

                searchIndex.push({
                    uid: `polygon-${poly.gisid}`,
                    id: String(poly.gisid),
                    gisid: String(poly.gisid),
                    point_gisid: '',
                    type: 'polygon',
                    geometryType: 'polygon',
                    title: `GIS ID: ${poly.gisid}`,
                    subtitle: `Area: ${poly.sqfeet || 0} sqft`,
                    assessment: poly.assessment || '',
                    old_assessment: poly.old_assessment || '',
                    owner_name: poly.owner_name || '',
                    phone_number: poly.phone_number || '',
                    sqfeet: poly.sqfeet || '',
                    center: center,
                    extent: extent,
                    coordinates: safeJsonParse(poly.coordinates, []),
                    searchText: `${poly.gisid} ${poly.assessment || ''} ${poly.old_assessment || ''} ${poly.owner_name || ''} ${poly.phone_number || ''} ${poly.sqfeet || ''}`.toLowerCase()
                });
            } catch (e) {
                console.error('polygon index error', e);
            }
        });

        lines.forEach(line => {
            try {
                const feature = lineSource.getFeatureById(String(line.gisid));
                const extent = feature ? feature.getGeometry().getExtent() : null;
                const center = extent ? ol.extent.getCenter(extent) : null;

                searchIndex.push({
                    uid: `line-${line.gisid}`,
                    id: String(line.gisid),
                    gisid: String(line.gisid),
                    point_gisid: '',
                    type: 'line',
                    geometryType: 'line',
                    title: `Road: ${line.road_name || line.gisid}`,
                    subtitle: `GIS ID: ${line.gisid}`,
                    assessment: '',
                    old_assessment: '',
                    owner_name: '',
                    phone_number: '',
                    road_name: line.road_name || '',
                    center: center,
                    extent: extent,
                    coordinates: safeJsonParse(line.coordinates, []),
                    searchText: `${line.gisid} ${line.road_name || ''}`.toLowerCase()
                });
            } catch (e) {
                console.error('line index error', e);
            }
        });

        points.forEach(point => {
            try {
                const rawCoords = safeJsonParse(point.coordinates, null);
                const projected = toProjectedPoint(rawCoords);

                searchIndex.push({
                    uid: `point-${point.gisid}`,
                    id: String(point.gisid),
                    gisid: String(point.gisid),
                    point_gisid: String(point.gisid),
                    type: 'point',
                    geometryType: 'point',
                    title: `GIS ID: ${point.gisid}`,
                    subtitle: 'Point Location',
                    assessment: '',
                    old_assessment: '',
                    owner_name: '',
                    phone_number: '',
                    center: projected,
                    extent: getPointExtent(projected),
                    coordinates: rawCoords,
                    searchText: `${point.gisid} point`.toLowerCase()
                });
            } catch (e) {
                console.error('point index error', e);
            }
        });

        pointDatas.forEach(pd => {
            try {
                const pointGisid = pd.point_gisid || pd.gisid || pd.id || '';
                const rawCoords = safeJsonParse(pd.coordinates, null);
                const projected = toProjectedPoint(rawCoords);

                searchIndex.push({
                    uid: `pointdata-${pointGisid}-${pd.id}`,
                    id: String(pd.id),
                    gisid: String(pointGisid),
                    point_gisid: String(pointGisid),
                    type: 'pointdata',
                    geometryType: 'point',
                    title: `GIS ID: ${pointGisid}`,
                    subtitle: `Assessment: ${pd.assessment || 'N/A'} | Owner: ${pd.owner_name || 'N/A'}`,
                    assessment: pd.assessment || '',
                    old_assessment: pd.old_assessment || '',
                    owner_name: pd.owner_name || '',
                    phone_number: pd.phone_number || '',
                    center: projected,
                    extent: getPointExtent(projected),
                    coordinates: rawCoords,
                    searchText: `${pointGisid} ${pd.assessment || ''} ${pd.old_assessment || ''} ${pd.owner_name || ''} ${pd.phone_number || ''}`.toLowerCase()
                });
            } catch (e) {
                console.error('pointdata index error', e);
            }
        });

        searchIndex = uniqBy(searchIndex, 'uid');
        console.log('Search Index Built:', searchIndex.length);
    }

    buildSearchIndex();

    // =========================================================
    // SEARCH UTILS
    // =========================================================
    function getItemByUid(uid) {
        return searchIndex.find(item => item.uid === uid) || null;
    }

    function getSearchMatches(value) {
        const v = String(value || '').toLowerCase().trim();
        if (!v) return [];

        const exact = searchIndex.filter(item =>
            String(item.gisid || '').toLowerCase() === v ||
            String(item.id || '').toLowerCase() === v ||
            String(item.point_gisid || '').toLowerCase() === v ||
            String(item.assessment || '').toLowerCase() === v
        );

        const partial = searchIndex.filter(item =>
            item.searchText && item.searchText.includes(v)
        );

        return uniqBy([...exact, ...partial], 'uid');
    }

    function findBestSearchMatch(value) {
        const matches = getSearchMatches(value);
        if (!matches.length) return null;

        const exactGis = matches.find(item => String(item.gisid).toLowerCase() === String(value).toLowerCase());
        if (exactGis) return exactGis;

        const exactPointGis = matches.find(item => String(item.point_gisid).toLowerCase() === String(value).toLowerCase());
        if (exactPointGis) return exactPointGis;

        const exactAssessment = matches.find(item => String(item.assessment).toLowerCase() === String(value).toLowerCase());
        if (exactAssessment) return exactAssessment;

        return matches[0];
    }

    function renderSuggestions(matches) {
        const $target = $('#searchSuggestions');

        if (!matches || !matches.length) {
            $target.html('');
            return;
        }

        let html = `<div class="dropdown-header">Suggestions</div>`;

        matches.slice(0, 8).forEach(item => {
            html += `
                <div class="search-result-item suggestion-item" data-uid="${escapeHtml(item.uid)}" style="cursor:pointer;">
                    <div class="search-result-title">${escapeHtml(item.title)}</div>
                    <div class="search-result-subtitle">${escapeHtml(item.subtitle || '')}</div>
                </div>
            `;
        });

        $target.html(html);
    }

    function renderSearchResults(matches, selector, heading = 'Results') {
        const $container = $(selector);

        if (!matches || !matches.length) {
            $container.html('<div class="p-3 text-center text-muted">No results found</div>');
            return;
        }

        let html = `<div class="dropdown-header">${heading} (${matches.length})</div>`;

        matches.slice(0, 20).forEach(item => {
            const icon = item.geometryType === 'polygon'
                ? 'pentagon'
                : item.geometryType === 'line'
                    ? 'vector-pen'
                    : 'geo-alt';

            const details = [];
            if (item.assessment) details.push(`Assess: ${escapeHtml(item.assessment)}`);
            if (item.owner_name) details.push(`Owner: ${escapeHtml(item.owner_name)}`);
            if (item.phone_number) details.push(`Phone: ${escapeHtml(item.phone_number)}`);

            html += `
                <div class="search-result-item" data-uid="${escapeHtml(item.uid)}">
                    <div class="search-result-title">
                        <i class="bi bi-${icon} me-2"></i>${escapeHtml(item.title)}
                    </div>
                    <div class="search-result-subtitle">${escapeHtml(item.subtitle || '')}</div>
                    ${details.length ? `<div class="search-result-subtitle" style="color:#666;">${details.join(' | ')}</div>` : ''}
                    <div class="search-result-actions">
                        <button class="btn btn-sm btn-success zoom-btn" data-uid="${escapeHtml(item.uid)}">Zoom</button>
                        <button class="btn btn-sm btn-primary direction-btn" data-uid="${escapeHtml(item.uid)}">Direction</button>
                    </div>
                </div>
            `;
        });

        $container.html(html);
    }

    // =========================================================
    // HIGHLIGHT / ZOOM
    // =========================================================
    function clearHighlight() {
        highlightSource.clear();
    }

    function highlightSearchItem(item) {
        clearHighlight();
        if (!item) return;

        try {
            let featureToHighlight = null;

            if (item.type === 'polygon') {
                const feature = polygonSource.getFeatureById(String(item.gisid));
                if (feature) {
                    featureToHighlight = feature.clone();
                    featureToHighlight.setStyle(createHighlightStyle('polygon'));
                }
            } else if (item.type === 'line') {
                const feature = lineSource.getFeatureById(String(item.gisid));
                if (feature) {
                    featureToHighlight = feature.clone();
                    featureToHighlight.setStyle(createHighlightStyle('line'));
                }
            } else {
                const center = item.center || toProjectedPoint(item.coordinates);
                if (center) {
                    featureToHighlight = new ol.Feature({
                        geometry: new ol.geom.Point(center)
                    });
                    featureToHighlight.setStyle(createHighlightStyle('point'));
                }
            }

            if (featureToHighlight) {
                highlightSource.addFeature(featureToHighlight);
            }
        } catch (e) {
            console.error('highlight error', e);
        }
    }

    function zoomToSearchItem(item) {
        if (!item) {
            showToast('No matching GISID found');
            return false;
        }

        try {
            highlightSearchItem(item);

            if ((item.type === 'polygon' || item.type === 'line') && item.extent) {
                map.getView().fit(item.extent, {
                    padding: [80, 80, 80, 80],
                    duration: 1000,
                    maxZoom: 21
                });
                showToast(`Zoomed to GIS ID ${item.gisid}`);
                return true;
            }

            const center = item.center || toProjectedPoint(item.coordinates);
            if (center) {
                map.getView().animate({
                    center: center,
                    zoom: 21,
                    duration: 1000
                });
                showToast(`Zoomed to GIS ID ${item.gisid}`);
                return true;
            }

            showToast(`No coordinates found for GIS ID ${item.gisid}`);
            return false;
        } catch (e) {
            console.error('zoom error', e);
            showToast('Zoom failed');
            return false;
        }
    }

    function zoomToFeature(gisid) {
        const item = findBestSearchMatch(gisid);
        return zoomToSearchItem(item);
    }

    // =========================================================
    // LOCATION / ROUTE
    // =========================================================
    function updateTrackLine() {
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
            updateTrackLine();
        }
    }

    function ensureCurrentLocation(callback) {
        if (currentPosition) {
            callback(currentPosition);
            return;
        }

        if (!navigator.geolocation) {
            showToast('Geolocation not supported');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function (pos) {
                updatePosition(pos);
                callback(currentPosition);
            },
            function (error) {
                showToast('Error getting location: ' + error.message);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    }

    function getItemLonLat(item) {
        if (!item) return null;
        const center = item.center || toProjectedPoint(item.coordinates);
        if (!center) return null;
        return ol.proj.toLonLat(center);
    }

    function showDestinationMarker(item) {
        if (destinationMarker) {
            destinationLayer.getSource().removeFeature(destinationMarker);
            destinationMarker = null;
        }

        const center = item.center || toProjectedPoint(item.coordinates);
        if (!center) return;

        destinationMarker = new ol.Feature({
            geometry: new ol.geom.Point(center)
        });

        destinationMarker.setStyle(createDestinationStyle(item.gisid || 'Destination'));
        destinationLayer.getSource().addFeature(destinationMarker);
    }

    function clearDirectionRoute() {
        if (directionRouteFeature) {
            routeLayer.getSource().removeFeature(directionRouteFeature);
            directionRouteFeature = null;
        }

        if (destinationMarker) {
            destinationLayer.getSource().removeFeature(destinationMarker);
            destinationMarker = null;
        }

        $('#directionControls').removeClass('active');
        $('#routeInfo').html('');
        $('#routeSteps').html('');
    }

    async function getDirectionsToItem(item) {
        if (!item) {
            showToast('Destination not found');
            return;
        }

        ensureCurrentLocation(async function (fromProjected) {
            try {
                const fromLonLat = ol.proj.toLonLat(fromProjected);
                const toLonLat = getItemLonLat(item);

                if (!toLonLat) {
                    showToast('Destination coordinates not found');
                    return;
                }

                showDestinationMarker(item);

                $('#directionControls').addClass('active');
                $('#routeInfo').html('Getting directions...');
                $('#routeSteps').html('');

                const url = `https://router.project-osrm.org/route/v1/driving/${fromLonLat[0]},${fromLonLat[1]};${toLonLat[0]},${toLonLat[1]}?overview=full&geometries=geojson&steps=true`;
                const response = await fetch(url);
                const data = await response.json();

                if (!data.routes || !data.routes.length) {
                    $('#routeInfo').html('Route not found');
                    showToast('Route not found');
                    return;
                }

                const route = data.routes[0];
                const routeCoords = route.geometry.coordinates.map(coord => ol.proj.fromLonLat(coord));

                if (directionRouteFeature) {
                    routeLayer.getSource().removeFeature(directionRouteFeature);
                }

                directionRouteFeature = new ol.Feature({
                    geometry: new ol.geom.LineString(routeCoords)
                });

                directionRouteFeature.setStyle(new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#0d6efd',
                        width: 5
                    })
                }));

                routeLayer.getSource().addFeature(directionRouteFeature);

                map.getView().fit(directionRouteFeature.getGeometry().getExtent(), {
                    padding: [80, 80, 120, 80],
                    duration: 1200,
                    maxZoom: 20
                });

                $('#routeInfo').html(`
                    <strong>To:</strong> ${escapeHtml(item.gisid || item.id)}<br>
                    <strong>Distance:</strong> ${formatDistance(route.distance)}<br>
                    <strong>Duration:</strong> ${formatDuration(route.duration)}
                `);

                const steps = route.legs?.[0]?.steps || [];
                if (!steps.length) {
                    $('#routeSteps').html('<div class="step-item">Route ready</div>');
                } else {
                    let stepsHtml = '';
                    steps.forEach((step, index) => {
                        const instruction =
                            step.maneuver?.instruction ||
                            step.name ||
                            'Continue';

                        stepsHtml += `
                            <div class="step-item">
                                ${index + 1}. ${escapeHtml(instruction)} (${formatDistance(step.distance)})
                            </div>
                        `;
                    });
                    $('#routeSteps').html(stepsHtml);
                }

                showToast('Directions loaded');
            } catch (e) {
                console.error('direction error', e);
                $('#routeInfo').html('Direction fetch failed');
                showToast('Direction fetch failed');
            }
        });
    }

    // =========================================================
    // LAYER CONTROL
    // =========================================================
    function switchBaseLayer(layer) {
        [osmLayer, satelliteLayer, streetViewLayer].forEach(l => l.setVisible(l === layer));

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

    // =========================================================
    // EVENTS - DROPDOWNS
    // =========================================================
    $(document).on('click', '.layer-toggle-btn', function (e) {
        e.stopPropagation();
        $('.layer-dropdown').toggleClass('active');
        $('.location-dropdown').removeClass('active');
        $('.search-dropdown').removeClass('active');
    });

    $(document).on('click', '.location-toggle-btn', function (e) {
        e.stopPropagation();
        $('.location-dropdown').toggleClass('active');
        $('.layer-dropdown').removeClass('active');
        $('.search-dropdown').removeClass('active');
    });

    $(document).on('click', '.search-toggle-btn', function (e) {
        e.stopPropagation();
        $('.search-dropdown').toggleClass('active');
        $('.layer-dropdown').removeClass('active');
        $('.location-dropdown').removeClass('active');
        setTimeout(() => $('#gisSearchInput').trigger('focus'), 100);
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.custom-layer-switcher').length) $('.layer-dropdown').removeClass('active');
        if (!$(e.target).closest('.custom-location-switcher').length) $('.location-dropdown').removeClass('active');
        if (!$(e.target).closest('.custom-search-switcher').length) $('.search-dropdown').removeClass('active');
    });

    // =========================================================
    // EVENTS - LAYERS
    // =========================================================
    $(document).on('click', '.layer-dropdown-item', function (e) {
        e.stopPropagation();

        const layerType = $(this).data('layer-type');
        const layerTitle = $(this).data('layer');

        if (layerType === 'base') {
            let layer = null;
            if (layerTitle === 'OpenStreetMap') layer = osmLayer;
            else if (layerTitle === 'Satellite') layer = satelliteLayer;
            else if (layerTitle === 'Street View') layer = streetViewLayer;

            if (layer) switchBaseLayer(layer);
            return;
        }

        if (layerTitle === 'Drone View') {
            const visible = toggleDroneLayer();
            $(this).toggleClass('active', visible);
            return;
        }

        if (layerType === 'vector') {
            let layer = null;

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

    $('#labelToggleBtn').on('click', function () {
        $(this).toggleClass('active-label');
        polygonLayer.setStyle(createPolygonStyle);
        polygonLayer.changed();
    });

    $('#legendToggleBtn').on('click', function () {
        Swal.fire({
            title: 'Infrastructure Legend',
            html: `
                <div style="text-align:left;">
                    <div><span style="display:inline-block;width:20px;height:20px;background:rgba(255,0,0,0.10);border:2px solid blue;margin-right:10px;"></span> Polygons</div>
                    <div><span style="display:inline-block;width:20px;height:4px;background:#ff0000;border-radius:2px;margin-right:10px;"></span> Lines</div>
                    <div><span style="display:inline-block;width:20px;height:20px;background:#198754;border-radius:50%;margin-right:10px;"></span> Points</div>
                    <div><span style="display:inline-block;width:20px;height:20px;background:#0d6efd;border-radius:50%;margin-right:10px;"></span> Current Location</div>
                    <div><span style="display:inline-block;width:20px;height:4px;background:#0d6efd;border-radius:2px;margin-right:10px;"></span> Direction Route</div>
                </div>
            `,
            icon: 'info',
            confirmButtonText: 'Close'
        });
    });

    $('#threeDToggleBtn').on('click', function () {
        $(this).toggleClass('active-3d');
        Swal.fire({
            title: '3D View',
            text: '3D functionality requires Cesium integration.',
            icon: 'info',
            confirmButtonText: 'OK'
        });
    });

    // =========================================================
    // EVENTS - LOCATION
    // =========================================================
    $('#liveLocationItem').on('click', function () {
        if (!navigator.geolocation) {
            showToast('Geolocation not supported');
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
                function (pos) {
                    updatePosition(pos);

                    if (!watchId) {
                        watchId = navigator.geolocation.watchPosition(
                            updatePosition,
                            function (error) {
                                showToast('Location error: ' + error.message);
                            },
                            {
                                enableHighAccuracy: true,
                                timeout: 10000,
                                maximumAge: 0
                            }
                        );
                    }
                },
                function (error) {
                    isLiveLocation = false;
                    $badge.text('OFF').removeClass('active');
                    $btn.removeClass('active-location');
                    showToast('Error getting location: ' + error.message);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000
                }
            );
        } else {
            $badge.text('OFF').removeClass('active');
            $btn.removeClass('active-location');
            showToast('Live location deactivated');

            if (watchId && !isTracking) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
        }

        $('.location-dropdown').removeClass('active');
    });

    $('#trackMeItem').on('click', function () {
        if (!navigator.geolocation) {
            showToast('Geolocation not supported');
            return;
        }

        const $badge = $('#trackMeBadge');
        const $btn = $('#locationToggleBtn');

        if (!isTracking) {
            isTracking = true;
            routePoints = [];
            $badge.text('ON').addClass('tracking');
            $btn.addClass('tracking');
            showToast('Tracking started');

            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    updatePosition(pos);

                    if (!watchId) {
                        watchId = navigator.geolocation.watchPosition(
                            updatePosition,
                            function (error) {
                                showToast('Tracking error: ' + error.message);
                            },
                            {
                                enableHighAccuracy: true,
                                timeout: 10000,
                                maximumAge: 0
                            }
                        );
                    }
                },
                function (error) {
                    isTracking = false;
                    $badge.text('OFF').removeClass('tracking');
                    $btn.removeClass('tracking');
                    showToast('Error starting tracking: ' + error.message);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000
                }
            );
        } else {
            isTracking = false;
            $badge.text('OFF').removeClass('tracking');
            $btn.removeClass('tracking');
            showToast('Tracking stopped');

            if (watchId && !isLiveLocation) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
        }

        $('.location-dropdown').removeClass('active');
    });

    $('#clearRouteItem, #closeRouteBtn').on('click', function () {
        clearDirectionRoute();

        if (routeLine) {
            routeLayer.getSource().removeFeature(routeLine);
            routeLine = null;
        }

        routePoints = [];

        if (!isTracking && !isLiveLocation && positionFeature) {
            positionLayer.getSource().removeFeature(positionFeature);
            positionFeature = null;
            currentPosition = null;
        }

        showToast('Route cleared');
        $('.location-dropdown').removeClass('active');
    });

    // =========================================================
    // EVENTS - SEARCH
    // =========================================================
    $('#gisSearchInput').on('input keyup', function () {
        const value = $(this).val().trim();

        if (!value) {
            $('#searchSuggestions').html('');
            $('#searchResults').html('');
            return;
        }

        const matches = getSearchMatches(value);
        renderSuggestions(matches.slice(0, 8));
        renderSearchResults(matches, '#searchResults', 'Results');
    });

    $('#gisSearchInput').on('keypress', function (e) {
        if (e.which === 13) {
            e.preventDefault();

            const value = $(this).val().trim();
            const bestMatch = findBestSearchMatch(value);

            if (bestMatch) {
                zoomToSearchItem(bestMatch);
                renderSuggestions([]);
                renderSearchResults([bestMatch], '#searchResults', 'Selected');
            } else {
                showToast('No matching GISID found');
            }
        }
    });

    $(document).on('click', '.suggestion-item', function () {
        const uid = $(this).data('uid');
        const item = getItemByUid(uid);
        if (!item) return;

        $('#gisSearchInput').val(item.gisid || item.id);
        renderSuggestions([]);
        renderSearchResults([item], '#searchResults', 'Selected');
        zoomToSearchItem(item);
    });

    $(document).on('click', '.search-result-item', function (e) {
        if ($(e.target).closest('.zoom-btn, .direction-btn').length) return;

        const uid = $(this).data('uid');
        const item = getItemByUid(uid);
        if (!item) return;

        zoomToSearchItem(item);
    });

    $(document).on('click', '.zoom-btn', function (e) {
        e.stopPropagation();
        const uid = $(this).data('uid');
        const item = getItemByUid(uid);
        zoomToSearchItem(item);
    });

    $(document).on('click', '.direction-btn', function (e) {
        e.stopPropagation();
        const uid = $(this).data('uid');
        const item = getItemByUid(uid);
        getDirectionsToItem(item);
    });

    // =========================================================
    // EVENTS - FILTER
    // =========================================================
    $('.search-tab-btn').on('click', function () {
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

    $('#applyFilterBtn').on('click', function () {
        const assessment = $('#filterAssessment').val().toLowerCase().trim();
        const oldAssessment = $('#filterOldAssessment').val().toLowerCase().trim();
        const ownerName = $('#filterOwnerName').val().toLowerCase().trim();
        const phoneNumber = $('#filterPhoneNumber').val().toLowerCase().trim();

        if (!assessment && !oldAssessment && !ownerName && !phoneNumber) {
            showToast('Please enter at least one filter criteria');
            return;
        }

        const matches = searchIndex.filter(item => {
            let ok = true;

            if (assessment) ok = ok && String(item.assessment || '').toLowerCase().includes(assessment);
            if (oldAssessment) ok = ok && String(item.old_assessment || '').toLowerCase().includes(oldAssessment);
            if (ownerName) ok = ok && String(item.owner_name || '').toLowerCase().includes(ownerName);
            if (phoneNumber) ok = ok && String(item.phone_number || '').toLowerCase().includes(phoneNumber);

            return ok;
        });

        renderSearchResults(matches, '#filterResults', 'Filtered Results');

        if (!matches.length) showToast('No results found');
        else showToast(`Found ${matches.length} result(s)`);
    });

    $('#filterAssessment, #filterOldAssessment, #filterOwnerName, #filterPhoneNumber').on('keypress', function (e) {
        if (e.which === 13) $('#applyFilterBtn').click();
    });

    // =========================================================
    // EVENTS - FULLSCREEN
    // =========================================================
    let isFullscreen = false;

    $('#fullscreenBtn').on('click', function () {
        $('#mapCard').addClass('fullscreen-mode');
        $('#map').addClass('fullscreen');
        $('#fullscreenBtn').hide();
        $('#fullscreenExitBtn').show();
        isFullscreen = true;

        setTimeout(() => map.updateSize(), 150);
    });

    $('#fullscreenExitBtn').on('click', function () {
        $('#mapCard').removeClass('fullscreen-mode');
        $('#map').removeClass('fullscreen');
        $('#fullscreenExitBtn').hide();
        $('#fullscreenBtn').show();
        isFullscreen = false;

        setTimeout(() => map.updateSize(), 150);
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && isFullscreen) {
            $('#fullscreenExitBtn').click();
        }
    });

    // =========================================================
    // MAP CLICK
    // =========================================================
    map.on('singleclick', function (evt) {
        let clickedFeature = null;

        map.forEachFeatureAtPixel(evt.pixel, function (feature, layer) {
            if (layer === highlightLayer || layer === routeLayer || layer === positionLayer || layer === destinationLayer) {
                return false;
            }
            clickedFeature = feature;
            return true;
        });

        if (!clickedFeature) return;

        const gisid = clickedFeature.get('gisid');
        if (!gisid) return;

        const item = findBestSearchMatch(gisid);
        if (item) {
            highlightSearchItem(item);
            showToast(`Selected GIS ID ${gisid}`);
        }
    });

    // =========================================================
    // INIT
    // =========================================================
    console.log('GIS Dashboard initialized successfully');
    console.log('Search Index Size:', searchIndex.length);
    console.log('Polygons:', polygons.length);
    console.log('Lines:', lines.length);
    console.log('Points:', points.length);
    console.log('Point Data:', pointDatas.length);
});</script>
@endpush

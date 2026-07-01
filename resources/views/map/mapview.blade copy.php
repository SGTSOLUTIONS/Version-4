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
        height: 600px;
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
        height: calc(100vh - 60px);
    }

    .custom-layer-switcher,
    .custom-location-switcher,
    .custom-search-switcher,
    .custom-edit-toggle {
        position: absolute;
        right: 20px;
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
    }

    .layer-dropdown.show,
    .location-dropdown.show,
    .search-dropdown.show,
    .edit-dropdown.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .layer-dropdown,
    .location-dropdown {
        min-width: 200px;
    }

    .search-dropdown {
        width: 320px;
    }

    .edit-dropdown {
        min-width: 280px;
        max-height: 550px;
        overflow-y: auto;
    }

    .layer-dropdown-item,
    .location-dropdown-item,
    .edit-dropdown-item {
        padding: 12px 18px;
        display: flex;
        align-items: center;
        gap: 14px;
        cursor: pointer;
        transition: background 0.15s;
        border-left: 3px solid transparent;
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

    .layer-icon,
    .location-item-icon,
    .edit-icon {
        width: 22px;
        text-align: center;
        font-size: 1.1rem;
        color: #64748b;
    }

    .layer-name,
    .location-item-name,
    .edit-name {
        flex: 1;
        font-size: 0.9rem;
        color: #334155;
    }

    .layer-check,
    .edit-check {
        color: #3b82f6;
        font-size: 1rem;
        opacity: 0;
        transition: opacity 0.15s;
    }

    .layer-dropdown-item.active .layer-check,
    .edit-dropdown-item.active .edit-check {
        opacity: 1;
    }

    .location-item-badge {
        font-size: 0.7rem;
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

    .live-location-dot {
        width: 16px;
        height: 16px;
        background: #3b82f6;
        border: 3px solid white;
        border-radius: 50%;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        animation: live-dot-pulse 2s infinite;
    }

    @keyframes live-dot-pulse {
        0% {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.4);
        }

        70% {
            box-shadow: 0 0 0 12px rgba(59, 130, 246, 0);
        }

        100% {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0);
        }
    }

    .accuracy-overlay {
        background: rgba(59, 130, 246, 0.1);
        border: 1.5px solid rgba(59, 130, 246, 0.3);
        border-radius: 50%;
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
        padding: 12px 18px;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s;
    }

    .search-result-item:last-child {
        border-bottom: none;
    }

    .search-result-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: #1e293b;
    }

    .search-result-subtitle {
        font-size: 0.75rem;
        color: #64748b;
    }

    .search-results-container {
        max-height: 300px;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .search-results-container::-webkit-scrollbar,
    .edit-dropdown::-webkit-scrollbar {
        width: 6px;
    }

    .search-results-container::-webkit-scrollbar-thumb,
    .edit-dropdown::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .search-results-container::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .edit-shortcut {
        font-size: 0.7rem;
        color: #94a3b8;
        font-family: monospace;
    }

    .draw-mode {
        cursor: crosshair !important;
    }

    .edit-mode {
        cursor: pointer !important;
    }

    .split-mode {
        cursor: crosshair !important;
    }

    .cut-mode {
        cursor: crosshair !important;
    }

    .edit-info-panel {
        position: absolute;
        bottom: 80px;
        left: 20px;
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 10px 15px;
        border-radius: 8px;
        font-size: 0.85rem;
        z-index: 1000;
        pointer-events: none;
        display: none;
    }

    .edit-info-panel.show {
        display: block;
    }

    .edit-info-panel i {
        margin-right: 8px;
    }

    .fullscreen-btn {
        position: absolute;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
    }

    @media (max-width: 768px) {
        #map {
            height: 400px;
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
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/ol@latest/dist/ol.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>

<script>
    $(document).ready(function() {
        let polygons = @json($polygons ?? [], JSON_HEX_TAG);
        let lines = @json($lines ?? [], JSON_HEX_TAG);
        let points = @json($points ?? [], JSON_HEX_TAG);
        let pointDatas = @json($pointDatas ?? [], JSON_HEX_TAG);
        let polygonDatas = @json($polygonDatas ?? [], JSON_HEX_TAG);
        let ward = @json($ward ?? [], JSON_HEX_TAG);
        let searchIndex = [];

        let imageExtentRaw = [{
                {
                    $ward - > extent_left ?? 0
                }
            },
            {
                {
                    $ward - > extent_bottom ?? 0
                }
            },
            {
                {
                    $ward - > extent_right ?? 0
                }
            },
            {
                {
                    $ward - > extent_top ?? 0
                }
            }
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

        const streetLayer = new ol.layer.Tile({
            title: 'Street View',
            type: 'base',
            visible: false,
            source: new ol.source.XYZ({
                url: 'https://{a-c}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png',
                attributions: '&copy; OpenStreetMap Contributors'
            })
        });

        function createPolygonStyle(feature) {
            const gisid = feature.get('gisid');
            const sqft = feature.get('sqfeet') || '0';
            const polygonData = polygonDatas.find(d => d.gisid == gisid);
            const color = polygonData ? 'red' : 'blue';
            const centerPoint = feature.getGeometry().getInteriorPoint();

            return [
                new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: color,
                        width: 4,
                        lineJoin: 'round',
                        lineCap: 'round'
                    }),
                    fill: new ol.style.Fill({
                        color: 'rgba(0, 0, 255, 0.1)'
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
            return new ol.style.Style({
                stroke: new ol.style.Stroke({
                    color: 'yellow',
                    width: 4,
                    lineJoin: 'round',
                    lineCap: 'round'
                }),
                text: new ol.style.Text({
                    text: feature.get('road_name') ? String(feature.get('road_name')) : '',
                    font: 'bold 14px Calibri, sans-serif',
                    placement: 'line',
                    overflow: true,
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

        function createPointStyle(feature) {
            const gisid = feature.get('gisid');
            const pointCount = pointDatas.filter(d => d.gisid == gisid).length;
            const polygonData = polygonDatas.find(d => d.gisid == gisid);
            let color = 'blue';

            if (polygonData) {
                color = pointCount > 0 ? (polygonData.number_bill == pointCount ? 'green' : 'red') : 'blue';
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
                    let coords = typeof l.coordinates === 'string' ? JSON.parse(l.coordinates) : l.coordinates;

                    if (coords.length === 1 && Array.isArray(coords[0]) && Array.isArray(coords[0][0])) {
                        coords = coords[0];
                    }

                    if (coords && coords.length >= 2) {
                        const feature = new ol.Feature({
                            geometry: new ol.geom.LineString(coords),
                            gisid: l.gisid,
                            type: 'Line',
                            road_name: l.road_name || null,
                            originalData: l
                        });
                        feature.setId(l.gisid);
                        lineSource.addFeature(feature);
                    }
                } catch (e) {
                    console.error('Line parse error:', e);
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
            title: 'Lines'
        });

        const pointLayer = new ol.layer.Vector({
            source: pointSource,
            style: createPointStyle,
            visible: true,
            title: 'Points'
        });

        const liveLocationSource = new ol.source.Vector();
        const liveLocationLayer = new ol.layer.Vector({
            source: liveLocationSource,
            visible: true,
            title: 'Live Location',
            zIndex: 999
        });

        const map = new ol.Map({
            target: 'map',
            layers: [
                osmLayer, satelliteLayer, streetLayer,
                droneLayer,
                polygonLayer, pointLayer, lineLayer,
                liveLocationLayer
            ],
            view: new ol.View({
                center: ol.extent.getCenter(imageExtent),
                zoom: 18
            })
        });

        let drawInteraction = null;
        let modifyInteraction = null;
        let snapInteraction = null;
        let selectInteraction = null;
        let translateInteraction = null;
        let currentDrawType = null;
        let selectedFeature = null;
        let routeLayer = null;

        const tempDrawSource = new ol.source.Vector();
        const tempDrawLayer = new ol.layer.Vector({
            source: tempDrawSource,
            style: new ol.style.Style({
                fill: new ol.style.Fill({
                    color: 'rgba(255, 0, 0, 0.2)'
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

        function showToast(msg, duration = 2500) {
            const $t = $('#locationToast');
            $t.text(msg).addClass('show');
            setTimeout(() => $t.removeClass('show'), duration);
        }

        function disableAllInteractions() {
            if (drawInteraction) {
                map.removeInteraction(drawInteraction);
                drawInteraction = null;
            }
            if (modifyInteraction) {
                map.removeInteraction(modifyInteraction);
                modifyInteraction = null;
            }
            if (translateInteraction) {
                map.removeInteraction(translateInteraction);
                translateInteraction = null;
            }
            if (selectInteraction) {
                map.removeInteraction(selectInteraction);
                selectInteraction = null;
            }
            if (snapInteraction) {
                map.removeInteraction(snapInteraction);
                snapInteraction = null;
            }
            tempDrawSource.clear();
            map.getTargetElement().classList.remove('draw-mode', 'split-mode', 'cut-mode');
        }

        function clearDrawInteraction() {
            if (drawInteraction) {
                map.removeInteraction(drawInteraction);
                drawInteraction = null;
            }
            tempDrawSource.clear();
            currentDrawType = null;
        }

        function initSelectInteraction() {
            if (selectInteraction) map.removeInteraction(selectInteraction);

            selectInteraction = new ol.interaction.Select({
                layers: [polygonLayer, lineLayer, pointLayer],
                style: new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#ff6600',
                        width: 4
                    }),
                    fill: new ol.style.Fill({
                        color: 'rgba(255, 102, 0, 0.2)'
                    }),
                    image: new ol.style.Circle({
                        radius: 10,
                        fill: new ol.style.Fill({
                            color: '#ff6600'
                        }),
                        stroke: new ol.style.Stroke({
                            color: '#fff',
                            width: 2
                        })
                    })
                })
            });

            selectInteraction.on('select', function(e) {
                if (e.selected.length > 0) {
                    selectedFeature = e.selected[0];
                    showToast(`Selected ${selectedFeature.get('type')} ID: ${selectedFeature.get('gisid')}`, 2000);
                } else {
                    selectedFeature = null;
                }
            });

            map.addInteraction(selectInteraction);
        }

        function initSnapInteraction() {
            if (snapInteraction) map.removeInteraction(snapInteraction);
            snapInteraction = new ol.interaction.Snap({
                source: polygonSource
            });
            map.addInteraction(snapInteraction);
        }

        function enableSelectMode() {
            disableAllInteractions();
            initSelectInteraction();
            initSnapInteraction();
            showToast('Select mode enabled', 2000);
        }

        function startDrawing(type) {
            disableAllInteractions();
            clearDrawInteraction();

            let geometryType;
            switch (type) {
                case 'Polygon':
                    geometryType = 'Polygon';
                    break;
                case 'Line':
                    geometryType = 'LineString';
                    break;
                case 'Point':
                    geometryType = 'Point';
                    break;
                default:
                    return;
            }

            map.getTargetElement().classList.add('draw-mode');

            drawInteraction = new ol.interaction.Draw({
                source: tempDrawSource,
                type: geometryType,
                style: new ol.style.Style({
                    fill: new ol.style.Fill({
                        color: 'rgba(0, 255, 0, 0.2)'
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
                const feature = e.feature;
                saveFeature(feature, type);
            });

            map.addInteraction(drawInteraction);
            currentDrawType = type;
        }

        function saveFeature(feature, type) {
            const coordinates = feature.getGeometry().getCoordinates();

            $.ajax({
                url: '/save-feature',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    layer_type: type,
                    feature: JSON.stringify(coordinates)
                },
                success: function(response) {
                    polygons = response.data.polygons ?? polygons;
                    points = response.data.points ?? points;
                    loadPolygonsToSource();
                    loadPointsToSource();
                    disableAllInteractions();
                    clearDrawInteraction();
                    buildSearchIndex();
                    Swal.fire('Success', 'Feature saved successfully', 'success');
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                    Swal.fire('Error', 'Error saving feature', 'error');
                }
            });
        }

        function updateFeatureProperties() {
            if (!selectedFeature) {
                Swal.fire('Error', 'Please select a feature first', 'error');
                return;
            }

            Swal.fire({
                title: 'Update Properties',
                html: `<input type="text" id="gisid" class="swal2-input" placeholder="GIS ID" value="${selectedFeature.get('gisid')}">`,
                preConfirm: () => {
                    const gisid = document.getElementById('gisid').value;
                    if (!gisid) {
                        Swal.showValidationMessage('GIS ID is required');
                        return false;
                    }
                    return {
                        gisid
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    selectedFeature.set('gisid', result.value.gisid);
                    selectedFeature.setId(result.value.gisid);
                    selectedFeature.changed();
                    Swal.fire('Success', 'Feature updated successfully!', 'success');
                }
            });
        }

        function deleteSelectedFeature() {
            if (!selectedFeature) {
                Swal.fire('Error', 'Please select a feature first', 'error');
                return;
            }

            const type = selectedFeature.get('type');

            Swal.fire({
                title: `Delete ${type}?`,
                text: `Delete ${type} ID: ${selectedFeature.get('gisid')}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (type === 'Polygon') polygonSource.removeFeature(selectedFeature);
                    else if (type === 'Line') lineSource.removeFeature(selectedFeature);
                    else if (type === 'Point') pointSource.removeFeature(selectedFeature);

                    selectedFeature = null;
                    if (selectInteraction) selectInteraction.getFeatures().clear();

                    Swal.fire('Deleted!', `${type} has been deleted.`, 'success');
                }
            });
        }

        function splitPolygon() {
            Swal.fire({
                title: 'Split Polygon',
                text: 'Split polygon functionality will be implemented here',
                icon: 'info',
                confirmButtonText: 'OK'
            });
        }

        function buildSearchIndex() {
            searchIndex = [];

            polygons.forEach(poly => {
                try {
                    let coords = JSON.parse(poly.coordinates);
                    searchIndex.push({
                        id: poly.gisid,
                        type: 'polygon',
                        title: `GIS ID: ${poly.gisid}`,
                        subtitle: `Building (${poly.sqfeet || 0} sqft)`,
                        coordinates: coords,
                        geometryType: 'polygon',
                        searchText: `${poly.gisid} ${poly.sqfeet} building polygon`
                    });
                } catch (e) {
                    console.error('Error parsing polygon:', e);
                }
            });

            lines.forEach(line => {
                try {
                    let coords = typeof line.coordinates === 'string' ? JSON.parse(line.coordinates) : line.coordinates;
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
                    let coords = JSON.parse(point.coordinates);
                    searchIndex.push({
                        id: point.gisid,
                        type: 'point',
                        title: `GIS ID: ${point.gisid}`,
                        subtitle: 'Point Location',
                        coordinates: coords,
                        geometryType: 'point',
                        searchText: `${point.gisid} point`
                    });
                } catch (e) {
                    console.error('Error parsing point:', e);
                }
            });

            pointDatas.forEach(pointData => {
                try {
                    let coords = JSON.parse(pointData.coordinates);
                    searchIndex.push({
                        id: pointData.gisid,
                        point_gisid: pointData.point_gisid,
                        type: 'pointdata',
                        title: `GIS ID: ${pointData.gisid}`,
                        assessmentTitle: `Assessment: ${pointData.assessment}`,
                        subtitle: `Assessment: ${pointData.assessment} | Point GIS ID: ${pointData.point_gisid}`,
                        coordinates: coords,
                        geometryType: 'point',
                        assessment: pointData.assessment,
                        searchText: `${pointData.gisid} ${pointData.assessment} ${pointData.point_gisid} assessment point`
                    });
                } catch (e) {
                    console.error('Error parsing pointData:', e);
                }
            });
        }

        function searchGIS(value) {
            const searchValue = value.toString().toLowerCase().trim();
            if (!searchValue) return [];

            return searchIndex.filter(item => {
                const gisidMatch = item.id && item.id.toString().toLowerCase().includes(searchValue);
                const assessmentMatch = item.assessment && item.assessment.toString().toLowerCase().includes(searchValue);
                const titleMatch = item.title && item.title.toLowerCase().includes(searchValue);
                const subtitleMatch = item.subtitle && item.subtitle.toLowerCase().includes(searchValue);
                const pointGisidMatch = item.point_gisid && item.point_gisid.toString().toLowerCase().includes(searchValue);

                return gisidMatch || assessmentMatch || titleMatch || subtitleMatch || pointGisidMatch;
            });
        }

        function zoomToFeature(gisid) {
            try {
                const point = points.find(item =>
                    item.gisid &&
                    item.gisid.toString().toLowerCase() === gisid.toString().toLowerCase()
                );

                if (!point) {
                    showToast('Feature not found', 3000);
                    return;
                }

                const coordinates = typeof point.coordinates === 'string' ? JSON.parse(point.coordinates) : point.coordinates;

                map.getView().animate({
                    center: coordinates,
                    zoom: 22,
                    duration: 1000
                });
            } catch (error) {
                console.error('Zoom error details:', error);
                showToast('Error zooming to feature', 3000);
            }
        }

        function getCurrentLocation(callback) {
            if (!navigator.geolocation) {
                Swal.fire('Error', 'Geolocation is not supported by your browser', 'error');
                callback(null);
                return false;
            }

            showToast('Getting your location...', 1500);

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    callback({
                        lon: position.coords.longitude,
                        lat: position.coords.latitude
                    });
                },
                function(error) {
                    let errorMsg = 'Unable to get your location. ';
                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg += 'Please enable location permissions.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg += 'Location information is unavailable.';
                            break;
                        case error.TIMEOUT:
                            errorMsg += 'Location request timed out.';
                            break;
                        default:
                            errorMsg += 'An unknown error occurred.';
                    }
                    Swal.fire('Location Error', errorMsg, 'error');
                    callback(null);
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000
                }
            );

            return true;
        }

        function getRoute(startLon, startLat, endLon, endLat) {
            const url = `https://router.project-osrm.org/route/v1/driving/${startLon},${startLat};${endLon},${endLat}?overview=full&geometries=geojson`;

            showToast('Calculating route...', 1500);

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (!data.routes || data.routes.length === 0) {
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

                    const routeExtent = routeFeature.getGeometry().getExtent();
                    if (routeExtent && routeExtent[0] !== routeExtent[2]) {
                        map.getView().fit(routeExtent, {
                            padding: [50, 50, 50, 50],
                            duration: 1000
                        });
                    }

                    const distance = (data.routes[0].distance / 1000).toFixed(2);
                    const duration = Math.round(data.routes[0].duration / 60);
                    showToast(`Route found! Distance: ${distance}km, Time: ${duration}min`, 4000);
                })
                .catch(error => {
                    console.error('Route error:', error);
                    Swal.fire('Error', 'Failed to calculate route. Please try again.', 'error');
                });
        }

        function getDirectionToFeature(feature) {
            getCurrentLocation(function(location) {
                if (!location) return;

                let destLon, destLat;

                try {
                    if (feature.geometryType === 'point') {
                        destLon = feature.coordinates[0];
                        destLat = feature.coordinates[1];
                    } else if (feature.geometryType === 'polygon') {
                        const coords = feature.coordinates;
                        let sumLon = 0,
                            sumLat = 0;

                        coords.forEach(c => {
                            sumLon += c[0];
                            sumLat += c[1];
                        });

                        destLon = sumLon / coords.length;
                        destLat = sumLat / coords.length;
                    } else if (feature.geometryType === 'line') {
                        const coords = feature.coordinates;
                        const midIndex = Math.floor(coords.length / 2);
                        destLon = coords[midIndex][0];
                        destLat = coords[midIndex][1];
                    } else {
                        Swal.fire('Error', 'Cannot get direction to this feature type', 'error');
                        return;
                    }

                    getRoute(location.lon, location.lat, destLon, destLat);
                } catch (error) {
                    console.error('Direction error:', error);
                    Swal.fire('Error', 'Failed to calculate direction', 'error');
                }
            });
        }

        function clearRoute() {
            if (routeLayer) {
                map.removeLayer(routeLayer);
                routeLayer = null;
                showToast('Route cleared', 2000);
            }
        }

        const $mapContainer = $('#map');
        const $mapCard = $('#mapCard');
        const $activeLayerBadge = $('#activeLayerBadge');

        function getActiveBaseLayerTitle() {
            return [osmLayer, satelliteLayer, streetLayer].find(l => l.getVisible())?.get('title') || 'OpenStreetMap';
        }

        function updateLayerUI() {
            const activeTitle = getActiveBaseLayerTitle();
            const droneVisible = droneLayer.getVisible();
            let badgeText = activeTitle;

            if (droneVisible) badgeText += ' + Drone';
            $activeLayerBadge.text(badgeText);

            $('.layer-dropdown-item[data-layer-type="base"]').removeClass('active');
            $(`.layer-dropdown-item[data-layer="${activeTitle}"]`).addClass('active');

            const droneItem = $('.layer-dropdown-item[data-layer="Drone View"]');
            if (droneVisible) droneItem.addClass('active');
            else droneItem.removeClass('active');
        }

        function switchBaseLayer(selectedLayer) {
            [osmLayer, satelliteLayer, streetLayer].forEach(l => l.setVisible(l === selectedLayer));
            updateLayerUI();
        }

        function toggleDroneLayer() {
            droneLayer.setVisible(!droneLayer.getVisible());
            updateLayerUI();
        }

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
                    <div class="location-toggle-btn" id="locationToggleBtn">
                        <i class="bi bi-geo-alt"></i>
                    </div>
                    <div class="location-dropdown" id="locationDropdown">
                        <div class="dropdown-header">Location Tools</div>
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
                    <div class="search-toggle-btn" id="searchToggleBtn">
                        <i class="bi bi-search"></i>
                    </div>
                    <div class="search-dropdown" id="searchDropdown">
                        <div class="dropdown-header">Search GIS</div>
                        <div class="p-3">
                            <input type="text" id="gisSearchInput" class="form-control" placeholder="Search by GIS ID or Assessment...">
                        </div>
                        <div id="searchResults" class="search-results-container"></div>
                    </div>
                </div>
            `);

        $mapContainer.append(`
                <div class="custom-edit-toggle">
                    <div class="edit-toggle-btn" id="editToggleBtn">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div class="edit-dropdown">
                        <div class="dropdown-header">Selection</div>
                        <div class="edit-dropdown-item" data-tool="select">
                            <div class="edit-icon"><i class="bi bi-cursor"></i></div>
                            <div class="edit-name">Select Feature</div>
                            <div class="edit-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-header">Drawing Tools</div>
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
                        <div class="dropdown-header">Edit Tools</div>
                        <div class="edit-dropdown-item" data-tool="splitPolygon">
                            <div class="edit-icon"><i class="bi bi-scissors"></i></div>
                            <div class="edit-name">Split Polygon</div>
                            <div class="edit-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                        <div class="edit-dropdown-item" data-tool="updateProperties">
                            <div class="edit-icon"><i class="bi bi-pencil-square"></i></div>
                            <div class="edit-name">Update Properties</div>
                            <div class="edit-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                        <div class="edit-dropdown-item" data-tool="deleteFeature">
                            <div class="edit-icon"><i class="bi bi-trash-fill"></i></div>
                            <div class="edit-name">Delete Feature</div>
                            <div class="edit-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                        <div class="edit-dropdown-item" data-tool="cancelEdit">
                            <div class="edit-icon"><i class="bi bi-x-circle"></i></div>
                            <div class="edit-name">Cancel Edit</div>
                            <div class="edit-check"><i class="bi bi-check-lg"></i></div>
                        </div>
                    </div>
                </div>
            `);

        $mapContainer.append(`
                <div class="fullscreen-btn" id="fullscreenBtn">
                    <i class="bi bi-arrows-fullscreen"></i>
                </div>
            `);

        $(document).on('click', '.edit-dropdown-item', function(e) {
            e.stopPropagation();
            const tool = $(this).data('tool');

            $('.edit-dropdown-item').removeClass('active');
            $(this).addClass('active');

            switch (tool) {
                case 'select':
                    enableSelectMode();
                    break;
                case 'drawPolygon':
                    startDrawing('Polygon');
                    break;
                case 'drawLine':
                    startDrawing('Line');
                    break;
                case 'drawPoint':
                    startDrawing('Point');
                    break;
                case 'splitPolygon':
                    splitPolygon();
                    break;
                case 'updateProperties':
                    updateFeatureProperties();
                    break;
                case 'deleteFeature':
                    deleteSelectedFeature();
                    break;
                case 'cancelEdit':
                    disableAllInteractions();
                    enableSelectMode();
                    showToast('Edit mode cancelled', 1500);
                    break;
            }

            $('.edit-dropdown').removeClass('show');
            $('#editToggleBtn').removeClass('active-edit');
        });

        $(document).on('click', '.layer-toggle-btn', function(e) {
            e.stopPropagation();
            $('.layer-dropdown').toggleClass('show');
            $('#locationDropdown').removeClass('show');
            $('#searchDropdown').removeClass('show');
            $('.edit-dropdown').removeClass('show');
        });

        $(document).on('click', '.layer-dropdown-item', function(e) {
            e.stopPropagation();
            const layerType = $(this).data('layer-type');
            const layerTitle = $(this).data('layer');

            if (layerType === 'base') {
                switchBaseLayer(layerTitle === 'Satellite' ? satelliteLayer : layerTitle === 'Street View' ? streetLayer : osmLayer);
                $('.layer-dropdown').removeClass('show');
            } else if (layerTitle === 'Drone View') {
                toggleDroneLayer();
            }
        });

        $('#locationToggleBtn').on('click', function(e) {
            e.stopPropagation();
            $('#locationDropdown').toggleClass('show');
            $('.layer-dropdown').removeClass('show');
            $('#searchDropdown').removeClass('show');
            $('.edit-dropdown').removeClass('show');
        });

        $('#clearRouteItem').on('click', function(e) {
            e.stopPropagation();
            clearRoute();
            $('#locationDropdown').removeClass('show');
        });

        $('#searchToggleBtn').on('click', function(e) {
            e.stopPropagation();
            $('#searchDropdown').toggleClass('show');
            $(this).toggleClass('active-search');
            $('#locationDropdown').removeClass('show');
            $('.layer-dropdown').removeClass('show');
            $('.edit-dropdown').removeClass('show');

            if ($('#searchDropdown').hasClass('show')) {
                setTimeout(() => $('#gisSearchInput').focus(), 100);
            }
        });

        $(document).on('keyup', '#gisSearchInput', function() {
            const value = $(this).val();

            if (!value || value.length < 1) {
                $('#searchResults').html('');
                return;
            }

            const results = searchGIS(value);
            let html = '';

            if (results.length === 0) {
                html = '<div class="p-3 text-center text-muted">No results found for "' + value + '"</div>';
            } else {
                results.slice(0, 10).forEach(item => {
                    let displayTitle = item.title;
                    let displaySubtitle = item.subtitle;

                    if (item.type === 'pointdata') {
                        displayTitle = `${item.title} | Assessment: ${item.assessment}`;
                        displaySubtitle = `Point GIS ID: ${item.point_gisid || 'N/A'} | ${item.subtitle}`;
                    }

                    html += `
                            <div class="search-result-item" data-id="${item.id}" data-type="${item.type}">
                                <div class="search-result-title">
                                    <i class="bi bi-${item.geometryType === 'point' ? 'geo-alt' : (item.geometryType === 'polygon' ? 'pentagon' : 'vector-pen')} me-2"></i>
                                    ${displayTitle}
                                </div>
                                <div class="search-result-subtitle">${displaySubtitle}</div>
                                <div class="mt-2 d-flex gap-2">
                                    <button class="btn btn-sm btn-success zoom-btn" data-id="${item.id}" data-type="${item.type}">
                                        <i class="bi bi-zoom-in"></i> Zoom
                                    </button>
                                    <button class="btn btn-sm btn-primary direction-btn" data-id="${item.id}" data-type="${item.type}">
                                        <i class="bi bi-sign-turn-right"></i> Direction
                                    </button>
                                </div>
                            </div>
                        `;
                });
            }

            $('#searchResults').html(html);
        });

        $(document).on('click', '.zoom-btn', function(e) {
            e.stopPropagation();

            const id = $(this).data('id');
            const type = $(this).data('type');
            const feature = searchIndex.find(f => f.id == id && f.type === type);

            if (feature) {
                zoomToFeature(id);
                $('#searchDropdown').removeClass('show');
                $('#searchToggleBtn').removeClass('active-search');
                $('#gisSearchInput').val('');
                $('#searchResults').html('');
            } else {
                showToast('Feature not found', 2000);
            }
        });

        $(document).on('click', '.direction-btn', function(e) {
            e.stopPropagation();

            const id = $(this).data('id');
            const type = $(this).data('type');
            const feature = searchIndex.find(f => f.id == id && f.type === type);

            if (feature) {
                getDirectionToFeature(feature);
                $('#searchDropdown').removeClass('show');
                $('#searchToggleBtn').removeClass('active-search');
                $('#gisSearchInput').val('');
                $('#searchResults').html('');
            } else {
                showToast('Feature not found', 2000);
            }
        });

        $('#editToggleBtn').on('click', function(e) {
            e.stopPropagation();
            $('.edit-dropdown').toggleClass('show');
            $(this).toggleClass('active-edit');
            $('#locationDropdown').removeClass('show');
            $('.layer-dropdown').removeClass('show');
            $('#searchDropdown').removeClass('show');
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.custom-layer-switcher').length) $('.layer-dropdown').removeClass('show');
            if (!$(e.target).closest('.custom-location-switcher').length) $('#locationDropdown').removeClass('show');
            if (!$(e.target).closest('.custom-search-switcher').length) {
                $('#searchDropdown').removeClass('show');
                $('#searchToggleBtn').removeClass('active-search');
            }
            if (!$(e.target).closest('.custom-edit-toggle').length) {
                $('.edit-dropdown').removeClass('show');
                $('#editToggleBtn').removeClass('active-edit');
            }
        });

        let isFullscreen = false;
        $('#fullscreenBtn').on('click', function() {
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

        buildSearchIndex();
        updateLayerUI();
        enableSelectMode();

        if (!droneImageURL || droneImageURL === "{{ asset('') }}") {
            droneLayer.setVisible(false);
        }
    });
</script>
@endpush
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
        height: 700px;
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
        height: calc(100vh - 60px);
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

    /* ─── DROPDOWN STYLES (SCROLLABLE FIX) ─── */
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

    /* Scrollbar styling */
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

    .edit-dropdown::-webkit-scrollbar-track,
    .layer-dropdown::-webkit-scrollbar-track,
    .location-dropdown::-webkit-scrollbar-track,
    .search-dropdown::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }

    /* ─── DROPDOWN ITEMS ─── */
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

    /* ─── EDIT/MOVE CONTROLS ─── */
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

    /* Delete Modal */
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
            height: 400px;
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

{{-- ══════════════ DELETE FEATURE MODAL ══════════════ --}}
<div class="modal fade" id="deleteFeatureModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.15);">

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
        let searchIndex = [];

        let imageExtentRaw = [{
                {
                    $ward - > extent_left ?? 0
                }
            },
            {
                {
                    $ward - > extent_bottom ?? 0
                }
            },
            {
                {
                    $ward - > extent_right ?? 0
                }
            },
            {
                {
                    $ward - > extent_top ?? 0
                }
            }
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
            const pointCount = pointDatas.filter(d => d.gisid == gisid).length;
            const polygonData = polygonDatas.find(d => d.gisid == gisid);
            let color = 'blue';
            if (polygonData) {
                color = pointCount > 0 ?
                    (polygonData.number_bill == pointCount ? 'green' : 'red') :
                    'blue';
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
                    const isValid = coords.length >= 2 &&
                        coords.every(c => Array.isArray(c) && c.length >= 2 &&
                            typeof c[0] === 'number' && typeof c[1] === 'number' &&
                            isFinite(c[0]) && isFinite(c[1]));
                    if (!isValid) {
                        console.warn('Skipping invalid line coords for gisid:', l.gisid, coords);
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
        let currentLocation = null; // Store current location for direction

        function updateLiveMarker(lon, lat, accuracy) {
            const coords = ol.proj.fromLonLat([lon, lat]);
            currentLocation = {
                lon,
                lat
            }; // Store for direction
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

            // Get initial position first
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
            let html =
                `<div style="text-align:left"><p><strong>GIS ID:</strong> ${gisid}</p><p><strong>Type:</strong> ${type}</p>`;
            if (type === 'Polygon') html +=
                `<p><strong>Area:</strong> ${feature.get('sqfeet') || 'N/A'} sqft</p>`;
            if (type === 'LineString') html +=
                `<p><strong>Road Name:</strong> ${feature.get('road_name') || 'N/A'}</p>`;
            const pointCount = pointDatas.filter(d => d.gisid == gisid).length;
            if (pointCount > 0) html += `<p><strong>Associated Points:</strong> ${pointCount}</p>`;
            html += '</div>';
            Swal.fire({
                title: 'Feature Details',
                html,
                icon: 'info',
                confirmButtonText: 'Close',
                width: 350
            });
        }

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
            $mapContainer.append($controls);

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

                    const index = polygons.findIndex(p => p.gisid == gisid);
                    if (index !== -1) {
                        polygons[index].coordinates = JSON.stringify(coordinates[0]);
                    }

                    loadPolygonsToSource();

                    selectedFeatureForEdit.setStyle(null);
                    selectedFeatureForEdit = null;
                    originalGeometry = null;
                    hideEditControls();

                    $('.edit-dropdown-item').removeClass('active');
                    $('.edit-dropdown').removeClass('show');
                    $('#editToggleBtn').removeClass('active-edit');

                    buildSearchIndex();
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
            $mapContainer.append($controls);

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

                    const index = polygons.findIndex(p => p.gisid == gisid);
                    if (index !== -1) {
                        polygons[index].coordinates = JSON.stringify(coordinates[0]);
                    }

                    loadPolygonsToSource();

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

                    buildSearchIndex();
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
            $mapContainer.append($btn);

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
                        disableSlectFeature();
                        loadPolygonsToSource();
                        loadPointsToSource();
                        disableAllInteractions();
                        clearDrawInteraction();
                        buildSearchIndex();
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
                Polygon: 'Polygon',
                LineString: 'LineString',
                Point: 'Point'
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
                    loadPolygonsToSource();
                    loadPointsToSource();
                    loadLinesToSource();
                    disableAllInteractions();
                    clearDrawInteraction();
                    buildSearchIndex();
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
            try {
                const point = points.find(item => item.gisid && item.gisid.toString().toLowerCase() === gisid
                    .toString().toLowerCase());
                if (!point) {
                    showToast('Feature not found', 3000);
                    return;
                }
                const coords = typeof point.coordinates === 'string' ? JSON.parse(point.coordinates) : point
                    .coordinates;
                map.getView().animate({
                    center: coords,
                    zoom: 22,
                    duration: 1000
                });
            } catch (error) {
                console.error('Zoom error:', error);
                showToast('Error zooming to feature', 3000);
            }
        }

        // ─── LOCATION & ROUTING (FIXED) ───
        function getCurrentLocation(callback) {
            if (!navigator.geolocation) {
                Swal.fire('Error', 'Geolocation is not supported by your browser', 'error');
                callback(null);
                return false;
            }

            showToast('📍 Getting your location...', 2000);

            // Use stored location if available
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
                    // Try one more time with a simpler approach
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

        function calculateDirection(loc, feature) {
            if (!loc) return;
            let destLon, destLat;
            try {
                if (feature.geometryType === 'point') {
                    [destLon, destLat] = feature.coordinates;
                } else if (feature.geometryType === 'polygon') {
                    let sumLon = 0,
                        sumLat = 0;
                    feature.coordinates.forEach(c => {
                        sumLon += c[0];
                        sumLat += c[1];
                    });
                    destLon = sumLon / feature.coordinates.length;
                    destLat = sumLat / feature.coordinates.length;
                } else if (feature.geometryType === 'line') {
                    const mid = Math.floor(feature.coordinates.length / 2);
                    [destLon, destLat] = feature.coordinates[mid];
                } else {
                    Swal.fire('Error', 'Cannot get direction to this feature type', 'error');
                    return;
                }
                getRoute(loc.lon, loc.lat, destLon, destLat);
            } catch (err) {
                console.error('Direction error:', err);
                Swal.fire('Error', 'Failed to calculate direction', 'error');
            }
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
                <div class="dropdown-header">Search GIS</div>
                <div class="p-3">
                    <input type="text" id="gisSearchInput" class="form-control"
                        placeholder="Search by GIS ID or Assessment...">
                </div>
                <div id="searchResults" class="search-results-container"></div>
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

        // Location toggle (FIXED)
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
                // Start both live location and store location
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
                // Enable tracking - automatically enables live location too
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
                    const icon = item.geometryType === 'point' ? 'geo-alt' : item
                        .geometryType === 'polygon' ? 'pentagon' : 'vector-pen';
                    html += `
                    <div class="search-result-item" data-id="${item.id}" data-type="${item.type}">
                        <div class="search-result-title">
                            <i class="bi bi-${icon} me-2"></i>${displayTitle}
                        </div>
                        <div class="search-result-subtitle">${displaySubtitle}</div>
                        <div class="mt-2 d-flex gap-2">
                            <button class="btn btn-sm btn-success zoom-btn" data-id="${item.id}" data-type="${item.type}">
                                <i class="bi bi-zoom-in"></i> Zoom
                            </button>
                            <button class="btn btn-sm btn-primary direction-btn" data-id="${item.id}" data-type="${item.type}">
                                <i class="bi bi-sign-turn-right"></i> Direction
                            </button>
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

                   polygons = response.data.polygons ?? polygons;
                        points = response.datapoints ?? points;
                        disableSlectFeature();
                        loadPolygonsToSource();
                        loadPointsToSource();
                        disableAllInteractions();

                    buildSearchIndex();
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

        console.log('✅ GIS Dashboard ready — Location and Direction fixed!');
    });
</script>
@endpush

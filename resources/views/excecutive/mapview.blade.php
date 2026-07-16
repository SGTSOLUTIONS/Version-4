@extends('layouts.office')

@section('title', 'Executive GIS Dashboard')
@section('page_title', 'Executive GIS Dashboard')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@latest/ol.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cesium.com/downloads/cesiumjs/releases/1.127/Build/Cesium/Widgets/widgets.css" rel="stylesheet" />

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
            position: relative;
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

        #map,
        #cesiumContainer {
            width: 100%;
            height: 800px;
            position: relative;
        }

        #cesiumContainer {
            display: none;
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

        .map-card.fullscreen-mode #map,
        .map-card.fullscreen-mode #cesiumContainer {
            height: calc(100vh - 5px);
        }

        .custom-layer-switcher,
        .custom-location-switcher,
        .custom-search-switcher,
        .custom-edit-toggle,
        .custom-label-toggle,
        .custom-legend-toggle,
        .custom-3d-toggle {
            position: absolute;
            right: 30px;
            z-index: 1000;
        }

        .custom-layer-switcher { top: 20px; }
        .custom-location-switcher { top: 74px; }
        .custom-search-switcher { top: 130px; }
        .custom-edit-toggle { top: 190px; }
        .custom-label-toggle { top: 246px; }
        .custom-legend-toggle { top: 302px; }
        .custom-3d-toggle { top: 358px; }

        .layer-toggle-btn,
        .location-toggle-btn,
        .search-toggle-btn,
        .edit-toggle-btn,
        .label-toggle-btn,
        .legend-toggle-btn,
        .threed-toggle-btn,
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
            font-size: 1.2rem;
        }

        .layer-toggle-btn:hover,
        .location-toggle-btn:hover,
        .search-toggle-btn:hover,
        .edit-toggle-btn:hover,
        .label-toggle-btn:hover,
        .legend-toggle-btn:hover,
        .threed-toggle-btn:hover,
        .fullscreen-btn:hover {
            background: #f8fafc;
            transform: scale(1.02);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .label-toggle-btn.active-label,
        .legend-toggle-btn.active-legend,
        .location-toggle-btn.active-location,
        .search-toggle-btn.active-search,
        .edit-toggle-btn.active-edit,
        .threed-toggle-btn.active-3d {
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
            max-height: 500px;
            overflow-y: auto !important;
            overflow-x: hidden;
        }

        .layer-dropdown-item,
        .location-dropdown-item,
        .edit-dropdown-item,
        .search-result-item {
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

        .layer-check {
            margin-left: auto;
            color: #3b82f6;
            opacity: 0;
        }

        .layer-dropdown-item.active .layer-check {
            opacity: 1;
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

        .infrastructure-legend {
            position: absolute;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            padding: 12px 16px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.15);
            max-height: 400px;
            overflow-y: auto;
            min-width: 180px;
            max-width: 240px;
            font-size: 12px;
            border: 1px solid #e5e7eb;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s;
            pointer-events: none;
        }

        .infrastructure-legend.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            pointer-events: auto;
        }

        @media (max-width: 768px) {
            #map,
            #cesiumContainer {
                height: 600px;
            }

            .custom-layer-switcher,
            .custom-location-switcher,
            .custom-search-switcher,
            .custom-edit-toggle,
            .custom-label-toggle,
            .custom-legend-toggle,
            .custom-3d-toggle {
                right: 10px;
            }

            .custom-layer-switcher { top: 10px; }
            .custom-location-switcher { top: 58px; }
            .custom-search-switcher { top: 106px; }
            .custom-edit-toggle { top: 154px; }
            .custom-label-toggle { top: 202px; }
            .custom-legend-toggle { top: 250px; }
            .custom-3d-toggle { top: 298px; }

            .layer-toggle-btn,
            .location-toggle-btn,
            .search-toggle-btn,
            .edit-toggle-btn,
            .label-toggle-btn,
            .legend-toggle-btn,
            .fullscreen-btn,
            .threed-toggle-btn {
                width: 38px;
                height: 38px;
                font-size: 1rem;
                border-radius: 10px;
            }
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
        <div id="cesiumContainer"></div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/ol@latest/dist/ol.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>

    <script>window.CESIUM_BASE_URL = 'https://cesium.com/downloads/cesiumjs/releases/1.127/Build/Cesium/';</script>
    <script src="https://cesium.com/downloads/cesiumjs/releases/1.127/Build/Cesium/Cesium.js"></script>

    <script>
        $(document).ready(function () {

            let polygons = @json($polygons ?? [], JSON_HEX_TAG);
            let lines = @json($lines ?? [], JSON_HEX_TAG);
            let points = @json($points ?? [], JSON_HEX_TAG);
            let pointDatas = @json($pointDatas ?? [], JSON_HEX_TAG);
            let polygonDatas = @json($polygonDatas ?? [], JSON_HEX_TAG);
            let ward = @json($ward ?? [], JSON_HEX_TAG);
            let buildingData = @json($buildingData ?? [], JSON_HEX_TAG);

            let allBuildings = buildingData.buildings || [];
            let usageCounts = buildingData.usage_counts || {};

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

            let imageExtentRaw = [
                {{ $ward->extent_left ?? 0 }},
                {{ $ward->extent_bottom ?? 0 }},
                {{ $ward->extent_right ?? 0 }},
                {{ $ward->extent_top ?? 0 }}
            ];

            function webMercatorToWgs84(x, y) {
                var lon = (x / 20037508.34) * 180;
                var lat = (y / 20037508.34) * 180;
                lat = 180 / Math.PI * (2 * Math.atan(Math.exp(lat * Math.PI / 180)) - Math.PI / 2);
                return [lon, lat];
            }

            const isAlreadyWgs84 =
                imageExtentRaw[0] >= -180 && imageExtentRaw[0] <= 180 &&
                imageExtentRaw[1] >= -90 && imageExtentRaw[1] <= 90 &&
                imageExtentRaw[2] >= -180 && imageExtentRaw[2] <= 180 &&
                imageExtentRaw[3] >= -90 && imageExtentRaw[3] <= 90;

            let wgs84Extent;
            let imageExtent3857;

            if (isAlreadyWgs84) {
                wgs84Extent = imageExtentRaw;

                const bl = ol.proj.fromLonLat([imageExtentRaw[0], imageExtentRaw[1]]);
                const tr = ol.proj.fromLonLat([imageExtentRaw[2], imageExtentRaw[3]]);
                imageExtent3857 = [bl[0], bl[1], tr[0], tr[1]];
            } else {
                const bl = webMercatorToWgs84(imageExtentRaw[0], imageExtentRaw[1]);
                const tr = webMercatorToWgs84(imageExtentRaw[2], imageExtentRaw[3]);

                wgs84Extent = [bl[0], bl[1], tr[0], tr[1]];
                imageExtent3857 = imageExtentRaw;
            }

            let droneImageURL = "{{ asset($ward->drone_image ?? '') }}";

            console.log('Drone Image URL:', droneImageURL);
            console.log('Original Extent:', imageExtentRaw);
            console.log('WGS84 Extent:', wgs84Extent);
            console.log('OL 3857 Extent:', imageExtent3857);

            const droneLayer = new ol.layer.Image({
                source: new ol.source.ImageStatic({
                    url: droneImageURL,
                    imageExtent: imageExtent3857,
                    imageSmoothing: false
                }),
                opacity: 0.9,
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

            const streetLayer = new ol.layer.Tile({
                title: 'Street View',
                type: 'base',
                visible: false,
                source: new ol.source.XYZ({
                    url: 'https://{a-c}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png',
                    attributions: '&copy; OpenStreetMap Contributors'
                })
            });

            const polygonSource = new ol.source.Vector();
            const lineSource = new ol.source.Vector();
            const pointSource = new ol.source.Vector();
            const buildingSource = new ol.source.Vector();

            function loadBuildingsWithColors(filteredBuildings) {
                buildingSource.clear();
                const dataToLoad = filteredBuildings || allBuildings;

                dataToLoad.forEach(building => {
                    try {
                        const coords = building.coordinates;
                        if (!coords || coords.length < 3) return;

                        const feature = new ol.Feature({
                            geometry: new ol.geom.Polygon([coords]),
                            gisid: building.gisid,
                            usage: building.usage,
                            color: building.color,
                            sqfeet: building.sqfeet,
                            type: 'Building',
                            originalData: building
                        });

                        feature.setId(building.gisid);

                        const color = building.color || '#BDBDBD';
                        feature.setStyle(new ol.style.Style({
                            fill: new ol.style.Fill({ color: color + '66' }),
                            stroke: new ol.style.Stroke({ color: color, width: 2.5 })
                        }));

                        buildingSource.addFeature(feature);
                    } catch (e) {
                        console.error('Building parse error:', e);
                    }
                });
            }

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
                        while (coords.length === 1 && Array.isArray(coords[0]) && Array.isArray(coords[0][0])) {
                            coords = coords[0];
                        }

                        const isValid = coords.length >= 2 && coords.every(c =>
                            Array.isArray(c) && c.length >= 2 &&
                            typeof c[0] === 'number' && typeof c[1] === 'number'
                        );

                        if (!isValid) return;

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

            function createPolygonStyle(feature) {
                return new ol.style.Style({
                    stroke: new ol.style.Stroke({ color: 'blue', width: 3 }),
                    fill: new ol.style.Fill({ color: 'rgba(0,0,255,0.08)' })
                });
            }

            function createLineStyle(feature) {
                return new ol.style.Style({
                    stroke: new ol.style.Stroke({ color: 'yellow', width: 4 })
                });
            }

            function createPointStyle(feature) {
                return new ol.style.Style({
                    image: new ol.style.Circle({
                        radius: 7,
                        fill: new ol.style.Fill({ color: 'blue' }),
                        stroke: new ol.style.Stroke({ color: '#fff', width: 2 })
                    })
                });
            }

            loadPolygonsToSource();
            loadLinesToSource();
            loadPointsToSource();
            loadBuildingsWithColors(allBuildings);

            const polygonLayer = new ol.layer.Vector({
                source: polygonSource,
                style: createPolygonStyle,
                visible: false,
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

            const buildingLayer = new ol.layer.Vector({
                source: buildingSource,
                visible: true,
                title: 'Buildings',
                zIndex: 10
            });

            const map = new ol.Map({
                target: 'map',
                layers: [
                    osmLayer,
                    satelliteLayer,
                    streetLayer,
                    droneLayer,
                    polygonLayer,
                    pointLayer,
                    lineLayer,
                    buildingLayer
                ],
                view: new ol.View({
                    center: ol.extent.getCenter(imageExtent3857),
                    zoom: 18
                })
            });

            let is3DActive = false;
            let cesiumViewer = null;
            let droneImageryLayer = null;
            let cesiumBuildingEntities = [];

            function showToast(msg, duration = 2500) {
                let $toast = $('#locationToast');
                if (!$toast.length) {
                    $('#map').append('<div class="location-toast" id="locationToast"></div>');
                    $toast = $('#locationToast');
                }

                $toast.text(msg).addClass('show');
                clearTimeout($toast.data('timeout'));
                $toast.data('timeout', setTimeout(() => $toast.removeClass('show'), duration));
            }

            function switchBaseLayer(selectedLayer) {
                [osmLayer, satelliteLayer, streetLayer].forEach(l => l.setVisible(l === selectedLayer));
                updateLayerUI();
            }

            function updateLayerUI() {
                const activeBase = [osmLayer, satelliteLayer, streetLayer].find(l => l.getVisible());
                const activeTitle = activeBase ? activeBase.get('title') : 'OpenStreetMap';
                const droneVisible = droneLayer.getVisible();

                $('#activeLayerBadge').text(droneVisible ? activeTitle + ' + Drone' : activeTitle);

                $('.layer-dropdown-item[data-layer-type="base"]').removeClass('active');
                $('.layer-dropdown-item[data-layer="' + activeTitle + '"]').addClass('active');

                const droneItem = $('.layer-dropdown-item[data-layer="Drone View"]');
                droneVisible ? droneItem.addClass('active') : droneItem.removeClass('active');

                if (droneImageryLayer) {
                    droneImageryLayer.show = droneVisible;
                }
            }

            function normalizeExtent(extent) {
                if (!Array.isArray(extent) || extent.length !== 4) return null;

                const west = parseFloat(extent[0]);
                const south = parseFloat(extent[1]);
                const east = parseFloat(extent[2]);
                const north = parseFloat(extent[3]);

                if (![west, south, east, north].every(Number.isFinite)) return null;
                if (west >= east || south >= north) return null;

                return [west, south, east, north];
            }

            function ringToLonLatFlatArray(ringCoords) {
                const flat = [];
                ringCoords.forEach(coord => {
                    const lonlat = ol.proj.toLonLat(coord);
                    flat.push(lonlat[0], lonlat[1]);
                });
                return flat;
            }

            function clearCesiumBuildings() {
                if (!cesiumViewer) return;
                cesiumBuildingEntities.forEach(entity => cesiumViewer.entities.remove(entity));
                cesiumBuildingEntities = [];
            }

            function addOsmFallback() {
                if (!cesiumViewer) return;

                try {
                    cesiumViewer.imageryLayers.removeAll();
                    cesiumViewer.imageryLayers.addImageryProvider(
                        new Cesium.OpenStreetMapImageryProvider({
                            url: 'https://tile.openstreetmap.org/'
                        })
                    );
                    droneImageryLayer = null;
                } catch (err) {
                    console.error('Failed to add OSM fallback:', err);
                }
            }

            async function init3DViewerWithDroneBase() {
                if (cesiumViewer) return cesiumViewer;

                cesiumViewer = new Cesium.Viewer('cesiumContainer', {
                    animation: false,
                    timeline: false,
                    geocoder: false,
                    homeButton: false,
                    sceneModePicker: false,
                    navigationHelpButton: false,
                    baseLayerPicker: false,
                    fullscreenButton: false,
                    imageryProvider: false,
                    terrainProvider: new Cesium.EllipsoidTerrainProvider(),
                    selectionIndicator: false,
                    infoBox: false
                });

                cesiumViewer.scene.globe.depthTestAgainstTerrain = false;
                cesiumViewer.scene.screenSpaceCameraController.enableCollisionDetection = false;

                const validWgs84Extent = normalizeExtent(wgs84Extent);

                if (!droneImageURL || !validWgs84Extent) {
                    console.warn('Drone image URL or WGS84 extent missing, using OSM fallback');
                    addOsmFallback();
                    return cesiumViewer;
                }

                try {
                    cesiumViewer.imageryLayers.removeAll();

                    const west = validWgs84Extent[0];
                    const south = validWgs84Extent[1];
                    const east = validWgs84Extent[2];
                    const north = validWgs84Extent[3];

                    const rectangle = Cesium.Rectangle.fromDegrees(west, south, east, north);

                    const provider = await Cesium.SingleTileImageryProvider.fromUrl(droneImageURL, {
                        rectangle: rectangle
                    });

                    droneImageryLayer = cesiumViewer.imageryLayers.addImageryProvider(provider);
                    droneImageryLayer.alpha = 1.0;
                    droneImageryLayer.show = true;

                    const centerLon = (west + east) / 2;
                    const centerLat = (south + north) / 2;

                    cesiumViewer.camera.flyTo({
                        destination: Cesium.Cartesian3.fromDegrees(centerLon, centerLat, 350),
                        orientation: {
                            heading: 0,
                            pitch: Cesium.Math.toRadians(-45),
                            roll: 0
                        },
                        duration: 2
                    });

                } catch (error) {
                    console.error('Error loading drone image in 3D:', error);
                    addOsmFallback();
                }

                return cesiumViewer;
            }

            function addBuildingsToCesium() {
                if (!cesiumViewer) return;

                clearCesiumBuildings();

                buildingSource.getFeatures().forEach(feature => {
                    try {
                        const geometry = feature.getGeometry();
                        if (!(geometry instanceof ol.geom.Polygon)) return;

                        const coords = geometry.getCoordinates()[0];
                        if (!coords || coords.length < 3) return;

                        const flatLonLat = ringToLonLatFlatArray(coords);
                        const original = feature.get('originalData') || {};
                        const fillColor = feature.get('color') || original.color || '#4CAF50';

                        const entity = cesiumViewer.entities.add({
                            name: 'Building ' + (feature.get('gisid') || ''),
                            polygon: {
                                hierarchy: Cesium.Cartesian3.fromDegreesArray(flatLonLat),
                                material: Cesium.Color.fromCssColorString(fillColor).withAlpha(0.45),
                                outline: true,
                                outlineColor: Cesium.Color.fromCssColorString(fillColor),
                                extrudedHeight: 20,
                                height: 0
                            }
                        });

                        cesiumBuildingEntities.push(entity);
                    } catch (err) {
                        console.error('Failed to add building to Cesium:', err);
                    }
                });
            }

            async function toggle3DView() {
                const $btn = $('#threedToggleBtn');

                if (!is3DActive) {
                    $('#map').hide();
                    $('#cesiumContainer').show();

                    await init3DViewerWithDroneBase();
                    addBuildingsToCesium();

                    is3DActive = true;
                    $btn.addClass('active-3d');
                    $btn.find('i').removeClass('bi-badge-3d').addClass('bi-badge-3d-fill');

                    showToast('3D view enabled', 1500);

                    setTimeout(() => {
                        if (cesiumViewer) cesiumViewer.resize();
                    }, 300);
                } else {
                    $('#cesiumContainer').hide();
                    $('#map').show();

                    is3DActive = false;
                    $btn.removeClass('active-3d');
                    $btn.find('i').removeClass('bi-badge-3d-fill').addClass('bi-badge-3d');

                    map.updateSize();
                    showToast('2D view enabled', 1500);
                }
            }

            const $mapContainer = $('#map');

            $mapContainer.append(`
                <div class="custom-layer-switcher">
                    <div class="layer-toggle-btn" id="layerToggleBtn"><i class="bi bi-layers"></i></div>
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
                    </div>
                </div>

                <div class="custom-3d-toggle">
                    <div class="threed-toggle-btn" id="threedToggleBtn" title="Toggle 3D View">
                        <i class="bi bi-badge-3d"></i>
                    </div>
                </div>

                <div class="location-toast" id="locationToast"></div>
            `);

            $('#layerToggleBtn').on('click', function (e) {
                e.stopPropagation();
                $('#layerDropdown').toggleClass('show');
            });

            $(document).on('click', function () {
                $('#layerDropdown').removeClass('show');
            });

            $('#layerDropdown').on('click', function (e) {
                e.stopPropagation();
            });

            $(document).on('click', '.layer-dropdown-item', function () {
                const type = $(this).data('layer-type');
                const layerName = $(this).data('layer');

                if (type === 'base') {
                    if (layerName === 'OpenStreetMap') switchBaseLayer(osmLayer);
                    if (layerName === 'Satellite') switchBaseLayer(satelliteLayer);
                    if (layerName === 'Street View') switchBaseLayer(streetLayer);
                }

                if (type === 'overlay' && layerName === 'Drone View') {
                    droneLayer.setVisible(!droneLayer.getVisible());
                    updateLayerUI();
                }
            });

            $('#threedToggleBtn').on('click', function () {
                toggle3DView();
            });

            updateLayerUI();
        });
    </script>
@endpush

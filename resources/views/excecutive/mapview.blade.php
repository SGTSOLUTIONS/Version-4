@extends('layouts.office')

@section('title', 'Executive GIS Dashboard')
@section('page_title', 'Executive GIS Dashboard')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@latest/ol.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cesium.com/downloads/cesiumjs/releases/1.127/Build/Cesium/Widgets/widgets.css" rel="stylesheet" />

    <style>
        /* ─── All existing styles remain the same ─── */
        /* ... (keep all existing styles from your previous file) ... */
    </style>
@endpush

@section('content')
    <!-- ─── All existing content remains the same ─── -->
    <!-- ... (keep all existing HTML content from your previous file) ... -->
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

            // ─── SOURCES ───
            const polygonSource = new ol.source.Vector();
            const lineSource = new ol.source.Vector();
            const pointSource = new ol.source.Vector();

            // ─── BUILDING SOURCE WITH USAGE COLORS ───
            const buildingSource = new ol.source.Vector();

            let currentFilteredBuildings = allBuildings;

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
                currentFilteredBuildings = dataToLoad;
            }

            // ─── BUILDING LAYER ───
            const buildingLayer = new ol.layer.Vector({
                source: buildingSource,
                visible: true,
                title: 'Buildings',
                zIndex: 10
            });

            // ─── STYLES ───
            let showLabels = true;

            function createPolygonStyle(feature) {
                const gisid = feature.get('gisid');
                const sqft = feature.get('sqfeet') || '0';
                const polygonData = polygonDatas.find(d => d.gisid == gisid);
                const color = polygonData ? 'red' : 'blue';
                const centerPoint = feature.getGeometry().getInteriorPoint();

                const styles = [
                    new ol.style.Style({
                        stroke: new ol.style.Stroke({ color, width: 4, lineJoin: 'round', lineCap: 'round' }),
                        fill: new ol.style.Fill({ color: 'rgba(0,0,255,0.1)' })
                    })
                ];

                if (showLabels) {
                    styles.push(new ol.style.Style({
                        geometry: centerPoint,
                        text: new ol.style.Text({
                            text: sqft + ' SQFT',
                            font: 'bold 14px Arial',
                            fill: new ol.style.Fill({ color: '#000' }),
                            backgroundFill: new ol.style.Fill({ color: '#fff' }),
                            backgroundStroke: new ol.style.Stroke({ color: '#000', width: 1 }),
                            padding: [4, 6, 4, 6],
                            overflow: true,
                            textAlign: 'center',
                            offsetY: 0
                        })
                    }));
                }
                return styles;
            }

            function createLineStyle(feature) {
                const roadName = feature.get('road_name');
                const styles = [
                    new ol.style.Style({
                        stroke: new ol.style.Stroke({ color: 'yellow', width: 4, lineJoin: 'round', lineCap: 'round' })
                    })
                ];
                if (showLabels && roadName) {
                    styles.push(new ol.style.Style({
                        text: new ol.style.Text({
                            text: String(roadName),
                            font: 'bold 14px Calibri, sans-serif',
                            placement: 'line',
                            overflow: true,
                            fill: new ol.style.Fill({ color: '#000' }),
                            stroke: new ol.style.Stroke({ color: '#fff', width: 3 }),
                            repeat: 400
                        })
                    }));
                }
                return styles;
            }

            function createPointStyle(feature) {
                const gisid = feature.get('gisid');
                const pointCount = pointDatas.filter(d => d.point_gisid == gisid).length;
                const polygonData = polygonDatas.find(d => d.gisid == gisid);
                let color = 'blue';
                if (polygonData) {
                    color = pointCount > 0 ? (polygonData.number_bill == pointCount ? 'green' : 'red') : 'blue';
                }

                const style = new ol.style.Style({
                    image: new ol.style.Circle({
                        radius: 8,
                        fill: new ol.style.Fill({ color }),
                        stroke: new ol.style.Stroke({ color, width: 2 })
                    })
                });

                if (showLabels && gisid) {
                    style.setText(new ol.style.Text({
                        text: String(gisid),
                        scale: 1.3,
                        offsetY: -15,
                        fill: new ol.style.Fill({ color: '#000' }),
                        stroke: new ol.style.Stroke({ color: '#fff', width: 3 })
                    }));
                }
                return style;
            }

            function createInfraStyle(color) {
                return (feature) => {
                    const geometryType = feature.getGeometry().getType();
                    const name = feature.get('name') || feature.get('osm_id') || '';

                    if (geometryType === 'Point') {
                        return new ol.style.Style({
                            image: new ol.style.Circle({
                                radius: 8,
                                fill: new ol.style.Fill({ color }),
                                stroke: new ol.style.Stroke({ color: '#ffffff', width: 2 })
                            }),
                            text: showLabels ? new ol.style.Text({
                                text: name,
                                font: '12px Arial',
                                offsetY: -15,
                                fill: new ol.style.Fill({ color: '#000000' }),
                                stroke: new ol.style.Stroke({ color: '#ffffff', width: 2 })
                            }) : undefined
                        });
                    }

                    if (geometryType === 'LineString') {
                        return new ol.style.Style({
                            stroke: new ol.style.Stroke({ color, width: 4 })
                        });
                    }

                    if (geometryType === 'Polygon' || geometryType === 'MultiPolygon') {
                        return new ol.style.Style({
                            fill: new ol.style.Fill({ color: color + '33' }),
                            stroke: new ol.style.Stroke({ color, width: 2 })
                        });
                    }
                };
            }

            // ─── LOAD SOURCES ───
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
                polygonLayer.setStyle(createPolygonStyle);
                lineLayer.setStyle(createLineStyle);
                pointLayer.setStyle(createPointStyle);
                Object.values(infraLayers).forEach(layer => {
                    const title = layer.get('title');
                    if (title && infraColors[title]) {
                        layer.setStyle(createInfraStyle(infraColors[title]));
                    }
                });
            }

            loadPolygonsToSource();
            loadLinesToSource();
            loadPointsToSource();

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

            let watchId = null, locationFeature = null, accuracyFeature = null;
            let liveActive = false, trackActive = false;
            let currentLocation = null;

            function updateLiveMarker(lon, lat, accuracy) {
                const coords = ol.proj.fromLonLat([lon, lat]);
                currentLocation = { lon, lat };
                if (!locationFeature) {
                    locationFeature = new ol.Feature({
                        geometry: new ol.geom.Point(coords)
                    });
                    locationFeature.setStyle(new ol.style.Style({
                        image: new ol.style.Circle({
                            radius: 10,
                            fill: new ol.style.Fill({ color: '#3b82f6' }),
                            stroke: new ol.style.Stroke({ color: '#fff', width: 3 })
                        })
                    }));
                    accuracyFeature = new ol.Feature({
                        geometry: new ol.geom.Circle(coords, accuracy || 10)
                    });
                    accuracyFeature.setStyle(new ol.style.Style({
                        fill: new ol.style.Fill({ color: 'rgba(59,130,246,0.10)' }),
                        stroke: new ol.style.Stroke({ color: 'rgba(59,130,246,0.35)', width: 1.5 })
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
                const { longitude, latitude, accuracy } = position.coords;
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
                    showToast('⚠️ Geolocation not supported', 3000);
                    return false;
                }
                if (watchId !== null) return true;

                navigator.geolocation.getCurrentPosition(
                    function(pos) {
                        onPosition(pos);
                        showToast('📍 Location acquired', 2000);
                    },
                    function(error) {
                        let msg = 'Could not get location: ';
                        switch (error.code) {
                            case error.PERMISSION_DENIED: msg += 'Please allow location access'; break;
                            case error.POSITION_UNAVAILABLE: msg += 'GPS signal weak'; break;
                            case error.TIMEOUT: msg += 'Request timed out'; break;
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
                        let msg = 'Location error: ';
                        switch (error.code) {
                            case error.PERMISSION_DENIED: msg += 'Please enable permissions.'; break;
                            case error.POSITION_UNAVAILABLE: msg += 'Location unavailable.'; break;
                            case error.TIMEOUT: msg += 'Request timed out.'; break;
                            default: msg += 'Unknown error.';
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
                $('#locationToggleBtn i').toggleClass('bi-geo-alt-fill', anyActive).toggleClass('bi-geo-alt', !anyActive);
            }

            // ─── MAP ───
            const map = new ol.Map({
                target: 'map',
                layers: [osmLayer, satelliteLayer, streetLayer, droneLayer, polygonLayer, pointLayer,
                    lineLayer, buildingLayer, liveLocationLayer
                ],
                view: new ol.View({
                    center: ol.extent.getCenter(imageExtent),
                    zoom: 18
                })
            });

            // ─── INTERACTIONS ───
            let drawInteraction = null, selectInteraction = null;
            let routeLayer = null;
            let selectedFeatureForSplit = null;
            let modifyInteraction = null;
            let translateInteraction = null;
            let selectedFeatureForEdit = null;
            let originalGeometry = null;
            let legendVisible = false;

            const tempDrawSource = new ol.source.Vector();
            const tempDrawLayer = new ol.layer.Vector({
                source: tempDrawSource,
                style: new ol.style.Style({
                    fill: new ol.style.Fill({ color: 'rgba(255,0,0,0.2)' }),
                    stroke: new ol.style.Stroke({ color: '#ff0000', width: 3 }),
                    image: new ol.style.Circle({ radius: 7, fill: new ol.style.Fill({ color: '#ff0000' }) })
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

            function showFlashMessage(message, type = 'info') {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                let icon = 'info';
                if (type === 'success') icon = 'success';
                else if (type === 'error') icon = 'error';
                else if (type === 'warning') icon = 'warning';
                Toast.fire({ icon, title: message });
            }

            // ─── Disable interactions ───
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
            }

            // ─── FILTER FUNCTIONS ───
            function filterBuildings() {
                const selectedUsage = $('#usageFilter').val();
                const minArea = parseFloat($('#areaMin').val()) || 0;
                const maxArea = parseFloat($('#areaMax').val()) || Infinity;
                const variationFilter = $('#usageVariationFilter').val();

                let filtered = allBuildings.filter(building => {
                    if (selectedUsage !== 'all' && building.usage !== selectedUsage) return false;
                    const area = parseFloat(building.sqfeet || 0);
                    if (area < minArea || area > maxArea) return false;
                    if (variationFilter === 'match') {
                        const variation = buildingVariations[building.gisid];
                        if (!variation || variation.usage_status !== 'MATCH') return false;
                    } else if (variationFilter === 'variation') {
                        const variation = buildingVariations[building.gisid];
                        if (!variation || variation.usage_status !== 'VARIATION') return false;
                    }
                    return true;
                });

                currentFilteredBuildings = filtered;
                $('#buildingCountDisplay').text(`Showing: ${filtered.length} buildings`);
                loadBuildingsWithColors(filtered);
                updateLegendCounts(filtered);

                // Refresh 3D buildings if active
                if (is3DActive) {
                    setTimeout(refreshCesiumBuildings, 300);
                }

                if (filtered.length === allBuildings.length) {
                    showToast('📊 Showing all buildings', 1500);
                } else {
                    showToast(`✅ Showing ${filtered.length} buildings with applied filters`, 2000);
                }
            }

            function updateLegendCounts(filtered) {
                const counts = {};
                filtered.forEach(b => { counts[b.usage] = (counts[b.usage] || 0) + 1; });
                $('.usage-legend .legend-count').each(function() {
                    const parentText = $(this).closest('.legend-item').find('.legend-label').text().toUpperCase();
                    $(this).text(counts[parentText] || 0);
                });
            }

            function resetFilters() {
                $('#usageFilter').val('all');
                $('#areaMin').val({{ $areaStats['min'] ?? 0 }});
                $('#areaMax').val({{ $areaStats['max'] ?? 0 }});
                $('#usageVariationFilter').val('all');
                currentFilteredBuildings = allBuildings;
                $('#buildingCountDisplay').text(`Showing: ${allBuildings.length} buildings`);
                loadBuildingsWithColors(allBuildings);
                updateLegendCounts(allBuildings);
                if (is3DActive) setTimeout(refreshCesiumBuildings, 300);
                showToast('🔄 Reset all filters - showing all buildings', 2000);
            }

            function clearFilters() {
                $('#usageFilter').val('all');
                $('#areaMin').val('');
                $('#areaMax').val('');
                $('#usageVariationFilter').val('all');
                currentFilteredBuildings = allBuildings;
                $('#buildingCountDisplay').text(`Showing: ${allBuildings.length} buildings`);
                loadBuildingsWithColors(allBuildings);
                updateLegendCounts(allBuildings);
                if (is3DActive) setTimeout(refreshCesiumBuildings, 300);
                showToast('🗑️ Filters cleared - showing all buildings', 2000);
            }

            // ─── BUILDING VIEW ───
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

                const mappedCount = pointDatas.filter(pd => pd.point_gisid == item.gisid).length;
                $('#bv_mapped').text(mappedCount);

                const variation = buildingVariations[item.gisid];
                if (variation) {
                    const areaBadgeClass = variation.area_status === 'MATCH' ? 'complete' : 'empty';
                    const usageBadgeClass = variation.usage_status === 'MATCH' ? 'complete' : 'empty';

                    $('#bv_variation_wrap').html(`
                        <div class="bv-variation-strip">
                            <div class="bv-variation-card">
                                <div class="stat-label">Building Area</div>
                                <div class="stat-value">${variation.building_area} <span class="stat-sub">sqft</span></div>
                            </div>
                            <div class="bv-variation-card">
                                <div class="stat-label">Assessment Area</div>
                                <div class="stat-value">${variation.assessment_area} <span class="stat-sub">sqft</span></div>
                            </div>
                            <div class="bv-variation-card">
                                <div class="stat-label">Area Variation</div>
                                <div class="stat-value">${variation.area_variation} <span class="stat-sub">(${variation.variation_percentage}%)</span></div>
                                <span class="bld-status-tag ${areaBadgeClass}">${variation.area_status}</span>
                            </div>
                            <div class="bv-variation-card">
                                <div class="stat-label">Usage Check</div>
                                <span class="bld-status-tag ${usageBadgeClass}">${variation.usage_status}</span>
                            </div>
                        </div>
                    `);
                } else {
                    $('#bv_variation_wrap').html('');
                }

                const amenities = [
                    ['Lift Room', item.liftroom], ['Head Room', item.headroom],
                    ['Overhead Tank', item.overhead_tank], ['Rainwater Harvesting', item.rainwater_harvesting],
                    ['Parking', item.parking], ['Ramp', item.ramp],
                    ['Hoarding', item.hoarding], ['CCTV', item.cctv],
                    ['Cell Tower', item.cell_tower], ['Solar Panel', item.solar_panel],
                    ['Water Connection', item.water_connection]
                ];
                let amenHtml = '';
                amenities.forEach(([label, val]) => {
                    if (val === 'Yes') {
                        amenHtml += `<span class="bld-status-tag complete me-1"><i class="bi bi-check-circle"></i> ${label}</span>`;
                    }
                });
                $('#bv_amenities').html(amenHtml || '<span class="text-muted small">No amenities recorded</span>');

                $('#bv_remarks').text(item.remarks || '—');
                $('#bv_corp_remarks').text(item.corporationremarks || '—');

                const assetUrl = window.assetUrl || "{{ asset('') }}";

                function loadImage(imgId, emptyId, errorId, imagePath) {
                    const $img = $('#' + imgId);
                    const $empty = $('#' + emptyId);
                    const $error = $('#' + errorId);

                    if (imagePath) {
                        const fullPath = imagePath.startsWith('http') ? imagePath : assetUrl + '/' + imagePath.replace(/^\/+/, '');
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

                const modal = new bootstrap.Modal(document.getElementById('buildingViewModal'));
                modal.show();
            }

            // ─── GET POINT DATA ───
            function getPointDataWithDetails(gisid, callback) {
                $.ajax({
                    url: '/commissioner/get-point-details',
                    method: 'GET',
                    data: { gisid: gisid, ward_id: {{ $ward->id }} },
                    success: function(res) {
                        if (res.status) {
                            callback(res.data);
                        } else {
                            showFlashMessage('Failed to load assessment data', 'error');
                            callback([]);
                        }
                    },
                    error: function() {
                        showFlashMessage('Failed to load assessment data', 'error');
                        callback([]);
                    }
                });
            }

            // ─── POINT DETAILS ───
            function openPointDetails(gisid) {
                currentPointGisid = gisid;
                $('#pointDetailsSearch').val('');
                $('#pdGisid').text(gisid);

                getPointDataWithDetails(gisid, function(data) {
                    currentPointRecords = data;
                    renderPointDetails(data);
                    const building = polygonDatas.find(p => p.gisid == gisid);
                    const billCount = building ? (building.number_bill || 0) : 0;
                    $('#pdBillSummary').text(`${data.length} of ${billCount} bills mapped`);
                });

                const modal = new bootstrap.Modal(document.getElementById('pointDetailsModal'));
                modal.show();
            }

            function renderPointDetails(records) {
                if (!records || !records.length) {
                    $('#pointDetailsContainer').html(
                        '<div class="bld-empty-state text-muted"><i class="bi bi-inbox fs-2"></i><p class="mt-2 mb-0">No assessment records found</p></div>'
                    );
                    return;
                }

                const v = (val) => (val === null || val === undefined || val === '') ? '<span class="text-muted">-</span>' : val;

                let html = '';
                records.forEach(record => {
                    const pd = record.point || {};
                    const mis = record.mis || {};
                    const wt = record.water_tax || {};
                    const ugd = record.ugd_tax || {};
                    const ptList = record.professional_tax || [];

                    const qcFilled = [pd.qcusage, pd.qcsqfeet, pd.qc_remarks]
                        .filter(val => val !== null && val !== '' && val !== undefined).length;
                    const qcClass = qcFilled === 3 ? 'complete' : qcFilled === 0 ? 'empty' : 'partial';
                    const qcLabel = qcFilled === 3 ? 'QC Complete' : qcFilled === 0 ? 'QC Pending' : 'QC Partial';

                    html += `
                        <div class="point-data-card" data-id="${pd.id}">
                            <div class="point-data-card-header">
                                <div>
                                    <div class="point-data-card-title">${v(pd.owner_name)}</div>
                                    <div class="point-data-card-subtitle">Assessment: ${v(pd.assessment)} • Door: ${v(pd.new_door_no || pd.old_door_no)}</div>
                                </div>
                                <div class="point-data-card-actions">
                                    <span class="bld-status-tag ${qcClass}" style="margin-right:6px;">${qcLabel}</span>
                                    <button class="pdc-action-btn pdc-qc-btn" title="Quality Check" data-id="${pd.id}" data-qc-btn><i class="bi bi-clipboard-check"></i></button>
                                </div>
                            </div>

                            <div class="tax-card-title mt-2"><i class="bi bi-person-badge me-1"></i>Point / Assessment Details</div>
                            <div class="point-data-card-grid" style="display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-bottom:10px;">
                                <div class="pdc-field"><div class="pdc-field-label">Assessment Type</div><div class="pdc-field-val">${v(pd.assessment_type)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Old Assessment</div><div class="pdc-field-val">${v(pd.old_assessment)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Worker Name</div><div class="pdc-field-val">${v(pd.worker_name)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Present Owner</div><div class="pdc-field-val">${v(pd.present_owner_name)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">EB Number</div><div class="pdc-field-val">${v(pd.eb)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Floor</div><div class="pdc-field-val">${v(pd.floor)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Bill Usage</div><div class="pdc-field-val">${v(pd.bill_usage)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Aadhar No</div><div class="pdc-field-val">${v(pd.aadhar_no)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Ration No</div><div class="pdc-field-val">${v(pd.ration_no)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Phone</div><div class="pdc-field-val">${v(pd.phone_number)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Old Door No</div><div class="pdc-field-val">${v(pd.old_door_no)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">New Door No</div><div class="pdc-field-val">${v(pd.new_door_no)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Plot Area</div><div class="pdc-field-val">${v(pd.plot_area)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Water Tax No</div><div class="pdc-field-val">${v(pd.water_tax)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Half Year Tax</div><div class="pdc-field-val">${v(pd.halfyeartax)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Balance</div><div class="pdc-field-val">${v(pd.balance)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">No. of Persons</div><div class="pdc-field-val">${v(pd.no_of_persons)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Zone</div><div class="pdc-field-val">${v(pd.zone)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">Remarks</div><div class="pdc-field-val">${v(pd.remarks)}</div></div>
                            </div>

                            <div class="tax-card-title mt-2"><i class="bi bi-clipboard-check me-1"></i>QC Details</div>
                            <div class="point-data-card-grid" style="display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-bottom:10px;">
                                <div class="pdc-field"><div class="pdc-field-label">QC Usage</div><div class="pdc-field-val ${!pd.qcusage ? 'empty' : ''}">${v(pd.qcusage)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">QC Sq.Feet</div><div class="pdc-field-val ${!pd.qcsqfeet ? 'empty' : ''}">${v(pd.qcsqfeet)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">QC By</div><div class="pdc-field-val">${v(pd.qc_name)}</div></div>
                                <div class="pdc-field"><div class="pdc-field-label">QC Remarks</div><div class="pdc-field-val">${v(pd.qc_remarks)}</div></div>
                            </div>

                            <div class="row mt-2 g-2">
                                <div class="col-md-3">
                                    <div class="tax-card">
                                        <div class="tax-card-title"><i class="bi bi-database me-1"></i>MIS Record</div>
                                        <div class="tax-card-row"><span class="tax-card-label">Road</span><span class="tax-card-value">${v(mis.road_name)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Ward No</span><span class="tax-card-value">${v(mis.ward_no)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Plot Area</span><span class="tax-card-value">${v(mis.plot_area)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Half Yr Tax</span><span class="tax-card-value">${v(mis.half_year_tax)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Balance</span><span class="tax-card-value">${v(mis.balance)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Usage</span><span class="tax-card-value">${v(mis.usage)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Type</span><span class="tax-card-value">${v(mis.type)}</span></div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="tax-card">
                                        <div class="tax-card-title"><i class="bi bi-droplet me-1"></i>Water Tax</div>
                                        <div class="tax-card-row"><span class="tax-card-label">Number</span><span class="tax-card-value">${v(wt.watertax_no)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Old Number</span><span class="tax-card-value">${v(wt.old_watertax_no)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Road</span><span class="tax-card-value">${v(wt.road_name)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Slab Rate</span><span class="tax-card-value">${v(wt.slab_rate)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Balance</span><span class="tax-card-value">${v(wt.balance)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Usage</span><span class="tax-card-value">${v(wt.usage)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Slab Desc</span><span class="tax-card-value">${v(wt.slab_description)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">DBC Type</span><span class="tax-card-value">${v(wt.DBC_type)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Phone</span><span class="tax-card-value">${v(wt.phone_number)}</span></div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="tax-card">
                                        <div class="tax-card-title"><i class="bi bi-pipe me-1"></i>UGD Tax</div>
                                        <div class="tax-card-row"><span class="tax-card-label">Number</span><span class="tax-card-value">${v(ugd.ugd_no)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Old Number</span><span class="tax-card-value">${v(ugd.old_ugd_no)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Owner</span><span class="tax-card-value">${v(ugd.owner_name)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Slab Rate</span><span class="tax-card-value">${v(ugd.slab_rate)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Balance</span><span class="tax-card-value">${v(ugd.balance)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Tax Year</span><span class="tax-card-value">${v(ugd.tax_year)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Tax Amount</span><span class="tax-card-value">${v(ugd.ugd_tax_amount)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Tax Due</span><span class="tax-card-value">${v(ugd.ugd_tax_due)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Tax Paid</span><span class="tax-card-value">${v(ugd.ugd_tax_paid)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Paid Date</span><span class="tax-card-value">${v(ugd.ugd_tax_paid_date)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Payment Mode</span><span class="tax-card-value">${v(ugd.payment_mode)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Receipt No</span><span class="tax-card-value">${v(ugd.receipt_number)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Due Date</span><span class="tax-card-value">${v(ugd.due_date)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Status</span><span class="tax-card-value">${v(ugd.status)}</span></div>
                                        <div class="tax-card-row"><span class="tax-card-label">Remarks</span><span class="tax-card-value">${v(ugd.remarks)}</span></div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="tax-card">
                                        <div class="tax-card-title"><i class="bi bi-briefcase me-1"></i>Professional Tax (${ptList.length})</div>
                                        ${ptList.length ? ptList.map(pt => `
                                            <div style="border-bottom:1px dashed #e5e7eb; padding:6px 0; margin-bottom:4px;">
                                                <div class="tax-card-row"><span class="tax-card-label">PT No</span><span class="tax-card-value">${v(pt.pt_number)}</span></div>
                                                <div class="tax-card-row"><span class="tax-card-label">Old PT No</span><span class="tax-card-value">${v(pt.old_pt_number)}</span></div>
                                                <div class="tax-card-row"><span class="tax-card-label">Establishment</span><span class="tax-card-value">${v(pt.establishment_name)}</span></div>
                                                <div class="tax-card-row"><span class="tax-card-label">Profession</span><span class="tax-card-value">${v(pt.profession_type)}</span></div>
                                                <div class="tax-card-row"><span class="tax-card-label">Employees</span><span class="tax-card-value">${v(pt.employee_count)}</span></div>
                                                <div class="tax-card-row"><span class="tax-card-label">Half Yr Tax</span><span class="tax-card-value">${v(pt.half_year_tax)}</span></div>
                                                <div class="tax-card-row"><span class="tax-card-label">Arrears</span><span class="tax-card-value">${v(pt.arrears)}</span></div>
                                                <div class="tax-card-row"><span class="tax-card-label">Penalty</span><span class="tax-card-value">${v(pt.penalty)}</span></div>
                                                <div class="tax-card-row"><span class="tax-card-label">Balance</span><span class="tax-card-value">${v(pt.balance)}</span></div>
                                                <div class="tax-card-row"><span class="tax-card-label">Status</span><span class="tax-card-value">${v(pt.payment_status)}</span></div>
                                                <div class="tax-card-row"><span class="tax-card-label">Remarks</span><span class="tax-card-value">${v(pt.remarks)}</span></div>
                                            </div>
                                        `).join('') : '<div class="tax-card-row"><span class="tax-card-label">No records</span></div>'}
                                    </div>
                                </div>
                            </div>
                        </div>`;
                });

                $('#pointDetailsContainer').html(html);

                $('#pointDetailsSearch').off('input').on('input', function() {
                    const searchVal = $(this).val().toLowerCase();
                    if (!searchVal) {
                        renderPointDetails(records);
                        return;
                    }
                    const filtered = records.filter(record => {
                        const pd = record.point || {};
                        return (pd.assessment || '').toString().toLowerCase().includes(searchVal) ||
                            (pd.owner_name || '').toLowerCase().includes(searchVal) ||
                            (pd.phone_number || '').toString().toLowerCase().includes(searchVal);
                    });
                    renderPointDetails(filtered);
                });
            }

            // ─── QC ───
            function openQcModal(id) {
                const record = currentPointRecords.find(r => r.point && r.point.id == id);
                const pd = record ? record.point : null;
                if (!pd) {
                    showFlashMessage('Could not find this assessment record.', 'error');
                    return;
                }
                $('#qc_point_data_id').val(id);
                $('#qc_owner_display').text(pd.owner_name || '');
                $('#qc_assessment_display').text(pd.assessment || '');
                $('#qcusage').val(pd.qcusage || '');
                $('#qcsqfeet').val(pd.qcsqfeet || '');
                $('#qc_remarks').val(pd.qc_remarks || '');
                const modal = new bootstrap.Modal(document.getElementById('qcModal'));
                modal.show();
            }

            $(document).on('click', '#saveQcBtn', function() {
                const id = $('#qc_point_data_id').val();
                const $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

                $.ajax({
                    url: `/point-data/${id}/qc`,
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        qcusage: $('#qcusage').val(),
                        qcsqfeet: $('#qcsqfeet').val(),
                        qc_remarks: $('#qc_remarks').val(),
                        ward_id: {{ $ward->id }}
                    },
                    success: function(res) {
                        const idx = pointDatas.findIndex(p => p.id == id);
                        if (idx > -1) pointDatas[idx] = res.point_data;

                        $('#qcModal').modal('hide');
                        showFlashMessage('QC data saved successfully!', 'success');

                        if (currentPointGisid) {
                            getPointDataWithDetails(currentPointGisid, function(data) {
                                currentPointRecords = data;
                                renderPointDetails(data);
                            });
                        }
                    },
                    error: function(xhr) {
                        showFlashMessage(xhr.responseJSON?.message || 'Failed to save QC data.', 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i>Save QC');
                    }
                });
            });

            $(document).on('click', '.pdc-qc-btn', function() {
                openQcModal($(this).data('id'));
            });

            // ─── CLICK HANDLERS ───
            function buildingClickHandler(feature) {
                const gisid = feature.get('gisid');
                let building = polygonDatas.find(p => p.gisid == gisid);
                if (building) {
                    showBuildingView(building);
                } else {
                    showFlashMessage('No building data found for this GIS ID', 'warning');
                }
            }

            function pointClick(feature) {
                const gisid = feature.get('gisid');
                let building = polygonDatas.find(polygondata => polygondata.gisid == gisid);
                if (building) {
                    openPointDetails(gisid);
                } else {
                    showFlashMessage('No building data found for this point', 'warning');
                }
            }

            function lineClick(feature) {
                const gisid = feature.get('gisid');
                const roadName = feature.get('road_name') || '';
                $('#line_gisid').val(gisid || '');
                $('#line_road_name').val(roadName || '');
                const modal = new bootstrap.Modal(document.getElementById('lineDetailsModal'));
                modal.show();
            }

            function showFeatureDetails(feature) {
                if (!feature) return;
                const type = feature.get('type');
                switch (type) {
                    case 'Building': buildingClickHandler(feature); break;
                    case 'Point': pointClick(feature); break;
                    case 'LineString': lineClick(feature); break;
                }
            }

            // ─── VIEW MODE ───
            function setNoneMode() {
                disableAllInteractions();
                hideSplitButton();
                hideEditControls();

                const viewInter = new ol.interaction.Select({
                    layers: [buildingLayer, lineLayer, pointLayer],
                    style: new ol.style.Style({
                        stroke: new ol.style.Stroke({ color: '#0066cc', width: 2, lineDash: [4, 4] }),
                        fill: new ol.style.Fill({ color: 'rgba(0,102,204,0.05)' }),
                        image: new ol.style.Circle({ radius: 6, fill: new ol.style.Fill({ color: '#0066cc' }),
                            stroke: new ol.style.Stroke({ color: '#fff', width: 2 }) })
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
                showToast('👁️ Click on features to view details', 2000);
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
                            searchText: `${poly.gisid} ${poly.sqfeet} building`
                        });
                    } catch (e) { console.error('Error parsing polygon:', e); }
                });
                lines.forEach(line => {
                    try {
                        const coords = typeof line.coordinates === 'string' ? JSON.parse(line.coordinates) : line.coordinates;
                        searchIndex.push({
                            id: line.gisid,
                            type: 'line',
                            title: line.road_name || `GIS ID: ${line.gisid}`,
                            subtitle: `Road (GIS ID: ${line.gisid})`,
                            coordinates: coords,
                            geometryType: 'line',
                            searchText: `${line.gisid} ${line.road_name || ''} road`
                        });
                    } catch (e) { console.error('Error parsing line:', e); }
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
                    } catch (e) { console.error('Error parsing point:', e); }
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
                    } catch (e) { console.error('Error parsing pointData:', e); }
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
                map.getView().animate({ center: coords, zoom: 22, duration: 1000 });
            }

            function getCoordsByGisId(gisid) {
                const point = points.find(p => p.gisid && p.gisid.toString() === gisid.toString());
                if (!point) return null;
                try {
                    const coords = typeof point.coordinates === 'string' ? JSON.parse(point.coordinates) : point.coordinates;
                    return coords;
                } catch (e) {
                    console.error('Coord parse error for gisid:', gisid, e);
                    return null;
                }
            }

            // ─── ROUTING ───
            function getCurrentLocation(callback) {
                if (!navigator.geolocation) {
                    Swal.fire('Error', 'Geolocation not supported', 'error');
                    callback(null);
                    return false;
                }
                if (currentLocation) {
                    callback(currentLocation);
                    return true;
                }
                navigator.geolocation.getCurrentPosition(
                    function(pos) {
                        currentLocation = { lon: pos.coords.longitude, lat: pos.coords.latitude };
                        callback(currentLocation);
                    },
                    function(error) {
                        Swal.fire('Location Error', 'Could not get your location', 'error');
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
                const url = `https://router.project-osrm.org/route/v1/driving/${startLon},${startLat};${endLon},${endLat}?overview=full&geometries=geojson`;
                showToast('🗺️ Calculating route...', 2000);
                fetch(url).then(r => r.json()).then(data => {
                    if (!data.routes || !data.routes.length) {
                        Swal.fire('Error', 'No route found', 'error');
                        return;
                    }
                    const routeCoords = data.routes[0].geometry.coordinates.map(c => ol.proj.fromLonLat(c));
                    const routeFeature = new ol.Feature({ geometry: new ol.geom.LineString(routeCoords) });
                    if (routeLayer) map.removeLayer(routeLayer);
                    routeLayer = new ol.layer.Vector({
                        source: new ol.source.Vector({ features: [routeFeature] }),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({ color: '#0066ff', width: 5, lineDash: [10, 5] })
                        })
                    });
                    map.addLayer(routeLayer);
                    const ext = routeFeature.getGeometry().getExtent();
                    if (ext && ext[0] !== ext[2]) map.getView().fit(ext, { padding: [50, 50, 50, 50], duration: 1000 });
                    const dist = (data.routes[0].distance / 1000).toFixed(2);
                    const dur = Math.round(data.routes[0].duration / 60);
                    showToast(`✅ Route found! Distance: ${dist}km, Time: ${dur}min`, 4000);
                }).catch(() => {
                    Swal.fire('Error', 'Failed to calculate route', 'error');
                });
            }

            function getDirectionToFeature(feature) {
                getCurrentLocation(function(loc) {
                    if (!loc) return;
                    const coords = getCoordsByGisId(feature.id);
                    if (!coords) {
                        Swal.fire('Error', `No coordinates for GIS ID: ${feature.id}`, 'error');
                        return;
                    }
                    const lonLat = ol.proj.toLonLat(coords);
                    getRoute(loc.lon, loc.lat, lonLat[0], lonLat[1]);
                });
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
                return [osmLayer, satelliteLayer, streetLayer].find(l => l.getVisible())?.get('title') || 'OpenStreetMap';
            }

            function updateLayerUI() {
                const activeTitle = getActiveBaseLayerTitle();
                const droneVisible = droneLayer.getVisible();
                $activeLayerBadge.text(droneVisible ? activeTitle + ' + Drone' : activeTitle);
                $('.layer-dropdown-item[data-layer-type="base"]').removeClass('active');
                $(`.layer-dropdown-item[data-layer="${activeTitle}"]`).addClass('active');
                const droneItem = $('.layer-dropdown-item[data-layer="Drone View"]');
                droneVisible ? droneItem.addClass('active') : droneItem.removeClass('active');

                $('.infrastructure-legend .legend-item').each(function() {
                    const type = $(this).data('type');
                    if (type && infraLayers[type]) {
                        const visible = infraLayers[type].getVisible();
                        $(this).toggleClass('inactive', !visible);
                    }
                });
            }

            function switchBaseLayer(selectedLayer) {
                [osmLayer, satelliteLayer, streetLayer].forEach(l => l.setVisible(l === selectedLayer));
                updateLayerUI();
            }

            function toggleDroneLayer() {
                droneLayer.setVisible(!droneLayer.getVisible());
                updateLayerUI();
                if (droneImageryLayer) {
                    droneImageryLayer.show = droneLayer.getVisible();
                }
            }

            // ─── LABEL TOGGLE ───
            function toggleLabels() {
                showLabels = !showLabels;
                $('#labelToggleBtn').toggleClass('active-label', showLabels);
                $('#labelToggleBtn i').toggleClass('bi-fonts', showLabels).toggleClass('bi-fonts', !showLabels);
                reloadAllSources();
                loadBuildingsWithColors(currentFilteredBuildings);
                showToast(showLabels ? '🏷️ Labels ON' : '🏷️ Labels OFF', 1500);
            }

            // ─── LEGEND TOGGLE ───
            function toggleLegend() {
                const $legend = $('.infrastructure-legend');
                if (!$legend.length) {
                    showToast('⚠️ Infrastructure data not loaded yet', 2000);
                    return;
                }
                legendVisible = !legendVisible;
                $legend.toggleClass('show', legendVisible);
                $('#legendToggleBtn').toggleClass('active-legend', legendVisible);
                showToast(legendVisible ? '🏗️ Legend shown' : '🏗️ Legend hidden', 1500);
            }

            // ─── INFRASTRUCTURE LAYERS ───
            let infraLayers = {};
            let infraGrouped = {};

            const infraColors = {
                'Road': '#FF6B6B', 'Road Junction': '#FFB74D', 'Bus Stop': '#FFA726',
                'Traffic Signal': '#F44336', 'Bridge': '#8D6E63', 'Drainage Line': '#4FC3F7',
                'Storm Water Line': '#4DD0E1', 'Sewer Line': '#9575CD', 'Water Supply Line': '#4DB6AC',
                'Waterbody': '#29B6F6', 'Canal': '#0288D1', 'Culvert': '#00897B',
                'Fire Hydrant': '#EF5350', 'Water Valve': '#81C784', 'Street Light': '#FFD54F',
                'Electric Pole': '#FF8A65', 'Street Manhole': '#A1887F', 'Transformer': '#AB47BC',
                'Building': '#78909C', 'Boundary Wall': '#795548', 'Park': '#66BB6A',
                'Playground': '#AED581', 'Cemetery': '#A1887F', 'Tree': '#388E3C'
            };

            function loadInfrastructure(wardId) {
                $.ajax({
                    url: `/infrastructure/data/${wardId}`,
                    method: 'GET',
                    success: function(res) {
                        if (res.success && res.data?.features?.length) {
                            displayInfrastructure(res.data);
                        }
                    },
                    error: function() {
                        console.warn('Infrastructure data not available');
                    }
                });
            }

            function displayInfrastructure(geojson) {
                if (!geojson?.features?.length) return;

                const grouped = {};
                geojson.features.forEach(feature => {
                    const type = feature.properties.type || 'Unknown';
                    if (!grouped[type]) grouped[type] = [];
                    grouped[type].push(feature);
                });
                infraGrouped = grouped;

                Object.entries(grouped).forEach(([type, features]) => {
                    const color = infraColors[type] || '#999999';
                    const source = new ol.source.Vector();

                    features.forEach(feature => {
                        let geometry = null;
                        try {
                            switch (feature.geometry.type) {
                                case 'Point':
                                    geometry = new ol.geom.Point(ol.proj.fromLonLat(feature.geometry.coordinates));
                                    break;
                                case 'LineString':
                                    geometry = new ol.geom.LineString(feature.geometry.coordinates.map(c => ol.proj.fromLonLat(c)));
                                    break;
                                case 'Polygon':
                                    geometry = new ol.geom.Polygon([feature.geometry.coordinates[0].map(c => ol.proj.fromLonLat(c))]);
                                    break;
                                case 'MultiPolygon':
                                    geometry = new ol.geom.MultiPolygon(
                                        feature.geometry.coordinates.map(poly => poly[0].map(c => ol.proj.fromLonLat(c)))
                                    );
                                    break;
                            }
                        } catch (e) { return; }

                        if (!geometry) return;
                        const olFeature = new ol.Feature({
                            geometry,
                            type,
                            name: feature.properties.name || '',
                            osm_id: feature.properties.osm_id || '',
                            properties: feature.properties
                        });
                        olFeature.setId(feature.properties.osm_id || feature.properties.id || Math.random().toString());
                        source.addFeature(olFeature);
                    });

                    const layer = new ol.layer.Vector({
                        source,
                        style: createInfraStyle(color),
                        title: type,
                        visible: true
                    });

                    map.addLayer(layer);
                    infraLayers[type] = layer;

                    const clickHandler = new ol.interaction.Select({
                        layers: [layer],
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({ color: '#0000ff', width: 3 }),
                            fill: new ol.style.Fill({ color: 'rgba(0,0,255,0.1)' })
                        })
                    });

                    clickHandler.on('select', function(e) {
                        if (e.selected.length > 0) {
                            showInfraProperties(e.selected[0]);
                            setTimeout(() => clickHandler.getFeatures().clear(), 100);
                        }
                    });

                    map.addInteraction(clickHandler);
                });

                createInfraLegend(grouped);
            }

            function showInfraProperties(feature) {
                const props = feature.get('properties') || {};
                const type = feature.get('type') || 'Unknown';
                const name = feature.get('name') || props.name || '';

                $('#infraType').text(type);

                let html = `
                    <div class="bld-section-divider mb-3"><i class="bi bi-info-circle me-2"></i>Feature Properties</div>
                    <table class="infra-prop-table">
                        <tr><td class="label-cell">Type</td><td class="value-cell"><strong>${type}</strong></td></tr>
                        <tr><td class="label-cell">Name</td><td class="value-cell">${name || '-'}</td></tr>
                        <tr><td class="label-cell">OSM ID</td><td class="value-cell">${feature.get('osm_id') || props.osm_id || '-'}</td></tr>
                `;

                Object.keys(props).forEach(key => {
                    if (key !== 'type' && key !== 'name' && key !== 'osm_id') {
                        const val = props[key];
                        if (val !== null && val !== undefined && val !== '') {
                            html += `<tr><td class="label-cell">${key.replace(/_/g, ' ').toUpperCase()}</td><td class="value-cell">${val}</td></tr>`;
                        }
                    }
                });

                html += `</table>`;

                const geom = feature.getGeometry();
                if (geom) {
                    const geomType = geom.getType();
                    html += `
                        <div class="bld-section-divider mt-3 mb-2"><i class="bi bi-geo me-2"></i>Geometry</div>
                        <table class="infra-prop-table">
                            <tr><td class="label-cell">Geometry Type</td><td class="value-cell">${geomType}</td></tr>
                    `;
                    if (geomType === 'Point') {
                        const coords = geom.getCoordinates();
                        const lonLat = ol.proj.toLonLat(coords);
                        html += `
                            <tr><td class="label-cell">Longitude</td><td class="value-cell">${lonLat[0].toFixed(6)}</td></tr>
                            <tr><td class="label-cell">Latitude</td><td class="value-cell">${lonLat[1].toFixed(6)}</td></tr>
                        `;
                    }
                    html += `</table>`;
                }

                $('#infraPropertiesContent').html(html);
                const modal = new bootstrap.Modal(document.getElementById('infraPropertiesModal'));
                modal.show();
            }

            function createInfraLegend(grouped) {
                $('.infrastructure-legend').remove();

                const legend = document.createElement('div');
                legend.className = 'infrastructure-legend' + (legendVisible ? ' show' : '');
                legend.innerHTML = `
                    <div class="legend-title">
                        <span>🏗️ Infrastructure</span>
                        <button class="toggle-all-btn" id="toggleAllInfra">Toggle All</button>
                    </div>
                `;

                const types = Object.keys(grouped).sort();
                types.forEach(type => {
                    const color = infraColors[type] || '#999999';
                    const count = grouped[type].length;
                    const item = document.createElement('div');
                    item.className = 'legend-item';
                    item.dataset.type = type;
                    item.innerHTML = `
                        <span class="color-dot" style="background:${color};"></span>
                        <span style="flex:1; font-size:12px;">${type}</span>
                        <span class="count-badge">${count}</span>
                    `;

                    item.addEventListener('click', () => {
                        const layer = infraLayers[type];
                        if (!layer) return;
                        const visible = !layer.getVisible();
                        layer.setVisible(visible);
                        item.classList.toggle('inactive', !visible);
                    });

                    legend.appendChild(item);
                });

                document.getElementById('map').appendChild(legend);
                $('#legendToggleBtn').toggleClass('active-legend', legendVisible);

                $('#toggleAllInfra').on('click', function(e) {
                    e.stopPropagation();
                    const allVisible = Object.values(infraLayers).every(l => l.getVisible());
                    Object.values(infraLayers).forEach(l => l.setVisible(!allVisible));
                    $('.infrastructure-legend .legend-item').toggleClass('inactive', allVisible);
                });
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

            $mapContainer.append(`
                <div class="custom-label-toggle">
                    <div class="label-toggle-btn active-label" id="labelToggleBtn" title="Toggle Labels">
                        <i class="bi bi-fonts"></i>
                    </div>
                </div>
            `);

            $mapContainer.append(`
                <div class="custom-legend-toggle">
                    <div class="legend-toggle-btn" id="legendToggleBtn" title="Toggle Infrastructure Legend">
                        <i class="bi bi-list-ul"></i>
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

            $mapContainer.append(`
                <div class="custom-edit-toggle">
                    <div class="edit-toggle-btn" id="editToggleBtn"><i class="bi bi-pencil-square"></i></div>
                    <div class="edit-dropdown" id="editDropdown">
                        <div class="dropdown-header">🔧 Modes</div>
                        <div class="edit-dropdown-item active" data-tool="none">
                            <div class="edit-icon"><i class="bi bi-eye"></i></div>
                            <div class="edit-name">View Only</div>
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

            $mapContainer.append(`
                <div class="custom-3d-toggle">
                    <div class="threed-toggle-btn" id="threeDToggleBtn" title="Toggle 3D View">
                        <i class="bi bi-box"></i>
                    </div>
                </div>
            `);

            $mapContainer.append(`<div class="fullscreen-btn" id="fullscreenBtn"><i class="bi bi-arrows-fullscreen"></i></div>`);

            // ─── FULLSCREEN ───
            let isFullscreen = false;
            $(document).on('click', '#fullscreenBtn', function() {
                const $icon = $(this).find('i');
                const $card = $('#mapCard');
                const $container = $('#map');

                if (!isFullscreen) {
                    $card.addClass('fullscreen-mode');
                    $container.addClass('fullscreen');
                    $('.custom-3d-toggle').css('display', 'block !important');
                    $icon.removeClass('bi-arrows-fullscreen').addClass('bi-fullscreen-exit');
                    isFullscreen = true;
                } else {
                    $card.removeClass('fullscreen-mode');
                    $container.removeClass('fullscreen');
                    $icon.removeClass('bi-fullscreen-exit').addClass('bi-arrows-fullscreen');
                    isFullscreen = false;
                }

                setTimeout(function() {
                    map.updateSize();
                    if (window.is3DActive && window.cesiumViewer) {
                        window.cesiumViewer.resize();
                    }
                    $('.custom-3d-toggle').css('display', isFullscreen ? 'block !important' : 'block');
                }, 150);
            });

            // ─── LABEL TOGGLE EVENT ───
            $(document).on('click', '#labelToggleBtn', function(e) {
                e.stopPropagation();
                toggleLabels();
            });

            // ─── LEGEND TOGGLE EVENT ───
            $(document).on('click', '#legendToggleBtn', function(e) {
                e.stopPropagation();
                toggleLegend();
            });

            // ─── LAYER DROPDOWN EVENTS ───
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
                } else if (layerType === 'vector') {
                    let layer;
                    if (layerTitle === 'Polygons') layer = polygonLayer;
                    else if (layerTitle === 'Lines') layer = lineLayer;
                    else if (layerTitle === 'Points') layer = pointLayer;
                    else if (layerTitle === 'Buildings') layer = buildingLayer;
                    if (layer) {
                        const visible = !layer.getVisible();
                        layer.setVisible(visible);
                        $(this).toggleClass('active', visible);
                    }
                }
            });

            // ─── LOCATION EVENTS ───
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
                    showToast('📍 Tracking enabled', 2000);
                } else {
                    trackActive = false;
                    if (!liveActive) {
                        stopWatching();
                        clearLiveMarker();
                    }
                    showToast('📍 Tracking disabled', 2000);
                }
                syncLocationUI();
                $('#locationDropdown').removeClass('show');
            });

            $(document).on('click', '#clearRouteItem', function(e) {
                e.stopPropagation();
                clearRoute();
                $('#locationDropdown').removeClass('show');
            });

            // ─── SEARCH EVENTS ───
            $(document).on('click', '#searchToggleBtn', function(e) {
                e.stopPropagation();
                $('#searchDropdown').toggleClass('show');
                $(this).toggleClass('active-search');
                $('#locationDropdown').removeClass('show');
                $('.layer-dropdown').removeClass('show');
                $('#editDropdown').removeClass('show');
                $('#editToggleBtn').removeClass('active-edit');
                if ($('#searchDropdown').hasClass('show')) setTimeout(() => $('#gisSearchInput').focus(), 100);
            });

            $(document).on('click', '.search-tab-btn', function() {
                $('.search-tab-btn').removeClass('active');
                $(this).addClass('active');
                const tab = $(this).data('tab');
                $('#quickSearchTab').toggle(tab === 'quick');
                $('#filterTab').toggle(tab === 'filter');
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

            $(document).on('click', '.zoom-btn', function(e) {
                e.stopPropagation();
                const id = $(this).data('id');
                const type = $(this).data('type');
                const item = searchIndex.find(f => f.id == id && f.type === type);
                if (item) {
                    zoomToFeature(item.id);
                    $('#searchDropdown').removeClass('show');
                    $('#searchToggleBtn').removeClass('active-search');
                    $('#gisSearchInput').val('');
                    $('#searchResults').html('');
                }
            });

            $(document).on('click', '.direction-btn', function(e) {
                e.stopPropagation();
                const id = $(this).data('id');
                const type = $(this).data('type');
                const item = searchIndex.find(f => f.id == id && f.type === type);
                if (item) {
                    getDirectionToFeature(item);
                    $('#searchDropdown').removeClass('show');
                    $('#searchToggleBtn').removeClass('active-search');
                    $('#gisSearchInput').val('');
                    $('#searchResults').html('');
                }
            });

            // ─── FILTER SEARCH ───
            $('#applyFilterBtn').on('click', function() {
                $.get('/point-data/filter', {
                    assessment: $('#filterAssessment').val(),
                    old_assessment: $('#filterOldAssessment').val(),
                    owner_name: $('#filterOwnerName').val(),
                    phone_number: $('#filterPhoneNumber').val()
                }, function(res) {
                    let html = '';
                    (res.data || []).forEach(pd => {
                        html += `
                            <div class="search-result-item">
                                <div class="search-result-title">${pd.owner_name || 'N/A'} — ${pd.assessment || 'N/A'}</div>
                                <div class="search-result-subtitle">GIS ID: ${pd.point_gisid || 'N/A'} | Phone: ${pd.phone_number || 'N/A'}</div>
                            </div>`;
                    });
                    $('#filterResults').html(html || '<div class="p-2 text-muted">No matches</div>');
                });
            });

            // ─── FILTER EVENTS ───
            $(document).on('click', '#applyFiltersBtn', filterBuildings);
            $(document).on('click', '#resetFiltersBtn', resetFilters);
            $(document).on('click', '#clearFiltersBtn', clearFilters);

            // ─── EDIT TOGGLE EVENTS ───
            $(document).on('click', '#editToggleBtn', function(e) {
                e.stopPropagation();
                $('#editDropdown').toggleClass('show');
                $(this).toggleClass('active-edit');
                $('#locationDropdown').removeClass('show');
                $('.layer-dropdown').removeClass('show');
                $('#searchDropdown').removeClass('show');
                $('#searchToggleBtn').removeClass('active-search');
            });

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
                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteFeatureModal'));
                    deleteModal.show();
                    return;
                }

                $('.edit-dropdown-item').removeClass('active');
                $(this).addClass('active');

                switch (tool) {
                    case 'none': setNoneMode(); break;
                    case 'editPolygon': setEditPolygonMode(); break;
                    case 'movePolygon': setMovePolygonMode(); break;
                    case 'split': setSplitMode(); break;
                    case 'drawPolygon': startDrawing('Polygon'); break;
                    case 'drawLine': startDrawing('LineString'); break;
                    case 'drawPoint': startDrawing('Point'); break;
                }

                $('#editDropdown').removeClass('show');
                $('#editToggleBtn').removeClass('active-edit');
            });

            // ─── EDIT POLYGON MODE ───
            function setEditPolygonMode() {
                disableAllInteractions();
                hideSplitButton();
                hideEditControls();
                selectedFeatureForEdit = null;
                originalGeometry = null;

                map.getTargetElement().classList.add('edit-mode');

                const editSelect = new ol.interaction.Select({
                    layers: [polygonLayer],
                    style: new ol.style.Style({
                        stroke: new ol.style.Stroke({ color: '#2563eb', width: 4 }),
                        fill: new ol.style.Fill({ color: 'rgba(37,99,235,0.2)' })
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
                            stroke: new ol.style.Stroke({ color: '#2563eb', width: 5 }),
                            fill: new ol.style.Fill({ color: 'rgba(37,99,235,0.3)' })
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
                        reloadAllSources();
                        disableAllInteractions();
                        clearDrawInteraction();
                        selectedFeatureForEdit.setStyle(null);
                        selectedFeatureForEdit = null;
                        originalGeometry = null;
                        hideEditControls();
                        showToast('✅ Polygon updated!', 2000);
                        setNoneMode();
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Failed to update polygon', 'error');
                        cancelEdit();
                    }
                });
            }

            // ─── MOVE MODE ───
            function setMovePolygonMode() {
                disableAllInteractions();
                hideSplitButton();
                hideEditControls();
                selectedFeatureForEdit = null;

                map.getTargetElement().classList.add('edit-mode');

                const moveSelect = new ol.interaction.Select({
                    layers: [polygonLayer],
                    style: new ol.style.Style({
                        stroke: new ol.style.Stroke({ color: '#f59e0b', width: 4 }),
                        fill: new ol.style.Fill({ color: 'rgba(245,158,11,0.2)' })
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
                            stroke: new ol.style.Stroke({ color: '#f59e0b', width: 5 }),
                            fill: new ol.style.Fill({ color: 'rgba(245,158,11,0.3)' })
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
                        showToast('✅ Polygon moved!', 2000);
                        setNoneMode();
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Failed to move polygon', 'error');
                        cancelMove();
                    }
                });
            }

            // ─── SPLIT MODE ───
            function setSplitMode() {
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
                            stroke: new ol.style.Stroke({ color: '#dc3545', width: 5 }),
                            fill: new ol.style.Fill({ color: 'rgba(220,53,69,0.3)' })
                        }));
                        selectedFeatureForSplit = feature;
                        showToast(`✂️ Polygon selected (ID: ${feature.get('gisid')})`, 3000);
                        showSplitButton(feature);
                    }
                });

                map.addInteraction(splitInter);
                selectInteraction = splitInter;
                showToast('✂️ Click a polygon to split', 2000);
            }

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
                        fill: new ol.style.Fill({ color: 'rgba(0,255,0,0.2)' }),
                        stroke: new ol.style.Stroke({ color: '#00ff00', width: 3 }),
                        image: new ol.style.Circle({ radius: 7, fill: new ol.style.Fill({ color: '#00ff00' }) })
                    })
                });

                drawInteraction.on('drawend', function(e) {
                    saveFeature(e.feature, type);
                });
                map.addInteraction(drawInteraction);
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
                        reloadAllSources();
                        disableAllInteractions();
                        clearDrawInteraction();
                        Swal.fire('Success', 'Feature saved successfully', 'success');
                        setNoneMode();
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Error saving feature', 'error');
                        setNoneMode();
                    }
                });
            }

            // ─── DELETE MODAL ───
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
                $btn.html('<span class="spinner-border spinner-border-sm me-1"></span>Deleting…').prop('disabled', true);

                $.ajax({
                    url: '/delete-feature',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: { type, gisid },
                    success: function(response) {
                        const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteFeatureModal'));
                        if (deleteModal) deleteModal.hide();
                        $btn.html('<i class="bi bi-trash3 me-1"></i>Delete').prop('disabled', false);

                        polygons = response.data.polygons ?? polygons;
                        points = response.data.points ?? points;
                        lines = response.data.lines ?? lines;

                        reloadAllSources();
                        disableAllInteractions();
                        setNoneMode();

                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: `${type.charAt(0).toUpperCase() + type.slice(1)} (GIS ID: ${gisid}) deleted.`,
                            timer: 2500,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        $btn.html('<i class="bi bi-trash3 me-1"></i>Delete').prop('disabled', false);
                        const msg = xhr.responseJSON?.message || `No ${type} found with GIS ID: ${gisid}`;
                        $('#deleteGisError').text(msg).show();
                    }
                });
            });

            // ─── CLOSE DROPDOWNS ───
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.custom-layer-switcher').length) $('.layer-dropdown').removeClass('show');
                if (!$(e.target).closest('.custom-location-switcher').length) $('#locationDropdown').removeClass('show');
                if (!$(e.target).closest('.custom-search-switcher').length) {
                    $('#searchDropdown').removeClass('show');
                    $('#searchToggleBtn').removeClass('active-search');
                }
                if (!$(e.target).closest('.custom-edit-toggle').length) {
                    $('#editDropdown').removeClass('show');
                    $('#editToggleBtn').removeClass('active-edit');
                }
            });

            // ════════════════════════════════════════════════════════════
            // 3D VIEW TOGGLE — Cesium globe with Satellite/OSM fallback
            // ════════════════════════════════════════════════════════════
            let cesiumViewer = null;
            let cesiumBuildingEntities = [];
            let is3DActive = false;
            let droneImageryLayer = null;

            window.is3DActive = is3DActive;
            window.cesiumViewer = cesiumViewer;

            function ringToLonLatFlatArray(ringCoords) {
                const flat = [];
                ringCoords.forEach(c => {
                    const lonlat = ol.proj.toLonLat(c);
                    flat.push(lonlat[0], lonlat[1]);
                });
                return flat;
            }

            function init3DViewerWithFallback() {
                if (cesiumViewer) return cesiumViewer;

                window.CESIUM_BASE_URL = 'https://cesium.com/downloads/cesiumjs/releases/1.127/Build/Cesium/';

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
                    terrainProvider: new Cesium.EllipsoidTerrainProvider()
                });

                // ─── TRY DRONE IMAGE FIRST, THEN FALLBACK ───
                let droneLoaded = false;

                if (droneImageURL && droneImageURL !== "{{ asset('') }}") {
                    try {
                        // Remove all existing imagery layers
                        cesiumViewer.imageryLayers.removeAll();

                        // Create rectangle for drone image
                        let rect;
                        if (isLatLon) {
                            rect = Cesium.Rectangle.fromDegrees(
                                imageExtentRaw[0],
                                imageExtentRaw[1],
                                imageExtentRaw[2],
                                imageExtentRaw[3]
                            );
                        } else {
                            // Convert EPSG:3857 to WGS84
                            function webMercatorToWgs84(x, y) {
                                var lon = (x / 20037508.34) * 180;
                                var lat = (y / 20037508.34) * 180;
                                lat = 180 / Math.PI * (2 * Math.atan(Math.exp(lat * Math.PI / 180)) - Math.PI / 2);
                                return [lon, lat];
                            }
                            const bl = webMercatorToWgs84(imageExtentRaw[0], imageExtentRaw[1]);
                            const tr = webMercatorToWgs84(imageExtentRaw[2], imageExtentRaw[3]);
                            rect = Cesium.Rectangle.fromDegrees(bl[0], bl[1], tr[0], tr[1]);
                        }

                        // Add drone image
                        const provider = new Cesium.SingleTileImageryProvider({
                            url: droneImageURL,
                            rectangle: rect
                        });

                        droneImageryLayer = cesiumViewer.imageryLayers.addImageryProvider(provider);
                        droneImageryLayer.alpha = 1.0;
                        droneImageryLayer.show = true;
                        droneLoaded = true;

                        console.log('✅ Drone image loaded in 3D view');

                        // Fly to drone extent
                        const west = rect.west;
                        const south = rect.south;
                        const east = rect.east;
                        const north = rect.north;
                        const centerLon = (west + east) / 2;
                        const centerLat = (south + north) / 2;
                        const height = Math.max(200, (east - west) * 111000 * 0.5);

                        cesiumViewer.camera.flyTo({
                            destination: Cesium.Cartesian3.fromDegrees(centerLon, centerLat, height),
                            orientation: {
                                heading: Cesium.Math.toRadians(0),
                                pitch: Cesium.Math.toRadians(-45),
                                roll: 0
                            },
                            duration: 2
                        });

                        showToast('🛩️ Drone image loaded in 3D view', 3000);
                    } catch (error) {
                        console.error('Drone image failed:', error);
                        droneLoaded = false;
                    }
                }

                // ─── FALLBACK: SATELLITE OR OSM ───
                if (!droneLoaded) {
                    try {
                        // Try Satellite imagery first
                        cesiumViewer.imageryLayers.addImageryProvider(
                            new Cesium.ArcGisMapServerImageryProvider({
                                url: 'https://services.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer'
                            })
                        );
                        showToast('🛰️ Using Satellite imagery (Drone not available)', 3000);
                        console.log('✅ Satellite imagery loaded as fallback');
                    } catch (error) {
                        // Final fallback: OpenStreetMap
                        cesiumViewer.imageryLayers.addImageryProvider(
                            new Cesium.OpenStreetMapImageryProvider({
                                url: 'https://tile.openstreetmap.org/'
                            })
                        );
                        showToast('🗺️ Using OpenStreetMap (Satellite failed)', 3000);
                        console.log('✅ OSM loaded as final fallback');
                    }
                }

                cesiumViewer.container.insertAdjacentHTML('beforeend',
                    '<div class="cesium-info-box">🧊 3D view' + (droneLoaded ? ' with Drone Image' : ' with Satellite/OSM') + ' — switch back to 2D to edit</div>'
                );

                window.cesiumViewer = cesiumViewer;
                return cesiumViewer;
            }

            function refreshCesiumBuildings() {
                if (!cesiumViewer) return;

                // Remove old buildings
                cesiumBuildingEntities.forEach(e => cesiumViewer.entities.remove(e));
                cesiumBuildingEntities = [];

                // Get filtered buildings from the map source
                const filteredFeatures = buildingSource.getFeatures();
                filteredFeatures.forEach(feature => {
                    try {
                        const gisid = feature.get('gisid');
                        const coords = feature.getGeometry().getCoordinates()[0];
                        const flat = ringToLonLatFlatArray(coords);
                        if (flat.length < 6) return;

                        const polygonData = polygonDatas.find(d => d.gisid == gisid);
                        const floors = polygonData?.number_floor ? parseInt(polygonData.number_floor) || 1 : 1;
                        const height = Math.max(1, floors) * 3;
                        const isMapped = !!polygonData;
                        const usage = feature.get('usage') || 'OTHER';
                        const color = usageColors[usage] || '#9E9E9E';

                        const entity = cesiumViewer.entities.add({
                            name: 'Building ' + gisid,
                            polygon: {
                                hierarchy: Cesium.Cartesian3.fromDegreesArray(flat),
                                height: 0,
                                extrudedHeight: height,
                                material: Cesium.Color.fromCssColorString(color).withAlpha(0.75),
                                outline: true,
                                outlineColor: Cesium.Color.WHITE,
                                outlineWidth: 1
                            },
                            description: `
                                <b>GIS ID:</b> ${gisid}<br>
                                <b>Area:</b> ${feature.get('sqfeet') || 0} sqft<br>
                                <b>Floors:</b> ${floors}<br>
                                <b>Usage:</b> ${usage}<br>
                                <b>Status:</b> ${isMapped ? 'Mapped' : 'Unmapped'}
                            `
                        });

                        cesiumBuildingEntities.push(entity);
                    } catch (e) {
                        console.error('Cesium build error:', e);
                    }
                });

                if (cesiumBuildingEntities.length) {
                    cesiumViewer.zoomTo(cesiumViewer.entities);
                }
            }

            function toggle3DView() {
                is3DActive = !is3DActive;
                window.is3DActive = is3DActive;
                $('#threeDToggleBtn').toggleClass('active-3d', is3DActive);
                $('#threeDToggleBtn i').toggleClass('bi-box', !is3DActive).toggleClass('bi-badge-3d', is3DActive);

                if (is3DActive) {
                    disableAllInteractions();
                    setNoneMode();

                    $('#map').hide();
                    $('#cesiumContainer').show();
                    init3DViewerWithFallback();
                    setTimeout(() => {
                        refreshCesiumBuildings();
                    }, 500);
                    showToast('🧊 3D View loaded', 2500);
                } else {
                    $('#cesiumContainer').hide();
                    $('#map').show();
                    setTimeout(() => map.updateSize(), 100);
                    showToast('🗺️ Back to 2D editable view', 1500);
                }
            }

            $(document).on('click', '#threeDToggleBtn', function(e) {
                e.stopPropagation();
                toggle3DView();
            });

            // ─── INIT ───
            buildSearchIndex();
            updateLayerUI();

            // Load buildings with colors
            currentFilteredBuildings = allBuildings;
            loadBuildingsWithColors(allBuildings);
            $('#buildingCountDisplay').text(`Showing: ${allBuildings.length} buildings`);

            setNoneMode();
            syncLocationUI();

            if (!droneImageURL || droneImageURL === "{{ asset('') }}") {
                droneLayer.setVisible(false);
            }

            loadInfrastructure({{ $ward->id }});

            console.log('✅ Executive GIS Dashboard ready with 3D fallback');
        });
    </script>
@endpush

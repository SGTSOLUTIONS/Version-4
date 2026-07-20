<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>OpenLayers - Satellite & Google Street View</title>

    <!-- OpenLayers CSS & JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@v9.2.4/ol.css" />
    <script src="https://cdn.jsdelivr.net/npm/ol@v9.2.4/dist/ol.min.js"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #1a1a2e; overflow: hidden; }

        #map {
            width: 100vw;
            height: 100vh;
            position: relative;
        }

        /* Control Panel */
        .controls {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(12px);
            padding: 12px 20px;
            border-radius: 14px;
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6);
            pointer-events: auto;
        }

        .controls button {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .controls button:hover { transform: translateY(-2px); }
        .controls button:active { transform: scale(0.95); }

        .btn-satellite {
            background: #4a9eff;
            color: white;
        }
        .btn-satellite.active {
            background: #ff6b6b;
            box-shadow: 0 0 25px rgba(255, 107, 107, 0.3);
        }

        .btn-street {
            background: #ff6b6b;
            color: white;
        }
        .btn-street.active {
            background: #4a9eff;
            box-shadow: 0 0 25px rgba(74, 158, 255, 0.3);
        }

        .controls .info {
            color: rgba(255, 255, 255, 0.8);
            font-size: 12px;
            padding: 6px 14px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 6px;
            border-left: 2px solid #4a9eff;
            font-family: 'Courier New', monospace;
            min-width: 180px;
            text-align: center;
        }

        /* Street View Container */
        #streetViewContainer {
            position: absolute;
            bottom: 30px;
            right: 30px;
            width: 450px;
            height: 350px;
            border-radius: 16px;
            overflow: hidden;
            z-index: 999;
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.8);
            border: 2px solid rgba(255, 255, 255, 0.15);
            display: none;
            background: #000;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        #streetViewContainer.visible {
            display: block;
            animation: slideUp 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px) scale(0.9); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        #streetViewContainer iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .street-label {
            position: absolute;
            bottom: 12px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            color: white;
            font-size: 11px;
            background: rgba(0, 0, 0, 0.75);
            padding: 5px 16px;
            border-radius: 20px;
            letter-spacing: 0.3px;
            pointer-events: none;
            white-space: nowrap;
            backdrop-filter: blur(4px);
        }

        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 14px;
            background: rgba(0, 0, 0, 0.8);
            padding: 12px 24px;
            border-radius: 10px;
            z-index: 1001;
            display: none;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .loading::before {
            content: '⏳ ';
        }

        .close-street {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1001;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            font-size: 16px;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            backdrop-filter: blur(4px);
        }

        .close-street:hover {
            background: rgba(255, 70, 70, 0.8);
            transform: rotate(90deg);
        }

        #streetViewContainer.visible .close-street {
            display: flex;
        }

        /* Google Street View specific styles */
        .street-controls {
            position: absolute;
            bottom: 50px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            display: none;
            gap: 10px;
            background: rgba(0, 0, 0, 0.7);
            padding: 8px 16px;
            border-radius: 20px;
            backdrop-filter: blur(4px);
        }

        #streetViewContainer.visible .street-controls {
            display: flex;
        }

        .street-controls button {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 4px 12px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }

        .street-controls button:hover {
            background: rgba(255, 255, 255, 0.4);
        }

        @media (max-width: 768px) {
            .controls {
                top: 10px;
                padding: 10px 12px;
                gap: 6px;
                width: 95%;
            }
            .controls button {
                padding: 7px 14px;
                font-size: 11px;
            }
            .controls .info {
                font-size: 10px;
                min-width: 120px;
                padding: 4px 10px;
            }
            #streetViewContainer {
                width: 92%;
                height: 200px;
                bottom: 15px;
                right: 4%;
                left: 4%;
            }
        }
    </style>
</head>
<body>

<div id="map">
    <div class="controls">
        <button class="btn-satellite active" id="btnSatellite">🛰️ Satellite</button>
        <button class="btn-street" id="btnStreet">🚶 Street View</button>
        <span class="info" id="coordInfo">📍 Click map to explore</span>
    </div>

    <div id="streetViewContainer">
        <button class="close-street" id="closeStreet">✕</button>
        <iframe id="streetIframe" src="about:blank"></iframe>
        <div class="street-label">📍 Google Street View</div>
        <div class="street-controls">
            <button id="streetZoomIn">➕</button>
            <button id="streetZoomOut">➖</button>
            <button id="streetRotate">🔄</button>
        </div>
    </div>

    <div class="loading" id="loading">Loading Google Street View...</div>
</div>

<script>
    // ============================================================
    // OPENLAYERS SETUP
    // ============================================================

    var Map = ol.Map;
    var View = ol.View;
    var TileLayer = ol.layer.Tile;
    var VectorLayer = ol.layer.Vector;
    var OSM = ol.source.OSM;
    var XYZ = ol.source.XYZ;
    var Vector = ol.source.Vector;
    var Feature = ol.Feature;
    var Point = ol.geom.Point;
    var Style = ol.style.Style;
    var Icon = ol.style.Icon;
    var fromLonLat = ol.proj.fromLonLat;
    var toLonLat = ol.proj.toLonLat;

    // ============================================================
    // 1. CREATE MAP
    // ============================================================
    var map = new Map({
        target: 'map',
        layers: [],
        view: new View({
            center: fromLonLat([-74.0060, 40.7128]),
            zoom: 15,
            maxZoom: 20,
            minZoom: 3
        })
    });

    // ============================================================
    // 2. BASE LAYER (OSM)
    // ============================================================
    var osmLayer = new TileLayer({
        source: new OSM(),
        opacity: 1.0,
        visible: true
    });
    map.addLayer(osmLayer);

    // ============================================================
    // 3. SATELLITE LAYER - Esri Satellite (no CORS issues)
    // ============================================================
    var satelliteLayer = new TileLayer({
        source: new XYZ({
            url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            maxZoom: 19,
            crossOrigin: 'anonymous',
            attributions: '© Esri, Maxar, Earthstar Geographics'
        }),
        visible: true,
        opacity: 1.0
    });
    map.addLayer(satelliteLayer);

    // ============================================================
    // 4. VECTOR LAYER FOR MARKER
    // ============================================================
    var markerSource = new Vector();
    var markerLayer = new VectorLayer({
        source: markerSource,
        style: new Style({
            image: new Icon({
                anchor: [0.5, 1],
                src: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(
                    '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="42" viewBox="0 0 32 42">' +
                        '<path d="M16 0 C7.16 0 0 7.16 0 16 C0 28 16 42 16 42 C16 42 32 28 32 16 C32 7.16 24.84 0 16 0 Z" ' +
                              'fill="#ff4444" stroke="#ffffff" stroke-width="2"/>' +
                        '<circle cx="16" cy="16" r="6" fill="#ffffff" stroke="#ff4444" stroke-width="2"/>' +
                    '</svg>'
                ),
                scale: 0.8,
                crossOrigin: 'anonymous'
            })
        })
    });
    map.addLayer(markerLayer);

    // ============================================================
    // 5. STATE
    // ============================================================
    var currentLon = -74.0060;
    var currentLat = 40.7128;
    var showSatellite = true;
    var showStreet = false;

    // ============================================================
    // 6. ADD INITIAL MARKER
    // ============================================================
    function addMarker(lon, lat) {
        markerSource.clear();
        var coord = fromLonLat([lon, lat]);
        var markerFeature = new Feature({
            geometry: new Point(coord)
        });
        markerSource.addFeature(markerFeature);
        currentLon = lon;
        currentLat = lat;

        document.getElementById('coordInfo').textContent =
            '📍 ' + lat.toFixed(5) + ', ' + lon.toFixed(5);
    }

    addMarker(-74.0060, 40.7128);

    // ============================================================
    // 7. MAP CLICK - Update marker and street view
    // ============================================================
    map.on('click', function(evt) {
        var coord = evt.coordinate;
        var lonLat = toLonLat(coord);
        var lon = lonLat[0];
        var lat = lonLat[1];

        addMarker(lon, lat);

        if (showStreet) {
            updateStreetView(lat, lon);
        }
    });

    // ============================================================
    // 8. GOOGLE STREET VIEW
    // ============================================================
    var streetContainer = document.getElementById('streetViewContainer');
    var streetIframe = document.getElementById('streetIframe');
    var loading = document.getElementById('loading');
    var closeStreetBtn = document.getElementById('closeStreet');

    // Google Street View requires an API key
    // Get your free API key from: https://developers.google.com/maps/documentation/embed/get-api-key
    var GOOGLE_API_KEY = 'YOUR_GOOGLE_API_KEY'; // Replace with your API key

    function updateStreetView(lat, lon) {
        loading.style.display = 'block';
        streetContainer.classList.remove('visible');

        // Google Street View Embed URL
        var streetViewUrl = 'https://www.google.com/maps/embed/v1/streetview' +
            '?key=' + GOOGLE_API_KEY +
            '&location=' + lat + ',' + lon +
            '&heading=0' +
            '&pitch=0' +
            '&fov=90' +
            '&zoom=1' +
            '&language=en';

        // If no API key, use the standard Google Maps embed (limited)
        if (GOOGLE_API_KEY === 'YOUR_GOOGLE_API_KEY') {
            streetViewUrl = 'https://www.google.com/maps?q=' + lat + ',' + lon + '&layer=c&cbll=' + lat + ',' + lon + '&cbp=11,0,0,0,0';
        }

        streetIframe.src = streetViewUrl;

        streetIframe.onload = function() {
            loading.style.display = 'none';
            streetContainer.classList.add('visible');
        };

        setTimeout(function() {
            if (loading.style.display !== 'none') {
                loading.style.display = 'none';
                streetContainer.classList.add('visible');
            }
        }, 8000);
    }

    // Close street view
    closeStreetBtn.addEventListener('click', function() {
        streetContainer.classList.remove('visible');
        streetIframe.src = 'about:blank';
        showStreet = false;
        document.getElementById('btnStreet').classList.remove('active');
        document.getElementById('btnStreet').textContent = '🚶 Street View';
        document.getElementById('btnSatellite').classList.add('active');
        document.getElementById('btnSatellite').textContent = '🛰️ Satellite (ON)';
        satelliteLayer.setVisible(true);
        document.getElementById('coordInfo').textContent =
            '🛰️ ' + currentLat.toFixed(5) + ', ' + currentLon.toFixed(5);
        showSatellite = true;
    });

    // Street view controls
    document.getElementById('streetZoomIn').addEventListener('click', function() {
        var currentSrc = streetIframe.src;
        if (currentSrc.includes('fov=')) {
            var newFov = parseInt(currentSrc.match(/fov=(\d+)/)[1]) - 10;
            if (newFov < 30) newFov = 30;
            streetIframe.src = currentSrc.replace(/fov=\d+/, 'fov=' + newFov);
        }
    });

    document.getElementById('streetZoomOut').addEventListener('click', function() {
        var currentSrc = streetIframe.src;
        if (currentSrc.includes('fov=')) {
            var newFov = parseInt(currentSrc.match(/fov=(\d+)/)[1]) + 10;
            if (newFov > 120) newFov = 120;
            streetIframe.src = currentSrc.replace(/fov=\d+/, 'fov=' + newFov);
        }
    });

    document.getElementById('streetRotate').addEventListener('click', function() {
        var currentSrc = streetIframe.src;
        if (currentSrc.includes('heading=')) {
            var currentHeading = parseInt(currentSrc.match(/heading=(\d+)/)[1]);
            var newHeading = (currentHeading + 45) % 360;
            streetIframe.src = currentSrc.replace(/heading=\d+/, 'heading=' + newHeading);
        }
    });

    // ============================================================
    // 9. BUTTON CONTROLS
    // ============================================================
    var btnSat = document.getElementById('btnSatellite');
    var btnStreet = document.getElementById('btnStreet');

    // Satellite button
    btnSat.addEventListener('click', function() {
        showSatellite = true;
        showStreet = false;

        btnSat.classList.add('active');
        btnStreet.classList.remove('active');
        btnSat.textContent = '🛰️ Satellite (ON)';
        btnStreet.textContent = '🚶 Street View';

        satelliteLayer.setVisible(true);
        streetContainer.classList.remove('visible');
        streetIframe.src = 'about:blank';

        document.getElementById('coordInfo').textContent =
            '🛰️ ' + currentLat.toFixed(5) + ', ' + currentLon.toFixed(5);
    });

    // Street View button
    btnStreet.addEventListener('click', function() {
        showSatellite = false;
        showStreet = true;

        btnStreet.classList.add('active');
        btnSat.classList.remove('active');
        btnStreet.textContent = '🚶 Street (ON)';
        btnSat.textContent = '🛰️ Satellite';

        satelliteLayer.setVisible(false);

        updateStreetView(currentLat, currentLon);

        document.getElementById('coordInfo').textContent =
            '🚶 ' + currentLat.toFixed(5) + ', ' + currentLon.toFixed(5);
    });

    // ============================================================
    // 10. KEYBOARD SHORTCUTS
    // ============================================================
    document.addEventListener('keydown', function(e) {
        if (e.key === 's' || e.key === 'S') {
            btnSat.click();
            e.preventDefault();
        } else if (e.key === 'v' || e.key === 'V') {
            btnStreet.click();
            e.preventDefault();
        } else if (e.key === 'Escape') {
            closeStreetBtn.click();
        }
    });

    // ============================================================
    // 11. HANDLE WINDOW RESIZE
    // ============================================================
    window.addEventListener('resize', function() {
        map.updateSize();
    });

    // ============================================================
    // 12. INITIAL LOAD
    // ============================================================
    setTimeout(function() {
        // Preload street view in background
        var preloadUrl = 'https://www.google.com/maps?q=40.7128,-74.0060&layer=c&cbll=40.7128,-74.0060&cbp=11,0,0,0,0';
        var preloadIframe = document.createElement('iframe');
        preloadIframe.src = preloadUrl;
        preloadIframe.style.display = 'none';
        document.body.appendChild(preloadIframe);
        setTimeout(function() {
            document.body.removeChild(preloadIframe);
        }, 5000);
    }, 2000);

    console.log('🚀 OpenLayers Satellite & Google Street View Viewer loaded!');
    console.log('💡 Click map to change location');
    console.log('⌨️  S = Satellite, V = Street View, ESC = Close street view');
    console.log('📌 Note: For best results, get a free Google Street View API key');
    console.log('🔑 https://developers.google.com/maps/documentation/embed/get-api-key');
</script>

</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Simple Aerial & Street View Viewer</title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #1a1a2e; }

        #map {
            width: 100%;
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
            backdrop-filter: blur(10px);
            padding: 15px 25px;
            border-radius: 12px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
            pointer-events: auto;
        }

        .controls button {
            padding: 10px 22px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-satellite {
            background: #4a9eff;
            color: white;
        }
        .btn-satellite:hover { background: #3a7fd4; transform: scale(1.05); }
        .btn-satellite.active { background: #ff6b6b; box-shadow: 0 0 20px rgba(255, 107, 107, 0.4); }

        .btn-street {
            background: #ff6b6b;
            color: white;
        }
        .btn-street:hover { background: #e05555; transform: scale(1.05); }
        .btn-street.active { background: #4a9eff; box-shadow: 0 0 20px rgba(74, 158, 255, 0.4); }

        .controls .info {
            color: rgba(255, 255, 255, 0.7);
            font-size: 12px;
            padding: 5px 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            border-left: 2px solid #4a9eff;
        }

        /* Street View Container (hidden by default) */
        #streetViewContainer {
            position: absolute;
            bottom: 30px;
            right: 30px;
            width: 400px;
            height: 300px;
            border-radius: 16px;
            overflow: hidden;
            z-index: 999;
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.7);
            border: 2px solid rgba(255, 255, 255, 0.2);
            display: none;
            background: #000;
            transition: all 0.4s ease;
        }

        #streetViewContainer.visible {
            display: block;
            animation: slideUp 0.4s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        #streetViewContainer iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .street-label {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            color: white;
            font-size: 11px;
            background: rgba(0, 0, 0, 0.7);
            padding: 4px 14px;
            border-radius: 20px;
            letter-spacing: 0.5px;
            pointer-events: none;
            white-space: nowrap;
        }

        /* Loading indicator */
        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 14px;
            background: rgba(0, 0, 0, 0.7);
            padding: 10px 20px;
            border-radius: 8px;
            z-index: 1001;
            display: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .controls {
                top: 10px;
                padding: 10px 15px;
                gap: 8px;
                width: 95%;
            }
            .controls button {
                padding: 8px 14px;
                font-size: 11px;
            }
            #streetViewContainer {
                width: 90%;
                height: 200px;
                bottom: 15px;
                right: 5%;
                left: 5%;
            }
        }
    </style>
</head>
<body>

<div id="map">
    <!-- Control Panel -->
    <div class="controls" id="controls">
        <button class="btn-satellite active" id="btnSatellite">🛰️ Satellite</button>
        <button class="btn-street" id="btnStreet">🚶 Street View</button>
        <span class="info" id="coordInfo">Click map to see location</span>
    </div>

    <!-- Street View Container -->
    <div id="streetViewContainer">
        <iframe id="streetIframe" src="about:blank"></iframe>
        <div class="street-label">📍 Mapillary Street View</div>
    </div>

    <!-- Loading -->
    <div class="loading" id="loading">Loading street view...</div>
</div>

<script>
    // ============================================================
    // 1. INITIALIZE MAP
    // ============================================================
    const map = L.map('map', {
        center: [40.7128, -74.0060], // New York
        zoom: 15,
        zoomControl: true
    });

    // OpenStreetMap base layer (for context)
    const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    });

    // ============================================================
    // 2. OPENAERIALMAP SATELLITE LAYER (using OAM public tiles)
    // ============================================================
    // Using OAM's public tile endpoint
    const oamLayer = L.tileLayer(
        'https://tiles.openaerialmap.org/tiles/1.0.0/global/{z}/{x}/{y}.png',
        {
            maxZoom: 19,
            attribution: '© OpenAerialMap',
            subdomains: ['a', 'b', 'c']
        }
    );

    // Also try alternative OAM tile source if needed
    const oamLayerAlt = L.tileLayer(
        'https://tiles.openaerialmap.org/tiles/1.0.0/global/{z}/{x}/{y}.png',
        {
            maxZoom: 19,
            attribution: '© OpenAerialMap'
        }
    );

    // Add layers to map
    osmLayer.addTo(map);
    oamLayer.addTo(map);

    // Track which layers are visible
    let showSatellite = true;
    let showStreet = false;

    // ============================================================
    // 3. MARKER & POPUP
    // ============================================================
    let currentMarker = null;
    let currentLat = 40.7128;
    let currentLng = -74.0060;

    // Click handler: place marker and update street view
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        currentLat = lat;
        currentLng = lng;

        // Update marker
        if (currentMarker) {
            map.removeLayer(currentMarker);
        }
        currentMarker = L.marker([lat, lng]).addTo(map);
        currentMarker.bindPopup(`📍 <b>${lat.toFixed(5)}, ${lng.toFixed(5)}</b>`).openPopup();

        // Update coordinates display
        document.getElementById('coordInfo').textContent =
            `📍 ${lat.toFixed(5)}, ${lng.toFixed(5)}`;

        // If street view is active, update it
        if (showStreet) {
            updateStreetView(lat, lng);
        }
    });

    // ============================================================
    // 4. STREET VIEW (Mapillary)
    // ============================================================
    const streetContainer = document.getElementById('streetViewContainer');
    const streetIframe = document.getElementById('streetIframe');
    const loading = document.getElementById('loading');

    function updateStreetView(lat, lng) {
        loading.style.display = 'block';
        streetContainer.classList.remove('visible');

        // Use Mapillary's embed API
        // Mapillary provides street-level imagery from crowdsourced photos
        const mapillaryUrl = `https://www.mapillary.com/embed?map_style=Mapillary%20streets&lat=${lat}&lng=${lng}&z=18&style=classic&theme=light`;

        // Alternative: Mapillary image API for static street view
        // const mapillaryImg = `https://api.mapillary.com/v3/images?client_id=YOUR_CLIENT_ID&lat=${lat}&lon=${lng}&radius=50`;

        // Set iframe source
        streetIframe.src = mapillaryUrl;

        // Show after load
        streetIframe.onload = function() {
            loading.style.display = 'none';
            streetContainer.classList.add('visible');
        };

        // Timeout fallback
        setTimeout(() => {
            if (loading.style.display !== 'none') {
                loading.style.display = 'none';
                streetContainer.classList.add('visible');
            }
        }, 8000);
    }

    // ============================================================
    // 5. BUTTON CONTROLS
    // ============================================================
    const btnSat = document.getElementById('btnSatellite');
    const btnStreet = document.getElementById('btnStreet');

    // Satellite button
    btnSat.addEventListener('click', function() {
        showSatellite = true;
        showStreet = false;

        // Update UI
        btnSat.classList.add('active');
        btnStreet.classList.remove('active');
        btnSat.textContent = '🛰️ Satellite (ON)';
        btnStreet.textContent = '🚶 Street View';

        // Show satellite layer, hide street view
        oamLayer.addTo(map);
        streetContainer.classList.remove('visible');
        streetIframe.src = 'about:blank';

        // Update info
        document.getElementById('coordInfo').textContent =
            `🛰️ Satellite view • ${currentLat.toFixed(5)}, ${currentLng.toFixed(5)}`;
    });

    // Street View button
    btnStreet.addEventListener('click', function() {
        showSatellite = false;
        showStreet = true;

        // Update UI
        btnStreet.classList.add('active');
        btnSat.classList.remove('active');
        btnStreet.textContent = '🚶 Street (ON)';
        btnSat.textContent = '🛰️ Satellite';

        // Hide satellite layer to see OSM background better
        map.removeLayer(oamLayer);

        // Update street view at current location
        updateStreetView(currentLat, currentLng);

        // Update info
        document.getElementById('coordInfo').textContent =
            `🚶 Street view • ${currentLat.toFixed(5)}, ${currentLng.toFixed(5)}`;
    });

    // ============================================================
    // 6. KEYBOARD SHORTCUTS
    // ============================================================
    document.addEventListener('keydown', function(e) {
        if (e.key === 's' || e.key === 'S') {
            btnSat.click();
        } else if (e.key === 'v' || e.key === 'V') {
            btnStreet.click();
        }
    });

    // ============================================================
    // 7. INITIAL LOAD - Place initial marker & street view
    // ============================================================
    setTimeout(() => {
        // Place initial marker
        if (currentMarker) map.removeLayer(currentMarker);
        currentMarker = L.marker([currentLat, currentLng]).addTo(map);
        currentMarker.bindPopup('📍 New York City').openPopup();

        // Load street view initially (but hidden)
        updateStreetView(currentLat, currentLng);
        // Hide it initially since satellite is active
        setTimeout(() => {
            streetContainer.classList.remove('visible');
            streetIframe.src = 'about:blank';
        }, 500);
    }, 1000);

    // ============================================================
    // 8. HANDLE WINDOW RESIZE
    // ============================================================
    window.addEventListener('resize', function() {
        map.invalidateSize();
    });

    console.log('🚀 Simple Aerial & Street View Viewer loaded!');
    console.log('💡 Click map to change location');
    console.log('⌨️  Press S for Satellite, V for Street View');
</script>

</body>
</html>

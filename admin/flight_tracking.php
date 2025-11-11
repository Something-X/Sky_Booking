<?php
require_once '../config.php';

if (!isAdmin()) {
    redirect('login.php');
}

// Get pending count for badge
$pending_result = $conn->query("SELECT COUNT(*) as total FROM pemesanan WHERE status = 'pending'");
$pending_count = $pending_result ? $pending_result->fetch_assoc()['total'] : 0;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Flight Tracking - SkyBooking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Leaflet.js CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        secondary: '#1e40af',
                        accent: '#f59e0b',
                        sidebar: '#1e293b'
                    }
                }
            }
        }
    </script>

    <style>
        #map {
            height: 600px;
            width: 100%;
            border-radius: 16px;
        }

        .plane-icon {
            width: 32px;
            height: 32px;
            transition: none;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-scheduled {
            background: #d1fae5;
            color: #065f46;
        }

        .status-departed {
            background: #fef3c7;
            color: #92400e;
        }

        .status-arrived {
            background: #dbeafe;
            color: #1e40af;
        }

        .flight-info-card {
            background: white;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            min-width: 280px;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        .animate-pulse-slow {
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes blinkDot {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.3;
                transform: scale(1.2);
            }
        }

        .live-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #ef4444;
            border-radius: 50%;
            animation: blinkDot 1.5s ease-in-out infinite;
            box-shadow: 0 0 8px rgba(239, 68, 68, 0.6);
        }

        .stat-card {
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }

        .refresh-btn {
            transition: all 0.3s ease;
        }

        .refresh-btn:hover {
            transform: scale(1.05);
        }

        .flight-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 8px;
            background: #f9fafb;
            font-size: 12px;
            font-weight: 500;
        }

        .legend-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
    </style>
</head>

<body class="bg-gray-50 flex">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 overflow-x-hidden">
        <!-- Top Bar -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-20">
            <div class="px-8 py-5 flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <h1 class="text-2xl font-bold text-gray-900">Live Flight Tracking</h1>
                        <div class="flex items-center gap-2 px-3 py-1 bg-red-50 rounded-full">
                            <span class="live-indicator"></span>
                            <span class="text-xs font-semibold text-red-600 uppercase">Live</span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500">Real-time monitoring penerbangan aktif</p>
                </div>
                <div class="flex items-center gap-4">
                    <div id="lastUpdate" class="text-xs text-gray-500 bg-gray-100 px-3 py-2 rounded-lg"></div>
                    <button onclick="refreshData()" class="refresh-btn bg-primary hover:bg-secondary text-white font-semibold px-5 py-2.5 rounded-lg flex items-center gap-2 shadow-sm">
                        <i class="fas fa-sync-alt text-sm" id="refreshIcon"></i>
                        Refresh
                    </button>
                </div>
            </div>
        </header>

        <div class="p-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-8">
                <div class="stat-card bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Total Flights</p>
                            <h3 id="totalFlights" class="text-3xl font-bold text-gray-900">0</h3>
                        </div>
                        <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                            <i class="fas fa-plane text-xl text-blue-600"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Scheduled</p>
                            <h3 id="scheduledFlights" class="text-3xl font-bold text-green-600">0</h3>
                        </div>
                        <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                            <i class="fas fa-clock text-xl text-green-600"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Departed</p>
                            <h3 id="departedFlights" class="text-3xl font-bold text-orange-600">0</h3>
                        </div>
                        <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center">
                            <i class="fas fa-plane-departure text-xl text-orange-600 animate-pulse-slow"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Arrived</p>
                            <h3 id="arrivedFlights" class="text-3xl font-bold text-blue-600">0</h3>
                        </div>
                        <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                            <i class="fas fa-plane-arrival text-xl text-blue-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Map Container -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
                <div class="flex justify-between items-center mb-5">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 mb-1">Flight Map</h2>
                        <p class="text-sm text-gray-500">Peta real-time pergerakan pesawat</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="legend-item">
                            <span class="legend-dot bg-green-500"></span>
                            <span>Scheduled</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot bg-orange-500"></span>
                            <span>Departed</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot bg-blue-500"></span>
                            <span>Arrived</span>
                        </div>
                    </div>
                </div>
                <div id="map" class="rounded-2xl overflow-hidden shadow-inner"></div>
            </div>

            <!-- Flight List -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="mb-5">
                    <h2 class="text-lg font-bold text-gray-900 mb-1">Active Flights</h2>
                    <p class="text-sm text-gray-500">Daftar penerbangan yang sedang aktif</p>
                </div>
                <div id="flightList" class="overflow-x-auto"></div>
            </div>
        </div>
    </main>

    <!-- Leaflet.js Script -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        let map, planeMarkers = {},
            airportMarkers = {},
            flightPaths = {};
        let updateInterval, animationIntervals = {};
        let flightsData = [];

        function initMap() {
            map = L.map('map').setView([-2.5, 118], 5);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 18
            }).addTo(map);
        }

        function getPlaneIcon(status, rotation = 0) {
            let color = '#10b981';
            if (status === 'Departed') color = '#f59e0b';
            if (status === 'Arrived') color = '#3b82f6';

            const svg = `
                <svg width="32" height="32" viewBox="0 0 24 24" style="transform: rotate(${rotation}deg);">
                    <path fill="${color}" d="M21,16V14L13,9V3.5A1.5,1.5 0 0,0 11.5,2A1.5,1.5 0 0,0 10,3.5V9L2,14V16L10,13.5V19L8,20.5V22L11.5,21L15,22V20.5L13,19V13.5L21,16Z" />
                </svg>
            `;

            return L.divIcon({
                html: svg,
                className: 'plane-icon',
                iconSize: [32, 32],
                iconAnchor: [16, 16]
            });
        }

        function getAirportIcon() {
            return L.divIcon({
                html: '<i class="fas fa-circle text-red-600 text-xl"></i>',
                className: '',
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });
        }

        function calculateRotation(lat1, lon1, lat2, lon2) {
            const dLon = (lon2 - lon1);
            const y = Math.sin(dLon) * Math.cos(lat2);
            const x = Math.cos(lat1) * Math.sin(lat2) - Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLon);
            let bearing = Math.atan2(y, x);
            bearing = bearing * (180 / Math.PI);
            bearing = (bearing + 360) % 360;
            return bearing;
        }

        function lerp(start, end, t) {
            return start + (end - start) * t;
        }

        function calculateRealTimeProgress(departureTime, arrivalTime) {
            const now = new Date();
            const depTime = new Date(departureTime);
            const arrTime = new Date(arrivalTime);

            const totalDuration = arrTime - depTime;
            const elapsed = now - depTime;

            let progress = (elapsed / totalDuration) * 100;
            progress = Math.max(0, Math.min(100, progress));

            return progress;
        }

        function animatePlane(flight) {
            if (animationIntervals[flight.id]) {
                clearInterval(animationIntervals[flight.id]);
            }

            if (flight.status !== 'Departed') {
                return;
            }

            const originLat = flight.origin.lat;
            const originLon = flight.origin.lon;
            const destLat = flight.destination.lat;
            const destLon = flight.destination.lon;
            const rotation = calculateRotation(originLat, originLon, destLat, destLon);

            function updatePosition() {
                const realProgress = calculateRealTimeProgress(flight.departure_time, flight.arrival_time);
                const progressDecimal = realProgress / 100;

                if (realProgress >= 100) {
                    clearInterval(animationIntervals[flight.id]);
                    return;
                }

                const newLat = lerp(originLat, destLat, progressDecimal);
                const newLon = lerp(originLon, destLon, progressDecimal);

                if (planeMarkers[flight.id]) {
                    planeMarkers[flight.id].setLatLng([newLat, newLon]);
                    planeMarkers[flight.id].setIcon(getPlaneIcon(flight.status, rotation));

                    const now = new Date();
                    const arrTime = new Date(flight.arrival_time);
                    const remainingMinutes = Math.round((arrTime - now) / 1000 / 60);
                    const etaText = remainingMinutes > 0 ? `${remainingMinutes} menit lagi` : 'Segera tiba';

                    planeMarkers[flight.id].setPopupContent(`
                        <div class="flight-info-card">
                            <div class="flex justify-between items-center mb-3">
                                <strong class="text-lg">${flight.flight_code}</strong>
                                <span class="status-badge status-${flight.status.toLowerCase()}">${flight.status}</span>
                            </div>
                            <p class="text-sm text-gray-700 mb-1 font-medium">${flight.airline}</p>
                            <p class="text-xs text-gray-500 mb-3">${flight.aircraft_type || 'N/A'}</p>
                            <hr class="my-3 border-gray-200">
                            <div class="space-y-2 mb-3">
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-plane-departure text-primary w-4"></i>
                                        <span class="font-medium">${flight.origin.city}</span>
                                    </div>
                                    <span class="text-xs text-gray-500">${new Date(flight.departure_time).toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'})}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-plane-arrival text-primary w-4"></i>
                                        <span class="font-medium">${flight.destination.city}</span>
                                    </div>
                                    <span class="text-xs text-gray-500">${new Date(flight.arrival_time).toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'})}</span>
                                </div>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <div class="flex justify-between text-xs mb-2">
                                    <span class="text-gray-600">Progress</span>
                                    <strong class="text-gray-900">${realProgress.toFixed(1)}%</strong>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                                    <div class="bg-primary rounded-full h-2 transition-all" style="width: ${realProgress}%"></div>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-600">ETA</span>
                                    <strong class="text-green-600">${etaText}</strong>
                                </div>
                            </div>
                        </div>
                    `);
                }
            }

            updatePosition();
            animationIntervals[flight.id] = setInterval(updatePosition, 2000);
        }

        async function updateFlightData() {
            try {
                const response = await fetch('../api/get_flight_positions.php');
                const data = await response.json();

                if (data.success) {
                    flightsData = data.flights;
                    updateStats(data);
                    updateMap(data);
                    updateFlightList(data);
                    
                    const now = new Date();
                    const timeStr = now.toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit', second: '2-digit'});
                    document.getElementById('lastUpdate').textContent = `Update: ${timeStr}`;
                }
            } catch (error) {
                console.error('Error fetching flight data:', error);
            }
        }

        function updateStats(data) {
            const scheduled = data.flights.filter(f => f.status === 'Scheduled').length;
            const departed = data.flights.filter(f => f.status === 'Departed').length;
            const arrived = data.flights.filter(f => f.status === 'Arrived').length;

            document.getElementById('totalFlights').textContent = data.flights.length;
            document.getElementById('scheduledFlights').textContent = scheduled;
            document.getElementById('departedFlights').textContent = departed;
            document.getElementById('arrivedFlights').textContent = arrived;
        }

        function updateMap(data) {
            const existingFlightIds = new Set(Object.keys(planeMarkers).map(id => parseInt(id)));
            const newFlightIds = new Set(data.flights.map(f => f.id));

            existingFlightIds.forEach(id => {
                if (!newFlightIds.has(id)) {
                    if (planeMarkers[id]) {
                        map.removeLayer(planeMarkers[id]);
                        delete planeMarkers[id];
                    }
                    if (flightPaths[id]) {
                        map.removeLayer(flightPaths[id]);
                        delete flightPaths[id];
                    }
                    if (animationIntervals[id]) {
                        clearInterval(animationIntervals[id]);
                        delete animationIntervals[id];
                    }
                }
            });

            data.airports.forEach(airport => {
                if (!airportMarkers[airport.code]) {
                    const marker = L.marker([airport.lat, airport.lon], {
                        icon: getAirportIcon(),
                        title: `${airport.name} (${airport.code})`
                    }).addTo(map);

                    marker.bindPopup(`
                        <div class="text-center p-2">
                            <strong class="text-lg font-bold">${airport.code}</strong>
                            <p class="text-sm font-medium">${airport.name}</p>
                            <p class="text-xs text-gray-500">${airport.city}</p>
                        </div>
                    `);

                    airportMarkers[airport.code] = marker;
                }
            });

            data.flights.forEach(flight => {
                if (!flightPaths[flight.id]) {
                    const pathCoords = [
                        [flight.origin.lat, flight.origin.lon],
                        [flight.destination.lat, flight.destination.lon]
                    ];

                    let pathColor = '#10b981';
                    if (flight.status === 'Departed') pathColor = '#f59e0b';
                    if (flight.status === 'Arrived') pathColor = '#3b82f6';

                    const path = L.polyline(pathCoords, {
                        color: pathColor,
                        weight: 2,
                        opacity: 0.5,
                        dashArray: flight.status === 'Scheduled' ? '5, 10' : null
                    }).addTo(map);

                    flightPaths[flight.id] = path;
                }

                const rotation = calculateRotation(
                    flight.origin.lat, flight.origin.lon,
                    flight.destination.lat, flight.destination.lon
                );

                if (!planeMarkers[flight.id]) {
                    const planeMarker = L.marker([flight.current_lat, flight.current_lon], {
                        icon: getPlaneIcon(flight.status, rotation),
                        title: flight.flight_code
                    }).addTo(map);

                    let etaText = '';
                    if (flight.status === 'Departed') {
                        const now = new Date();
                        const arrTime = new Date(flight.arrival_time);
                        const remainingMinutes = Math.round((arrTime - now) / 1000 / 60);
                        etaText = remainingMinutes > 0 ? `${remainingMinutes} menit lagi` : 'Segera tiba';
                    }

                    planeMarker.bindPopup(`
                        <div class="flight-info-card">
                            <div class="flex justify-between items-center mb-3">
                                <strong class="text-lg">${flight.flight_code}</strong>
                                <span class="status-badge status-${flight.status.toLowerCase()}">${flight.status}</span>
                            </div>
                            <p class="text-sm text-gray-700 mb-1 font-medium">${flight.airline}</p>
                            <p class="text-xs text-gray-500 mb-3">${flight.aircraft_type || 'N/A'}</p>
                            <hr class="my-3 border-gray-200">
                            <div class="space-y-2 mb-3">
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-plane-departure text-primary w-4"></i>
                                        <span class="font-medium">${flight.origin.city}</span>
                                    </div>
                                    <span class="text-xs text-gray-500">${new Date(flight.departure_time).toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'})}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-plane-arrival text-primary w-4"></i>
                                        <span class="font-medium">${flight.destination.city}</span>
                                    </div>
                                    <span class="text-xs text-gray-500">${new Date(flight.arrival_time).toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'})}</span>
                                </div>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <div class="flex justify-between text-xs mb-2">
                                    <span class="text-gray-600">Progress</span>
                                    <strong class="text-gray-900">${flight.progress.toFixed(1)}%</strong>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2 ${flight.status === 'Departed' ? 'mb-2' : ''}">
                                    <div class="bg-primary rounded-full h-2 transition-all" style="width: ${flight.progress}%"></div>
                                </div>
                                ${flight.status === 'Departed' ? `
                                    <div class="flex justify-between text-xs">
                                        <span class="text-gray-600">ETA</span>
                                        <strong class="text-green-600">${etaText}</strong>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `);

                    planeMarkers[flight.id] = planeMarker;

                    if (flight.status === 'Departed') {
                        animatePlane(flight);
                    }
                }
            });
        }

        function updateFlightList(data) {
            const container = document.getElementById('flightList');

            if (data.flights.length === 0) {
                container.innerHTML = '<div class="text-center py-12"><p class="text-gray-500">Tidak ada penerbangan aktif saat ini</p></div>';
                return;
            }

            let html = `
                <table class="w-full flight-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Flight</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Airline</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Route</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Departure</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Arrival</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Progress</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
            `;

            data.flights.forEach(flight => {
                const depTime = new Date(flight.departure_time);
                const arrTime = new Date(flight.arrival_time);

                html += `
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-4">
                            <span class="font-semibold text-gray-900">${flight.flight_code}</span>
                        </td>
                        <td class="px-4 py-4">
                            <span class="text-sm text-gray-700">${flight.airline}</span>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-900">${flight.origin.code}</span>
                                <i class="fas fa-arrow-right text-xs text-gray-400"></i>
                                <span class="text-sm font-medium text-gray-900">${flight.destination.code}</span>
                            </div>
                            <span class="text-xs text-gray-500">${flight.origin.city} - ${flight.destination.city}</span>
                        </td>
                        <td class="px-4 py-4">
                            <span class="text-sm text-gray-700">${depTime.toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'})}</span>
                        </td>
                        <td class="px-4 py-4">
                            <span class="text-sm text-gray-700">${arrTime.toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'})}</span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="status-badge status-${flight.status.toLowerCase()}">${flight.status}</span>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex-1 bg-gray-200 rounded-full h-2 overflow-hidden">
                                    <div class="bg-primary rounded-full h-2 transition-all duration-500" style="width: ${flight.progress}%"></div>
                                </div>
                                <span class="text-xs font-semibold text-gray-700 min-w-[40px] text-right">${flight.progress.toFixed(0)}%</span>
                            </div>
                        </td>
                    </tr>
                `;
            });

            html += `</tbody></table>`;
            container.innerHTML = html;
        }

        function refreshData() {
            const icon = document.getElementById('refreshIcon');
            icon.classList.add('fa-spin');

            updateFlightData().then(() => {
                setTimeout(() => {
                    icon.classList.remove('fa-spin');
                }, 500);
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            updateFlightData();

            updateInterval = setInterval(updateFlightData, 30000);

            setInterval(() => {
                fetch('../auto_update_flights.php?ajax=1');
            }, 60000);

            setInterval(() => {
                flightsData.forEach(flight => {
                    if (flight.status === 'Departed' && planeMarkers[flight.id]) {
                        const realProgress = calculateRealTimeProgress(flight.departure_time, flight.arrival_time);
                        const progressDecimal = realProgress / 100;

                        const newLat = lerp(flight.origin.lat, flight.destination.lat, progressDecimal);
                        const newLon = lerp(flight.origin.lon, flight.destination.lon, progressDecimal);

                        planeMarkers[flight.id].setLatLng([newLat, newLon]);
                    }
                });
            }, 60000);
        });

        window.addEventListener('beforeunload', function() {
            if (updateInterval) clearInterval(updateInterval);
            Object.values(animationIntervals).forEach(interval => clearInterval(interval));
        });
    </script>
</body>

</html>
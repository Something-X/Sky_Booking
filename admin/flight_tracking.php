<?php
require_once '../config.php';

if (!isAdmin()) {
    redirect('login.php');
}
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
                        primary: '#0066cc',
                        secondary: '#004999',
                        accent: '#ff6b35'
                    }
                }
            }
        }
    </script>
    
    <style>
        #map { height: 600px; width: 100%; }
        
        .plane-icon {
            width: 32px;
            height: 32px;
            transition: all 0.5s ease-out;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .status-scheduled { background: #10b981; color: white; }
        .status-departed { background: #f59e0b; color: white; }
        .status-arrived { background: #3b82f6; color: white; }
        
        .flight-info-card {
            background: white;
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            min-width: 250px;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .animate-pulse-slow {
            animation: pulse 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-gradient-to-r from-primary to-secondary shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="dashboard.php" class="text-white text-2xl font-bold flex items-center">
                    <i class="fas fa-plane-departure mr-2"></i>SkyBooking Admin
                </a>
                <div class="flex items-center space-x-6">
                    <a href="dashboard.php" class="text-white hover:text-gray-200 transition">Dashboard</a>
                    <a href="flight_tracking.php" class="text-white hover:text-gray-200 transition border-b-2 border-white">Live Tracking</a>
                    <a href="penerbangan.php" class="text-white hover:text-gray-200 transition">Penerbangan</a>
                    <a href="pemesanan.php" class="text-white hover:text-gray-200 transition">Pemesanan</a>
                    <a href="logout.php" class="text-white hover:text-gray-200 transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-4xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-plane text-primary mr-4"></i>
                    Live Flight Tracking
                </h1>
                <p class="text-gray-600 mt-2">Real-time monitoring penerbangan aktif</p>
            </div>
            <div class="flex items-center gap-4">
                <div id="lastUpdate" class="text-sm text-gray-600"></div>
                <button onclick="refreshData()" class="bg-primary hover:bg-secondary text-white font-bold px-6 py-3 rounded-lg transition duration-300 flex items-center">
                    <i class="fas fa-sync-alt mr-2" id="refreshIcon"></i>
                    Refresh Data
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Total Flights</p>
                        <h3 id="totalFlights" class="text-3xl font-bold text-gray-800">0</h3>
                    </div>
                    <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-plane text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Scheduled</p>
                        <h3 id="scheduledFlights" class="text-3xl font-bold text-green-600">0</h3>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-clock text-2xl text-green-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Departed</p>
                        <h3 id="departedFlights" class="text-3xl font-bold text-orange-600">0</h3>
                    </div>
                    <div class="w-14 h-14 bg-orange-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-plane-departure text-2xl text-orange-600 animate-pulse-slow"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Arrived</p>
                        <h3 id="arrivedFlights" class="text-3xl font-bold text-blue-600">0</h3>
                    </div>
                    <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-plane-arrival text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Map Container -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-map-marked-alt text-primary mr-3"></i>
                    Peta Penerbangan Indonesia
                </h2>
                <div class="flex items-center gap-2">
                    <span class="status-badge status-scheduled">● Scheduled</span>
                    <span class="status-badge status-departed">● Departed</span>
                    <span class="status-badge status-arrived">● Arrived</span>
                </div>
            </div>
            <div id="map" class="rounded-lg overflow-hidden"></div>
        </div>

        <!-- Flight List -->
        <div class="mt-6 bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Active Flights</h2>
            <div id="flightList" class="overflow-x-auto"></div>
        </div>
    </div>

    <!-- Leaflet.js Script -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        let map, planeMarkers = {}, airportMarkers = {}, flightPaths = {};
        let updateInterval;
        
        // Initialize map centered on Indonesia
        function initMap() {
            map = L.map('map').setView([-2.5, 118], 5);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 18
            }).addTo(map);
        }
        
        // Plane icon SVG
        function getPlaneIcon(status, rotation = 0) {
            let color = '#10b981'; // green
            if (status === 'Departed') color = '#f59e0b'; // orange
            if (status === 'Arrived') color = '#3b82f6'; // blue
            
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
        
        // Airport icon
        function getAirportIcon() {
            return L.divIcon({
                html: '<i class="fas fa-circle text-red-600 text-xl"></i>',
                className: '',
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });
        }
        
        // Calculate rotation angle between two points
        function calculateRotation(lat1, lon1, lat2, lon2) {
            const dLon = (lon2 - lon1);
            const y = Math.sin(dLon) * Math.cos(lat2);
            const x = Math.cos(lat1) * Math.sin(lat2) - Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLon);
            let bearing = Math.atan2(y, x);
            bearing = bearing * (180 / Math.PI);
            bearing = (bearing + 360) % 360;
            return bearing;
        }
        
        // Update flight data
        async function updateFlightData() {
            try {
                const response = await fetch('../api/get_flight_positions.php');
                const data = await response.json();
                
                if (data.success) {
                    updateStats(data);
                    updateMap(data);
                    updateFlightList(data);
                    document.getElementById('lastUpdate').textContent = `Last update: ${data.timestamp}`;
                }
            } catch (error) {
                console.error('Error fetching flight data:', error);
            }
        }
        
        // Update statistics
        function updateStats(data) {
            const scheduled = data.flights.filter(f => f.status === 'Scheduled').length;
            const departed = data.flights.filter(f => f.status === 'Departed').length;
            const arrived = data.flights.filter(f => f.status === 'Arrived').length;
            
            document.getElementById('totalFlights').textContent = data.flights.length;
            document.getElementById('scheduledFlights').textContent = scheduled;
            document.getElementById('departedFlights').textContent = departed;
            document.getElementById('arrivedFlights').textContent = arrived;
        }
        
        // Update map with flights
        function updateMap(data) {
            // Clear old markers
            Object.values(planeMarkers).forEach(marker => map.removeLayer(marker));
            Object.values(flightPaths).forEach(path => map.removeLayer(path));
            planeMarkers = {};
            flightPaths = {};
            
            // Add airports
            data.airports.forEach(airport => {
                if (!airportMarkers[airport.code]) {
                    const marker = L.marker([airport.lat, airport.lon], {
                        icon: getAirportIcon(),
                        title: `${airport.name} (${airport.code})`
                    }).addTo(map);
                    
                    marker.bindPopup(`
                        <div class="text-center">
                            <strong class="text-lg">${airport.code}</strong>
                            <p class="text-sm">${airport.name}</p>
                            <p class="text-xs text-gray-600">${airport.city}</p>
                        </div>
                    `);
                    
                    airportMarkers[airport.code] = marker;
                }
            });
            
            // Add flights
            data.flights.forEach(flight => {
                // Draw flight path
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
                    opacity: 0.6,
                    dashArray: flight.status === 'Scheduled' ? '5, 10' : null
                }).addTo(map);
                
                flightPaths[flight.id] = path;
                
                // Add plane marker
                const rotation = calculateRotation(
                    flight.origin.lat, flight.origin.lon,
                    flight.destination.lat, flight.destination.lon
                );
                
                const planeMarker = L.marker([flight.current_lat, flight.current_lon], {
                    icon: getPlaneIcon(flight.status, rotation),
                    title: flight.flight_code
                }).addTo(map);
                
                // Popup with flight info
                planeMarker.bindPopup(`
                    <div class="flight-info-card">
                        <div class="flex justify-between items-center mb-2">
                            <strong class="text-lg">${flight.flight_code}</strong>
                            <span class="status-badge status-${flight.status.toLowerCase()}">${flight.status}</span>
                        </div>
                        <p class="text-sm text-gray-700 mb-1">${flight.airline}</p>
                        <p class="text-xs text-gray-600 mb-2">${flight.aircraft_type}</p>
                        <hr class="my-2">
                        <div class="text-sm mb-1">
                            <i class="fas fa-plane-departure text-primary"></i> 
                            ${flight.origin.city} (${flight.origin.code})
                        </div>
                        <div class="text-sm mb-2">
                            <i class="fas fa-plane-arrival text-primary"></i> 
                            ${flight.destination.city} (${flight.destination.code})
                        </div>
                        <div class="bg-gray-100 rounded p-2 mt-2">
                            <div class="flex justify-between text-xs mb-1">
                                <span>Progress:</span>
                                <strong>${flight.progress.toFixed(1)}%</strong>
                            </div>
                            <div class="w-full bg-gray-300 rounded-full h-2">
                                <div class="bg-primary rounded-full h-2" style="width: ${flight.progress}%"></div>
                            </div>
                        </div>
                    </div>
                `);
                
                planeMarkers[flight.id] = planeMarker;
            });
        }
        
        // Update flight list table
        function updateFlightList(data) {
            const container = document.getElementById('flightList');
            
            if (data.flights.length === 0) {
                container.innerHTML = '<p class="text-center text-gray-500 py-8">Tidak ada penerbangan aktif saat ini</p>';
                return;
            }
            
            let html = `
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Flight</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Airline</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Route</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Departure</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Arrival</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold">Status</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold">Progress</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            data.flights.forEach(flight => {
                const depTime = new Date(flight.departure_time);
                const arrTime = new Date(flight.arrival_time);
                
                html += `
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-semibold">${flight.flight_code}</td>
                        <td class="px-4 py-3">${flight.airline}</td>
                        <td class="px-4 py-3">
                            ${flight.origin.code} → ${flight.destination.code}
                            <br><span class="text-xs text-gray-500">${flight.origin.city} - ${flight.destination.city}</span>
                        </td>
                        <td class="px-4 py-3 text-sm">${depTime.toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'})}</td>
                        <td class="px-4 py-3 text-sm">${arrTime.toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'})}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="status-badge status-${flight.status.toLowerCase()}">${flight.status}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2">
                                    <div class="bg-primary rounded-full h-2 transition-all" style="width: ${flight.progress}%"></div>
                                </div>
                                <span class="text-xs font-semibold">${flight.progress.toFixed(0)}%</span>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            html += `</tbody></table>`;
            container.innerHTML = html;
        }
        
        // Refresh data manually
        function refreshData() {
            const icon = document.getElementById('refreshIcon');
            icon.classList.add('fa-spin');
            
            updateFlightData().then(() => {
                setTimeout(() => {
                    icon.classList.remove('fa-spin');
                }, 500);
            });
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            updateFlightData();
            
            // Auto-refresh setiap 10 detik
            updateInterval = setInterval(updateFlightData, 10000);
            
            // Call auto-update script setiap 1 menit
            setInterval(() => {
                fetch('../auto_update_flights.php?ajax=1');
            }, 60000);
        });
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (updateInterval) clearInterval(updateInterval);
        });
    </script>
</body>
</html>
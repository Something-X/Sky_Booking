let allFlights = [];
let returnFlights = [];
let pendingBookingData = null;
let selectedDepartureFlight = null;
let isRoundTrip = false;
let searchParams = {};

// ==================== Modal Functions ====================
function showLoginModal(flightId, jumlah) {
    pendingBookingData = { flightId, jumlah };
    document.getElementById('loginModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeLoginModal() {
    document.getElementById('loginModal').classList.remove('active');
    document.body.style.overflow = 'auto';
    pendingBookingData = null;
}

function redirectToLogin() {
    if (pendingBookingData) {
        const { flightId, jumlah } = pendingBookingData;
        window.location.href = `login.php?redirect=${encodeURIComponent('pesan.php?id=' + flightId + '&jumlah=' + jumlah)}`;
    }
}

// Close modal when clicking outside
document.getElementById('loginModal').addEventListener('click', function(e) {
    if (e.target === this) closeLoginModal();
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLoginModal();
});

// ==================== Flight Search & API ====================
window.addEventListener('DOMContentLoaded', initializeSearch);

function initializeSearch() {
    const urlParams = new URLSearchParams(window.location.search);
    searchParams = {
        asal: urlParams.get('asal') || '',
        tujuan: urlParams.get('tujuan') || '',
        tanggal: urlParams.get('tanggal') || '',
        tanggal_kembali: urlParams.get('tanggal_kembali') || '',
        kelas: urlParams.get('kelas') || 'Economy',
        jumlah: parseInt(urlParams.get('jumlah')) || 1
    };

    isRoundTrip = !!searchParams.tanggal_kembali;

    if (isRoundTrip) {
        updateUIForRoundTrip();
    }

    searchFlights();
}

function updateUIForRoundTrip() {
    const searchSummary = document.querySelector('.search-summary .container > div');
    if (searchSummary) {
        // Add return date to summary
        const returnDateHTML = `
            <div class="border-l border-blue-400 pl-6 flex items-center gap-2">
                <i class="fas fa-calendar-check text-blue-200"></i>
                <span class="text-blue-100">Return:</span>
                <span class="font-bold">${formatDate(searchParams.tanggal_kembali)}</span>
            </div>
        `;
        const dateElement = searchSummary.querySelector('.border-l');
        dateElement.insertAdjacentHTML('afterend', returnDateHTML);
    }
}

async function searchFlights() {
    const formData = new FormData();
    formData.append('asal', searchParams.asal);
    formData.append('tujuan', searchParams.tujuan);
    formData.append('tanggal', searchParams.tanggal);
    formData.append('kelas', searchParams.kelas);

    try {
        const response = await fetch('api/search_flights.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success && data.data && data.data.length > 0) {
            allFlights = data.data;
            
            // If round trip, also search return flights
            if (isRoundTrip) {
                await searchReturnFlights();
            }
            
            populateAirlineFilters(allFlights);
            displayDepartureFlights();
        } else {
            document.getElementById('loadingSpinner').classList.add('hidden');
            showNoResults();
        }
    } catch (error) {
        console.error('Search error:', error);
        document.getElementById('loadingSpinner').classList.add('hidden');
        showError();
    }
}

async function searchReturnFlights() {
    const formData = new FormData();
    formData.append('asal', searchParams.tujuan); // Swap origin and destination
    formData.append('tujuan', searchParams.asal);
    formData.append('tanggal', searchParams.tanggal_kembali);
    formData.append('kelas', searchParams.kelas);

    try {
        const response = await fetch('api/search_flights.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success && data.data) {
            returnFlights = data.data;
        }
    } catch (error) {
        console.error('Return flight search error:', error);
    }
}

function displayDepartureFlights() {
    document.getElementById('loadingSpinner').classList.add('hidden');
    
    const headerTitle = document.querySelector('.result-header h2');
    if (isRoundTrip) {
        headerTitle.innerHTML = `
            <i class="fas fa-plane-departure mr-2" style="color: #3b82f6;"></i>
            <span id="resultCount">${allFlights.length}</span> Departure Flights
            <span class="text-base font-normal text-gray-500 ml-2">(Step 1 of 2)</span>
        `;
    } else {
        headerTitle.innerHTML = `
            <i class="fas fa-plane mr-2" style="color: #3b82f6;"></i>
            <span id="resultCount">${allFlights.length}</span> Flights Available
        `;
    }
    
    applyFilters();
}

function displayReturnFlights() {
    const headerTitle = document.querySelector('.result-header h2');
    headerTitle.innerHTML = `
        <i class="fas fa-plane-arrival mr-2" style="color: #3b82f6;"></i>
        <span id="resultCount">${returnFlights.length}</span> Return Flights
        <span class="text-base font-normal text-gray-500 ml-2">(Step 2 of 2)</span>
    `;

    // Show selected departure flight summary
    const container = document.getElementById('resultsContainer');
    const selectedFlightSummary = createSelectedFlightSummary(selectedDepartureFlight);
    container.innerHTML = selectedFlightSummary;

    // Display return flights
    allFlights = returnFlights;
    populateAirlineFilters(returnFlights);
    applyFilters(true); // true = append to existing content
}

function createSelectedFlightSummary(flight) {
    const totalHarga = flight.harga * searchParams.jumlah;
    return `
        <div class="mb-6 p-6 rounded-2xl" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border: 2px solid #3b82f6;">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold flex items-center" style="color: #1e3a8a;">
                    <i class="fas fa-check-circle mr-2 text-green-600"></i>
                    Selected Departure Flight
                </h3>
                <button onclick="backToDeparture()" class="text-sm text-blue-600 hover:text-blue-800 font-semibold">
                    <i class="fas fa-edit mr-1"></i>Change Flight
                </button>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-4 rounded-xl">
                <div class="flex items-center gap-3">
                    <div class="airline-logo">
                        <i class="fas fa-plane text-2xl" style="color: #3b82f6;"></i>
                    </div>
                    <div>
                        <div class="font-bold" style="color: #1e3a8a;">${flight.maskapai}</div>
                        <div class="text-xs text-gray-500">${flight.kode_penerbangan}</div>
                    </div>
                </div>
                <div class="flex items-center gap-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold" style="color: #1e3a8a;">${flight.jam_berangkat}</div>
                        <div class="text-xs text-gray-600">${flight.asal.split('(')[1]?.replace(')', '') || flight.asal}</div>
                    </div>
                    <div><i class="fas fa-arrow-right" style="color: #3b82f6;"></i></div>
                    <div class="text-center">
                        <div class="text-2xl font-bold" style="color: #1e3a8a;">${flight.jam_tiba}</div>
                        <div class="text-xs text-gray-600">${flight.tujuan.split('(')[1]?.replace(')', '') || flight.tujuan}</div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-lg font-bold" style="color: #f97316;">${formatRupiah(totalHarga)}</div>
                    <div class="text-xs text-gray-500">${searchParams.jumlah} passenger${searchParams.jumlah > 1 ? 's' : ''}</div>
                </div>
            </div>
        </div>
        <div class="mb-6">
            <h3 class="text-xl font-bold mb-4" style="color: #1e3a8a;">
                <i class="fas fa-plane-arrival mr-2" style="color: #3b82f6;"></i>
                Select Your Return Flight
            </h3>
        </div>
    `;
}

function backToDeparture() {
    selectedDepartureFlight = null;
    displayDepartureFlights();
}

// ==================== Filter Functions ====================
function populateAirlineFilters(flights) {
    const airlines = [...new Set(flights.map(f => f.maskapai))];
    const container = document.getElementById('airlineFilters');
    container.innerHTML = airlines.map(airline => `
        <label class="flex items-center cursor-pointer text-sm group">
            <input type="checkbox" class="filter-airline filter-checkbox mr-3 w-4 h-4" value="${airline}" checked>
            <span class="text-gray-700 group-hover:text-blue-600 transition">${airline}</span>
        </label>
    `).join('');

    document.querySelectorAll('.filter-airline').forEach(el => {
        el.addEventListener('change', () => applyFilters());
    });
}

function applyFilters(appendMode = false) {
    const selectedAirlines = Array.from(document.querySelectorAll('.filter-airline:checked')).map(el => el.value);
    const priceRange = document.querySelector('.filter-price:checked')?.value;
    let minPrice = 0, maxPrice = 99999999;
    
    if (priceRange) {
        [minPrice, maxPrice] = priceRange.split('-').map(Number);
    }

    const selectedTimes = Array.from(document.querySelectorAll('.filter-time:checked')).map(el => el.value);

    let filtered = allFlights.filter(flight => {
        if (!selectedAirlines.includes(flight.maskapai)) return false;

        const price = flight.harga * searchParams.jumlah;
        if (price < minPrice || price > maxPrice) return false;

        if (selectedTimes.length > 0) {
            const time = flight.jam_berangkat;
            const [hours] = time.split(':').map(Number);
            const timeInMinutes = hours * 60;

            const matchesTime = selectedTimes.some(range => {
                const [start, end] = range.split('-').map(t => {
                    const [h, m] = t.split(':').map(Number);
                    return h * 60 + (m || 0);
                });
                return timeInMinutes >= start && timeInMinutes < end;
            });

            if (!matchesTime) return false;
        }

        return true;
    });

    displayResults(filtered, appendMode);
}

// ==================== Display Functions ====================
function displayResults(flights, appendMode = false) {
    const container = document.getElementById('resultsContainer');
    document.getElementById('resultCount').textContent = flights.length;

    if (flights.length === 0) {
        container.innerHTML = `
            <div class="flight-card p-8 text-center">
                <i class="fas fa-search text-6xl mb-4" style="color: #93c5fd;"></i>
                <h3 class="text-xl font-bold mb-2" style="color: #1e3a8a;">No Flights Found</h3>
                <p class="text-gray-600 mb-4">Try adjusting your filters or search criteria</p>
                <button onclick="resetFilters()" class="book-button">
                    <i class="fas fa-redo mr-2"></i>Reset Filters
                </button>
            </div>
        `;
        return;
    }

    const flightsHTML = flights.map((flight, index) => {
        const totalHarga = flight.harga * searchParams.jumlah;
        return createFlightCard(flight, totalHarga, index);
    }).join('');

    if (appendMode) {
        container.insertAdjacentHTML('beforeend', flightsHTML);
    } else {
        container.innerHTML = flightsHTML;
    }

    attachDetailToggleListeners();
}

function createFlightCard(flight, totalHarga, index) {
    const isReturnSelection = selectedDepartureFlight !== null;
    const buttonText = isReturnSelection ? 'Select Return' : (isRoundTrip ? 'Select Departure' : 'Book Now');
    const buttonIcon = isReturnSelection ? 'fa-check-circle' : 'fa-ticket-alt';
    
    return `
        <div class="flight-card mb-5 overflow-hidden">
            <div class="p-6">
                <div class="flex flex-wrap items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="airline-logo">
                            <i class="fas fa-plane text-3xl" style="color: #3b82f6;"></i>
                        </div>
                        <div>
                            <div class="font-bold text-lg" style="color: #1e3a8a;">${flight.maskapai}</div>
                            <div class="text-xs text-gray-500 mt-0.5">${flight.kode_penerbangan}</div>
                            <div class="price-badge mt-2">Best Price</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-8 flex-1 min-w-0">
                        <div class="text-center">
                            <div class="text-3xl font-bold" style="color: #1e3a8a;">${flight.jam_berangkat}</div>
                            <div class="text-sm text-gray-600 mt-1 font-medium">${flight.asal.split('(')[1]?.replace(')', '') || flight.asal}</div>
                        </div>
                        
                        <div class="flex-1 relative px-4">
                            <div class="border-t-2 border-dashed" style="border-color: #93c5fd;"></div>
                            <div class="absolute -top-[0px] left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-blue-100 px-3 py-1 rounded-full">
                                <i class="fas fa-plane text-sm" style="color: #3b82f6;"></i>
                            </div>
                            <div class="text-center text-xs text-gray-500 mt-3 font-medium">Direct Flight</div>
                        </div>
                        
                        <div class="text-center">
                            <div class="text-3xl font-bold" style="color: #1e3a8a;">${flight.jam_tiba}</div>
                            <div class="text-sm text-gray-600 mt-1 font-medium">${flight.tujuan.split('(')[1]?.replace(')', '') || flight.tujuan}</div>
                        </div>
                    </div>

                    <div class="text-right">
                        <div class="mb-1">
                            <span class="text-xs text-gray-500">Starting from</span>
                        </div>
                        <div class="text-3xl font-bold mb-1" style="color: #f97316;">${formatRupiah(totalHarga)}</div>
                        <div class="text-xs text-gray-500 mb-4">for ${searchParams.jumlah} passenger${searchParams.jumlah > 1 ? 's' : ''}</div>
                        <button onclick="handleFlightSelection(${flight.id}, ${searchParams.jumlah}, ${index})" class="book-button">
                            <i class="fas ${buttonIcon} mr-2"></i>${buttonText}
                        </button>
                    </div>
                </div>

                <button class="toggle-detail detail-toggle mt-5 w-full text-center py-2 flex items-center justify-center gap-2" data-detail="detail-${index}">
                    <span>View Flight Details</span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </button>
            </div>

            <div id="detail-${index}" class="flight-detail" style="background: #f8fafc; border-top: 2px solid #e5e7eb;">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h5 class="font-bold text-lg mb-4 flex items-center" style="color: #1e3a8a;">
                                <i class="fas fa-plane-departure mr-2" style="color: #3b82f6;"></i>
                                Departure
                            </h5>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Airport:</span>
                                    <span class="font-semibold" style="color: #1e3a8a;">${flight.asal}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Time:</span>
                                    <span class="font-semibold" style="color: #1e3a8a;">${flight.jam_berangkat}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Available Seats:</span>
                                    <span class="font-semibold text-green-600">${flight.tersedia} seats</span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h5 class="font-bold text-lg mb-4 flex items-center" style="color: #1e3a8a;">
                                <i class="fas fa-plane-arrival mr-2" style="color: #3b82f6;"></i>
                                Arrival
                            </h5>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Airport:</span>
                                    <span class="font-semibold" style="color: #1e3a8a;">${flight.tujuan}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Time:</span>
                                    <span class="font-semibold" style="color: #1e3a8a;">${flight.jam_tiba}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 p-5 rounded-xl" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-info-circle text-xl mt-0.5" style="color: #3b82f6;"></i>
                            <div class="flex-1">
                                <p class="font-bold mb-2" style="color: #1e3a8a;">Flight Information</p>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm text-gray-700">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-suitcase text-xs" style="color: #3b82f6;"></i>
                                        <span>Cabin baggage 7kg</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-luggage-cart text-xs" style="color: #3b82f6;"></i>
                                        <span>Check-in baggage 20kg</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-shield-alt text-xs" style="color: #3b82f6;"></i>
                                        <span>Refundable</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function handleFlightSelection(flightId, jumlah, flightIndex) {
    const isLoggedIn = document.querySelector('[data-logged-in]')?.dataset.loggedIn === 'true';
    
    if (!isLoggedIn) {
        showLoginModal(flightId, jumlah);
        return;
    }

    if (isRoundTrip && !selectedDepartureFlight) {
        // Select departure flight and show return flights
        selectedDepartureFlight = allFlights[flightIndex];
        displayReturnFlights();
    } else if (isRoundTrip && selectedDepartureFlight) {
        // Both flights selected, proceed to booking
        const returnFlight = allFlights[flightIndex];
        window.location.href = `pesan.php?departure_id=${selectedDepartureFlight.id}&return_id=${returnFlight.id}&jumlah=${jumlah}`;
    } else {
        // One-way booking
        window.location.href = `pesan.php?id=${flightId}&jumlah=${jumlah}`;
    }
}

function attachDetailToggleListeners() {
    document.querySelectorAll('.toggle-detail').forEach(btn => {
        btn.addEventListener('click', function() {
            const detailId = this.dataset.detail;
            const detail = document.getElementById(detailId);
            const icon = this.querySelector('.toggle-icon');

            detail.classList.toggle('active');
            icon.classList.toggle('fa-chevron-down');
            icon.classList.toggle('fa-chevron-up');
        });
    });
}

function showNoResults() {
    document.getElementById('resultsContainer').innerHTML = `
        <div class="flight-card p-12 text-center">
            <i class="fas fa-plane-slash text-7xl mb-6" style="color: #93c5fd;"></i>
            <h3 class="text-2xl font-bold mb-3" style="color: #1e3a8a;">No Flights Available</h3>
            <p class="text-gray-600 mb-6 max-w-md mx-auto">Sorry, we couldn't find any flights matching your search criteria.</p>
            <button onclick="window.location.href='index.php'" class="book-button">
                <i class="fas fa-search mr-2"></i>New Search
            </button>
        </div>
    `;
}

function showError() {
    document.getElementById('resultsContainer').innerHTML = `
        <div class="flight-card p-12 text-center">
            <i class="fas fa-exclamation-triangle text-7xl mb-6" style="color: #fbbf24;"></i>
            <h3 class="text-2xl font-bold mb-3" style="color: #1e3a8a;">Something Went Wrong</h3>
            <p class="text-gray-600 mb-6">Please try again or contact customer service</p>
            <button onclick="initializeSearch()" class="book-button">
                <i class="fas fa-redo mr-2"></i>Try Again
            </button>
        </div>
    `;
}

// ==================== Utility Functions ====================
function formatRupiah(angka) {
    return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function formatDate(dateStr) {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const date = new Date(dateStr);
    return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
}

function resetFilters() {
    document.querySelectorAll('.filter-airline').forEach(el => el.checked = true);
    document.querySelectorAll('.filter-price')[0].checked = true;
    document.querySelectorAll('.filter-time').forEach(el => el.checked = false);
    applyFilters();
}

// ==================== Event Listeners ====================
document.querySelectorAll('.filter-price, .filter-time').forEach(el => {
    el.addEventListener('change', () => applyFilters());
});

document.getElementById('resetFilter')?.addEventListener('click', resetFilters);

document.getElementById('sortSelect')?.addEventListener('change', function() {
    const value = this.value;
    const sorted = [...allFlights];

    switch (value) {
        case 'price-asc':
            sorted.sort((a, b) => a.harga - b.harga);
            break;
        case 'price-desc':
            sorted.sort((a, b) => b.harga - a.harga);
            break;
        case 'time-asc':
            sorted.sort((a, b) => a.jam_berangkat.localeCompare(b.jam_berangkat));
            break;
        case 'time-desc':
            sorted.sort((a, b) => b.jam_berangkat.localeCompare(a.jam_berangkat));
            break;
    }

    allFlights = sorted;
    applyFilters(selectedDepartureFlight !== null);
});
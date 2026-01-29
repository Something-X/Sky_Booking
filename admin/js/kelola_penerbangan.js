// ========================================
// KELOLA PENERBANGAN JAVASCRIPT
// ========================================

let currentTab = "pesawat";

// Initialize on page load
window.addEventListener("DOMContentLoaded", function () {
  console.log("=== Page loaded, initializing... ===");
  loadAirports();
  switchTab("pesawat");

  // Auto refresh arrived count every 30 seconds
  setInterval(updateArrivedCount, 30000);
  updateArrivedCount();
});

// ========================================
// TAB SWITCHING
// ========================================
function switchTab(tab) {
  console.log("=== Switching to tab:", tab, "===");
  currentTab = tab;

  // Hide all content
  document
    .querySelectorAll(".tab-content")
    .forEach((el) => el.classList.add("hidden"));
  document
    .querySelectorAll(".tab-button")
    .forEach((el) => el.classList.remove("active"));

  // Show selected content
  document.getElementById("content-" + tab).classList.remove("hidden");
  document.getElementById("tab-" + tab).classList.add("active");

  // Load data
  if (tab === "pesawat") {
    console.log("Loading pesawat...");
    loadPesawat();
  } else if (tab === "jadwal") {
    console.log("Loading jadwal...");
    loadJadwal();
  } else if (tab === "arrived") {
    console.log("Loading arrived aircraft...");
    loadArrived();
  }
}

// ========================================
// LOAD AIRPORTS
// ========================================
async function loadAirports() {
  console.log("=== Loading airports... ===");
  try {
    const response = await fetch("api/get_airports.php");
    console.log("Response status:", response.status);

    const data = await response.json();
    console.log("Airports data received:", data);

    if (data.success) {
      const airports = data.data;
      console.log("Number of airports:", airports.length);

      // Populate all airport selects
      const selects = [
        "airportSelect",
        "originAirportSelect",
        "destAirportSelect",
      ];

      selects.forEach((selectId) => {
        const select = document.getElementById(selectId);
        console.log(
          `Populating select: ${selectId}`,
          select ? "Found" : "NOT FOUND",
        );

        if (select) {
          // Clear existing options
          select.innerHTML = "";

          // Add placeholder
          const defaultOption = document.createElement("option");
          defaultOption.value = "";
          defaultOption.textContent =
            selectId === "airportSelect"
              ? "Pilih Bandara"
              : selectId === "originAirportSelect"
                ? "Pilih Bandara Asal"
                : "Pilih Bandara Tujuan";
          select.appendChild(defaultOption);

          // Add airport options
          airports.forEach((airport) => {
            const option = document.createElement("option");
            option.value = airport.id;
            option.textContent = `${airport.city} (${airport.code}) - ${airport.name}`;
            select.appendChild(option);
          });

          console.log(
            `✓ ${selectId} populated with ${select.options.length} options`,
          );
        } else {
          console.warn(`✗ Select element ${selectId} not found in DOM`);
        }
      });

      console.log("=== Airports loaded successfully ===");
    } else {
      console.error("Failed to load airports:", data.message);
    }
  } catch (error) {
    console.error("Error loading airports:", error);
  }
}

// ========================================
// PESAWAT FUNCTIONS
// ========================================
async function loadPesawat() {
  const search = document.getElementById("searchPesawat")?.value || "";
  const status = document.getElementById("filterStatusPesawat")?.value || "";

  try {
    const response = await fetch(
      `api/get_pesawat.php?search=${encodeURIComponent(search)}&status=${status}`,
    );
    const data = await response.json();

    if (data.success) {
      displayPesawat(data.data);
    }
  } catch (error) {
    console.error("Error:", error);
    showError("Gagal memuat data pesawat");
  }
}

function displayPesawat(pesawat) {
  const tbody = document.getElementById("pesawatTableBody");

  if (pesawat.length === 0) {
    tbody.innerHTML = `
            <tr>
                <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-plane text-4xl mb-2"></i>
                    <p>Tidak ada data pesawat</p>
                </td>
            </tr>
        `;
    return;
  }

  let html = "";
  pesawat.forEach((p) => {
    const statusClass =
      {
        operasional: "bg-green-100 text-green-800",
        maintenance: "bg-yellow-100 text-yellow-800",
        "non-aktif": "bg-red-100 text-red-800",
      }[p.status_pesawat] || "bg-gray-100 text-gray-800";

    // CLASS BADGES untuk Kelas Layanan
    const kelasClass =
      {
        "Economy Class": "bg-blue-100 text-blue-800",
        "Business Class": "bg-purple-100 text-purple-800",
        "First Class": "bg-amber-100 text-amber-800",
      }[p.kelas_layanan] || "bg-gray-100 text-gray-800";

    html += `
            <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                <td class="px-6 py-4 font-semibold">${p.maskapai}</td>
                <td class="px-6 py-4">
                    <span class="font-mono text-sm bg-gray-100 px-2 py-1 rounded">${p.nomor_registrasi}</span>
                </td>
                <td class="px-6 py-4 text-sm">${p.model}</td>
                <td class="px-6 py-4 text-center">
                    <span class="font-bold text-primary">${p.kapasitas}</span>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="${kelasClass} px-3 py-1 rounded-full text-xs font-semibold">
                        ${p.kelas_layanan || "Economy Class"}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm">${p.lokasi || "-"}</td>
                <td class="px-6 py-4 text-center">
                    <span class="${statusClass} px-3 py-1 rounded-full text-xs font-semibold uppercase">
                        ${p.status_pesawat}
                    </span>
                </td>
                <td class="px-6 py-4 text-center">
                    <button onclick="editPesawat(${p.id})" class="text-blue-600 hover:text-blue-800 mr-3 transition" title="Edit">
                        <i class="fas fa-edit text-lg"></i>
                    </button>
                    <button onclick="deletePesawat(${p.id}, '${p.nomor_registrasi}')" class="text-red-600 hover:text-red-800 transition" title="Hapus">
                        <i class="fas fa-trash text-lg"></i>
                    </button>
                </td>
            </tr>
        `;
  });

  tbody.innerHTML = html;
}

function showAddPesawatModal() {
  document.getElementById("pesawatModalTitle").textContent = "Tambah Pesawat";
  document.getElementById("pesawatForm").reset();
  document.getElementById("pesawatId").value = "";
  document.getElementById("pesawatModal").classList.remove("hidden");
}

async function editPesawat(id) {
  try {
    const response = await fetch(`api/get_pesawat_detail.php?id=${id}`);
    const data = await response.json();

    if (data.success) {
      const p = data.data;
      document.getElementById("pesawatModalTitle").textContent = "Edit Pesawat";
      document.getElementById("pesawatId").value = p.id;

      const form = document.getElementById("pesawatForm");
      form.maskapai.value = p.maskapai;
      form.nomor_registrasi.value = p.nomor_registrasi;
      form.model.value = p.model;
      form.kapasitas.value = p.kapasitas;

      // Set kelas layanan
      const kelasSelect = form.kelas_layanan;
      if (kelasSelect) {
        kelasSelect.value = p.kelas_layanan || "Economy Class";
        console.log("✓ Kelas layanan set to:", kelasSelect.value);
      } else {
        console.warn("⚠ Kelas layanan field not found in form");
      }

      form.airport_id.value = p.airport_id || "";
      form.status_pesawat.value = p.status_pesawat;

      document.getElementById("pesawatModal").classList.remove("hidden");
    }
  } catch (error) {
    showError("Gagal memuat data pesawat");
  }
}

async function deletePesawat(id, nomor) {
  if (
    !confirm(
      `Yakin ingin menghapus pesawat ${nomor}?\n\nPeringatan: Semua jadwal penerbangan yang menggunakan pesawat ini akan terpengaruh!`,
    )
  )
    return;

  try {
    const response = await fetch("api/delete_pesawat.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + id,
    });

    const data = await response.json();

    if (data.success) {
      showSuccess("Pesawat berhasil dihapus");
      loadPesawat();
    } else {
      showError(data.message || "Gagal menghapus pesawat");
    }
  } catch (error) {
    showError("Terjadi kesalahan");
  }
}

// Form Submit Pesawat
document
  .getElementById("pesawatForm")
  ?.addEventListener("submit", async function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');

    submitBtn.disabled = true;
    submitBtn.innerHTML =
      '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';

    try {
      const response = await fetch("api/save_pesawat.php", {
        method: "POST",
        body: formData,
      });

      const data = await response.json();

      if (data.success) {
        showSuccess("Data pesawat berhasil disimpan");
        closeModal("pesawatModal");
        loadPesawat();
        updateArrivedCount(); // Update badge
      } else {
        showError(data.message || "Gagal menyimpan data");
      }
    } catch (error) {
      showError("Terjadi kesalahan");
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Simpan';
    }
  });

// ========================================
// JADWAL FUNCTIONS
// ========================================
async function loadJadwal() {
  const search = document.getElementById("searchJadwal")?.value || "";
  const tanggal = document.getElementById("filterTanggal")?.value || "";
  const status = document.getElementById("filterStatusJadwal")?.value || "";

  try {
    const response = await fetch(
      `api/get_jadwal.php?search=${encodeURIComponent(
        search,
      )}&tanggal=${tanggal}&status=${status}`,
    );
    const data = await response.json();

    if (data.success) {
      displayJadwal(data.data);
    }
  } catch (error) {
    console.error("Error:", error);
    showError("Gagal memuat data jadwal");
  }
}

function displayJadwal(jadwal) {
  const tbody = document.getElementById("jadwalTableBody");

  if (jadwal.length === 0) {
    tbody.innerHTML = `
            <tr>
                <td colspan="10" class="px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-calendar-alt text-4xl mb-2"></i>
                    <p>Tidak ada jadwal penerbangan</p>
                </td>
            </tr>
        `;
    return;
  }

  let html = "";
  jadwal.forEach((j) => {
    const statusClass =
      {
        Scheduled: "bg-blue-100 text-blue-800",
        Departed: "bg-purple-100 text-purple-800",
        Arrived: "bg-green-100 text-green-800",
        Cancelled: "bg-red-100 text-red-800",
      }[j.status_tracking] || "bg-gray-100 text-gray-800";

    // CLASS BADGES untuk Kelas Layanan
    const kelasClass =
      {
        "Economy Class": "bg-blue-100 text-blue-800",
        "Business Class": "bg-purple-100 text-purple-800",
        "First Class": "bg-amber-100 text-amber-800",
      }[j.kelas_layanan] || "bg-gray-100 text-gray-800";

    // Extract airport codes
    let asalCode = "N/A";
    let tujuanCode = "N/A";

    if (j.asal) {
      const match = j.asal.match(/\(([A-Z]{3})\)/);
      asalCode = match ? match[1] : j.asal.substring(0, 3).toUpperCase();
    }

    if (j.tujuan) {
      const match = j.tujuan.match(/\(([A-Z]{3})\)/);
      tujuanCode = match ? match[1] : j.tujuan.substring(0, 3).toUpperCase();
    }

    const rute = `${asalCode} → ${tujuanCode}`;
    const namaAsal = j.asal ? j.asal.split("(")[0].trim() : "-";
    const namaTujuan = j.tujuan ? j.tujuan.split("(")[0].trim() : "-";

    html += `
            <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                <td class="px-4 py-4">
                    <span class="font-mono text-sm font-semibold">${
                      j.kode_penerbangan
                    }</span>
                </td>
                <td class="px-4 py-4">
                    <div class="text-sm font-semibold">${
                      j.pesawat_nomor || "N/A"
                    }</div>
                    <div class="text-xs text-gray-500">${j.maskapai}</div>
                </td>
                <td class="px-4 py-4 text-sm">  
                    <div class="font-medium text-primary">${rute}</div>
                    <div class="text-xs text-gray-400">${namaAsal} → ${namaTujuan}</div>
                </td>
                <td class="px-4 py-4 text-sm">${formatTanggal(j.tanggal)}</td>
                <td class="px-4 py-4 text-sm">
                    <div>${j.jam_berangkat}</div>
                    <div class="text-xs text-gray-500">→ ${j.jam_tiba}</div>
                </td>
                <td class="px-4 py-4 text-sm text-right font-semibold">${formatRupiah(
                  j.harga,
                )}</td>
                <td class="px-4 py-4 text-sm text-center">
                    <span>${j.tersedia}/${j.kapasitas}</span>
                </td>
                <td class="px-4 py-4 text-center">
                    <span class="${kelasClass} px-3 py-1 rounded-full text-xs font-semibold">
                        ${j.kelas_layanan || "Economy Class"}
                    </span>
                </td>
                <td class="px-4 py-4 text-center">
                    <span class="${statusClass} px-3 py-1 rounded-full text-xs font-semibold">
                        ${j.status_tracking}
                    </span>
                </td>
                <td class="px-4 py-4 text-center">
                    <button onclick="editJadwal(${
                      j.id
                    })" class="text-blue-600 hover:text-blue-800 mr-3 transition" title="Edit">
                        <i class="fas fa-edit text-lg"></i>
                    </button>
                    <button onclick="deleteJadwal(${j.id}, '${
                      j.kode_penerbangan
                    }')" class="text-red-600 hover:text-red-800 transition" title="Hapus">
                        <i class="fas fa-trash text-lg"></i>
                    </button>
                </td>
            </tr>
        `;
  });

  tbody.innerHTML = html;
}

async function showAddJadwalModal() {
  console.log("=== Opening add jadwal modal ===");

  document.getElementById("jadwalForm").reset();
  document.getElementById("jadwalId").value = "";
  document.getElementById("jadwalFromArrived").value = "0";
  document.getElementById("jadwalModalTitle").textContent =
    "Buat Jadwal Penerbangan";
  document.getElementById("jadwalModal").classList.remove("hidden");

  const originSelect = document.getElementById("originAirportSelect");
  const destSelect = document.getElementById("destAirportSelect");
  const pesawatSelect = document.getElementById("pesawatSelectJadwal");

  if (originSelect)
    originSelect.innerHTML = '<option value="">Memuat...</option>';
  if (destSelect) destSelect.innerHTML = '<option value="">Memuat...</option>';
  if (pesawatSelect)
    pesawatSelect.innerHTML = '<option value="">Memuat...</option>';

  await Promise.all([loadAirports(), loadOperationalPesawat()]);

  const today = new Date().toISOString().split("T")[0];
  const tanggalInput = document.querySelector('input[name="tanggal"]');
  if (tanggalInput) {
    tanggalInput.setAttribute("min", today);
  }
}

async function loadOperationalPesawat() {
  console.log("Loading operational aircraft...");
  try {
    const response = await fetch("api/get_pesawat.php?status=operasional");
    const data = await response.json();

    if (data.success) {
      const select = document.getElementById("pesawatSelectJadwal");
      if (!select) {
        console.error("pesawatSelectJadwal not found");
        return;
      }

      select.innerHTML = '<option value="">Pilih Pesawat</option>';

      data.data.forEach((p) => {
        const option = document.createElement("option");
        option.value = p.id;
        option.textContent = `${p.nomor_registrasi} - ${p.maskapai} (${p.model})`;
        option.dataset.kapasitas = p.kapasitas;
        select.appendChild(option);
      });

      console.log(`✓ Loaded ${data.data.length} operational aircraft`);
    }
  } catch (error) {
    console.error("Error loading aircraft:", error);
  }
}

async function editJadwal(id) {
  console.log("=== Editing jadwal:", id, "===");

  document.getElementById("jadwalModal").classList.remove("hidden");
  document.getElementById("jadwalModalTitle").textContent =
    "Edit Jadwal Penerbangan";
  document.getElementById("jadwalId").value = id;

  const originSelect = document.getElementById("originAirportSelect");
  const destSelect = document.getElementById("destAirportSelect");
  const pesawatSelect = document.getElementById("pesawatSelectJadwal");

  if (originSelect)
    originSelect.innerHTML = '<option value="">Memuat...</option>';
  if (destSelect) destSelect.innerHTML = '<option value="">Memuat...</option>';
  if (pesawatSelect)
    pesawatSelect.innerHTML = '<option value="">Memuat...</option>';

  try {
    const [_, __, flightResponse] = await Promise.all([
      loadAirports(),
      loadOperationalPesawat(),
      fetch(`api/get_jadwal_detail.php?id=${id}`),
    ]);

    const data = await flightResponse.json();
    console.log("Flight details:", data);

    if (data.success) {
      const j = data.data;

      const form = document.getElementById("jadwalForm");
      form.pesawat_id.value = j.pesawat_id || "";
      form.kode_penerbangan.value = j.kode_penerbangan;
      form.harga.value = j.harga;
      form.origin_airport_id.value = j.origin_airport_id || "";
      form.destination_airport_id.value = j.destination_airport_id || "";
      form.tanggal.value = j.tanggal;
      form.jam_berangkat.value = j.jam_berangkat;
      form.jam_tiba.value = j.jam_tiba;
      form.status_tracking.value = j.status_tracking;

      console.log("Form populated with:", {
        origin: form.origin_airport_id.value,
        dest: form.destination_airport_id.value,
      });
    } else {
      showError("Gagal memuat data jadwal");
    }
  } catch (error) {
    console.error("Error in editJadwal:", error);
    showError("Gagal memuat data jadwal");
  }
}

async function deleteJadwal(id, kode) {
  if (!confirm(`Yakin ingin menghapus jadwal penerbangan ${kode}?`)) return;

  try {
    const response = await fetch("api/delete_jadwal.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + id,
    });

    const data = await response.json();

    if (data.success) {
      showSuccess("Jadwal berhasil dihapus");
      loadJadwal();
      updateArrivedCount(); // Update badge
    } else {
      showError(data.message || "Gagal menghapus jadwal");
    }
  } catch (error) {
    showError("Terjadi kesalahan");
  }
}

document
  .getElementById("jadwalForm")
  ?.addEventListener("submit", async function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');

    const pesawatSelect = document.getElementById("pesawatSelectJadwal");
    const selectedOption = pesawatSelect.options[pesawatSelect.selectedIndex];
    const kapasitas = selectedOption.dataset.kapasitas || 100;
    formData.append("kapasitas", kapasitas);

    submitBtn.disabled = true;
    submitBtn.innerHTML =
      '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';

    try {
      const response = await fetch("api/save_jadwal.php", {
        method: "POST",
        body: formData,
      });

      const data = await response.json();

      if (data.success) {
        showSuccess("Jadwal penerbangan berhasil disimpan");
        closeModal("jadwalModal");
        loadJadwal();
        updateArrivedCount(); // Update badge
      } else {
        showError(data.message || "Gagal menyimpan jadwal");
      }
    } catch (error) {
      showError("Terjadi kesalahan");
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Simpan';
    }
  });

// ========================================
// PESAWAT TERSEDIA FUNCTIONS
// ========================================
async function loadArrived() {
  console.log("=== loadArrived() called ===");
  const tbody = document.getElementById("arrivedTableBody");
  
  // Show loading
  tbody.innerHTML = `
    <tr>
      <td colspan="6" class="px-6 py-8 text-center text-gray-500">
        <i class="fas fa-spinner fa-spin text-2xl"></i>
        <p class="mt-2">Memuat data...</p>
      </td>
    </tr>
  `;
  
  console.log("Fetching from: api/get_available_aircraft.php");
  
  try {
    const response = await fetch("api/get_available_aircraft.php");
    console.log("Response status:", response.status);
    const data = await response.json();
    console.log("Data received:", data);

    if (data.success) {
      displayArrived(data.data);
      updateArrivedCount(data.data.length);
    } else {
      showError(data.message || "Gagal memuat data pesawat tersedia");
      tbody.innerHTML = `
        <tr>
          <td colspan="6" class="px-6 py-8 text-center text-red-500">
            <i class="fas fa-exclamation-triangle text-4xl mb-2"></i>
            <p>${data.message || "Gagal memuat data"}</p>
          </td>
        </tr>
      `;
    }
  } catch (error) {
    console.error("Error:", error);
    showError("Gagal memuat data pesawat tersedia");
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="px-6 py-8 text-center text-red-500">
          <i class="fas fa-exclamation-triangle text-4xl mb-2"></i>
          <p>Terjadi kesalahan saat memuat data</p>
        </td>
      </tr>
    `;
  }
}

function displayArrived(aircraft) {
  const tbody = document.getElementById("arrivedTableBody");

  if (aircraft.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
          <i class="fas fa-calendar-check text-4xl mb-2 text-blue-500"></i>
          <p class="text-lg font-semibold">Semua pesawat operasional sudah memiliki jadwal aktif!</p>
          <p class="text-sm mt-2">Pesawat tanpa jadwal penerbangan aktif (Scheduled/Departed) akan muncul di sini</p>
        </td>
      </tr>
    `;
    return;
  }

  let html = "";
  aircraft.forEach((a) => {
    // CLASS BADGES untuk Kelas Layanan
    const kelasClass =
      {
        "Economy Class": "bg-blue-100 text-blue-800",
        "Business Class": "bg-purple-100 text-purple-800",
        "First Class": "bg-amber-100 text-amber-800",
      }[a.kelas_layanan] || "bg-gray-100 text-gray-800";

    html += `
      <tr class="border-b border-gray-200 hover:bg-green-50 transition">
        <td class="px-6 py-4">
          <span class="font-mono text-sm font-semibold">${a.nomor_registrasi}</span>
        </td>
        <td class="px-6 py-4">
          <div class="text-sm font-semibold">${a.maskapai}</div>
          <div class="text-xs text-gray-500">${a.model}</div>
        </td>
        <td class="px-6 py-4">
          <div class="text-sm font-semibold text-green-600">
            <i class="fas fa-map-marker-alt mr-1"></i>${a.lokasi_terakhir || "Tidak diketahui"}
          </div>
        </td>
        <td class="px-6 py-4 text-center">
          <div class="text-sm">Kapasitas: <span class="font-bold text-primary">${a.kapasitas}</span></div>
          <div class="text-xs mt-1">
            <span class="${kelasClass} px-2 py-1 rounded-full font-semibold">
              ${a.kelas_layanan || "Economy Class"}
            </span>
          </div>
        </td>
        <td class="px-6 py-4 text-center">
          <span class="bg-amber-100 text-amber-800 px-3 py-1 rounded-full text-xs font-semibold">
            <i class="fas fa-clock mr-1"></i>BELUM ADA JADWAL
          </span>
        </td>
        <td class="px-6 py-4 text-center">
          <button onclick="createFlightForAircraft(${a.id})" 
            class="bg-primary hover:bg-secondary text-white font-bold px-4 py-2 rounded-lg transition duration-300 text-sm shadow-lg hover:shadow-xl">
            <i class="fas fa-calendar-plus mr-2"></i>Buat Jadwal
          </button>
        </td>
      </tr>
    `;
  });

  tbody.innerHTML = html;
}

async function createFlightForAircraft(pesawatId) {
  console.log("=== Creating flight schedule for aircraft:", pesawatId, "===");

  document.getElementById("jadwalForm").reset();
  document.getElementById("jadwalId").value = "";
  document.getElementById("jadwalFromArrived").value = "1";
  document.getElementById("jadwalModalTitle").textContent =
    "Buat Jadwal Penerbangan Baru";
  document.getElementById("jadwalModal").classList.remove("hidden");

  const originSelect = document.getElementById("originAirportSelect");
  const destSelect = document.getElementById("destAirportSelect");
  const pesawatSelect = document.getElementById("pesawatSelectJadwal");

  if (originSelect)
    originSelect.innerHTML = '<option value="">Memuat...</option>';
  if (destSelect) destSelect.innerHTML = '<option value="">Memuat...</option>';
  if (pesawatSelect)
    pesawatSelect.innerHTML = '<option value="">Memuat...</option>';

  try {
    // Load airports dan pesawat
    await Promise.all([loadAirports(), loadOperationalPesawat()]);

    // Get aircraft details
    const response = await fetch(`api/get_pesawat_detail.php?id=${pesawatId}`);
    const data = await response.json();

    if (data.success) {
      const aircraft = data.data;
      const form = document.getElementById("jadwalForm");

      // Pre-select the aircraft
      form.pesawat_id.value = pesawatId;

      // Set origin to aircraft's current location if available
      if (aircraft.airport_id) {
        form.origin_airport_id.value = aircraft.airport_id;
      }

      // Set minimum date to today
      const today = new Date().toISOString().split("T")[0];
      form.tanggal.setAttribute("min", today);
      form.tanggal.value = today;

      console.log(
        "=== Flight form ready for aircraft:",
        aircraft.nomor_registrasi,
        "===",
      );
    } else {
      showError("Gagal memuat detail pesawat");
    }
  } catch (error) {
    console.error("Error in createFlightForAircraft:", error);
    showError("Gagal memuat data pesawat");
  }
}

async function updateArrivedCount(count = null) {
  if (count === null) {
    try {
      const response = await fetch("api/get_available_aircraft.php");
      const data = await response.json();
      count = data.success ? data.data.length : 0;
    } catch (error) {
      console.error("Error updating arrived count:", error);
      return;
    }
  }

  const badge = document.getElementById("arrivedBadge");
  if (badge) {
    if (count > 0) {
      badge.textContent = count;
      badge.classList.remove("hidden");
    } else {
      badge.classList.add("hidden");
    }
  }
}

// ========================================
// UTILITY FUNCTIONS
// ========================================
function closeModal(modalId) {
  document.getElementById(modalId).classList.add("hidden");
}

function formatRupiah(angka) {
  return "Rp " + parseInt(angka).toLocaleString("id-ID");
}

function formatTanggal(tanggal) {
  const date = new Date(tanggal);
  const options = { day: "numeric", month: "short", year: "numeric" };
  return date.toLocaleDateString("id-ID", options);
}

function showSuccess(message) {
  alert("✅ " + message);
}

function showError(message) {
  alert("❌ " + message);
}

// Close modal when clicking outside
window.onclick = function (event) {
  const modals = ["pesawatModal", "jadwalModal"];
  modals.forEach((modalId) => {
    const modal = document.getElementById(modalId);
    if (event.target === modal) {
      closeModal(modalId);
    }
  });
};
<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pemesanan - SkyBooking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }

        .page-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1e3a8a 100%);
            padding: 4rem 0 6rem 0;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 50%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="white" opacity="0.1"/></svg>') repeat;
            opacity: 0.5;
        }

        .search-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(30, 58, 138, 0.15);
            margin-top: -3rem;
            position: relative;
            z-index: 10;
        }

        .booking-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            overflow: hidden;
            border-left: 4px solid #3b82f6;
        }

        .booking-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(30, 58, 138, 0.2);
        }

        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-pending {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
        }

        .status-lunas {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }

        .status-batal {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
        }

        .status-expired {
            background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
            color: #1f2937;
        }

        .detail-modal {
            backdrop-filter: blur(8px);
        }

        .modal-content {
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
        }

        .info-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3b82f6;
        }

        .action-button {
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(239, 68, 68, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .search-input {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        /* Loading spinner animation */
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .btn-loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .btn-loading i {
            animation: spin 1s linear infinite;
        }

        /* Navbar blur effect saat modal terbuka */
        body.modal-open #navbar {
            filter: blur(4px);
            pointer-events: none;
        }

        body.modal-open .page-header,
        body.modal-open .container {
            filter: blur(4px);
            pointer-events: none;
        }

        /* Sembunyikan scrollbar pada modal */
        .modal-content {
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* Internet Explorer 10+ */
        }

        .modal-content::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container mx-auto px-4" style="position: relative; z-index: 10;">
            <div class="max-w-4xl">
                <h1 class="text-5xl font-bold text-white mb-4">
                    <i class="fas fa-history mr-4 mt-[60px]"></i>
                    Riwayat Pemesanan
                </h1>
                <p class="text-blue-100 text-lg">Lihat dan kelola semua riwayat pemesanan tiket penerbangan Anda</p>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 pb-12">
        <!-- Search Box -->
        <div class="search-card max-w-4xl mx-auto p-6 mb-8">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text"
                        id="searchInput"
                        placeholder="Cari berdasarkan kode booking, nama, atau email..."
                        class="search-input w-full pl-12"
                        style="padding-left: 48px;">
                </div>
                <button onclick="searchBooking()"
                    class="action-button btn-primary">
                    <i class="fas fa-search"></i>
                    Cari
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div id="loadingSpinner" class="text-center py-12 hidden">
            <div class="inline-block animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600"></div>
            <p class="mt-4 text-gray-600 font-semibold">Memuat data pemesanan...</p>
        </div>

        <!-- Results -->
        <div id="resultsContainer" class="max-w-4xl mx-auto"></div>
    </div>

    <!-- Detail Modal -->
    <div id="detailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 detail-modal z-[1100] flex items-center justify-center p-4">
        <div class="modal-content bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto relative z-[1101]">
            <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-800 text-white px-8 py-6 flex justify-between items-center z-10">
                <h3 class="text-2xl font-bold">Detail Pemesanan</h3>
                <button onclick="closeModal()" class="text-white hover:text-blue-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modalContent" class="p-8"></div>
        </div>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', function() {
            loadBookings();
        });

        async function loadBookings(search = '') {
            const loadingSpinner = document.getElementById('loadingSpinner');
            const resultsContainer = document.getElementById('resultsContainer');

            loadingSpinner.classList.remove('hidden');
            resultsContainer.innerHTML = '';

            try {
                const response = await fetch('api/get_bookings.php?search=' + encodeURIComponent(search));
                const data = await response.json();

                loadingSpinner.classList.add('hidden');

                if (data.success && data.data.length > 0) {
                    displayBookings(data.data);
                } else {
                    resultsContainer.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-ticket-alt text-5xl text-blue-400"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-800 mb-2">Tidak Ada Riwayat Pemesanan</h3>
                            <p class="text-gray-600 mb-6">Belum ada riwayat pemesanan atau data tidak ditemukan</p>
                            <button onclick="window.location.href='index.php'" class="action-button btn-primary">
                                <i class="fas fa-search"></i>
                                Cari Penerbangan
                            </button>
                        </div>
                    `;
                }
            } catch (error) {
                loadingSpinner.classList.add('hidden');
                resultsContainer.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-exclamation-triangle text-5xl text-red-400"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">Terjadi Kesalahan</h3>
                        <p class="text-gray-600">Gagal memuat data pemesanan</p>
                    </div>
                `;
            }
        }

        function searchBooking() {
            const search = document.getElementById('searchInput').value;
            loadBookings(search);
        }

        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchBooking();
            }
        });

        function displayBookings(bookings) {
            const container = document.getElementById('resultsContainer');
            let html = '';

            bookings.forEach(booking => {
                const flightDate = new Date(booking.tanggal);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const isExpired = flightDate < today && booking.status === 'pending';

                let statusClass, statusIcon, statusText;

                if (isExpired) {
                    statusClass = 'status-expired';
                    statusIcon = 'fa-ban';
                    statusText = 'KADALUARSA';
                } else {
                    const statusMap = {
                        'pending': {
                            class: 'status-pending',
                            icon: 'fa-clock',
                            text: 'PENDING'
                        },
                        'lunas': {
                            class: 'status-lunas',
                            icon: 'fa-check-circle',
                            text: 'LUNAS'
                        },
                        'batal': {
                            class: 'status-batal',
                            icon: 'fa-times-circle',
                            text: 'BATAL'
                        }
                    };
                    const status = statusMap[booking.status] || statusMap['pending'];
                    statusClass = status.class;
                    statusIcon = status.icon;
                    statusText = status.text;
                }

                html += `
                    <div class="booking-card mb-6 ${isExpired ? 'opacity-75' : ''}">
                        <div class="p-6">
                            <!-- Header -->
                            <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl flex items-center justify-center">
                                        <i class="fas fa-plane text-white text-2xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-2xl font-bold text-gray-800">${booking.kode_booking}</h3>
                                        <p class="text-sm text-gray-500">Tanggal Booking: ${booking.created_at || '-'}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="status-badge ${statusClass}">
                                        <i class="fas ${statusIcon}"></i>
                                        ${statusText}
                                    </span>
                                    ${isExpired ? '<span class="status-badge" style="background: #ef4444; color: white;"><i class="fas fa-exclamation-triangle"></i> EXPIRED</span>' : ''}
                                </div>
                            </div>

                            <!-- Flight Info -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                <div>
                                    <h5 class="text-xs font-semibold text-gray-500 mb-2 uppercase">Penerbangan</h5>
                                    <div class="info-row">
                                        <div class="info-icon">
                                            <i class="fas fa-plane-departure"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-bold text-gray-800">${booking.maskapai}</p>
                                            <p class="text-sm text-gray-600">${booking.asal} → ${booking.tujuan}</p>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h5 class="text-xs font-semibold text-gray-500 mb-2 uppercase">Tanggal</h5>
                                    <div class="info-row">
                                        <div class="info-icon">
                                            <i class="fas fa-calendar-alt"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-bold text-gray-800 ${isExpired ? 'text-red-600' : ''}">${booking.tanggal}</p>
                                            <p class="text-sm text-gray-600">${booking.jumlah_penumpang} Penumpang</p>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h5 class="text-xs font-semibold text-gray-500 mb-2 uppercase">Total Harga</h5>
                                    <div class="info-row">
                                        <div class="info-icon">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-bold text-2xl text-blue-600">${formatRupiah(booking.total_harga)}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Passenger Info -->
                            <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl p-4 mb-6">
                                <h5 class="text-xs font-semibold text-gray-600 mb-3 uppercase">Informasi Pemesan</h5>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-user text-blue-600"></i>
                                        <span class="text-sm text-gray-700">${booking.nama_pemesan}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-envelope text-blue-600"></i>
                                        <span class="text-sm text-gray-700">${booking.email}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-phone text-blue-600"></i>
                                        <span class="text-sm text-gray-700">${booking.no_hp}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-wrap gap-3 justify-end">
                                <button onclick="showDetail(${booking.id})" class="action-button btn-primary">
                                    <i class="fas fa-eye"></i>
                                    Lihat Detail
                                </button>
                                ${booking.status === 'lunas' || booking.status === 'pending' ? `
                                    <button id="invoiceBtn${booking.id}" onclick="sendInvoice(${booking.id}, '${booking.email}')" class="action-button btn-success">
                                        <i class="fas fa-envelope"></i>
                                        Kirim Invoice
                                    </button>
                                ` : ''}
                                ${isExpired || booking.status === 'batal' ? `
                                <button onclick="deleteBooking(${booking.id})" class="action-button btn-danger">
                                <i class="fas fa-trash"></i>
                                        Hapus
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        async function sendInvoice(bookingId, email) {
            const btn = document.getElementById('invoiceBtn' + bookingId);
            const originalHTML = btn.innerHTML;

            // Disable button and show loading
            btn.classList.add('btn-loading');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';

            console.log("Mengirim invoice untuk ID:", bookingId, "ke Email:", email);

            try {
                const formData = new FormData();
                formData.append('id', bookingId);

                const response = await fetch('api/send_infoice.php', {
                    method: 'POST',
                    body: formData
                });

                console.log("HTTP Response Status:", response.status);

                const responseText = await response.text();
                console.log("Raw Response dari Server:", responseText);

                // const data = await response.json();

                // Coba parsing sebagai JSON
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    console.error("Gagal parsing JSON. Kemungkinan ada error PHP/HTML di file api:", e);
                    showNotification('error', 'Format respons server tidak valid (Cek Console)');
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('btn-loading');
                    return;
                }

                console.log("Parsed JSON Data:", data);

                if (data.success) {
                    // Success notification
                    showNotification('success', data.message || 'Invoice berhasil dikirim ke ' + email);
                    btn.innerHTML = '<i class="fas fa-check-circle"></i> Terkirim';
                    setTimeout(() => {
                        btn.innerHTML = originalHTML;
                        btn.classList.remove('btn-loading');
                    }, 3000);
                } else {
                    console.warn("Server merespons success:false - Pesan:", data.message);
                    showNotification('error', data.message || 'Gagal mengirim invoice');
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('btn-loading');
                }
            } catch (error) {
                console.error("Fetch Error:", error);
                showNotification('error', 'Terjadi kesalahan saat mengirim invoice');
                btn.innerHTML = originalHTML;
                btn.classList.remove('btn-loading');
            }
        }

        function showNotification(type, message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 100px;
                right: 20px;
                padding: 16px 24px;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                z-index: 9999;
                animation: slideIn 0.3s ease-out;
                max-width: 400px;
            `;

            if (type === 'success') {
                notification.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                notification.style.color = 'white';
                notification.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-check-circle" style="font-size: 24px;"></i>
                        <div>
                            <strong style="display: block; margin-bottom: 4px;">Berhasil!</strong>
                            <span>${message}</span>
                        </div>
                    </div>
                `;
            } else {
                notification.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
                notification.style.color = 'white';
                notification.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-exclamation-circle" style="font-size: 24px;"></i>
                        <div>
                            <strong style="display: block; margin-bottom: 4px;">Gagal!</strong>
                            <span>${message}</span>
                        </div>
                    </div>
                `;
            }

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }

        async function deleteBooking(id) {
            if (!confirm('Yakin ingin menghapus pemesanan ini? Data akan dihapus permanen.')) {
                return;
            }

            try {
                const response = await fetch('api/delete_booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'id=' + id
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('success', 'Pemesanan berhasil dihapus');
                    loadBookings();
                } else {
                    showNotification('error', data.message || 'Gagal menghapus pemesanan');
                }
            } catch (error) {
                showNotification('error', 'Terjadi kesalahan');
            }
        }

        async function showDetail(id) {
            const modalContent = document.getElementById('modalContent');
            const modal = document.getElementById('detailModal');
            
            modalContent.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-4xl text-blue-600"></i></div>';
            modal.classList.remove('hidden');
            
            // Tambahkan class modal-open ke body untuk blur effect
            document.body.classList.add('modal-open');

            try {
                const response = await fetch('api/get_booking_detail.php?id=' + id);
                const data = await response.json();

                if (data.success) {
                    displayDetail(data.data);
                } else {
                    modalContent.innerHTML = '<p class="text-red-600 text-center">Gagal memuat detail</p>';
                }
            } catch (error) {
                modalContent.innerHTML = '<p class="text-red-600 text-center">Terjadi kesalahan</p>';
            }
        }

        function displayDetail(data) {
            const modalContent = document.getElementById('modalContent');

            const statusMap = {
                'pending': {
                    class: 'status-pending',
                    icon: 'fa-clock',
                    text: 'PENDING'
                },
                'lunas': {
                    class: 'status-lunas',
                    icon: 'fa-check-circle',
                    text: 'LUNAS'
                },
                'batal': {
                    class: 'status-batal',
                    icon: 'fa-times-circle',
                    text: 'BATAL'
                }
            };
            const status = statusMap[data.status] || statusMap['pending'];

            let penumpangHtml = '';
            data.penumpang.forEach((p, i) => {
                penumpangHtml += `
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-5 mb-4">
                        <h5 class="font-bold text-gray-800 mb-3 flex items-center">
                            <span class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center mr-3 text-sm">${i + 1}</span>
                            Penumpang ${i + 1}
                        </h5>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500 block mb-1">Nama Lengkap</span>
                                <span class="font-semibold text-gray-800">${p.nama_lengkap}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 block mb-1">Jenis Kelamin</span>
                                <span class="font-semibold text-gray-800">${p.jenis_kelamin}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 block mb-1">Tanggal Lahir</span>
                                <span class="font-semibold text-gray-800">${p.tanggal_lahir}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 block mb-1">NIK</span>
                                <span class="font-semibold text-gray-800">${p.nik || '-'}</span>
                            </div>
                        </div>
                    </div>
                `;
            });

            modalContent.innerHTML = `
                <div class="space-y-6">
                    <!-- Booking Code Card -->
                    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-2xl p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-sm opacity-90 mb-2">Kode Booking</p>
                                <p class="text-4xl font-bold">${data.kode_booking}</p>
                            </div>
                            <span class="status-badge ${status.class}">
                                <i class="fas ${status.icon}"></i>
                                ${status.text}
                            </span>
                        </div>
                        <p class="text-sm opacity-90">Tanggal Booking: ${data.created_at}</p>
                    </div>
                    
                    <!-- Flight Info -->
                    <div>
                        <h4 class="font-bold text-gray-800 mb-4 text-lg flex items-center">
                            <i class="fas fa-plane-departure text-blue-600 mr-3"></i>
                            Informasi Penerbangan
                        </h4>
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl p-6 space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Maskapai:</span>
                                <span class="font-bold text-gray-800">${data.maskapai}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Kode Penerbangan:</span>
                                <span class="font-bold text-gray-800">${data.kode_penerbangan}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Rute:</span>
                                <span class="font-bold text-gray-800">${data.asal} → ${data.tujuan}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tanggal:</span>
                                <span class="font-bold text-gray-800">${data.tanggal}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Waktu:</span>
                                <span class="font-bold text-gray-800">${data.jam_berangkat} - ${data.jam_tiba}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pemesan Info -->
                    <div>
                        <h4 class="font-bold text-gray-800 mb-4 text-lg flex items-center">
                            <i class="fas fa-user-circle text-blue-600 mr-3"></i>
                            Informasi Pemesan
                        </h4>
                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6 space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Nama:</span>
                                <span class="font-bold text-gray-800">${data.nama_pemesan}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Email:</span>
                                <span class="font-bold text-gray-800">${data.email}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">No. HP:</span>
                                <span class="font-bold text-gray-800">${data.no_hp}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Passengers -->
                    <div>
                        <h4 class="font-bold text-gray-800 mb-4 text-lg flex items-center">
                            <i class="fas fa-users text-blue-600 mr-3"></i>
                            Data Penumpang
                        </h4>
                        ${penumpangHtml}
                    </div>
                    
                    <!-- Total -->
                    <div class="border-t-2 border-gray-200 pt-6">
                        <div class="flex justify-between items-center">
                            <span class="text-xl font-bold text-gray-800">Total Pembayaran</span>
                            <span class="text-3xl font-bold text-blue-600">${formatRupiah(data.total_harga)}</span>
                        </div>
                    </div>
                </div>
            `;
        }

        function closeModal() {
            document.getElementById('detailModal').classList.add('hidden');
            // Hapus class modal-open dari body untuk menghilangkan blur effect
            document.body.classList.remove('modal-open');
        }

        function formatRupiah(angka) {
            return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Add animation styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>
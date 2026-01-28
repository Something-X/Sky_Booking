<?php require_once 'config.php';
$isLoggedIn = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkyBooking - Pemesanan Tiket Pesawat</title>
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
        }

        /* Animation untuk modal */
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        #chatSupportModal:not(.hidden)>div:last-child {
            animation: slideInRight 0.3s ease-out;
        }

        /* Pulse animation untuk notification dot */
        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.1);
                opacity: 0.8;
            }
        }

        .hero-bg {
            background: linear-gradient(135deg, #3b82f6 0%, #1e3a8a 100%);
            position: relative;
            overflow: hidden;
        }

        .landmark-image {
            position: absolute;
            right: 0;
            bottom: 0;
            height: 100%;
            object-fit: cover;
            opacity: 0.9;
        }

        .search-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(30, 58, 138, 0.15);
        }

        .tab-button {
            padding: 12px 24px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            transition: all 0.3s;
        }

        .tab-button.active {
            background: #1e3a8a;
            color: white;
            border-radius: 8px;
        }

        .input-field {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px;
            width: 100%;
            font-size: 15px;
            transition: all 0.3s;
        }

        .input-field:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-button {
            background: #3b82f6;
            color: white;
            padding: 14px 32px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .search-button:hover {
            background: #2563eb;
            transform: translateX(4px);
        }

        .destination-card {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            cursor: pointer;
        }

        .destination-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(30, 58, 138, 0.2);
        }

        .swap-button {
            background: white;
            border: 1px solid #e5e7eb;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .swap-button:hover {
            background: #eff6ff;
            border-color: #3b82f6;
            transform: rotate(180deg);
        }

        .swap-button:hover i {
            color: #3b82f6;
        }

        .feature-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            cursor: pointer;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(30, 58, 138, 0.15);
        }

        .icon-wrapper {
            width: 64px;
            height: 64px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .testimonial-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .destination-grid-card {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            height: 280px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .destination-grid-card:hover {
            transform: scale(1.05);
        }

        .destination-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8) 0%, transparent 100%);
            padding: 1.5rem;
            color: white;
        }

        .cta-section {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            border-radius: 24px;
            padding: 4rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .cta-pattern {
            position: absolute;
            top: 0;
            right: 0;
            width: 50%;
            height: 100%;
            opacity: 0.1;
        }

        /* Custom Select Styling */
        select.input-field {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 20px;
            padding-right: 40px;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        select.input-field:hover {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.08);
        }

        select.input-field:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
            transform: translateY(-1px);
        }

        select.input-field option {
            border-radius: 8px;
            padding: 8px;
            margin: 4px 0;
        }

        /* Custom Dropdown Styling */
        .custom-dropdown {
            position: relative;
            display: inline-block;
            width: 100%;
            max-width: 200px;
        }

        .dropdown-trigger {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px 16px;
            background: white;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            color: #333;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
        }

        .dropdown-trigger:hover {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.08);
            background: linear-gradient(135deg, #f8fbff 0%, #ffffff 100%);
        }

        .dropdown-trigger.active {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
            transform: translateY(-1px);
        }

        .dropdown-arrow {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-left: 8px;
        }

        .dropdown-trigger.active .dropdown-arrow {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: none;
        }

        .dropdown-menu.active {
            max-height: 400px;
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
            animation: slideDown 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-item {
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #f3f4f6;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%);
            color: #3b82f6;
            padding-left: 20px;
        }

        .dropdown-item.selected {
            background: linear-gradient(135deg, #dbeafe 0%, #e0f2fe 100%);
            color: #1e40af;
            font-weight: 600;
        }

        .dropdown-item.selected::before {
            content: '✓';
            font-weight: bold;
            color: #3b82f6;
            font-size: 16px;
        }

        .dropdown-item:active {
            transform: scale(0.98);
        }

        /* Modern Date Input Styling */
        input[type="date"].input-field {
            position: relative;
            background: white;
            cursor: pointer;
            color: #333;
            font-weight: 500;
        }

        input[type="date"].input-field::-webkit-calendar-picker-indicator {
            cursor: pointer;
            border-radius: 6px;
            margin-right: 4px;
            opacity: 0.8;
            filter: invert(0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        input[type="date"].input-field:hover::-webkit-calendar-picker-indicator {
            opacity: 1;
            filter: invert(0);
        }

        input[type="date"].input-field:focus::-webkit-calendar-picker-indicator {
            filter: invert(0.5) hue-rotate(200deg);
        }

        /* Date Input Container with Badge */
        .date-input-wrapper {
            position: relative;
        }

        .date-input-wrapper.has-value::after {
            content: '';
            position: absolute;
            top: -8px;
            right: 20px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
            z-index: 10;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.7;
                transform: scale(1.2);
            }
        }

        .date-input-wrapper.departure::before {
            content: '📅';
            position: absolute;
            left: 12px;
            top: 14px;
            font-size: 16px;
            z-index: 5;
        }

        .date-input-wrapper.departure .input-field {
            padding-left: 40px;
            border-color: #fbbf24;
            box-shadow: inset 0 0 0 0.5px #fbbf24;
        }

        .date-input-wrapper.return::before {
            content: '🔄';
            position: absolute;
            left: 12px;
            top: 14px;
            font-size: 16px;
            z-index: 5;
        }

        .date-input-wrapper.return .input-field {
            padding-left: 40px;
            border-color: #8b5cf6;
            box-shadow: inset 0 0 0 0.5px #8b5cf6;
        }

        .date-input-wrapper .input-field:focus {
            padding-left: 40px;
        }

        /* Date Label Enhancement */
        .date-label-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .date-label-badge.departure {
            color: #d97706;
        }

        .date-label-badge.departure::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #fbbf24;
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        .date-label-badge.return {
            color: #7c3aed;
        }

        .date-label-badge.return::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #8b5cf6;
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Selected Date Indicator */
        .date-selected-indicator {
            position: absolute;
            bottom: -20px;
            left: 0;
            font-size: 11px;
            color: #3b82f6;
            font-weight: 600;
            display: none;
            animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .date-selected-indicator.show {
            display: block;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== TRAVELOKA STYLE FLATPICKR CALENDAR ===== */
        .flatpickr-calendar {
            background: white;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
            border-radius: 16px;
            border: none;
            animation: calendarSlideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            padding: 24px 20px;
            width: 100%;
            max-width: 820px;
        }

        @keyframes calendarSlideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Dual Calendar Layout */
        .flatpickr-calendar.hasTime .flatpickr-innerContainer {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .flatpickr-innerContainer {
            display: contents;
        }

        .flatpickr-rContainer {
            display: contents;
        }

        /* Month Navigation */
        .flatpickr-months {
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 0 20px 0;
            margin-bottom: 20px;
            grid-column: 1 / -1;
        }

        .flatpickr-month {
            flex: 1;
            text-align: center;
            background: transparent;
            border: none;
            padding: 0;
            margin: 0;
        }

        .flatpickr-current-month {
            font-size: 18px;
            font-weight: 700;
            color: #1e3a8a;
            letter-spacing: -0.3px;
        }

        .flatpickr-current-month .cur-month {
            font-weight: 700;
        }

        /* Previous/Next Buttons */
        .flatpickr-prev-month,
        .flatpickr-next-month {
            color: #3b82f6;
            fill: #3b82f6;
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: absolute;
            top: 0;
        }

        .flatpickr-prev-month {
            left: -50px;
        }

        .flatpickr-next-month {
            right: -50px;
        }

        .flatpickr-prev-month:hover,
        .flatpickr-next-month:hover {
            background: #eff6ff;
            color: #1e40af;
            fill: #1e40af;
            transform: scale(1.1);
        }

        /* Weekdays */
        .flatpickr-weekdays {
            background: transparent;
            border: none;
            padding: 0 0 12px 0;
            margin: 0 0 12px 0;
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0;
        }

        .flatpickr-weekday {
            background: transparent;
            color: #667085;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 0;
            width: auto;
            flex-basis: auto;
            margin: 0;
            text-align: center;
        }

        /* Days Container */
        .dayContainer {
            background: transparent;
            padding: 0;
            margin: 0;
            width: 100%;
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
        }

        /* Individual Days */
        .flatpickr-day {
            background: transparent;
            color: #344054;
            border: none;
            padding: 0;
            margin: 0;
            width: 100%;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 14px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            max-width: none;
            flex-basis: auto;
            position: relative;
        }

        .flatpickr-day:hover:not(.disabled):not(.nextMonthDay):not(.prevMonthDay) {
            background: #f0f9ff;
            color: #3b82f6;
            transform: scale(1.08);
        }

        /* Today */
        .flatpickr-day.today {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3);
            border-radius: 6px;
        }

        .flatpickr-day.today:hover {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            transform: scale(1.1);
        }

        /* Selected Date (Start) */
        .flatpickr-day.selected {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            border-radius: 6px;
        }

        .flatpickr-day.selected:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: scale(1.1);
        }

        /* Range Between Dates */
        .flatpickr-day.inRange {
            background: linear-gradient(90deg, #dbeafe 0%, #dbeafe 100%);
            color: #1e40af;
            box-shadow: none;
            border-radius: 0;
            font-weight: 500;
        }

        /* Start of Range */
        .flatpickr-day.startRange {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border-radius: 6px 0 0 6px;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            font-weight: 700;
        }

        /* End of Range */
        .flatpickr-day.endRange {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border-radius: 0 6px 6px 0;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            font-weight: 700;
        }

        /* Disabled & Out of Month Days */
        .flatpickr-day.disabled,
        .flatpickr-day.prevMonthDay,
        .flatpickr-day.nextMonthDay {
            color: #d1d5db;
            background: transparent;
            cursor: not-allowed;
            opacity: 0.5;
        }

        /* Time Section */
        .flatpickr-time {
            border: none;
            padding: 0;
            margin: 0;
            text-align: center;
            grid-column: 1 / -1;
            margin-top: 20px;
        }

        .flatpickr-time input {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 14px;
            font-weight: 500;
            color: #333;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .flatpickr-time input:focus {
            outline: none;
            background: white;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .flatpickr-am-pm {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            color: #1e3a8a;
            font-weight: 600;
            padding: 8px 12px;
            font-size: 14px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .flatpickr-am-pm:hover {
            background: white;
            border-color: #3b82f6;
            color: #3b82f6;
        }
    </style>
</head>

<body>
    <!-- Include Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section with Full Background Image -->
    <div class="relative" style="min-height: 650px; overflow: hidden;">
        <!-- Full Background Image -->
        <div class="absolute inset-0">
            <img src="uploads/boro2.png"
                alt="Borobudur Temple"
                class="w-full h-full object-cover"
                style="filter: brightness(0.99);">
        </div>

        <!-- Gradient Overlay -->
        <div class="absolute inset-0 bg-gradient-to-r from-black/40 via-black/20 to-transparent"></div>

        <!-- Content -->
        <div class="container mt-[60px] mx-auto px-4 py-16 md:py-24" style="position: relative; z-index: 10;">
            <div class="max-w-2xl">
                <h1 class="text-5xl md:text-6xl font-bold text-white mb-6" style="line-height: 1.1; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">
                    Hey Buddy! where are you
                    <br>
                    <span style="font-weight: 800;">Flying</span> to?
                </h1>
                <button class="bg-white text-gray-800 px-6 py-3 rounded-lg font-medium hover:bg-gray-50 transition inline-flex items-center gap-2 shadow-lg">
                    Explore Now
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Search Card -->
    <div class="container mx-auto px-4 -mt-[140px]" style="position: relative; z-index: 20; margin-bottom: 60px;">
        <div class="search-card max-w-6xl mx-auto p-6">

            <form id="searchForm">
                <!-- Trip Type and Class -->
                <div class="flex gap-4 mb-6 flex-wrap">
                    <!-- Passengers Dropdown -->
                    <div class="custom-dropdown">
                        <div class="dropdown-trigger" id="passengersTrigger">
                            <span id="passengersValue">02 Passengers</span>
                            <div class="dropdown-arrow">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                        <div class="dropdown-menu" id="passengersMenu">
                            <div class="dropdown-item" data-value="1">01 Passenger</div>
                            <div class="dropdown-item selected" data-value="2">02 Passengers</div>
                            <div class="dropdown-item" data-value="3">03 Passengers</div>
                            <div class="dropdown-item" data-value="4">04 Passengers</div>
                            <div class="dropdown-item" data-value="5">05 Passengers</div>
                            <div class="dropdown-item" data-value="6">06 Passengers</div>
                            <div class="dropdown-item" data-value="7">07 Passengers</div>
                        </div>
                        <input type="hidden" id="jumlahInput" name="jumlah" value="2">
                    </div>

                    <!-- Class Dropdown -->
                    <div class="custom-dropdown">
                        <div class="dropdown-trigger" id="classTrigger">
                            <span id="classValue">Economy Class</span>
                            <div class="dropdown-arrow">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                        <div class="dropdown-menu" id="classMenu">
                            <div class="dropdown-item selected" data-value="Economy">Economy Class</div>
                            <div class="dropdown-item" data-value="Business">Business Class</div>
                            <div class="dropdown-item" data-value="First">First Class</div>
                        </div>
                        <input type="hidden" id="kelasInput" name="kelas" value="Economy">
                    </div>
                </div>

                <!-- Main Search Fields -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <!-- From -->
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium mb-2" style="color: #1e3a8a;">FROM</label>
                        <input type="text"
                            id="asal"
                            name="asal"
                            placeholder="From"
                            class="input-field font-semibold text-lg"
                            required>
                        <div class="text-xs text-gray-500 mt-1">Jakarta, Surabaya, Malang</div>
                    </div>

                    <!-- Swap Button -->
                    <div class="md:col-span-1 flex justify-center mb-[30px]">
                        <button type="button" class="swap-button">
                            <i class="fas fa-exchange-alt text-gray-400"></i>
                        </button>
                    </div>

                    <!-- To -->
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium mb-2" style="color: #1e3a8a;">TO</label>
                        <input type="text"
                            id="tujuan"
                            name="tujuan"
                            placeholder="To"
                            class="input-field font-semibold text-lg"
                            required>
                        <div class="text-xs text-gray-500 mt-1">Makassar, Denpasar, Bali</div>
                    </div>


                    <!-- Departure Date -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-2" style="color: #1e3a8a;">DEPARTURE</label>
                        <input type="date"
                            id="tanggal"
                            name="tanggal"
                            class="input-field"
                            required>
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>Prev</span>
                            <span>Next</span>
                        </div>
                    </div>

                    <!-- Return Date -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-2" style="color: #1e3a8a;">
                            <input type="checkbox" id="roundTripCheckbox" class="mr-2 w-4 h-4 cursor-pointer">
                            RETURN
                        </label>
                        <input type="date"
                            id="tanggal_kembali"
                            name="tanggal_kembali"
                            class="input-field"
                            disabled>
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>Prev</span>
                            <span>Next</span>
                        </div>
                    </div>

                    <!-- Search Button -->
                    <div class="md:col-span-1">
                        <button type="submit" class="search-button w-full justify-center">
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Popular Destinations -->
    <div class="container mx-auto px-4 py-12">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold" style="color: #1e3a8a;">Popular Destination</h2>
            <a href="#" class="font-medium border-b-2 transition" style="color: #3b82f6; border-color: #3b82f6;">
                Explore All
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Destination Card 1 -->
            <div class="destination-card bg-white">
                <img src="https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=400&h=300&fit=crop"
                    alt="Paris"
                    class="w-full h-48 object-cover">
                <div class="p-4">
                    <h3 class="font-bold text-lg mb-1" style="color: #1e3a8a;">Paris</h3>
                    <p class="text-gray-600 text-sm">France</p>
                    <p class="font-semibold mt-2" style="color: #3b82f6;">From Rp 8.500.000</p>
                </div>
            </div>

            <!-- Destination Card 2 -->
            <div class="destination-card bg-white">
                <img src="https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=400&h=300&fit=crop"
                    alt="London"
                    class="w-full h-48 object-cover">
                <div class="p-4">
                    <h3 class="font-bold text-lg mb-1" style="color: #1e3a8a;">London</h3>
                    <p class="text-gray-600 text-sm">United Kingdom</p>
                    <p class="font-semibold mt-2" style="color: #3b82f6;">From Rp 9.200.000</p>
                </div>
            </div>

            <!-- Destination Card 3 -->
            <div class="destination-card bg-white">
                <img src="https://images.unsplash.com/photo-1549144511-f099e773c147?w=400&h=300&fit=crop"
                    alt="Tokyo"
                    class="w-full h-48 object-cover">
                <div class="p-4">
                    <h3 class="font-bold text-lg mb-1" style="color: #1e3a8a;">Tokyo</h3>
                    <p class="text-gray-600 text-sm">Japan</p>
                    <p class="font-semibold mt-2" style="color: #3b82f6;">From Rp 6.800.000</p>
                </div>
            </div>

            <!-- Destination Card 4 -->
            <div class="destination-card bg-white">
                <img src="https://images.unsplash.com/photo-1523906834658-6e24ef2386f9?w=400&h=300&fit=crop"
                    alt="Dubai"
                    class="w-full h-48 object-cover">
                <div class="p-4">
                    <h3 class="font-bold text-lg mb-1" style="color: #1e3a8a;">Dubai</h3>
                    <p class="text-gray-600 text-sm">United Arab Emirates</p>
                    <p class="font-semibold mt-2" style="color: #3b82f6;">From Rp 5.500.000</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Blockchain Features Section -->
    <div class="container mx-auto px-4 py-16">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold mb-4" style="color: #1e3a8a;">Blockchain Simplifies Your Flight</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Save time, Make payments with blockchain, Stay happy and Experience world-class convenience</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="feature-card text-center">
                <div class="icon-wrapper mx-auto">
                    <i class="fas fa-clock text-3xl text-white"></i>
                </div>
                <h4 class="font-bold text-xl mb-3" style="color: #1e3a8a;">Choose Your Flight</h4>
                <p class="text-gray-600 text-sm">Browse through thousands of flights and choose the one that suits you best</p>
            </div>

            <div class="feature-card text-center">
                <div class="icon-wrapper mx-auto">
                    <i class="fas fa-shield-alt text-3xl text-white"></i>
                </div>
                <h4 class="font-bold text-xl mb-3" style="color: #1e3a8a;">Secure Payment in Crypto</h4>
                <p class="text-gray-600 text-sm">Pay securely using cryptocurrency with blockchain technology</p>
            </div>

            <div class="feature-card text-center">
                <div class="icon-wrapper mx-auto">
                    <i class="fas fa-download text-3xl text-white"></i>
                </div>
                <h4 class="font-bold text-xl mb-3" style="color: #1e3a8a;">Get Instant E-Ticket</h4>
                <p class="text-gray-600 text-sm">Receive your e-ticket instantly after successful payment</p>
            </div>

            <div class="feature-card text-center">
                <div class="icon-wrapper mx-auto">
                    <i class="fas fa-plane-departure text-3xl text-white"></i>
                </div>
                <h4 class="font-bold text-xl mb-3" style="color: #1e3a8a;">Easy Boarding</h4>
                <p class="text-gray-600 text-sm">Board your flight hassle-free with digital ticket verification</p>
            </div>
        </div>
    </div>

    <!-- The Smarter Way Section -->
    <div class="container mx-auto px-4 py-16">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold mb-4" style="color: #1e3a8a;">The Smarter Way to Fly with Crypto</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Experience the future of travel with blockchain-powered booking</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
            <div class="feature-card">
                <div class="icon-wrapper">
                    <i class="fas fa-lock text-3xl text-white"></i>
                </div>
                <h4 class="font-bold text-xl mb-3" style="color: #1e3a8a;">Secure Transactions</h4>
                <p class="text-gray-600 mb-4">Your transactions are protected by blockchain technology ensuring maximum security</p>
                <a href="#" class="text-blue-600 font-medium hover:underline">Read More →</a>
            </div>

            <div class="feature-card">
                <div class="icon-wrapper">
                    <i class="fas fa-certificate text-3xl text-white"></i>
                </div>
                <h4 class="font-bold text-xl mb-3" style="color: #1e3a8a;">Instant Confirmation</h4>
                <p class="text-gray-600 mb-4">Get instant booking confirmation with smart contracts on the blockchain</p>
                <a href="#" class="text-blue-600 font-medium hover:underline">Read More →</a>
            </div>

            <div class="feature-card">
                <div class="icon-wrapper">
                    <i class="fas fa-globe text-3xl text-white"></i>
                </div>
                <h4 class="font-bold text-xl mb-3" style="color: #1e3a8a;">Global Access</h4>
                <p class="text-gray-600 mb-4">Book flights anywhere in the world with cryptocurrency payments</p>
                <a href="#" class="text-blue-600 font-medium hover:underline">Read More →</a>
            </div>

            <div class="feature-card">
                <div class="icon-wrapper">
                    <i class="fas fa-wallet text-3xl text-white"></i>
                </div>
                <h4 class="font-bold text-xl mb-3" style="color: #1e3a8a;">Multi-Wallet Support</h4>
                <p class="text-gray-600 mb-4">Connect your favorite crypto wallet for seamless payments</p>
                <a href="#" class="text-blue-600 font-medium hover:underline">Read More →</a>
            </div>

            <div class="feature-card">
                <div class="icon-wrapper">
                    <i class="fas fa-gift text-3xl text-white"></i>
                </div>
                <h4 class="font-bold text-xl mb-3" style="color: #1e3a8a;">Exclusive Crypto Cashback</h4>
                <p class="text-gray-600 mb-4">Earn crypto rewards on every booking you make</p>
                <a href="#" class="text-blue-600 font-medium hover:underline">Read More →</a>
            </div>

            <div class="feature-card">
                <div class="icon-wrapper">
                    <i class="fas fa-headset text-3xl text-white"></i>
                </div>
                <h4 class="font-bold text-xl mb-3" style="color: #1e3a8a;">24/7 Support</h4>
                <p class="text-gray-600 mb-4">Round-the-clock customer support for all your booking needs</p>
                <a href="#" class="text-blue-600 font-medium hover:underline">Read More →</a>
            </div>
        </div>
    </div>

    <!-- Testimonials Section -->
    <div class="py-16" style="background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%);">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold mb-4" style="color: #1e3a8a;">What Our Clients Say?</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-5xl mx-auto">
                <div class="testimonial-card">
                    <div class="flex items-center mb-4">
                        <div class="flex gap-1">
                            <i class="fas fa-star text-yellow-400"></i>
                            <i class="fas fa-star text-yellow-400"></i>
                            <i class="fas fa-star text-yellow-400"></i>
                            <i class="fas fa-star text-yellow-400"></i>
                            <i class="fas fa-star text-yellow-400"></i>
                        </div>
                    </div>
                    <p class="text-gray-700 mb-4">"Amazing flight with crypto payment! Very easy and quick process. I never thought booking flights could be this simple. The blockchain technology makes everything transparent and secure."</p>
                    <div class="flex items-center">
                        <img src="https://i.pravatar.cc/50?img=1" alt="User" class="w-12 h-12 rounded-full mr-3">
                        <div>
                            <h5 class="font-bold" style="color: #1e3a8a;">John Doe</h5>
                            <p class="text-sm text-gray-500">CEO, TechCorp</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <div class="flex items-center mb-4">
                        <div class="flex gap-1">
                            <i class="fas fa-star text-yellow-400"></i>
                            <i class="fas fa-star text-yellow-400"></i>
                            <i class="fas fa-star text-yellow-400"></i>
                            <i class="fas fa-star text-yellow-400"></i>
                            <i class="fas fa-star text-yellow-400"></i>
                        </div>
                    </div>
                    <p class="text-gray-700 mb-4">"I love how secure and fast the payment process is. The customer support is also very helpful and responsive. Highly recommended for crypto enthusiasts who love to travel!"</p>
                    <div class="flex items-center">
                        <img src="https://i.pravatar.cc/50?img=5" alt="User" class="w-12 h-12 rounded-full mr-3">
                        <div>
                            <h5 class="font-bold" style="color: #1e3a8a;">Jane Smith</h5>
                            <p class="text-sm text-gray-500">Entrepreneur</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-center gap-2 mt-8">
                <button class="w-10 h-10 rounded-full bg-white flex items-center justify-center hover:bg-blue-600 hover:text-white transition">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 transition">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- CTA Section with Images -->
    <div class="container mx-auto px-4 py-16">
        <div class="cta-section text-white">
            <div class="relative z-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                    <div>
                        <h2 class="text-4xl font-bold mb-4">Fly Smarter. Pay Smarter.<br>Travel with Crypto.</h2>
                        <p class="text-blue-100 mb-6">Experience the future of travel booking with cryptocurrency payments</p>
                        <div class="flex gap-4">
                            <button class="bg-white text-blue-900 px-6 py-3 rounded-lg font-semibold hover:bg-blue-50 transition">Learn More</button>
                            <button class="border-2 border-white px-6 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-900 transition">Book Now</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <img src="https://i.pravatar.cc/150?img=10" alt="Traveler" class="rounded-full w-32 h-32 object-cover mx-auto border-4 border-white shadow-lg">
                        <img src="https://i.pravatar.cc/150?img=20" alt="Traveler" class="rounded-full w-32 h-32 object-cover mx-auto border-4 border-white shadow-lg mt-8">
                        <img src="https://i.pravatar.cc/150?img=30" alt="Traveler" class="rounded-full w-32 h-32 object-cover mx-auto border-4 border-white shadow-lg">
                        <img src="https://images.unsplash.com/photo-1488085061387-422e29b40080?w=150&h=150&fit=crop" alt="Destination" class="rounded-full w-32 h-32 object-cover mx-auto border-4 border-white shadow-lg mt-8">
                    </div>
                </div>
            </div>
            <div class="cta-pattern">
                <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                            <circle cx="20" cy="20" r="2" fill="white" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#grid)" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Quick Links Footer Section -->
    <div class="bg-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h4 class="font-bold text-xl mb-4" style="color: #1e3a8a;">FlyCrypto</h4>
                    <p class="text-gray-600 mb-4">Booking flights made easier than ever with blockchain technology</p>
                    <div class="flex gap-3">
                        <a href="#" class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center hover:bg-blue-600 hover:text-white transition">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center hover:bg-blue-600 hover:text-white transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center hover:bg-blue-600 hover:text-white transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center hover:bg-blue-600 hover:text-white transition">
                            <i class="fab fa-linkedin"></i>
                        </a>
                    </div>
                </div>

                <div>
                    <h4 class="font-bold text-lg mb-4" style="color: #1e3a8a;">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-600 hover:text-blue-600 transition">Home</a></li>
                        <li><a href="#" class="text-gray-600 hover:text-blue-600 transition">About</a></li>
                        <li><a href="#" class="text-gray-600 hover:text-blue-600 transition">Offers</a></li>
                        <li><a href="#" class="text-gray-600 hover:text-blue-600 transition">Privacy Policy</a></li>
                        <li><a href="#" class="text-gray-600 hover:text-blue-600 transition">Terms & Conditions</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-lg mb-4" style="color: #1e3a8a;">Destinations</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-600 hover:text-blue-600 transition">USA → Canada</a></li>
                        <li><a href="#" class="text-gray-600 hover:text-blue-600 transition">Europe → Asia</a></li>
                        <li><a href="#" class="text-gray-600 hover:text-blue-600 transition">Dubai → London</a></li>
                        <li><a href="#" class="text-gray-600 hover:text-blue-600 transition">Sydney → Singapore</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-lg mb-4" style="color: #1e3a8a;">Support</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-600 hover:text-blue-600 transition">Help Center</a></li>
                        <li><a href="#" class="text-gray-600 hover:text-blue-600 transition">Contact Us</a></li>
                        <li><a href="#" class="text-gray-600 hover:text-blue-600 transition">FAQs</a></li>
                        <li class="text-gray-600">Phone: +1-203-123-4567</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Support Button (Floating) -->
    <div id="chatSupportBtn" class="fixed bottom-6 right-6 z-[999] cursor-pointer group">
        <div class="relative">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-blue-700 rounded-full flex items-center justify-center shadow-2xl hover:shadow-blue-500/50 transition-all duration-300 hover:scale-110">
                <i class="fas fa-headset text-white text-2xl"></i>
            </div>
            <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full animate-pulse"></span>
            <!-- Tooltip -->
            <div class="absolute bottom-full right-0 mb-3 bg-gray-900 text-white text-sm px-4 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none">
                Butuh Bantuan?
                <div class="absolute top-full right-4 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900"></div>
            </div>
        </div>
    </div>

    <!-- Chat Support Modal -->
    <div id="chatSupportModal" class="fixed inset-0 z-[1000] hidden">
        <!-- Backdrop blur -->
        <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" onclick="closeChatSupport()"></div>

        <!-- Modal Container -->
        <div class="absolute bottom-0 right-0 m-6 w-full max-w-md">
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-300" style="max-height: 85vh;">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6 relative">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center">
                                <i class="fas fa-headset text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-white font-bold text-lg">Customer Support</h3>
                                <p class="text-blue-100 text-sm">Kami siap membantu Anda</p>
                            </div>
                        </div>
                        <button onclick="closeChatSupport()" class="text-white/80 hover:text-white transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <!-- Wave decoration -->
                    <div class="absolute bottom-0 left-0 right-0 h-4 bg-white" style="clip-path: polygon(0 50%, 10% 0, 20% 50%, 30% 0, 40% 50%, 50% 0, 60% 50%, 70% 0, 80% 50%, 90% 0, 100% 50%, 100% 100%, 0 100%);"></div>
                </div>

                <!-- Content -->
                <div class="p-6" style="max-height: calc(85vh - 140px); overflow-y: auto;">
                    <div id="chatWelcome">
                        <div class="text-center mb-6">
                            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-comments text-blue-600 text-3xl"></i>
                            </div>
                            <h4 class="text-xl font-bold text-gray-800 mb-2">Halo! Ada yang bisa kami bantu?</h4>
                            <p class="text-gray-600 text-sm">Pilih kategori keluhan Anda di bawah ini</p>
                        </div>

                        <!-- Quick Options -->
                        <div class="space-y-3 mb-6">
                            <button onclick="selectCategory('Pemesanan')" class="w-full p-4 bg-gradient-to-r from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 rounded-xl text-left transition-all duration-300 group border-2 border-transparent hover:border-blue-300">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                        <i class="fas fa-ticket-alt text-white text-lg"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h5 class="font-bold text-gray-800 mb-1">Masalah Pemesanan</h5>
                                        <p class="text-sm text-gray-600">Kesulitan memesan tiket atau booking</p>
                                    </div>
                                    <i class="fas fa-chevron-right text-blue-600 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                </div>
                            </button>

                            <button onclick="selectCategory('Pembayaran')" class="w-full p-4 bg-gradient-to-r from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 rounded-xl text-left transition-all duration-300 group border-2 border-transparent hover:border-green-300">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                        <i class="fas fa-credit-card text-white text-lg"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h5 class="font-bold text-gray-800 mb-1">Masalah Pembayaran</h5>
                                        <p class="text-sm text-gray-600">Kendala dalam proses pembayaran</p>
                                    </div>
                                    <i class="fas fa-chevron-right text-green-600 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                </div>
                            </button>

                            <button onclick="selectCategory('Penerbangan')" class="w-full p-4 bg-gradient-to-r from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 rounded-xl text-left transition-all duration-300 group border-2 border-transparent hover:border-purple-300">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                        <i class="fas fa-plane text-white text-lg"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h5 class="font-bold text-gray-800 mb-1">Info Penerbangan</h5>
                                        <p class="text-sm text-gray-600">Pertanyaan seputar jadwal & rute</p>
                                    </div>
                                    <i class="fas fa-chevron-right text-purple-600 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                </div>
                            </button>

                            <button onclick="selectCategory('Akun')" class="w-full p-4 bg-gradient-to-r from-orange-50 to-orange-100 hover:from-orange-100 hover:to-orange-200 rounded-xl text-left transition-all duration-300 group border-2 border-transparent hover:border-orange-300">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                        <i class="fas fa-user-circle text-white text-lg"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h5 class="font-bold text-gray-800 mb-1">Masalah Akun</h5>
                                        <p class="text-sm text-gray-600">Login, password, atau profil</p>
                                    </div>
                                    <i class="fas fa-chevron-right text-orange-600 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                </div>
                            </button>

                            <button onclick="selectCategory('Lainnya')" class="w-full p-4 bg-gradient-to-r from-gray-50 to-gray-100 hover:from-gray-100 hover:to-gray-200 rounded-xl text-left transition-all duration-300 group border-2 border-transparent hover:border-gray-300">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-gray-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                        <i class="fas fa-question-circle text-white text-lg"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h5 class="font-bold text-gray-800 mb-1">Lainnya</h5>
                                        <p class="text-sm text-gray-600">Pertanyaan atau keluhan lainnya</p>
                                    </div>
                                    <i class="fas fa-chevron-right text-gray-600 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Form -->
                    <div id="chatForm" class="hidden">
                        <div class="mb-4">
                            <button onclick="backToCategories()" class="flex items-center gap-2 text-blue-600 hover:text-blue-700 font-medium mb-4">
                                <i class="fas fa-arrow-left"></i>
                                Kembali
                            </button>
                        </div>

                        <form id="supportForm" class="space-y-4">
                            <input type="hidden" id="kategoriInput" name="kategori">

                            <div id="guestInfo" class="space-y-4 <?= isLoggedIn() ? 'hidden' : '' ?>">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Anda</label>
                                    <input type="text" name="nama" <?= !isLoggedIn() ? 'required' : '' ?> class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                                    <input type="email" name="email" <?= !isLoggedIn() ? 'required' : '' ?> class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Kategori</label>
                                <input type="text" id="kategoriDisplay" readonly class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Subjek</label>
                                <input type="text" name="subjek" required placeholder="Jelaskan masalah Anda secara singkat" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Pesan</label>
                                <textarea name="pesan" required rows="4" placeholder="Jelaskan detail masalah Anda..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none"></textarea>
                            </div>

                            <button type="submit" id="submitBtn" class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold py-4 rounded-lg transition-all duration-300 flex items-center justify-center gap-2">
                                <i class="fas fa-paper-plane"></i>
                                Kirim Keluhan
                            </button>
                        </form>
                    </div>

                    <!-- Success Message -->
                    <div id="chatSuccess" class="hidden text-center">
                        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-check-circle text-green-600 text-4xl"></i>
                        </div>
                        <h4 class="text-xl font-bold text-gray-800 mb-2">Keluhan Terkirim!</h4>
                        <p class="text-gray-600 mb-6">Tim kami akan segera menghubungi Anda melalui email dalam waktu 1x24 jam.</p>
                        <button onclick="resetChatSupport()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition-all duration-300">
                            Kirim Keluhan Lain
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Custom Dropdown Functionality
        function initCustomDropdown(triggerId, menuId, inputId) {
            const trigger = document.getElementById(triggerId);
            const menu = document.getElementById(menuId);
            const input = document.getElementById(inputId);
            const items = menu.querySelectorAll('.dropdown-item');
            const valueDisplay = trigger.querySelector('span');

            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                const isActive = menu.classList.contains('active');

                // Close all other dropdowns
                document.querySelectorAll('.dropdown-menu.active').forEach(m => {
                    if (m !== menu) m.classList.remove('active');
                });
                document.querySelectorAll('.dropdown-trigger.active').forEach(t => {
                    if (t !== trigger) t.classList.remove('active');
                });

                // Toggle current dropdown
                if (isActive) {
                    menu.classList.remove('active');
                    trigger.classList.remove('active');
                } else {
                    menu.classList.add('active');
                    trigger.classList.add('active');
                }
            });

            items.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const value = this.getAttribute('data-value');
                    const text = this.textContent;

                    // Update hidden input
                    input.value = value;

                    // Update display text
                    valueDisplay.textContent = text;

                    // Update selected state
                    items.forEach(i => i.classList.remove('selected'));
                    this.classList.add('selected');

                    // Close dropdown with animation
                    menu.classList.remove('active');
                    trigger.classList.remove('active');
                });
            });
        }

        // Initialize dropdowns
        initCustomDropdown('passengersTrigger', 'passengersMenu', 'jumlahInput');
        initCustomDropdown('classTrigger', 'classMenu', 'kelasInput');

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.custom-dropdown')) {
                document.querySelectorAll('.dropdown-menu.active').forEach(menu => {
                    menu.classList.remove('active');
                });
                document.querySelectorAll('.dropdown-trigger.active').forEach(trigger => {
                    trigger.classList.remove('active');
                });
            }
        });

        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('tanggal').min = today;
        document.getElementById('tanggal').value = '';

        // Return date checkbox logic
        const roundTripCheckbox = document.getElementById('roundTripCheckbox');
        const tanggalKembali = document.getElementById('tanggal_kembali');
        const tanggalAwal = document.getElementById('tanggal');

        roundTripCheckbox.addEventListener('change', function() {
            if (this.checked) {
                tanggalKembali.disabled = false;
                tanggalKembali.min = tanggalAwal.value || today;
                tanggalKembali.focus();
                tanggalKembali.classList.add('border-blue-500');
            } else {
                tanggalKembali.disabled = true;
                tanggalKembali.value = '';
                tanggalKembali.classList.remove('border-blue-500');
            }
        });

        tanggalAwal.addEventListener('change', function() {
            if (roundTripCheckbox.checked && this.value) {
                tanggalKembali.min = this.value;
            }
        });

        // Swap button functionality
        document.querySelector('.swap-button').addEventListener('click', function() {
            const asal = document.getElementById('asal');
            const tujuan = document.getElementById('tujuan');
            const temp = asal.value;
            asal.value = tujuan.value;
            tujuan.value = temp;
        });

        // Search Form Handler
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const isLoggedIn = <?= json_encode($isLoggedIn) ?>;
            const formData = new FormData(this);
            const params = new URLSearchParams(formData);

            if (!roundTripCheckbox.checked || !document.getElementById('tanggal_kembali').value) {
                params.delete('tanggal_kembali');
            }

            const redirectUrl = 'search_results.php?' + params.toString();

            if (!isLoggedIn) {
                window.location.href = 'login.php?redirect=' + encodeURIComponent(redirectUrl);
            } else {
                window.location.href = redirectUrl;
            }
        });

        // ========== CHAT SUPPORT FUNCTIONS (COMPLETE FIX) ==========

        function openChatSupport() {
            console.log('✅ Opening chat support modal');
            document.getElementById('chatSupportModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeChatSupport() {
            console.log('✅ Closing chat support modal');
            document.getElementById('chatSupportModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function selectCategory(kategori) {
            console.log('✅ Category selected:', kategori);

            // Set hidden input dan display
            document.getElementById('kategoriInput').value = kategori;
            document.getElementById('kategoriDisplay').value = kategori;

            // Verify
            console.log('   kategoriInput value:', document.getElementById('kategoriInput').value);
            console.log('   kategoriDisplay value:', document.getElementById('kategoriDisplay').value);

            // Hide welcome, show form
            document.getElementById('chatWelcome').classList.add('hidden');
            document.getElementById('chatForm').classList.remove('hidden');
        }

        function backToCategories() {
            console.log('✅ Back to categories');
            document.getElementById('chatWelcome').classList.remove('hidden');
            document.getElementById('chatForm').classList.add('hidden');
            document.getElementById('supportForm').reset();
        }

        function resetChatSupport() {
            console.log('✅ Resetting chat support');
            document.getElementById('chatSuccess').classList.add('hidden');
            document.getElementById('chatWelcome').classList.remove('hidden');
            document.getElementById('supportForm').reset();
        }

        // ========== EVENT LISTENERS ==========

        // Chat support button
        document.getElementById('chatSupportBtn').addEventListener('click', function() {
            console.log('🖱️ Chat support button clicked');
            openChatSupport();
        });

        // ========== FORM SUBMIT HANDLER (FIXED VERSION) ==========
        // ========== FORM SUBMIT HANDLER (FINAL FIX - SKIP HIDDEN FIELDS) ==========
        document.getElementById('supportForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            console.log('===========================================');
            console.log('📋 FORM SUBMIT STARTED');
            console.log('===========================================');

            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;

            // Disable button
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...';
            submitBtn.disabled = true;

            try {
                // ========== COLLECT FORM DATA ==========
                const formData = new FormData(this);

                console.log('📦 Form Data Collected:');

                // ✅ FIX: Check apakah guestInfo visible atau tidak
                const guestInfoDiv = document.getElementById('guestInfo');
                const isGuestInfoVisible = !guestInfoDiv.classList.contains('hidden');

                console.log('👤 Guest info visible:', isGuestInfoVisible);

                let hasError = false;
                let errorFields = [];

                // Log semua field
                for (let [key, value] of formData.entries()) {
                    console.log(`   ${key}: "${value}"`);

                    // ✅ SKIP VALIDATION untuk nama & email jika user sudah login
                    if ((key === 'nama' || key === 'email') && !isGuestInfoVisible) {
                        console.log(`   ℹ️ Skipping validation for "${key}" (user is logged in)`);
                        continue;
                    }

                    // Validate field yang lain
                    if (!value || value.trim() === '') {
                        hasError = true;
                        errorFields.push(key);
                        console.error(`   ❌ Field "${key}" is EMPTY!`);
                    }
                }

                // Check kategori
                const kategoriInput = document.getElementById('kategoriInput').value;
                console.log('🏷️ Kategori dari hidden input:', kategoriInput);

                if (!kategoriInput || kategoriInput.trim() === '') {
                    console.error('❌ KATEGORI KOSONG!');
                    alert('Silakan pilih kategori terlebih dahulu');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    return;
                }

                // Validate required fields
                if (hasError) {
                    console.error('❌ VALIDATION ERROR: Empty fields:', errorFields.join(', '));
                    alert('Mohon isi semua field yang diperlukan');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    return;
                }

                console.log('✅ Validation passed');

                // ========== SEND REQUEST ==========
                console.log('🌐 Sending request to: api/submit_support.php');

                const response = await fetch('api/submit_support.php', {
                    method: 'POST',
                    body: formData
                });

                console.log('📡 Response received:');
                console.log('   Status:', response.status);
                console.log('   OK:', response.ok);

                // ========== CHECK CONTENT TYPE ==========
                const contentType = response.headers.get("content-type");
                console.log('   Content-Type:', contentType);

                if (!contentType || !contentType.includes("application/json")) {
                    const text = await response.text();
                    console.error('❌ RESPONSE IS NOT JSON!');
                    console.error('Raw response:', text);

                    alert('Terjadi error di server. Cek console (F12) untuk detail.');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    return;
                }

                // ========== PARSE JSON ==========
                const data = await response.json();
                console.log('📄 Parsed JSON:');
                console.log(data);

                if (data.success) {
                    console.log('✅ SUCCESS!');
                    console.log('   Ticket ID:', data.ticket_id);
                    console.log('   Message:', data.message);

                    // Hide form, show success
                    document.getElementById('chatForm').classList.add('hidden');
                    document.getElementById('chatSuccess').classList.remove('hidden');

                } else {
                    console.error('❌ API returned error:', data.message);
                    alert(data.message || 'Gagal mengirim keluhan');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }

            } catch (error) {
                console.error('===========================================');
                console.error('❌ EXCEPTION CAUGHT');
                console.error('===========================================');
                console.error('Error type:', error.name);
                console.error('Error message:', error.message);
                console.error('Stack trace:', error.stack);

                alert('Terjadi kesalahan. Cek console (F12) untuk detail.');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }

            console.log('===========================================');
            console.log('📋 FORM SUBMIT ENDED');
            console.log('===========================================');
        });

        console.log('✅ Chat support scripts loaded successfully');
    </script>
</body>

</html>
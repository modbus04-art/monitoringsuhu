<?php
session_start();

// Cek apakah sudah login
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Monitoring Suhu MODBUS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #16213e 100%);
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated background */
        body::before {
            content: '';
            position: fixed;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(52, 152, 219, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            top: -50px;
            left: -50px;
            animation: float 6s ease-in-out infinite;
            z-index: 0;
            pointer-events: none;
        }

        body::after {
            content: '';
            position: fixed;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(155, 89, 182, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -50px;
            right: -50px;
            animation: float 8s ease-in-out infinite reverse;
            z-index: 0;
            pointer-events: none;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(20px); }
        }

        /* Navbar */
        .navbar {
            background: rgba(20, 29, 47, 0.7) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px 30px;
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, #3498db, #9b59b6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .navbar-brand i {
            margin-right: 10px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .navbar-text {
            color: #b0bec5 !important;
            font-size: 14px;
        }

        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            background: #27ae60;
            border-radius: 50%;
            margin-right: 8px;
            animation: pulse 2s infinite;
        }

        .status-indicator.offline {
            background: #e74c3c;
            animation: none;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(39, 174, 96, 0.7); }
            50% { opacity: 1; box-shadow: 0 0 0 10px rgba(39, 174, 96, 0); }
        }

        /* Main content */
        .main-content {
            padding: 30px 20px;
            position: relative;
            z-index: 1;
        }

        /* Top bar with time and status */
        .top-info-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(20, 29, 47, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            animation: slideDown 0.6s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .time-display {
            font-size: 32px;
            font-weight: 700;
            color: #3498db;
            font-family: 'Courier New', monospace;
            min-width: 220px;
        }

        .date-display {
            font-size: 14px;
            color: #b0bec5;
            margin-left: 20px;
        }

        .status-section {
            text-align: right;
        }

        .status-box {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .connection-status {
            background: rgba(39, 174, 96, 0.1);
            border: 1px solid rgba(39, 174, 96, 0.3);
            padding: 10px 16px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .connection-status.online {
            color: #27ae60;
        }

        .connection-status.offline {
            background: rgba(231, 76, 60, 0.1);
            border-color: rgba(231, 76, 60, 0.3);
            color: #e74c3c;
        }

        .connection-status:hover {
            transform: translateY(-2px);
        }

        .wifi-icon {
            font-size: 18px;
        }

        /* Sidebar/Menu */
        .sidebar {
            background: rgba(20, 29, 47, 0.6);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 15px;
            height: fit-content;
            position: sticky;
            top: 20px;
            animation: slideLeft 0.6s ease-out;
        }

        @keyframes slideLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .menu-title {
            font-size: 12px;
            text-transform: uppercase;
            color: #7f8c8d;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 15px;
            margin-top: 20px;
        }

        .menu-title:first-child {
            margin-top: 0;
        }

        .nav-link {
            color: #b0bec5;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            border-left: 3px solid transparent;
        }

        .nav-link:hover {
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
            border-left-color: #3498db;
            transform: translateX(5px);
        }

        .nav-link.active {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
            border-left-color: #3498db;
        }

        .nav-link i {
            font-size: 18px;
            min-width: 22px;
        }

        /* Cards */
        .card {
            background: rgba(20, 29, 47, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            color: #ffffff;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card:hover {
            border-color: rgba(52, 152, 219, 0.3);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(52, 152, 219, 0.1);
        }

        .card-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 20px;
            background: transparent;
        }

        .card-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 20px;
        }

        /* Temp cards */
        .temp-card {
            text-align: center;
            padding: 30px 20px;
        }

        .temp-icon {
            font-size: 50px;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #3498db, #9b59b6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .temp-value {
            font-size: 48px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 5px;
            font-family: 'Courier New', monospace;
        }

        .temp-label {
            color: #b0bec5;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .temp-status {
            margin-top: 15px;
            font-size: 12px;
            padding: 8px 12px;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 6px;
            display: inline-block;
            color: #3498db;
        }

        /* Control buttons */
        .control-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .control-btn {
            background: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.3);
            color: #3498db;
            padding: 20px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-weight: 600;
            position: relative;
            overflow: hidden;
        }

        .control-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .control-btn:hover::before {
            left: 100%;
        }

        .control-btn:hover {
            background: rgba(52, 152, 219, 0.2);
            border-color: #3498db;
            transform: translateY(-3px);
        }

        .control-btn.active {
            background: rgba(46, 204, 113, 0.2);
            border-color: #2ecc71;
            color: #2ecc71;
        }

        .control-btn.active:before {
            animation: pulse-light 1s infinite;
        }

        @keyframes pulse-light {
            0%, 100% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0.5) inset; }
            50% { box-shadow: 0 0 0 5px rgba(46, 204, 113, 0) inset; }
        }

        .control-btn i {
            font-size: 24px;
        }

        /* Chart container */
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }

        .chart-canvas {
            max-height: 300px;
        }

        /* Table */
        .table {
            color: #b0bec5;
            border-collapse: collapse;
        }

        .table thead th {
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.02);
            color: #ffffff;
            font-weight: 600;
            padding: 15px;
            text-align: left;
        }

        .table tbody tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: rgba(52, 152, 219, 0.05);
        }

        .table tbody td {
            padding: 12px 15px;
        }

        /* Badge */
        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .badge-danger {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .badge-warning {
            background: rgba(241, 196, 15, 0.2);
            color: #f1c40f;
            border: 1px solid rgba(241, 196, 15, 0.3);
        }

        /* Logout button */
        .btn-logout {
            width: 100%;
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid rgba(231, 76, 60, 0.3);
            color: #e74c3c;
            padding: 12px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            margin-top: 30px;
        }

        .btn-logout:hover {
            background: rgba(231, 76, 60, 0.2);
            border-color: #e74c3c;
            transform: translateY(-2px);
        }

        /* Grid layout */
        .row {
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .row:nth-child(1) { animation-delay: 0.1s; }
        .row:nth-child(2) { animation-delay: 0.2s; }
        .row:nth-child(3) { animation-delay: 0.3s; }

        /* Loading spinner */
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .top-info-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .status-section {
                text-align: left;
            }

            .time-display {
                font-size: 24px;
            }

            .control-grid {
                grid-template-columns: 1fr;
            }

            .sidebar {
                margin-bottom: 20px;
                position: static;
            }
        }

        /* Menu active indicator */
        .menu-indicator {
            width: 3px;
            height: 20px;
            background: linear-gradient(180deg, #3498db, #9b59b6);
            border-radius: 2px;
            margin-right: 10px;
        }

        /* Content sections */
        .section {
            display: none;
        }

        .section.active {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <span class="navbar-brand">
                <i class="fas fa-thermometer-half"></i> Smart Thermo
            </span>
            <div class="navbar-text">
                <span class="status-indicator" id="statusIndicator"></span>
                <span id="statusText">Terhubung dengan Hardware</span>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content container-fluid">
        <!-- Top Info Bar -->
        <div class="top-info-bar">
            <div>
                <div class="time-display" id="timeDisplay">00:00:00</div>
                <div class="date-display" id="dateDisplay">Sabtu, 14 Mei 2026</div>
            </div>
            <div class="status-section">
                <div class="status-box">
                    <div class="connection-status online" id="connectionStatus">
                        <i class="fas fa-wifi wifi-icon"></i>
                        <span>Hardware Terkoneksi</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2">
                <div class="sidebar">
                    <div class="menu-title">Menu</div>

                    <div class="nav-link active" onclick="showSection('monitoring')">
                        <i class="fas fa-chart-line"></i>
                        <span>Monitoring</span>
                    </div>
                    <div class="nav-link" onclick="showSection('kontrol')">
                        <i class="fas fa-sliders-h"></i>
                        <span>Kontrol</span>
                    </div>
                    <div class="nav-link" onclick="showSection('grafik')">
                        <i class="fas fa-chart-area"></i>
                        <span>Grafik</span>
                    </div>
                    <div class="nav-link" onclick="showSection('riwayat')">
                        <i class="fas fa-history"></i>
                        <span>Riwayat</span>
                    </div>

                    <div class="menu-title">Pengaturan</div>
                    <div class="nav-link" onclick="showSection('pengaturan')">
                        <i class="fas fa-cog"></i>
                        <span>Pengaturan</span>
                    </div>

                    <button class="btn-logout" onclick="logout()">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="col-lg-10">
                <!-- Monitoring Section -->
                <div class="section active" id="monitoring">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-home"></i> Suhu Ruangan (DHT22)
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="temp-card">
                                        <div class="temp-icon">
                                            <i class="fas fa-home"></i>
                                        </div>
                                        <div class="temp-value" id="tempRuangan">28.5</div>
                                        <div class="temp-label">°C</div>
                                        <div class="temp-status">
                                            <i class="fas fa-droplet"></i> Kelembaban: <span id="humidityRuangan">65</span>%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-cogs"></i> Suhu Mesin/Perangkat (PT100)
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="temp-card">
                                        <div class="temp-icon">
                                            <i class="fas fa-cogs"></i>
                                        </div>
                                        <div class="temp-value" id="tempMesin">45.3</div>
                                        <div class="temp-label">°C</div>
                                        <div class="temp-status">
                                            <i class="fas fa-gauge"></i> Status: <span id="statusMesin">Normal</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kontrol Section -->
                <div class="section" id="kontrol">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-sliders-h"></i> Kontrol Perangkat
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6 class="mb-4">Kipas (Fan)</h6>
                            <div class="control-grid">
                                <button class="control-btn" id="fan1Btn" onclick="toggleFan(1)">
                                    <i class="fas fa-fan"></i>
                                    <span>Kipas 1: <span id="fan1Status">OFF</span></span>
                                </button>
                                <button class="control-btn" id="fan2Btn" onclick="toggleFan(2)">
                                    <i class="fas fa-fan"></i>
                                    <span>Kipas 2: <span id="fan2Status">OFF</span></span>
                                </button>
                            </div>

                            <h6 class="mb-4 mt-4">Buzzer (Alarm)</h6>
                            <div class="control-grid">
                                <button class="control-btn" id="buzzerBtn" onclick="toggleBuzzer()">
                                    <i class="fas fa-volume-up"></i>
                                    <span>Buzzer: <span id="buzzerStatus">OFF</span></span>
                                </button>
                                <div style="padding: 20px; background: rgba(241, 196, 15, 0.1); border-radius: 12px; border: 1px solid rgba(241, 196, 15, 0.3); display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-info-circle" style="color: #f1c40f;"></i>
                                    <span style="font-size: 14px; color: #b0bec5;">Aktif saat suhu mesin > 50°C</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grafik Section -->
                <div class="section" id="grafik">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-chart-area"></i> Grafik Suhu Real-time (24 Jam)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="tempChart" class="chart-canvas"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Riwayat Section -->
                <div class="section" id="riwayat">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-history"></i> Riwayat Data
                            </h5>
                        </div>
                        <div class="card-body">
                            <div style="overflow-x: auto;">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Waktu</th>
                                            <th>Suhu Ruangan</th>
                                            <th>Kelembaban</th>
                                            <th>Suhu Mesin</th>
                                            <th>Status Kipas 1</th>
                                            <th>Status Kipas 2</th>
                                            <th>Buzzer</th>
                                        </tr>
                                    </thead>
                                    <tbody id="historyTable">
                                        <tr>
                                            <td>14:35:22</td>
                                            <td>28.5°C</td>
                                            <td>65%</td>
                                            <td>45.3°C</td>
                                            <td><span class="badge badge-success">ON</span></td>
                                            <td><span class="badge badge-danger">OFF</span></td>
                                            <td><span class="badge badge-danger">OFF</span></td>
                                        </tr>
                                        <tr>
                                            <td>14:30:15</td>
                                            <td>28.2°C</td>
                                            <td>64%</td>
                                            <td>44.8°C</td>
                                            <td><span class="badge badge-success">ON</span></td>
                                            <td><span class="badge badge-danger">OFF</span></td>
                                            <td><span class="badge badge-danger">OFF</span></td>
                                        </tr>
                                        <tr>
                                            <td>14:25:08</td>
                                            <td>27.9°C</td>
                                            <td>63%</td>
                                            <td>44.1°C</td>
                                            <td><span class="badge badge-danger">OFF</span></td>
                                            <td><span class="badge badge-danger">OFF</span></td>
                                            <td><span class="badge badge-danger">OFF</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pengaturan Section -->
                <div class="section" id="pengaturan">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-cog"></i> Pengaturan Sistem
                            </h5>
                        </div>
                        <div class="card-body">
                            <div style="display: grid; gap: 20px;">
                                <div>
                                    <label style="color: #b0bec5; font-weight: 600; margin-bottom: 10px; display: block;">
                                        <i class="fas fa-server"></i> Status Server
                                    </label>
                                    <div style="background: rgba(39, 174, 96, 0.1); border: 1px solid rgba(39, 174, 96, 0.3); padding: 15px; border-radius: 10px; color: #2ecc71;">
                                        <span class="loading-spinner"></span> Server: Online
                                    </div>
                                </div>

                                <div>
                                    <label style="color: #b0bec5; font-weight: 600; margin-bottom: 10px; display: block;">
                                        <i class="fas fa-microchip"></i> Informasi Hardware
                                    </label>
                                    <div style="background: rgba(52, 152, 219, 0.1); border: 1px solid rgba(52, 152, 219, 0.3); padding: 15px; border-radius: 10px; color: #3498db;">
                                        <p style="margin: 0; font-size: 14px;">
                                            <strong>Transmitter:</strong> ATMega328 - Baurate: 9600<br>
                                            <strong>Receiver:</strong> ESP32 - SSID: SmartThermo_WiFi<br>
                                            <strong>Protokol:</strong> Modbus RTU - 9600 bps
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <label style="color: #b0bec5; font-weight: 600; margin-bottom: 10px; display: block;">
                                        <i class="fas fa-heartbeat"></i> Statistik Sistem
                                    </label>
                                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                                        <div style="background: rgba(155, 89, 182, 0.1); border: 1px solid rgba(155, 89, 182, 0.3); padding: 15px; border-radius: 10px; text-align: center;">
                                            <div style="font-size: 24px; color: #9b59b6; font-weight: 700;">99.8%</div>
                                            <div style="font-size: 12px; color: #b0bec5;">Uptime</div>
                                        </div>
                                        <div style="background: rgba(46, 204, 113, 0.1); border: 1px solid rgba(46, 204, 113, 0.3); padding: 15px; border-radius: 10px; text-align: center;">
                                            <div style="font-size: 24px; color: #2ecc71; font-weight: 700;">2.4 GB</div>
                                            <div style="font-size: 12px; color: #b0bec5;">Storage Used</div>
                                        </div>
                                        <div style="background: rgba(52, 152, 219, 0.1); border: 1px solid rgba(52, 152, 219, 0.3); padding: 15px; border-radius: 10px; text-align: center;">
                                            <div style="font-size: 24px; color: #3498db; font-weight: 700;">234</div>
                                            <div style="font-size: 12px; color: #b0bec5;">Data Points</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Update waktu real-time
        function updateTime() {
            const now = new Date();
            const time = now.toLocaleTimeString('id-ID', { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit' 
            });
            const date = now.toLocaleDateString('id-ID', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            document.getElementById('timeDisplay').textContent = time;
            document.getElementById('dateDisplay').textContent = date;
        }

        // Update setiap detik
        updateTime();
        setInterval(updateTime, 1000);

        // Data suhu simulasi
        let tempData = {
            ruangan: [26, 27, 28, 28.5, 28.2, 27.9, 28.1, 28.4, 28.6, 29, 28.8, 28.5],
            mesin: [42, 43, 44, 45.3, 44.8, 44.1, 44.5, 45, 45.8, 46, 45.5, 45.3]
        };

        let fan1Status = false;
        let fan2Status = false;
        let buzzerStatus = false;

        // Initialize Chart
        function initChart() {
            const ctx = document.getElementById('tempChart').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['02:00', '04:00', '06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00', '24:00'],
                    datasets: [
                        {
                            label: 'Suhu Ruangan',
                            data: tempData.ruangan,
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52, 152, 219, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointBackgroundColor: '#3498db',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 7
                        },
                        {
                            label: 'Suhu Mesin',
                            data: tempData.mesin,
                            borderColor: '#e74c3c',
                            backgroundColor: 'rgba(231, 76, 60, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointBackgroundColor: '#e74c3c',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 7
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: '#b0bec5',
                                font: { size: 14, weight: 600 },
                                padding: 20
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 20,
                            max: 50,
                            grid: { color: 'rgba(255, 255, 255, 0.05)' },
                            ticks: { color: '#b0bec5' }
                        },
                        x: {
                            grid: { color: 'rgba(255, 255, 255, 0.05)' },
                            ticks: { color: '#b0bec5' }
                        }
                    }
                }
            });
        }

        // Show section
        function showSection(sectionName) {
            const sections = document.querySelectorAll('.section');
            sections.forEach(section => section.classList.remove('active'));
            
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => link.classList.remove('active'));
            
            document.getElementById(sectionName).classList.add('active');
            event.target.closest('.nav-link').classList.add('active');

            if (sectionName === 'grafik' && !window.chartInitialized) {
                setTimeout(initChart, 100);
                window.chartInitialized = true;
            }
        }

        // Toggle Fan
        function toggleFan(fanNumber) {
            if (fanNumber === 1) {
                fan1Status = !fan1Status;
                const btn = document.getElementById('fan1Btn');
                const status = document.getElementById('fan1Status');
                if (fan1Status) {
                    btn.classList.add('active');
                    status.textContent = 'ON';
                } else {
                    btn.classList.remove('active');
                    status.textContent = 'OFF';
                }
            } else {
                fan2Status = !fan2Status;
                const btn = document.getElementById('fan2Btn');
                const status = document.getElementById('fan2Status');
                if (fan2Status) {
                    btn.classList.add('active');
                    status.textContent = 'ON';
                } else {
                    btn.classList.remove('active');
                    status.textContent = 'OFF';
                }
            }
        }

        // Toggle Buzzer
        function toggleBuzzer() {
            buzzerStatus = !buzzerStatus;
            const btn = document.getElementById('buzzerBtn');
            const status = document.getElementById('buzzerStatus');
            if (buzzerStatus) {
                btn.classList.add('active');
                status.textContent = 'ON';
            } else {
                btn.classList.remove('active');
                status.textContent = 'OFF';
            }
        }

        // Logout
        function logout() {
            window.location.href = 'logout.php';
        }

        // Simulate temperature updates
        function updateTemperatures() {
            const ruanganVariation = (Math.random() - 0.5) * 0.5;
            const mesinVariation = (Math.random() - 0.5) * 1;
            
            let tempRuangan = parseFloat(document.getElementById('tempRuangan').textContent) + ruanganVariation;
            let tempMesin = parseFloat(document.getElementById('tempMesin').textContent) + mesinVariation;
            
            // Keep values within reasonable ranges
            tempRuangan = Math.max(25, Math.min(35, tempRuangan));
            tempMesin = Math.max(40, Math.min(55, tempMesin));
            
            document.getElementById('tempRuangan').textContent = tempRuangan.toFixed(1);
            document.getElementById('tempMesin').textContent = tempMesin.toFixed(1);
            document.getElementById('humidityRuangan').textContent = Math.round(Math.random() * 30 + 55);
            
            // Update status
            if (tempMesin > 50) {
                document.getElementById('statusMesin').textContent = 'TINGGI';
                document.getElementById('statusMesin').style.color = '#e74c3c';
                // Auto trigger buzzer
                if (!buzzerStatus) {
                    buzzerStatus = true;
                    document.getElementById('buzzerBtn').classList.add('active');
                    document.getElementById('buzzerStatus').textContent = 'ON';
                }
            } else if (tempMesin > 45) {
                document.getElementById('statusMesin').textContent = 'Sedang';
                document.getElementById('statusMesin').style.color = '#f1c40f';
            } else {
                document.getElementById('statusMesin').textContent = 'Normal';
                document.getElementById('statusMesin').style.color = '#2ecc71';
                // Auto turn off buzzer
                if (buzzerStatus) {
                    buzzerStatus = false;
                    document.getElementById('buzzerBtn').classList.remove('active');
                    document.getElementById('buzzerStatus').textContent = 'OFF';
                }
            }
        }

        // Update setiap 3 detik
        setInterval(updateTemperatures, 3000);

        // Simulate hardware connection status
        setInterval(() => {
            const statusIndicator = document.getElementById('statusIndicator');
            const statusText = document.getElementById('statusText');
            const connectionStatus = document.getElementById('connectionStatus');
            
            // Simulate 95% uptime
            const isConnected = Math.random() > 0.05;
            
            if (isConnected) {
                statusIndicator.classList.remove('offline');
                statusText.textContent = 'Terhubung dengan Hardware';
                connectionStatus.classList.remove('offline');
                connectionStatus.classList.add('online');
                connectionStatus.innerHTML = '<i class="fas fa-wifi wifi-icon"></i><span>Hardware Terkoneksi</span>';
            } else {
                statusIndicator.classList.add('offline');
                statusText.textContent = 'Koneksi Terputus';
                connectionStatus.classList.remove('online');
                connectionStatus.classList.add('offline');
                connectionStatus.innerHTML = '<i class="fas fa-wifi-slash wifi-icon"></i><span>Hardware Terputus</span>';
            }
        }, 5000);
    </script>
</body>
</html>
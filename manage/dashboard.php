<?php
session_start();
date_default_timezone_set('Asia/Kolkata');

// Ensure the user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

require_once '../includes/db.php';

// Fetch counts safely (checking if tables exist first)
$doctorsCount = 0;
$appointmentsCount = 0;
$pendingAppointmentsCount = 0;
$confirmedAppointmentsCount = 0;
$reportsCount = 0;
$patientsCount = 0;

if ($conn->query("SHOW TABLES LIKE 'doctors'")->num_rows > 0) {
    $doctorsCount = $conn->query("SELECT COUNT(*) as count FROM doctors")->fetch_assoc()['count'] ?? 0;
}
if ($conn->query("SHOW TABLES LIKE 'appointments'")->num_rows > 0) {
    $apptQuery = $conn->query("SELECT status, COUNT(*) as count FROM appointments GROUP BY status");
    if ($apptQuery && $apptQuery->num_rows > 0) {
        while ($row = $apptQuery->fetch_assoc()) {
            $appointmentsCount += $row['count'];
            if ($row['status'] == 'Pending') $pendingAppointmentsCount = $row['count'];
            if ($row['status'] == 'Confirmed') $confirmedAppointmentsCount = $row['count'];
        }
    }
}
if ($conn->query("SHOW TABLES LIKE 'reports'")->num_rows > 0) {
    $reportsCount = $conn->query("SELECT COUNT(*) as count FROM reports")->fetch_assoc()['count'] ?? 0;
}
if ($conn->query("SHOW TABLES LIKE 'patients'")->num_rows > 0) {
    $patientsCount = $conn->query("SELECT COUNT(*) as count FROM patients")->fetch_assoc()['count'] ?? 0;
}

$favicon = '';
$favResult = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'favicon'");
if ($favResult && $favResult->num_rows > 0) {
    $favicon = $favResult->fetch_assoc()['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700,800" rel="stylesheet">
    <?php if (!empty($favicon)): ?>
        <link rel="icon" href="../<?= htmlspecialchars($favicon) ?>?v=<?= time() ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-sidebar.css">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin-custom.css">
    <link rel="stylesheet" href="../assets/css/admin-responsive.css">
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="admin-container">
        
        <div class="admin-header">
            <h2><i class="fas fa-chart-line"></i> Dashboard Overview</h2>
        </div>
        
        <div class="dashboard-grid">
            <div class="stat-card stat-card-link" onclick="window.location.href='doctors.php'">
                <div class="stat-icon stat-icon-blue"><i class="fas fa-user-md"></i></div>
                <div class="stat-info"><h3 class="stat-value"><?= $doctorsCount ?></h3><span class="stat-label">Total Doctors</span></div>
            </div>
            <div class="stat-card stat-card-link" onclick="window.location.href='patients.php'">
                <div class="stat-icon stat-icon-indigo"><i class="fas fa-users"></i></div>
                <div class="stat-info"><h3 class="stat-value"><?= $patientsCount ?></h3><span class="stat-label">Total Patients</span></div>
            </div>
            <div class="stat-card stat-card-link" onclick="window.location.href='reports.php'">
                <div class="stat-icon stat-icon-orange"><i class="fas fa-file-medical"></i></div>
                <div class="stat-info"><h3 class="stat-value"><?= $reportsCount ?></h3><span class="stat-label">Total Reports</span></div>
            </div>
            <div class="stat-card stat-card-link" onclick="window.location.href='appointments.php?from_date=&to_date='">
                <div class="stat-icon stat-icon-green"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-info"><h3 class="stat-value"><?= $appointmentsCount ?></h3><span class="stat-label">Total Appointments</span></div>
            </div>
            <div class="stat-card stat-card-link" onclick="window.location.href='appointments.php?status=Pending'">
                <div class="stat-icon stat-icon-red"><i class="fas fa-clock"></i></div>
                <div class="stat-info"><h3 class="stat-value"><?= $pendingAppointmentsCount ?></h3><span class="stat-label">Pending Appointments</span></div>
            </div>
            <div class="stat-card stat-card-link" onclick="window.location.href='appointments.php?status=Confirmed'">
                <div class="stat-icon stat-icon-indigo"><i class="fas fa-thumbs-up"></i></div>
                <div class="stat-info"><h3 class="stat-value"><?= $confirmedAppointmentsCount ?></h3><span class="stat-label">Confirmed Appointments</span></div>
            </div>
        </div>
        </div>
    </main>
</body>
</html>
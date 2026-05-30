<?php
require_once __DIR__ . '/db.php';

// Fetch all settings
$app_settings = [];
$settingsResult = $conn->query("SELECT setting_key, setting_value FROM settings");
if ($settingsResult) {
    while ($row = $settingsResult->fetch_assoc()) {
        $app_settings[$row['setting_key']] = $row['setting_value'];
    }
}

$frontend_logo = isset($app_settings['admin_logo']) && !empty($app_settings['admin_logo']) ? $app_settings['admin_logo'] : 'assets/images/logo.png';
$frontend_logo_v = $frontend_logo . '?v=' . time(); // Cache-buster

$top_bar_address = $app_settings['top_bar_address'] ?? 'Hyderbeigh, Baramulla, Jammu & Kashmir';
$top_bar_timing = $app_settings['top_bar_timing'] ?? 'Mon - Sat: 8:00 AM - 8:00 PM | Sun: 9:00 AM - 2:00 PM';
$top_bar_phone = $app_settings['top_bar_phone'] ?? '+91 7051674869';
$top_bar_email = $app_settings['top_bar_email'] ?? 'info@maxdiagnostic.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Max Diagnostic Center | Hyderbeigh</title>

    <!-- Bunny Fonts -->
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700,800" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

   <link rel="stylesheet" href="assets/css/header.css">
   <link rel="stylesheet" href="assets/css/global.css">
   <link rel="stylesheet" href="assets/css/footer.css">
   <link rel="stylesheet" href="assets/css/index.css">
   <link rel="stylesheet" href="assets/css/header-mobile.css">
   <?php
    // Header code injection
    echo $app_settings['header_scripts'] ?? '';
   ?>
</head>

<body class="page-body">
<?php echo $app_settings['body_start_scripts'] ?? ''; ?>
    <!-- ==========================
         TOP BAR
    =========================== -->

    <div class="top-bar">

        <div class="container">

            <div class="top-left">

                <span class="top-info">
                    <i class="fas fa-location-dot top-icon"></i>
                    <?= htmlspecialchars($top_bar_address) ?>
                </span>

            </div>

            <div class="top-center">

                <span class="top-info">
                    <i class="far fa-clock top-icon"></i>
                    <?= htmlspecialchars($top_bar_timing) ?>
                </span>

            </div>

            <div class="top-right">

                <span class="top-info">
                    <i class="fas fa-phone top-icon"></i>
                    <?= htmlspecialchars($top_bar_phone) ?>
                </span>

                <span class="top-info">
                    <i class="fas fa-envelope top-icon"></i>
                    <?= htmlspecialchars($top_bar_email) ?>
                </span>

            </div>

        </div>

    </div>

    <!-- ==========================
         HEADER
    =========================== -->

    <header class="header">

        <div class="container">

            <div class="logo">

                <a href="index.php"><img class="logo-image" src="<?= htmlspecialchars($frontend_logo_v) ?>" alt="Max Diagnostic Center"></a>

            </div>

            <div class="menu-overlay" id="menu-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 998; backdrop-filter: blur(2px);"></div>

            <nav class="navbar" id="navbar">

                <ul class="nav-list">

                    <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>

                    <li class="nav-item"><a class="nav-link" href="services.php">Services</a></li>

                        <li class="nav-item"><a class="nav-link" href="specialists.php">Our Specialists</a></li>

                    <li class="nav-item"><a class="nav-link" href="appointments.php">Appointments</a></li>

                    <li class="nav-item mobile-only"><a class="nav-link" href="reports.php">Download Reports</a></li>

                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>

                </ul>

            </nav>

            <div class="header-actions">

                <a href="reports.php" class="appointment-btn">
                    Download Reports
                </a>

                <div class="menu-toggle" id="mobile-menu">
                    <i class="fas fa-bars"></i>
                </div>

            </div>

        </div>

    </header>
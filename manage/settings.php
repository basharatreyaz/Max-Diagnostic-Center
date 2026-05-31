<?php
session_start();
date_default_timezone_set('Asia/Kolkata');

// Ensure the user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['admin_permissions']) || ($_SESSION['admin_permissions'] !== 'all' && !in_array('settings', (array)$_SESSION['admin_permissions']))) {
    header("Location: dashboard.php");
    exit;
}

require_once '../includes/db.php';

$seo_pages = [
    'home' => 'Home Page',
    'about' => 'About Us',
    'services' => 'Services',
    'specialists' => 'Specialists',
    'appointments' => 'Appointments',
    'reports' => 'Reports',
    'contact' => 'Contact Us'
];

// Check if settings table exists, if not create and seed it
$tableCheck = $conn->query("SHOW TABLES LIKE 'settings'");
if ($tableCheck && $tableCheck->num_rows == 0) {
    $sql = "CREATE TABLE settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value VARCHAR(255) NOT NULL
    )";
    $conn->query($sql);
    
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('items_per_page', '10'), ('header_scripts', ''), ('body_start_scripts', ''), ('footer_scripts', ''), ('ticket_title', 'Appointment Token'), ('ticket_footer_text', ''), ('ticket_phone', '+91 7051674869'), ('ticket_address', 'Opposite JK Bank, Hyderbeigh, Jammu & Kashmir 193121'), ('ticket_qr_link', '')");
    
    // Default appointment form settings
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('appointment_show_age', '1'), ('appointment_show_gender', '1'), ('appointment_show_address', '0')");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('appointment_show_department', '1'), ('appointment_show_doctor', '1')");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('top_bar_address', 'Hyderbeigh, Baramulla, Jammu & Kashmir'), ('top_bar_timing', 'Mon - Sat: 8:00 AM - 8:00 PM | Sun: 9:00 AM - 2:00 PM'), ('top_bar_phone', '+91 7051674869'), ('top_bar_email', 'info@maxdiagnostic.com')");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('social_facebook', '#'), ('social_instagram', '#'), ('social_whatsapp', '#')");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('whatsapp_number', '917051674869')");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('meta_title', 'Max Diagnostic Center'), ('meta_description', 'Comprehensive Diagnostic & Healthcare Solutions')");
    
    $seo_inserts = [];
    foreach ($seo_pages as $key => $label) {
        $seo_inserts[] = "('meta_title_$key', 'Max Diagnostic Center | $label')";
        $seo_inserts[] = "('meta_desc_$key', 'Comprehensive Diagnostic & Healthcare Solutions - $label')";
    }
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES " . implode(", ", $seo_inserts));

    clearSettingsCache();
} else {
    // Ensure column can hold large scripts
    $columnInfo = $conn->query("SHOW COLUMNS FROM settings WHERE Field = 'setting_value'");
    if ($columnInfo) {
        $columnType = $columnInfo->fetch_assoc()['Type'];
        if (strtolower($columnType) != 'text') {
            $conn->query("ALTER TABLE settings MODIFY COLUMN setting_value TEXT");
        }
    }
    $conn->query("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('ticket_title', 'Appointment Token'), ('ticket_footer_text', ''), ('ticket_phone', '+91 7051674869'), ('ticket_address', 'Opposite JK Bank, Hyderbeigh, Jammu & Kashmir 193121'), ('ticket_qr_link', '')");
    // Ensure appointment settings exist
    $conn->query("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('appointment_show_age', '1'), ('appointment_show_gender', '1'), ('appointment_show_address', '0')");
    $conn->query("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('appointment_show_department', '1'), ('appointment_show_doctor', '1')");
    $conn->query("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('top_bar_address', 'Hyderbeigh, Baramulla, Jammu & Kashmir'), ('top_bar_timing', 'Mon - Sat: 8:00 AM - 8:00 PM | Sun: 9:00 AM - 2:00 PM'), ('top_bar_phone', '+91 7051674869'), ('top_bar_email', 'info@maxdiagnostic.com')");
    $conn->query("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('social_facebook', '#'), ('social_instagram', '#'), ('social_whatsapp', '#')");
    $conn->query("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('whatsapp_number', '917051674869')");
    $conn->query("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('meta_title', 'Max Diagnostic Center'), ('meta_description', 'Comprehensive Diagnostic & Healthcare Solutions')");
    
    $seo_inserts = [];
    foreach ($seo_pages as $key => $label) {
        $seo_inserts[] = "('meta_title_$key', 'Max Diagnostic Center | $label')";
        $seo_inserts[] = "('meta_desc_$key', 'Comprehensive Diagnostic & Healthcare Solutions - $label')";
    }
    $conn->query("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES " . implode(", ", $seo_inserts));

    clearSettingsCache();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    $items_per_page = (int)$_POST['items_per_page'];
    if ($items_per_page < 1) $items_per_page = 10;
    $items_per_page_esc = $conn->real_escape_string($items_per_page);
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('items_per_page', '$items_per_page_esc') ON DUPLICATE KEY UPDATE setting_value = '$items_per_page_esc'");

    $header_scripts = $conn->real_escape_string($_POST['header_scripts']);
    $body_start_scripts = $conn->real_escape_string($_POST['body_start_scripts']);
    $footer_scripts = $conn->real_escape_string($_POST['footer_scripts']);

    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('header_scripts', '$header_scripts') ON DUPLICATE KEY UPDATE setting_value = '$header_scripts'");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('body_start_scripts', '$body_start_scripts') ON DUPLICATE KEY UPDATE setting_value = '$body_start_scripts'");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('footer_scripts', '$footer_scripts') ON DUPLICATE KEY UPDATE setting_value = '$footer_scripts'");

    $ticket_title = $conn->real_escape_string($_POST['ticket_title']);
    $ticket_footer_text = $conn->real_escape_string($_POST['ticket_footer_text']);
    $ticket_phone = $conn->real_escape_string($_POST['ticket_phone']);
    $ticket_address = $conn->real_escape_string($_POST['ticket_address']);
    $ticket_qr_link = $conn->real_escape_string($_POST['ticket_qr_link']);
    
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('ticket_title', '$ticket_title') ON DUPLICATE KEY UPDATE setting_value = '$ticket_title'");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('ticket_footer_text', '$ticket_footer_text') ON DUPLICATE KEY UPDATE setting_value = '$ticket_footer_text'");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('ticket_phone', '$ticket_phone') ON DUPLICATE KEY UPDATE setting_value = '$ticket_phone'");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('ticket_address', '$ticket_address') ON DUPLICATE KEY UPDATE setting_value = '$ticket_address'");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('ticket_qr_link', '$ticket_qr_link') ON DUPLICATE KEY UPDATE setting_value = '$ticket_qr_link'");

    $top_bar_address = $conn->real_escape_string($_POST['top_bar_address']);
    $top_bar_timing = $conn->real_escape_string($_POST['top_bar_timing']);
    $top_bar_phone = $conn->real_escape_string($_POST['top_bar_phone']);
    $top_bar_email = $conn->real_escape_string($_POST['top_bar_email']);
    
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('top_bar_address', '$top_bar_address') ON DUPLICATE KEY UPDATE setting_value = '$top_bar_address'");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('top_bar_timing', '$top_bar_timing') ON DUPLICATE KEY UPDATE setting_value = '$top_bar_timing'");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('top_bar_phone', '$top_bar_phone') ON DUPLICATE KEY UPDATE setting_value = '$top_bar_phone'");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('top_bar_email', '$top_bar_email') ON DUPLICATE KEY UPDATE setting_value = '$top_bar_email'");

    $social_facebook = $conn->real_escape_string($_POST['social_facebook']);
    $social_instagram = $conn->real_escape_string($_POST['social_instagram']);
    $social_whatsapp = $conn->real_escape_string($_POST['social_whatsapp']);
    
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('social_facebook', '$social_facebook') ON DUPLICATE KEY UPDATE setting_value = '$social_facebook'");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('social_instagram', '$social_instagram') ON DUPLICATE KEY UPDATE setting_value = '$social_instagram'");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('social_whatsapp', '$social_whatsapp') ON DUPLICATE KEY UPDATE setting_value = '$social_whatsapp'");

    $whatsapp_number = $conn->real_escape_string($_POST['whatsapp_number']);
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('whatsapp_number', '$whatsapp_number') ON DUPLICATE KEY UPDATE setting_value = '$whatsapp_number'");

    $meta_title = $conn->real_escape_string($_POST['meta_title']);
    $meta_description = $conn->real_escape_string($_POST['meta_description']);
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('meta_title', '$meta_title') ON DUPLICATE KEY UPDATE setting_value = '$meta_title'");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('meta_description', '$meta_description') ON DUPLICATE KEY UPDATE setting_value = '$meta_description'");

    foreach ($seo_pages as $key => $label) {
        if (isset($_POST["meta_title_$key"])) {
            $m_title = $conn->real_escape_string($_POST["meta_title_$key"]);
            $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('meta_title_$key', '$m_title') ON DUPLICATE KEY UPDATE setting_value = '$m_title'");
        }
        if (isset($_POST["meta_desc_$key"])) {
            $m_desc = $conn->real_escape_string($_POST["meta_desc_$key"]);
            $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('meta_desc_$key', '$m_desc') ON DUPLICATE KEY UPDATE setting_value = '$m_desc'");
        }
    }

    // Appointment Form Settings
    $appointment_settings = [
        'appointment_show_age' => isset($_POST['appointment_show_age']) ? 1 : 0,
        'appointment_show_gender' => isset($_POST['appointment_show_gender']) ? 1 : 0,
        'appointment_show_address' => isset($_POST['appointment_show_address']) ? 1 : 0,
        'appointment_show_department' => isset($_POST['appointment_show_department']) ? 1 : 0,
        'appointment_show_doctor' => isset($_POST['appointment_show_doctor']) ? 1 : 0,
    ];
    foreach ($appointment_settings as $key => $value) {
        $value_esc = $conn->real_escape_string($value);
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value_esc') ON DUPLICATE KEY UPDATE setting_value = '$value_esc'");
    }
    
    // Admin Logo Upload
    $allowedImageTypes = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 
        'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'
    ];
    if (isset($_FILES['admin_logo']) && $_FILES['admin_logo']['error'] == 0) {
        $targetDir = "../assets/images/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        if ($ext = validateSecureUpload($_FILES['admin_logo']['tmp_name'], $_FILES['admin_logo']['name'], $_FILES['admin_logo']['size'], $allowedImageTypes)) {
            $fileName = bin2hex(random_bytes(16)) . '.' . $ext;
            $targetFilePath = $targetDir . $fileName;
            
            if (move_uploaded_file($_FILES['admin_logo']['tmp_name'], $targetFilePath)) {
                $logoPath = "assets/images/" . $fileName;
                $oldLogoResult = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'admin_logo'");
                if ($oldLogoResult && $oldLogoResult->num_rows > 0) {
                    $oldLogo = $oldLogoResult->fetch_assoc()['setting_value'];
                    if (!empty($oldLogo) && strpos($oldLogo, 'logo.png') === false && file_exists("../" . $oldLogo)) {
                        unlink("../" . $oldLogo);
                    }
                }
                $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('admin_logo', '$logoPath') ON DUPLICATE KEY UPDATE setting_value = '$logoPath'");
            }
        }
    }
    
    // Favicon Upload
    $allowedFaviconTypes = [
        'ico' => ['image/x-icon', 'image/vnd.microsoft.icon', 'application/octet-stream'],
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 
        'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'
    ];
    if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] == 0) {
        $targetDir = "../assets/images/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        if ($ext = validateSecureUpload($_FILES['favicon']['tmp_name'], $_FILES['favicon']['name'], $_FILES['favicon']['size'], $allowedFaviconTypes)) {
            $fileName = 'favicon_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $targetFilePath = $targetDir . $fileName;
            
            if (move_uploaded_file($_FILES['favicon']['tmp_name'], $targetFilePath)) {
                $faviconPath = "assets/images/" . $fileName;
                $oldFaviconResult = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'favicon'");
                if ($oldFaviconResult && $oldFaviconResult->num_rows > 0) {
                    $oldFavicon = $oldFaviconResult->fetch_assoc()['setting_value'];
                    if (!empty($oldFavicon) && file_exists("../" . $oldFavicon)) {
                        unlink("../" . $oldFavicon);
                    }
                }
                $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('favicon', '$faviconPath') ON DUPLICATE KEY UPDATE setting_value = '$faviconPath'");
            }
        }
    }
    
    clearSettingsCache();
    $_SESSION['setting_msg'] = "Settings updated successfully!";
    header("Location: settings.php");
    exit;
}

// Fetch current setting
$settings = getCachedSettings($conn);

$current_limit = (int)($settings['items_per_page'] ?? 10);
$header_scripts = $settings['header_scripts'] ?? '';
$body_start_scripts = $settings['body_start_scripts'] ?? '';
$footer_scripts = $settings['footer_scripts'] ?? '';
$ticket_title = $settings['ticket_title'] ?? 'Appointment Token';
$ticket_footer_text = $settings['ticket_footer_text'] ?? '';
$ticket_phone = $settings['ticket_phone'] ?? '+91 7051674869';
$ticket_address = $settings['ticket_address'] ?? 'Opposite JK Bank, Hyderbeigh, Jammu & Kashmir 193121';
$ticket_qr_link = $settings['ticket_qr_link'] ?? '';

$top_bar_address = $settings['top_bar_address'] ?? 'Hyderbeigh, Baramulla, Jammu & Kashmir';
$top_bar_timing = $settings['top_bar_timing'] ?? 'Mon - Sat: 8:00 AM - 8:00 PM | Sun: 9:00 AM - 2:00 PM';
$top_bar_phone = $settings['top_bar_phone'] ?? '+91 7051674869';
$top_bar_email = $settings['top_bar_email'] ?? 'info@maxdiagnostic.com';

$social_facebook = $settings['social_facebook'] ?? '#';
$social_instagram = $settings['social_instagram'] ?? '#';
$social_whatsapp = $settings['social_whatsapp'] ?? '#';

$whatsapp_number = $settings['whatsapp_number'] ?? '917051674869';

$meta_title = $settings['meta_title'] ?? 'Max Diagnostic Center';
$meta_description = $settings['meta_description'] ?? 'Comprehensive Diagnostic & Healthcare Solutions';

$settings['appointment_show_age'] = $settings['appointment_show_age'] ?? 0;
$settings['appointment_show_gender'] = $settings['appointment_show_gender'] ?? 0;
$settings['appointment_show_address'] = $settings['appointment_show_address'] ?? 0;
$settings['appointment_show_department'] = $settings['appointment_show_department'] ?? 1;
$settings['appointment_show_doctor'] = $settings['appointment_show_doctor'] ?? 1;

$setting_msg = '';
if (isset($_SESSION['setting_msg'])) {
    $setting_msg = $_SESSION['setting_msg'];
    unset($_SESSION['setting_msg']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Settings</title>
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700,800" rel="stylesheet">
    <?php if (!empty($settings['favicon'])): ?>
        <link rel="icon" href="../<?= htmlspecialchars($settings['favicon']) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-sidebar.css">
    <link rel="stylesheet" href="../assets/css/admin-settings.css">
    <link rel="stylesheet" href="../assets/css/admin-custom.css">
    <link rel="stylesheet" href="../assets/css/admin-responsive.css">
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="admin-container">
            <div class="admin-header">
                <h2><i class="fas fa-cog"></i> Global Settings</h2>
            </div>
            <?php if ($setting_msg): ?>
                <div class="success-msg"><i class="fas fa-check-circle"></i> <?= $setting_msg ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                
                <div class="settings-tabs">
                    <button type="button" class="tab-btn active" onclick="openTab('tab-general', this)"><i class="fas fa-home"></i> General & Branding</button>
                    <button type="button" class="tab-btn" onclick="openTab('tab-seo', this)"><i class="fas fa-globe"></i> SEO</button>
                    <button type="button" class="tab-btn" onclick="openTab('tab-app', this)"><i class="fas fa-cogs"></i> App Settings</button>
                    <button type="button" class="tab-btn" onclick="openTab('tab-advanced', this)"><i class="fas fa-code"></i> Advanced</button>
                </div>

                <div id="tab-general" class="tab-content active">
                <div class="card">
                    <h3><i class="fas fa-image"></i> Branding</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="admin_logo">Admin Panel Logo</label>
                            <?php if (!empty($settings['admin_logo'])): ?>
                                <div class="settings-logo-preview">
                                    <img src="../<?= htmlspecialchars($settings['admin_logo']) ?>" alt="Current Logo" class="settings-logo-img">
                                </div>
                            <?php endif; ?>
                            <input type="file" id="admin_logo" name="admin_logo" accept="image/*">
                            <p class="form-hint">Upload a new logo to replace the default one.</p>
                        </div>
                        <div class="form-group">
                            <label for="favicon">Website Favicon</label>
                            <?php if (!empty($settings['favicon'])): ?>
                                <div class="settings-logo-preview">
                                    <img src="../<?= htmlspecialchars($settings['favicon']) ?>" alt="Current Favicon" class="settings-favicon-img">
                                </div>
                            <?php endif; ?>
                            <input type="file" id="favicon" name="favicon" accept=".ico,.png,.jpg,.jpeg,.gif,.webp">
                            <p class="form-hint">Upload a favicon (.ico, .png) to appear in the browser tab.</p>
                        </div>
                    </div>
                </div>
                    <div class="card">
                        <h3><i class="fas fa-bars"></i> Top Bar Settings</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="top_bar_address">Address</label>
                                <input type="text" id="top_bar_address" name="top_bar_address" value="<?= htmlspecialchars($top_bar_address) ?>">
                            </div>
                            <div class="form-group">
                                <label for="top_bar_timing">Timing</label>
                                <input type="text" id="top_bar_timing" name="top_bar_timing" value="<?= htmlspecialchars($top_bar_timing) ?>">
                            </div>
                            <div class="form-group">
                                <label for="top_bar_phone">Phone</label>
                                <input type="text" id="top_bar_phone" name="top_bar_phone" value="<?= htmlspecialchars($top_bar_phone) ?>">
                            </div>
                            <div class="form-group">
                                <label for="top_bar_email">Email</label>
                                <input type="email" id="top_bar_email" name="top_bar_email" value="<?= htmlspecialchars($top_bar_email) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <h3><i class="fas fa-share-alt"></i> Social Media Links</h3>
                        <div class="form-grid">
                            <div class="form-group"><label for="social_facebook">Facebook URL</label><input type="text" id="social_facebook" name="social_facebook" value="<?= htmlspecialchars($social_facebook) ?>"></div>
                            <div class="form-group"><label for="social_instagram">Instagram URL</label><input type="text" id="social_instagram" name="social_instagram" value="<?= htmlspecialchars($social_instagram) ?>"></div>
                            <div class="form-group"><label for="social_whatsapp">WhatsApp URL</label><input type="text" id="social_whatsapp" name="social_whatsapp" value="<?= htmlspecialchars($social_whatsapp) ?>"></div>
                            <div class="form-group">
                                <label for="whatsapp_number">WhatsApp Chat Number (Mobile Bottom Bar)</label>
                                <input type="text" id="whatsapp_number" name="whatsapp_number" placeholder="e.g. 917051674869" value="<?= htmlspecialchars($whatsapp_number) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div id="tab-seo" class="tab-content">
                <div class="card">
                    <h3><i class="fas fa-globe"></i> SEO & Meta Data</h3>
                    <div class="form-group settings-global-box">
                        <h4 class="settings-section-title">Global Defaults</h4>
                        <div class="form-group settings-group-margin">
                            <label for="meta_title">Global Meta Title</label>
                            <input type="text" id="meta_title" name="meta_title" value="<?= htmlspecialchars($meta_title) ?>">
                        </div>
                        <div class="form-group settings-group-no-margin">
                            <label for="meta_description">Global Meta Description</label>
                            <textarea id="meta_description" name="meta_description" rows="3"><?= htmlspecialchars($meta_description) ?></textarea>
                        </div>
                    </div>
                    <h4 class="settings-seo-header">Page-Specific SEO</h4>
                    <?php foreach ($seo_pages as $key => $label): ?>
                        <div class="form-group settings-page-box">
                            <label class="settings-seo-title"><i class="fas fa-file-alt"></i> <?= $label ?> Page</label>
                            <input type="text" name="meta_title_<?= $key ?>" placeholder="Meta Title for <?= $label ?>" value="<?= htmlspecialchars($settings['meta_title_' . $key] ?? '') ?>" class="settings-seo-input">
                            <textarea name="meta_desc_<?= $key ?>" placeholder="Meta Description for <?= $label ?>" rows="2"><?= htmlspecialchars($settings['meta_desc_' . $key] ?? '') ?></textarea>
                        </div>
                    <?php endforeach; ?>
                </div>
                </div>

                <div id="tab-app" class="tab-content">
                <div class="card">
                    <h3><i class="fas fa-sliders-h"></i> Display Preferences</h3>
                    <div class="form-group"><label>Items Per Page (Pagination Limit)</label><input type="number" name="items_per_page" min="1" max="100" value="<?= $current_limit ?>" required></div>
                </div>
                <div class="card">
                    <h3><i class="fas fa-ticket-alt"></i> Ticket Settings</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="ticket_title">Ticket Title</label>
                            <input type="text" id="ticket_title" name="ticket_title" value="<?= htmlspecialchars($ticket_title) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="ticket_phone">Hospital Phone Number</label>
                            <input type="text" id="ticket_phone" name="ticket_phone" value="<?= htmlspecialchars($ticket_phone) ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ticket_address">Hospital Address</label>
                        <textarea id="ticket_address" name="ticket_address" rows="2"><?= htmlspecialchars($ticket_address) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="ticket_qr_link">QR Code Link (Optional)</label>
                        <input type="text" id="ticket_qr_link" name="ticket_qr_link" value="<?= htmlspecialchars($ticket_qr_link) ?>" placeholder="Leave blank to use default (download-reports.php)">
                    </div>
                    <div class="form-group">
                        <label for="ticket_footer_text">Ticket Footer Text</label>
                        <textarea id="ticket_footer_text" name="ticket_footer_text" rows="3"><?= htmlspecialchars($ticket_footer_text) ?></textarea>
                    </div>
                </div>
                <div class="card">
                    <h3><i class="fas fa-calendar-alt"></i> Appointment Form Fields</h3>
                    <div class="form-group-flex">
                        <label for="show_age">Show 'Age' Field</label>
                        <input type="checkbox" id="show_age" name="appointment_show_age" value="1" <?= ($settings['appointment_show_age'] ?? 0) ? 'checked' : '' ?>>
                    </div>
                    <div class="form-group-flex">
                        <label for="show_gender">Show 'Gender' Field</label>
                        <input type="checkbox" id="show_gender" name="appointment_show_gender" value="1" <?= ($settings['appointment_show_gender'] ?? 0) ? 'checked' : '' ?>>
                    </div>
                    <div class="form-group-flex">
                        <label for="show_address">Show 'Address' Field</label>
                        <input type="checkbox" id="show_address" name="appointment_show_address" value="1" <?= ($settings['appointment_show_address'] ?? 0) ? 'checked' : '' ?>>
                    </div>
                    <div class="form-group-flex">
                        <label for="show_department">Show 'Department' Field</label>
                        <input type="checkbox" id="show_department" name="appointment_show_department" value="1" <?= ($settings['appointment_show_department'] ?? 1) ? 'checked' : '' ?>>
                    </div>
                    <div class="form-group-flex">
                        <label for="show_doctor">Show 'Doctor' Field</label>
                        <input type="checkbox" id="show_doctor" name="appointment_show_doctor" value="1" <?= ($settings['appointment_show_doctor'] ?? 1) ? 'checked' : '' ?>>
                    </div>
                </div>
                </div>

                <div id="tab-advanced" class="tab-content">
                    <div class="card">
                        <h3><i class="fas fa-code"></i> Code Injection</h3>
                        <div class="form-group">
                            <label for="header_scripts">Header Scripts (before &lt;/head&gt;)</label>
                            <textarea id="header_scripts" name="header_scripts" rows="5" placeholder="e.g., <script>...</script> or <style>...</style>"><?= htmlspecialchars($header_scripts) ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="body_start_scripts">Body Start Scripts (after &lt;body&gt;)</label>
                            <textarea id="body_start_scripts" name="body_start_scripts" rows="5" placeholder="e.g., Google Tag Manager noscript snippet"><?= htmlspecialchars($body_start_scripts) ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="footer_scripts">Footer Scripts (before &lt;/body&gt;)</label>
                            <textarea id="footer_scripts" name="footer_scripts" rows="5" placeholder="e.g., Google Analytics script"><?= htmlspecialchars($footer_scripts) ?></textarea>
                        </div>
                    </div>
                </div>

                <button type="submit" name="update_settings" class="btn-primary btn-font-outfit settings-save-btn"><i class="fas fa-save"></i> Save All Settings</button>
            </form>
        </div>
    </main>
    <script>
        function openTab(tabId, btn) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            btn.classList.add('active');
        }
    </script>
</body>
</html>
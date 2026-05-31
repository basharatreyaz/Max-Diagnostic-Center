<?php
session_start();
date_default_timezone_set('Asia/Kolkata');

// Ensure the user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

require_once '../includes/db.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid Appointment ID.");
}

$id = (int)$_GET['id'];
$result = $conn->query("SELECT * FROM appointments WHERE id = $id");

if (!$result || $result->num_rows == 0) {
    die("Appointment not found.");
}

$appointment = $result->fetch_assoc();

// Calculate daily appointment number based on appointment date
$appointment_date_val = date('Y-m-d', strtotime($appointment['appointment_date']));
$countResult = $conn->query("SELECT COUNT(*) as daily_no FROM appointments WHERE appointment_date = '$appointment_date_val' AND id <= $id");
$daily_no = 1;
if ($countResult && $countResult->num_rows > 0) {
    $daily_no = $countResult->fetch_assoc()['daily_no'];
}
$display_id = date('d-m-', strtotime($appointment['appointment_date'])) . $daily_no;

// Fetch Ticket Settings
$settings = getCachedSettings($conn);
$ticket_title = $settings['ticket_title'] ?? 'Appointment Token';
$ticket_footer_text = $settings['ticket_footer_text'] ?? '';
$ticket_phone = $settings['ticket_phone'] ?? '+91 7051674869';
$ticket_address = $settings['ticket_address'] ?? 'Opposite JK Bank, Hyderbeigh, Jammu & Kashmir 193121';
$ticket_qr_link = $settings['ticket_qr_link'] ?? '';
$admin_logo = isset($settings['admin_logo']) && !empty($settings['admin_logo']) ? '../' . $settings['admin_logo'] : '../assets/images/logo.png';
$favicon = $settings['favicon'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Ticket - <?= htmlspecialchars($appointment['patient_name']) ?></title>
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700,800" rel="stylesheet">
    <?php if (!empty($favicon)): ?>
        <link rel="icon" href="../<?= htmlspecialchars($favicon) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/print-ticket.css">
    <link rel="stylesheet" href="../assets/css/admin-custom.css">
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px; padding: 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #00a39d; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: 600;">
            <i class="fas fa-print"></i> Print Ticket
        </button>
        <p style="font-size: 14px; color: #64748b; margin-top: 10px; margin-bottom: 0;">You can click on any dotted line to type a value before printing, or leave it blank to write by hand.</p>
    </div>
    <div class="ticket" style="--bg-logo: url('<?= htmlspecialchars($admin_logo) ?>');">
        <div class="ticket-header">
            <img src="<?= htmlspecialchars($admin_logo) ?>" alt="Max Diagnostic Center">
            <div class="header-content">
                <h2><?= htmlspecialchars($ticket_title) ?></h2>
                <div class="contact-info">
                    <?php if (!empty($ticket_address)): ?>
                        <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($ticket_address) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($ticket_phone)): ?>
                        <p><i class="fas fa-phone-alt"></i> <?= htmlspecialchars($ticket_phone) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="ticket-body">
            <div class="patient-bar">
                <span class="name editable-field" contenteditable="true"><?= htmlspecialchars($appointment['patient_name']) ?></span>
                <span class="id">ID: <?= htmlspecialchars($display_id) ?></span>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Age</span>
                    <span class="info-value editable-field" contenteditable="true"><?= empty($appointment['age']) ? '' : htmlspecialchars($appointment['age']) . ' Years' ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Gender</span>
                    <span class="info-value editable-field" contenteditable="true"><?= empty($appointment['gender']) ? '' : htmlspecialchars($appointment['gender']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Address</span>
                    <span class="info-value editable-field" contenteditable="true"><?= empty($appointment['address']) ? '' : htmlspecialchars($appointment['address']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Consulting Doctor</span>
                    <span class="info-value editable-field" contenteditable="true"><?= (empty($appointment['doctor']) || $appointment['doctor'] === 'Not Specified') ? '' : htmlspecialchars($appointment['doctor']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Department</span>
                    <span class="info-value editable-field" contenteditable="true"><?= (empty($appointment['department']) || $appointment['department'] === 'Not Specified') ? '' : htmlspecialchars($appointment['department']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Appointment Date</span>
                    <span class="info-value"><?= date('d M Y', strtotime($appointment['appointment_date'])) ?></span>
                </div>
            </div>
            <div class="prescription-section">
                <h4><i class="fas fa-file-prescription"></i> Prescription / Notes</h4>
                <div class="notes-area"></div>
                <div class="signature-area">Doctor's Signature</div>
            </div>
        </div>
        <div class="ticket-footer">
            <div class="qr-section qr-section-center">
                <?php
                $reports_url = trim($ticket_qr_link);
                if (empty($reports_url)) {
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                    $base_dir = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['PHP_SELF']))), '/');
                    $reports_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $base_dir . '/download-reports.php';
                }
                $qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=" . urlencode($reports_url);
                ?>
                <img src="<?= htmlspecialchars($qr_api_url) ?>" alt="QR Code" class="qr-img">
                <p class="qr-text">Scan to Download Reports</p>
            </div>
            <?php if (!empty(trim($ticket_footer_text))): ?>
                <p><?= nl2br(htmlspecialchars($ticket_footer_text)) ?></p>
            <?php endif; ?>
            <p class="print-time">Printed on: <?= date('d M Y, h:i A') ?></p>
        </div>
    </div>
    <script>
        window.addEventListener('load', function() {
            // Auto-print has been disabled to allow receptionists to manually edit empty fields first.
            // They can click the "Print Ticket" button when ready.
        });
    </script>
</body>
</html>
<?php
session_start();

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
$settings = [];
$settingsResult = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('ticket_title', 'ticket_footer_text', 'ticket_phone', 'ticket_address', 'admin_logo')");
if ($settingsResult) {
    while ($row = $settingsResult->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}
$ticket_title = $settings['ticket_title'] ?? 'Appointment Token';
$ticket_footer_text = $settings['ticket_footer_text'] ?? 'Please present this token at the reception desk.';
$ticket_phone = $settings['ticket_phone'] ?? '+91 7051674869';
$ticket_address = $settings['ticket_address'] ?? 'Opposite JK Bank, Hyderbeigh, Jammu & Kashmir 193121';
$admin_logo = isset($settings['admin_logo']) && !empty($settings['admin_logo']) ? '../' . $settings['admin_logo'] : '../assets/images/logo.png';
$admin_logo_v = $admin_logo . '?v=' . time(); // Cache-buster
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Ticket - <?= htmlspecialchars($appointment['patient_name']) ?></title>
    <link href="https://fonts.bunny.net/css?family=outfit:400,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/print-ticket.css">
</head>
<body>
    <div class="ticket" style="--bg-logo: url('<?= htmlspecialchars($admin_logo_v) ?>');">
        <div class="ticket-header">
            <img src="<?= htmlspecialchars($admin_logo_v) ?>" alt="Max Diagnostic Center">
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
                <span class="name"><?= htmlspecialchars($appointment['patient_name']) ?></span>
                <span class="id">ID: <?= htmlspecialchars($display_id) ?></span>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Age</span>
                    <span class="info-value"><?= htmlspecialchars($appointment['age'] ?? 'N/A') ?> Years</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Gender</span>
                    <span class="info-value"><?= htmlspecialchars($appointment['gender'] ?? 'N/A') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Address</span>
                    <span class="info-value"><?= htmlspecialchars($appointment['address'] ?? 'N/A') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Consulting Doctor</span>
                    <span class="info-value"><?= htmlspecialchars($appointment['doctor']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Department</span>
                    <span class="info-value"><?= htmlspecialchars($appointment['department']) ?></span>
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
            <p><?= nl2br(htmlspecialchars($ticket_footer_text)) ?></p>
            <p class="print-time">Printed on: <?= date('d M Y, h:i A') ?></p>
        </div>
    </div>
    <script>
        // Ensure fonts AND all images are fully loaded before opening the print dialog
        window.addEventListener('load', function() {
            if (document.fonts) {
                document.fonts.ready.then(function () {
                    // Slight delay to ensure browser paints the final image frame
                    setTimeout(function() { window.print(); }, 300);
                });
            } else {
                setTimeout(function() { window.print(); }, 500);
            }
        });
    </script>
</body>
</html>
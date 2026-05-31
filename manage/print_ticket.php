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
    <style>
        /* Base Page Styles */
        body { background-color: #f1f5f9; margin: 0; font-family: 'Outfit', sans-serif; color: #1e293b; }
        
        /* Sticky Top Bar */
        .top-action-bar {
            position: sticky; top: 0; z-index: 1000; display: flex; justify-content: space-between; align-items: center;
            background: white; padding: 15px 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-bottom: 1px solid #e2e8f0;
        }
        .top-action-bar h3 { margin: 0; color: #0f172a; font-size: 18px; }
        .top-action-bar p { margin: 5px 0 0 0; color: #64748b; font-size: 14px; }
        .btn-print {
            background: #00a39d; color: white; padding: 10px 24px; border: none; border-radius: 8px;
            font-size: 15px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
            transition: 0.3s; box-shadow: 0 4px 10px rgba(0, 163, 157, 0.2); font-family: 'Outfit', sans-serif;
        }
        .btn-print:hover { background: #008a85; transform: translateY(-2px); }

        /* Ticket Container */
        .ticket-container {
            max-width: 800px; margin: 40px auto; background: white; border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1); overflow: hidden; position: relative;
        }
        
        /* Watermark Background */
        .ticket-container::before {
            content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background-image: var(--bg-logo); background-repeat: no-repeat; background-position: center;
            background-size: 50%; opacity: 0.03; pointer-events: none; z-index: 0;
        }

        /* Ticket Header */
        .ticket-header {
            display: flex; justify-content: space-between; align-items: center; padding: 20px 30px;
            background: #f8fafc; border-bottom: 3px solid #00a39d; position: relative; z-index: 1;
        }
        .ticket-header img { max-height: 60px; }
        .header-info { text-align: right; }
        .header-info h2 { margin: 0 0 6px 0; color: #0b2b52; font-size: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
        .header-info p { margin: 0 0 3px 0; color: #475569; font-size: 12px; display: flex; align-items: center; justify-content: flex-end; gap: 8px; }
        .header-info i { color: #00a39d; }

        /* Ticket Body */
        .ticket-body { padding: 20px 30px; position: relative; z-index: 1; }
        
        /* Patient Bar */
        .patient-bar {
            display: flex; justify-content: space-between; align-items: center; background: #0b2b52;
            color: white; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px;
        }
        .patient-name { font-size: 16px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .patient-name i { opacity: 0.8; }
        .patient-id { font-family: monospace; font-size: 14px; background: rgba(255,255,255,0.15); padding: 4px 10px; border-radius: 6px; font-weight: 600; }

        /* Info Grid */
        .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
        .info-box { background: #f8fafc; padding: 8px 12px; border-radius: 6px; border: 1px solid #e2e8f0; }
        .info-box.highlight { background: #e7fffd; border-color: #ccfbf9; }
        .info-box.full-width { grid-column: span 3; }
        .info-box.two-thirds { grid-column: span 2; }
        
        .info-label { display: block; font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 600; margin-bottom: 2px; }
        .info-box.highlight .info-label { color: #007a75; }
        
        .info-value { font-size: 14px; font-weight: 500; color: #0f172a; width: 100%; display: block; }
        .info-box.highlight .info-value { color: #00a39d; font-weight: 600; font-size: 15px; }

        /* Prescription Area */
        .prescription-area { border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; min-height: 400px; position: relative; }
        .prescription-title { margin: 0 0 10px 0; color: #0b2b52; font-size: 15px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; }
        .prescription-title i { color: #00a39d; font-size: 18px; }
        
        .signature-box { position: absolute; bottom: 20px; right: 20px; text-align: center; width: 200px; }
        .signature-line { border-bottom: 1px solid #94a3b8; height: 40px; margin-bottom: 8px; }
        .signature-text { font-size: 12px; color: #64748b; font-weight: 500; }

        /* Ticket Footer */
        .ticket-footer {
            padding: 20px 30px; background: #f8fafc; border-top: 1px dashed #cbd5e1;
            display: flex; justify-content: space-between; align-items: flex-end; position: relative; z-index: 1;
        }
        .qr-widget { display: flex; align-items: center; gap: 12px; }
        .qr-widget img { width: 55px; height: 55px; border: 2px solid white; border-radius: 6px; box-shadow: 0 3px 10px rgba(0,0,0,0.08); }
        .qr-info h4 { margin: 0; font-size: 13px; font-weight: 700; color: #0b2b52; }
        .qr-info p { margin: 2px 0 0 0; font-size: 11px; color: #64748b; }
        
        .footer-text { text-align: right; }
        .footer-note { font-size: 12px; color: #475569; margin: 0 0 8px 0; font-weight: 500; max-width: 300px; line-height: 1.4; }
        .print-timestamp { font-size: 10px; color: #94a3b8; margin: 0; }

        /* Editable Fields */
        .editable-field { min-width: 60px; display: inline-block; border-bottom: 1px dashed #cbd5e1; outline: none; transition: 0.3s; }
        .editable-field:focus { background-color: #f8fafc; border-bottom-color: #00a39d; padding: 0 5px; }
        .patient-bar .editable-field { border-bottom-color: rgba(255,255,255,0.3); }
        .patient-bar .editable-field:focus { background-color: rgba(255,255,255,0.1); border-bottom-color: white; color: white; }

        /* Print Styles */
        @media print {
            @page { size: A4; margin: 0.5cm; }
            html, body { height: 100%; margin: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            body { background: white !important; padding: 0 !important; }
            .top-action-bar { display: none !important; }
            .ticket-container { box-shadow: none !important; margin: 0 !important; max-width: 100% !important; border: none !important; border-radius: 0 !important; height: 100vh; display: flex; flex-direction: column; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .ticket-container::before { opacity: 0.06 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .ticket-header { border-bottom: 3px solid #00a39d !important; background: transparent !important; padding: 15px 20px; flex-shrink: 0; }
            .ticket-body { flex-grow: 1; display: flex; flex-direction: column; padding: 15px 20px; }
            .patient-bar { background: #f1f5f9 !important; color: #0f172a !important; border: 1px solid #cbd5e1 !important; box-shadow: inset 0 0 0 1000px #f1f5f9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; padding: 10px 15px; margin-bottom: 15px; flex-shrink: 0; }
            .patient-name, .patient-name i { color: #0f172a !important; }
            .patient-id { background: white !important; color: #475569 !important; border: 1px solid #cbd5e1 !important; }
            .info-grid { gap: 8px; margin-bottom: 15px; flex-shrink: 0; }
            .info-box { background: white !important; border: 1px solid #cbd5e1 !important; box-shadow: inset 0 0 0 1000px white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; padding: 6px 10px; }
            .prescription-area { border: 1px solid #cbd5e1 !important; flex-grow: 1; min-height: 0 !important; }
            .ticket-footer { background: white !important; border-top: 2px solid #e2e8f0 !important; box-shadow: inset 0 0 0 1000px white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; padding: 15px 20px; flex-shrink: 0; }
            .editable-field:not(:empty) { border-bottom: none !important; padding: 0 !important; }
            .editable-field:empty { border-bottom: 1px dashed #cbd5e1 !important; }
        }
    </style>
</head>
<body>
    <!-- Top Action Bar -->
    <div class="top-action-bar">
        <div>
            <h3>Appointment Token</h3>
            <p>Click on any dotted line to type a value before printing, or leave blank to handwrite.</p>
        </div>
        <button class="btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Print Ticket
        </button>
    </div>

    <!-- Printable Ticket Area -->
    <div class="ticket-container" style="--bg-logo: url('<?= htmlspecialchars($admin_logo) ?>');">
        
        <div class="ticket-header">
            <img src="<?= htmlspecialchars($admin_logo) ?>" alt="Max Diagnostic Center">
            <div class="header-info">
                <h2><?= htmlspecialchars($ticket_title) ?></h2>
                <?php if (!empty($ticket_address)): ?>
                    <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($ticket_address) ?></p>
                <?php endif; ?>
                <?php if (!empty($ticket_phone)): ?>
                    <p><i class="fas fa-phone-alt"></i> <?= htmlspecialchars($ticket_phone) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="ticket-body">
            <div class="patient-bar">
                <span class="patient-name"><i class="fas fa-user-injured"></i> <span class="editable-field" contenteditable="true"><?= htmlspecialchars($appointment['patient_name']) ?></span></span>
                <span class="patient-id">ID: <?= htmlspecialchars($display_id) ?></span>
            </div>
            
            <div class="info-grid">
                <div class="info-box">
                    <span class="info-label">Age</span>
                    <span class="info-value editable-field" contenteditable="true"><?= empty($appointment['age']) ? '' : htmlspecialchars($appointment['age']) . ' Years' ?></span>
                </div>
                <div class="info-box">
                    <span class="info-label">Gender</span>
                    <span class="info-value editable-field" contenteditable="true"><?= empty($appointment['gender']) ? '' : htmlspecialchars($appointment['gender']) ?></span>
                </div>
                <div class="info-box">
                    <span class="info-label">Appointment Date</span>
                    <span class="info-value"><i class="far fa-calendar-alt" style="color: #00a39d; margin-right: 5px;"></i> <?= date('d M Y', strtotime($appointment['appointment_date'])) ?></span>
                </div>
                <div class="info-box full-width">
                    <span class="info-label">Address</span>
                    <span class="info-value editable-field" contenteditable="true"><?= empty($appointment['address']) ? '' : htmlspecialchars($appointment['address']) ?></span>
                </div>
                <div class="info-box highlight">
                    <span class="info-label">Department</span>
                    <span class="info-value editable-field" contenteditable="true"><?= (empty($appointment['department']) || $appointment['department'] === 'Not Specified') ? '' : htmlspecialchars($appointment['department']) ?></span>
                </div>
                <div class="info-box highlight two-thirds">
                    <span class="info-label">Consulting Doctor</span>
                    <span class="info-value"><i class="fas fa-user-md" style="opacity: 0.8; margin-right: 5px;"></i> <span class="editable-field" contenteditable="true"><?= (empty($appointment['doctor']) || $appointment['doctor'] === 'Not Specified') ? '' : htmlspecialchars($appointment['doctor']) ?></span></span>
                </div>
            </div>
            
            <div class="prescription-area">
                <h4 class="prescription-title"><i class="fas fa-file-prescription"></i> Prescription / Notes</h4>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <span class="signature-text">Doctor's Signature / Stamp</span>
                </div>
            </div>
        </div>
        
        <div class="ticket-footer">
            <div class="qr-widget">
                <?php
                $reports_url = trim($ticket_qr_link);
                if (empty($reports_url)) {
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                    $base_dir = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['PHP_SELF']))), '/');
                    $reports_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $base_dir . '/reports.php';
                }
                $qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=" . urlencode($reports_url);
                ?>
                <img src="<?= htmlspecialchars($qr_api_url) ?>" alt="QR Code">
                <div class="qr-info">
                    <h4>Download Reports</h4>
                    <p>Scan QR code</p>
                </div>
            </div>
            <div class="footer-text">
                <?php if (!empty(trim($ticket_footer_text))): ?>
                    <p class="footer-note"><?= nl2br(htmlspecialchars($ticket_footer_text)) ?></p>
                <?php endif; ?>
                <p class="print-timestamp">Printed on: <?= date('d M Y, h:i A') ?></p>
            </div>
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
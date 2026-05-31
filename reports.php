<?php
session_start();
require_once 'includes/db.php';

$reports_result = null;
$search_mobile = '';
$search_name = '';
$has_searched = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_reports'])) {
    $has_searched = true;
    $search_name = isset($_POST['first_name']) ? $conn->real_escape_string(strtolower(trim($_POST['first_name']))) : '';
    $search_mobile = $conn->real_escape_string(trim($_POST['mobile']));

    $reports_result = [];
    $tableCheck = $conn->query("SHOW TABLES LIKE 'reports'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        if ($search_name !== '') {
            $result = $conn->query("SELECT * FROM reports WHERE LOWER(patient_first_name) = '$search_name' AND mobile = '$search_mobile' ORDER BY uploaded_at DESC");
        } else {
            $result = $conn->query("SELECT * FROM reports WHERE mobile = '$search_mobile' ORDER BY uploaded_at DESC");
        }
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $reports_result[] = $row;
            }
        }
    }
}

include 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/reports.css">

<!-- ==========================
     PAGE HEADER
=========================== -->
<section class="page-header error-page-header">
    <div class="container">
        <h1>Download Reports</h1>
        <p>Access Your Diagnostic Test Results Online</p>
    </div>
</section>

<!-- ==========================
     REPORT SECTION
=========================== -->
<section class="report-section">

    <div class="container">

        <div class="report-box">

            <div class="report-left">

                <div class="report-icon">

                    <i class="fas fa-file-medical report-icon-inner"></i>

                </div>

                <div class="report-content">

                    <h3 class="report-title">Patient Report Access</h3>

                    <p class="report-desc">
                        Enter your Mobile Number
                        to download your reports.
                    </p>

                </div>

            </div>

            <div class="report-right">

                <form class="report-form" method="POST" action="reports.php#report-results">

                    <input
                        class="report-input"
                        type="text"
                        name="mobile"
                        value="<?= htmlspecialchars($search_mobile) ?>"
                        placeholder="Mobile Number" required>

                    <input
                        class="report-input"
                        type="text"
                        name="first_name"
                        value="<?= htmlspecialchars($search_name) ?>"
                        placeholder="First Name (Optional)">

                    <button class="report-submit" type="submit" name="search_reports">

                        Download Report

                    </button>

                </form>

            </div>

        </div>

    </div>

</section>

<?php if ($has_searched): ?>
<section id="report-results" class="reports-results-section">
    <div class="container reports-results-container">
        <h2 class="reports-results-title">Reports for <?= !empty($search_name) ? htmlspecialchars(ucwords($_POST['first_name'])) : htmlspecialchars($search_mobile) ?></h2>
        <?php if (count($reports_result) === 0): ?>
            <div class="reports-error-msg">
                No reports found for the provided details. Please check your spelling and try again.
            </div>
        <?php else: ?>
        <ul class="reports-list">
            <?php foreach ($reports_result as $report): ?>
                <li class="reports-list-item">
                    <div class="reports-item-header">
                        <span class="reports-item-meta">Tracking ID: <span class="reports-tracking-id"><?= htmlspecialchars($report['tracking_id'] ?? 'N/A') ?></span> &bull; Uploaded: <?= date('d M Y, h:i A', strtotime($report['uploaded_at'])) ?></span>
                    </div>
                    <?php 
                    $decoded = json_decode($report['report_file'], true);
                    $normalized_data = [];
                    if (is_array($decoded) && !empty($decoded)) {
                        if (isset($decoded[0]) && is_string($decoded[0])) {
                            $normalized_data[] = ['test_name' => $report['test_name'], 'files' => $decoded];
                        } elseif (isset($decoded[0]['test_name'])) {
                            $normalized_data = $decoded;
                        }
                    } elseif (!empty($report['report_file']) && !is_array($decoded)) {
                        $normalized_data[] = ['test_name' => $report['test_name'], 'files' => [$report['report_file']]];
                    } elseif (empty($decoded) && !empty($report['test_name'])) {
                        $normalized_data[] = ['test_name' => $report['test_name'], 'files' => []];
                    }
                    ?>
                    <div class="reports-test-list">
                        <?php foreach ($normalized_data as $test): ?>
                            <div class="reports-test-item">
                                <strong class="reports-test-name"><?= htmlspecialchars($test['test_name']) ?></strong>
                                <?php if (!empty($test['files'])): ?>
                                    <div class="reports-download-group">
                                        <?php foreach ($test['files'] as $idx => $file): ?>
                                            <a href="<?= htmlspecialchars($file) ?>" class="btn-primary report-download-btn" download>Download <?= count($test['files']) > 1 ? 'Part ' . ($idx + 1) : 'Report' ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="reports-pending-badge">Pending Upload</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($normalized_data)): ?>
                            <span class="reports-pending-msg">Report not available yet, please try later</span>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<!-- ==========================
     INFO SECTION
=========================== -->
<section class="reports-info reports-info-section">
    <div class="container">
        <div class="reports-info-header">
            <h2 class="reports-info-title">Secure & Fast Access</h2>
            <p class="reports-info-desc">We ensure your medical data is handled with the utmost privacy and security.</p>
        </div>
        <div class="reports-info-grid">
            <div class="reports-info-card">
                <div class="reports-info-icon reports-info-icon-lock">
                    <i class="fas fa-lock"></i>
                </div>
                <h3 class="reports-info-card-title">Secure Access</h3>
                <p class="reports-info-card-desc">Your reports are encrypted and strictly accessible only by verifying your registered credentials.</p>
            </div>
            <div class="reports-info-card">
                <div class="reports-info-icon reports-info-icon-bolt">
                    <i class="fas fa-bolt"></i>
                </div>
                <h3 class="reports-info-card-title">Instant Availability</h3>
                <p class="reports-info-card-desc">Download your results immediately as soon as they are published by our diagnostic laboratory.</p>
            </div>
            <div class="reports-info-card">
                <div class="reports-info-icon reports-info-icon-history">
                    <i class="fas fa-history"></i>
                </div>
                <h3 class="reports-info-card-title">Always Available</h3>
                <p class="reports-info-card-desc">Access your historical medical reports anytime, anywhere without needing to visit the clinic.</p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
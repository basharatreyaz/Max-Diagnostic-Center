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
<section class="page-header" style="background: var(--primary); padding: 60px 0; text-align: center; color: white;">
    <div class="container">
        <h1 style="margin: 0; font-size: 36px;">Download Reports</h1>
        <p style="margin-top: 10px; font-size: 18px; opacity: 0.9;">Access Your Diagnostic Test Results Online</p>
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
<section id="report-results" style="padding: 0 0 80px; background: var(--bg-light);">
    <div class="container" style="max-width: 800px; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,.05); margin: auto;">
        <h2 style="color: var(--secondary); margin-bottom: 20px;">Reports for <?= !empty($search_name) ? htmlspecialchars(ucwords($_POST['first_name'])) : htmlspecialchars($search_mobile) ?></h2>
        <?php if (count($reports_result) === 0): ?>
            <div style="background: #fef2f2; color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; text-align: center; border: 1px solid #fca5a5;">
                No reports found for the provided details. Please check your spelling and try again.
            </div>
        <?php else: ?>
        <ul style="list-style: none; padding: 0; margin: 0;">
            <?php foreach ($reports_result as $report): ?>
                <li style="display: flex; flex-direction: column; gap: 10px; padding: 15px; border-bottom: 1px solid var(--border); font-weight: 500;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 5px;">
                        <span style="font-size: 14px; color: var(--text-muted);">Tracking ID: <span style="font-family: monospace; font-weight: 600; color: var(--primary);"><?= htmlspecialchars($report['tracking_id'] ?? 'N/A') ?></span> &bull; Uploaded: <?= date('d M Y, h:i A', strtotime($report['uploaded_at'])) ?></span>
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
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($normalized_data as $test): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 12px 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                <strong style="color: var(--secondary); font-size: 15px;"><?= htmlspecialchars($test['test_name']) ?></strong>
                                <?php if (!empty($test['files'])): ?>
                                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                        <?php foreach ($test['files'] as $idx => $file): ?>
                                            <a href="<?= htmlspecialchars($file) ?>" class="report-download-btn" download style="padding: 8px 15px; font-size: 13px;">Download <?= count($test['files']) > 1 ? 'Part ' . ($idx + 1) : 'Report' ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: #ef4444; font-size: 12px; font-weight: 600; background: #fef2f2; padding: 6px 12px; border-radius: 6px; border: 1px solid #fca5a5;">Pending Upload</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($normalized_data)): ?>
                            <span style="color: #ef4444; font-size: 13px; font-weight: 600; background: #fef2f2; padding: 8px 12px; border-radius: 6px; border: 1px solid #fca5a5; display: inline-block; width: fit-content;">Report not available yet, please try later</span>
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
<section class="reports-info" style="padding: 80px 0;">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <h2 style="color: var(--secondary); margin-bottom: 15px; font-size: 32px;">Secure & Fast Access</h2>
            <p style="color: var(--text-muted); font-size: 16px; max-width: 600px; margin: auto;">We ensure your medical data is handled with the utmost privacy and security.</p>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
            <div style="background: white; padding: 30px; border-radius: 12px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.05); border: 1px solid var(--border);">
                <div style="width: 60px; height: 60px; background: #e0f2fe; color: #0284c7; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 20px;">
                    <i class="fas fa-lock"></i>
                </div>
                <h3 style="color: var(--secondary); margin-bottom: 15px; font-size: 20px;">Secure Access</h3>
                <p style="color: var(--text-muted); font-size: 15px; margin: 0; line-height: 1.6;">Your reports are encrypted and strictly accessible only by verifying your registered credentials.</p>
            </div>
            <div style="background: white; padding: 30px; border-radius: 12px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.05); border: 1px solid var(--border);">
                <div style="width: 60px; height: 60px; background: #ecfdf5; color: #059669; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 20px;">
                    <i class="fas fa-bolt"></i>
                </div>
                <h3 style="color: var(--secondary); margin-bottom: 15px; font-size: 20px;">Instant Availability</h3>
                <p style="color: var(--text-muted); font-size: 15px; margin: 0; line-height: 1.6;">Download your results immediately as soon as they are published by our diagnostic laboratory.</p>
            </div>
            <div style="background: white; padding: 30px; border-radius: 12px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.05); border: 1px solid var(--border);">
                <div style="width: 60px; height: 60px; background: #fef3c7; color: #d97706; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 20px;">
                    <i class="fas fa-history"></i>
                </div>
                <h3 style="color: var(--secondary); margin-bottom: 15px; font-size: 20px;">Always Available</h3>
                <p style="color: var(--text-muted); font-size: 15px; margin: 0; line-height: 1.6;">Access your historical medical reports anytime, anywhere without needing to visit the clinic.</p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
<?php
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = isset($_POST['first_name']) ? strtolower(trim($_POST['first_name'])) : '';
    $mobile = trim($_POST['mobile']);

    $reports = [];
    $tableCheck = $conn->query("SHOW TABLES LIKE 'reports'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $stmt = $conn->prepare("SELECT * FROM reports WHERE LOWER(patient_first_name) = ? AND mobile = ? ORDER BY uploaded_at DESC");
        $stmt->bind_param("ss", $first_name, $mobile);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $reports[] = $row;
            }
        }
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Reports - Max Diagnostic Center</title>
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700,800" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/download-report.css">
</head>
<body>
 
    <div class="container">
        <h2>Reports for <?= !empty($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : htmlspecialchars($mobile) ?></h2>
        <?php if (count($reports) === 0): ?>
            <div class="reports-error-msg">
                No reports found for the provided details. Please check your spelling and try again.
            </div>
        <?php else: ?>
        <ul class="report-list">
            <?php foreach ($reports as $report): ?>
                <li class="report-item download-report-list-item">
                    <div class="download-report-header">
                        <span class="download-report-meta">Tracking ID: <span class="download-report-tracking-id"><?= htmlspecialchars($report['tracking_id'] ?? 'N/A') ?></span> &bull; Uploaded: <?= date('d M Y, h:i A', strtotime($report['uploaded_at'])) ?></span>
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
                    <div class="download-report-test-list">
                        <?php foreach ($normalized_data as $test): ?>
                            <div class="download-report-test-item">
                                <strong class="download-report-test-name"><?= htmlspecialchars($test['test_name']) ?></strong>
                                <?php if (!empty($test['files'])): ?>
                                    <div class="download-report-download-group">
                                        <?php foreach ($test['files'] as $idx => $file): ?>
                                            <a href="<?= htmlspecialchars($file) ?>" class="download-btn download-report-download-btn" download>Download <?= count($test['files']) > 1 ? 'Part ' . ($idx + 1) : 'Report' ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="download-report-pending-badge">Pending Upload</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($normalized_data)): ?>
                            <span class="download-report-pending-msg">Report not available yet, please try later</span>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <br>
        <a href="index.php" class="download-report-back-link">&larr; Back to Home</a>
    </div>
</body>
</html>
<?php } else { header("Location: index.php"); } ?>
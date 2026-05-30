<?php
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = isset($_POST['first_name']) ? strtolower(trim($_POST['first_name'])) : '';
    $mobile = trim($_POST['mobile']);

    $reports = [];
    $tableCheck = $conn->query("SHOW TABLES LIKE 'reports'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        if ($first_name !== '') {
            $stmt = $conn->prepare("SELECT * FROM reports WHERE LOWER(patient_first_name) = ? AND mobile = ? ORDER BY uploaded_at DESC");
            $stmt->bind_param("ss", $first_name, $mobile);
        } else {
            $stmt = $conn->prepare("SELECT * FROM reports WHERE mobile = ? ORDER BY uploaded_at DESC");
            $stmt->bind_param("s", $mobile);
        }
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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/download-report.css">
</head>
<body>
 
    <div class="container">
        <h2>Reports for <?= !empty($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : htmlspecialchars($mobile) ?></h2>
        <?php if (count($reports) === 0): ?>
            <div style="background: #fef2f2; color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; text-align: center; border: 1px solid #fca5a5;">
                No reports found for the provided details. Please check your spelling and try again.
            </div>
        <?php else: ?>
        <ul class="report-list">
            <?php foreach ($reports as $report): ?>
                <li class="report-item" style="flex-direction: column; align-items: flex-start; gap: 15px;">
                    <div style="width: 100%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 5px;">
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
                    <div style="width: 100%; display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($normalized_data as $test): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 12px 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                <strong style="color: var(--secondary); font-size: 15px;"><?= htmlspecialchars($test['test_name']) ?></strong>
                                <?php if (!empty($test['files'])): ?>
                                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                        <?php foreach ($test['files'] as $idx => $file): ?>
                                            <a href="<?= htmlspecialchars($file) ?>" class="download-btn" download style="padding: 8px 15px; font-size: 13px;">Download <?= count($test['files']) > 1 ? 'Part ' . ($idx + 1) : 'Report' ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: #ef4444; font-size: 12px; font-weight: 600; background: #fef2f2; padding: 6px 12px; border-radius: 6px; border: 1px solid #fca5a5;">Pending Upload</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($normalized_data)): ?>
                            <span style="color: #ef4444; font-size: 13px; font-weight: 600; background: #fef2f2; padding: 8px 12px; border-radius: 6px; border: 1px solid #fca5a5; display: inline-block;">Report not available yet, please try later</span>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <br>
        <a href="index.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">&larr; Back to Home</a>
    </div>
</body>
</html>
<?php } else { header("Location: index.php"); } ?>
<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['admin_permissions']) || ($_SESSION['admin_permissions'] !== 'all' && !in_array('reports', (array)$_SESSION['admin_permissions']))) {
    header("Location: dashboard.php");
    exit;
}

require_once '../includes/db.php';

// Check if table exists, if not create
$tableCheck = $conn->query("SHOW TABLES LIKE 'reports'");
if ($tableCheck && $tableCheck->num_rows == 0) {
    $sql = "CREATE TABLE reports (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        tracking_id VARCHAR(50) UNIQUE,
        patient_first_name VARCHAR(100) NOT NULL,
        mobile VARCHAR(20) NOT NULL,
        test_name VARCHAR(255) NOT NULL,
        report_file TEXT,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
} else {
    // Add test_name column if it doesn't exist to support the new feature on existing tables
    $columnCheck = $conn->query("SHOW COLUMNS FROM reports LIKE 'test_name'");
    if ($columnCheck && $columnCheck->num_rows == 0) {
        $conn->query("ALTER TABLE reports ADD COLUMN test_name VARCHAR(255) NOT NULL AFTER mobile");
    }

    // Add tracking_id column if it doesn't exist
    $columnCheck2 = $conn->query("SHOW COLUMNS FROM reports LIKE 'tracking_id'");
    if ($columnCheck2 && $columnCheck2->num_rows == 0) {
        $conn->query("ALTER TABLE reports ADD COLUMN tracking_id VARCHAR(50) UNIQUE AFTER id");
        // Populate existing rows to ensure they don't break the new layout
        $existing = $conn->query("SELECT id FROM reports WHERE tracking_id IS NULL");
        if ($existing && $existing->num_rows > 0) {
            while ($row = $existing->fetch_assoc()) {
                $tid = 'REP-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
                $conn->query("UPDATE reports SET tracking_id = '$tid' WHERE id = " . $row['id']);
            }
        }
    }
    
    // Ensure report_file can hold multiple files (TEXT)
    $columnInfo = $conn->query("SHOW COLUMNS FROM reports WHERE Field = 'report_file'");
    if ($columnInfo) {
        $columnType = $columnInfo->fetch_assoc()['Type'];
        if (strtolower($columnType) != 'text') {
            $conn->query("ALTER TABLE reports MODIFY COLUMN report_file TEXT");
        }
    }
}

// Handle Add/Update Report
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_report'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $mobile = $conn->real_escape_string($_POST['mobile']);
    
    $final_data = [];
    $all_test_names = [];

    $allowedReportTypes = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    // Process Old Tests
    if (isset($_POST['old_test_names'])) {
        foreach ($_POST['old_test_names'] as $index => $old_tname) {
            if (isset($_POST['remove_tests']) && in_array($index, $_POST['remove_tests'])) {
                if (isset($_POST['old_test_files'][$index])) {
                    foreach ($_POST['old_test_files'][$index] as $ofile) {
                        if (file_exists("../" . $ofile)) unlink("../" . $ofile);
                    }
                }
                continue;
            }

            $current_files = [];
            if (isset($_POST['old_test_files'][$index])) {
                foreach ($_POST['old_test_files'][$index] as $ofile) {
                    if (isset($_POST['remove_files']) && in_array($ofile, $_POST['remove_files'])) {
                        if (file_exists("../" . $ofile)) unlink("../" . $ofile);
                    } else {
                        $current_files[] = $ofile;
                    }
                }
            }

            $file_input_name = "old_report_files_" . $index;
            if (isset($_FILES[$file_input_name]) && !empty($_FILES[$file_input_name]['name'][0])) {
                $targetDir = "../assets/reports/";
                if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                foreach ($_FILES[$file_input_name]['tmp_name'] as $fkey => $tmp_name) {
                    if ($_FILES[$file_input_name]['error'][$fkey] == 0) {
                        $originalName = $_FILES[$file_input_name]["name"][$fkey];
                        $size = $_FILES[$file_input_name]["size"][$fkey];
                        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                        if (array_key_exists($ext, $allowedReportTypes)) {
                            $fileName = bin2hex(random_bytes(16)) . '.' . $ext;
                            if (move_uploaded_file($tmp_name, $targetDir . $fileName)) $current_files[] = "assets/reports/" . $fileName;
                        }
                    }
                }
            }

            if (trim($old_tname) !== '') {
                $final_data[] = ['test_name' => trim($old_tname), 'files' => $current_files];
                $all_test_names[] = trim($old_tname);
            }
        }
    }

    // Process New Tests
    if (isset($_POST['new_test_names'])) {
        foreach ($_POST['new_test_names'] as $index => $new_tname) {
            if (trim($new_tname) === '') continue;
            $current_files = [];
            $file_input_name = "new_report_files_" . $index;
            if (isset($_FILES[$file_input_name]) && !empty($_FILES[$file_input_name]['name'][0])) {
                $targetDir = "../assets/reports/";
                if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                foreach ($_FILES[$file_input_name]['tmp_name'] as $fkey => $tmp_name) {
                    if ($_FILES[$file_input_name]['error'][$fkey] == 0) {
                        $originalName = $_FILES[$file_input_name]["name"][$fkey];
                        $size = $_FILES[$file_input_name]["size"][$fkey];
                        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                        if (array_key_exists($ext, $allowedReportTypes)) {
                            $fileName = bin2hex(random_bytes(16)) . '.' . $ext;
                            if (move_uploaded_file($tmp_name, $targetDir . $fileName)) $current_files[] = "assets/reports/" . $fileName;
                        }
                    }
                }
            }
            $final_data[] = ['test_name' => trim($new_tname), 'files' => $current_files];
            $all_test_names[] = trim($new_tname);
        }
    }

    $filePathStr = empty($final_data) ? '' : $conn->real_escape_string(json_encode($final_data));
    $combined_test_names = $conn->real_escape_string(implode(', ', $all_test_names));

    if (isset($_POST['report_id']) && !empty($_POST['report_id'])) {
        $id = (int)$_POST['report_id'];
        $conn->query("UPDATE reports SET patient_first_name='$first_name', mobile='$mobile', test_name='$combined_test_names', report_file='$filePathStr' WHERE id=$id");
    } else {
        $tracking_id = 'REP-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        $conn->query("INSERT INTO reports (tracking_id, patient_first_name, mobile, test_name, report_file) VALUES ('$tracking_id', '$first_name', '$mobile', '$combined_test_names', '$filePathStr')");
    }
    
    header("Location: reports.php");
    exit;
}

// Handle Delete Report
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_report'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    $id = (int)$_POST['id'];
    
    // Fetch file path to delete file from server
    $result = $conn->query("SELECT report_file FROM reports WHERE id = $id");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['report_file'])) {
            $decoded = json_decode($row['report_file'], true);
            $filesToDelete = [];
            if (is_array($decoded)) {
                if (isset($decoded[0]) && is_string($decoded[0])) {
                    $filesToDelete = $decoded;
                } elseif (isset($decoded[0]['test_name'])) {
                    foreach ($decoded as $t) {
                        if (isset($t['files']) && is_array($t['files'])) {
                            $filesToDelete = array_merge($filesToDelete, $t['files']);
                        }
                    }
                }
            } elseif (is_string($row['report_file'])) {
                $filesToDelete[] = $row['report_file'];
            }
            foreach ($filesToDelete as $file) {
                $filePathToDelete = "../" . $file;
                if (file_exists($filePathToDelete) && is_file($filePathToDelete)) {
                    unlink($filePathToDelete);
                }
            }
        }
    }
    
    $conn->query("DELETE FROM reports WHERE id = $id");
    header("Location: reports.php");
    exit;
}

$conditions = [];

if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $searchTerm = $conn->real_escape_string(trim($_GET['search']));
    $conditions[] = "(patient_first_name LIKE '%$searchTerm%' OR mobile LIKE '%$searchTerm%' OR tracking_id LIKE '%$searchTerm%')";
}

if (isset($_GET['date']) && trim($_GET['date']) !== '') {
    $dateTerm = $conn->real_escape_string(trim($_GET['date']));
    $conditions[] = "DATE(uploaded_at) = '$dateTerm'";
}

$searchQuery = "";
if (count($conditions) > 0) {
    $searchQuery = " WHERE " . implode(" AND ", $conditions);
}

list($reports, $totalPages, $page) = getPaginatedResults(
    $conn,
    "SELECT COUNT(*) as total FROM reports $searchQuery",
    "SELECT * FROM reports $searchQuery ORDER BY uploaded_at DESC"
);

// Fetch report for editing if requested
$edit_report = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $edit_result = $conn->query("SELECT * FROM reports WHERE id = $edit_id");
    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_report = $edit_result->fetch_assoc();
    }
}

$show_form = isset($_GET['action']) && $_GET['action'] == 'add' || isset($_GET['edit_id']);

$settings = getCachedSettings($conn);
$favicon = $settings['favicon'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Reports</title>
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700,800" rel="stylesheet">
    <?php if (!empty($favicon)): ?>
        <link rel="icon" href="../<?= htmlspecialchars($favicon) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-reports.css">
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="admin-container">
            <div class="admin-header">
                <h2><i class="fas fa-file-medical"></i> <?= $show_form ? ($edit_report ? 'Edit Report' : 'Add Report') : 'Manage Reports' ?></h2>
                <?php if (!$show_form): ?>
                    <a href="?action=add" class="btn-primary" style="text-decoration: none;"><i class="fas fa-plus"></i> Add Report</a>
                <?php endif; ?>
            </div>
            
            <?php if ($show_form): ?>
            <div class="card">
                <h3><i class="fas fa-<?= $edit_report ? 'edit' : 'upload' ?>"></i> <?= $edit_report ? 'Edit Patient Details' : 'Add Patient Report Details' ?></h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <?php if ($edit_report): ?>
                        <input type="hidden" name="report_id" value="<?= $edit_report['id'] ?>">
                    <?php endif; ?>
                    <div class="form-grid">
                        <input type="text" name="first_name" placeholder="Patient First Name" value="<?= $edit_report ? htmlspecialchars($edit_report['patient_first_name']) : '' ?>" required>
                        <input type="text" name="mobile" placeholder="Mobile Number" value="<?= $edit_report ? htmlspecialchars($edit_report['mobile']) : '' ?>" required>
                    </div>

                    <div class="diagnostic-wrapper">
                        <h4 style="color: var(--secondary); margin-top: 0; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                            <span><i class="fas fa-vial"></i> Diagnostic Tests</span>
                            <button type="button" onclick="addTest()" class="btn-primary add-test-btn"><i class="fas fa-plus"></i> Add Test</button>
                        </h4>
                        
                        <div id="tests-container">
                            <?php if ($edit_report): ?>
                                <?php 
                                $decoded = json_decode($edit_report['report_file'], true);
                                $normalized_data = [];
                                if (is_array($decoded) && !empty($decoded)) {
                                    if (isset($decoded[0]) && is_string($decoded[0])) {
                                        $normalized_data[] = ['test_name' => $edit_report['test_name'], 'files' => $decoded];
                                    } elseif (isset($decoded[0]['test_name'])) {
                                        $normalized_data = $decoded;
                                    }
                                } elseif (!empty($edit_report['report_file']) && !is_array($decoded)) {
                                    $normalized_data[] = ['test_name' => $edit_report['test_name'], 'files' => [$edit_report['report_file']]];
                                } elseif (empty($decoded) && !empty($edit_report['test_name'])) {
                                    $normalized_data[] = ['test_name' => $edit_report['test_name'], 'files' => []];
                                }
                                
                                foreach ($normalized_data as $idx => $test_data): 
                                ?>
                                <div class="test-group">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                        <h5 style="margin:0; color: var(--secondary); font-size: 15px;">Test <?= $idx + 1 ?></h5>
                                        <label style="color: #ef4444; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                            <input type="checkbox" name="remove_tests[]" value="<?= $idx ?>"> Remove Test
                                        </label>
                                    </div>
                                    <div class="form-grid" style="margin-bottom: 15px;">
                                        <input type="text" name="old_test_names[<?= $idx ?>]" value="<?= htmlspecialchars($test_data['test_name']) ?>" placeholder="Test Name" required>
                                        <input type="file" name="old_report_files_<?= $idx ?>[]" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="padding: 12px 15px;">
                                    </div>
                                    
                                    <?php if (!empty($test_data['files'])): ?>
                                        <p style="margin: 0 0 10px; font-weight: 600; color: var(--text-muted); font-size: 13px;">Attached Files (Check to remove):</p>
                                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                            <?php foreach ($test_data['files'] as $fidx => $file): ?>
                                                <label class="remove-file-label">
                                                    <input type="hidden" name="old_test_files[<?= $idx ?>][]" value="<?= htmlspecialchars($file) ?>">
                                                    <input type="checkbox" name="remove_files[]" value="<?= htmlspecialchars($file) ?>">
                                                    File <?= $fidx + 1 ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="test-group">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                        <h5 style="margin:0; color: var(--secondary); font-size: 15px;">Test 1</h5>
                                    </div>
                                    <div class="form-grid" style="margin-bottom: 15px;">
                                        <input type="text" name="new_test_names[0]" placeholder="Test Name (e.g., Blood Test)" required>
                                        <input type="file" name="new_report_files_0[]" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="padding: 12px 15px;">
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="display: flex; gap: 15px; margin-top: 10px;">
                        <button type="submit" name="save_report" class="btn-primary" style="font-family: 'Outfit', sans-serif;"><i class="fas fa-save"></i> <?= $edit_report ? 'Update Report' : 'Save Details' ?></button>
                        <a href="reports.php" style="background: #f1f5f9; color: #64748b; padding: 14px 25px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s;"><i class="fas fa-times"></i> Cancel</a>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list-ul"></i> Uploaded Reports</h3>
                    <form method="GET" class="filter-form">
                        <input type="text" name="search" placeholder="Search Name, Mobile, or ID..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" class="filter-input">
                        <input type="date" name="date" value="<?= isset($_GET['date']) ? htmlspecialchars($_GET['date']) : '' ?>" class="filter-select filter-date">
                        <button type="submit" class="btn-primary btn-filter"><i class="fas fa-filter"></i> Filter</button>
                        <?php if ((isset($_GET['search']) && trim($_GET['search']) !== '') || (isset($_GET['date']) && trim($_GET['date']) !== '')): ?>
                            <a href="reports.php" class="btn-primary btn-clear"><i class="fas fa-times"></i> Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead><tr><th>Tracking ID</th><th>Patient Name</th><th>Mobile</th><th>Test Name</th><th>Upload Date</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php if ($reports && $reports->num_rows > 0): while ($row = $reports->fetch_assoc()): ?>
                                <tr><td><span style="font-family: monospace; font-weight: 600; color: var(--primary);"><?= htmlspecialchars($row['tracking_id'] ?? 'N/A') ?></span></td><td><strong><?= htmlspecialchars($row['patient_first_name']) ?></strong></td><td><?= htmlspecialchars($row['mobile']) ?></td><td><span class="badge"><?= htmlspecialchars($row['test_name'] ?? 'N/A') ?></span></td><td><?= date('d M Y, h:i A', strtotime($row['uploaded_at'])) ?></td>
                                    <td><div class="action-buttons">
                                        <a href="?edit_id=<?= $row['id'] ?>" class="edit-btn"><i class="fas fa-edit"></i> Edit</a>
                                        <?php if (!empty($row['report_file'])): ?>
                                            <?php 
                                            $decoded = json_decode($row['report_file'], true);
                                            $normalized_data = [];
                                            if (is_array($decoded) && !empty($decoded)) {
                                                if (isset($decoded[0]) && is_string($decoded[0])) { $normalized_data[] = ['files' => $decoded]; }
                                                elseif (isset($decoded[0]['test_name'])) { $normalized_data = $decoded; }
                                            } elseif (!empty($row['report_file']) && !is_array($decoded)) {
                                                $normalized_data[] = ['files' => [$row['report_file']]];
                                            }
                                            $file_count = 0;
                                            foreach ($normalized_data as $t) {
                                                if (isset($t['files']) && is_array($t['files'])) {
                                                    foreach ($t['files'] as $f) {
                                                        $file_count++;
                                                        echo '<a href="../' . htmlspecialchars($f) . '" class="view-btn" target="_blank"><i class="fas fa-eye"></i> View ' . $file_count . '</a>';
                                                    }
                                                }
                                            }
                                            if ($file_count === 0): ?>
                                                <span class="badge" style="background: #fef2f2; color: #ef4444; border: 1px solid #fca5a5;">Pending Upload</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge" style="background: #fef2f2; color: #ef4444; border: 1px solid #fca5a5;">Pending Upload</span>
                                        <?php endif; ?>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this report?');" style="margin: 0;"><input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>"><input type="hidden" name="id" value="<?= $row['id'] ?>"><button type="submit" name="delete_report" class="delete-btn"><i class="fas fa-trash-alt"></i> Delete</button></form>
                                    </div></td></tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="6" style="text-align: center; padding: 40px; color: #777;">No reports uploaded yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php renderPaginationLinks($totalPages, $page); ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
    <script>
    let newTestCount = 1;
    function addTest() {
        const container = document.getElementById('tests-container');
        const div = document.createElement('div');
        div.className = 'test-group';
        div.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h5 style="margin:0; color: var(--secondary); font-size: 15px;">New Test</h5>
                <button type="button" onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: #ef4444; cursor: pointer; font-weight: 600; font-family: 'Outfit', sans-serif;"><i class="fas fa-times"></i> Remove</button>
            </div>
            <div class="form-grid" style="margin-bottom: 15px;">
                <input type="text" name="new_test_names[${newTestCount}]" placeholder="Test Name (e.g., Blood Test)" required>
                <input type="file" name="new_report_files_${newTestCount}[]" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="padding: 12px 15px;">
            </div>
        `;
        container.appendChild(div);
        newTestCount++;
    }
    </script>
</body>
</html>
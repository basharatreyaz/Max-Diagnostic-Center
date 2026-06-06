<?php
session_start();
date_default_timezone_set('Asia/Kolkata');

// Ensure the user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['admin_permissions']) || ($_SESSION['admin_permissions'] !== 'all' && !in_array('patients', (array)$_SESSION['admin_permissions']))) {
    header("Location: dashboard.php");
    exit;
}

require_once '../includes/db.php';

// Check if table exists, if not create
$tableCheck = $conn->query("SHOW TABLES LIKE 'patients'");
if ($tableCheck && $tableCheck->num_rows == 0) {
    $sql = "CREATE TABLE patients (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        age INT(3) DEFAULT NULL,
        gender VARCHAR(20) DEFAULT NULL,
        address TEXT,
        doctor_appointed VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
} else {
    // Add age column if it doesn't exist
    $columnCheck = $conn->query("SHOW COLUMNS FROM patients LIKE 'age'");
    if ($columnCheck && $columnCheck->num_rows == 0) {
        $conn->query("ALTER TABLE patients ADD COLUMN age INT(3) DEFAULT NULL AFTER phone");
    }
    // Add gender column if it doesn't exist
    $columnCheck2 = $conn->query("SHOW COLUMNS FROM patients LIKE 'gender'");
    if ($columnCheck2 && $columnCheck2->num_rows == 0) {
        $conn->query("ALTER TABLE patients ADD COLUMN gender VARCHAR(20) DEFAULT NULL AFTER age");
    }
}

// PRG message
$patient_msg = '';
if (isset($_SESSION['patient_msg'])) {
    $patient_msg = $_SESSION['patient_msg'];
    unset($_SESSION['patient_msg']);
}

// Handle Add Patient
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_patient'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    
    $name = $conn->real_escape_string(trim($_POST['name']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $age = isset($_POST['age']) && trim($_POST['age']) !== '' ? (int)$_POST['age'] : null;
    $gender = isset($_POST['gender']) && trim($_POST['gender']) !== '' ? $conn->real_escape_string(trim($_POST['gender'])) : null;
    $address = $conn->real_escape_string(trim($_POST['address']));
    $doctor_appointed = $conn->real_escape_string(trim($_POST['doctor_appointed']));
    
    $check = $conn->query("SELECT id FROM patients WHERE phone = '$phone' AND LOWER(name) = LOWER('$name')");
    if ($check && $check->num_rows > 0) {
        $_SESSION['patient_msg'] = "Patient already exists in the system!";
    } else {
        $stmt = $conn->prepare("INSERT INTO patients (name, phone, age, gender, address, doctor_appointed) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisss", $name, $phone, $age, $gender, $address, $doctor_appointed);
        $stmt->execute();
        
        $_SESSION['patient_msg'] = "Patient details added successfully!";
    }

    header("Location: patients.php");
    exit;
}

// Handle Update Patient
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_patient'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    
    $id = (int)$_POST['patient_id'];
    $name = $conn->real_escape_string(trim($_POST['name']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $age = isset($_POST['age']) && trim($_POST['age']) !== '' ? (int)$_POST['age'] : null;
    $gender = isset($_POST['gender']) && trim($_POST['gender']) !== '' ? $conn->real_escape_string(trim($_POST['gender'])) : null;
    $address = $conn->real_escape_string(trim($_POST['address']));
    $doctor_appointed = $conn->real_escape_string(trim($_POST['doctor_appointed']));
    
    $stmt = $conn->prepare("UPDATE patients SET name=?, phone=?, age=?, gender=?, address=?, doctor_appointed=? WHERE id=?");
    $stmt->bind_param("ssisssi", $name, $phone, $age, $gender, $address, $doctor_appointed, $id);
    $stmt->execute();
    
    $_SESSION['patient_msg'] = "Patient details updated successfully!";
    header("Location: patients.php");
    exit;
}

// Handle Delete Patient
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_patient'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM patients WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $_SESSION['patient_msg'] = "Patient record deleted successfully!";
    header("Location: patients.php");
    exit;
}

$searchQuery = "";
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $searchTerm = $conn->real_escape_string(trim($_GET['search']));
    $searchQuery = " WHERE name LIKE '%$searchTerm%' OR phone LIKE '%$searchTerm%' OR doctor_appointed LIKE '%$searchTerm%'";
}

// Handle CSV Export
if (isset($_GET['export_csv']) && $_GET['export_csv'] == '1') {
    $exportQuery = "SELECT * FROM patients $searchQuery ORDER BY created_at DESC";
    $exportResult = $conn->query($exportQuery);
    
    if ($exportResult) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=patients_export_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Patient Name', 'Phone', 'Age', 'Gender', 'Address', 'Doctor Appointed', 'Date Added']);
        
        while ($row = $exportResult->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['name'],
                $row['phone'],
                $row['age'] ?? 'N/A',
                $row['gender'] ?? 'N/A',
                $row['address'],
                $row['doctor_appointed'],
                date('d M Y', strtotime($row['created_at']))
            ]);
        }
        fclose($output);
        exit;
    }
}

list($patients, $totalPages, $page) = getPaginatedResults(
    $conn,
    "SELECT COUNT(*) as total FROM patients $searchQuery",
    "SELECT * FROM patients $searchQuery ORDER BY created_at DESC"
);

// Fetch patient for editing if requested
$edit_patient = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $edit_result = $conn->query("SELECT * FROM patients WHERE id = $edit_id");
    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_patient = $edit_result->fetch_assoc();
    }
}

// Fetch appointment history if requested
$view_patient = null;
$patient_appointments = null;
if (isset($_GET['view_id'])) {
    $view_id = (int)$_GET['view_id'];
    $view_result = $conn->query("SELECT * FROM patients WHERE id = $view_id");
    if ($view_result && $view_result->num_rows > 0) {
        $view_patient = $view_result->fetch_assoc();
        $p_phone = $conn->real_escape_string($view_patient['phone']);
        $p_name = $conn->real_escape_string($view_patient['name']);
        $patient_appointments = $conn->query("SELECT * FROM appointments WHERE mobile = '$p_phone' AND LOWER(patient_name) = LOWER('$p_name') ORDER BY appointment_date DESC");
    }
}

$show_form = isset($_GET['action']) && $_GET['action'] == 'add' || isset($_GET['edit_id']);
$show_view = isset($_GET['view_id']) && $view_patient;

// Fetch distinct doctors for dropdown
$doctorsResult = $conn->query("SELECT name FROM doctors WHERE is_active = 1 ORDER BY name ASC");
$doctorsList = [];
if ($doctorsResult && $doctorsResult->num_rows > 0) {
    while ($doc = $doctorsResult->fetch_assoc()) {
        $doctorsList[] = $doc['name'];
    }
}

$favicon = '';
$favResult = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'favicon'");
if ($favResult && $favResult->num_rows > 0) {
    $favicon = $favResult->fetch_assoc()['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Patients</title>
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700,800" rel="stylesheet">
    <?php if (!empty($favicon)): ?>
        <link rel="icon" href="../<?= htmlspecialchars($favicon) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-sidebar.css">
    <link rel="stylesheet" href="../assets/css/admin-doctors.css"> <!-- Restores table and card layouts -->
    <link rel="stylesheet" href="../assets/css/admin-custom.css">
    <link rel="stylesheet" href="../assets/css/admin-responsive.css">
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="admin-container">
            <div class="admin-header">
                <h2><i class="fas fa-users"></i> <?= $show_form ? ($edit_patient ? 'Edit Patient Details' : 'Add Patient Details') : ($show_view ? 'Patient History' : 'Manage Patients') ?></h2>
                <?php if (!$show_form && !$show_view): ?>
                    <a href="?action=add" class="btn-primary"><i class="fas fa-plus"></i> Add Patient</a>
                <?php endif; ?>
            </div>

        <?php if (!empty($patient_msg)): ?>
            <div class="success-msg" style="margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($patient_msg) ?>
            </div>
        <?php endif; ?>

            <?php if ($show_form): ?>
            <div class="card">
                <h3><i class="fas fa-<?= $edit_patient ? 'edit' : 'plus-circle' ?>"></i> <?= $edit_patient ? 'Edit Patient' : 'Add New Patient' ?></h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <?php if ($edit_patient): ?>
                        <input type="hidden" name="patient_id" value="<?= $edit_patient['id'] ?>">
                    <?php endif; ?>
                    <div class="form-grid">
                        <input type="text" name="name" class="form-input" placeholder="Patient Name" value="<?= $edit_patient ? htmlspecialchars($edit_patient['name']) : '' ?>" required>
                        <input type="text" name="phone" class="form-input" placeholder="Phone Number" value="<?= $edit_patient ? htmlspecialchars($edit_patient['phone']) : '' ?>" required>
                        <input type="number" name="age" class="form-input" placeholder="Age (Optional)" value="<?= $edit_patient ? htmlspecialchars($edit_patient['age'] ?? '') : '' ?>">
                        <select name="gender" class="form-input">
                            <option value="">Select Gender (Optional)</option>
                            <option value="Male" <?= ($edit_patient && ($edit_patient['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($edit_patient && ($edit_patient['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($edit_patient && ($edit_patient['gender'] ?? '') === 'Other') ? 'selected' : '' ?>>Other</option>
                        </select>
                        <select name="doctor_appointed" class="form-input">
                            <option value="">Select Doctor Appointed (Optional)</option>
                            <?php foreach ($doctorsList as $docName): ?>
                                <option value="<?= htmlspecialchars($docName) ?>" <?= ($edit_patient && $edit_patient['doctor_appointed'] == $docName) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($docName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <textarea name="address" class="form-input" placeholder="Address (Optional)" rows="3" style="grid-column: 1 / -1; resize: vertical;"><?= $edit_patient ? htmlspecialchars($edit_patient['address']) : '' ?></textarea>
                    </div>
                    <div class="form-actions" style="margin-top: 20px;">
                        <button type="submit" name="<?= $edit_patient ? 'update_patient' : 'add_patient' ?>" class="btn-primary btn-font-outfit">
                            <i class="fas fa-save"></i> <?= $edit_patient ? 'Update Details' : 'Save Details' ?>
                        </button>
                        <a href="patients.php" class="btn-cancel-link"><i class="fas fa-times"></i> Cancel</a>
                    </div>
                </form>
            </div>
            <?php elseif ($show_view): ?>
            <div class="card">
                <div class="card-header" style="justify-content: flex-start; gap: 15px; border-bottom: none; padding-bottom: 0;">
                    <a href="patients.php" class="btn-cancel-link" style="padding: 8px 15px;"><i class="fas fa-arrow-left"></i> Back</a>
                    <h3 style="margin:0; padding:0; border:none;"><i class="fas fa-history"></i> Appointment History for <?= htmlspecialchars($view_patient['name']) ?></h3>
                    <a href="appointments.php?action=add&patient_name=<?= urlencode($view_patient['name']) ?>&mobile=<?= urlencode($view_patient['phone']) ?>&age=<?= urlencode($view_patient['age'] ?? '') ?>&gender=<?= urlencode($view_patient['gender'] ?? '') ?>&address=<?= urlencode($view_patient['address'] ?? '') ?>" class="book-btn" style="margin-left: auto;"><i class="fas fa-calendar-plus"></i> Book New Appt</a>
                </div>
                <div class="table-responsive" style="margin-top: 20px;">
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Department</th><th>Doctor</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($patient_appointments && $patient_appointments->num_rows > 0): ?>
                                <?php while ($appt = $patient_appointments->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= date('d M Y', strtotime($appt['appointment_date'])) ?></strong></td>
                                    <td><?= htmlspecialchars($appt['department']) ?></td>
                                    <td><?= htmlspecialchars($appt['doctor']) ?></td>
                                    <td>
                                        <?php
                                        $statusClass = strtolower($appt['status']);
                                        $badgeColor = '';
                                        if ($statusClass == 'pending') $badgeColor = 'background: #fef3c7; color: #d97706;';
                                        else if ($statusClass == 'confirmed') $badgeColor = 'background: #e0f2fe; color: #0284c7;';
                                        ?>
                                        <span style="padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; <?= $badgeColor ?>"><?= htmlspecialchars($appt['status']) ?></span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr class="no-results-row"><td colspan="4" class="no-patients-msg">No appointment history found for this patient.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list-ul"></i> Patients List</h3>
                    <form method="GET" class="filter-form">
                        <input type="text" name="search" placeholder="Search Name, Phone..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" class="filter-input">
                        <button type="submit" class="btn-primary btn-filter"><i class="fas fa-search"></i> Search</button>
                    <button type="submit" name="export_csv" value="1" class="btn-primary btn-export-csv"><i class="fas fa-file-csv"></i> Export CSV</button>
                        <?php if (isset($_GET['search']) && trim($_GET['search']) !== ''): ?>
                            <a href="patients.php" class="btn-primary btn-clear"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>Name</th><th>Phone</th><th>Address</th><th>Doctor Appointed</th><th>Date Added</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($patients && $patients->num_rows > 0): ?>
                                <?php while ($row = $patients->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($row['name']) ?></strong>
                                        <?php if (!empty($row['age']) || !empty($row['gender'])): ?>
                                            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                                                <?= implode(' • ', array_filter([$row['age'] ? $row['age'] . ' Yrs' : '', $row['gender']])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['phone']) ?></td>
                                    <td><?= htmlspecialchars($row['address']) ?></td>
                                    <td><?php if (!empty($row['doctor_appointed'])): ?><span class="badge badge-dept"><?= htmlspecialchars($row['doctor_appointed']) ?></span><?php else: ?><span style="color: #94a3b8; font-size: 13px;">None</span><?php endif; ?></td>
                                    <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                    <td><div class="action-buttons"><a href="appointments.php?action=add&patient_name=<?= urlencode($row['name']) ?>&mobile=<?= urlencode($row['phone']) ?>&age=<?= urlencode($row['age'] ?? '') ?>&gender=<?= urlencode($row['gender'] ?? '') ?>&address=<?= urlencode($row['address'] ?? '') ?>" class="book-btn"><i class="fas fa-calendar-plus"></i> Book Appt</a><a href="?view_id=<?= $row['id'] ?>" class="history-btn"><i class="fas fa-history"></i> History</a><a href="?edit_id=<?= $row['id'] ?>" class="edit-btn"><i class="fas fa-edit"></i> Edit</a><form method="POST" onsubmit="return confirm('Are you sure you want to delete this patient record?');" class="action-form" style="margin:0;"><input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>"><input type="hidden" name="id" value="<?= $row['id'] ?>"><button type="submit" name="delete_patient" class="delete-btn"><i class="fas fa-trash-alt"></i> Delete</button></form></div></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr class="no-results-row"><td colspan="6" class="no-patients-msg">No patients found in the database.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div id="infinite-scroll-spinner" style="display: none; text-align: center; padding: 20px; color: #64748b; font-weight: 500;">
                    <i class="fas fa-spinner fa-spin"></i> Loading more patients...
                </div>
                
                <script>
                document.addEventListener("DOMContentLoaded", function() {
                    let currentPage = <?= (int)$page ?>;
                    const totalPages = <?= (int)$totalPages ?>;
                    const tbody = document.querySelector('.table-responsive tbody');
                    const spinner = document.getElementById('infinite-scroll-spinner');
                    let isLoading = false;

                    function loadMore() {
                        if (isLoading || currentPage >= totalPages) return;
                        isLoading = true;
                        
                        currentPage++;
                        const url = new URL(window.location.href);
                        url.searchParams.set('page', currentPage);
                        
                        fetch(url).then(res => res.text()).then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const newRows = doc.querySelectorAll('.table-responsive tbody tr');
                            
                            newRows.forEach(row => tbody.appendChild(row));
                            
                            isLoading = false;
                            if (currentPage >= totalPages) {
                                spinner.style.display = 'none';
                            }
                        }).catch(() => { isLoading = false; });
                    }

                    const observer = new IntersectionObserver((entries) => {
                        if (entries[0].isIntersecting) {
                            loadMore();
                        }
                    }, { rootMargin: '200px' });

                    if (currentPage < totalPages) {
                        spinner.style.display = 'block';
                        observer.observe(spinner);
                    }
                });
                </script>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
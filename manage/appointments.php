<?php
session_start();
date_default_timezone_set('Asia/Kolkata');

// Ensure the user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['admin_permissions']) || ($_SESSION['admin_permissions'] !== 'all' && !in_array('appointments', (array)$_SESSION['admin_permissions']))) {
    header("Location: dashboard.php");
    exit;
}

require_once '../includes/db.php';

// Handle Update Status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    $id = $_POST['id'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE appointments SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    header("Location: appointments.php");
    exit;
}

// Handle Add Appointment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_appointment'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    $patient_name = $_POST['patient_name'];
    $mobile = $_POST['mobile'];
    $department = $_POST['department'];
    $doctor = $_POST['doctor'];
    $appointment_date = $_POST['appointment_date'];
    $status = $_POST['status'];
    
    $age = isset($_POST['age']) && $_POST['age'] !== '' ? (int)$_POST['age'] : null;
    $gender = isset($_POST['gender']) && $_POST['gender'] !== '' ? $_POST['gender'] : null;
    $address = isset($_POST['address']) && $_POST['address'] !== '' ? $_POST['address'] : null;

    $stmt = $conn->prepare("INSERT INTO appointments (patient_name, mobile, department, doctor, appointment_date, status, age, gender, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssiss", $patient_name, $mobile, $department, $doctor, $appointment_date, $status, $age, $gender, $address);
    $stmt->execute();

    header("Location: appointments.php");
    exit;
}

// Handle Delete Appointment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_appointment'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
    $stmt->bind_param("i", $_POST['id']);
    $stmt->execute();
    header("Location: appointments.php");
    exit;
}

$conditions = [];

if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $searchTerm = $conn->real_escape_string(trim($_GET['search']));
    $conditions[] = "(patient_name LIKE '%$searchTerm%' OR mobile LIKE '%$searchTerm%' OR department LIKE '%$searchTerm%' OR doctor LIKE '%$searchTerm%')";
}

if (isset($_GET['status']) && trim($_GET['status']) !== '') {
    $statusTerm = $conn->real_escape_string(trim($_GET['status']));
    $conditions[] = "status = '$statusTerm'";
}

if (isset($_GET['doctor']) && trim($_GET['doctor']) !== '') {
    $doctorTerm = $conn->real_escape_string(trim($_GET['doctor']));
    $conditions[] = "doctor = '$doctorTerm'";
}

if (isset($_GET['from_date']) || isset($_GET['to_date'])) {
    if (isset($_GET['from_date']) && trim($_GET['from_date']) !== '') {
        $fromTerm = $conn->real_escape_string(trim($_GET['from_date']));
        $conditions[] = "appointment_date >= '$fromTerm'";
    }
    if (isset($_GET['to_date']) && trim($_GET['to_date']) !== '') {
        $toTerm = $conn->real_escape_string(trim($_GET['to_date']));
        $conditions[] = "appointment_date <= '$toTerm'";
    }
} else {
    if (!isset($_GET['status']) && !isset($_GET['search']) && !isset($_GET['doctor'])) {
        $_GET['from_date'] = date('Y-m-d');
        $_GET['to_date'] = date('Y-m-d');
        $conditions[] = "appointment_date >= '" . date('Y-m-d') . "'";
        $conditions[] = "appointment_date <= '" . date('Y-m-d') . "'";
    }
}

$searchQuery = "";
if (count($conditions) > 0) {
    $searchQuery = " WHERE " . implode(" AND ", $conditions);
}

// Handle CSV Export
if (isset($_GET['export_csv']) && $_GET['export_csv'] == '1') {
    $exportQuery = "SELECT * FROM appointments $searchQuery ORDER BY appointment_date DESC";
    $exportResult = $conn->query($exportQuery);
    
    if ($exportResult) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=appointments_export_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Patient Name', 'Mobile', 'Age', 'Gender', 'Address', 'Department', 'Doctor', 'Appointment Date', 'Status', 'Requested On']);
        
        while ($row = $exportResult->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['patient_name'],
                $row['mobile'],
                $row['age'] ?? 'N/A',
                $row['gender'] ?? 'N/A',
                $row['address'] ?? 'N/A',
                $row['department'],
                $row['doctor'],
                date('d M Y', strtotime($row['appointment_date'])),
                $row['status'],
                date('d M Y, h:i A', strtotime($row['created_at']))
            ]);
        }
        fclose($output);
        exit;
    }
}

// Handle PDF Export
if (isset($_GET['export_pdf']) && $_GET['export_pdf'] == '1') {
    $exportQuery = "SELECT * FROM appointments $searchQuery ORDER BY appointment_date DESC";
    $exportResult = $conn->query($exportQuery);
    
    if ($exportResult) {
        $html = '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Appointments Export</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
                h2 { text-align: center; color: #0b2b52; margin-bottom: 5px; }
                p { text-align: center; color: #666; margin-top: 0; margin-bottom: 20px; font-size: 14px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 10px; text-align: left; font-size: 14px; }
                th { background-color: #f8fafc; color: #0b2b52; font-weight: bold; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                @media print {
                    @page { margin: 1cm; size: landscape; }
                    body { margin: 0; }
                }
            </style>
        </head>
        <body onload="window.print();">
            <h2>Appointments Report</h2>
            <p>Generated on ' . date('d M Y, h:i A') . '</p>
            <table>
                <thead>
                    <tr>
                        <th>Patient Name</th>
                        <th>Mobile</th>
                        <th>Department</th>
                        <th>Doctor</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>';
        while ($row = $exportResult->fetch_assoc()) {
            $html .= '<tr><td>' . htmlspecialchars($row['patient_name']) . '</td><td>' . htmlspecialchars($row['mobile']) . '</td><td>' . htmlspecialchars($row['department']) . '</td><td>' . htmlspecialchars($row['doctor']) . '</td><td>' . date('d M Y', strtotime($row['appointment_date'])) . '</td><td>' . $row['status'] . '</td></tr>';
        }
        $html .= '</tbody></table></body></html>';
        echo $html;
        exit;
    }
}

list($appointments, $totalPages, $page) = getPaginatedResults(
    $conn,
    "SELECT COUNT(*) as total FROM appointments $searchQuery",
    "SELECT * FROM appointments $searchQuery ORDER BY appointment_date DESC"
);

// Fetch all doctors from the doctors table, as well as any historical doctors present in the appointments table
$distinctDoctors = $conn->query("SELECT name as doctor FROM doctors UNION SELECT doctor FROM appointments WHERE doctor != '' ORDER BY doctor ASC");

$show_form = isset($_GET['action']) && $_GET['action'] == 'add';

// Fetch appointment form settings
$form_settings = [];
$settingsResult = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'appointment_%'");
if ($settingsResult) {
    while ($row = $settingsResult->fetch_assoc()) {
        $form_settings[$row['setting_key']] = $row['setting_value'];
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
    <title>Admin - Manage Appointments</title>
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700,800" rel="stylesheet">
    <?php if (!empty($favicon)): ?>
        <link rel="icon" href="../<?= htmlspecialchars($favicon) ?>?v=<?= time() ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-sidebar.css">
    <link rel="stylesheet" href="../assets/css/admin-appointments.css">
    <link rel="stylesheet" href="../assets/css/admin-custom.css">
    <link rel="stylesheet" href="../assets/css/admin-responsive.css">
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="admin-container">
        
        <div class="admin-header">
            <h2><i class="fas fa-calendar-check"></i> <?= $show_form ? 'Add Appointment' : 'Manage Appointments' ?></h2>
            <?php if (!$show_form): ?>
                <a href="?action=add" class="btn-primary"><i class="fas fa-plus"></i> Add Appointment</a>
            <?php endif; ?>
        </div>
        
        <?php if ($show_form): ?>
        <div class="card">
            <h3><i class="fas fa-plus-circle"></i> Add New Appointment</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <div class="form-grid">
                    <input type="text" name="patient_name" class="form-input" placeholder="Patient Name" required>
                    <input type="text" name="mobile" class="form-input" placeholder="Mobile Number" required>
                    
                    <?php if (!empty($form_settings['appointment_show_age'])): ?>
                        <input type="number" name="age" class="form-input" placeholder="Age">
                    <?php endif; ?>

                    <?php if (!empty($form_settings['appointment_show_gender'])): ?>
                        <select name="gender" class="form-input">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    <?php endif; ?>

                    <?php if (!empty($form_settings['appointment_show_address'])): ?>
                         <input type="text" name="address" class="form-input" placeholder="Address">
                    <?php endif; ?>
                    
                    <?php if (!isset($form_settings['appointment_show_department']) || $form_settings['appointment_show_department'] == '1'): ?>
                    <select name="department" id="department-select" class="form-input" required>
                        <option value="">Select Department</option>
                        <?php
                        $deptQuery = $conn->query("SELECT DISTINCT specialty FROM doctors WHERE specialty != '' AND is_active = 1 ORDER BY specialty ASC");
                        if ($deptQuery && $deptQuery->num_rows > 0) {
                            while ($dept = $deptQuery->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars(trim($dept['specialty'])) . '">' . htmlspecialchars(trim($dept['specialty'])) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <?php else: ?>
                        <input type="hidden" name="department" value="Not Specified">
                    <?php endif; ?>

                    <?php if (!isset($form_settings['appointment_show_doctor']) || $form_settings['appointment_show_doctor'] == '1'): ?>
                    <select name="doctor" id="doctor-select" class="form-input" required>
                        <option value="">Select Doctor</option>
                        <?php
                        $docQuery = $conn->query("SELECT name, specialty FROM doctors WHERE name != '' AND is_active = 1 ORDER BY name ASC");
                        if ($docQuery && $docQuery->num_rows > 0) {
                            while ($doc = $docQuery->fetch_assoc()) {
                                echo '<option class="doctor-option" value="' . htmlspecialchars(trim($doc['name'])) . '" data-department="' . htmlspecialchars(trim($doc['specialty'])) . '">' . htmlspecialchars(trim($doc['name'])) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <?php else: ?>
                        <input type="hidden" name="doctor" value="Not Specified">
                    <?php endif; ?>

                    <input type="date" name="appointment_date" class="form-input" required>
                    
                    <select name="status" class="form-input" required>
                        <option value="Pending">Pending</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_appointment" class="btn-primary btn-font-outfit">
                        <i class="fas fa-save"></i> Save Appointment
                    </button>
                    <a href="appointments.php" class="btn-cancel-link"><i class="fas fa-times"></i> Cancel</a>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list-ul"></i> Appointment Directory</h3>
                <form method="GET" class="filter-form">
                    <input type="text" name="search" placeholder="Search Patient, Mobile, Doctor..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" class="filter-input">
                    
                    <select name="status" class="filter-select">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?= (isset($_GET['status']) && $_GET['status'] == 'Pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="Confirmed" <?= (isset($_GET['status']) && $_GET['status'] == 'Confirmed') ? 'selected' : '' ?>>Confirmed</option>
                        <option value="Completed" <?= (isset($_GET['status']) && $_GET['status'] == 'Completed') ? 'selected' : '' ?>>Completed</option>
                        <option value="Cancelled" <?= (isset($_GET['status']) && $_GET['status'] == 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    
                    <select name="doctor" class="filter-select">
                        <option value="">All Doctors</option>
                        <?php if ($distinctDoctors && $distinctDoctors->num_rows > 0): while($docRow = $distinctDoctors->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($docRow['doctor']) ?>" <?= (isset($_GET['doctor']) && $_GET['doctor'] == $docRow['doctor']) ? 'selected' : '' ?>><?= htmlspecialchars($docRow['doctor']) ?></option>
                        <?php endwhile; endif; ?>
                    </select>
                    
                    <input type="date" name="from_date" value="<?= isset($_GET['from_date']) ? htmlspecialchars($_GET['from_date']) : '' ?>" class="filter-select filter-date" title="From Date">
                    <input type="date" name="to_date" value="<?= isset($_GET['to_date']) ? htmlspecialchars($_GET['to_date']) : '' ?>" class="filter-select filter-date" title="To Date">
                    
                    <button type="submit" class="btn-primary btn-filter"><i class="fas fa-filter"></i> Filter</button>
                    <button type="submit" name="export_csv" value="1" class="btn-primary btn-export-csv"><i class="fas fa-file-csv"></i> Export CSV</button>
                    <button type="submit" name="export_pdf" value="1" class="btn-primary btn-export-pdf"><i class="fas fa-file-pdf"></i> Export PDF</button>
                    <?php 
                    $is_filtered = (isset($_GET['search']) && trim($_GET['search']) !== '') || 
                                   (isset($_GET['status']) && trim($_GET['status']) !== '') || 
                                   (isset($_GET['doctor']) && trim($_GET['doctor']) !== '') || 
                                   (isset($_GET['from_date']) && trim($_GET['from_date']) !== date('Y-m-d')) ||
                                   (isset($_GET['to_date']) && trim($_GET['to_date']) !== date('Y-m-d'));
                    if ($is_filtered): ?>
                        <a href="appointments.php" class="btn-primary btn-clear"><i class="fas fa-times"></i> Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Patient Info</th>
                            <th>Department & Doctor</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($appointments && $appointments->num_rows > 0): ?>
                            <?php while ($row = $appointments->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="patient-info">
                                        <span class="patient-name"><?= htmlspecialchars($row['patient_name']) ?></span>
                                        <span class="patient-mobile"><i class="fas fa-phone-alt" style="color: #94a3b8; font-size: 11px;"></i> <?= htmlspecialchars($row['mobile']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="patient-info">
                                        <span class="badge badge-dept"><?= htmlspecialchars($row['department']) ?></span>
                                        <span class="patient-mobile"><i class="fas fa-user-md" style="color: #94a3b8; font-size: 11px;"></i> <?= htmlspecialchars($row['doctor']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="patient-info">
                                        <span class="patient-name"><i class="far fa-calendar-alt" style="color: var(--primary);"></i> <?= htmlspecialchars(date('d M Y', strtotime($row['appointment_date']))) ?></span>
                                        <span class="patient-mobile">Requested: <?= htmlspecialchars(date('d M', strtotime($row['created_at']))) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST" class="action-form">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="update_status" value="1">
                                        <select name="status" class="status-select <?= strtolower($row['status']) ?>" onchange="this.form.submit()">
                                            <option value="Pending" <?= $row['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Confirmed" <?= $row['status'] == 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                            <option value="Completed" <?= $row['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="Cancelled" <?= $row['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <div class="action-flex">
                                        <a href="print_ticket.php?id=<?= $row['id'] ?>" target="_blank" class="print-btn">
                                            <i class="fas fa-print"></i> Print
                                        </a>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this appointment?');" class="action-form">
                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="delete_appointment" class="delete-btn">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="no-appointments-msg">No appointments found.</td></tr>
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
    document.addEventListener("DOMContentLoaded", function() {
        const deptSelect = document.getElementById('department-select');
        const docSelect = document.getElementById('doctor-select');
        
        if(deptSelect && docSelect) {
            const docOptions = docSelect.querySelectorAll('.doctor-option');
            
            deptSelect.addEventListener('change', function() {
                const selectedDept = this.value.trim().toLowerCase();
                docSelect.value = ''; // Reset doctor selection
                
                docOptions.forEach(function(option) {
                    const dDept = (option.getAttribute('data-department') || '').trim().toLowerCase();
                    if (selectedDept === '' || dDept === selectedDept) {
                        option.style.display = ''; // Show
                    } else {
                        option.style.display = 'none'; // Hide
                    }
                });
            });
        }
    });
    </script>
</body>
</html>
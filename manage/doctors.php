<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['admin_permissions']) || ($_SESSION['admin_permissions'] !== 'all' && !in_array('doctors', (array)$_SESSION['admin_permissions']))) {
    header("Location: dashboard.php");
    exit;
}

require_once '../includes/db.php';

// Check if table exists, if not create and seed it
$tableCheck = $conn->query("SHOW TABLES LIKE 'doctors'");
if ($tableCheck && $tableCheck->num_rows == 0) {
    $sql = "CREATE TABLE doctors (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        degree VARCHAR(100) NOT NULL,
        specialty VARCHAR(100) NOT NULL,
        days VARCHAR(100) NOT NULL,
        time VARCHAR(100) NOT NULL,
        image VARCHAR(255) NOT NULL,
        is_active TINYINT(1) DEFAULT 1
    )";
    $conn->query($sql);
    
    // Insert default data
    $seed = "INSERT INTO doctors (name, degree, specialty, days, time, image, is_active) VALUES 
        ('Dr. Aamir Khan', 'MD (Medicine)', 'General Physician', 'Mon - Sat', '10:00 AM - 2:00 PM', 'assets/images/doctor1.png', 1),
        ('Dr. Showkat Mir', 'MD (Cardiology)', 'Cardiologist', 'Mon - Sat', '02:00 PM - 6:00 PM', 'assets/images/doctor2.jpg', 1),
        ('Dr. Imran Bashir', 'MS (Orthopaedics)', 'Orthopaedic Surgeon', 'Mon - Sat', '10:00 AM - 1:00 PM', 'assets/images/doctor3.jpg', 1),
        ('Dr. Aaliya Jan', 'MD (Gynaecology)', 'Gynaecologist', 'Mon - Sat', '11:00 AM - 3:00 PM', 'assets/images/doctor4.jpg', 1),
        ('Dr. Faizan Ahmad', 'MD (Paediatrics)', 'Paediatrician', 'Mon - Sat', '04:00 PM - 7:00 PM', 'assets/images/doctor5.jpg', 1)";
    $conn->query($seed);
} else {
    $columnCheck = $conn->query("SHOW COLUMNS FROM doctors LIKE 'is_active'");
    if ($columnCheck && $columnCheck->num_rows == 0) {
        $conn->query("ALTER TABLE doctors ADD COLUMN is_active TINYINT(1) DEFAULT 1");
    }
}

// Fix broken image paths from older seed data
$conn->query("UPDATE doctors SET image = CONCAT('assets/', image) WHERE image LIKE 'images/%'");

// Handle Add Doctor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_doctor'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    $name = $conn->real_escape_string(trim($_POST['name']));
    $degree = $conn->real_escape_string(trim($_POST['degree']));
    $specialty = $conn->real_escape_string(trim($_POST['specialty']));
    $days = $conn->real_escape_string(trim($_POST['days']));
    $time = $conn->real_escape_string(trim($_POST['time']));
    
    $imagePath = '';
    $allowedImageTypes = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 
        'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'
    ];

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $targetDir = "../assets/images/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        if ($ext = validateSecureUpload($_FILES['image']['tmp_name'], $_FILES['image']['name'], $_FILES['image']['size'], $allowedImageTypes)) {
            $fileName = bin2hex(random_bytes(16)) . '.' . $ext;
            $targetFilePath = $targetDir . $fileName;
            
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
                $imagePath = "assets/images/" . $fileName;
            }
        }
    }

    $image = $conn->real_escape_string($imagePath);
    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
    $conn->query("INSERT INTO doctors (name, degree, specialty, days, time, image, is_active) VALUES ('$name', '$degree', '$specialty', '$days', '$time', '$image', $is_active)");
    header("Location: doctors.php");
    exit;
}

// Handle Update Doctor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_doctor'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    $id = (int)$_POST['doctor_id'];
    $name = $conn->real_escape_string(trim($_POST['name']));
    $degree = $conn->real_escape_string(trim($_POST['degree']));
    $specialty = $conn->real_escape_string(trim($_POST['specialty']));
    $days = $conn->real_escape_string(trim($_POST['days']));
    $time = $conn->real_escape_string(trim($_POST['time']));
    $existing_image = $conn->real_escape_string($_POST['existing_image']);
    
    $imagePath = $existing_image;
    $allowedImageTypes = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 
        'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'
    ];

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $targetDir = "../assets/images/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        if ($ext = validateSecureUpload($_FILES['image']['tmp_name'], $_FILES['image']['name'], $_FILES['image']['size'], $allowedImageTypes)) {
            $fileName = bin2hex(random_bytes(16)) . '.' . $ext;
            $targetFilePath = $targetDir . $fileName;
            
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
                $imagePath = "assets/images/" . $fileName;
                // Delete the old image file to save space (optional)
                if ($existing_image != $imagePath && file_exists("../" . $existing_image)) {
                    unlink("../" . $existing_image);
                }
            }
        }
    }

    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
    $conn->query("UPDATE doctors SET name='$name', degree='$degree', specialty='$specialty', days='$days', time='$time', image='$imagePath', is_active=$is_active WHERE id=$id");
    header("Location: doctors.php");
    exit;
}

// Handle Delete Doctor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_doctor'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    $id = (int)$_POST['id'];
    
    // Fetch image path to delete file from server
    $result = $conn->query("SELECT image FROM doctors WHERE id = $id");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $imagePathToDelete = "../" . $row['image'];
        if (file_exists($imagePathToDelete) && is_file($imagePathToDelete)) {
            unlink($imagePathToDelete);
        }
    }
    
    $conn->query("DELETE FROM doctors WHERE id = $id");
    header("Location: doctors.php");
    exit;
}

// Handle Toggle Status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status_btn'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    $id = (int)$_POST['id'];
    $new_status = (int)$_POST['toggle_status'];
    $conn->query("UPDATE doctors SET is_active = $new_status WHERE id = $id");
    header("Location: doctors.php");
    exit;
}

$searchQuery = "";
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $searchTerm = $conn->real_escape_string(trim($_GET['search']));
    $searchQuery = " WHERE name LIKE '%$searchTerm%' OR specialty LIKE '%$searchTerm%'";
}

list($doctors, $totalPages, $page) = getPaginatedResults(
    $conn,
    "SELECT COUNT(*) as total FROM doctors $searchQuery",
    "SELECT * FROM doctors $searchQuery"
);

// Fetch doctor for editing if requested
$edit_doctor = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $edit_result = $conn->query("SELECT * FROM doctors WHERE id = $edit_id");
    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_doctor = $edit_result->fetch_assoc();
    }
}
    $show_form = isset($_GET['action']) && $_GET['action'] == 'add' || isset($_GET['edit_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Doctors</title>
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700,800" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-doctors.css">
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="admin-container">
        
        <div class="admin-header"><h2><i class="fas fa-user-md"></i> <?= $show_form ? ($edit_doctor ? 'Edit Doctor' : 'Add Doctor') : 'Manage Doctors' ?></h2>
            <?php if (!$show_form): ?>
                <a href="?action=add" class="btn-primary"><i class="fas fa-plus"></i> Add Doctor</a>
            <?php endif; ?>
        </div>
        
        <?php if ($show_form): ?>
        <div class="card">
            <h3><i class="fas fa-<?= $edit_doctor ? 'edit' : 'plus-circle' ?>"></i> <?= $edit_doctor ? 'Edit Specialist' : 'Add New Specialist' ?></h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <?php if ($edit_doctor): ?>
                    <input type="hidden" name="doctor_id" value="<?= $edit_doctor['id'] ?>">
                    <input type="hidden" name="existing_image" value="<?= htmlspecialchars($edit_doctor['image']) ?>">
                <?php endif; ?>
                <div class="form-grid">
                    <input type="text" name="name" placeholder="Doctor Name (e.g., Dr. Aamir Khan)" value="<?= $edit_doctor ? htmlspecialchars($edit_doctor['name']) : '' ?>" required>
                    <input type="text" name="degree" placeholder="Degree (e.g., MD Medicine)" value="<?= $edit_doctor ? htmlspecialchars($edit_doctor['degree']) : '' ?>" required>
                    <input type="text" name="specialty" placeholder="Specialty (e.g., Cardiologist)" value="<?= $edit_doctor ? htmlspecialchars($edit_doctor['specialty']) : '' ?>" required>
                    <input type="text" name="days" placeholder="Available Days (e.g., Mon - Sat)" value="<?= $edit_doctor ? htmlspecialchars($edit_doctor['days']) : '' ?>" required>
                    <input type="text" name="time" placeholder="Timing (e.g., 10:00 AM - 2:00 PM)" value="<?= $edit_doctor ? htmlspecialchars($edit_doctor['time']) : '' ?>" required>
                    <select name="is_active" required>
                        <option value="1" <?= ($edit_doctor && (!isset($edit_doctor['is_active']) || $edit_doctor['is_active'] == 1)) ? 'selected' : '' ?>>Active (Visible)</option>
                        <option value="0" <?= ($edit_doctor && isset($edit_doctor['is_active']) && $edit_doctor['is_active'] == 0) ? 'selected' : '' ?>>Inactive (Hidden)</option>
                    </select>
                    <input type="file" name="image" accept="image/*" <?= $edit_doctor ? '' : 'required' ?>>
                </div>
                <div class="form-actions">
                    <button type="submit" name="<?= $edit_doctor ? 'update_doctor' : 'add_doctor' ?>" class="btn-primary">
                        <i class="fas fa-save"></i> <?= $edit_doctor ? 'Update Doctor' : 'Save Doctor' ?>
                    </button>
                    <a href="doctors.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
                </div>
            </form>
        </div>
        <?php else: ?>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list-ul"></i> Doctor Directory</h3>
                <form method="GET" class="filter-form">
                    <input type="text" name="search" placeholder="Search Name or Specialty..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" class="filter-input">
                    <button type="submit" class="btn-primary btn-filter"><i class="fas fa-search"></i> Search</button>
                    <?php if (isset($_GET['search']) && trim($_GET['search']) !== ''): ?>
                        <a href="doctors.php" class="btn-primary btn-clear"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Profile</th>
                            <th>Doctor Info</th>
                            <th>Specialty</th>
                            <th>Schedule</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($doctors && $doctors->num_rows > 0): ?>
                            <?php while ($row = $doctors->fetch_assoc()): ?>
                            <tr>
                                <td><img src="../<?= htmlspecialchars($row['image']) ?>" class="img-preview" alt="Doctor"></td>
                                <td>
                                    <div class="doc-info">
                                        <span class="doc-name"><?= htmlspecialchars($row['name']) ?></span>
                                        <span class="doc-deg"><?= htmlspecialchars($row['degree']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge"><?= htmlspecialchars($row['specialty']) ?></span>
                                </td>
                                <td>
                                    <div class="doc-info">
                                        <span class="timing-info"><i class="far fa-calendar-alt" style="color: var(--primary);"></i> <?= htmlspecialchars($row['days']) ?></span>
                                        <span class="doc-deg"><i class="far fa-clock"></i> <?= htmlspecialchars($row['time']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST" class="action-form">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="toggle_status" value="<?= (!isset($row['is_active']) || $row['is_active'] == 1) ? 0 : 1 ?>">
                                        <button type="submit" name="update_status_btn" class="btn-toggle">
                                            <?php if (!isset($row['is_active']) || $row['is_active'] == 1): ?>
                                                <span class="badge badge-active">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-inactive">Inactive</span>
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?edit_id=<?= $row['id'] ?>" class="edit-btn">
                                            <i class="fas fa-edit"></i> Edit
                                        </a><form method="POST" onsubmit="return confirm('Are you sure you want to delete this doctor?');" class="action-form">
                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="delete_doctor" class="delete-btn">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr class="no-results-row"><td colspan="6">No doctors found in the database.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php renderPaginationLinks($totalPages, $page); ?>
        </div>
        <?php endif; ?>
        
        </div>
    </main>
</body>
</html>
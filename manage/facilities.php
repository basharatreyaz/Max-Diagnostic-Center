<?php
session_start();
date_default_timezone_set('Asia/Kolkata');

// Ensure the user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['admin_permissions']) || ($_SESSION['admin_permissions'] !== 'all' && !in_array('facilities', (array)$_SESSION['admin_permissions']))) {
    header("Location: dashboard.php");
    exit;
}

require_once '../includes/db.php';


// Check if table exists, if not create and seed it
$tableCheck = $conn->query("SHOW TABLES LIKE 'facilities'");
if ($tableCheck && $tableCheck->num_rows == 0) {
    $sql = "CREATE TABLE facilities (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        image VARCHAR(255) NOT NULL
    )";
    $conn->query($sql);
    
    // Insert default data
    $seed = "INSERT INTO facilities (name, image) VALUES 
        ('Digital X-Ray', 'assets/images/xray.jpg'),
        ('Ultrasound', 'assets/images/ultrasound.jpg'),
        ('ECG', 'assets/images/ecg.jpg'),
        ('Pathology Lab', 'assets/images/pathology.jpg'),
        ('Automated Analyzer', 'assets/images/auto.jpg'),
        ('Sample Collection', 'assets/images/sample.jpg')";
    $conn->query($seed);
}

// Handle Add Facility
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_facility'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    $name = $conn->real_escape_string($_POST['name']);
    
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
    $conn->query("INSERT INTO facilities (name, image) VALUES ('$name', '$image')");
    header("Location: facilities.php");
    exit;
}

// Handle Update Facility
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_facility'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    $id = (int)$_POST['facility_id'];
    $name = $conn->real_escape_string($_POST['name']);
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

    $conn->query("UPDATE facilities SET name='$name', image='$imagePath' WHERE id=$id");
    header("Location: facilities.php");
    exit;
}

// Handle Delete Facility
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_facility'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    $id = (int)$_POST['id'];
    
    // Fetch image path to delete file from server
    $result = $conn->query("SELECT image FROM facilities WHERE id = $id");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $imagePathToDelete = "../" . $row['image'];
        if (file_exists($imagePathToDelete) && is_file($imagePathToDelete)) {
            unlink($imagePathToDelete);
        }
    }
    
    $conn->query("DELETE FROM facilities WHERE id = $id");
    header("Location: facilities.php");
    exit;
}

$searchQuery = "";
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $searchTerm = $conn->real_escape_string(trim($_GET['search']));
    $searchQuery = " WHERE name LIKE '%$searchTerm%'";
}

list($facilities, $totalPages, $page) = getPaginatedResults(
    $conn,
    "SELECT COUNT(*) as total FROM facilities $searchQuery",
    "SELECT * FROM facilities $searchQuery"
);

// Fetch facility for editing if requested
$edit_facility = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $edit_result = $conn->query("SELECT * FROM facilities WHERE id = $edit_id");
    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_facility = $edit_result->fetch_assoc();
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
    <title>Admin - Manage Facilities</title>
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700,800" rel="stylesheet">
    <?php if (!empty($favicon)): ?>
        <link rel="icon" href="../<?= htmlspecialchars($favicon) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-sidebar.css">
    <link rel="stylesheet" href="../assets/css/admin-facilities.css">
    <link rel="stylesheet" href="../assets/css/admin-custom.css">
    <link rel="stylesheet" href="../assets/css/admin-responsive.css">
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="admin-container">
        <div class="admin-header">
            <h2><i class="fas fa-microscope"></i> <?= $show_form ? ($edit_facility ? 'Edit Facility' : 'Add Facility') : 'Manage Facilities' ?></h2>
            <?php if (!$show_form): ?>
                <a href="?action=add" class="btn-primary"><i class="fas fa-plus"></i> Add Facility</a>
            <?php endif; ?>
        </div>
        <?php if ($show_form): ?>
        <div class="card">
            <h3><i class="fas fa-<?= $edit_facility ? 'edit' : 'plus-circle' ?>"></i> <?= $edit_facility ? 'Edit Facility' : 'Add New Facility' ?></h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <?php if ($edit_facility): ?><input type="hidden" name="facility_id" value="<?= $edit_facility['id'] ?>"><input type="hidden" name="existing_image" value="<?= htmlspecialchars($edit_facility['image']) ?>"><?php endif; ?>
                <div class="form-grid">
                    <input type="text" name="name" placeholder="Facility Name (e.g., Digital X-Ray)" value="<?= $edit_facility ? htmlspecialchars($edit_facility['name']) : '' ?>" required>
                    <input type="file" name="image" accept="image/*" <?= $edit_facility ? '' : 'required' ?>>
                </div>
                <div class="form-actions">
                    <button type="submit" name="<?= $edit_facility ? 'update_facility' : 'add_facility' ?>" class="btn-primary btn-font-outfit"><i class="fas fa-save"></i> <?= $edit_facility ? 'Update Facility' : 'Save Facility' ?></button>
                    <a href="facilities.php" class="btn-cancel-link"><i class="fas fa-times"></i> Cancel</a>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list-ul"></i> Facilities Directory</h3>
                <form method="GET" class="filter-form"><input type="text" name="search" placeholder="Search Facility Name..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" class="filter-input"><button type="submit" class="btn-primary btn-filter"><i class="fas fa-search"></i> Search</button><?php if (isset($_GET['search']) && trim($_GET['search']) !== ''): ?><a href="facilities.php" class="btn-primary btn-clear"><i class="fas fa-times"></i></a><?php endif; ?></form>
            </div>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>Image</th><th>Facility Name</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php if ($facilities && $facilities->num_rows > 0): ?><?php while ($row = $facilities->fetch_assoc()): ?><tr><td><img src="../<?= htmlspecialchars($row['image']) ?>" class="img-preview" alt="Facility"></td><td><span class="facility-name-text"><?= htmlspecialchars($row['name']) ?></span></td><td><div class="action-buttons"><a href="?edit_id=<?= $row['id'] ?>" class="edit-btn"><i class="fas fa-edit"></i> Edit</a><form method="POST" onsubmit="return confirm('Are you sure you want to delete this facility?');" class="action-form"><input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>"><input type="hidden" name="id" value="<?= $row['id'] ?>"><button type="submit" name="delete_facility" class="delete-btn"><i class="fas fa-trash-alt"></i> Delete</button></form></div></td></tr><?php endwhile; ?><?php else: ?><tr class="no-results-row"><td colspan="3" class="no-facilities-msg">No facilities found in the database.</td></tr><?php endif; ?>
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
<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['admin_permissions']) || ($_SESSION['admin_permissions'] !== 'all' && !in_array('services', (array)$_SESSION['admin_permissions']))) {
    header("Location: dashboard.php");
    exit;
}

require_once '../includes/db.php';

// Check if table exists, if not create and seed it
$tableCheck = $conn->query("SHOW TABLES LIKE 'services'");
if ($tableCheck && $tableCheck->num_rows == 0) {
    $sql = "CREATE TABLE services (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        description TEXT NOT NULL,
        image VARCHAR(255) DEFAULT NULL,
        price VARCHAR(50) DEFAULT NULL
    )";
    $conn->query($sql);
    
    $conn->query("INSERT INTO services (title, description) VALUES ('Diagnostic Services', 'Comprehensive pathology and radiology services including blood tests, biochemistry, and microbiology.')");
    $conn->query("INSERT INTO services (title, description) VALUES ('Specialist Consultations', 'Expert consultations in general medicine, cardiology, orthopaedics, and gynaecology.')");
    $conn->query("INSERT INTO services (title, description) VALUES ('Health Packages', 'Wide range of preventive health check-up packages for you and your family.')");
    $conn->query("INSERT INTO services (title, description) VALUES ('Home Collection', 'Convenient home sample collection services by trained phlebotomists.')");
} else {
    $columnCheck = $conn->query("SHOW COLUMNS FROM services LIKE 'image'");
    if ($columnCheck && $columnCheck->num_rows == 0) {
        $conn->query("ALTER TABLE services ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER icon");
    }
    $columnCheckPrice = $conn->query("SHOW COLUMNS FROM services LIKE 'price'");
    if ($columnCheckPrice && $columnCheckPrice->num_rows == 0) {
        $conn->query("ALTER TABLE services ADD COLUMN price VARCHAR(50) DEFAULT NULL AFTER image");
    }
}

// PRG Pattern
if (isset($_SESSION['service_msg'])) {
    $service_msg = $_SESSION['service_msg'];
    unset($_SESSION['service_msg']);
}

// Handle Add Service
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_service'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = $conn->real_escape_string($_POST['price']);
    
    $imagePath = '';
    $allowedImageTypes = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 
        'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'
    ];

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $targetDir = "../assets/images/services/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        if ($ext = validateSecureUpload($_FILES['image']['tmp_name'], $_FILES['image']['name'], $_FILES['image']['size'], $allowedImageTypes)) {
            $fileName = bin2hex(random_bytes(16)) . '.' . $ext;
            $targetFilePath = $targetDir . $fileName;
            
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
                $imagePath = "assets/images/services/" . $fileName;
            }
        }
    }

    $conn->query("INSERT INTO services (title, description, image, price) VALUES ('$title', '$description', '$imagePath', '$price')");
    $_SESSION['service_msg'] = "Service added successfully!";
    header("Location: services.php");
    exit;
}

// Handle Update Service
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_service'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    $id = (int)$_POST['service_id'];
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = $conn->real_escape_string($_POST['price']);
    $existing_image = $conn->real_escape_string($_POST['existing_image']);
    
    $imagePath = $existing_image;
    $allowedImageTypes = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 
        'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'
    ];

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $targetDir = "../assets/images/services/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        if ($ext = validateSecureUpload($_FILES['image']['tmp_name'], $_FILES['image']['name'], $_FILES['image']['size'], $allowedImageTypes)) {
            $fileName = bin2hex(random_bytes(16)) . '.' . $ext;
            $targetFilePath = $targetDir . $fileName;
            
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
                $imagePath = "assets/images/services/" . $fileName;
                if ($existing_image != '' && $existing_image != $imagePath && file_exists("../" . $existing_image)) {
                    unlink("../" . $existing_image);
                }
            }
        }
    }

    $conn->query("UPDATE services SET title='$title', description='$description', image='$imagePath', price='$price' WHERE id=$id");
    $_SESSION['service_msg'] = "Service updated successfully!";
    header("Location: services.php");
    exit;
}

// Handle Delete Service
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_service'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    $id = (int)$_POST['id'];
    
    $result = $conn->query("SELECT image FROM services WHERE id = $id");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['image']) && file_exists("../" . $row['image'])) {
            unlink("../" . $row['image']);
        }
    }

    $conn->query("DELETE FROM services WHERE id = $id");
    $_SESSION['service_msg'] = "Service deleted successfully!";
    header("Location: services.php");
    exit;
}

list($services, $totalPages, $page) = getPaginatedResults(
    $conn,
    "SELECT COUNT(*) as total FROM services",
    "SELECT * FROM services ORDER BY id ASC"
);

$edit_service = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $edit_result = $conn->query("SELECT * FROM services WHERE id = $edit_id");
    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_service = $edit_result->fetch_assoc();
    }
}
$show_form = isset($_GET['action']) && $_GET['action'] == 'add' || isset($_GET['edit_id']);

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
    <title>Admin - Manage Services</title>
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700,800" rel="stylesheet">
    <?php if (!empty($favicon)): ?>
        <link rel="icon" href="../<?= htmlspecialchars($favicon) ?>?v=<?= time() ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-services.css">
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="admin-container">
        
        <div class="admin-header">
            <h2><i class="fas fa-notes-medical"></i> <?= $show_form ? ($edit_service ? 'Edit Service' : 'Add Service') : 'Manage Services' ?></h2>
            <?php if (!$show_form): ?>
                <a href="?action=add" class="btn-primary"><i class="fas fa-plus"></i> Add Service</a>
            <?php endif; ?>
        </div>

        <?php if (isset($service_msg)): ?>
            <div class="success-msg">
                <i class="fas fa-check-circle"></i> <?= $service_msg ?>
            </div>
        <?php endif; ?>
        
        <?php if ($show_form): ?>
        <div class="card">
            <h3><i class="fas fa-<?= $edit_service ? 'edit' : 'plus-circle' ?>"></i> <?= $edit_service ? 'Edit Service' : 'Add New Service' ?></h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <?php if ($edit_service): ?>
                    <input type="hidden" name="service_id" value="<?= $edit_service['id'] ?>">
                    <input type="hidden" name="existing_image" value="<?= htmlspecialchars($edit_service['image'] ?? '') ?>">
                <?php endif; ?>
                <div class="form-grid">
                    <input type="text" name="title" placeholder="Service Title (e.g., Blood Test)" value="<?= $edit_service ? htmlspecialchars($edit_service['title']) : '' ?>" required>
                    <input type="text" name="price" placeholder="Price (optional, e.g., ₹500)" value="<?= $edit_service ? htmlspecialchars($edit_service['price']) : '' ?>">
                    <input type="file" name="image" accept="image/*">
                </div>
                <textarea name="description" placeholder="Service Description..." rows="4" required><?= $edit_service ? htmlspecialchars($edit_service['description']) : '' ?></textarea>
                
                <div class="form-actions">
                    <button type="submit" name="<?= $edit_service ? 'update_service' : 'add_service' ?>" class="btn-primary">
                        <i class="fas fa-save"></i> <?= $edit_service ? 'Update Service' : 'Save Service' ?>
                    </button>
                    <a href="services.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="table-responsive">
                <table>
                    <thead><tr><th>Image</th><th>Title</th><th>Description</th><th>Price</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php if ($services && $services->num_rows > 0): while ($row = $services->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($row['image'])): ?>
                                        <img src="../<?= htmlspecialchars($row['image']) ?>" alt="Service Image" class="service-img-preview">
                                    <?php else: ?>
                                        <div class="service-img-placeholder">No Image</div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($row['title']) ?></strong></td>
                                <td><div class="service-desc-preview"><?= htmlspecialchars($row['description']) ?></div></td>
                                <td><?= !empty($row['price']) ? htmlspecialchars($row['price']) : '<span class="price-na">N/A</span>' ?></td>
                                <td><div class="action-buttons"><a href="?edit_id=<?= $row['id'] ?>" class="edit-btn"><i class="fas fa-edit"></i> Edit</a><form method="POST" onsubmit="return confirm('Delete this service?');" class="action-form"><input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>"><input type="hidden" name="id" value="<?= $row['id'] ?>"><button type="submit" name="delete_service" class="delete-btn"><i class="fas fa-trash-alt"></i> Delete</button></form></div></td></tr>
                        <?php endwhile; else: ?>
                            <tr class="no-results-row"><td colspan="6">No services found.</td></tr>
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
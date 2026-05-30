<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['admin_permissions']) || ($_SESSION['admin_permissions'] !== 'all' && !in_array('messages', (array)$_SESSION['admin_permissions']))) {
    header("Location: dashboard.php");
    exit;
}

require_once '../includes/db.php';

// Check if table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'inquiries'");
if ($tableCheck && $tableCheck->num_rows == 0) {
    $sql = "CREATE TABLE inquiries (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('Unread', 'Read') DEFAULT 'Unread',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
}

// Handle Update Status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    $id = (int)$_POST['id'];
    $status = $conn->real_escape_string($_POST['status']);
    
    $conn->query("UPDATE inquiries SET status='$status' WHERE id=$id");
    header("Location: messages.php");
    exit;
}

// Handle Delete Inquiry
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_inquiry'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    $id = (int)$_POST['id'];
    $conn->query("DELETE FROM inquiries WHERE id = $id");
    header("Location: messages.php");
    exit;
}

$searchQuery = "";
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $searchTerm = $conn->real_escape_string(trim($_GET['search']));
    $searchQuery = " WHERE name LIKE '%$searchTerm%' OR email LIKE '%$searchTerm%' OR subject LIKE '%$searchTerm%'";
}

list($inquiries, $totalPages, $page) = getPaginatedResults(
    $conn,
    "SELECT COUNT(*) as total FROM inquiries $searchQuery",
    "SELECT * FROM inquiries $searchQuery ORDER BY created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Messages</title>
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700,800" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-messages.css">
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="admin-container">
            <div class="admin-header">
                <h2><i class="fas fa-envelope"></i> Manage Messages</h2>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-inbox"></i> Inbox</h3>
                    <form method="GET" class="filter-form">
                        <input type="text" name="search" placeholder="Search Name, Email, Subject..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" class="filter-input">
                        <button type="submit" class="btn-primary btn-filter"><i class="fas fa-search"></i> Search</button>
                        <?php if (isset($_GET['search']) && trim($_GET['search']) !== ''): ?>
                            <a href="messages.php" class="btn-primary btn-clear"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead><tr><th>Sender Info</th><th>Subject</th><th>Message</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php if ($inquiries && $inquiries->num_rows > 0): while ($row = $inquiries->fetch_assoc()): ?>
                                <tr class="<?= $row['status'] == 'Unread' ? 'font-weight: 600;' : '' ?>">
                                    <td>
                                        <div class="sender-info">
                                            <span class="sender-name"><?= htmlspecialchars($row['name']) ?></span>
                                            <span class="sender-email"><i class="fas fa-envelope"></i> <?= htmlspecialchars($row['email']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($row['subject']) ?></td>
                                    <td><div class="message-preview" title="<?= htmlspecialchars($row['message']) ?>"><?= htmlspecialchars($row['message']) ?></div></td>
                                    <td><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <form method="POST" class="action-form">
                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="update_status" value="1">
                                            <select name="status" class="status-select <?= strtolower($row['status']) ?>" onchange="this.form.submit()">
                                                <option value="Unread" <?= $row['status'] == 'Unread' ? 'selected' : '' ?>>Unread</option>
                                                <option value="Read" <?= $row['status'] == 'Read' ? 'selected' : '' ?>>Read</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this message?');" class="action-form">
                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="delete_inquiry" class="delete-btn"><i class="fas fa-trash-alt"></i> Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr class="no-results-row"><td colspan="6">No messages found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php renderPaginationLinks($totalPages, $page); ?>
            </div>
        </div>
    </main>
</body>
</html>
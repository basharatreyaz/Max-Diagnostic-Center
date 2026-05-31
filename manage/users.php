<?php
session_start();
date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || $_SESSION['admin_role'] !== 'superadmin') {
    header("Location: login.php");
    exit;
}

require_once '../includes/db.php';

$form_error = '';
if (isset($_SESSION['user_form_error'])) {
    $form_error = $_SESSION['user_form_error'];
    unset($_SESSION['user_form_error']);
}

// Handle Add/Edit User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_user'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    $username = $_POST['username'];
    $role = 'admin'; // all non-superadmin created users are 'admin'
    $permissions = isset($_POST['permissions']) ? json_encode($_POST['permissions']) : '[]';
    $user_id = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    // Prevent username reuse
    $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
    $stmt->bind_param("si", $username, $user_id);
    $stmt->execute();
    $check_res = $stmt->get_result();
    if ($check_res && $check_res->num_rows > 0) {
        $_SESSION['user_form_error'] = "<strong>Error:</strong> The username '" . htmlspecialchars($username) . "' is already taken. Please choose a different one.";
        $redirect_url = "users.php" . ($user_id > 0 ? "?edit_id=" . $user_id : "?action=add");
        header("Location: " . $redirect_url);
        exit;
    }

    if (!empty($_POST['user_id'])) {
        // Update
        
        $stmt = $conn->prepare("SELECT role FROM admins WHERE id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $check_res = $stmt->get_result();
        $edit_role = 'admin';
        if ($check_res && $check_res->num_rows > 0) {
            $edit_role = $check_res->fetch_assoc()['role'];
        }
        if ($edit_role === 'superadmin') {
            $permissions = 'all';
        }

        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admins SET username=?, password=?, permissions=? WHERE id=?");
            $stmt->bind_param("sssi", $username, $password, $permissions, $user_id);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("UPDATE admins SET username=?, permissions=? WHERE id=?");
            $stmt->bind_param("ssi", $username, $permissions, $user_id);
            $stmt->execute();
        }
    } else {
        // Insert
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admins (username, password, role, permissions) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $password, $role, $permissions);
        $stmt->execute();
    }
    header("Location: users.php");
    exit;
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    $stmt = $conn->prepare("DELETE FROM admins WHERE id=? AND role!='superadmin'");
    $stmt->bind_param("i", $_POST['id']);
    $stmt->execute();
    header("Location: users.php");
    exit;
}

$admins = $conn->query("SELECT * FROM admins ORDER BY id ASC");
$edit_user = null;
if (isset($_GET['edit_id'])) {
    $stmt = $conn->prepare("SELECT * FROM admins WHERE id=?");
    $stmt->bind_param("i", $_GET['edit_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) $edit_user = $res->fetch_assoc();
}
$show_form = isset($_GET['action']) && $_GET['action'] == 'add' || $edit_user;

$settings = getCachedSettings($conn);
$favicon = $settings['favicon'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Users</title>
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700,800" rel="stylesheet">
    <?php if (!empty($favicon)): ?>
        <link rel="icon" href="../<?= htmlspecialchars($favicon) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-sidebar.css">
    <link rel="stylesheet" href="../assets/css/admin-doctors.css"> <!-- Reusing styles -->
    <link rel="stylesheet" href="../assets/css/admin-users.css">
    <link rel="stylesheet" href="../assets/css/admin-custom.css">
    <link rel="stylesheet" href="../assets/css/admin-responsive.css">
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="admin-container">
            <div class="admin-header">
                <h2><i class="fas fa-users-cog"></i> <?= $show_form ? 'Manage User' : 'Admin Users' ?></h2>
                <?php if (!$show_form): ?>
                    <a href="?action=add" class="btn-primary btn-add-report"><i class="fas fa-plus"></i> Add User</a>
                <?php endif; ?>
            </div>

            <?php if ($show_form): ?>
                <div class="card">
                    <?php if ($form_error): ?>
                        <div class="error-msg"><?= $form_error ?></div>
                    <?php endif; ?>
                    <h3><i class="fas fa-user-plus"></i> <?= $edit_user ? 'Edit User' : 'New User' ?></h3>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <?php if ($edit_user): ?><input type="hidden" name="user_id" value="<?= $edit_user['id'] ?>"><?php endif; ?>
                        <div class="form-grid">
                            <input type="text" name="username" placeholder="Username" value="<?= $edit_user ? htmlspecialchars($edit_user['username']) : '' ?>" required class="form-input">
                            <input type="password" name="password" placeholder="<?= $edit_user ? 'Leave blank to keep current' : 'Password' ?>" <?= $edit_user ? '' : 'required' ?> class="form-input">
                        </div>
                        
                        <h4 class="perm-header">Permissions</h4>
                        <?php 
                        $perms = $edit_user ? json_decode($edit_user['permissions'], true) : [];
                        if (!is_array($perms)) $perms = [];
                        $modules = ['doctors' => 'Doctors', 'appointments' => 'Appointments', 'reports' => 'Reports', 'messages' => 'Messages', 'services' => 'Services', 'facilities' => 'Facilities', 'settings' => 'Settings'];
                        ?>
                        <div class="perm-grid">
                            <?php foreach ($modules as $key => $label): ?>
                                <?php 
                                $is_superadmin = ($edit_user && $edit_user['role'] === 'superadmin');
                                $is_checked = $is_superadmin || in_array($key, $perms);
                                ?>
                                <label class="perm-label">
                                    <input type="checkbox" name="permissions[]" value="<?= $key ?>" <?= $is_checked ? 'checked' : '' ?> <?= $is_superadmin ? 'disabled' : '' ?>>
                                    <?= $label ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="form-actions users-form-actions">
                            <button type="submit" name="save_user" class="btn-primary btn-font-outfit"><i class="fas fa-save"></i> Save User</button>
                            <a href="users.php" class="btn-cancel-link"><i class="fas fa-times"></i> Cancel</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="table-responsive">
                        <table>
                            <thead><tr><th>Username</th><th>Role</th><th>Permissions</th><th>Last Login</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php if ($admins && $admins->num_rows > 0): while ($row = $admins->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($row['username']) ?></strong></td>
                                        <td><span class="badge"><?= htmlspecialchars($row['role']) ?></span></td>
                                        <td>
                                            <?php 
                                            if ($row['role'] == 'superadmin' || $row['permissions'] == 'all') echo "All Access";
                                            else {
                                                $p = json_decode($row['permissions'], true);
                                                echo is_array($p) ? implode(', ', array_map('ucfirst', $p)) : 'None';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?= !empty($row['last_login']) ? date('d M Y, h:i A', strtotime($row['last_login'])) : '<span style="color: #94a3b8;">Never</span>' ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?edit_id=<?= $row['id'] ?>" class="edit-btn"><i class="fas fa-edit"></i> Edit</a>
                                                <?php if ($row['role'] != 'superadmin'): ?>
                                                <form method="POST" onsubmit="return confirm('Delete this user?');" class="action-form inline-form">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <button type="submit" name="delete_user" class="delete-btn"><i class="fas fa-trash-alt"></i> Delete</button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
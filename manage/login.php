<?php
session_start();

// If already logged in, redirect to admin panel
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

require_once '../includes/db.php';

// Fetch admin logo
$adminLogo = '../assets/images/logo.png';
$logoResult = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'admin_logo'");
if ($logoResult && $logoResult->num_rows > 0) {
    $logoValue = $logoResult->fetch_assoc()['setting_value'];
    if (!empty($logoValue)) {
        $adminLogo = '../' . $logoValue;
    }
}

$favicon = '';
$favResult = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'favicon'");
if ($favResult && $favResult->num_rows > 0) {
    $favicon = $favResult->fetch_assoc()['setting_value'];
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) die("CSRF validation failed.");
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        // Verify hashed password
        if (password_verify($password, $admin['password'])) {
            $updateStmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
            $updateStmt->bind_param("i", $admin['id']);
            $updateStmt->execute();

            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_permissions'] = $admin['permissions'] ? json_decode($admin['permissions'], true) : [];
            if ($admin['permissions'] === 'all' || $admin['role'] === 'superadmin') {
                $_SESSION['admin_permissions'] = 'all';
            }
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password!";
        }
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Max Diagnostic Center</title>
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700,800" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <?php if (!empty($favicon)): ?>
        <link rel="icon" href="../<?= htmlspecialchars($favicon) ?>?v=<?= time() ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin-login.css">
    <link rel="stylesheet" href="../assets/css/admin-responsive.css">
</head>
<body>
    <div class="login-box">
        <img src="<?= htmlspecialchars($adminLogo) ?>" alt="Max Diagnostic Center" class="brand-logo">
        <h2 class="login-title">Admin Access</h2>
        <?php if ($error): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <div class="input-group">
                <input type="text" name="username" class="form-input" placeholder="Username" required>
                <i class="fas fa-user"></i>
            </div>
            <div class="input-group">
                <input type="password" name="password" class="form-input" placeholder="Password" required>
                <i class="fas fa-lock"></i>
            </div>
            <button type="submit" name="login" class="login-btn">Login securely <i class="fas fa-arrow-right"></i></button>
        </form>
    </div>
</body>
</html>
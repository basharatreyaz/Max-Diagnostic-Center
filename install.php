<?php
$message = '';
$step = 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['install'])) {
    $host = $_POST['host'] ?? 'localhost';
    $username = $_POST['username'] ?? 'root';
    $password = $_POST['password'] ?? '';
    $dbname = $_POST['dbname'] ?? 'max';

    $conn = @new mysqli($host, $username, $password);
    if ($conn->connect_error) {
        $message = "Connection failed: " . $conn->connect_error;
    } else {
        if (!$conn->query("CREATE DATABASE IF NOT EXISTS `$dbname`")) {
            $message = "Error creating database: " . $conn->error;
        } else {
            $conn->select_db($dbname);

            $dbConfig = <<<'EOD'
<?php
$host = "DB_HOST";
$username = "DB_USER";
$password = "DB_PASS";
$dbname = "DB_NAME";

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// CSRF Protection
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Shared Appointment Booking Logic
function handleAppointmentBooking($conn, $postData) {
    $patient_name = $postData['patient_name'];
    $mobile = $postData['mobile'];
    $department = $postData['department'];
    $doctor = $postData['doctor'];
    $raw_date = str_replace('/', '-', $postData['appointment_date']);
    $parsed = strtotime($raw_date);
    $appointment_date = $parsed ? date('Y-m-d', $parsed) : $postData['appointment_date'];
    $age = isset($postData['age']) && $postData['age'] !== '' ? (int)$postData['age'] : null;
    $gender = isset($postData['gender']) && $postData['gender'] !== '' ? $postData['gender'] : null;
    $address = isset($postData['address']) && $postData['address'] !== '' ? $postData['address'] : null;
    $time_slot = isset($postData['time_slot']) && $postData['time_slot'] !== '' ? $postData['time_slot'] : null;
    $status = 'Pending';

    $stmt = $conn->prepare("INSERT INTO appointments (patient_name, mobile, department, doctor, appointment_date, time_slot, status, age, gender, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssss", $patient_name, $mobile, $department, $doctor, $appointment_date, $time_slot, $status, $age, $gender, $address);
    $result = $stmt->execute();

    if ($result) {
        $name_esc = $conn->real_escape_string(trim($patient_name));
        $phone_esc = $conn->real_escape_string(trim($mobile));
        $age_val = $age !== null ? $age : "NULL";
        $gender_val = $gender !== null ? "'" . $conn->real_escape_string(trim($gender)) . "'" : "NULL";
        $address_esc = $conn->real_escape_string(trim($address ?? ''));
        $doctor_esc = $conn->real_escape_string(trim($doctor ?? ''));
        
        $check = $conn->query("SELECT id FROM patients WHERE phone = '$phone_esc' AND LOWER(name) = LOWER('$name_esc')");
        if ($check && $check->num_rows == 0) {
            $tableCheck = $conn->query("SHOW TABLES LIKE 'patients'");
            if ($tableCheck && $tableCheck->num_rows > 0) {
                $conn->query("INSERT INTO patients (name, phone, age, gender, address, doctor_appointed) VALUES ('$name_esc', '$phone_esc', $age_val, $gender_val, '$address_esc', '$doctor_esc')");
            }
        }
    }
    return $result;
}

// Reusable pagination functions
function getPaginatedResults($conn, $countQuery, $dataQuery, $limit = 10) {
    // Check for a custom limit in settings
    $settings = getCachedSettings($conn);
    if (!empty($settings['items_per_page'])) {
        $limit = (int)$settings['items_per_page'];
    }

    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    $totalQuery = $conn->query($countQuery);
    $totalResult = $totalQuery ? $totalQuery->fetch_assoc()['total'] : 0;
    $totalPages = ceil($totalResult / $limit);

    $data = $conn->query($dataQuery . " LIMIT $limit OFFSET $offset");

    return [$data, $totalPages, $page];
}

function renderPaginationLinks($totalPages, $currentPage) {
    if ($totalPages > 1) {
        $queryParams = $_GET;
        unset($queryParams['page']);
        $queryString = http_build_query($queryParams);
        $queryString = $queryString ? '&' . $queryString : '';
        
        echo '<div style="display: flex; justify-content: center; gap: 10px; margin-top: 20px;">';
        for ($i = 1; $i <= $totalPages; $i++) {
            $activeStyle = ($i == $currentPage) ? 'background: var(--secondary);' : '';
            echo '<a href="?page=' . $i . $queryString . '" class="btn-primary" style="padding: 8px 15px; ' . $activeStyle . '">' . $i . '</a>';
        }
        echo '</div>';
    }
}

/**
 * Validates an uploaded file for security.
 * 
 * @param string $tmpName Temporary file path
 * @param string $originalName Original file name
 * @param int $size File size in bytes
 * @param array $allowedMimeTypes Mapping of extension to array of allowed MIME types
 * @param int $maxSize Maximum allowed file size in bytes
 * @return string|false The validated extension or false on failure
 */
function validateSecureUpload($tmpName, $originalName, $size, $allowedMimeTypes, $maxSize = 5242880) {
    if ($size > $maxSize || $size === 0) {
        return false;
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tmpName);
    finfo_close($finfo);
    
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    
    if (!array_key_exists($extension, $allowedMimeTypes) || !in_array($mimeType, (array)$allowedMimeTypes[$extension])) {
        return false;
    }
    
    return $extension;
}

// Settings Cache Functions
function getCachedSettings($conn) {
    $cacheFile = __DIR__ . '/settings_cache.json';
    if (file_exists($cacheFile)) {
        $cached = @json_decode(file_get_contents($cacheFile), true);
        if (is_array($cached)) return $cached;
    }
    $settings = [];
    $result = $conn->query("SELECT setting_key, setting_value FROM settings");
    if ($result) {
        while ($row = $result->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];
        @file_put_contents($cacheFile, json_encode($settings));
    }
    return $settings;
}

function clearSettingsCache() {
    $cacheFile = __DIR__ . '/settings_cache.json';
    if (file_exists($cacheFile)) @unlink($cacheFile);
}
?>
EOD;

            $dbConfig = str_replace(
                ['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME'],
                [
                    str_replace(['\\', '"'], ['\\\\', '\"'], $host),
                    str_replace(['\\', '"'], ['\\\\', '\"'], $username),
                    str_replace(['\\', '"'], ['\\\\', '\"'], $password),
                    str_replace(['\\', '"'], ['\\\\', '\"'], $dbname)
                ],
                $dbConfig
            );

            if (!is_dir('includes')) {
                mkdir('includes', 0755, true);
            }

            if (file_put_contents('includes/db.php', $dbConfig) === false) {
                $message = "Failed to write to includes/db.php. Please check directory permissions.";
            } else {
                // Admins
                $conn->query("CREATE TABLE IF NOT EXISTS admins (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    role VARCHAR(20) DEFAULT 'superadmin',
                    permissions TEXT NULL,
                    last_login DATETIME NULL
                )");
                $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $conn->query("INSERT IGNORE INTO admins (username, password, role, permissions) VALUES ('admin', '$defaultPassword', 'superadmin', 'all')");

                // Settings
                $conn->query("CREATE TABLE IF NOT EXISTS settings (
                    setting_key VARCHAR(100) PRIMARY KEY,
                    setting_value TEXT NOT NULL
                )");
                $conn->query("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES 
                    ('items_per_page', '10'), 
                    ('header_scripts', ''), 
                    ('body_start_scripts', ''), 
                    ('footer_scripts', ''), 
                    ('ticket_title', 'Appointment Token'), 
                    ('ticket_footer_text', ''), 
                    ('ticket_qr_link', ''), 
                    ('ticket_phone', '+91 7051674869'), 
                    ('ticket_address', 'Opposite JK Bank, Hyderbeigh, Jammu & Kashmir 193121'),
                    ('appointment_show_age', '1'), 
                    ('appointment_show_gender', '1'), 
                    ('appointment_show_address', '0'),
                    ('appointment_show_department', '1'),
                    ('appointment_show_doctor', '1'),
                    ('top_bar_address', 'Hyderbeigh, Baramulla, Jammu & Kashmir'), 
                    ('top_bar_timing', 'Mon - Sat: 8:00 AM - 8:00 PM | Sun: 9:00 AM - 2:00 PM'), 
                    ('top_bar_phone', '+91 7051674869'), 
                    ('top_bar_email', 'info@maxdiagnostic.com'),
                    ('social_facebook', '#'), 
                    ('social_instagram', '#'), 
                    ('social_whatsapp', '#')");

                // Appointments
                $conn->query("CREATE TABLE IF NOT EXISTS appointments (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    patient_name VARCHAR(100) NOT NULL,
                    mobile VARCHAR(20) NOT NULL,
                    age INT(3) DEFAULT NULL,
                    gender VARCHAR(20) DEFAULT NULL,
                    address TEXT DEFAULT NULL,
                    department VARCHAR(100) NOT NULL,
                    doctor VARCHAR(100) NOT NULL,
                    appointment_date DATE NOT NULL,
                    time_slot VARCHAR(50) DEFAULT NULL,
                    status ENUM('Pending', 'Confirmed') DEFAULT 'Pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");

                // Patients
                $conn->query("CREATE TABLE IF NOT EXISTS patients (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(150) NOT NULL,
                    phone VARCHAR(20) NOT NULL,
                    age INT(3) DEFAULT NULL,
                    gender VARCHAR(20) DEFAULT NULL,
                    address TEXT,
                    doctor_appointed VARCHAR(100),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");

                // Doctors
                $conn->query("CREATE TABLE IF NOT EXISTS doctors (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    degree VARCHAR(100) NOT NULL,
                    specialty VARCHAR(100) NOT NULL,
                    days VARCHAR(100) NOT NULL,
                    time VARCHAR(100) NOT NULL,
                    image VARCHAR(255) NOT NULL,
                    is_active TINYINT(1) DEFAULT 1
                )");

                // Facilities
                $conn->query("CREATE TABLE IF NOT EXISTS facilities (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(150) NOT NULL,
                    image VARCHAR(255) NOT NULL
                )");
                
                $conn->query("INSERT IGNORE INTO facilities (name, image) VALUES 
                    ('Digital X-Ray', 'assets/images/xray.jpg'),
                    ('Ultrasound', 'assets/images/ultrasound.jpg'),
                    ('ECG', 'assets/images/ecg.jpg'),
                    ('Pathology Lab', 'assets/images/pathology.jpg'),
                    ('Automated Analyzer', 'assets/images/auto.jpg'),
                    ('Sample Collection', 'assets/images/sample.jpg')");

                // Reports
                $conn->query("CREATE TABLE IF NOT EXISTS reports (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    tracking_id VARCHAR(50) UNIQUE,
                    patient_first_name VARCHAR(100) NOT NULL,
                    mobile VARCHAR(20) NOT NULL,
                    test_name VARCHAR(255) NOT NULL,
                    report_file TEXT,
                    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");

                // Inquiries
                $conn->query("CREATE TABLE IF NOT EXISTS inquiries (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    subject VARCHAR(200) NOT NULL,
                    message TEXT NOT NULL,
                    status ENUM('Unread', 'Read') DEFAULT 'Unread',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");

                // Services
                $conn->query("CREATE TABLE IF NOT EXISTS services (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(150) NOT NULL,
                    description TEXT NOT NULL,
                    image VARCHAR(255) DEFAULT NULL,
                    price VARCHAR(50) DEFAULT NULL
                )");
                
                $conn->query("INSERT IGNORE INTO services (title, description) VALUES ('Diagnostic Services', 'Comprehensive pathology and radiology services including blood tests, biochemistry, and microbiology.'), ('Specialist Consultations', 'Expert consultations in general medicine, cardiology, orthopaedics, and gynaecology.'), ('Health Packages', 'Wide range of preventive health check-up packages for you and your family.'), ('Home Collection', 'Convenient home sample collection services by trained phlebotomists.')");

                $step = 2;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Installation - Max Diagnostic Center</title>
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700,800" rel="stylesheet">
    <style>
        :root {
            --primary: #00a39d;
            --secondary: #0b2b52;
            --text-muted: #666;
            --bg-light: #f8fcff;
            --border: #ddd;
        }
        body { background: var(--bg-light); display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: 'Outfit', sans-serif; margin: 0; }
        .install-box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); max-width: 500px; width: 100%; text-align: center; }
        .install-box h2 { color: var(--secondary); margin-top: 0; margin-bottom: 20px; }
        .form-input { width: 100%; padding: 15px; margin-bottom: 15px; border: 1px solid var(--border); border-radius: 8px; font-family: 'Outfit', sans-serif; box-sizing: border-box; outline: none; transition: 0.3s; }
        .form-input:focus { border-color: var(--primary); }
        .btn-primary { width: 100%; padding: 15px; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; font-family: 'Outfit', sans-serif; font-size: 16px; }
        .btn-primary:hover { background: var(--secondary); }
        .error-msg { background: #fef2f2; color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; border: 1px solid #fca5a5; }
        .success-msg { background: #ecfdf5; color: #10b981; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; border: 1px solid #6ee7b7; }
    </style>
</head>
<body>
    <div class="install-box">
        <?php if ($step == 1): ?>
            <h2>Database Configuration</h2>
            <p style="color: var(--text-muted); margin-bottom: 25px;">Enter your database connection details to install the application.</p>
            <?php if ($message): ?>
                <div class="error-msg"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="text" name="host" class="form-input" placeholder="Database Host (e.g., localhost)" value="<?= htmlspecialchars($_POST['host'] ?? 'localhost') ?>" required>
                <input type="text" name="username" class="form-input" placeholder="Database Username (e.g., root)" value="<?= htmlspecialchars($_POST['username'] ?? 'root') ?>" required>
                <input type="password" name="password" class="form-input" placeholder="Database Password">
                <input type="text" name="dbname" class="form-input" placeholder="Database Name (e.g., max)" value="<?= htmlspecialchars($_POST['dbname'] ?? 'max') ?>" required>
                <button type="submit" name="install" class="btn-primary">Install Database</button>
            </form>
        <?php else: ?>
            <h2>Installation Complete!</h2>
            <div class="success-msg">Database and tables have been set up successfully.</div>
            <p style="color: var(--text-muted); margin-bottom: 20px;">The default admin account is <strong>admin</strong> with password <strong>admin123</strong>.</p>
            <p style="color: #ef4444; font-weight: 600; margin-bottom: 25px;">For security reasons, please delete the install.php file immediately.</p>
            <a href="index.php" class="btn-primary" style="display: inline-block; text-decoration: none; box-sizing: border-box;">Go to Website</a>
        <?php endif; ?>
    </div>
</body>
</html>
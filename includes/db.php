<?php
$host = "sql101.infinityfree.com";
$username = "if0_41953766";
$password = "7fp9okG4XNJ";
$dbname = "if0_41953766_maxcenter";
// $host="localhost";
// $username="root";
// $password="";
// $dbname="max";

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
    $appointment_date = $postData['appointment_date'];
    $age = isset($postData['age']) && $postData['age'] !== '' ? (int)$postData['age'] : null;
    $gender = isset($postData['gender']) && $postData['gender'] !== '' ? $postData['gender'] : null;
    $address = isset($postData['address']) && $postData['address'] !== '' ? $postData['address'] : null;
    $status = 'Pending';

    $stmt = $conn->prepare("INSERT INTO appointments (patient_name, mobile, department, doctor, appointment_date, status, age, gender, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssiss", $patient_name, $mobile, $department, $doctor, $appointment_date, $status, $age, $gender, $address);
    return $stmt->execute();
}

// Reusable pagination functions
function getPaginatedResults($conn, $countQuery, $dataQuery, $limit = 10) {
    // Check for a custom limit in settings
    $settingResult = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'items_per_page'");
    if ($settingResult && $settingResult->num_rows > 0) {
        $limit = (int)$settingResult->fetch_assoc()['setting_value'];
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
?>
<?php
session_start();
require_once 'includes/db.php';

// Ensure the user is an authenticated superadmin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || $_SESSION['admin_role'] !== 'superadmin') {
    header("HTTP/1.1 403 Forbidden");
    die("Access denied. Only superadmins can perform this action.");
}

// Warning: This script will remove ALL data from the database tables!

$tables = [
    'admins',
    'settings',
    'appointments',
    'doctors',
    'facilities',
    'reports',
    'inquiries',
    'services'
];

// Temporarily disable foreign key checks to prevent constraint errors
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

foreach ($tables as $table) {
    $conn->query("TRUNCATE TABLE `$table`");
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "All data has been successfully removed from the database tables. Please run install.php to restore the default admin account and settings.";
?>
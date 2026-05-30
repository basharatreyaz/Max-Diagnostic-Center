<?php
require_once 'includes/db.php';

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
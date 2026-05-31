<?php
session_start();
require_once 'includes/db.php';
include 'includes/header.php';
?>
<section class="page-header error-page-header">
    <div class="container">
        <h1>404</h1>
        <p>Page Not Found</p>
    </div>
</section>
<section class="error-content">
    <div class="container">
        <i class="fas fa-exclamation-triangle error-icon"></i>
        <h2 class="error-title">Oops! We can't find that page.</h2>
        <p class="error-message">
            The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
        </p>
        <a href="index.php" class="primary-btn error-home-btn">
            <i class="fas fa-home"></i> Back to Home
        </a>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
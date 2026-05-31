<?php
session_start();
require_once 'includes/db.php';
include 'includes/header.php';
?>
<section class="page-header error-page-header">
    <div class="container">
        <h1>500</h1>
        <p>Internal Server Error</p>
    </div>
</section>
<section class="error-content">
    <div class="container">
        <i class="fas fa-server error-icon"></i>
        <h2 class="error-title">Oops! Something went wrong on our end.</h2>
        <p class="error-message">
            We are currently experiencing an internal server problem. Please try again later.
        </p>
        <a href="index.php" class="primary-btn error-home-btn">
            <i class="fas fa-home"></i> Back to Home
        </a>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
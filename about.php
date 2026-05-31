<?php
session_start();
require_once 'includes/db.php';
include 'includes/header.php';
?>

<!-- ==========================
     PAGE HEADER
=========================== -->
<section class="page-header about-page-header">
    <div class="container">
        <h1>About Us</h1>
        <p>Committed to Accurate Diagnostics & Better Health</p>
    </div>
</section>

<!-- ==========================
     ABOUT CONTENT
=========================== -->
<section class="about-content about-content-section">
    <div class="container">
        <div class="about-flex-container">
            <div class="about-img-container">
                <img src="assets/images/hero-lab.jpg" alt="About Max Diagnostic Center">
            </div>
            <div class="about-text-container">
                <h2 class="about-title">Welcome to Max Diagnostic Center</h2>
                <p class="about-text">
                    Located in the heart of Hyderbeigh, Max Diagnostic Center is a state-of-the-art facility dedicated to providing accurate, reliable, and timely diagnostic services. We understand that accurate diagnosis is the cornerstone of effective medical treatment.
                </p>
                <p class="about-text">
                    Equipped with the latest medical technology and staffed by highly experienced specialists and technicians, we offer a comprehensive range of diagnostic and consultation services. Our patient-first approach ensures that you receive the highest quality of care in a comfortable and hygienic environment.
                </p>
                <div class="mission-vision-container">
                    <div class="mission-card">
                        <h4 class="mission-title">Our Mission</h4>
                        <p class="mission-text">To deliver high-quality diagnostic services with unmatched accuracy and patient care.</p>
                    </div>
                    <div class="mission-card">
                        <h4 class="mission-title">Our Vision</h4>
                        <p class="mission-text">To be the most trusted healthcare partner in the region through continuous innovation.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==========================
     CORE VALUES
=========================== -->
<section class="core-values">
    <div class="container">
        <div class="core-values-header">
            <h2 class="core-values-title">Our Core Values</h2>
            <p class="core-values-subtitle">The principles that guide us in delivering exceptional healthcare services every single day.</p>
        </div>
        <div class="core-values-grid">
            <div class="value-card">
                <div class="value-icon-wrapper value-icon-accuracy">
                    <i class="fas fa-bullseye"></i>
                </div>
                <h3 class="value-title">Accuracy</h3>
                <p class="value-text">We employ rigorous quality control measures to ensure every test result is precise and reliable.</p>
            </div>
            <div class="value-card">
                <div class="value-icon-wrapper value-icon-compassion">
                    <i class="fas fa-heart"></i>
                </div>
                <h3 class="value-title">Compassion</h3>
                <p class="value-text">We treat every patient with empathy, respect, and dignity, making their visit as comfortable as possible.</p>
            </div>
            <div class="value-card">
                <div class="value-icon-wrapper value-icon-efficiency">
                    <i class="fas fa-bolt"></i>
                </div>
                <h3 class="value-title">Efficiency</h3>
                <p class="value-text">We value your time, ensuring fast processing of tests and quick delivery of diagnostic reports.</p>
            </div>
        </div>
    </div>
</section>

<!-- ==========================
     CTA SECTION
=========================== -->
<section class="cta-section">
    <div class="container">
        <h2 class="cta-title">Experience the Best in Healthcare</h2>
        <p class="cta-text">
            Your health is your greatest wealth. Let us help you maintain it with our expert diagnostic and consultation services.
        </p>
        <a href="contact.php" class="primary-btn cta-btn">
            <i class="fas fa-phone-alt btn-icon-gap"></i> Contact Us Today
        </a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
<?php
session_start();
require_once 'includes/db.php';
include 'includes/header.php';
?>

<!-- ==========================
     PAGE HEADER
=========================== -->
<section class="page-header services-page-header">
    <div class="container">
        <h1>Our Services</h1>
        <p>Comprehensive Diagnostic & Healthcare Solutions</p>
    </div>
</section>

<!-- ==========================
     SERVICES
=========================== -->
<section class="services-page services-page-section">
    <div class="container">
        
        <div class="services-page-intro">
            <h2 class="services-page-title">Excellence in Diagnostics</h2>
            <p class="services-page-text">
                At Max Diagnostic Center, we offer an extensive array of diagnostic and healthcare services tailored to meet your medical needs. With state-of-the-art technology and a team of dedicated professionals, we ensure high accuracy and swift reporting to facilitate prompt medical treatments.
            </p>
        </div>

        <div class="services-page-grid">
            <?php
            $tableCheck = $conn->query("SHOW TABLES LIKE 'services'");
            if ($tableCheck && $tableCheck->num_rows > 0) {
                $result = $conn->query("SELECT * FROM services ORDER BY id ASC");
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        ?>
                        <div class="service-item-card">
                            <?php if(!empty($row['image'])): ?>
                                <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['title']) ?>" class="service-item-img">
                            <?php else: ?>
                                <div class="service-item-placeholder">
                                    <?= htmlspecialchars($row['title']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="service-item-content">
                                <h3 class="service-item-title"><?= htmlspecialchars($row['title']) ?></h3>
                                <?php if(!empty($row['price'])): ?>
                                    <div class="service-item-price"><?= htmlspecialchars($row['price']) ?></div>
                                <?php endif; ?>
                                <p class="service-item-desc"><?= htmlspecialchars($row['description']) ?></p>
                                <a href="appointments.php" class="service-item-link"><i class="fas fa-arrow-right"></i> Book Now</a>
                            </div>
                        </div>
                        <?php
                    }
                }
            }
            ?>
        </div>
    </div>
</section>

<!-- ==========================
     WHY TRUST US
=========================== -->
<section class="services-info trust-section">
    <div class="container">
        <div class="trust-container">
            <div class="trust-left">
                <h2 class="trust-title">Why Trust Our Services?</h2>
                <ul class="trust-list">
                    <li class="trust-list-item">
                        <div class="trust-icon-wrapper"><i class="fas fa-check"></i></div>
                        <div>
                            <strong class="trust-item-title">World-Class Equipment</strong>
                            <span class="trust-item-desc">We invest in the latest medical technology to guarantee precision and reliability in all diagnostic procedures.</span>
                        </div>
                    </li>
                    <li class="trust-list-item">
                        <div class="trust-icon-wrapper"><i class="fas fa-check"></i></div>
                        <div>
                            <strong class="trust-item-title">Experienced Specialists</strong>
                            <span class="trust-item-desc">Our team consists of highly skilled doctors, pathologists, and radiologists with extensive experience.</span>
                        </div>
                    </li>
                    <li class="trust-list-item">
                        <div class="trust-icon-wrapper"><i class="fas fa-check"></i></div>
                        <div>
                            <strong class="trust-item-title">Fast & Secure Reports</strong>
                            <span class="trust-item-desc">Access your diagnostic reports online securely, often within 24 hours of sample collection.</span>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="trust-right">
                <div class="trust-box">
                    <i class="fas fa-heart-pulse trust-box-icon"></i>
                    <h3 class="trust-box-title">Your Health is Our Priority</h3>
                    <p class="trust-box-desc">Providing exceptional healthcare solutions with compassion and accuracy.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==========================
     CTA SECTION
=========================== -->
<section class="cta-section">
    <div class="container">
        <h2 class="cta-title">Ready to Prioritize Your Health?</h2>
        <a href="appointments.php" class="primary-btn cta-btn cta-btn-margin"><i class="fas fa-calendar-check btn-icon-gap"></i> Book Appointment Now</a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
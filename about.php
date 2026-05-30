<?php
session_start();
require_once 'includes/db.php';
include 'includes/header.php';
?>

<!-- ==========================
     PAGE HEADER
=========================== -->
<section class="page-header" style="background: var(--primary); padding: 60px 0; text-align: center; color: white;">
    <div class="container">
        <h1 style="margin: 0; font-size: 36px;">About Us</h1>
        <p style="margin-top: 10px; font-size: 18px; opacity: 0.9;">Committed to Accurate Diagnostics & Better Health</p>
    </div>
</section>

<!-- ==========================
     ABOUT CONTENT
=========================== -->
<section class="about-content" style="padding: 80px 0;">
    <div class="container">
        <div style="display: flex; flex-wrap: wrap; gap: 40px; align-items: center;">
            <div style="flex: 1 1 400px;">
                <img src="assets/images/hero-lab.jpg" alt="About Max Diagnostic Center" style="width: 100%; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            </div>
            <div style="flex: 1 1 500px;">
                <h2 style="color: var(--secondary); margin-bottom: 20px; font-size: 32px;">Welcome to Max Diagnostic Center</h2>
                <p style="color: var(--text-muted); font-size: 16px; line-height: 1.8; margin-bottom: 20px;">
                    Located in the heart of Hyderbeigh, Max Diagnostic Center is a state-of-the-art facility dedicated to providing accurate, reliable, and timely diagnostic services. We understand that accurate diagnosis is the cornerstone of effective medical treatment.
                </p>
                <p style="color: var(--text-muted); font-size: 16px; line-height: 1.8; margin-bottom: 30px;">
                    Equipped with the latest medical technology and staffed by highly experienced specialists and technicians, we offer a comprehensive range of diagnostic and consultation services. Our patient-first approach ensures that you receive the highest quality of care in a comfortable and hygienic environment.
                </p>
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <div style="flex: 1 1 200px; background: var(--bg-light); padding: 15px 20px; border-radius: 8px; border-left: 4px solid var(--primary);">
                        <h4 style="margin: 0 0 5px; color: var(--secondary);">Our Mission</h4>
                        <p style="margin: 0; color: var(--text-muted); font-size: 14px; line-height: 1.5;">To deliver high-quality diagnostic services with unmatched accuracy and patient care.</p>
                    </div>
                    <div style="flex: 1 1 200px; background: var(--bg-light); padding: 15px 20px; border-radius: 8px; border-left: 4px solid var(--primary);">
                        <h4 style="margin: 0 0 5px; color: var(--secondary);">Our Vision</h4>
                        <p style="margin: 0; color: var(--text-muted); font-size: 14px; line-height: 1.5;">To be the most trusted healthcare partner in the region through continuous innovation.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==========================
     CORE VALUES
=========================== -->
<section class="core-values" style="background: var(--bg-light); padding: 80px 0;">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <h2 style="color: var(--secondary); margin-bottom: 15px; font-size: 32px;">Our Core Values</h2>
            <p style="color: var(--text-muted); font-size: 16px; max-width: 600px; margin: auto;">The principles that guide us in delivering exceptional healthcare services every single day.</p>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
            <div style="background: white; padding: 30px; border-radius: 12px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                <div style="width: 70px; height: 70px; background: #e0f2fe; color: #0284c7; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 20px;">
                    <i class="fas fa-bullseye"></i>
                </div>
                <h3 style="color: var(--secondary); margin-bottom: 15px; font-size: 20px;">Accuracy</h3>
                <p style="color: var(--text-muted); font-size: 15px; margin: 0; line-height: 1.6;">We employ rigorous quality control measures to ensure every test result is precise and reliable.</p>
            </div>
            <div style="background: white; padding: 30px; border-radius: 12px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                <div style="width: 70px; height: 70px; background: #ecfdf5; color: #059669; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 20px;">
                    <i class="fas fa-heart"></i>
                </div>
                <h3 style="color: var(--secondary); margin-bottom: 15px; font-size: 20px;">Compassion</h3>
                <p style="color: var(--text-muted); font-size: 15px; margin: 0; line-height: 1.6;">We treat every patient with empathy, respect, and dignity, making their visit as comfortable as possible.</p>
            </div>
            <div style="background: white; padding: 30px; border-radius: 12px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                <div style="width: 70px; height: 70px; background: #fef3c7; color: #d97706; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 20px;">
                    <i class="fas fa-bolt"></i>
                </div>
                <h3 style="color: var(--secondary); margin-bottom: 15px; font-size: 20px;">Efficiency</h3>
                <p style="color: var(--text-muted); font-size: 15px; margin: 0; line-height: 1.6;">We value your time, ensuring fast processing of tests and quick delivery of diagnostic reports.</p>
            </div>
        </div>
    </div>
</section>

<!-- ==========================
     CTA SECTION
=========================== -->
<section class="cta-section" style="background: var(--secondary); padding: 80px 0; text-align: center; color: white;">
    <div class="container">
        <h2 style="margin-bottom: 20px; font-size: 36px;">Experience the Best in Healthcare</h2>
        <p style="font-size: 18px; margin-bottom: 35px; max-width: 600px; margin-left: auto; margin-right: auto; opacity: 0.9; line-height: 1.6;">
            Your health is your greatest wealth. Let us help you maintain it with our expert diagnostic and consultation services.
        </p>
        <a href="contact.php" class="primary-btn" style="display: inline-block; text-decoration: none; font-size: 18px; padding: 16px 35px;">
            <i class="fas fa-phone-alt" style="margin-right: 8px;"></i> Contact Us Today
        </a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
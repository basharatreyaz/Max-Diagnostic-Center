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
        <h1 style="margin: 0; font-size: 36px;">Our Services</h1>
        <p style="margin-top: 10px; font-size: 18px; opacity: 0.9;">Comprehensive Diagnostic & Healthcare Solutions</p>
    </div>
</section>

<!-- ==========================
     SERVICES
=========================== -->
<section class="services-page" style="padding: 80px 0; background: var(--bg-light);">
    <div class="container">
        
        <div style="text-align: center; margin-bottom: 60px; max-width: 800px; margin-left: auto; margin-right: auto;">
            <h2 style="color: var(--secondary); margin-bottom: 20px; font-size: 32px;">Excellence in Diagnostics</h2>
            <p style="color: var(--text-muted); font-size: 16px; line-height: 1.8;">
                At Max Diagnostic Center, we offer an extensive array of diagnostic and healthcare services tailored to meet your medical needs. With state-of-the-art technology and a team of dedicated professionals, we ensure high accuracy and swift reporting to facilitate prompt medical treatments.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px;">
            <?php
            $tableCheck = $conn->query("SHOW TABLES LIKE 'services'");
            if ($tableCheck && $tableCheck->num_rows > 0) {
                $result = $conn->query("SELECT * FROM services ORDER BY id ASC");
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        ?>
                        <div style="background: white; border-radius: 12px; overflow: hidden; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.05); transition: 0.3s; display: flex; flex-direction: column;">
                            <?php if(!empty($row['image'])): ?>
                                <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['title']) ?>" style="width: 100%; height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div style="width: 100%; height: 200px; background: #e0f2fe; display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 24px; font-weight: 600; padding: 20px;">
                                    <?= htmlspecialchars($row['title']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div style="padding: 30px; flex-grow: 1; display: flex; flex-direction: column;">
                                <h3 style="color: var(--secondary); margin-bottom: 15px; font-size: 22px; margin-top: 10px;"><?= htmlspecialchars($row['title']) ?></h3>
                                <?php if(!empty($row['price'])): ?>
                                    <div style="font-size: 18px; font-weight: 700; color: var(--primary); margin-bottom: 15px;"><?= htmlspecialchars($row['price']) ?></div>
                                <?php endif; ?>
                                <p style="color: var(--text-muted); line-height: 1.6; margin: 0 0 20px; flex-grow: 1;"><?= htmlspecialchars($row['description']) ?></p>
                                <a href="appointments.php" style="color: var(--primary); font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px;"><i class="fas fa-arrow-right"></i> Book Now</a>
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
<section class="services-info" style="padding: 80px 0;">
    <div class="container">
        <div style="display: flex; flex-wrap: wrap; gap: 50px; align-items: center;">
            <div style="flex: 1 1 400px;">
                <h2 style="color: var(--secondary); margin-bottom: 20px; font-size: 32px;">Why Trust Our Services?</h2>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 15px;">
                    <li style="display: flex; gap: 15px; align-items: flex-start;">
                        <div style="width: 30px; height: 30px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 14px;"><i class="fas fa-check"></i></div>
                        <div>
                            <strong style="color: var(--secondary); display: block; margin-bottom: 5px; font-size: 18px;">World-Class Equipment</strong>
                            <span style="color: var(--text-muted); line-height: 1.6;">We invest in the latest medical technology to guarantee precision and reliability in all diagnostic procedures.</span>
                        </div>
                    </li>
                    <li style="display: flex; gap: 15px; align-items: flex-start;">
                        <div style="width: 30px; height: 30px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 14px;"><i class="fas fa-check"></i></div>
                        <div>
                            <strong style="color: var(--secondary); display: block; margin-bottom: 5px; font-size: 18px;">Experienced Specialists</strong>
                            <span style="color: var(--text-muted); line-height: 1.6;">Our team consists of highly skilled doctors, pathologists, and radiologists with extensive experience.</span>
                        </div>
                    </li>
                    <li style="display: flex; gap: 15px; align-items: flex-start;">
                        <div style="width: 30px; height: 30px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 14px;"><i class="fas fa-check"></i></div>
                        <div>
                            <strong style="color: var(--secondary); display: block; margin-bottom: 5px; font-size: 18px;">Fast & Secure Reports</strong>
                            <span style="color: var(--text-muted); line-height: 1.6;">Access your diagnostic reports online securely, often within 24 hours of sample collection.</span>
                        </div>
                    </li>
                </ul>
            </div>
            <div style="flex: 1 1 400px; text-align: center;">
                <div style="background: var(--bg-light); border-radius: 12px; padding: 40px; box-shadow: inset 0 0 20px rgba(0,0,0,0.02);">
                    <i class="fas fa-heart-pulse" style="font-size: 100px; color: var(--primary); margin-bottom: 20px; display: block; opacity: 0.8;"></i>
                    <h3 style="color: var(--secondary); margin-bottom: 15px;">Your Health is Our Priority</h3>
                    <p style="color: var(--text-muted); margin: 0; line-height: 1.6;">Providing exceptional healthcare solutions with compassion and accuracy.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==========================
     CTA SECTION
=========================== -->
<section class="cta-section" style="background: var(--secondary); padding: 80px 0; text-align: center; color: white;">
    <div class="container">
        <h2 style="margin-bottom: 20px; font-size: 36px;">Ready to Prioritize Your Health?</h2>
        <a href="appointments.php" class="primary-btn" style="display: inline-block; text-decoration: none; font-size: 18px; padding: 16px 35px; margin-top: 15px;"><i class="fas fa-calendar-check" style="margin-right: 8px;"></i> Book Appointment Now</a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
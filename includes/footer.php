<!-- ==========================
     FOOTER
=========================== -->

<footer class="footer">

    <div class="container">

        <div class="footer-grid">

            <div class="footer-col">

                <img class="footer-logo"
                    src="<?= htmlspecialchars($frontend_logo ?? 'assets/images/logo.png') ?>"
                    alt="Max Diagnostic Center">

                <p class="footer-desc">

                    Max Diagnostic Center is committed
                    to providing accurate diagnostics,
                    expert care and excellent patient
                    service.

                </p>

                <?php
                $social_facebook = $app_settings['social_facebook'] ?? '#';
                $social_instagram = $app_settings['social_instagram'] ?? '#';
                $social_whatsapp = $app_settings['social_whatsapp'] ?? '#';
                $whatsapp_number = $app_settings['whatsapp_number'] ?? '917051674869';
                $whatsapp_chat_link = !empty(trim($whatsapp_number)) ? "https://wa.me/" . preg_replace('/[^0-9]/', '', $whatsapp_number) : '#';
                ?>
                <div class="social-icons">

                    <a class="social-link" href="<?= htmlspecialchars($social_facebook) ?>">
                        <i class="fab fa-facebook-f social-icon-inner"></i>
                    </a>

                    <a class="social-link" href="<?= htmlspecialchars($social_instagram) ?>">
                        <i class="fab fa-instagram social-icon-inner"></i>
                    </a>

                    <a class="social-link" href="<?= htmlspecialchars($social_whatsapp) ?>">
                        <i class="fab fa-whatsapp social-icon-inner"></i>
                    </a>

                </div>

            </div>

            <div class="footer-col">

                <h4 class="footer-title">Quick Links</h4>

                <ul class="footer-list">

                    <li class="footer-item"><a class="footer-link" href="index.php">Home</a></li>
                    <li class="footer-item"><a class="footer-link" href="about.php">About Us</a></li>
                    <li class="footer-item"><a class="footer-link" href="services.php">Services</a></li>
                        <li class="footer-item"><a class="footer-link" href="specialists.php">Specialists</a></li>
                    <li class="footer-item"><a class="footer-link" href="appointments.php">Appointments</a></li>
                    <li class="footer-item"><a class="footer-link" href="reports.php">Reports</a></li>

                </ul>

            </div>

            <div class="footer-col">

                <h4 class="footer-title">Our Services</h4>

                <ul class="footer-list">

                    <li class="footer-item">Blood Testing</li>
                    <li class="footer-item">Pathology</li>
                    <li class="footer-item">Radiology</li>
                    <li class="footer-item">Cardiology</li>
                    <li class="footer-item">Orthopaedics</li>
                    <li class="footer-item">Gynaecology</li>

                </ul>

            </div>

            <div class="footer-col">

                <h4 class="footer-title">Patient Services</h4>

                <ul class="footer-list">

                    <li class="footer-item"><a href="appointments.php" class="footer-link">Book Appointment</a></li>
                    <li class="footer-item"><a href="reports.php" class="footer-link">Download Reports</a></li>
                    <li class="footer-item">Home Collection</li>
                    <li class="footer-item">Health Packages</li>
                    <li class="footer-item">FAQs</li>

                </ul>

            </div>

            <div class="footer-col">

                <h4 class="footer-title">Contact Us</h4>

                <ul class="footer-list">

                    <li class="footer-item">
                        <?= nl2br(htmlspecialchars($top_bar_address ?? 'Opposite JK Bank, Hyderbeigh, Jammu & Kashmir 193121')) ?>
                    </li>

                    <li class="footer-item">
                        <?= htmlspecialchars($top_bar_phone ?? '+91 7051674869') ?>
                    </li>

                    <li class="footer-item">
                        <?= htmlspecialchars($top_bar_email ?? 'info@maxdiagnostic.com') ?>
                    </li>

                    <li class="footer-item">
                        <?= htmlspecialchars($top_bar_timing ?? 'Mon - Sat: 8:00 AM - 8:00 PM | Sun: 9:00 AM - 2:00 PM') ?>
                    </li>

                </ul>

            </div>

        </div>

    </div>

</footer>

<!-- COPYRIGHT -->

<div class="copyright">

    <div class="container">

        <p class="copyright-text">

            ©<?php echo date('Y') ?> Max Diagnostic Center,
            Hyderbeigh. All Rights Reserved.

        </p>

        <div class="copyright-links">

            <a class="copyright-link" target="_blank" href="https://wpsitedoctors.com">
                Designed and developed by WP Site Doctors
            </a>

           

        </div>
        <div class="copyright-links">

            <a class="copyright-link" href="#">
                Privacy Policy
            </a>

            <span class="copyright-divider">|</span>

            <a class="copyright-link" href="#">
                Terms & Conditions
            </a>

        </div>


    </div>

</div>

<a href="#" class="back-to-top" id="backToTop" title="Back to top">
    <i class="fas fa-chevron-up"></i>
</a>

<div class="bottom-action-bar">
    <a href="<?= htmlspecialchars($whatsapp_chat_link) ?>" class="action-btn whatsapp-btn" target="_blank">
        <i class="fab fa-whatsapp"></i> WhatsApp
    </a>
    <a href="appointments.php" class="action-btn appointment-btn-bar">
        <i class="fas fa-calendar-check"></i> Book Appointment
    </a>
    <a href="reports.php" class="action-btn report-btn-bar">
        <i class="fas fa-file-medical"></i> Download Reports
    </a>
</div>

<div id="demo-popup" class="demo-popup-overlay">
    <div class="demo-popup-content">
        <h3>Demo Version</h3>
        <p>This is a demo version and may have bugs and should not be used for production.</p>
        <button id="demo-popup-close">I Understand</button>
    </div>
</div>

<script>
    const mobileMenu = document.getElementById('mobile-menu');
    const navbar = document.getElementById('navbar');
    const menuOverlay = document.getElementById('menu-overlay');
    
    function toggleMenu() {
        navbar.classList.toggle('active');
        const isActive = navbar.classList.contains('active');
        
        if (menuOverlay) {
            menuOverlay.style.display = isActive ? 'block' : 'none';
            document.body.style.overflow = isActive ? 'hidden' : ''; // Prevent body scroll
        }
        
        const icon = mobileMenu.querySelector('i');
        if (isActive) {
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-times');
        } else {
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
    }

    if (mobileMenu) mobileMenu.addEventListener('click', toggleMenu);
    if (menuOverlay) menuOverlay.addEventListener('click', toggleMenu);

    // Dynamic Doctor Filtering based on Department
    document.addEventListener("DOMContentLoaded", function() {
        const deptSelect = document.getElementById('department-select');
        const docSelect = document.getElementById('doctor-select');
        
        if(deptSelect && docSelect) {
            const docOptions = docSelect.querySelectorAll('.doctor-option');
            
            function filterDoctors(selectedDept) {
                const sDept = selectedDept.trim().toLowerCase();
                docOptions.forEach(function(option) {
                    const dDept = (option.getAttribute('data-department') || '').trim().toLowerCase();
                    if (sDept === '' || dDept === sDept) {
                        option.style.display = ''; // Show
                    } else {
                        option.style.display = 'none'; // Hide
                    }
                });
            }
            
            // Initial filter on load (without resetting doctor selection)
            filterDoctors(deptSelect.value);
            
            deptSelect.addEventListener('change', function() {
                filterDoctors(this.value);
                docSelect.value = ''; // Reset doctor selection
            });
        }
    });

    // Back to Top Button
    const backToTopBtn = document.getElementById('backToTop');
    if (backToTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        });

        backToTopBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // Demo Popup
    const demoPopup = document.getElementById('demo-popup');
    const demoPopupClose = document.getElementById('demo-popup-close');
    
    if (demoPopup && !localStorage.getItem('demoPopupShown')) {
        demoPopup.style.display = 'flex';
    }
    
    if (demoPopupClose) {
        demoPopupClose.addEventListener('click', function() {
            demoPopup.style.display = 'none';
            localStorage.setItem('demoPopupShown', 'true');
        });
    }
</script>

<?php
    // Footer code injection
    if (isset($app_settings['footer_scripts'])) {
        echo $app_settings['footer_scripts'];
    }
?>

</body>
</html>
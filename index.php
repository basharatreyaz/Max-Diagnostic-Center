<?php
session_start();
require_once 'includes/db.php';
$appointment_msg = '';
$appointment_error = '';

// Fetch appointment form settings
$form_settings = [];
if (isset($conn)) {
    $settingsResult = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'appointment_%'");
    if ($settingsResult) {
        while ($row = $settingsResult->fetch_assoc()) {
            $form_settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}

if (isset($_SESSION['appointment_msg'])) {
    $appointment_msg = $_SESSION['appointment_msg'];
    unset($_SESSION['appointment_msg']);
}

if (isset($_SESSION['appointment_error'])) {
    $appointment_error = $_SESSION['appointment_error'];
    unset($_SESSION['appointment_error']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    if (handleAppointmentBooking($conn, $_POST)) {
        $_SESSION['appointment_msg'] = "Your appointment has been booked successfully! We will contact you shortly.";
    } else {
        $_SESSION['appointment_error'] = "Error booking appointment. Please try again.";
    }
    header("Location: index.php#appointment-section");
    exit;
}
?>
<?php include 'includes/header.php'; ?>

    <!-- ==========================
         HERO SECTION
    =========================== -->

    <section class="hero">

        <div class="container hero-grid">

            <div class="hero-content">

                <h1 class="hero-title">
                    Advanced Diagnostics & <span class="hero-highlight">Specialist Care</span><br>
                    Under One Roof
                </h1>

                <p class="hero-description">
                    Accurate testing. Expert care.
                    Faster results. Your health is our priority.
                </p>

                <div class="hero-buttons">

                    <a href="#appointment-section" class="primary-btn">

                        <i class="fas fa-calendar-check btn-icon"></i>
                        Book Appointment

                    </a>
                
                    <a href="specialists.php" class="secondary-btn">

                        <i class="fas fa-user-doctor btn-icon"></i>
                        View Specialists

                    </a>

                </div>

            </div>

            <div class="hero-image">

                <img class="hero-img" src="assets/images/hero-lab.jpg"
                    alt="Diagnostic Laboratory">

            </div>

        </div>

    </section>

    <!-- ==========================
         STATS SECTION
    =========================== -->

    <section class="stats-section">

        <div class="container">

            <div class="stats-grid">

                <div class="stat-card">

                    <i class="fas fa-user-doctor stat-icon"></i>

                    <div class="stat-content">

                        <h3 class="stat-value">20+</h3>
                        <p class="stat-label">Specialists</p>

                    </div>

                </div>

                <div class="stat-card">

                    <i class="fas fa-microscope stat-icon"></i>

                    <div class="stat-content">

                        <h3 class="stat-value">Modern</h3>
                        <p class="stat-label">Diagnostic Lab</p>

                    </div>

                </div>

                <div class="stat-card">

                    <i class="fas fa-location-arrow stat-icon"></i>

                    <div class="stat-content">

                        <h3 class="stat-value">Fast</h3>
                        <p class="stat-label">Report Delivery</p>

                    </div>

                </div>

                <div class="stat-card">

                    <i class="fas fa-clock stat-icon"></i>

                    <div class="stat-content">

                        <h3 class="stat-value">7 Days</h3>
                        <p class="stat-label">Service Available</p>

                    </div>

                </div>

            </div>

        </div>

    </section>

    <!-- ==========================
         SERVICES
    =========================== -->

    <section class="services">

        <div class="container">

            <div class="section-title">

                <span class="title-line"></span>

                <h2 class="section-heading">Our Services</h2>

                <span class="title-line"></span>

            </div>

            <div class="services-grid">

                <!-- Card -->

                <div class="service-card">

                    <div class="service-icon cyan">

                        <i class="fas fa-flask"></i>

                    </div>

                    <h3>Diagnostic Services</h3>

                    <ul>

                        <li class="service-icon-list"><i class="fas fa-vial service-icon-list-icon service-icon-orange"></i> Blood Tests</li>

                        <li class="service-icon-list"><i class="fas fa-flask service-icon-list-icon service-icon-orange"></i> Biochemistry</li>

                        <li class="service-icon-list"><i class="fas fa-microscope service-icon-list-icon service-icon-orange"></i> Pathology</li>

                        <li class="service-icon-list"><i class="fas fa-bacteria service-icon-list-icon service-icon-orange"></i> Microbiology</li>

                        <li class="service-icon-list"><i class="fas fa-x-ray service-icon-list-icon service-icon-orange"></i> Radiology</li>

                    </ul>

                </div>

                <!-- Card -->

                <div class="service-card">

                    <div class="service-icon green">

                        <i class="fas fa-stethoscope"></i>

                    </div>

                    <h3>Specialist Consultations</h3>

                    <ul>

                        <li class="service-icon-list"><i class="fas fa-stethoscope service-icon-list-icon service-icon-green"></i> General Medicine</li>

                        <li class="service-icon-list"><i class="fas fa-heart-pulse service-icon-list-icon service-icon-green"></i> Cardiology</li>

                        <li class="service-icon-list"><i class="fas fa-bone service-icon-list-icon service-icon-green"></i> Orthopaedics</li>

                        <li class="service-icon-list"><i class="fas fa-child-reaching service-icon-list-icon service-icon-green"></i> Paediatrics</li>

                        <li class="service-icon-list"><i class="fas fa-person-pregnant service-icon-list-icon service-icon-green"></i> Gynaecology</li>

                    </ul>

                </div>

                <!-- Card -->

                <div class="service-card">

                    <div class="service-icon orange">

                        <i class="fas fa-user-group"></i>

                    </div>

                    <h3>Patient Services</h3>

                    <ul>

                        <li class="service-icon-list"><i class="fas fa-calendar-plus service-icon-list-icon service-icon-orange"></i> Online Appointment</li>

                        <li class="service-icon-list"><i class="fas fa-file-arrow-down service-icon-list-icon service-icon-orange"></i> Report Downloads</li>

                        <li class="service-icon-list"><i class="fas fa-kit-medical service-icon-list-icon service-icon-orange"></i> Health Packages</li>

                        <li class="service-icon-list"><i class="fas fa-house-medical service-icon-list-icon service-icon-orange"></i> Home Sample Collection</li>

                    </ul>

                </div>

                <!-- Card -->

                <div class="service-card package-card">

                    <div class="service-icon purple">

                        <i class="fas fa-gift service-icon-inner"></i>

                    </div>

                    <h3 class="service-title">Health Packages</h3>

                    <p class="service-desc">

                        Choose from a wide range
                        of health check-up packages
                        for you and your family.

                    </p>

                    <a class="service-link" href="#">
                        View Packages
                    </a>

                </div>

            </div>

        </div>

    </section>

    <!-- ==========================
     OUR SPECIALISTS
=========================== -->

<section class="specialists">

    <div class="container">

        <div class="section-title">

            <span class="title-line"></span>
            <h2 class="section-heading">Our Specialists</h2>
            <span class="title-line"></span>

        </div>

        <?php
        $specialists = [];
        $result = $conn->query("SELECT * FROM doctors WHERE is_active = 1");
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $specialists[] = $row;
            }
        }
        ?>

        <div class="specialists-grid">

            <?php foreach($specialists as $doctor): ?>
            <!-- Doctor Card -->
            <div class="doctor-card">
                <div class="doctor-image">
                    <img class="doctor-img" src="<?php echo htmlspecialchars($doctor['image']); ?>" alt="<?php echo htmlspecialchars($doctor['name']); ?>">
                </div>
                <div class="doctor-content">
                    <h3 class="doctor-name"><?php echo htmlspecialchars($doctor['name']); ?></h3>
                    <span class="degree">
                        <?php echo htmlspecialchars($doctor['degree']); ?>
                    </span>
                    <p class="doctor-specialty"><?php echo htmlspecialchars($doctor['specialty']); ?></p>
                    <div class="doctor-timing">
                        <span class="timing-text">
                            <i class="fas fa-calendar timing-icon"></i>
                            <?php echo htmlspecialchars($doctor['days']); ?>
                        </span>
                        <span class="timing-text">
                            <i class="fas fa-clock timing-icon"></i>
                            <?php echo htmlspecialchars($doctor['time']); ?>
                        </span>
                    </div>
                    <a href="index.php?dept=<?php echo urlencode(trim($doctor['specialty'])); ?>&doc=<?php echo urlencode(trim($doctor['name'])); ?>#appointment-section" class="doctor-btn">
                        Book Appointment
                    </a>
                </div>
            </div>
            <?php endforeach; ?>

        </div>

        <div class="text-center">

            <a href="specialists.php" class="view-all-btn">
                View All Specialists
            </a>

        </div>

    </div>

</section>

<!-- ==========================
     APPOINTMENT SECTION
=========================== -->

<section class="appointment-section" id="appointment-section">

    <div class="container">

        <div class="appointment-box">

            <div class="appointment-left">

                <div class="appointment-icon">

                    <i class="fas fa-calendar-check appointment-icon-inner"></i>

                </div>

                <div class="appointment-content">

                    <h2 class="appointment-title">Need a Consultation?</h2>

                    <p class="appointment-desc">
                        Book your appointment in less than a minute.
                    </p>

                    <ul class="appointment-list">

                        <li class="appointment-list-item">
                            <i class="fas fa-bolt appointment-list-icon"></i> Easy & Quick Booking
                        </li>

                        <li class="appointment-list-item">
                            <i class="fas fa-user-md appointment-list-icon"></i> Choose Your Preferred Doctor
                        </li>

                        <li class="appointment-list-item">
                            <i class="fas fa-check-circle appointment-list-icon"></i> Instant Confirmation
                        </li>

                    </ul>

                </div>

            </div>

            <div class="appointment-right">

                <?php if (!empty($appointment_msg)): ?>
                    <div class="appointment-msg-success-box msg-success">
                        <i class="fas fa-check-circle"></i> <?= $appointment_msg ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($appointment_error)): ?>
                    <div class="appointment-msg-error-box msg-error">
                        <i class="fas fa-exclamation-circle"></i> <?= $appointment_error ?>
                    </div>
                <?php endif; ?>

                <form class="appointment-form" method="POST" action="index.php#appointment-section">

                    <div class="form-grid">

                        <input class="form-input" type="text" name="patient_name" placeholder="Your Name" required>

                        <input class="form-input" type="tel" name="mobile" placeholder="Mobile Number" required>

                        <?php if (!empty($form_settings['appointment_show_age'])): ?>
                            <input class="form-input" type="number" name="age" placeholder="Age">
                        <?php endif; ?>

                        <?php if (!empty($form_settings['appointment_show_gender'])): ?>
                            <select class="form-select" name="gender">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        <?php endif; ?>

                        <?php
                        $doctorsList = [];
                        $specialtiesList = [];
                        $docQuery = $conn->query("SELECT name, specialty FROM doctors WHERE name != '' AND is_active = 1 ORDER BY name ASC");
                        if ($docQuery && $docQuery->num_rows > 0) {
                            while ($doc = $docQuery->fetch_assoc()) {
                                $doctorsList[] = $doc;
                                $spec = trim($doc['specialty']);
                                if ($spec !== '' && !in_array($spec, $specialtiesList)) {
                                    $specialtiesList[] = $spec;
                                }
                            }
                        }
                        natcasesort($specialtiesList);
                        ?>

                        <?php if (!isset($form_settings['appointment_show_department']) || $form_settings['appointment_show_department'] == '1'): ?>
                        <select class="form-select" name="department" id="department-select" required>
                            <option value="" class="form-option">Select Department</option>
                            <?php
                            foreach ($specialtiesList as $dept) {
                                $selected = (isset($_GET['dept']) && strcasecmp(trim($_GET['dept']), $dept) == 0) ? 'selected' : '';
                                echo '<option class="form-option" value="' . htmlspecialchars($dept) . '" ' . $selected . '>' . htmlspecialchars($dept) . '</option>';
                            }
                            ?>
                        </select>
                        <?php else: ?>
                            <input type="hidden" name="department" value="Not Specified">
                        <?php endif; ?>

                        <?php if (!isset($form_settings['appointment_show_doctor']) || $form_settings['appointment_show_doctor'] == '1'): ?>
                        <select class="form-select" name="doctor" id="doctor-select" required>
                            <option value="" class="form-option">Select Doctor</option>
                            <?php
                            foreach ($doctorsList as $doc) {
                                $selected = (isset($_GET['doc']) && strcasecmp(trim($_GET['doc']), trim($doc['name'])) == 0) ? 'selected' : '';
                                echo '<option class="form-option doctor-option" value="' . htmlspecialchars(trim($doc['name'])) . '" data-department="' . htmlspecialchars(trim($doc['specialty'])) . '" ' . $selected . '>' . htmlspecialchars(trim($doc['name'])) . '</option>';
                            }
                            ?>
                        </select>
                        <?php else: ?>
                            <input type="hidden" name="doctor" value="Not Specified">
                        <?php endif; ?>

                        <input class="form-input" name="appointment_date" type="date" required>

                        <?php if (!empty($form_settings['appointment_show_address'])): ?>
                            <textarea name="address" class="form-input" placeholder="Address" rows="3" style="grid-column: 1 / -1;"></textarea>
                        <?php endif; ?>

                        <button class="form-submit" type="submit" name="book_appointment" style="grid-column: 1 / -1;">
                            Book Appointment Now

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</section>

<!-- ==========================
     DIAGNOSTIC FACILITIES
=========================== -->

<section class="facilities">

    <div class="container">

        <div class="section-title">

            <span class="title-line"></span>
            <h2 class="section-heading">Our Diagnostic Facilities</h2>
            <span class="title-line"></span>

        </div>

        <?php
        $facilities = [];
        $result = $conn->query("SELECT * FROM facilities ORDER BY id ASC");
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $facilities[] = $row;
            }
        }
        ?>

        <div class="facility-grid">

            <?php if (!empty($facilities)): ?>
                <?php foreach ($facilities as $facility): ?>
                <div class="facility-item">
                    <img class="facility-img" src="<?php echo htmlspecialchars($facility['image']); ?>" alt="<?php echo htmlspecialchars($facility['name']); ?>">
                    <h4 class="facility-name"><?php echo htmlspecialchars($facility['name']); ?></h4>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Default layout if no facilities exist -->
                <div class="facility-item"><img class="facility-img" src="assets/images/xray.jpg" alt="Digital X-Ray"><h4 class="facility-name">Digital X-Ray</h4></div>
                <div class="facility-item"><img class="facility-img" src="assets/images/ultrasound.jpg" alt="Ultrasound"><h4 class="facility-name">Ultrasound</h4></div>
                <div class="facility-item"><img class="facility-img" src="assets/images/ecg.jpg" alt="ECG"><h4 class="facility-name">ECG</h4></div>
                <div class="facility-item"><img class="facility-img" src="assets/images/pathology.jpg" alt="Pathology Lab"><h4 class="facility-name">Pathology Lab</h4></div>
                <div class="facility-item"><img class="facility-img" src="assets/images/auto.jpg" alt="Automated Analyzer"><h4 class="facility-name">Automated Analyzer</h4></div>
                <div class="facility-item"><img class="facility-img" src="assets/images/sample.jpg" alt="Sample Collection"><h4 class="facility-name">Sample Collection</h4></div>
            <?php endif; ?>

        </div>

    </div>

</section>


<!-- ==========================
     REPORT DOWNLOAD
=========================== -->

<section class="report-section">

    <div class="container">

        <div class="report-box">

            <div class="report-left">

                <div class="report-icon">

                    <i class="fas fa-file-medical report-icon-inner"></i>

                </div>

                <div class="report-content">

                    <h3 class="report-title">Patient Report Access</h3>

                    <p class="report-desc">
                        Enter your Mobile Number
                        to download your reports.
                    </p>

                </div>

            </div>

            <div class="report-right">

                <form class="report-form" method="POST" action="reports.php#report-results">

                    <input
                        class="report-input"
                        type="text"
                        name="mobile"
                        placeholder="Mobile Number" required>

                    <input
                        class="report-input"
                        type="text"
                        name="first_name"
                        placeholder="First Name (Optional)">

                    <button class="report-submit" type="submit" name="search_reports">

                        Download Report

                    </button>

                </form>

            </div>

        </div>

    </div>

</section>

<!-- ==========================
     WHY CHOOSE US
=========================== -->

<section class="why-us">

    <div class="container">

        <div class="section-title">

            <span class="title-line"></span>

            <h2 class="section-heading">
                Why Choose Max Diagnostic Center?
            </h2>

            <span class="title-line"></span>

        </div>

        <div class="why-grid">

            <div class="why-card">

                <div class="why-icon">

                    <i class="fas fa-user-doctor why-icon-inner"></i>

                </div>

                <h4 class="why-title">
                    Experienced Specialists
                </h4>

                <p class="why-desc">
                    Highly qualified and experienced
                    doctors across multiple specialties.
                </p>

            </div>

            <div class="why-card">

                <div class="why-icon">

                    <i class="fas fa-microscope why-icon-inner"></i>

                </div>

                <h4 class="why-title">
                    Accurate Diagnostics
                </h4>

                <p class="why-desc">
                    State-of-the-art equipment and
                    quality-controlled testing.
                </p>

            </div>

            <div class="why-card">

                <div class="why-icon">

                    <i class="fas fa-file-waveform why-icon-inner"></i>

                </div>

                <h4 class="why-title">
                    Fast Reports
                </h4>

                <p class="why-desc">
                    Quick turnaround times for
                    tests and reports.
                </p>

            </div>

            <div class="why-card">

                <div class="why-icon">

                    <i class="fas fa-heart-pulse why-icon-inner"></i>

                </div>

                <h4 class="why-title">
                    Patient-Centred Care
                </h4>

                <p class="why-desc">
                    Compassionate and personalised
                    healthcare services.
                </p>

            </div>

        </div>

    </div>

</section>

<!-- ==========================
     TESTIMONIALS
=========================== -->

<section class="testimonials">

    <div class="container">

        <div class="section-title">

            <span class="title-line"></span>

            <h2 class="section-heading">
                What Our Patients Say
            </h2>

            <span class="title-line"></span>

        </div>

        <div class="testimonial-grid">

            <div class="testimonial-card">

                <div class="stars">

                    ★★★★★

                </div>

                <p class="testimonial-text">

                    Excellent staff and quick
                    report delivery. The entire
                    process was smooth and
                    hassle-free.

                </p>

                <div class="testimonial-user">

                    <img class="testimonial-img"
                        src="assets/images/patient1.jpg"
                        alt="">

                    <div class="testimonial-author">

                        <h5 class="testimonial-name">
                            Bilal Ahmad
                        </h5>

                    </div>

                </div>

            </div>

            <div class="testimonial-card">

                <div class="stars">

                    ★★★★★

                </div>

                <p class="testimonial-text">

                    Very professional doctors and
                    clean environment. Highly
                    recommended.

                </p>

                <div class="testimonial-user">

                    <img class="testimonial-img"
                        src="assets/images/patient2.jpg"
                        alt="">

                    <div class="testimonial-author">

                        <h5 class="testimonial-name">
                            Sana Iqbal
                        </h5>

                    </div>

                </div>

            </div>

            <div class="testimonial-card">

                <div class="stars">

                    ★★★★★

                </div>

                <p class="testimonial-text">

                    Booking appointments online
                    is very easy and convenient.
                    Great service.

                </p>

                <div class="testimonial-user">

                    <img class="testimonial-img"
                        src="assets/images/patient3.jpg"
                        alt="">

                    <div class="testimonial-author">

                        <h5 class="testimonial-name">
                            Mudasir Lone
                        </h5>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

<!-- ==========================
     FOOTER
=========================== -->

<?php include 'includes/footer.php'; ?>
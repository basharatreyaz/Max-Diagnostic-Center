<?php
session_start();
require_once 'includes/db.php';
$appointment_msg = '';
$appointment_error = '';

// Fetch appointment form settings
$form_settings = getCachedSettings($conn);

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
    header("Location: appointments.php#appointment-section");
    exit;
}
?>
<?php include 'includes/header.php'; ?>

<link rel="stylesheet" href="assets/css/appointments.css">

<!-- ==========================
     PAGE HEADER
=========================== -->
<section class="page-header">
    <div class="container">
        <h1>Book an Appointment</h1>
        <p>Schedule Your Visit with Our Specialists</p>
    </div>
</section>

<!-- ==========================
     HOW IT WORKS
=========================== -->
<section class="how-it-works">
    <div class="container">
        <div class="how-it-works-header">
            <h2>How to Book</h2>
            <p>Booking an appointment at Max Diagnostic Center is simple and convenient. Follow these three easy steps.</p>
        </div>
        <div class="how-it-works-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <div class="step-icon blue">
                    <i class="fas fa-user-md"></i>
                </div>
                <h3>Select Specialist</h3>
                <p>Choose the department and the doctor you wish to consult from our expert panel.</p>
            </div>
            <div class="step-card">
                <div class="step-number">2</div>
                <div class="step-icon green">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3>Pick a Date</h3>
                <p>Select a suitable date for your visit. Our system will record your request instantly.</p>
            </div>
            <div class="step-card">
                <div class="step-number">3</div>
                <div class="step-icon yellow">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>Get Confirmation</h3>
                <p>Our team will contact you to confirm your appointment time and provide any necessary instructions.</p>
            </div>
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
                        <li class="appointment-list-item"><i class="fas fa-bolt"></i> Easy & Quick Booking</li>
                        <li class="appointment-list-item"><i class="fas fa-user-md"></i> Choose Your Preferred Doctor</li>
                        <li class="appointment-list-item"><i class="fas fa-check-circle"></i> Instant Confirmation</li>
                    </ul>

                </div>

            </div>

            <div class="appointment-right">

                <?php if (!empty($appointment_msg)): ?>
                    <div class="msg-success">
                        <i class="fas fa-check-circle"></i> <?= $appointment_msg ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($appointment_error)): ?>
                    <div class="msg-error">
                        <i class="fas fa-exclamation-circle"></i> <?= $appointment_error ?>
                    </div>
                <?php endif; ?>

                <form class="appointment-form" method="POST" action="appointments.php#appointment-section">

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

                        <select class="form-select" name="department" id="department-select" required>
                            <option value="" class="form-option">Select Department</option>
                            <?php
                            foreach ($specialtiesList as $dept) {
                                $selected = (isset($_GET['dept']) && strcasecmp(trim($_GET['dept']), $dept) == 0) ? 'selected' : '';
                                echo '<option class="form-option" value="' . htmlspecialchars($dept) . '" ' . $selected . '>' . htmlspecialchars($dept) . '</option>';
                            }
                            ?>
                        </select>

                        <select class="form-select" name="doctor" id="doctor-select" required>
                            <option value="" class="form-option">Select Doctor</option>
                            <?php
                            foreach ($doctorsList as $doc) {
                                $selected = (isset($_GET['doc']) && strcasecmp(trim($_GET['doc']), trim($doc['name'])) == 0) ? 'selected' : '';
                                echo '<option class="form-option doctor-option" value="' . htmlspecialchars(trim($doc['name'])) . '" data-department="' . htmlspecialchars(trim($doc['specialty'])) . '" ' . $selected . '>' . htmlspecialchars(trim($doc['name'])) . '</option>';
                            }
                            ?>
                        </select>

                        <input class="form-input" name="appointment_date" type="date" required>

                        <?php if (!empty($form_settings['appointment_show_address'])): ?>
                            <textarea name="address" class="form-input form-full-width" placeholder="Address" rows="3"></textarea>
                        <?php endif; ?>

                        <button class="form-submit form-full-width" type="submit" name="book_appointment">
                            Book Appointment Now
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</section>



<!-- ==========================
     FAQS
=========================== -->
<section class="faqs">
    <div class="container faqs-container">
        <div class="faqs-header">
            <h2>Frequently Asked Questions</h2>
        </div>
        
        <div class="faqs-list">
            <div class="faq-card">
                <h4><i class="fas fa-question-circle"></i> What should I bring to my appointment?</h4>
                <p>Please bring a valid photo ID, your previous medical records or prescriptions, and any referral forms if applicable.</p>
            </div>
            <div class="faq-card">
                <h4><i class="fas fa-question-circle"></i> How do I cancel or reschedule?</h4>
                <p>You can cancel or reschedule your appointment by calling our front desk at +91 7051674869 at least 24 hours in advance so we may offer the slot to another patient.</p>
            </div>
            <div class="faq-card">
                <h4><i class="fas fa-question-circle"></i> Is there a waiting time?</h4>
                <p>While we try our absolute best to stick to the schedule, medical emergencies can sometimes cause slight delays. We appreciate your patience and understanding.</p>
            </div>
        </div>
    </div>
</section>

<!-- Script logic is already globally provided in footer.php for filtering! -->

<?php include 'includes/footer.php'; ?>
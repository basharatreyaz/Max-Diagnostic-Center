<?php
session_start();
require_once 'includes/db.php';

$contact_msg = '';
if (isset($_SESSION['contact_msg'])) {
    $contact_msg = $_SESSION['contact_msg'];
    unset($_SESSION['contact_msg']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_contact'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    // Ensure inquiries table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'inquiries'");
    if ($tableCheck && $tableCheck->num_rows == 0) {
        $sql = "CREATE TABLE inquiries (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('Unread', 'Read') DEFAULT 'Unread',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->query($sql);
    }

    $stmt = $conn->prepare("INSERT INTO inquiries (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $subject, $message);
    if ($stmt->execute()) {
        $_SESSION['contact_msg'] = "Thank you for reaching out! Your message has been received and our team will get back to you shortly.";
        header("Location: contact.php");
        exit;
    }
}

include 'includes/header.php';
?>

<!-- ==========================
     PAGE HEADER
=========================== -->
<section class="page-header contact-page-header">
    <div class="container">
        <h1>Contact Us</h1>
        <p>We're Here to Help and Answer Any Questions</p>
    </div>
</section>

<!-- ==========================
     CONTACT SECTION
=========================== -->
<section class="contact-section contact-section-padding">
    <div class="container">
        <div class="contact-container">
            
            <!-- Contact Info -->
            <div class="contact-info-col">
                <h2 class="contact-info-title">Get in Touch</h2>
                <p class="contact-info-desc">
                    Have questions about our tests, health packages, or home collection services? Feel free to reach out to us using the contact details below or send us a message directly.
                </p>
                
                <div class="contact-details-list">
                    <div class="contact-detail-item">
                        <div class="contact-detail-icon contact-detail-icon-loc">
                            <i class="fas fa-location-dot"></i>
                        </div>
                        <div>
                            <h4 class="contact-detail-title">Our Location</h4>
                            <p class="contact-detail-text">Opposite JK Bank, Hyderbeigh<br>Jammu & Kashmir 193121</p>
                        </div>
                    </div>

                    <div class="contact-detail-item">
                        <div class="contact-detail-icon contact-detail-icon-phone">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div>
                            <h4 class="contact-detail-title">Phone Number</h4>
                            <p class="contact-detail-text">+91 7051674869</p>
                        </div>
                    </div>

                    <div class="contact-detail-item">
                        <div class="contact-detail-icon contact-detail-icon-email">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <h4 class="contact-detail-title">Email Address</h4>
                            <p class="contact-detail-text">info@maxdiagnostic.com</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="contact-form-col">
                <h3 class="contact-form-title">Send us a Message</h3>
                
                <?php if (!empty($contact_msg)): ?>
                    <div class="contact-msg-success">
                        <i class="fas fa-check-circle"></i> <?= $contact_msg ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="contact.php" class="contact-form">
                    <div class="contact-form-row">
                        <div class="contact-form-group">
                            <input type="text" name="name" placeholder="Your Name" required class="contact-input">
                        </div>
                        <div class="contact-form-group">
                            <input type="email" name="email" placeholder="Email Address" required class="contact-input">
                        </div>
                    </div>
                    
                    <input type="text" name="subject" placeholder="Subject" required class="contact-input">
                    
                    <textarea name="message" placeholder="Your Message" rows="5" required class="contact-textarea"></textarea>
                    
                    <link rel="stylesheet" href="assets/css/contact.css">

                    <button type="submit" name="submit_contact" class="primary-btn contact-submit-btn">
                        Send Message <i class="fas fa-paper-plane" style="margin-left: 8px;"></i>
                    </button>
                </form>
            </div>

        </div>
    </div>
</section>

<!-- ==========================
     MAP SECTION
=========================== -->
<section class="map-section">
    <!-- Embedded Google Map -->
    <iframe 
        src="https://maps.google.com/maps?q=Max%20health%20Care%20Diagnostic%20centre,%20Opposite%20jk%20Bank%20Hyderbeigh,%20Jammu%20and%20Kashmir%20193121&t=&z=16&ie=UTF8&iwloc=&output=embed" 
        class="map-iframe"
        allowfullscreen="" 
        loading="lazy" 
        referrerpolicy="no-referrer-when-downgrade">
    </iframe>
</section>

<?php include 'includes/footer.php'; ?>
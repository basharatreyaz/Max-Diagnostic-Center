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
<section class="page-header" style="background: var(--primary); padding: 60px 0; text-align: center; color: white;">
    <div class="container">
        <h1 style="margin: 0; font-size: 36px;">Contact Us</h1>
        <p style="margin-top: 10px; font-size: 18px; opacity: 0.9;">We're Here to Help and Answer Any Questions</p>
    </div>
</section>

<!-- ==========================
     CONTACT SECTION
=========================== -->
<section class="contact-section" style="padding: 80px 0;">
    <div class="container">
        <div style="display: flex; flex-wrap: wrap; gap: 40px;">
            
            <!-- Contact Info -->
            <div style="flex: 1 1 400px;">
                <h2 style="color: var(--secondary); margin-bottom: 20px; font-size: 32px;">Get in Touch</h2>
                <p style="color: var(--text-muted); font-size: 16px; line-height: 1.8; margin-bottom: 30px;">
                    Have questions about our tests, health packages, or home collection services? Feel free to reach out to us using the contact details below or send us a message directly.
                </p>
                
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <div style="display: flex; align-items: flex-start; gap: 15px; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--border);">
                        <div style="width: 50px; height: 50px; background: #e0f2fe; color: #0284c7; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                            <i class="fas fa-location-dot"></i>
                        </div>
                        <div>
                            <h4 style="margin: 0 0 5px; color: var(--secondary); font-size: 18px;">Our Location</h4>
                            <p style="margin: 0; color: var(--text-muted); font-size: 15px; line-height: 1.5;">Opposite JK Bank, Hyderbeigh<br>Jammu & Kashmir 193121</p>
                        </div>
                    </div>

                    <div style="display: flex; align-items: flex-start; gap: 15px; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--border);">
                        <div style="width: 50px; height: 50px; background: #ecfdf5; color: #059669; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div>
                            <h4 style="margin: 0 0 5px; color: var(--secondary); font-size: 18px;">Phone Number</h4>
                            <p style="margin: 0; color: var(--text-muted); font-size: 15px; line-height: 1.5;">+91 7051674869</p>
                        </div>
                    </div>

                    <div style="display: flex; align-items: flex-start; gap: 15px; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--border);">
                        <div style="width: 50px; height: 50px; background: #fef3c7; color: #d97706; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <h4 style="margin: 0 0 5px; color: var(--secondary); font-size: 18px;">Email Address</h4>
                            <p style="margin: 0; color: var(--text-muted); font-size: 15px; line-height: 1.5;">info@maxdiagnostic.com</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div style="flex: 1 1 500px; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid var(--border);">
                <h3 style="color: var(--secondary); margin-top: 0; margin-bottom: 25px; font-size: 24px;">Send us a Message</h3>
                
                <?php if (!empty($contact_msg)): ?>
                    <div style="background: #ecfdf5; color: #10b981; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; text-align: center; border: 1px solid #6ee7b7;">
                        <i class="fas fa-check-circle"></i> <?= $contact_msg ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="contact.php" style="display: flex; flex-direction: column; gap: 20px;">
                    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                        <div style="flex: 1 1 200px;">
                            <input type="text" name="name" placeholder="Your Name" required style="width: 100%; padding: 15px; border: 1px solid var(--border); border-radius: 8px; font-family: 'Outfit', sans-serif; outline: none; box-sizing: border-box; transition: 0.3s;">
                        </div>
                        <div style="flex: 1 1 200px;">
                            <input type="email" name="email" placeholder="Email Address" required style="width: 100%; padding: 15px; border: 1px solid var(--border); border-radius: 8px; font-family: 'Outfit', sans-serif; outline: none; box-sizing: border-box; transition: 0.3s;">
                        </div>
                    </div>
                    
                    <input type="text" name="subject" placeholder="Subject" required style="width: 100%; padding: 15px; border: 1px solid var(--border); border-radius: 8px; font-family: 'Outfit', sans-serif; outline: none; box-sizing: border-box; transition: 0.3s;">
                    
                    <textarea name="message" placeholder="Your Message" rows="5" required style="width: 100%; padding: 15px; border: 1px solid var(--border); border-radius: 8px; font-family: 'Outfit', sans-serif; outline: none; box-sizing: border-box; resize: vertical; transition: 0.3s;"></textarea>
                    
                    <link rel="stylesheet" href="assets/css/contact.css">

                    <button type="submit" name="submit_contact" class="primary-btn" style="border: none; font-family: 'Outfit', sans-serif; font-size: 16px; cursor: pointer; transition: 0.3s;">
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
<section class="map-section" style="line-height: 0;">
    <!-- Embedded Google Map -->
    <iframe 
        src="https://maps.google.com/maps?q=Max%20health%20Care%20Diagnostic%20centre,%20Opposite%20jk%20Bank%20Hyderbeigh,%20Jammu%20and%20Kashmir%20193121&t=&z=16&ie=UTF8&iwloc=&output=embed" 
        width="100%" 
        height="450" 
        style="border:0;" 
        allowfullscreen="" 
        loading="lazy" 
        referrerpolicy="no-referrer-when-downgrade">
    </iframe>
</section>

<?php include 'includes/footer.php'; ?>
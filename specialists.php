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
        <h1 style="margin: 0; font-size: 36px;">Our Specialists</h1>
        <p style="margin-top: 10px; font-size: 18px; opacity: 0.9;">Expert Care from Top Medical Professionals</p>
    </div>
</section>

<!-- ==========================
     ABOUT OUR TEAM
=========================== -->
<section class="about-team" style="padding: 80px 0 20px; text-align: center;">
    <div class="container" style="max-width: 800px; margin: auto;">
        <h2 style="color: var(--secondary); margin-bottom: 20px; font-size: 36px;">Dedicated to Your Health</h2>
        <p style="color: var(--text-muted); font-size: 18px; line-height: 1.8;">
            At Max Diagnostic Center, we pride ourselves on housing a distinguished panel of highly qualified and experienced medical professionals. Our specialists bring decades of combined expertise from premier medical institutions to provide you with accurate diagnoses, compassionate care, and advanced treatment plans.
        </p>
    </div>
</section>

<!-- ==========================
     OUR SPECIALISTS
=========================== -->
<section class="specialists" style="padding: 40px 0 80px;">
    <div class="container">
        <?php
        $specialists = [];
        $tableCheck = $conn->query("SHOW TABLES LIKE 'doctors'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            $colCheck = $conn->query("SHOW COLUMNS FROM doctors LIKE 'is_active'");
            $activeCond = ($colCheck && $colCheck->num_rows > 0) ? " WHERE is_active = 1" : "";
            $result = $conn->query("SELECT * FROM doctors$activeCond");
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $specialists[] = $row;
                }
            }
        }
        ?>

        <div class="filter-container" style="text-align: center; margin-bottom: 40px; display: flex; justify-content: center;">
            <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <label for="department-filter" style="font-weight: 600; color: var(--secondary); font-size: 16px;">Filter by Specialty:</label>
                <select id="department-filter" style="padding: 10px 15px; border: 1px solid var(--border); border-radius: 8px; font-family: 'Outfit', sans-serif; outline: none; font-size: 16px; min-width: 220px; color: var(--text-dark);">
                    <option value="">All Specialties</option>
                    <?php
                    if ($tableCheck && $tableCheck->num_rows > 0) {
                        $colCheck = $conn->query("SHOW COLUMNS FROM doctors LIKE 'is_active'");
                        $activeCond = ($colCheck && $colCheck->num_rows > 0) ? " AND is_active = 1" : "";
                        $deptQuery = $conn->query("SELECT DISTINCT specialty FROM doctors WHERE specialty != ''$activeCond ORDER BY specialty ASC");
                        if ($deptQuery && $deptQuery->num_rows > 0) {
                            while ($dept = $deptQuery->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars(trim($dept['specialty'])) . '">' . htmlspecialchars(trim($dept['specialty'])) . '</option>';
                            }
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
        
        <div class="specialists-grid">
            <?php foreach($specialists as $doctor): ?>
            <!-- Doctor Card -->
            <div class="doctor-card" data-department="<?php echo htmlspecialchars(trim($doctor['specialty'])); ?>">
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
        
        <?php if (empty($specialists)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <h3>No specialists found.</h3>
            </div>
        <?php else: ?>
            <div id="no-results-msg" style="display: none; text-align: center; padding: 40px; color: var(--text-muted);">
                <h3>No specialists found for this department.</h3>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ==========================
     CTA SECTION
=========================== -->
<section class="cta-section" style="background: var(--secondary); padding: 80px 0; text-align: center; color: white;">
    <div class="container">
        <h2 style="margin-bottom: 20px; font-size: 36px;">Ready to Consult with Our Experts?</h2>
        <p style="font-size: 18px; margin-bottom: 35px; max-width: 600px; margin-left: auto; margin-right: auto; opacity: 0.9; line-height: 1.6;">
            Book your appointment online today to secure your preferred time slot and skip the waiting room queues. Your health is our top priority.
        </p>
        <a href="index.php#appointment-section" class="primary-btn" style="display: inline-block; text-decoration: none; font-size: 18px; padding: 16px 35px;">
            <i class="fas fa-calendar-check" style="margin-right: 8px;"></i> Book Appointment Now
        </a>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const filterSelect = document.getElementById('department-filter');
    const doctorCards = document.querySelectorAll('.doctor-card');
    const noResultsMsg = document.getElementById('no-results-msg');

    if (filterSelect && doctorCards.length > 0) {
        filterSelect.addEventListener('change', function() {
            const selectedDept = this.value.trim().toLowerCase();
            let visibleCount = 0;

            doctorCards.forEach(card => {
                const cardDept = (card.getAttribute('data-department') || '').trim().toLowerCase();
                // Show card if no department is selected, or if the card's department matches
                if (selectedDept === '' || cardDept === selectedDept) {
                    card.style.display = ''; // Restore default display
                    visibleCount++;
                } else {
                    card.style.display = 'none'; // Hide the card
                }
            });

            // Display the "No results" message if no cards are visible
            if (noResultsMsg) {
                noResultsMsg.style.display = (visibleCount === 0) ? 'block' : 'none';
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
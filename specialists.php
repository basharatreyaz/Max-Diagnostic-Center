<?php
session_start();
require_once 'includes/db.php';
include 'includes/header.php';
?>

<!-- ==========================
     PAGE HEADER
=========================== -->
<section class="page-header specialists-page-header">
    <div class="container">
        <h1>Our Specialists</h1>
        <p>Expert Care from Top Medical Professionals</p>
    </div>
</section>

<!-- ==========================
     ABOUT OUR TEAM
=========================== -->
<section class="about-team">
    <div class="container about-team-container">
        <h2 class="about-team-title">Dedicated to Your Health</h2>
        <p class="about-team-text">
            At Max Diagnostic Center, we pride ourselves on housing a distinguished panel of highly qualified and experienced medical professionals. Our specialists bring decades of combined expertise from premier medical institutions to provide you with accurate diagnoses, compassionate care, and advanced treatment plans.
        </p>
    </div>
</section>

<!-- ==========================
     OUR SPECIALISTS
=========================== -->
<section class="specialists specialists-list-section">
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

        <div class="filter-container">
            <div class="filter-group">
                <label for="department-filter" class="filter-label">Filter by Specialty:</label>
                <select id="department-filter" class="filter-select">
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
            <div class="no-specialists-msg">
                <h3>No specialists found.</h3>
            </div>
        <?php else: ?>
            <div id="no-results-msg" class="no-results-msg">
                <h3>No specialists found for this department.</h3>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ==========================
     CTA SECTION
=========================== -->
<section class="cta-section">
    <div class="container">
        <h2 class="cta-title">Ready to Consult with Our Experts?</h2>
        <p class="cta-text">
            Book your appointment online today to secure your preferred time slot and skip the waiting room queues. Your health is our top priority.
        </p>
        <a href="index.php#appointment-section" class="primary-btn cta-btn">
            <i class="fas fa-calendar-check btn-icon-gap"></i> Book Appointment Now
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
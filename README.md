# Max Diagnostic Center

Max Diagnostic Center is a comprehensive clinic and diagnostic laboratory management system built with PHP and MySQL. It features a patient-facing frontend for booking appointments and downloading test reports, alongside a robust admin dashboard for staff to manage day-to-day operations.

## Features

### Patient Frontend
- **Responsive Design:** Mobile-friendly UI built with modern HTML5, CSS3, and Vanilla JavaScript.
- **Online Appointments:** Patients can seamlessly book appointments with specific doctors and departments.
- **Download Reports:** Securely download medical reports by entering a registered mobile number.
- **Specialists Directory:** Filterable list of available doctors and their schedules.
- **Services & Facilities:** Detailed overview of clinical and diagnostic services offered.
- **Contact Form:** Send direct inquiries to the clinic.

### Admin Backend
- **Dashboard Overview:** Quick statistics on doctors, total reports, and appointment statuses.
- **Manage Appointments:** 
  - View, filter, and update appointment statuses (Pending, Confirmed, Completed, Cancelled).
  - Export appointment lists to CSV or PDF.
  - Generate and print token tickets (includes a dynamic QR code for patients to download reports).
- **Manage Reports:** Upload single or multiple file reports (PDF, JPG, PNG, DOC) linked to a patient's mobile number and name.
- **Manage Doctors:** Add and edit doctor profiles, schedules, specialties, and visibility statuses.
- **Manage Services & Facilities:** Control the content displayed on the frontend pages.
- **Manage Messages:** View and update the status of inquiries received from the contact form.
- **Global Settings:** 
  - Update clinic branding (Logo, Favicon).
  - Manage SEO meta tags per page.
  - Toggle appointment form fields (Age, Gender, Address).
  - Inject custom header/footer scripts (e.g., Google Analytics).
- **Role-Based Access Control:** Superadmins can create sub-admin accounts with specific module permissions.
- **Security:** Built-in CSRF protection, secure file upload validation, and password hashing.
- **Dark Mode:** Toggleable dark mode interface for the admin panel.

## Technologies Used

- **Backend:** PHP (7.4+)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Fonts & Icons:** Google Fonts (Outfit), Font Awesome (6.7.2)

## Installation Instructions

1. **Clone the Repository:**
   Place the project folder (e.g., `Max`) into your local server's web root directory (like `htdocs` for XAMPP or `www` for WAMP).

2. **Run the Installer:**
   Open your web browser and navigate to the installer script:
   ```text
   http://localhost/Max/install.php
   ```

3. **Database Configuration:**
   - Enter your database host, username, password, and desired database name.
   - The installer will automatically create the database, tables, and seed the initial data.
   - It will also generate the `includes/db.php` configuration file.

4. **Secure the Installation:**
   **CRITICAL:** Once the installation is successful, delete the `install.php` and `clear_data.php` files from your server to prevent unauthorized access or data loss.

5. **Login to Admin Panel:**
   Navigate to:
   ```text
   http://localhost/Max/manage/
   ```
   - **Default Username:** `admin`
   - **Default Password:** `admin123`
   
   *(Please change the password immediately after your first login via the Manage Users tab).*

## Folder Structure

- `/assets/` - Contains all CSS, images, and uploaded files (reports, doctor images).
- `/includes/` - Shared components like `header.php`, `footer.php`, and `db.php`.
- `/manage/` - The entire Admin Panel application.
- `/tests/` - PHPUnit test files for automated testing.
- Root files - Patient-facing frontend pages (`index.php`, `about.php`, etc.).

## License

Designed and developed for Max Diagnostic Center. All Rights Reserved.
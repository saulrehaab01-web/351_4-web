# Grand Palace Hotel Website

A PHP-based hotel reservation website implementing secure authentication, room search, booking, payment, and role-based dashboards.

## Setup

1. Import the database schema:
   - Use `hotel_reservation_database.sql` to create the database and tables.
   - Example MySQL command:
     ```bash
     mysql -u root -p < hotel_reservation_database.sql
     ```

2. Configure PHP database credentials in `config.php`:
   - `DB_HOST`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASS`

3. Install Composer dependencies (optional, for PHPMailer):
   ```bash
   composer install
   ```

4. Configure Google OAuth if desired by setting `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI` in `config.php`.

5. Open the site in a browser from your PHP web server root.

## Pages Included

- `index.php` — Homepage
- `about.php` — About page
- `rooms.php` — Room listing
- `room_details.php` — Room details
- `contact.php` — Contact page
- `login.php` / `register.php` — Authentication
- `forgot_password.php`, `reset_password.php` — Password recovery
- `otp_verify.php` — OTP verification
- `search.php` — Room search
- `booking.php`, `confirmation.php`, `payment.php` — Booking flow
- `dashboard.php` — Guest dashboard
- `profile.php` — Profile management
- `admin.php`, `receptionist.php`, `room_management.php`, `user_management.php`, `reports.php` — Role-based tools
- `access_denied.php`, `session_timeout.php`, `404.php` — System pages

## Notes

- The site uses dark blue and gold styling.
- Authentication uses password hashing, session tracking, OTP verification, and login attempt logging.
- The design leverages the supplied images in the `images/` folder.

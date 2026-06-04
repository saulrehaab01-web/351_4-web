<?php
// Global configuration for Grand Palace Hotel website

ob_start();
session_start();

// ── Security Headers ─────────────────────────────────────
header("X-Frame-Options: DENY");                           // prevent clickjacking
header("X-Content-Type-Options: nosniff");                 // prevent MIME sniffing
header("X-XSS-Protection: 1; mode=block");                 // legacy XSS filter
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'hotel_reservation_system');
define('DB_USER', 'root');
define('DB_PASS', '');

define('SITE_NAME', 'Grand Palace Hotel');
define('SITE_EMAIL', 'info@grandpalacehotel.com');
define('SITE_PHONE', '+1 800 123 4567');
define('SITE_ADDRESS', '123 Palace Avenue, Luxury City');

define('MAILER_SMTP_HOST', 'smtp.gmail.com');
define('MAILER_SMTP_PORT', 587);
define('MAILER_SMTP_USER', 'your-smtp-username@example.com');
define('MAILER_SMTP_PASS', 'your-smtp-password');
define('MAILER_SMTP_SECURE', 'tls');
define('MAILER_FROM_NAME', SITE_NAME);

define('GOOGLE_CLIENT_ID', '');
define('GOOGLE_CLIENT_SECRET', '');
define('GOOGLE_REDIRECT_URI', 'http://localhost/351/google_oauth.php');

define('OTP_EXPIRY_SECONDS', 300);
define('SESSION_TIMEOUT_SECONDS', 1800);

// ── reCAPTCHA ─────────────────────────────────────────────
define('RECAPTCHA_SITE_KEY',   'YOUR_SITE_KEY_HERE');
define('RECAPTCHA_SECRET_KEY', 'YOUR_SECRET_KEY_HERE');
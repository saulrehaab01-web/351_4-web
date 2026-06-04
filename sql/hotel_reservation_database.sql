-- ============================================================================
-- Hotel Reservation System Database Schema
-- Group 6 - IS351 Project
-- Database: hotel_reservation_system
-- ============================================================================

-- Create Database
CREATE DATABASE IF NOT EXISTS hotel_reservation_system;
USE hotel_reservation_system;

-- ============================================================================
-- 1. USERS TABLE - Core authentication and user management
-- ============================================================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role_id INT NOT NULL,
    google_id VARCHAR(255) DEFAULT NULL,
    google_profile_picture VARCHAR(500) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(64) DEFAULT NULL,
    password_reset_token VARCHAR(64) DEFAULT NULL,
    password_reset_expires DATETIME DEFAULT NULL,
    failed_login_attempts INT DEFAULT 0,
    account_locked_until DATETIME DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_role (role_id),
    INDEX idx_google_id (google_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. ROLES TABLE - RBAC (Role-Based Access Control)
-- ============================================================================
CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    role_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default roles for Hotel Reservation System
INSERT INTO roles (role_id, role_name, role_description) VALUES
(1, 'Admin', 'Full system access - Manage hotel system, users, rooms, reservations'),
(2, 'Receptionist', 'Manage reservations, check-ins, check-outs, room assignments'),
(3, 'Guest', 'Book rooms, view reservations, manage profile');

-- ============================================================================
-- 3. PERMISSIONS TABLE - Granular access control
-- ============================================================================
CREATE TABLE permissions (
    permission_id INT AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(100) NOT NULL UNIQUE,
    permission_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert permissions following Least Privilege principle
INSERT INTO permissions (permission_name, permission_description) VALUES
-- User management
('manage_users', 'Create, update, delete users'),
('view_users', 'View user information'),
('manage_roles', 'Assign and modify user roles'),

-- Room management
('manage_rooms', 'Create, update, delete rooms and room types'),
('view_rooms', 'View room information and availability'),

-- Reservation management
('create_reservation', 'Create new reservations'),
('view_own_reservations', 'View own reservations'),
('view_all_reservations', 'View all reservations'),
('modify_reservation', 'Modify reservation details'),
('cancel_reservation', 'Cancel reservations'),
('approve_reservation', 'Approve pending reservations'),

-- Check-in/Check-out
('checkin_guest', 'Check-in guests'),
('checkout_guest', 'Check-out guests'),

-- Payment management
('process_payment', 'Process and record payments'),
('view_payments', 'View payment records'),
('manage_payments', 'Modify payment records'),

-- Reports and analytics
('view_reports', 'View system reports'),
('generate_reports', 'Generate custom reports'),

-- System settings
('manage_settings', 'Modify system settings'),
('view_audit_logs', 'View system audit logs');

-- ============================================================================
-- 4. ROLE_PERMISSIONS TABLE - Many-to-many relationship
-- ============================================================================
CREATE TABLE role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Assign permissions to roles (Least Privilege implementation)
-- Admin: All permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, permission_id FROM permissions;

-- Receptionist: Limited operational permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, permission_id FROM permissions 
WHERE permission_name IN (
    'view_users', 'view_rooms', 'create_reservation', 'view_all_reservations',
    'modify_reservation', 'cancel_reservation', 'approve_reservation',
    'checkin_guest', 'checkout_guest', 'process_payment', 'view_payments',
    'view_reports'
);

-- Guest: Minimal permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, permission_id FROM permissions 
WHERE permission_name IN (
    'view_rooms', 'create_reservation', 'view_own_reservations', 'cancel_reservation'
);

-- ============================================================================
-- 5. TWO-FACTOR AUTHENTICATION TABLE
-- ============================================================================
CREATE TABLE two_factor_auth (
    tfa_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    otp_expiry DATETIME NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_otp (user_id, otp_code),
    INDEX idx_expiry (otp_expiry)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. SESSIONS TABLE - Secure session management
-- ============================================================================
CREATE TABLE sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7. ROOM TYPES TABLE
-- ============================================================================
CREATE TABLE room_types (
    room_type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    base_price DECIMAL(10, 2) NOT NULL,
    max_occupancy INT NOT NULL,
    amenities TEXT,
    image_url VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample room types
INSERT INTO room_types (type_name, description, base_price, max_occupancy, amenities) VALUES
('Standard Single', 'Comfortable single room with basic amenities', 89.99, 1, 'TV, Wi-Fi, Air Conditioning, Mini Fridge'),
('Standard Double', 'Spacious double room with two beds', 129.99, 2, 'TV, Wi-Fi, Air Conditioning, Mini Fridge, Coffee Maker'),
('Deluxe Suite', 'Luxurious suite with separate living area', 249.99, 3, 'TV, Wi-Fi, Air Conditioning, Mini Bar, Kitchenette, Balcony'),
('Executive Suite', 'Premium suite with ocean view and premium amenities', 399.99, 4, 'Smart TV, High-Speed Wi-Fi, Air Conditioning, Full Bar, Kitchen, Jacuzzi, Ocean View'),
('Family Room', 'Large room suitable for families', 179.99, 4, 'TV, Wi-Fi, Air Conditioning, Mini Fridge, Extra Beds');

-- ============================================================================
-- 8. ROOMS TABLE
-- ============================================================================
CREATE TABLE rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL UNIQUE,
    room_type_id INT NOT NULL,
    floor_number INT,
    status ENUM('Available', 'Occupied', 'Maintenance', 'Reserved') DEFAULT 'Available',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_type_id) REFERENCES room_types(room_type_id),
    INDEX idx_status (status),
    INDEX idx_room_number (room_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample rooms
INSERT INTO rooms (room_number, room_type_id, floor_number, status) VALUES
('101', 1, 1, 'Available'),
('102', 1, 1, 'Available'),
('103', 2, 1, 'Available'),
('104', 2, 1, 'Available'),
('201', 2, 2, 'Available'),
('202', 3, 2, 'Available'),
('203', 3, 2, 'Available'),
('301', 4, 3, 'Available'),
('302', 4, 3, 'Available'),
('303', 5, 3, 'Available');

-- ============================================================================
-- 9. RESERVATIONS TABLE
-- ============================================================================
CREATE TABLE reservations (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_number VARCHAR(20) NOT NULL UNIQUE,
    guest_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    number_of_guests INT NOT NULL,
    special_requests TEXT,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('Pending', 'Confirmed', 'CheckedIn', 'CheckedOut', 'Cancelled', 'NoShow') DEFAULT 'Pending',
    created_by INT NOT NULL,
    approved_by INT DEFAULT NULL,
    cancelled_by INT DEFAULT NULL,
    cancellation_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guest_id) REFERENCES users(user_id),
    FOREIGN KEY (room_id) REFERENCES rooms(room_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id),
    FOREIGN KEY (cancelled_by) REFERENCES users(user_id),
    INDEX idx_guest (guest_id),
    INDEX idx_room (room_id),
    INDEX idx_dates (check_in_date, check_out_date),
    INDEX idx_status (status),
    INDEX idx_reservation_number (reservation_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 10. PAYMENTS TABLE
-- ============================================================================
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    payment_reference VARCHAR(50) NOT NULL UNIQUE,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('Cash', 'CreditCard', 'DebitCard', 'BankTransfer', 'OnlinePayment') NOT NULL,
    payment_status ENUM('Pending', 'Completed', 'Failed', 'Refunded') DEFAULT 'Pending',
    transaction_id VARCHAR(100),
    payment_date DATETIME,
    processed_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id),
    FOREIGN KEY (processed_by) REFERENCES users(user_id),
    INDEX idx_reservation (reservation_id),
    INDEX idx_reference (payment_reference),
    INDEX idx_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 11. AUDIT LOGS TABLE - Security and compliance tracking
-- ============================================================================
CREATE TABLE audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 12. LOGIN ATTEMPTS TABLE - Security monitoring
-- ============================================================================
CREATE TABLE login_attempts (
    attempt_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    email VARCHAR(100),
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    attempt_result ENUM('Success', 'Failed', 'Blocked') NOT NULL,
    failure_reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip (ip_address),
    INDEX idx_username (username),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 13. CSRF TOKENS TABLE - CSRF protection
-- ============================================================================
CREATE TABLE csrf_tokens (
    token_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    token VARCHAR(64) NOT NULL UNIQUE,
    form_name VARCHAR(50) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 14. GUEST DOCUMENTS TABLE - Store encrypted guest documents
-- ============================================================================
CREATE TABLE guest_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    guest_id INT NOT NULL,
    document_type ENUM('ID', 'Passport', 'DrivingLicense', 'Other') NOT NULL,
    document_number_encrypted VARCHAR(255),
    file_path_encrypted VARCHAR(500),
    uploaded_by INT NOT NULL,
    verified BOOLEAN DEFAULT FALSE,
    verified_by INT,
    verified_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guest_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id),
    FOREIGN KEY (verified_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 15. SYSTEM SETTINGS TABLE
-- ============================================================================
CREATE TABLE system_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_encrypted BOOLEAN DEFAULT FALSE,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('hotel_name', 'Grand Paradise Hotel', 'string', 'Hotel name displayed on website'),
('hotel_email', 'info@grandparadisehotel.com', 'string', 'Hotel contact email'),
('hotel_phone', '+679-123-4567', 'string', 'Hotel contact phone'),
('check_in_time', '14:00', 'string', 'Default check-in time'),
('check_out_time', '11:00', 'string', 'Default check-out time'),
('session_timeout', '1800', 'integer', 'Session timeout in seconds (30 minutes)'),
('max_login_attempts', '5', 'integer', 'Maximum failed login attempts before lockout'),
('lockout_duration', '900', 'integer', 'Account lockout duration in seconds (15 minutes)'),
('otp_expiry', '300', 'integer', 'OTP expiry time in seconds (5 minutes)'),
('enable_2fa', 'true', 'boolean', 'Enable two-factor authentication'),
('enable_email_verification', 'true', 'boolean', 'Require email verification for new accounts'),
('tax_rate', '15', 'integer', 'Tax rate percentage'),
('cancellation_hours', '24', 'integer', 'Minimum hours before check-in to allow cancellation');

-- ============================================================================
-- 16. NOTIFICATIONS TABLE
-- ============================================================================
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('Info', 'Warning', 'Success', 'Error') DEFAULT 'Info',
    is_read BOOLEAN DEFAULT FALSE,
    link_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 17. REVIEWS TABLE - Guest feedback
-- ============================================================================
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    guest_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_title VARCHAR(200),
    review_text TEXT,
    is_approved BOOLEAN DEFAULT FALSE,
    approved_by INT,
    approved_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id),
    FOREIGN KEY (guest_id) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id),
    INDEX idx_reservation (reservation_id),
    INDEX idx_guest (guest_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- VIEWS FOR COMMON QUERIES
-- ============================================================================

-- View: Active Reservations
CREATE VIEW active_reservations AS
SELECT 
    r.reservation_id,
    r.reservation_number,
    u.full_name AS guest_name,
    u.email AS guest_email,
    u.phone AS guest_phone,
    rm.room_number,
    rt.type_name AS room_type,
    r.check_in_date,
    r.check_out_date,
    r.number_of_guests,
    r.total_amount,
    r.status,
    r.created_at
FROM reservations r
JOIN users u ON r.guest_id = u.user_id
JOIN rooms rm ON r.room_id = rm.room_id
JOIN room_types rt ON rm.room_type_id = rt.room_type_id
WHERE r.status IN ('Pending', 'Confirmed', 'CheckedIn');

-- View: Available Rooms
CREATE VIEW available_rooms AS
SELECT 
    rm.room_id,
    rm.room_number,
    rm.floor_number,
    rt.type_name,
    rt.description,
    rt.base_price,
    rt.max_occupancy,
    rt.amenities
FROM rooms rm
JOIN room_types rt ON rm.room_type_id = rt.room_type_id
WHERE rm.status = 'Available';

-- View: User Permissions
CREATE VIEW user_permissions AS
SELECT 
    u.user_id,
    u.username,
    u.email,
    r.role_name,
    p.permission_name,
    p.permission_description
FROM users u
JOIN roles r ON u.role_id = r.role_id
JOIN role_permissions rp ON r.role_id = rp.role_id
JOIN permissions p ON rp.permission_id = p.permission_id
WHERE u.is_active = TRUE;

-- ============================================================================
-- STORED PROCEDURES
-- ============================================================================

-- Procedure: Check room availability
DELIMITER //
CREATE PROCEDURE check_room_availability(
    IN p_check_in DATE,
    IN p_check_out DATE,
    IN p_room_type_id INT
)
BEGIN
    SELECT 
        rm.room_id,
        rm.room_number,
        rt.type_name,
        rt.base_price
    FROM rooms rm
    JOIN room_types rt ON rm.room_type_id = rt.room_type_id
    WHERE rm.room_type_id = p_room_type_id
    AND rm.status = 'Available'
    AND rm.room_id NOT IN (
        SELECT room_id 
        FROM reservations 
        WHERE status IN ('Confirmed', 'CheckedIn')
        AND (
            (check_in_date <= p_check_in AND check_out_date > p_check_in)
            OR (check_in_date < p_check_out AND check_out_date >= p_check_out)
            OR (check_in_date >= p_check_in AND check_out_date <= p_check_out)
        )
    );
END //
DELIMITER ;

-- Procedure: Generate reservation number
DELIMITER //
CREATE PROCEDURE generate_reservation_number(OUT reservation_num VARCHAR(20))
BEGIN
    DECLARE next_id INT;
    SELECT COALESCE(MAX(reservation_id), 0) + 1 INTO next_id FROM reservations;
    SET reservation_num = CONCAT('RES', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(next_id, 5, '0'));
END //
DELIMITER ;

-- ============================================================================
-- TRIGGERS FOR AUDIT LOGGING
-- ============================================================================

-- Trigger: Log user updates
DELIMITER //
CREATE TRIGGER trg_users_update
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, old_value, new_value)
    VALUES (
        NEW.user_id,
        'UPDATE',
        'users',
        NEW.user_id,
        CONCAT('email:', OLD.email, ',role:', OLD.role_id, ',active:', OLD.is_active),
        CONCAT('email:', NEW.email, ',role:', NEW.role_id, ',active:', NEW.is_active)
    );
END //
DELIMITER ;

-- Trigger: Log reservation changes
DELIMITER //
CREATE TRIGGER trg_reservations_update
AFTER UPDATE ON reservations
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, old_value, new_value)
    VALUES (
        NEW.guest_id,
        'UPDATE_RESERVATION',
        'reservations',
        NEW.reservation_id,
        CONCAT('status:', OLD.status, ',room:', OLD.room_id),
        CONCAT('status:', NEW.status, ',room:', NEW.room_id)
    );
END //
DELIMITER ;

-- ============================================================================
-- SAMPLE DATA FOR TESTING
-- ============================================================================

-- Insert sample admin user (password: Admin@123)
INSERT INTO users (username, email, password_hash, full_name, phone, role_id, is_active, email_verified) VALUES
('admin', 'admin@grandparadise.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', '+679-999-0001', 1, TRUE, TRUE);

-- Insert sample receptionist (password: Recep@123)
INSERT INTO users (username, email, password_hash, full_name, phone, role_id, is_active, email_verified) VALUES
('receptionist1', 'reception@grandparadise.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Receptionist', '+679-999-0002', 2, TRUE, TRUE);

-- Insert sample guest (password: Guest@123)
INSERT INTO users (username, email, password_hash, full_name, phone, role_id, is_active, email_verified) VALUES
('guest1', 'guest1@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Guest', '+679-999-0003', 3, TRUE, TRUE);

-- ============================================================================
-- INDEXES FOR PERFORMANCE OPTIMIZATION
-- ============================================================================

-- Additional composite indexes for common queries
CREATE INDEX idx_reservations_guest_status ON reservations(guest_id, status);
CREATE INDEX idx_reservations_dates_status ON reservations(check_in_date, check_out_date, status);
CREATE INDEX idx_rooms_type_status ON rooms(room_type_id, status);
CREATE INDEX idx_payments_reservation_status ON payments(reservation_id, payment_status);

-- ============================================================================
-- SECURITY NOTES
-- ============================================================================
/*
1. All passwords must be hashed using password_hash() in PHP
2. Implement prepared statements for all database queries
3. Store sensitive data (documents, etc.) encrypted
4. Use HTTPS for all communications
5. Implement CSRF tokens for all forms
6. Set secure session cookies (HttpOnly, Secure, SameSite)
7. Implement rate limiting for login attempts
8. Regular backup of audit_logs table
9. Use environment variables for database credentials
10. Implement input validation and sanitization in PHP

RBAC Implementation:
- Admin: Full access to all features
- Receptionist: Limited to operational tasks (reservations, check-in/out, payments)
- Guest: Can only view and book rooms, manage own reservations

Least Privilege:
- Each role has only the minimum permissions needed
- Guests cannot access other guests' data
- Receptionists cannot modify system settings or user roles
- All actions are logged in audit_logs table
*/

-- ============================================================================
-- END OF DATABASE SCHEMA
-- ============================================================================

-- ============================================
-- RESORT COTTAGE RESERVATION SYSTEM
-- MySQL Database Schema
-- ============================================

-- Drop database if exists (use with caution!)
-- DROP DATABASE IF EXISTS resort_reservation_db;

-- Create database
CREATE DATABASE IF NOT EXISTS resort_reservation_db;
USE resort_reservation_db;

-- ============================================
-- TABLE 1: users
-- Stores all user accounts (customers and admins)
-- ============================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    is_email_verified BOOLEAN DEFAULT FALSE,
    otp_code VARCHAR(6) NULL,
    otp_expires_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 2: user_profiles
-- Personal information and verification details
-- ============================================
CREATE TABLE user_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    full_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20) NULL,
    address TEXT NULL,
    id_image_path VARCHAR(255) NULL,
    verification_status ENUM('unverified', 'pending_verification', 'verified', 'rejected') 
        DEFAULT 'unverified',
    admin_remarks TEXT NULL,
    verified_by INT NULL,
    verified_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_verification_status (verification_status),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 3: ocr_verification_logs
-- Records from automated ID scanning
-- ============================================
CREATE TABLE ocr_verification_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    profile_id INT NOT NULL,
    extracted_text TEXT NULL,
    normalized_text TEXT NULL,
    confidence_score DECIMAL(5,2) NULL COMMENT 'Accuracy percentage (0-100)',
    processed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profile_id) REFERENCES user_profiles(profile_id) ON DELETE CASCADE,
    INDEX idx_profile_id (profile_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 4: cottages
-- Resort properties available for booking
-- ============================================
CREATE TABLE cottages (
    cottage_id INT AUTO_INCREMENT PRIMARY KEY,
    cottage_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    capacity INT NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 5: cottage_images
-- Photos of each cottage
-- ============================================
CREATE TABLE cottage_images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    cottage_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cottage_id) REFERENCES cottages(cottage_id) ON DELETE CASCADE,
    INDEX idx_cottage_id (cottage_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 6: reservations
-- Booking requests and their status
-- ============================================
CREATE TABLE reservations (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    cottage_id INT NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    total_nights INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending_admin_review', 'approved', 'rejected') 
        DEFAULT 'pending_admin_review',
    admin_remarks TEXT NULL,
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (cottage_id) REFERENCES cottages(cottage_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_cottage_id (cottage_id),
    INDEX idx_status (status),
    INDEX idx_dates (check_in_date, check_out_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 7: payment_proofs
-- Evidence of payment for reservations
-- ============================================
CREATE TABLE payment_proofs (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL UNIQUE,
    receipt_image_path VARCHAR(255) NOT NULL,
    reference_number VARCHAR(100) NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE,
    INDEX idx_reservation_id (reservation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 8: audit_logs
-- Complete history of system actions
-- ============================================
CREATE TABLE audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action_type VARCHAR(100) NOT NULL,
    table_affected VARCHAR(50) NULL,
    record_id INT NULL,
    old_value TEXT NULL COMMENT 'JSON format',
    new_value TEXT NULL COMMENT 'JSON format',
    ip_address VARCHAR(45) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 9: login_attempts
-- Tracks failed login attempts for security
-- ============================================
CREATE TABLE login_attempts (
    attempt_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NULL,
    is_successful BOOLEAN DEFAULT FALSE,
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_attempted_at (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SAMPLE DATA (Optional - for testing)
-- ============================================

-- Insert sample admin account
-- Password: admin123 (this is a bcrypt hash example - replace with actual hash)
INSERT INTO users (email, password_hash, role, is_email_verified) VALUES
('admin@resort.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJK', 'admin', TRUE);

-- Insert sample cottages
INSERT INTO cottages (cottage_name, description, capacity, price_per_night, is_active) VALUES
('Sunset Villa', 'Beautiful beachfront cottage with stunning sunset views', 6, 3500.00, TRUE),
('Mountain View Cabin', 'Cozy cabin surrounded by pine trees', 4, 2500.00, TRUE),
('Lakeside Retreat', 'Peaceful cottage by the lake with fishing access', 8, 4000.00, TRUE);

-- ============================================
-- USEFUL VIEWS (Optional)
-- ============================================

-- View for pending verifications
CREATE VIEW pending_verifications AS
SELECT 
    up.profile_id,
    u.email,
    up.full_name,
    up.phone_number,
    up.id_image_path,
    up.created_at,
    ocr.confidence_score
FROM user_profiles up
JOIN users u ON up.user_id = u.user_id
LEFT JOIN ocr_verification_logs ocr ON up.profile_id = ocr.profile_id
WHERE up.verification_status = 'pending_verification'
ORDER BY up.created_at ASC;

-- View for pending reservations
CREATE VIEW pending_reservations AS
SELECT 
    r.reservation_id,
    u.email,
    up.full_name,
    c.cottage_name,
    r.check_in_date,
    r.check_out_date,
    r.total_nights,
    r.total_price,
    pp.receipt_image_path,
    pp.reference_number,
    r.created_at
FROM reservations r
JOIN users u ON r.user_id = u.user_id
JOIN user_profiles up ON u.user_id = up.user_id
JOIN cottages c ON r.cottage_id = c.cottage_id
LEFT JOIN payment_proofs pp ON r.reservation_id = pp.reservation_id
WHERE r.status = 'pending_admin_review'
ORDER BY r.created_at ASC;

-- View for cottage availability dashboard
CREATE VIEW cottage_availability AS
SELECT 
    c.cottage_id,
    c.cottage_name,
    c.capacity,
    c.price_per_night,
    COUNT(r.reservation_id) as total_reservations,
    SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_reservations
FROM cottages c
LEFT JOIN reservations r ON c.cottage_id = r.cottage_id
WHERE c.is_active = TRUE
GROUP BY c.cottage_id, c.cottage_name, c.capacity, c.price_per_night;

-- ============================================
-- STORED PROCEDURES (Optional - Advanced)
-- ============================================

-- Procedure to check date availability
DELIMITER //
CREATE PROCEDURE check_date_availability(
    IN p_cottage_id INT,
    IN p_check_in DATE,
    IN p_check_out DATE
)
BEGIN
    SELECT COUNT(*) as conflict_count
    FROM reservations
    WHERE cottage_id = p_cottage_id
    AND status = 'approved'
    AND (
        (check_in_date <= p_check_in AND check_out_date > p_check_in)
        OR
        (check_in_date < p_check_out AND check_out_date >= p_check_out)
        OR
        (check_in_date >= p_check_in AND check_out_date <= p_check_out)
    );
END //
DELIMITER ;

ALTER TABLE user_profiles 
ADD COLUMN id_number VARCHAR(50) NULL AFTER address;

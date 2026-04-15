-- Tour booking contacts: per-booking customer contact info
-- Module-isolated table (does NOT touch core company table)
CREATE TABLE IF NOT EXISTS tour_booking_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    contact_name VARCHAR(255) DEFAULT '',
    mobile VARCHAR(50) DEFAULT '',
    email VARCHAR(255) DEFAULT '',
    gender ENUM('male','female','other') NULL,
    nationality VARCHAR(100) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_booking_id (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

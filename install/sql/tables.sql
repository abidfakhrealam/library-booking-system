-- Extended table structure with additional features

-- Students table with admin flag
CREATE TABLE students (
    student_id VARCHAR(20) PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    department VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Wings table
CREATE TABLE wings (
    wing_id INT AUTO_INCREMENT PRIMARY KEY,
    wing_name ENUM('North', 'South', 'East', 'West') NOT NULL UNIQUE,
    description TEXT,
    floor INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE
);

-- Cubicles table with additional features
CREATE TABLE cubicles (
    cubicle_id INT AUTO_INCREMENT PRIMARY KEY,
    wing_id INT NOT NULL,
    cubicle_number VARCHAR(10) NOT NULL,
    qr_code_path VARCHAR(255) NOT NULL UNIQUE,
    qr_code_identifier VARCHAR(50) NOT NULL UNIQUE,
    has_outlet BOOLEAN DEFAULT FALSE,
    has_monitor BOOLEAN DEFAULT FALSE,
    is_accessible BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (wing_id) REFERENCES wings(wing_id),
    UNIQUE KEY (wing_id, cubicle_number)
);

-- Booking slots
CREATE TABLE booking_slots (
    slot_id INT AUTO_INCREMENT PRIMARY KEY,
    slot_name VARCHAR(20) NOT NULL,
    duration_hours INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Bookings with additional tracking
CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    cubicle_id INT NOT NULL,
    slot_id INT NOT NULL,
    booking_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('Active', 'Completed', 'Cancelled', 'No-Show') DEFAULT 'Active',
    checked_in BOOLEAN DEFAULT FALSE,
    checked_in_time DATETIME NULL,
    notes TEXT,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (cubicle_id) REFERENCES cubicles(cubicle_id),
    FOREIGN KEY (slot_id) REFERENCES booking_slots(slot_id)
);

-- System settings
CREATE TABLE settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_group VARCHAR(50) DEFAULT 'General'
);

-- Activity log
CREATE TABLE activity_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(20),
    activity_type VARCHAR(50) NOT NULL,
    activity_details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES students(student_id)
);

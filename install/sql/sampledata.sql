-- Insert wings
INSERT INTO wings (wing_name, description, floor) VALUES 
('North', 'North Wing of the Library - Quiet Study Area', 1),
('South', 'South Wing of the Library - Group Study Area', 1),
('East', 'East Wing of the Library - Research Area', 2),
('West', 'West Wing of the Library - Computer Lab', 2);

-- Insert booking slots
INSERT INTO booking_slots (slot_name, duration_hours) VALUES 
('2 Hours', 2),
('4 Hours', 4),
('6 Hours', 6);

-- Insert sample cubicles (5 per wing)
INSERT INTO cubicles (wing_id, cubicle_number, qr_code_path, qr_code_identifier) VALUES 
-- North Wing
(1, 'N-101', '/assets/qrcodes/N-101.png', 'CUBE-N101'),
(1, 'N-102', '/assets/qrcodes/N-102.png', 'CUBE-N102'),
(1, 'N-103', '/assets/qrcodes/N-103.png', 'CUBE-N103'),
(1, 'N-104', '/assets/qrcodes/N-104.png', 'CUBE-N104'),
(1, 'N-105', '/assets/qrcodes/N-105.png', 'CUBE-N105'),

-- South Wing
(2, 'S-101', '/assets/qrcodes/S-101.png', 'CUBE-S101'),
(2, 'S-102', '/assets/qrcodes/S-102.png', 'CUBE-S102'),
(2, 'S-103', '/assets/qrcodes/S-103.png', 'CUBE-S103'),
(2, 'S-104', '/assets/qrcodes/S-104.png', 'CUBE-S104'),
(2, 'S-105', '/assets/qrcodes/S-105.png', 'CUBE-S105'),

-- East Wing
(3, 'E-201', '/assets/qrcodes/E-201.png', 'CUBE-E201'),
(3, 'E-202', '/assets/qrcodes/E-202.png', 'CUBE-E202'),
(3, 'E-203', '/assets/qrcodes/E-203.png', 'CUBE-E203'),
(3, 'E-204', '/assets/qrcodes/E-204.png', 'CUBE-E204'),
(3, 'E-205', '/assets/qrcodes/E-205.png', 'CUBE-E205'),

-- West Wing
(4, 'W-201', '/assets/qrcodes/W-201.png', 'CUBE-W201'),
(4, 'W-202', '/assets/qrcodes/W-202.png', 'CUBE-W202'),
(4, 'W-203', '/assets/qrcodes/W-203.png', 'CUBE-W203'),
(4, 'W-204', '/assets/qrcodes/W-204.png', 'CUBE-W204'),
(4, 'W-205', '/assets/qrcodes/W-205.png', 'CUBE-W205');

-- System settings
INSERT INTO settings (setting_name, setting_value, setting_group) VALUES
('system_name', 'University Library Cubicle Booking System', 'General'),
('max_daily_bookings', '1', 'Booking'),
('allow_concurrent_bookings', '0', 'Booking'),
('checkin_required', '1', 'Booking'),
('checkin_window_minutes', '15', 'Booking'),
('default_timezone', 'Asia/Kolkata', 'System');

<?php
// Prevent direct access
defined('BASE_PATH') or die('No direct script access allowed');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'library_booking');
define('DB_USER', 'root');
define('DB_PASS', '');

// System settings
define('BASE_URL', 'http://localhost/library-booking-system');
define('MAX_BOOKING_HOURS', 6);
define('QR_CODE_DIR', __DIR__ . '/../assets/qrcodes/');
define('ADMIN_EMAIL', 'admin@university.edu');

// Timezone settings
date_default_timezone_set('Asia/Kolkata');

// Session settings
session_start();
session_regenerate_id(true);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

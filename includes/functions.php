<?php
require_once 'db.php';

function getAvailableCubicles($wingId = null, $startTime = null, $endTime = null) {
    $startTime = $startTime ?: date('Y-m-d H:i:s');
    $endTime = $endTime ?: date('Y-m-d H:i:s', strtotime('+6 hours'));
    
    $sql = "SELECT c.*, w.wing_name 
           FROM cubicles c
           JOIN wings w ON c.wing_id = w.wing_id
           WHERE c.is_active = TRUE
           AND c.cubicle_id NOT IN (
               SELECT b.cubicle_id FROM bookings b
               WHERE b.status = 'Active'
               AND (
                   (b.start_time < ? AND b.end_time > ?) OR
                   (b.start_time BETWEEN ? AND ?) OR
                   (b.end_time BETWEEN ? AND ?)
               )
           )";
    
    $params = array_fill(0, 6, $endTime);
    array_splice($params, 1, 0, $startTime);
    array_splice($params, 3, 0, $startTime);
    array_splice($params, 5, 0, $endTime);
    
    if ($wingId) {
        $sql .= " AND c.wing_id = ?";
        $params[] = $wingId;
    }
    
    return Database::getInstance()->fetchAll($sql, $params);
}

function createBooking($studentId, $cubicleId, $slotId) {
    $db = Database::getInstance();
    
    // Get slot duration
    $slot = $db->fetchOne("SELECT duration_hours FROM booking_slots WHERE slot_id = ?", [$slotId]);
    if (!$slot) return false;
    
    $startTime = date('Y-m-d H:i:s');
    $endTime = date('Y-m-d H:i:s', strtotime("+{$slot['duration_hours']} hours"));
    
    // Check if student already has active booking
    $activeBookings = $db->fetchOne(
        "SELECT COUNT(*) as count FROM bookings 
        WHERE student_id = ? AND status = 'Active' AND end_time > NOW()",
        [$studentId]
    );
    
    if ($activeBookings['count'] > 0) {
        return ['success' => false, 'message' => 'You already have an active booking'];
    }
    
    // Create booking
    $sql = "INSERT INTO bookings (student_id, cubicle_id, slot_id, start_time, end_time)
           VALUES (?, ?, ?, ?, ?)";
    
    $result = $db->query($sql, [$studentId, $cubicleId, $slotId, $startTime, $endTime]);
    
    if ($result) {
        logActivity($studentId, 'booking_create', "Booked cubicle $cubicleId until $endTime");
        return ['success' => true, 'end_time' => $endTime];
    }
    
    return ['success' => false, 'message' => 'Failed to create booking'];
}

function getStudentBookings($studentId) {
    $sql = "SELECT b.*, c.cubicle_number, w.wing_name, s.slot_name
           FROM bookings b
           JOIN cubicles c ON b.cubicle_id = c.cubicle_id
           JOIN wings w ON c.wing_id = w.wing_id
           JOIN booking_slots s ON b.slot_id = s.slot_id
           WHERE b.student_id = ?
           ORDER BY b.start_time DESC";
    
    return Database::getInstance()->fetchAll($sql, [$studentId]);
}

function checkInBooking($bookingId, $studentId) {
    $db = Database::getInstance();
    
    // Verify booking belongs to student
    $booking = $db->fetchOne(
        "SELECT * FROM bookings 
        WHERE booking_id = ? AND student_id = ? AND status = 'Active'",
        [$bookingId, $studentId]
    );
    
    if (!$booking) {
        return ['success' => false, 'message' => 'Booking not found'];
    }
    
    // Check if already checked in
    if ($booking['checked_in']) {
        return ['success' => false, 'message' => 'Already checked in'];
    }
    
    // Check if within check-in window (15 minutes after start)
    $checkinWindow = strtotime($booking['start_time']) + (15 * 60);
    if (time() > $checkinWindow) {
        // Mark as no-show
        $db->query(
            "UPDATE bookings SET status = 'No-Show' 
            WHERE booking_id = ?",
            [$bookingId]
        );
        
        return ['success' => false, 'message' => 'Check-in window expired. Marked as No-Show'];
    }
    
    // Perform check-in
    $result = $db->query(
        "UPDATE bookings SET checked_in = TRUE, checked_in_time = NOW() 
        WHERE booking_id = ?",
        [$bookingId]
    );
    
    if ($result) {
        logActivity($studentId, 'check_in', "Checked in to booking $bookingId");
        return ['success' => true];
    }
    
    return ['success' => false, 'message' => 'Failed to check in'];
}

function generateQRCode($cubicleId, $cubicleNumber, $wingName) {
    require_once 'phpqrcode/qrlib.php'; // Include QR code library
    
    $url = BASE_URL . "/book.php?cubicle=" . urlencode($cubicleId);
    $filename = QR_CODE_DIR . "{$wingName}-{$cubicleNumber}.png";
    
    // Generate QR code
    QRcode::png($url, $filename, QR_ECLEVEL_L, 10);
    
    // Update database with QR code path
    $db = Database::getInstance();
    $db->query(
        "UPDATE cubicles SET qr_code_path = ?, qr_code_identifier = ? 
        WHERE cubicle_id = ?",
        ["/assets/qrcodes/{$wingName}-{$cubicleNumber}.png", 
         "CUBE-{$wingName}{$cubicleNumber}", 
         $cubicleId]
    );
    
    return $filename;
}

// In includes/functions.php
function sendBookingConfirmation($studentEmail, $cubicleDetails, $endTime) {
    $subject = "Library Cubicle Booking Confirmation";
    $message = "
        <h2>Your Cubicle Booking is Confirmed</h2>
        <p><strong>Cubicle:</strong> {$cubicleDetails['cubicle_number']} ({$cubicleDetails['wing_name']} Wing)</p>
        <p><strong>Booking End Time:</strong> " . date('F j, Y g:i A', strtotime($endTime)) . "</p>
        <p>Please remember to check in by scanning the cubicle QR code again when you arrive.</p>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: library@university.edu\r\n";
    
    mail($studentEmail, $subject, $message, $headers);
}

// Then modify createBooking() to call this after successful booking
if ($result['success']) {
    $student = $db->fetchOne("SELECT email FROM students WHERE student_id = ?", [$studentId]);
    $cubicle = $db->fetchOne("SELECT c.cubicle_number, w.wing_name FROM cubicles c JOIN wings w ON c.wing_id = w.wing_id WHERE c.cubicle_id = ?", [$cubicleId]);
    
    sendBookingConfirmation($student['email'], $cubicle, $endTime);
}

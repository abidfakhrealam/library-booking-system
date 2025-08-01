<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Mark expired bookings as completed
$db = Database::getInstance();
$db->query("
    UPDATE bookings 
    SET status = 'Completed' 
    WHERE status = 'Active' AND end_time < NOW()
");

// Log the cleanup
$affected = $db->getConnection()->affected_rows;
if ($affected > 0) {
    file_put_contents(__DIR__ . '/cleanup.log', date('Y-m-d H:i:s') . " - Marked $affected bookings as completed\n", FILE_APPEND);
}
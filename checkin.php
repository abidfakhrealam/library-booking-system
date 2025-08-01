<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

if (!isset($_GET['booking'])) {
    header('Location: mybookings.php');
    exit;
}

$bookingId = $_GET['booking'];
$result = checkInBooking($bookingId, $_SESSION['user']['id']);

if ($result['success']) {
    $_SESSION['success'] = 'Checked in successfully!';
} else {
    $_SESSION['error'] = $result['message'];
}

header('Location: mybookings.php');
exit;

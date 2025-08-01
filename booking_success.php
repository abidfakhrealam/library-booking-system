<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['booking_success'])) {
    header('Location: ' . BASE_URL);
    exit;
}

$booking = $_SESSION['booking_success'];
unset($_SESSION['booking_success']);

include 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card text-center">
                <div class="card-header bg-success text-white">
                    <h2>Booking Confirmed</h2>
                </div>
                <div class="card-body">
                    <h1 class="display-4">✓</h1>
                    <h3 class="card-title">Cubicle <?= htmlspecialchars($booking['cubicle']) ?></h3>
                    <p class="card-text"><?= htmlspecialchars($booking['wing']) ?> Wing</p>
                    
                    <div class="alert alert-info">
                        <p class="lead">Your booking is active until</p>
                        <h4><?= date('h:i A', strtotime($booking['end_time'])) ?></h4>
                    </div>
                    
                    <p>Please check in within 15 minutes of arrival by scanning the QR code again.</p>
                    
                    <a href="<?= BASE_URL ?>" class="btn btn-primary">Return to Home</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

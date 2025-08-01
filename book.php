<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Get cubicle from QR code
$cubicleId = $_GET['cubicle'] ?? null;
if (!$cubicleId) {
    header('Location: ' . BASE_URL);
    exit;
}

// Get cubicle info
$cubicle = Database::getInstance()->fetchOne(
    "SELECT c.*, w.wing_name 
    FROM cubicles c
    JOIN wings w ON c.wing_id = w.wing_id
    WHERE c.cubicle_id = ?",
    [$cubicleId]
);

if (!$cubicle) {
    die('Invalid cubicle');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    $studentId = $_POST['student_id'];
    $slotId = $_POST['slot_id'];
    
    $result = createBooking($studentId, $cubicleId, $slotId);
    
    if ($result['success']) {
        $_SESSION['booking_success'] = [
            'cubicle' => $cubicle['cubicle_number'],
            'wing' => $cubicle['wing_name'],
            'end_time' => $result['end_time']
        ];
        header('Location: ' . BASE_URL . '/booking_success.php');
        exit;
    } else {
        $error = $result['message'];
    }
}

// Get available slots
$slots = Database::getInstance()->fetchAll(
    "SELECT * FROM booking_slots WHERE is_active = TRUE"
);

include 'includes/header.php';
?>

<div class="container">
    <h1>Book Cubicle <?= htmlspecialchars($cubicle['cubicle_number']) ?></h1>
    <p class="lead"><?= htmlspecialchars($cubicle['wing_name']) ?> Wing</p>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Booking Details</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="student_id">Student ID</label>
                            <input type="text" class="form-control" id="student_id" name="student_id" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="slot_id">Booking Duration</label>
                            <select class="form-control" id="slot_id" name="slot_id" required>
                                <?php foreach ($slots as $slot): ?>
                                    <option value="<?= $slot['slot_id'] ?>"><?= htmlspecialchars($slot['slot_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Book Now</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Cubicle Information</h3>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <strong>Wing:</strong> <?= htmlspecialchars($cubicle['wing_name']) ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Number:</strong> <?= htmlspecialchars($cubicle['cubicle_number']) ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Features:</strong>
                            <?php 
                            $features = [];
                            if ($cubicle['has_outlet']) $features[] = 'Power Outlet';
                            if ($cubicle['has_monitor']) $features[] = 'Monitor';
                            if ($cubicle['is_accessible']) $features[] = 'Accessible';
                            echo $features ? implode(', ', $features) : 'Standard';
                            ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

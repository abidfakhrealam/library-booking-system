<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Get available cubicles by wing
$wings = Database::getInstance()->fetchAll("
    SELECT w.*, 
           (SELECT COUNT(*) FROM cubicles c WHERE c.wing_id = w.wing_id AND c.is_active = TRUE) as cubicle_count
    FROM wings w
    WHERE w.is_active = TRUE
    ORDER BY w.wing_name
");

// Get current bookings count
$currentBookings = Database::getInstance()->fetchOne("
    SELECT COUNT(*) as count FROM bookings 
    WHERE status = 'Active' AND end_time > NOW()
")['count'];

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto text-center">
            <h1 class="display-4">Library Cubicle Booking System</h1>
            <p class="lead">Book your study space in the university library</p>
            
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h3><i class="bi bi-door-open"></i> <?= $wings ? array_sum(array_column($wings, 'cubicle_count')) : 0 ?></h3>
                            <p class="text-muted">Total Cubicles Available</p>
                        </div>
                        <div class="col-md-6">
                            <h3><i class="bi bi-people"></i> <?= $currentBookings ?></h3>
                            <p class="text-muted">Active Bookings Now</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <?php foreach ($wings as $wing): ?>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="card-title"><?= htmlspecialchars($wing['wing_name']) ?> Wing</h3>
                        <p class="card-text"><?= htmlspecialchars($wing['description']) ?></p>
                        <p><i class="bi bi-door-closed"></i> <?= $wing['cubicle_count'] ?> cubicles</p>
                        
                        <?php 
                        $available = getAvailableCubicles($wing['wing_id']);
                        $availableCount = count($available);
                        $percentage = $wing['cubicle_count'] > 0 ? 
                            round(($availableCount / $wing['cubicle_count']) * 100) : 0;
                        ?>
                        
                        <div class="mb-3">
                            <span class="badge bg-<?= $percentage > 50 ? 'success' : ($percentage > 20 ? 'warning' : 'danger') ?>">
                                <?= $availableCount ?> available (<?= $percentage ?>%)
                            </span>
                        </div>
                        
                        <div class="progress mb-3" style="height: 20px;">
                            <div class="progress-bar bg-success" style="width: <?= $percentage ?>%"></div>
                        </div>
                        
                        <a href="book.php?wing=<?= $wing['wing_id'] ?>" class="btn btn-primary">
                            View Cubicles
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (isLoggedIn()): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3>My Bookings</h3>
                    </div>
                    <div class="card-body">
                        <?php 
                        $myBookings = getStudentBookings($_SESSION['user']['id']);
                        if ($myBookings): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Cubicle</th>
                                            <th>Wing</th>
                                            <th>Start Time</th>
                                            <th>End Time</th>
                                            <th>Status</th>
                                            <th>Checked In</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($myBookings as $booking): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($booking['cubicle_number']) ?></td>
                                                <td><?= htmlspecialchars($booking['wing_name']) ?></td>
                                                <td><?= date('M j, g:i A', strtotime($booking['start_time'])) ?></td>
                                                <td><?= date('M j, g:i A', strtotime($booking['end_time'])) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= 
                                                        $booking['status'] == 'Active' ? 'success' : 
                                                        ($booking['status'] == 'Completed' ? 'primary' : 'warning') 
                                                    ?>">
                                                        <?= $booking['status'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?= $booking['checked_in'] ? 
                                                        date('g:i A', strtotime($booking['checked_in_time'])) : 
                                                        '<span class="text-muted">No</span>' ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>You don't have any bookings yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

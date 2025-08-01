<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

// Get stats for dashboard
$stats = Database::getInstance()->fetchOne("
    SELECT 
        (SELECT COUNT(*) FROM students WHERE is_active = TRUE) as total_students,
        (SELECT COUNT(*) FROM cubicles WHERE is_active = TRUE) as total_cubicles,
        (SELECT COUNT(*) FROM bookings WHERE DATE(start_time) = CURDATE()) as today_bookings,
        (SELECT COUNT(*) FROM bookings WHERE status = 'Active') as active_bookings
");

// Recent bookings
$recentBookings = Database::getInstance()->fetchAll("
    SELECT b.*, s.full_name, c.cubicle_number, w.wing_name
    FROM bookings b
    JOIN students s ON b.student_id = s.student_id
    JOIN cubicles c ON b.cubicle_id = c.cubicle_id
    JOIN wings w ON c.wing_id = w.wing_id
    ORDER BY b.booking_time DESC
    LIMIT 10
");

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
            </div>
            
            <!-- Stats Cards -->
            <div class="row">
                <div class="col-md-3">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Students</h5>
                            <p class="card-text display-4"><?= $stats['total_students'] ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Cubicles</h5>
                            <p class="card-text display-4"><?= $stats['total_cubicles'] ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-white bg-info mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Today's Bookings</h5>
                            <p class="card-text display-4"><?= $stats['today_bookings'] ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-white bg-warning mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Active Bookings</h5>
                            <p class="card-text display-4"><?= $stats['active_bookings'] ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Bookings -->
            <h2 class="mt-4">Recent Bookings</h2>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Cubicle</th>
                            <th>Wing</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBookings as $booking): ?>
                            <tr>
                                <td><?= htmlspecialchars($booking['full_name']) ?></td>
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
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

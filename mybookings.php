<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$bookings = getStudentBookings($_SESSION['user']['id']);

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">My Bookings</h1>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <?php if ($bookings): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Cubicle</th>
                                        <th>Wing</th>
                                        <th>Duration</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Status</th>
                                        <th>Checked In</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($booking['cubicle_number']) ?></td>
                                            <td><?= htmlspecialchars($booking['wing_name']) ?></td>
                                            <td><?= htmlspecialchars($booking['slot_name']) ?></td>
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
                                            <td>
                                                <?php if ($booking['status'] == 'Active' && !$booking['checked_in']): ?>
                                                    <a href="checkin.php?booking=<?= $booking['booking_id'] ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        Check In
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-calendar-x" style="font-size: 3rem; color: #6c757d;"></i>
                            <h4 class="mt-3">No Bookings Found</h4>
                            <p>You haven't made any cubicle bookings yet.</p>
                            <a href="/" class="btn btn-primary">Book a Cubicle Now</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

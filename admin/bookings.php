<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

// Handle actions
if (isset($_GET['action']) && $_GET['action'] == 'cancel' && isset($_GET['id'])) {
    $result = Database::getInstance()->query(
        "UPDATE bookings SET status = 'Cancelled' WHERE booking_id = ?",
        [$_GET['id']]
    );
    
    if ($result) {
        $_SESSION['success'] = 'Booking cancelled successfully';
    } else {
        $_SESSION['error'] = 'Failed to cancel booking';
    }
    
    header('Location: bookings.php');
    exit;
}

// Filter handling
$filter = [];
$sql = "SELECT b.*, s.full_name, s.email, c.cubicle_number, w.wing_name, sl.slot_name
       FROM bookings b
       JOIN students s ON b.student_id = s.student_id
       JOIN cubicles c ON b.cubicle_id = c.cubicle_id
       JOIN wings w ON c.wing_id = w.wing_id
       JOIN booking_slots sl ON b.slot_id = sl.slot_id";

if (isset($_GET['status']) && in_array($_GET['status'], ['Active', 'Completed', 'Cancelled', 'No-Show'])) {
    $sql .= " WHERE b.status = ?";
    $filter[] = $_GET['status'];
}

if (isset($_GET['wing']) && is_numeric($_GET['wing'])) {
    $sql .= (strpos($sql, 'WHERE') === false ? " WHERE" : " AND") . " w.wing_id = ?";
    $filter[] = $_GET['wing'];
}

if (isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) {
    $sql .= (strpos($sql, 'WHERE') === false ? " WHERE" : " AND") . " DATE(b.start_time) = ?";
    $filter[] = $_GET['date'];
}

$sql .= " ORDER BY b.start_time DESC";

$bookings = Database::getInstance()->fetchAll($sql, $filter);

// Get wings for filter
$wings = Database::getInstance()->fetchAll("SELECT * FROM wings ORDER BY wing_name");

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Bookings</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="bookings.php" class="btn btn-outline-secondary">All Bookings</a>
                    </div>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Filters</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="Active" <?= isset($_GET['status']) && $_GET['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                                <option value="Completed" <?= isset($_GET['status']) && $_GET['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="Cancelled" <?= isset($_GET['status']) && $_GET['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                <option value="No-Show" <?= isset($_GET['status']) && $_GET['status'] == 'No-Show' ? 'selected' : '' ?>>No-Show</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Wing</label>
                            <select name="wing" class="form-select">
                                <option value="">All Wings</option>
                                <?php foreach ($wings as $wing): ?>
                                    <option value="<?= $wing['wing_id'] ?>" <?= isset($_GET['wing']) && $_GET['wing'] == $wing['wing_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($wing['wing_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="form-control" value="<?= $_GET['date'] ?? '' ?>">
                        </div>
                        
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="bookings.php" class="btn btn-link ms-2">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Bookings Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
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
                                <td><?= $booking['booking_id'] ?></td>
                                <td>
                                    <?= htmlspecialchars($booking['full_name']) ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($booking['email']) ?></small>
                                </td>
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
                                    <?php if ($booking['status'] == 'Active'): ?>
                                        <a href="bookings.php?action=cancel&id=<?= $booking['booking_id'] ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to cancel this booking?')">
                                            Cancel
                                        </a>
                                    <?php endif; ?>
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

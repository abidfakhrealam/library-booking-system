<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

// Default report - weekly usage
$startDate = date('Y-m-d', strtotime('-7 days'));
$endDate = date('Y-m-d');

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $reportType = $_POST['report_type'];
}

// Get report data
$usageReport = Database::getInstance()->fetchAll("
    SELECT 
        DATE(b.start_time) as booking_date,
        COUNT(*) as total_bookings,
        SUM(CASE WHEN b.status = 'Completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN b.status = 'No-Show' THEN 1 ELSE 0 END) as no_shows,
        SUM(CASE WHEN b.checked_in = TRUE THEN 1 ELSE 0 END) as checked_in
    FROM bookings b
    WHERE DATE(b.start_time) BETWEEN ? AND ?
    GROUP BY DATE(b.start_time)
    ORDER BY booking_date DESC
", [$startDate, $endDate]);

$wingUsage = Database::getInstance()->fetchAll("
    SELECT 
        w.wing_name,
        COUNT(*) as total_bookings,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM bookings WHERE DATE(start_time) BETWEEN ? AND ?), 2) as percentage
    FROM bookings b
    JOIN cubicles c ON b.cubicle_id = c.cubicle_id
    JOIN wings w ON c.wing_id = w.wing_id
    WHERE DATE(b.start_time) BETWEEN ? AND ?
    GROUP BY w.wing_name
    ORDER BY total_bookings DESC
", [$startDate, $endDate, $startDate, $endDate]);

$popularCubicles = Database::getInstance()->fetchAll("
    SELECT 
        CONCAT(w.wing_name, '-', c.cubicle_number) as cubicle,
        COUNT(*) as bookings_count
    FROM bookings b
    JOIN cubicles c ON b.cubicle_id = c.cubicle_id
    JOIN wings w ON c.wing_id = w.wing_id
    WHERE DATE(b.start_time) BETWEEN ? AND ?
    GROUP BY cubicle
    ORDER BY bookings_count DESC
    LIMIT 10
", [$startDate, $endDate]);

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Reports</h1>
            </div>
            
            <!-- Report Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Generate Report</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?= $startDate ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?= $endDate ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Report Type</label>
                            <select name="report_type" class="form-select">
                                <option value="usage">Usage Summary</option>
                                <option value="wings">Wing Usage</option>
                                <option value="cubicles">Popular Cubicles</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Generate</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Usage Report -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Usage Summary (<?= date('M j, Y', strtotime($startDate)) ?> to <?= date('M j, Y', strtotime($endDate)) ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Total Bookings</th>
                                    <th>Completed</th>
                                    <th>No-Shows</th>
                                    <th>Checked In</th>
                                    <th>Utilization</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usageReport as $report): ?>
                                    <tr>
                                        <td><?= date('M j, Y', strtotime($report['booking_date'])) ?></td>
                                        <td><?= $report['total_bookings'] ?></td>
                                        <td><?= $report['completed'] ?></td>
                                        <td><?= $report['no_shows'] ?></td>
                                        <td><?= $report['checked_in'] ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-success" 
                                                     style="width: <?= ($report['checked_in'] / $report['total_bookings']) * 100 ?>%">
                                                    <?= round(($report['checked_in'] / $report['total_bookings']) * 100, 2) ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Wing Usage -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Wing Usage Distribution</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Wing</th>
                                            <th>Bookings</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($wingUsage as $wing): ?>
                                            <tr>
                                                <td><?= $wing['wing_name'] ?></td>
                                                <td><?= $wing['total_bookings'] ?></td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-info" style="width: <?= $wing['percentage'] ?>%">
                                                            <?= $wing['percentage'] ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Most Popular Cubicles</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Cubicle</th>
                                            <th>Bookings</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($popularCubicles as $cubicle): ?>
                                            <tr>
                                                <td><?= $cubicle['cubicle'] ?></td>
                                                <td><?= $cubicle['bookings_count'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

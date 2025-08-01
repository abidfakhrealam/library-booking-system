<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

// Handle actions
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'add':
            handleAddCubicle();
            break;
        case 'edit':
            handleEditCubicle();
            break;
        case 'delete':
            handleDeleteCubicle();
            break;
        case 'generate_qr':
            handleGenerateQR();
            break;
    }
}

function handleAddCubicle() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $wingId = $_POST['wing_id'];
        $cubicleNumber = $_POST['cubicle_number'];
        $hasOutlet = isset($_POST['has_outlet']) ? 1 : 0;
        $hasMonitor = isset($_POST['has_monitor']) ? 1 : 0;
        $isAccessible = isset($_POST['is_accessible']) ? 1 : 0;
        
        $db = Database::getInstance();
        
        // First insert with placeholder QR code
        $sql = "INSERT INTO cubicles 
                (wing_id, cubicle_number, qr_code_path, qr_code_identifier, has_outlet, has_monitor, is_accessible)
                VALUES (?, ?, '', '', ?, ?, ?)";
        
        $result = $db->query($sql, [
            $wingId, $cubicleNumber, $hasOutlet, $hasMonitor, $isAccessible
        ]);
        
        if ($result) {
            $cubicleId = $db->lastInsertId();
            
            // Get wing name for QR code generation
            $wing = $db->fetchOne("SELECT wing_name FROM wings WHERE wing_id = ?", [$wingId]);
            
            // Generate QR code
            generateQRCode($cubicleId, $cubicleNumber, $wing['wing_name']);
            
            $_SESSION['success'] = 'Cubicle added successfully';
            header('Location: cubicles.php');
            exit;
        } else {
            $_SESSION['error'] = 'Failed to add cubicle';
        }
    }
}

function handleEditCubicle() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
        $cubicleId = $_GET['id'];
        $wingId = $_POST['wing_id'];
        $cubicleNumber = $_POST['cubicle_number'];
        $hasOutlet = isset($_POST['has_outlet']) ? 1 : 0;
        $hasMonitor = isset($_POST['has_monitor']) ? 1 : 0;
        $isAccessible = isset($_POST['is_accessible']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        $sql = "UPDATE cubicles SET
                wing_id = ?,
                cubicle_number = ?,
                has_outlet = ?,
                has_monitor = ?,
                is_accessible = ?,
                is_active = ?
                WHERE cubicle_id = ?";
        
        $result = Database::getInstance()->query($sql, [
            $wingId, $cubicleNumber, $hasOutlet, $hasMonitor, $isAccessible, $isActive, $cubicleId
        ]);
        
        if ($result) {
            $_SESSION['success'] = 'Cubicle updated successfully';
            header('Location: cubicles.php');
            exit;
        } else {
            $_SESSION['error'] = 'Failed to update cubicle';
        }
    }
}

function handleDeleteCubicle() {
    if (isset($_GET['id'])) {
        // Soft delete (mark as inactive)
        $result = Database::getInstance()->query(
            "UPDATE cubicles SET is_active = FALSE WHERE cubicle_id = ?",
            [$_GET['id']]
        );
        
        if ($result) {
            $_SESSION['success'] = 'Cubicle deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete cubicle';
        }
        
        header('Location: cubicles.php');
        exit;
    }
}

function handleGenerateQR() {
    if (isset($_GET['id'])) {
        $cubicle = Database::getInstance()->fetchOne(
            "SELECT c.*, w.wing_name FROM cubicles c
            JOIN wings w ON c.wing_id = w.wing_id
            WHERE c.cubicle_id = ?",
            [$_GET['id']]
        );
        
        if ($cubicle) {
            generateQRCode($cubicle['cubicle_id'], $cubicle['cubicle_number'], $cubicle['wing_name']);
            $_SESSION['success'] = 'QR code regenerated successfully';
        } else {
            $_SESSION['error'] = 'Cubicle not found';
        }
        
        header('Location: cubicles.php');
        exit;
    }
}

// Get all cubicles
$cubicles = Database::getInstance()->fetchAll("
    SELECT c.*, w.wing_name 
    FROM cubicles c
    JOIN wings w ON c.wing_id = w.wing_id
    ORDER BY w.wing_name, c.cubicle_number
");

// Get wings for dropdown
$wings = Database::getInstance()->fetchAll("SELECT * FROM wings WHERE is_active = TRUE");

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Cubicles</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCubicleModal">
                    Add New Cubicle
                </button>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Wing</th>
                            <th>Cubicle Number</th>
                            <th>Features</th>
                            <th>QR Code</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cubicles as $cubicle): ?>
                            <tr>
                                <td><?= $cubicle['cubicle_id'] ?></td>
                                <td><?= htmlspecialchars($cubicle['wing_name']) ?></td>
                                <td><?= htmlspecialchars($cubicle['cubicle_number']) ?></td>
                                <td>
                                    <?php 
                                    $features = [];
                                    if ($cubicle['has_outlet']) $features[] = 'Outlet';
                                    if ($cubicle['has_monitor']) $features[] = 'Monitor';
                                    if ($cubicle['is_accessible']) $features[] = 'Accessible';
                                    echo $features ? implode(', ', $features) : 'Standard';
                                    ?>
                                </td>
                                <td>
                                    <?php if ($cubicle['qr_code_path']): ?>
                                        <a href="<?= BASE_URL . $cubicle['qr_code_path'] ?>" target="_blank">
                                            <img src="<?= BASE_URL . $cubicle['qr_code_path'] ?>" width="50" alt="QR Code">
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Not generated</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $cubicle['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $cubicle['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-outline-primary" 
                                       data-bs-toggle="modal" data-bs-target="#editCubicleModal<?= $cubicle['cubicle_id'] ?>">
                                        Edit
                                    </a>
                                    
                                    <a href="cubicles.php?action=generate_qr&id=<?= $cubicle['cubicle_id'] ?>" 
                                       class="btn btn-sm btn-outline-info">
                                        Regenerate QR
                                    </a>
                                    
                                    <a href="cubicles.php?action=delete&id=<?= $cubicle['cubicle_id'] ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Are you sure?')">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                            
                            <!-- Edit Modal -->
                            <div class="modal fade" id="editCubicleModal<?= $cubicle['cubicle_id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Cubicle <?= $cubicle['cubicle_number'] ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="cubicles.php?action=edit&id=<?= $cubicle['cubicle_id'] ?>">
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Wing</label>
                                                    <select class="form-select" name="wing_id" required>
                                                        <?php foreach ($wings as $wing): ?>
                                                            <option value="<?= $wing['wing_id'] ?>" <?= $wing['wing_id'] == $cubicle['wing_id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($wing['wing_name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Cubicle Number</label>
                                                    <input type="text" class="form-control" name="cubicle_number" 
                                                           value="<?= htmlspecialchars($cubicle['cubicle_number']) ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="has_outlet" 
                                                               id="hasOutlet<?= $cubicle['cubicle_id'] ?>" <?= $cubicle['has_outlet'] ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="hasOutlet<?= $cubicle['cubicle_id'] ?>">
                                                            Has Power Outlet
                                                        </label>
                                                    </div>
                                                    
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="has_monitor" 
                                                               id="hasMonitor<?= $cubicle['cubicle_id'] ?>" <?= $cubicle['has_monitor'] ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="hasMonitor<?= $cubicle['cubicle_id'] ?>">
                                                            Has Monitor
                                                        </label>
                                                    </div>
                                                    
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="is_accessible" 
                                                               id="isAccessible<?= $cubicle['cubicle_id'] ?>" <?= $cubicle['is_accessible'] ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="isAccessible<?= $cubicle['cubicle_id'] ?>">
                                                            Accessible
                                                        </label>
                                                    </div>
                                                    
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="is_active" 
                                                               id="isActive<?= $cubicle['cubicle_id'] ?>" <?= $cubicle['is_active'] ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="isActive<?= $cubicle['cubicle_id'] ?>">
                                                            Active
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<!-- Add Cubicle Modal -->
<div class="modal fade" id="addCubicleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Cubicle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="cubicles.php?action=add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Wing</label>
                        <select class="form-select" name="wing_id" required>
                            <?php foreach ($wings as $wing): ?>
                                <option value="<?= $wing['wing_id'] ?>"><?= htmlspecialchars($wing['wing_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cubicle Number</label>
                        <input type="text" class="form-control" name="cubicle_number" required>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="has_outlet" id="hasOutletNew">
                            <label class="form-check-label" for="hasOutletNew">Has Power Outlet</label>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="has_monitor" id="hasMonitorNew">
                            <label class="form-check-label" for="hasMonitorNew">Has Monitor</label>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_accessible" id="isAccessibleNew">
                            <label class="form-check-label" for="isAccessibleNew">Accessible</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Cubicle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

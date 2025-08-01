<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

// Handle actions
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'add':
            handleAddStudent();
            break;
        case 'edit':
            handleEditStudent();
            break;
        case 'toggle':
            handleToggleStudent();
            break;
    }
}

function handleAddStudent() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $studentId = $_POST['student_id'];
        $fullName = $_POST['full_name'];
        $email = $_POST['email'];
        $department = $_POST['department'];
        $phone = $_POST['phone'] ?? null;
        $password = $_POST['password'];
        $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
        
        $hashedPass = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO students 
               (student_id, full_name, email, department, phone, password, is_admin)
               VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $result = Database::getInstance()->query($sql, [
            $studentId, $fullName, $email, $department, $phone, $hashedPass, $isAdmin
        ]);
        
        if ($result) {
            $_SESSION['success'] = 'Student added successfully';
            header('Location: students.php');
            exit;
        } else {
            $_SESSION['error'] = 'Failed to add student';
        }
    }
}

function handleEditStudent() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
        $studentId = $_GET['id'];
        $fullName = $_POST['full_name'];
        $email = $_POST['email'];
        $department = $_POST['department'];
        $phone = $_POST['phone'] ?? null;
        $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
        
        // Check if password is being updated
        $passwordUpdate = '';
        $params = [$fullName, $email, $department, $phone, $isAdmin, $studentId];
        
        if (!empty($_POST['password'])) {
            $hashedPass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $passwordUpdate = ', password = ?';
            array_splice($params, 4, 0, $hashedPass);
        }
        
        $sql = "UPDATE students SET
               full_name = ?,
               email = ?,
               department = ?,
               phone = ?,
               is_admin = ?
               $passwordUpdate
               WHERE student_id = ?";
        
        $result = Database::getInstance()->query($sql, $params);
        
        if ($result) {
            $_SESSION['success'] = 'Student updated successfully';
            header('Location: students.php');
            exit;
        } else {
            $_SESSION['error'] = 'Failed to update student';
        }
    }
}

function handleToggleStudent() {
    if (isset($_GET['id'])) {
        $result = Database::getInstance()->query(
            "UPDATE students SET is_active = NOT is_active WHERE student_id = ?",
            [$_GET['id']]
        );
        
        if ($result) {
            $_SESSION['success'] = 'Student status updated';
        } else {
            $_SESSION['error'] = 'Failed to update student status';
        }
        
        header('Location: students.php');
        exit;
    }
}

// Get all students
$students = Database::getInstance()->fetchAll("
    SELECT * FROM students ORDER BY is_active DESC, full_name
");

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Students</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                    Add New Student
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
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Last Login</th>
                            <th>Status</th>
                            <th>Admin</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= htmlspecialchars($student['student_id']) ?></td>
                                <td><?= htmlspecialchars($student['full_name']) ?></td>
                                <td><?= htmlspecialchars($student['email']) ?></td>
                                <td><?= htmlspecialchars($student['department']) ?></td>
                                <td>
                                    <?= $student['last_login'] ? 
                                        date('M j, g:i A', strtotime($student['last_login'])) : 
                                        '<span class="text-muted">Never</span>' ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $student['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $student['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <?= $student['is_admin'] ? 
                                        '<span class="badge bg-danger">Admin</span>' : 
                                        '<span class="badge bg-secondary">Student</span>' ?>
                                </td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-outline-primary" 
                                       data-bs-toggle="modal" data-bs-target="#editStudentModal<?= $student['student_id'] ?>">
                                        Edit
                                    </a>
                                    
                                    <a href="students.php?action=toggle&id=<?= $student['student_id'] ?>" 
                                       class="btn btn-sm btn-outline-<?= $student['is_active'] ? 'warning' : 'success' ?>">
                                        <?= $student['is_active'] ? 'Deactivate' : 'Activate' ?>
                                    </a>
                                </td>
                            </tr>
                            
                            <!-- Edit Modal -->
                            <div class="modal fade" id="editStudentModal<?= $student['student_id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Student: <?= htmlspecialchars($student['full_name']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="students.php?action=edit&id=<?= $student['student_id'] ?>">
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Full Name</label>
                                                    <input type="text" class="form-control" name="full_name" 
                                                           value="<?= htmlspecialchars($student['full_name']) ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Email</label>
                                                    <input type="email" class="form-control" name="email" 
                                                           value="<?= htmlspecialchars($student['email']) ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Department</label>
                                                    <input type="text" class="form-control" name="department" 
                                                           value="<?= htmlspecialchars($student['department']) ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Phone</label>
                                                    <input type="text" class="form-control" name="phone" 
                                                           value="<?= htmlspecialchars($student['phone'] ?? '') ?>">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">New Password (leave blank to keep current)</label>
                                                    <input type="password" class="form-control" name="password">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="is_admin" 
                                                               id="isAdmin<?= $student['student_id'] ?>" <?= $student['is_admin'] ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="isAdmin<?= $student['student_id'] ?>">
                                                            Administrator
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

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="students.php?action=add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Student ID</label>
                        <input type="text" class="form-control" name="student_id" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="full_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <input type="text" class="form-control" name="department" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_admin" id="isAdminNew">
                            <label class="form-check-label" for="isAdminNew">Administrator</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<?php
// Check if system is already installed
if (file_exists('../includes/config.php')) {
    die('System is already installed. Please remove the install directory.');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = $_POST['db_host'] ?? 'localhost';
    $dbName = $_POST['db_name'] ?? 'library_booking';
    $dbUser = $_POST['db_user'] ?? 'root';
    $dbPass = $_POST['db_pass'] ?? '';
    
    $adminEmail = $_POST['admin_email'] ?? '';
    $adminPass = $_POST['admin_pass'] ?? '';
    
    // Validate inputs
    $errors = [];
    if (empty($dbHost)) $errors[] = 'Database host is required';
    if (empty($dbName)) $errors[] = 'Database name is required';
    if (empty($dbUser)) $errors[] = 'Database user is required';
    if (empty($adminEmail)) $errors[] = 'Admin email is required';
    if (empty($adminPass)) $errors[] = 'Admin password is required';
    
    if (empty($errors)) {
        // Test database connection
        try {
            $db = new mysqli($dbHost, $dbUser, $dbPass);
            
            if ($db->connect_error) {
                throw new Exception("Connection failed: " . $db->connect_error);
            }
            
            // Create database if not exists
            if (!$db->select_db($dbName)) {
                if (!$db->query("CREATE DATABASE $dbName")) {
                    throw new Exception("Error creating database: " . $db->error);
                }
                $db->select_db($dbName);
            }
            
            // Execute SQL files
            $sqlFiles = ['tables.sql', 'sampledata.sql'];
            foreach ($sqlFiles as $file) {
                $sql = file_get_contents("sql/$file");
                if (!$db->multi_query($sql)) {
                    throw new Exception("Error executing $file: " . $db->error);
                }
                while ($db->more_results()) {
                    $db->next_result();
                }
            }
            
            // Create admin user
            $hashedPass = password_hash($adminPass, PASSWORD_DEFAULT);
            $db->query("INSERT INTO students (student_id, full_name, email, department, is_admin) 
                       VALUES ('admin', 'System Admin', '$adminEmail', 'Administration', 1)");
            
            // Create config file
            $configContent = <<<EOT
<?php
// Database configuration
define('DB_HOST', '$dbHost');
define('DB_NAME', '$dbName');
define('DB_USER', '$dbUser');
define('DB_PASS', '$dbPass');

// System settings
define('BASE_URL', 'http://' . \$_SERVER['HTTP_HOST'] . str_replace('/install', '', dirname(\$_SERVER['SCRIPT_NAME'])));
define('MAX_BOOKING_HOURS', 6);
define('QR_CODE_DIR', __DIR__ . '/../assets/qrcodes/');
EOT;
            
            file_put_contents('../includes/config.php', $configContent);
            
            // Create QR code directory
            if (!file_exists('../assets/qrcodes')) {
                mkdir('../assets/qrcodes', 0755, true);
            }
            
            // Installation complete
            header('Location: ../admin/dashboard.php?installed=1');
            exit;
            
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Library Booking System Installation</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
        .error { color: red; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 15px; background: #0066cc; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Library Booking System Installation</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <h2>Database Configuration</h2>
        
        <div class="form-group">
            <label>Database Host</label>
            <input type="text" name="db_host" value="localhost" required>
        </div>
        
        <div class="form-group">
            <label>Database Name</label>
            <input type="text" name="db_name" value="library_booking" required>
        </div>
        
        <div class="form-group">
            <label>Database Username</label>
            <input type="text" name="db_user" value="root" required>
        </div>
        
        <div class="form-group">
            <label>Database Password</label>
            <input type="password" name="db_pass">
        </div>
        
        <h2>Admin Account</h2>
        
        <div class="form-group">
            <label>Admin Email</label>
            <input type="email" name="admin_email" required>
        </div>
        
        <div class="form-group">
            <label>Admin Password</label>
            <input type="password" name="admin_pass" required>
        </div>
        
        <button type="submit">Install System</button>
    </form>
</body>
</html>

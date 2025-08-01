<?php
require_once 'db.php';

function authenticateStudent($studentId, $password) {
    $sql = "SELECT * FROM students WHERE student_id = ? AND is_active = TRUE";
    $student = Database::getInstance()->fetchOne($sql, [$studentId]);
    
    if ($student && password_verify($password, $student['password'])) {
        // Update last login
        $updateSql = "UPDATE students SET last_login = NOW() WHERE student_id = ?";
        Database::getInstance()->query($updateSql, [$studentId]);
        
        // Set session
        $_SESSION['user'] = [
            'id' => $student['student_id'],
            'name' => $student['full_name'],
            'email' => $student['email'],
            'is_admin' => $student['is_admin']
        ];
        
        logActivity($student['student_id'], 'login', 'User logged in');
        return true;
    }
    
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['user']['is_admin'];
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . '/?error=unauthorized');
        exit;
    }
}

function logActivity($userId, $type, $details) {
    $sql = "INSERT INTO activity_log (user_id, activity_type, activity_details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)";
    
    $params = [
        $userId,
        $type,
        $details,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ];
    
    Database::getInstance()->query($sql, $params);
}

function logout() {
    if (isset($_SESSION['user'])) {
        logActivity($_SESSION['user']['id'], 'logout', 'User logged out');
    }
    
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

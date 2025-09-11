<?php
/**
 * Authentication System
 * Simple role-based authentication for Students, Teachers, and Administrators
 */

session_start();

require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    public function register($username, $email, $password, $full_name, $role = 'student') {
        // Validate input
        if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters'];
        }
        
        // Check if username or email already exists
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$username, $email]
        );
        
        if ($existing) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        // Hash password and insert user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $this->db->query(
                "INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)",
                [$username, $email, $hashedPassword, $full_name, $role]
            );
            
            return ['success' => true, 'message' => 'Registration successful'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    public function login($username, $password) {
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Username and password are required'];
        }
        
        // Find user by username or email
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1",
            [$username, $username]
        );
        
        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        
        return ['success' => true, 'message' => 'Login successful', 'user' => $user];
    }
    
    public function logout() {
        session_unset();
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );
    }
    
    public function hasRole($role) {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: /Edu-Home-Virtual-Classroom/login.php');
            exit();
        }
    }
    
    public function requireRole($role) {
        $this->requireLogin();
        if (!$this->hasRole($role)) {
            header('Location: /Edu-Home-Virtual-Classroom/dashboard.php');
            exit();
        }
    }
}

// Global auth instance
$auth = new Auth();
?>

<?php

class Auth {
    private $db;
    private $useSession;
    
    public function __construct($db = null, $useSession = true) {
        if ($db === null) {
            global $db;
            $this->db = $db;
        } else {
            $this->db = $db;
        }
        $this->useSession = $useSession;
        
        if ($useSession && session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }
    
    public function login($username, $password) {
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Username and password are required'];
        }
        
        // Find user by username or email
        $stmt = $this->db->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        if ($this->useSession) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
        }
        
        return ['success' => true, 'message' => 'Login successful', 'user' => $user];
    }
    
    public function logout() {
        if ($this->useSession) {
            session_unset();
            session_destroy();
        }
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    public function isLoggedIn() {
        return $this->useSession && isset($_SESSION['user_id']);
    }
}
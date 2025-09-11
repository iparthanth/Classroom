<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Require login
$auth->requireLogin();

// Get current user
$user = $auth->getCurrentUser();

// Redirect based on user role
switch($user['role']) {
    case 'admin':
        redirect('/admin/dashboard.php');
        break;
    case 'teacher':
        redirect('/teacher/dashboard.php');
        break;
    case 'student':
        redirect('/student/dashboard.php');
        break;
    default:
        // If role is not recognized, logout and redirect to home
        $auth->logout();
        redirect('/');
}
?>

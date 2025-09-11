<?php
/**
 * Utility Functions
 * Helper functions for the E-Learning System
 */

/**
 * Display a flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Format date/time
 */
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

function formatDateTime($date) {
    return date('F j, Y, g:i a', strtotime($date));
}

/**
 * Slugify text for URLs
 */
function slugify($text) {
    // Replace spaces with hyphens
    $text = str_replace(' ', '-', $text);
    // Remove special characters
    $text = preg_replace('/[^A-Za-z0-9\-]/', '', $text);
    // Convert to lowercase
    $text = strtolower($text);
    // Remove duplicate hyphens
    $text = preg_replace('/-+/', '-', $text);
    // Trim hyphens from beginning and end
    return trim($text, '-');
}

/**
 * Truncate text with ellipsis
 */
function truncate($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

/**
 * Sanitize user input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Get the base URL of the application
 */
function baseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $basePath = '/Classroom/';
    
    return $protocol . '://' . $host . $basePath;
}

/**
 * Redirect to a URL
 */
function redirect($path) {
    header('Location: ' . baseUrl() . $path);
    exit();
}

/**
 * Get the current URL path
 */
function currentPath() {
    $path = $_SERVER['REQUEST_URI'];
    $basePath = '/Classroom';
    
    // Remove base path from URL
    if (strpos($path, $basePath) === 0) {
        $path = substr($path, strlen($basePath));
    }
    
    return $path;
}

/**
 * Check if current URL path matches a given path
 */
function isActivePath($path) {
    return currentPath() === $path;
}

/**
 * Generate a random string
 */
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get file extension
 */
function getFileExtension($filename) {
    return pathinfo($filename, PATHINFO_EXTENSION);
}

/**
 * Check if file extension is allowed
 */
function isAllowedFileExtension($filename, $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt']) {
    $ext = getFileExtension($filename);
    return in_array(strtolower($ext), $allowed);
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Create a pagination array
 */
function paginate($total, $perPage, $currentPage) {
    $lastPage = ceil($total / $perPage);
    $previousPage = $currentPage > 1 ? $currentPage - 1 : null;
    $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
    
    return [
        'total' => $total,
        'perPage' => $perPage,
        'currentPage' => $currentPage,
        'lastPage' => $lastPage,
        'previousPage' => $previousPage,
        'nextPage' => $nextPage
    ];
}
?>

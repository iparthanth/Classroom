<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Require login
$auth->requireLogin();
$user = $auth->getCurrentUser();

// Get file ID and verify permission
$submission_id = (int)($_GET['id'] ?? 0);

// Get submission details
$submission = null;
if ($user['role'] === 'teacher') {
    // Teachers can access files from their courses
    $submission = $db->fetchOne(
        "SELECT s.*, a.course_id, c.teacher_id, a.title as assignment_title,
                u.full_name as student_name
         FROM submissions s 
         JOIN assignments a ON s.assignment_id = a.id 
         JOIN courses c ON a.course_id = c.id 
         JOIN users u ON s.student_id = u.id
         WHERE s.id = ? AND c.teacher_id = ?",
        [$submission_id, $user['id']]
    );
} elseif ($user['role'] === 'student') {
    // Students can only access their own files
    $submission = $db->fetchOne(
        "SELECT s.*, a.title as assignment_title, u.full_name as student_name 
         FROM submissions s 
         JOIN assignments a ON s.assignment_id = a.id
         JOIN users u ON s.student_id = u.id
         WHERE s.id = ? AND s.student_id = ?",
        [$submission_id, $user['id']]
    );
} else {
    // Admin can access all files
    $submission = $db->fetchOne(
        "SELECT s.*, a.title as assignment_title, u.full_name as student_name
         FROM submissions s 
         JOIN assignments a ON s.assignment_id = a.id
         JOIN users u ON s.student_id = u.id
         WHERE s.id = ?",
        [$submission_id]
    );
}

if (!$submission || !$submission['file_path']) {
    header('HTTP/1.0 404 Not Found');
    echo 'File not found';
    exit;
}

$file_path = __DIR__ . '/uploads/' . $submission['file_path'];

if (!file_exists($file_path)) {
    header('HTTP/1.0 404 Not Found');
    echo 'File not found';
    exit;
}

// Get file extension and set content type
$extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
$content_types = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'txt' => 'text/plain',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png'
];

$content_type = $content_types[$extension] ?? 'application/octet-stream';

// Clean output buffer
if (ob_get_level()) {
    ob_end_clean();
}

// Set headers for file download
header('Content-Type: ' . $content_type);
header('Content-Disposition: inline; filename="' . 
    $submission['assignment_title'] . '_' . 
    $submission['student_name'] . '.' . $extension . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output file content
readfile($file_path);
exit;
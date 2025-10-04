<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Require login
$auth->requireLogin();
$user = $auth->getCurrentUser();

$file_path = null;
$file_name = null;

if (isset($_GET['file'])) {
    $file = $_GET['file'];
    // This is for assignment files
    // Need to verify that the user has access to the course
    $assignment = $db->fetchOne(
        "SELECT a.* FROM assignments a JOIN enrollments e ON a.course_id = e.course_id WHERE a.file_path = ? AND e.student_id = ?",
        [$file, $user['id']]
    );

    if ($assignment) {
        $file_path = __DIR__ . '/uploads/assignments/' . basename($assignment['file_path']);
        $file_name = $assignment['title'] . '.' . pathinfo($file_path, PATHINFO_EXTENSION);
    }
} elseif (isset($_GET['id'])) {
    // This is for submission files
    $submission_id = (int)($_GET['id'] ?? 0);
    $submission = null;
    if ($user['role'] === 'teacher') {
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
        $submission = $db->fetchOne(
            "SELECT s.*, a.title as assignment_title, u.full_name as student_name 
             FROM submissions s 
             JOIN assignments a ON s.assignment_id = a.id
             JOIN users u ON s.student_id = u.id
             WHERE s.id = ? AND s.student_id = ?",
            [$submission_id, $user['id']]
        );
    } else {
        $submission = $db->fetchOne(
            "SELECT s.*, a.title as assignment_title, u.full_name as student_name
             FROM submissions s 
             JOIN assignments a ON s.assignment_id = a.id
             JOIN users u ON s.student_id = u.id
             WHERE s.id = ?",
            [$submission_id]
        );
    }

    if ($submission && $submission['file_path']) {
        $file_path = __DIR__ . '/uploads/' . $submission['file_path'];
        $file_name = $submission['assignment_title'] . '_' . $submission['student_name'] . '.' . pathinfo($file_path, PATHINFO_EXTENSION);
    }
}

if (!$file_path || !file_exists($file_path)) {
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
header('Content-Disposition: inline; filename="' . $file_name . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output file content
readfile($file_path);
exit;
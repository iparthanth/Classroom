<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireRole('admin');
$user = $auth->getCurrentUser();

$course_id = (int)($_GET['id'] ?? 0);

// Get course details with teacher info
$course = $db->fetchOne(
    "SELECT c.*, u.full_name as teacher_name, u.email as teacher_email
     FROM courses c
     JOIN users u ON c.teacher_id = u.id
     WHERE c.id = ?",
    [$course_id]
);

if (!$course) {
    die("Course not found");
}

// Get enrolled students
$students = $db->fetchAll(
    "SELECT u.*, e.enrolled_at
     FROM enrollments e
     JOIN users u ON e.student_id = u.id
     WHERE e.course_id = ?
     ORDER BY e.enrolled_at DESC",
    [$course_id]
);

// Get assignments
$assignments = $db->fetchAll(
    "SELECT a.*, 
            (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id) as submission_count
     FROM assignments a
     WHERE a.course_id = ?
     ORDER BY a.due_date DESC",
    [$course_id]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Details - <?php echo htmlspecialchars($course['title']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font:14px/1.5 Arial,sans-serif;background:#f5f5f5;color:#333}
        .navbar{display:flex;justify-content:space-between;padding:15px 20px;
                background:#fff;border-bottom:1px solid #ddd}
        .brand{font-size:18px;font-weight:700;color:#dc3545}
        .navbar a{margin-left:15px;color:#333;text-decoration:none}
        .navbar a:hover{color:#dc3545}
        .container{max-width:1200px;margin:20px auto;padding:0 20px}
        .box{background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;margin-bottom:20px}
        h1,h2,h3{margin-bottom:15px;color:#333}
        .subtitle{font-size:14px;color:#666;margin-bottom:20px}
        .grid{display:grid;grid-gap:20px;grid-template-columns:repeat(auto-fit,minmax(300px,1fr))}
        .badge{display:inline-block;padding:4px 8px;border-radius:12px;font-size:12px;font-weight:500}
        .badge-blue{background:#e6f3ff;color:#0066cc}
        .badge-green{background:#e6f4ea;color:#1e7e34}
        .badge-red{background:#fde8e8;color:#dc3545}
        .text-muted{color:#666}
        .list{list-style:none}
        .list li{padding:12px;border-bottom:1px solid #eee}
        .list li:last-child{border-bottom:none}
        .btn{display:inline-block;padding:8px 16px;border-radius:4px;text-decoration:none;
             font-size:14px;margin-right:10px}
        .btn-primary{background:#0066cc;color:#fff}
        .btn-danger{background:#dc3545;color:#fff}
        .info-grid{display:grid;grid-template-columns:120px 1fr;gap:8px;margin-bottom:15px}
        .info-label{color:#666;font-weight:500}
        .table{width:100%;border-collapse:collapse;margin-top:10px}
        .table th,.table td{padding:12px;text-align:left;border-bottom:1px solid #eee}
        .table th{background:#f8f9fa;font-weight:600;color:#666}
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="brand">Course Details</div>
        <div>
            <a href="courses.php">Back to Courses</a>
            <a href="dashboard.php">Dashboard</a>
            <a href="../logout.php" style="color:#dc3545">Logout</a>
        </div>
    </nav>

    <div class="container">
        <!-- Course Overview -->
        <div class="box">
            <h1><?php echo htmlspecialchars($course['title']); ?></h1>
            <span class="badge <?php echo $course['is_active'] ? 'badge-green' : 'badge-red'; ?>">
                <?php echo $course['is_active'] ? 'Active' : 'Inactive'; ?>
            </span>
            
            <div class="info-grid" style="margin-top:20px">
                <div class="info-label">Course Code:</div>
                <div><?php echo htmlspecialchars($course['course_code']); ?></div>
                
                <div class="info-label">Teacher:</div>
                <div>
                    <?php echo htmlspecialchars($course['teacher_name']); ?>
                    (<?php echo htmlspecialchars($course['teacher_email']); ?>)
                </div>
                
                <div class="info-label">Max Students:</div>
                <div><?php echo $course['max_students']; ?></div>
                
                <div class="info-label">Created:</div>
                <div><?php echo formatDateTime($course['created_at']); ?></div>
            </div>

            <div style="margin-top:20px">
                <h3>Description</h3>
                <p><?php echo nl2br(htmlspecialchars($course['description'] ?? 'No description available')); ?></p>
            </div>
        </div>

        <div class="grid">
            <!-- Enrolled Students -->
            <div class="box">
                <h2>Enrolled Students</h2>
                <div class="subtitle"><?php echo count($students); ?> students enrolled</div>
                
                <?php if (empty($students)): ?>
                    <p class="text-muted">No students enrolled yet</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Enrolled Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($student['full_name']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($student['email']); ?></small>
                                    </td>
                                    <td><?php echo formatDateTime($student['enrolled_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Assignments -->
            <div class="box">
                <h2>Assignments</h2>
                <div class="subtitle"><?php echo count($assignments); ?> assignments total</div>
                
                <?php if (empty($assignments)): ?>
                    <p class="text-muted">No assignments created yet</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Assignment</th>
                                <th>Due Date</th>
                                <th>Submissions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                                    <td><?php echo formatDateTime($assignment['due_date']); ?></td>
                                    <td>
                                        <span class="badge badge-blue">
                                            <?php echo $assignment['submission_count']; ?> submissions
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
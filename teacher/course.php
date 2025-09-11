<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require teacher login
$auth->requireRole('teacher');
$user = $auth->getCurrentUser();

$course_id = (int)($_GET['id'] ?? 0);

// Get course details - only if this teacher owns the course
$course = $db->fetchOne(
    "SELECT * FROM courses WHERE id = ? AND teacher_id = ?",
    [$course_id, $user['id']]
);

if (!$course) {
    setFlash('error', 'Course not found or you do not have permission to access it.');
    redirect('/teacher/dashboard.php');
}

// Get enrolled students
$students = $db->fetchAll(
    "SELECT u.id, u.full_name, u.email, u.username, e.enrolled_at, e.status 
     FROM enrollments e 
     JOIN users u ON e.student_id = u.id 
     WHERE e.course_id = ? 
     ORDER BY e.enrolled_at DESC",
    [$course_id]
);

// Get course assignments
$assignments = $db->fetchAll(
    "SELECT a.*, 
     (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id) as submission_count
     FROM assignments a 
     WHERE a.course_id = ? 
     ORDER BY a.due_date ASC",
    [$course_id]
);

// Handle student removal
if ($_POST && $_POST['action'] === 'remove_student') {
    $student_id = (int)($_POST['student_id'] ?? 0);
    if ($student_id) {
        try {
            $db->query("DELETE FROM enrollments WHERE course_id = ? AND student_id = ?", [$course_id, $student_id]);
            setFlash('success', 'Student removed from course.');
            redirect("/teacher/course.php?id=$course_id");
        } catch (Exception $e) {
            setFlash('error', 'Failed to remove student.');
        }
    }
}

$flash = getFlash();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Course - <?php echo htmlspecialchars($course['title']); ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
        }
        .navbar {
            background: #fff;
            border-bottom: 1px solid #ddd;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .brand { 
            color: #7c3aed; 
            font-weight: 700; 
            font-size: 18px; 
        }
        .navbar a { 
            text-decoration: none; 
            color: #333; 
            margin-left: 15px; 
        }
        .navbar a:hover { 
            color: #7c3aed; 
        }
        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .box {
            background: #fff;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .flash {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .flash.success { 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb; 
        }
        .flash.error { 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
        }
        .grid {
            display: grid;
            gap: 20px;
        }
        .grid-2-cols {
            grid-template-columns: 1fr 1fr;
        }
        .grid-4-cols {
            grid-template-columns: repeat(4, 1fr);
        }
        @media (max-width: 768px) {
            .grid-2-cols, .grid-4-cols {
                grid-template-columns: 1fr;
            }
        }
        h1 { 
            color: #333;
            font-size: 24px; 
            margin: 0 0 10px; 
        }
        h2 { 
            color: #333; 
            font-size: 18px; 
            margin: 0 0 15px; 
        }
        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .course-meta {
            color: #666;
            font-size: 14px;
            margin: 5px 0;
        }
        .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        .btn-primary {
            background: #7c3aed;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .student-list, .assignment-list {
            max-height: 400px;
            overflow-y: auto;
        }
        .student-card, .assignment-card {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .student-card:hover, .assignment-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .student-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        .student-meta, .assignment-meta {
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-active {
            background: #e6f4ea;
            color: #1e7e34;
        }
        .status-closed {
            background: #f8f9fa;
            color: #6c757d;
        }
        .stat-box {
            text-align: center;
            padding: 15px;
            border-radius: 6px;
            background: #fff;
            border: 1px solid #ddd;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #7c3aed;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        .remove-btn {
            color: #dc3545;
            background: none;
            border: none;
            font-size: 13px;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .remove-btn:hover {
            background: #ffebee;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <nav class="navbar">
        <div class="brand">Manage Course</div>
        <div>
            <a href="dashboard.php">Dashboard</a>
            <span style="color: #666;">Hello, <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" style="color: #dc3545;">Logout</a>
        </div>
    </nav>

    <div class="container">
        <!-- Flash Messages -->
        <?php if($flash): ?>
            <div class="flash <?php echo $flash['type']; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <!-- Course Header -->
        <div class="box">
            <div class="course-header">
                <div>
                    <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                    <div class="course-meta"><?php echo htmlspecialchars($course['course_code']); ?></div>
                    <div class="course-meta" style="white-space: pre-line;"><?php echo htmlspecialchars($course['description']); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo count($students); ?></div>
                    <div class="stat-label">Enrolled Students</div>
                </div>
            </div>
            
            <div class="btn-group">
                <a href="assignments.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary">
                    📝 Manage Assignments
                </a>
                <a href="../whiteboard.php?course_id=<?php echo $course_id; ?>" class="btn btn-success">
                    🎨 Whiteboard
                </a>
                <a href="../chat.php?course_id=<?php echo $course_id; ?>" class="btn btn-secondary">
                    💬 Chat
                </a>
            </div>
        </div>

        <div class="grid grid-2-cols">
            <!-- Enrolled Students -->
            <div class="box">
                <h2>Enrolled Students (<?php echo count($students); ?>)</h2>
                
                <?php if (empty($students)): ?>
                    <div style="text-align: center; padding: 40px 0;">
                        <div style="font-size: 48px; color: #ccc; margin-bottom: 20px;">👥</div>
                        <p style="color: #666;">No students enrolled yet.</p>
                    </div>
                <?php else: ?>
                    <div class="student-list">
                        <?php foreach ($students as $student): ?>
                            <div class="student-card">
                                <div class="student-name"><?php echo htmlspecialchars($student['full_name']); ?></div>
                                <div class="student-meta"><?php echo htmlspecialchars($student['email']); ?></div>
                                <div class="student-meta">Enrolled: <?php echo formatDateTime($student['enrolled_at']); ?></div>
                                <div style="text-align: right; margin-top: 10px;">
                                    <form method="POST" class="inline" onsubmit="return confirm('Remove this student from the course?')">
                                        <input type="hidden" name="action" value="remove_student">
                                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                        <button type="submit" class="remove-btn">Remove Student</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Assignments -->
            <div class="box">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h2 style="margin: 0;">Assignments (<?php echo count($assignments); ?>)</h2>
                    <a href="assignments.php?course_id=<?php echo $course_id; ?>" 
                       style="color: #7c3aed; text-decoration: none;">View All</a>
                </div>
                
                <?php if (empty($assignments)): ?>
                    <div style="text-align: center; padding: 40px 0;">
                        <div style="font-size: 48px; color: #ccc; margin-bottom: 20px;">📋</div>
                        <p style="color: #666; margin-bottom: 15px;">No assignments created yet.</p>
                        <a href="assignments.php?course_id=<?php echo $course_id; ?>" 
                           class="btn btn-primary">Create Assignment</a>
                    </div>
                <?php else: ?>
                    <div class="assignment-list">
                        <?php foreach ($assignments as $assignment): ?>
                            <div class="assignment-card">
                                <div style="display: flex; justify-content: space-between;">
                                    <div>
                                        <div class="student-name"><?php echo htmlspecialchars($assignment['title']); ?></div>
                                        <div class="assignment-meta">Due: <?php echo formatDateTime($assignment['due_date']); ?></div>
                                        <div class="assignment-meta">
                                            <?php echo $assignment['submission_count']; ?> submissions • 
                                            <?php echo $assignment['max_points']; ?> points
                                        </div>
                                    </div>
                                    <div>
                                        <?php if (strtotime($assignment['due_date']) < time()): ?>
                                            <span class="status-badge status-closed">Closed</span>
                                        <?php else: ?>
                                            <span class="status-badge status-active">Active</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="btn-group" style="margin-top: 10px;">
                                    <a href="grade-assignment.php?id=<?php echo $assignment['id']; ?>" 
                                       class="btn btn-primary">Grade</a>
                                    <a href="assignments.php?course_id=<?php echo $course_id; ?>&edit=<?php echo $assignment['id']; ?>" 
                                       class="btn btn-secondary">Edit</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Course Statistics -->
        <div class="stats-grid grid-4-cols" style="margin-top: 30px;">
            <div class="stat-box">
                <div class="stat-number"><?php echo $course['max_students']; ?></div>
                <div class="stat-label">Max Capacity</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo count($students); ?></div>
                <div class="stat-label">Current Students</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo count($assignments); ?></div>
                <div class="stat-label">Assignments</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">
                    <?php echo array_sum(array_column($assignments, 'submission_count')); ?>
                </div>
                <div class="stat-label">Total Submissions</div>
            </div>
        </div>
    </div>
</body>
</html>

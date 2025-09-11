<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require teacher login
$auth->requireRole('teacher');
$user = $auth->getCurrentUser();

// Handle course creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'create_course') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $course_code = sanitizeInput($_POST['course_code'] ?? '');
    $max_students = (int)($_POST['max_students'] ?? 50);
    
    if (!empty($title) && !empty($course_code)) {
        try {
            $db->query(
                "INSERT INTO courses (title, description, course_code, teacher_id, max_students) VALUES (?, ?, ?, ?, ?)",
                [$title, $description, $course_code, $user['id'], $max_students]
            );
            setFlash('success', 'Course created successfully!');
        } catch (Exception $e) {
            setFlash('error', 'Failed to create course: ' . $e->getMessage());
        }
    } else {
        setFlash('error', 'Title and course code are required');
    }
    redirect('/teacher/dashboard.php');
}

// Get teacher's courses
$courses = $db->fetchAll(
    "SELECT c.*, 
     (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as student_count,
     (SELECT COUNT(*) FROM assignments WHERE course_id = c.id) as assignment_count
     FROM courses c 
     WHERE c.teacher_id = ? 
     ORDER BY c.created_at DESC",
    [$user['id']]
);

// Get recent assignments
$assignments = $db->fetchAll(
    "SELECT a.*, c.title as course_title,
     (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id) as submission_count
     FROM assignments a 
     JOIN courses c ON a.course_id = c.id 
     WHERE c.teacher_id = ?
     ORDER BY a.created_at DESC
     LIMIT 10",
    [$user['id']]
);

$flash = getFlash();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - E-Learning System</title>
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
        .brand { color: #7c3aed; font-weight: 700; font-size: 18px; }
        .navbar a { text-decoration: none; color: #333; margin-left: 15px; }
        .navbar a:hover { color: #7c3aed; }
        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .welcome-box {
            background: #7c3aed;
            color: white;
            padding: 25px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
        .flash {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .flash.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .flash.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        .box {
            background: #fff;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #ddd;
        }
        h1 { color: #7c3aed; font-size: 24px; margin: 0 0 10px; }
        h2 { color: #333; font-size: 18px; margin: 0 0 15px; }
        h3 { color: #333; font-size: 16px; margin: 0 0 10px; }
        .course-item, .assignment-item {
            border: 1px solid #eee;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .course-item:hover, .assignment-item:hover { background: #f8f9fa; }
        .course-links a {
            display: inline-block;
            background: #7c3aed;
            color: white;
            padding: 5px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 5px;
            margin-top: 8px;
        }
        .course-links a:hover { background: #6b21d1; }
        .btn {
            background: #7c3aed;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .btn:hover { background: #6b21d1; }
        .btn-create {
            background: #28a745;
        }
        .btn-create:hover { background: #218838; }
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .quick-actions {
            margin-top: 20px;
        }
        .quick-actions a {
            display: block;
            background: #007bff;
            color: white;
            padding: 12px;
            text-decoration: none;
            border-radius: 4px;
            text-align: center;
            margin-bottom: 8px;
        }
        .quick-actions a:hover { background: #0056b3; }
        .empty-state {
            text-align: center;
            color: #666;
            padding: 30px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 20px;
            width: 90%;
            max-width: 500px;
            border-radius: 6px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        input, textarea, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        textarea {
            height: 80px;
            resize: vertical;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="brand">👨‍🏫 Teacher Dashboard</div>
        <div>
            <span>Hello, <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if($flash): ?>
            <div class="flash <?php echo $flash['type']; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <div class="welcome-box">
            <h1>Welcome, Professor <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
            <p>Ready to inspire and educate your students?</p>
        </div>

        <div class="content">
            <!-- Main Content -->
            <div>
                <!-- My Courses -->
                <div class="box">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h2>My Courses</h2>
                        <button class="btn btn-create" onclick="showCreateCourse()">Create Course</button>
                    </div>
                    
                    <?php if(empty($courses)): ?>
                        <div class="empty-state">
                            <p>You haven't created any courses yet.</p>
                            <button class="btn btn-create" onclick="showCreateCourse()">Create Your First Course</button>
                        </div>
                    <?php else: ?>
                        <?php foreach($courses as $course): ?>
                            <div class="course-item">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div>
                                        <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                        <p style="color: #666; font-size: 14px; margin: 5px 0;">
                                            <?php echo htmlspecialchars($course['course_code']); ?>
                                        </p>
                                        <p style="color: #666; font-size: 14px; margin: 5px 0;">
                                            <?php echo truncate($course['description'], 100); ?>
                                        </p>
                                    </div>
                                    <div style="text-align: right; font-size: 12px; color: #666;">
                                        <div><?php echo $course['student_count']; ?> students</div>
                                        <div><?php echo $course['assignment_count']; ?> assignments</div>
                                    </div>
                                </div>
                                <div class="course-links">
                                    <a href="course.php?id=<?php echo $course['id']; ?>">Manage Course</a>
                                    <a href="../whiteboard.php?course_id=<?php echo $course['id']; ?>">Whiteboard</a>
                                    <a href="../chat.php?course_id=<?php echo $course['id']; ?>">Chat</a>
                                    <a href="assignments.php?course_id=<?php echo $course['id']; ?>">Assignments</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Recent Assignments -->
                <div class="box" style="margin-top: 20px;">
                    <h2>Recent Assignments</h2>
                    <?php if(empty($assignments)): ?>
                        <div class="empty-state">
                            <p>No assignments created yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($assignments as $assignment): ?>
                            <div class="assignment-item">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <h3><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                        <p style="color: #666; font-size: 14px; margin: 5px 0;">
                                            <?php echo htmlspecialchars($assignment['course_title']); ?>
                                        </p>
                                        <p style="color: #666; font-size: 12px; margin: 5px 0;">
                                            Due: <?php echo formatDateTime($assignment['due_date']); ?>
                                        </p>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 12px; color: #666; margin-bottom: 8px;">
                                            <?php echo $assignment['submission_count']; ?> submissions
                                        </div>
                                        <a href="grade-assignment.php?id=<?php echo $assignment['id']; ?>" class="btn">
                                            Grade
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <!-- Quick Stats -->
                <div class="box">
                    <h2>Quick Stats</h2>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span>Total Courses</span>
                            <strong><?php echo count($courses); ?></strong>
                        </div>
                        <div class="stat-item">
                            <span>Total Students</span>
                            <strong>
                                <?php echo array_sum(array_column($courses, 'student_count')); ?>
                            </strong>
                        </div>
                        <div class="stat-item">
                            <span>Assignments Created</span>
                            <strong>
                                <?php echo count($assignments); ?>
                            </strong>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="box quick-actions" style="margin-top: 20px;">
                    <h2>Quick Actions</h2>
                    <a href="all-assignments.php">All Assignments</a>
                    <a href="profile.php">Edit Profile</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Course Modal -->
    <div id="createCourseModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Create New Course</h2>
                <button onclick="closeModal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="create_course">
                
                <div class="form-group">
                    <label>Course Title</label>
                    <input type="text" name="title" required>
                </div>
                
                <div class="form-group">
                    <label>Course Code</label>
                    <input type="text" name="course_code" required>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Maximum Students</label>
                    <input type="number" name="max_students" value="50" min="1" max="500">
                </div>
                
                <div style="text-align: right;">
                    <button type="button" onclick="closeModal()" style="background: #6c757d; margin-right: 10px;" class="btn">Cancel</button>
                    <button type="submit" class="btn">Create Course</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showCreateCourse() {
            document.getElementById('createCourseModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('createCourseModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('createCourseModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>

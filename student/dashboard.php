<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require student login
$auth->requireRole('student');
$user = $auth->getCurrentUser();

// Get student's enrolled courses
$courses = $db->fetchAll(
    "SELECT c.*, u.full_name as teacher_name 
     FROM courses c 
     JOIN enrollments e ON c.id = e.course_id 
     JOIN users u ON c.teacher_id = u.id 
     WHERE e.student_id = ? AND c.is_active = 1",
    [$user['id']]
);

// Get recent assignments
$assignments = $db->fetchAll(
    "SELECT a.*, c.title as course_title, s.submitted_at, s.points_awarded
     FROM assignments a 
     JOIN courses c ON a.course_id = c.id 
     JOIN enrollments e ON c.id = e.course_id 
     LEFT JOIN submissions s ON a.id = s.assignment_id AND s.student_id = e.student_id
     WHERE e.student_id = ?
     ORDER BY a.due_date ASC
     LIMIT 10",
    [$user['id']]
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - E-Learning System</title>
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
        .brand { color: #28a745; font-weight: 700; font-size: 18px; }
        .navbar a { text-decoration: none; color: #333; margin-left: 15px; }
        .navbar a:hover { color: #28a745; }
        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .welcome-box {
            background: #28a745;
            color: white;
            padding: 25px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
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
        h1 { color: #28a745; font-size: 24px; margin: 0 0 10px; }
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
            background: #28a745;
            color: white;
            padding: 5px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 5px;
            margin-top: 8px;
        }
        .course-links a:hover { background: #218838; }
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
        .status-submitted { color: #28a745; font-weight: bold; }
        .status-overdue { color: #dc3545; font-weight: bold; }
        .submit-btn {
            background: #28a745;
            color: white;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
        }
        .submit-btn:hover { background: #218838; }
        .empty-state {
            text-align: center;
            color: #666;
            padding: 30px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="brand">📚 Student Dashboard</div>
        <div>
            <span>Hello, <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="welcome-box">
            <h1>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
            <p>Ready to continue your learning journey?</p>
        </div>

        <div class="content">
            <!-- Main Content -->
            <div>
                <!-- My Courses -->
                <div class="box">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h2>My Courses</h2>
                        <a href="browse-courses.php" style="color: #28a745; text-decoration: none;">Browse More</a>
                    </div>
                    
                    <?php if(empty($courses)): ?>
                        <div class="empty-state">
                            <p>You're not enrolled in any courses yet.</p>
                            <a href="browse-courses.php" class="submit-btn">Find Courses</a>
                        </div>
                    <?php else: ?>
                        <?php foreach($courses as $course): ?>
                            <div class="course-item">
                                <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                <p style="color: #666; font-size: 14px; margin: 5px 0;">
                                    <?php echo htmlspecialchars($course['course_code']); ?>
                                </p>
                                <p style="color: #666; font-size: 14px; margin: 5px 0;">
                                    Teacher: <?php echo htmlspecialchars($course['teacher_name']); ?>
                                </p>
                                <div class="course-links">
                                    <a href="course.php?id=<?php echo $course['id']; ?>">Enter Course</a>
                                    <a href="../whiteboard.php?course_id=<?php echo $course['id']; ?>">Whiteboard</a>
                                    <a href="../chat.php?course_id=<?php echo $course['id']; ?>">Chat</a>
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
                            <p>No assignments yet.</p>
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
                                    <div>
                                        <?php if($assignment['submitted_at']): ?>
                                            <span class="status-submitted">Submitted</span>
                                            <?php if($assignment['points_awarded']): ?>
                                                <div style="font-size: 12px; margin-top: 5px;">
                                                    Score: <?php echo $assignment['points_awarded']; ?>%
                                                </div>
                                            <?php endif; ?>
                                        <?php elseif(strtotime($assignment['due_date']) < time()): ?>
                                            <span class="status-overdue">Overdue</span>
                                        <?php else: ?>
                                            <a href="assignment.php?id=<?php echo $assignment['id']; ?>" class="submit-btn">
                                                Submit
                                            </a>
                                        <?php endif; ?>
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
                            <span>Enrolled Courses</span>
                            <strong><?php echo count($courses); ?></strong>
                        </div>
                        <div class="stat-item">
                            <span>Pending Assignments</span>
                            <strong>
                                <?php 
                                echo count(array_filter($assignments, function($a) {
                                    return !$a['submitted_at'] && strtotime($a['due_date']) > time();
                                }));
                                ?>
                            </strong>
                        </div>
                        <div class="stat-item">
                            <span>Completed</span>
                            <strong>
                                <?php 
                                echo count(array_filter($assignments, function($a) {
                                    return $a['submitted_at'];
                                }));
                                ?>
                            </strong>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="box quick-actions" style="margin-top: 20px;">
                    <h2>Quick Actions</h2>
                    <a href="browse-courses.php">Browse Courses</a>
                    <a href="my-submissions.php">My Submissions</a>
                    <a href="profile.php">Edit Profile</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

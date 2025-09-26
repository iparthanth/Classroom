<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireRole('student');
$user = $auth->getCurrentUser();

$courses = $db->fetchAll("
    SELECT c.*, u.full_name AS teacher_name
      FROM courses c
      JOIN enrollments e ON c.id = e.course_id
      JOIN users u ON c.teacher_id = u.id
     WHERE e.student_id = ? AND c.is_active = 1
", [$user['id']]);

$assignments = $db->fetchAll("
    SELECT a.*, c.title AS course_title, s.submitted_at, s.points_awarded
      FROM assignments a
      JOIN courses c ON a.course_id = c.id
      JOIN enrollments e ON c.id = e.course_id
 LEFT JOIN submissions s ON a.id = s.assignment_id AND s.student_id = e.student_id
     WHERE e.student_id = ?
  ORDER BY a.due_date ASC
  LIMIT 10
", [$user['id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Student Dashboard</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:Arial,sans-serif;background:#f5f5f5}
        .navbar{background:#fff;border-bottom:1px solid #ddd;padding:15px 20px;display:flex;justify-content:space-between;align-items:center}
        .brand{color:#28a745;font-weight:700;font-size:18px}
        .navbar a{color:#333;text-decoration:none;margin-left:15px}
        .navbar a:hover{color:#28a745}
        .container{max-width:1000px;margin:20px auto;padding:0 20px}
        .welcome-box{background:#28a745;color:#fff;padding:25px;border-radius:6px;margin-bottom:20px;text-align:center}
        .content{display:grid;grid-template-columns:2fr 1fr;gap:20px}
        .box{background:#fff;padding:20px;border-radius:6px;border:1px solid #ddd}
        h1{color:#28a745;font-size:24px;margin-bottom:10px}
        h2{color:#333;font-size:18px;margin-bottom:15px}
        h3{color:#333;font-size:16px;margin-bottom:10px}
        .course-item, .assignment-item{border:1px solid #eee;border-radius:4px;padding:15px;margin-bottom:10px;transition:background-color 0.2s ease}
        .course-item:hover, .assignment-item:hover{background:#f8f9fa}
        .course-links a{display:inline-block;background:#28a745;color:#fff;padding:5px 12px;text-decoration:none;border-radius:4px;font-size:12px;margin-right:5px;margin-top:8px}
        .course-links a:hover{background:#218838}
        .stats-grid{display:grid;grid-template-columns:1fr;gap:15px}
        .stat-item{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #eee}
        .quick-actions{margin-top:20px}
        .quick-actions a{display:block;background:#007bff;color:#fff;padding:12px;text-decoration:none;border-radius:4px;text-align:center;margin-bottom:8px}
        .quick-actions a:hover{background:#0056b3}
        .status-submitted{color:#28a745;font-weight:bold}
        .status-overdue{color:#dc3545;font-weight:bold}
        .submit-btn{background:#28a745;color:#fff;padding:6px 12px;text-decoration:none;border-radius:4px;font-size:12px}
        .submit-btn:hover{background:#218838}
        .empty-state{text-align:center;color:#666;padding:30px}
    </style>
</head>
<body>
<nav class="navbar">
    <div class="brand">📚 Student Dashboard</div>
    <div>
        <span>Hello, <?=htmlspecialchars($user['full_name'])?></span>
        <a href="../logout.php">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="welcome-box">
        <h1>Welcome back, <?=htmlspecialchars($user['full_name'])?>!</h1>
        <p>Ready to continue your learning journey?</p>
    </div>

    <div class="content">
        <div>
            <div class="box">
                <div class="flex justify-between items-center mb-4">
                    <h2>My Courses</h2>
                    <a href="browse-courses.php" class="text-green-700 hover:text-green-900">Browse More</a>
                </div>
                <?php if (empty($courses)): ?>
                    <div class="empty-state">
                        <p>You're not enrolled in any courses yet.</p>
                        <a href="browse-courses.php" class="submit-btn">Find Courses</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($courses as $course): ?>
                        <div class="course-item">
                            <h3><?=htmlspecialchars($course['title'])?></h3>
                            <p style="color:#666;font-size:14px;margin:5px 0;"><?=htmlspecialchars($course['course_code'])?></p>
                            <p style="color:#666;font-size:14px;margin:5px 0;">Teacher: <?=htmlspecialchars($course['teacher_name'])?></p>
                            <div class="course-links">
                                <a href="course.php?id=<?=$course['id']?>">Enter Course</a>
                                <a href="../whiteboard.php?course_id=<?=$course['id']?>">Whiteboard</a>
                                <a href="../chat.php?course_id=<?=$course['id']?>">Chat</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="box mt-5">
                <h2>Recent Assignments</h2>
                <?php if (empty($assignments)): ?>
                    <div class="empty-state"><p>No assignments yet.</p></div>
                <?php else: ?>
                    <?php foreach ($assignments as $assignment): ?>
                        <div class="assignment-item">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3><?=htmlspecialchars($assignment['title'])?></h3>
                                    <p style="color:#666;font-size:14px;margin:5px 0;"><?=htmlspecialchars($assignment['course_title'])?></p>
                                    <p style="color:#666;font-size:12px;margin:5px 0;">Due: <?=formatDateTime($assignment['due_date'])?></p>
                                </div>
                                <div>
                                    <?php if ($assignment['submitted_at']): ?>
                                        <span class="status-submitted">Submitted</span>
                                        <?php if ($assignment['points_awarded']): ?>
                                            <div style="font-size:12px;margin-top:5px;">Score: <?=$assignment['points_awarded']?>%</div>
                                        <?php endif; ?>
                                    <?php elseif (strtotime($assignment['due_date']) < time()): ?>
                                        <span class="status-overdue">Overdue</span>
                                    <?php else: ?>
                                        <a href="assignment.php?id=<?=$assignment['id']?>" class="submit-btn">Submit</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach;?>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <div class="box">
                <h2>Quick Stats</h2>
                <div class="stats-grid">
                    <div class="stat-item"><span>Enrolled Courses</span><strong><?=count($courses)?></strong></div>
                    <div class="stat-item"><span>Pending Assignments</span><strong><?=count(array_filter($assignments, fn($a)=>!$a['submitted_at'] && strtotime($a['due_date'])>time()))?></strong></div>
                    <div class="stat-item"><span>Completed</span><strong><?=count(array_filter($assignments, fn($a)=>$a['submitted_at']))?></strong></div>
                </div>
            </div>

            <div class="box quick-actions mt-5">
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

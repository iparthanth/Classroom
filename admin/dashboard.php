<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require admin login
$auth->requireRole('admin');
$user = $auth->getCurrentUser();

// Handle user management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'toggle_user_status') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $current_status = $db->fetchOne("SELECT is_active FROM users WHERE id = ?", [$user_id]);
        
        if ($current_status) {
            $new_status = $current_status['is_active'] ? 0 : 1;
            $db->query("UPDATE users SET is_active = ? WHERE id = ?", [$new_status, $user_id]);
            setFlash('success', 'User status updated successfully');
        }
    }
    
    redirect('/admin/dashboard.php');
}

// Get system statistics
$stats = [
    'total_users' => $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'],
    'total_students' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'student'")['count'],
    'total_teachers' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'")['count'],
    'total_courses' => $db->fetchOne("SELECT COUNT(*) as count FROM courses")['count'],
    'total_assignments' => $db->fetchOne("SELECT COUNT(*) as count FROM assignments")['count'],
    'total_submissions' => $db->fetchOne("SELECT COUNT(*) as count FROM submissions")['count']
];

// Get recent users
$recent_users = $db->fetchAll(
    "SELECT * FROM users ORDER BY created_at DESC LIMIT 10"
);

// Get active courses
$active_courses = $db->fetchAll(
    "SELECT c.*, u.full_name as teacher_name, 
     (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as student_count
     FROM courses c 
     JOIN users u ON c.teacher_id = u.id 
     WHERE c.is_active = 1 
     ORDER BY c.created_at DESC 
     LIMIT 10"
);

$flash = getFlash();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - E-Learning System</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
            color: #333;
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
            color: #dc3545;
            font-weight: 700; 
            font-size: 18px; 
        }
        .navbar a { 
            text-decoration: none; 
            color: #333; 
            margin-left: 15px; 
        }
        .navbar a:hover { 
            color: #dc3545;
        }
        .container {
            max-width: 1200px;
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
        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #e6f4ea;
            color: #1e7e34;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #fde8e8;
            color: #dc3545;
            border: 1px solid #f5c6cb;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-box {
            background: #fff;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #ddd;
        }
        .stat-label {
            color: #666;
            font-size: 13px;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #dc3545;
        }
        .grid-2col {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .user-card, .course-card {
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .user-card:hover, .course-card:hover {
            background: #f8f9fa;
        }
        .user-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .user-email {
            color: #666;
            font-size: 14px;
        }
        .user-meta {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-green {
            background: #e6f4ea;
            color: #1e7e34;
        }
        .badge-red {
            background: #fde8e8;
            color: #dc3545;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            text-align: center;
            color: #fff;
            background: #dc3545;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar">
        <div class="brand">Admin Dashboard</div>
        <div>
            <span style="color: #666;">Hello, <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" style="color: #dc3545;">Logout</a>
        </div>
    </nav>

    <div class="container">
        <!-- Flash Messages -->
        <?php if($flash): ?>
            <div class="alert <?php echo $flash['type'] === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <!-- Welcome Section -->
        <div class="box" style="background: #dc3545; color: white;">
            <h2 style="font-size: 24px; margin: 0 0 10px;">System Administration</h2>
            <p style="color: rgba(255,255,255,0.9); margin: 0;">Monitor and manage the E-Learning platform</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-label">Total Users</div>
                <div class="stat-value"><?php echo $stats['total_users']; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Students</div>
                <div class="stat-value"><?php echo $stats['total_students']; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Teachers</div>
                <div class="stat-value"><?php echo $stats['total_teachers']; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Courses</div>
                <div class="stat-value"><?php echo $stats['total_courses']; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Assignments</div>
                <div class="stat-value"><?php echo $stats['total_assignments']; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Submissions</div>
                <div class="stat-value"><?php echo $stats['total_submissions']; ?></div>
            </div>
        </div>

        <div class="grid-2col">
            <!-- Recent Users -->
            <div class="box">
                <h3 style="font-size: 18px; margin: 0 0 20px;">Recent Users</h3>
                <div>
                    <?php foreach($recent_users as $recent_user): ?>
                        <div class="user-card">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div>
                                    <div class="user-name"><?php echo htmlspecialchars($recent_user['full_name']); ?></div>
                                    <div class="user-email"><?php echo htmlspecialchars($recent_user['email']); ?></div>
                                    <div class="user-meta">
                                        <?php echo ucfirst($recent_user['role']); ?> • 
                                        Joined <?php echo formatDate($recent_user['created_at']); ?>
                                    </div>
                                </div>
                                <form method="POST" style="margin-left: 10px;">
                                    <input type="hidden" name="action" value="toggle_user_status">
                                    <input type="hidden" name="user_id" value="<?php echo $recent_user['id']; ?>">
                                    <button type="submit" class="badge <?php echo $recent_user['is_active'] ? 'badge-green' : 'badge-red'; ?>">
                                        <?php echo $recent_user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Active Courses -->
            <div class="box">
                <h3 style="font-size: 18px; margin: 0 0 20px;">Active Courses</h3>
                <div>
                    <?php if(empty($active_courses)): ?>
                        <p style="color: #666; text-align: center; padding: 20px;">No courses created yet.</p>
                    <?php else: ?>
                        <?php foreach($active_courses as $course): ?>
                            <div class="course-card">
                                <h4 style="font-weight: 600; margin: 0 0 5px;"><?php echo htmlspecialchars($course['title']); ?></h4>
                                <p style="color: #666; font-size: 14px; margin: 0 0 5px;"><?php echo htmlspecialchars($course['course_code']); ?></p>
                                <p style="color: #666; font-size: 13px; margin: 0;">
                                    Teacher: <?php echo htmlspecialchars($course['teacher_name']); ?> • 
                                    <?php echo $course['student_count']; ?> students enrolled
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Admin Actions -->
        <div class="box">
            <h3 style="font-size: 18px; margin: 0 0 20px;">Admin Actions</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <a href="users.php" class="btn" style="background: #0066cc;">
                    Manage Users
                </a>
                <a href="courses.php" class="btn" style="background: #1e7e34;">
                    Manage Courses
                </a>
                <a href="reports.php" class="btn" style="background: #6f42c1;">
                    View Reports
                </a>
                <a href="settings.php" class="btn" style="background: #5a6268;">
                    System Settings
                </a>
            </div>
        </div>
    </div>
</body>
</html>

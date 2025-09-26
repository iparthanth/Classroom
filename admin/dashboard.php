<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireRole('admin');
$user = $auth->getCurrentUser();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle_user_status') {
        $id = (int)($_POST['user_id'] ?? 0);
        if ($row = $db->fetchOne("SELECT is_active FROM users WHERE id = ?", [$id])) {
            $db->query("UPDATE users SET is_active = ? WHERE id = ?", [!$row['is_active'], $id]);
            setFlash('success', 'User status updated successfully');
        }
    }
    redirect('/admin/dashboard.php');
}

// Fetch stats
$stats = $db->fetchAll("
    SELECT 'total_users' AS key, COUNT(*) AS value FROM users
    UNION ALL SELECT 'total_students', COUNT(*) FROM users WHERE role='student'
    UNION ALL SELECT 'total_teachers', COUNT(*) FROM users WHERE role='teacher'
    UNION ALL SELECT 'total_courses', COUNT(*) FROM courses
    UNION ALL SELECT 'total_assignments', COUNT(*) FROM assignments
    UNION ALL SELECT 'total_submissions', COUNT(*) FROM submissions
", [], PDO::FETCH_KEY_PAIR);

// Fetch recent users and active courses
$recent_users   = $db->fetchAll("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
$active_courses = $db->fetchAll("
    SELECT c.id, c.title, c.course_code, u.full_name AS teacher_name,
           (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS student_count
      FROM courses c
      JOIN users u ON u.id = c.teacher_id
     WHERE c.is_active = 1
     ORDER BY c.created_at DESC
     LIMIT 10
");

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; color: #333; }
        .navbar { display: flex; justify-content: space-between; padding: 15px 20px; background: #fff; border-bottom: 1px solid #ddd; }
        .brand { font-size: 18px; font-weight: 700; color: #28a745; }
        .navbar a { margin-left: 15px; color: #333; text-decoration: none; }
        .navbar a:hover { color: #28a745; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .box { background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 20px; }
        .alert { padding: 12px 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #e6f4ea; color: #1e7e34; border: 1px solid #c3e6cb; }
        .alert-error { background: #fde8e8; color: #dc3545; border: 1px solid #f5c6cb; }
        h1, h2, h3 { margin-bottom: 10px; color: #333; }
        .subtitle { font-size: 14px; color: #666; margin-bottom: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-box { padding: 15px; border: 1px solid #ddd; border-radius: 6px; text-align: center; }
        .stat-label { font-size: 13px; color: #666; margin-bottom: 5px; }
        .stat-value { font-size: 24px; font-weight: 700; color: #28a745; }
        .grid-2col { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .user-card, .course-card { padding: 15px; border: 1px solid #eee; border-radius: 4px; margin-bottom: 10px; }
        .user-card:hover, .course-card:hover { background: #f8f9fa; }
        .user-name { font-weight: 600; margin-bottom: 5px; }
        .user-email { font-size: 14px; color: #666; }
        .user-meta { font-size: 12px; color: #666; margin-top: 5px; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; cursor: pointer; border: none; }
        .badge-green { background: #e6f4ea; color: #1e7e34; }
        .badge-red { background: #fde8e8; color: #dc3545; }
        .btn { display: inline-block; padding: 10px 20px; border: none; border-radius: 4px; color: #fff; text-decoration: none; font-weight: 500; cursor: pointer; }
        .btn:hover { opacity: 0.9; }
        .btn-blue { background: #0066cc; }
        .btn-green { background: #1e7e34; }
        .btn-purple { background: #6f42c1; }
        .btn-gray { background: #5a6268; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="brand">Admin Dashboard</div>
        <div>
            <span style="color:#666">Hello, <?=htmlspecialchars($user['full_name'])?></span>
            <a href="../logout.php" style="color:#dc3545">Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if($flash): ?>
            <div class="alert <?= $flash['type']==='success'?'alert-success':'alert-error' ?>">
                <?=htmlspecialchars($flash['message'])?>
            </div>
        <?php endif; ?>

        <div class="box" style="background:#28a745;color:#fff">
            <h2>System Administration</h2>
            <p>Monitor and manage the E-Learning platform</p>
        </div>

        <div class="stats-grid">
            <?php foreach($stats as $key => $val): ?>
                <div class="stat-box">
                    <div class="stat-label"><?=ucwords(str_replace('_',' ',$key))?></div>
                    <div class="stat-value"><?=$val?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="grid-2col">
            <div class="box">
                <h3>Recent Users</h3>
                <?php foreach($recent_users as $u): ?>
                    <div class="user-card" style="display:flex;justify-content:space-between;align-items:start">
                        <div>
                            <div class="user-name"><?=htmlspecialchars($u['full_name'])?></div>
                            <div class="user-email"><?=htmlspecialchars($u['email'])?></div>
                            <div class="user-meta">
                                <?=ucfirst($u['role'])?> • Joined <?=formatDate($u['created_at'])?>
                            </div>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="toggle_user_status">
                            <input type="hidden" name="user_id" value="<?=$u['id']?>">
                            <button class="badge <?=$u['is_active']?'badge-green':'badge-red'?>">
                                <?=$u['is_active']?'Active':'Inactive'?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="box">
                <h3>Active Courses</h3>
                <?php if(empty($active_courses)): ?>
                    <p style="color:#666;text-align:center;padding:20px">No active courses.</p>
                <?php else: ?>
                    <?php foreach($active_courses as $c): ?>
                        <div class="course-card">
                            <div style="font-weight:600"><?=$c['title']?></div>
                            <div style="font-size:14px;color:#666"><?=$c['course_code']?></div>
                            <div style="font-size:13px;color:#666">
                                Teacher: <?=$c['teacher_name']?> • <?=$c['student_count']?> students
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="box">
            <h3>Admin Actions</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px">
                <a href="users.php"        class="btn btn-blue">Manage Users</a>
                <a href="courses.php"      class="btn btn-green">Manage Courses</a>
                <a href="reports.php"      class="btn btn-purple">View Reports</a>
                <a href="settings.php"     class="btn btn-gray">System Settings</a>
            </div>
        </div>
    </div>
</body>
</html>

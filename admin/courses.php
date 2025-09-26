<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireRole('admin');
$user = $auth->getCurrentUser();

// Toggle course status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'toggle_course_status') {
    $courseId = (int)$_POST['course_id'];
    if ($course = $db->fetchOne("SELECT is_active FROM courses WHERE id = ?", [$courseId])) {
        $db->executeQuery("UPDATE courses SET is_active = ? WHERE id = ?", [!$course['is_active'], $courseId]);
        redirect('courses.php');
    }
}

// Fetch courses with teacher name and enrollment count
$courses = $db->fetchAll("
    SELECT c.id, c.title, c.course_code, c.created_at, c.is_active,
           u.full_name AS teacher_name,
           COUNT(e.id) AS enrollment_count
      FROM courses c
      JOIN users u ON c.teacher_id = u.id
 LEFT JOIN enrollments e ON e.course_id = c.id
  GROUP BY c.id
  ORDER BY c.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Courses</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; color: #333; }
        .navbar { display: flex; justify-content: space-between; padding: 15px 20px; background: #fff; border-bottom: 1px solid #ddd; }
        .brand { font-size: 18px; font-weight: 700; color: #28a745; }
        .navbar a { margin-left: 15px; color: #333; text-decoration: none; }
        .navbar a:hover { color: #28a745; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .box { background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 20px; }
        h1, h3 { margin-bottom: 10px; color: #333; }
        .subtitle { font-size: 14px; color: #666; margin-bottom: 20px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .stats p { font-size: 20px; font-weight: 700; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; }
        tr:hover { background: #f8f9fa; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 12px; }
        .badge-blue { background: #e6f3ff; color: #0066cc; }
        .badge-green { background: #e6f4ea; color: #1e7e34; }
        .badge-red { background: #fde8e8; color: #dc3545; }
        .btn { background: none; border: none; color: #666; padding: 6px 12px; font-size: 14px; cursor: pointer; }
        .btn:hover { background: #f8f9fa; }
        .course-code { font-size: 13px; color: #666; }
        .text-muted { color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="brand">Course Management</div>
        <div>
            <span style="color:#666">Admin: <?=htmlspecialchars($user['full_name'])?></span>
            <a href="dashboard.php">Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="box">
            <h1>Course Management</h1>
            <div class="subtitle">Manage all courses in the system</div>
            <div class="stats">
                <div>
                    <h3 class="text-muted">Total Courses</h3>
                    <p><?=count($courses)?></p>
                </div>
            </div>
        </div>

        <div class="box table-container">
            <table>
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Teacher</th>
                        <th>Enrollments</th>
                        <th>Created</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($courses as $c): ?>
                        <tr>
                            <td>
                                <div><?=htmlspecialchars($c['title'])?></div>
                                <div class="course-code"><?=htmlspecialchars($c['course_code'])?></div>
                            </td>
                            <td><?=htmlspecialchars($c['teacher_name'])?></td>
                            <td><span class="badge badge-blue"><?=$c['enrollment_count']?> students</span></td>
                            <td><span class="text-muted"><?=formatDate($c['created_at'])?></span></td>
                            <td>
                                <span class="badge <?= $c['is_active'] ? 'badge-green' : 'badge-red' ?>">
                                    <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="action" value="toggle_course_status">
                                    <input type="hidden" name="course_id" value="<?=$c['id']?>">
                                    <button type="submit" class="btn">
                                        <?= $c['is_active'] ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                                <a class="btn" style="color:#0066cc" href="course-details.php?id=<?=$c['id']?>">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

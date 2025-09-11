<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireRole('admin');
$user = $auth->getCurrentUser();

// Handle course status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_course_status') {
        $course_id = (int)$_POST['course_id'];
        
        $course = $db->fetchOne("SELECT * FROM courses WHERE id = ?", [$course_id]);
        if ($course) {
            $new_status = $course['is_active'] ? 0 : 1;
            $db->query("UPDATE courses SET is_active = ? WHERE id = ?", [$new_status, $course_id]);
            redirect('courses.php');
        }
    }
}

// Get all courses with teacher info and enrollment count
$courses = $db->fetchAll(
    "SELECT c.*, u.full_name as teacher_name,
            (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) as enrollment_count
     FROM courses c
     JOIN users u ON c.teacher_id = u.id
     ORDER BY c.created_at DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - Admin Dashboard</title>
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
        .subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background: #f8f9fa;
            text-align: left;
            padding: 12px;
            font-size: 13px;
            font-weight: 600;
            color: #666;
            border-bottom: 2px solid #ddd;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-blue {
            background: #e6f3ff;
            color: #0066cc;
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
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            background: none;
            color: #666;
        }
        .btn:hover {
            background: #f8f9fa;
        }
        .text-muted {
            color: #666;
        }
        .course-info {
            margin-bottom: 4px;
        }
        .course-code {
            color: #666;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar">
        <div class="brand">Course Management</div>
        <div>
            <span style="color: #666;">Admin: <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="dashboard.php">Dashboard</a>
            <a href="../logout.php" style="color: #dc3545;">Logout</a>
        </div>
    </nav>

    <div class="container">
        <!-- Page Header -->
        <div class="box">
            <h1>Course Management</h1>
            <div class="subtitle">Manage all courses in the system</div>
            
            <!-- Course Stats -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div>
                    <h3 class="text-muted" style="margin: 0 0 5px;">Total Courses</h3>
                    <p style="font-size: 20px; font-weight: bold; margin: 0;"><?php echo count($courses); ?></p>
                </div>
            </div>
        </div>

        <!-- Courses Table -->
        <div class="box">
            <div class="table-container">
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
                        <?php foreach($courses as $course): ?>
                            <tr>
                                <td>
                                    <div class="course-info"><?php echo htmlspecialchars($course['title']); ?></div>
                                    <div class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></div>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($course['teacher_name']); ?>
                                </td>
                                <td>
                                    <span class="badge badge-blue">
                                        <?php echo $course['enrollment_count']; ?> students
                                    </span>
                                </td>
                                <td>
                                    <span class="text-muted"><?php echo formatDate($course['created_at']); ?></span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $course['is_active'] ? 'badge-green' : 'badge-red'; ?>">
                                        <?php echo $course['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_course_status">
                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                        <button type="submit" class="btn">
                                            <?php echo $course['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                    <a href="../teacher/course.php?id=<?php echo $course['id']; ?>" class="btn" style="color: #0066cc;" target="_blank">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

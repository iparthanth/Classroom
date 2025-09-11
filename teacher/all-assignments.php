<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require teacher login
$auth->requireRole('teacher');
$user = $auth->getCurrentUser();

// Get all assignments by this teacher
$assignments = $db->fetchAll(
    "SELECT a.*, c.title as course_title, c.course_code,
            (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id) as submission_count,
            (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as total_students
     FROM assignments a 
     JOIN courses c ON a.course_id = c.id 
     WHERE c.teacher_id = ?
     ORDER BY a.due_date DESC",
    [$user['id']]
);

$flash = getFlash();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Assignments - Teacher Dashboard</title>
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
        h1 { 
            color: #7c3aed; 
            font-size: 24px; 
            margin: 0 0 10px; 
        }
        h2 { 
            color: #333; 
            font-size: 18px; 
            margin: 0 0 15px; 
        }
        .assignment-card {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.2s ease;
        }
        .assignment-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .assignment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        .assignment-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0 0 5px;
        }
        .assignment-meta {
            color: #666;
            font-size: 14px;
            margin: 5px 0;
        }
        .assignment-status {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 13px;
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
        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            margin: 10px 0;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: #7c3aed;
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        .btn-group {
            display: flex;
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        .stat-box {
            text-align: center;
            padding: 15px;
            border-radius: 6px;
        }
        .stat-box .number {
            font-size: 24px;
            font-weight: bold;
            color: #7c3aed;
        }
        .stat-box .label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        .stat-box.blue { background: #e8f0fe; }
        .stat-box.green { background: #e6f4ea; }
        .stat-box.yellow { background: #fff3cd; }
        .stat-box.purple { background: #f3e8ff; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <nav class="navbar">
        <div class="brand">All Assignments</div>
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

        <div class="box">
            <h2>All Your Assignments (<?php echo count($assignments); ?>)</h2>
            
            <?php if (empty($assignments)): ?>
                <div class="text-center py-12">
                    <div class="text-gray-400 text-6xl mb-4">📋</div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No assignments created yet</h3>
                    <p class="text-gray-500 mb-4">Create your first assignment to get started.</p>
                    <a href="dashboard.php" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                        Go to Dashboard
                    </a>
                </div>
            <?php else: ?>
                <?php foreach($assignments as $assignment): ?>
                    <div class="assignment-card">
                        <div class="assignment-header">
                            <div>
                                <h3 class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                <div class="assignment-meta">
                                    <span>📚 <?php echo htmlspecialchars($assignment['course_title']); ?></span> &bull;
                                    <span>📅 Due: <?php echo formatDateTime($assignment['due_date']); ?></span> &bull;
                                    <span>🎯 <?php echo $assignment['max_points']; ?> points</span>
                                </div>
                                <?php if($assignment['description']): ?>
                                    <p class="assignment-meta" style="margin-top: 10px;">
                                        <?php echo truncate($assignment['description'], 150); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if (strtotime($assignment['due_date']) < time()): ?>
                                    <span class="assignment-status status-closed">Closed</span>
                                <?php else: ?>
                                    <span class="assignment-status status-active">Active</span>
                                <?php endif; ?>
                            </div>
                        </div>
                            
                            <!-- Submission Stats -->
                            <div class="stats-grid">
                                <div class="stat-box blue">
                                    <div class="number"><?php echo $assignment['total_students']; ?></div>
                                    <div class="label">Total Students</div>
                                </div>
                                <div class="stat-box green">
                                    <div class="number"><?php echo $assignment['submission_count']; ?></div>
                                    <div class="label">Submissions</div>
                                </div>
                                <div class="stat-box yellow">
                                    <div class="number">
                                        <?php echo $assignment['total_students'] - $assignment['submission_count']; ?>
                                    </div>
                                    <div class="label">Pending</div>
                                </div>
                            </div>
                            
                            <!-- Progress Bar -->
                            <?php if ($assignment['total_students'] > 0): ?>
                                <div style="margin: 15px 0;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 14px; color: #666;">
                                        <span>Submission Progress</span>
                                        <span><?php echo round(($assignment['submission_count'] / $assignment['total_students']) * 100); ?>%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" 
                                             style="width: <?php echo ($assignment['submission_count'] / $assignment['total_students']) * 100; ?>%"></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Actions -->
                            <div class="btn-group">
                                <a href="grade-assignment.php?id=<?php echo $assignment['id']; ?>" 
                                   class="btn btn-primary">
                                    🎯 Grade Submissions (<?php echo $assignment['submission_count']; ?>)
                                </a>
                                <a href="assignments.php?course_id=<?php echo $assignment['course_id']; ?>&edit=<?php echo $assignment['id']; ?>" 
                                   class="btn btn-secondary">
                                    ✏️ Edit Assignment
                                </a>
                                <a href="course.php?id=<?php echo $assignment['course_id']; ?>" 
                                   class="btn btn-success">
                                    📚 View Course
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                
                <!-- Summary Statistics -->
                <div class="stats-grid" style="margin-top: 30px;">
                    <div class="stat-box blue">
                        <div class="number"><?php echo count($assignments); ?></div>
                        <div class="label">Total Assignments</div>
                    </div>
                    <div class="stat-box green">
                        <div class="number">
                            <?php echo count(array_filter($assignments, function($a) { return strtotime($a['due_date']) > time(); })); ?>
                        </div>
                        <div class="label">Active</div>
                    </div>
                    <div class="stat-box yellow">
                        <div class="number">
                            <?php echo count(array_filter($assignments, function($a) { return strtotime($a['due_date']) <= time(); })); ?>
                        </div>
                        <div class="label">Closed</div>
                    </div>
                    <div class="stat-box purple">
                        <div class="number">
                            <?php echo array_sum(array_column($assignments, 'submission_count')); ?>
                        </div>
                        <div class="label">Total Submissions</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

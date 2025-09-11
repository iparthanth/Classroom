<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireRole('teacher');
$user = $auth->getCurrentUser();

$course_id = (int)($_GET['course_id'] ?? 0);

// Verify teacher owns this course
$course = $db->fetchOne(
    "SELECT * FROM courses WHERE id = ? AND teacher_id = ?",
    [$course_id, $user['id']]
);

if (!$course) {
    redirect('/teacher/dashboard.php');
}

// Handle assignment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'create_assignment') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $due_date = $_POST['due_date'] ?? '';
    $max_points = (int)($_POST['max_points'] ?? 100);
    $file_required = isset($_POST['file_required']) ? 1 : 0;
    
    if (!empty($title) && !empty($due_date)) {
        try {
            $db->query(
                "INSERT INTO assignments (course_id, title, description, due_date, max_points, file_required) VALUES (?, ?, ?, ?, ?, ?)",
                [$course_id, $title, $description, $due_date, $max_points, $file_required]
            );
            setFlash('success', 'Assignment created successfully!');
        } catch (Exception $e) {
            setFlash('error', 'Failed to create assignment: ' . $e->getMessage());
        }
    } else {
        setFlash('error', 'Title and due date are required');
    }
    
    redirect('/teacher/assignments.php?course_id=' . $course_id);
}

// Get assignments for this course
$assignments = $db->fetchAll(
    "SELECT a.*, 
     (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id) as submission_count,
     (SELECT COUNT(*) FROM enrollments WHERE course_id = a.course_id) as total_students
     FROM assignments a 
     WHERE a.course_id = ? 
     ORDER BY a.due_date DESC",
    [$course_id]
);

$flash = getFlash();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments - <?php echo htmlspecialchars($course['title']); ?></title>
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
        .flash {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .flash.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .flash.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .course-info {
            background: #7c3aed;
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
        h1 { color: #7c3aed; font-size: 24px; margin: 0 0 10px; }
        h2 { color: #333; font-size: 18px; margin: 0 0 15px; }
        h3 { color: #333; font-size: 16px; margin: 0 0 10px; }
        .assignment-item {
            border: 1px solid #eee;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .assignment-item:hover { background: #f8f9fa; }
        .btn {
            background: #7c3aed;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
        }
        .btn:hover { background: #6b21d1; }
        .btn-create {
            background: #28a745;
            margin-bottom: 20px;
        }
        .btn-create:hover { background: #218838; }
        .btn-success {
            background: #28a745;
            font-size: 12px;
            padding: 4px 8px;
        }
        .btn-success:hover { background: #218838; }
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
            max-height: 90vh;
            overflow-y: auto;
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
        .badge {
            background: #007bff;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
        }
        .badge.file-required { background: #17a2b8; }
        .assignment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        .assignment-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #666;
        }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-actions { display: flex; gap: 10px; margin-top: 20px; }
        .form-actions button { flex: 1; }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="brand">📝 Assignments - <?php echo htmlspecialchars($course['course_code']); ?></div>
        <div>
            <a href="dashboard.php">Back to Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if($flash): ?>
            <div class="flash <?php echo $flash['type']; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <div class="course-info">
            <h1><?php echo htmlspecialchars($course['title']); ?></h1>
            <p><?php echo htmlspecialchars($course['description']); ?></p>
        </div>

        <div class="content">
            <!-- Main Content -->
            <div>
                <button class="btn btn-create" onclick="showCreateAssignment()">➕ Create New Assignment</button>
                
                <div class="box">
                    <h2>Course Assignments</h2>
                    
                    <?php if(empty($assignments)): ?>
                        <div class="empty-state">
                            <p>No assignments created yet.</p>
                            <button class="btn btn-create" onclick="showCreateAssignment()">Create Your First Assignment</button>
                        </div>
                    <?php else: ?>
                        <?php foreach($assignments as $assignment): ?>
                            <div class="assignment-item">
                                <div class="assignment-header">
                                    <div>
                                        <h3><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                        <div style="margin: 5px 0;">
                                            <span class="badge"><?php echo $assignment['max_points']; ?> points</span>
                                            <?php if($assignment['file_required']): ?>
                                                <span class="badge file-required">File Required</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <p style="color: #666; margin: 10px 0;"><?php echo htmlspecialchars($assignment['description']); ?></p>
                                
                                <div class="assignment-footer">
                                    <div>
                                        <strong>Due:</strong> <?php echo formatDateTime($assignment['due_date']); ?>
                                    </div>
                                    <div>
                                        <?php echo $assignment['submission_count']; ?> / <?php echo $assignment['total_students']; ?> submitted
                                    </div>
                                </div>
                                
                                <div style="margin-top: 10px;">
                                    <a href="grade-assignment.php?id=<?php echo $assignment['id']; ?>" class="btn btn-success">
                                        View Submissions
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <div class="box">
                    <h2>Course Stats</h2>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span>Total Assignments</span>
                            <strong><?php echo count($assignments); ?></strong>
                        </div>
                        <div class="stat-item">
                            <span>Total Submissions</span>
                            <strong><?php echo array_sum(array_column($assignments, 'submission_count')); ?></strong>
                        </div>
                        <div class="stat-item">
                            <span>Students Enrolled</span>
                            <strong><?php echo !empty($assignments) ? $assignments[0]['total_students'] : 0; ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Assignment Modal -->
    <div id="createAssignmentModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Create New Assignment</h2>
                <button onclick="hideCreateAssignment()" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="create_assignment">
                
                <div class="form-group">
                    <label>Assignment Title</label>
                    <input type="text" name="title" placeholder="e.g., Programming Quiz 1" required>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="Assignment instructions and details..."></textarea>
                </div>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label>Due Date</label>
                        <input type="datetime-local" name="due_date" required>
                    </div>
                    <div class="form-group">
                        <label>Max Points</label>
                        <input type="number" name="max_points" value="100" min="1" max="1000">
                    </div>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" name="file_required" style="margin-right: 8px; width: auto;">
                        Require file upload from students
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn">Create Assignment</button>
                    <button type="button" onclick="hideCreateAssignment()" class="btn" style="background: #6c757d;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showCreateAssignment() {
            document.getElementById('createAssignmentModal').style.display = 'block';
        }
        
        function hideCreateAssignment() {
            document.getElementById('createAssignmentModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('createAssignmentModal');
            if (event.target === modal) {
                hideCreateAssignment();
            }
        }
    </script>
</body>
</html>

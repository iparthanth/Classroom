<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require teacher login
$auth->requireRole('teacher');
$user = $auth->getCurrentUser();

$assignment_id = (int)($_GET['id'] ?? 0);

// Get assignment details - only if this teacher owns the course
$assignment = $db->fetchOne(
    "SELECT a.*, c.title as course_title, c.teacher_id 
     FROM assignments a 
     JOIN courses c ON a.course_id = c.id 
     WHERE a.id = ? AND c.teacher_id = ?",
    [$assignment_id, $user['id']]
);

if (!$assignment) {
    setFlash('error', 'Assignment not found or you do not have permission to access it.');
    redirect('/teacher/dashboard.php');
}

// Get all submissions for this assignment
$submissions = $db->fetchAll(
    "SELECT s.*, u.full_name, u.email 
     FROM submissions s 
     JOIN users u ON s.student_id = u.id 
     WHERE s.assignment_id = ? 
     ORDER BY s.submitted_at ASC",
    [$assignment_id]
);

// Handle grading
if ($_POST && $_POST['action'] === 'grade_submission') {
    $submission_id = (int)($_POST['submission_id'] ?? 0);
    $points = (int)($_POST['points'] ?? 0);
    $feedback = trim($_POST['feedback'] ?? '');
    
    if ($submission_id && $points >= 0 && $points <= $assignment['max_points']) {
        try {
            $db->query(
                "UPDATE submissions SET points_awarded = ?, feedback = ?, graded_at = NOW(), graded_by = ? WHERE id = ?",
                [$points, $feedback, $user['id'], $submission_id]
            );
            setFlash('success', 'Grade saved successfully!');
            redirect("/teacher/grade-assignment.php?id=$assignment_id");
        } catch (Exception $e) {
            setFlash('error', 'Failed to save grade.');
        }
    } else {
        setFlash('error', 'Invalid grade. Points must be between 0 and ' . $assignment['max_points'] . '.');
    }
}

$flash = getFlash();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Assignment - <?php echo htmlspecialchars($assignment['title']); ?></title>
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
        .flash.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .flash.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        h1 { color: #7c3aed; font-size: 24px; margin: 0 0 10px; }
        h2 { color: #333; font-size: 18px; margin: 0 0 15px; }
        h3 { color: #333; font-size: 16px; margin: 0 0 10px; }
        .submission-item {
            border: 1px solid #eee;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .submission-info {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        .student-info {
            flex: 1;
        }
        .grade-status {
            text-align: right;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
        }
        .grade-status.pending {
            background: #fff3cd;
            color: #856404;
        }
        .grade-status.graded {
            background: #d4edda;
            color: #155724;
        }
        .submission-content {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .btn {
            background: #7c3aed;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn:hover { background: #6b21d1; }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
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
        }
        .stat-box .label {
            color: #666;
            font-size: 14px;
        }
        .stat-box.blue { background: #e8f0fe; }
        .stat-box.green { background: #e6f4ea; }
        .stat-box.yellow { background: #fef3c7; }
        .stat-box.purple { background: #f3e8ff; }
        .feedback-box {
            background: #e8f0fe;
            border-left: 4px solid #7c3aed;
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 0 4px 4px 0;
        }
        .file-link {
            display: inline-flex;
            align-items: center;
            color: #7c3aed;
            text-decoration: none;
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .file-link:hover {
            background: #f8f9fa;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="navbar">
        <div class="brand">🎓 Grade Assignment</div>
        <div>
            <a href="dashboard.php">Dashboard</a>
            <span style="color: #666;">Hello, <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" style="color: #dc3545;">Logout</a>
        </div>
    </div>

    <div class="container">
        <!-- Flash Messages -->
        <?php if($flash): ?>
            <div class="flash <?php echo $flash['type']; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <!-- Assignment Header -->
        <div class="box">
            <h1><?php echo htmlspecialchars($assignment['title']); ?></h1>
            <p style="color: #666;"><?php echo htmlspecialchars($assignment['course_title']); ?></p>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 15px;">
                <div>
                    <span style="color: #666;">Due Date:</span>
                    <span style="font-weight: 600;"><?php echo formatDateTime($assignment['due_date']); ?></span>
                </div>
                <div>
                    <span style="color: #666;">Max Points:</span>
                    <span style="font-weight: 600;"><?php echo $assignment['max_points']; ?></span>
                </div>
                <div>
                    <span style="color: #666;">Submissions:</span>
                    <span style="font-weight: 600;"><?php echo count($submissions); ?></span>
                </div>
            </div>
            <?php if($assignment['description']): ?>
                <div style="margin-top: 15px;">
                    <span style="color: #666; font-size: 14px;">Description:</span>
                    <p style="margin-top: 5px;"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Submissions -->
        <div class="box">
            <h2>Student Submissions (<?php echo count($submissions); ?>)</h2>
            
            <?php if (empty($submissions)): ?>
                <div style="text-align: center; padding: 40px 0;">
                    <div style="font-size: 48px; color: #ccc; margin-bottom: 20px;">📋</div>
                    <h3 style="font-size: 20px; color: #333; margin-bottom: 10px;">No submissions yet</h3>
                    <p style="color: #666;">Students haven't submitted their work for this assignment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($submissions as $submission): ?>
                    <div class="submission-item">
                        <div class="submission-info">
                            <div class="student-info">
                                <h3><?php echo htmlspecialchars($submission['full_name']); ?></h3>
                                <p style="color: #666; font-size: 14px;"><?php echo htmlspecialchars($submission['email']); ?></p>
                                <p style="color: #666; font-size: 12px;">Submitted: <?php echo formatDateTime($submission['submitted_at']); ?></p>
                            </div>
                            <div>
                                <?php if ($submission['points_awarded'] !== null): ?>
                                    <div class="grade-status graded">
                                        Graded: <?php echo $submission['points_awarded']; ?>/<?php echo $assignment['max_points']; ?>
                                    </div>
                                    <?php if ($submission['graded_at']): ?>
                                        <p style="font-size: 12px; color: #666; margin-top: 5px; text-align: right;">
                                            Graded: <?php echo formatDateTime($submission['graded_at']); ?>
                                        </p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="grade-status pending">
                                        Pending Grade
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                            
                            <!-- Submission Content -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 15px 0;">
                                <!-- Text Submission -->
                                <?php if ($submission['submission_text']): ?>
                                    <div>
                                        <h4 style="font-weight: 600; margin-bottom: 10px;">Text Submission</h4>
                                        <div class="submission-content">
                                            <?php echo nl2br(htmlspecialchars($submission['submission_text'])); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- File Submission -->
                                <?php if ($submission['file_path']): ?>
                                    <div>
                                        <h4 style="font-weight: 600; margin-bottom: 10px;">File Submission</h4>
                                        <div class="submission-content">
                                            <a href="../download.php?id=<?php echo $submission['id']; ?>" 
                                               class="file-link" 
                                               target="_blank">
                                                📎 View Submitted File
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Current Feedback (if any) -->
                            <?php if ($submission['feedback']): ?>
                                <div style="margin: 15px 0;">
                                    <h4 style="font-weight: 600; margin-bottom: 10px;">Current Feedback</h4>
                                    <div class="feedback-box">
                                        <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Grading Form -->
                            <form method="POST" style="border-top: 1px solid #eee; padding-top: 20px;">
                                <input type="hidden" name="action" value="grade_submission">
                                <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                
                                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                                    <div class="form-group">
                                        <label>Points (0-<?php echo $assignment['max_points']; ?>)</label>
                                        <input type="number" name="points" 
                                               value="<?php echo $submission['points_awarded'] ?? ''; ?>"
                                               min="0" max="<?php echo $assignment['max_points']; ?>"
                                               class="form-control"
                                               required>
                                    </div>
                                    <div class="form-group">
                                        <label>Feedback (Optional)</label>
                                        <textarea name="feedback" rows="3" 
                                                  class="form-control"
                                                  placeholder="Provide feedback to the student..."><?php echo htmlspecialchars($submission['feedback'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                
                                <div style="margin-top: 15px;">
                                    <button type="submit" class="btn">
                                        <?php echo $submission['points_awarded'] !== null ? 'Update Grade' : 'Save Grade'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Grading Summary -->
                <div class="stats-grid">
                    <div class="stat-box blue">
                        <div class="number"><?php echo count($submissions); ?></div>
                        <div class="label">Total Submissions</div>
                    </div>
                    <div class="stat-box green">
                        <div class="number">
                            <?php echo count(array_filter($submissions, function($s) { return $s['points_awarded'] !== null; })); ?>
                        </div>
                        <div class="label">Graded</div>
                    </div>
                    <div class="stat-box yellow">
                        <div class="number">
                            <?php echo count(array_filter($submissions, function($s) { return $s['points_awarded'] === null; })); ?>
                        </div>
                        <div class="label">Pending</div>
                    </div>
                    <div class="stat-box purple">
                        <div class="number">
                            <?php 
                            $graded = array_filter($submissions, function($s) { return $s['points_awarded'] !== null; });
                            if(count($graded) > 0) {
                                $average = array_sum(array_map(function($s) { return $s['points_awarded']; }, $graded)) / count($graded);
                                echo round($average, 1);
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </div>
                        <div class="label">Average Score</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

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
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {theme: {extend: {colors: {primary: '#2563eb', secondary: '#10b981', accent: '#f59e0b'}}}}
    </script>
</head>
<body class="bg-gray-50">
    <!-- Header styled like Railway Management System -->
    <nav class="bg-white shadow-md border-b">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-xl">E</span>
                    </div>
                    <h1 class="text-xl font-bold text-gray-800">E-Learning System</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-blue-600 hover:text-blue-800 font-medium">Dashboard</a>
                    <span class="text-gray-700"><?php echo htmlspecialchars($user['full_name']); ?></span>
                    <a href="../logout.php" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Flash Messages -->
        <?php if($flash): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $flash['type'] == 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <!-- Assignment Header -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200 mb-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($assignment['title']); ?></h1>
            <p class="text-gray-600"><?php echo htmlspecialchars($assignment['course_title']); ?></p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div class="bg-blue-50 border border-blue-200 rounded p-3 text-center">
                    <div class="text-sm text-blue-700 font-medium">Due Date</div>
                    <div class="font-bold text-blue-600"><?php echo formatDateTime($assignment['due_date']); ?></div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded p-3 text-center">
                    <div class="text-sm text-green-700 font-medium">Max Points</div>
                    <div class="font-bold text-green-600"><?php echo $assignment['max_points']; ?></div>
                </div>
                <div class="bg-purple-50 border border-purple-200 rounded p-3 text-center">
                    <div class="text-sm text-purple-700 font-medium">Submissions</div>
                    <div class="font-bold text-purple-600"><?php echo count($submissions); ?></div>
                </div>
            </div>
            
            <?php if($assignment['description']): ?>
                <div class="mt-4">
                    <h3 class="font-medium text-gray-700 mb-2">Description</h3>
                    <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Submissions -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Student Submissions (<?php echo count($submissions); ?>)</h2>
            
            <?php if (empty($submissions)): ?>
                <div class="text-center py-12">
                    <div class="text-gray-400 text-6xl mb-4">📋</div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No submissions yet</h3>
                    <p class="text-gray-500">Students haven't submitted their work for this assignment.</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($submissions as $submission): 
                        $isGraded = $submission['points_awarded'] !== null;
                    ?>
                        <div class="border border-gray-300 rounded-lg p-5 hover:bg-blue-50 transition-colors">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($submission['full_name']); ?></h3>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($submission['email']); ?></p>
                                    <p class="text-xs text-gray-500 mt-1">Submitted: <?php echo formatDateTime($submission['submitted_at']); ?></p>
                                </div>
                                <div class="text-right">
                                    <?php if ($isGraded): ?>
                                        <div class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                                            Graded: <?php echo $submission['points_awarded']; ?>/<?php echo $assignment['max_points']; ?>
                                        </div>
                                        <?php if ($submission['graded_at']): ?>
                                            <p class="text-xs text-gray-500 mt-1">Graded: <?php echo formatDateTime($submission['graded_at']); ?></p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">
                                            Pending Grade
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Submission Content -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <?php if ($submission['submission_text']): ?>
                                    <div>
                                        <h4 class="font-medium text-gray-700 mb-2">Text Submission</h4>
                                        <div class="bg-gray-50 p-3 rounded border text-sm">
                                            <?php echo nl2br(htmlspecialchars($submission['submission_text'])); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($submission['file_path']): ?>
                                    <div>
                                        <h4 class="font-medium text-gray-700 mb-2">File Submission</h4>
                                        <a href="../download.php?id=<?php echo $submission['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-800 text-sm font-medium" 
                                           target="_blank">
                                            📎 View Submitted File
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Current Feedback (if any) -->
                            <?php if ($submission['feedback']): ?>
                                <div class="mb-4">
                                    <h4 class="font-medium text-gray-700 mb-2">Current Feedback</h4>
                                    <div class="bg-blue-50 border-l-4 border-blue-400 p-3 rounded-r text-sm">
                                        <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Grading Form -->
                            <form method="POST" class="border-t pt-4">
                                <input type="hidden" name="action" value="grade_submission">
                                <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Points (0-<?php echo $assignment['max_points']; ?>)</label>
                                        <input type="number" name="points" 
                                               value="<?php echo $submission['points_awarded'] ?? ''; ?>"
                                               min="0" max="<?php echo $assignment['max_points']; ?>"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Feedback (Optional)</label>
                                        <textarea name="feedback" rows="3" 
                                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                  placeholder="Provide feedback to the student..."><?php echo htmlspecialchars($submission['feedback'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-medium">
                                        <?php echo $isGraded ? 'Update Grade' : 'Save Grade'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Grading Summary -->
                <?php
                $gradedCount = count(array_filter($submissions, function($s) { return $s['points_awarded'] !== null; }));
                $pendingCount = count(array_filter($submissions, function($s) { return $s['points_awarded'] === null; }));
                $graded = array_filter($submissions, function($s) { return $s['points_awarded'] !== null; });
                $average = count($graded) > 0 ? round(array_sum(array_map(function($s) { return $s['points_awarded']; }, $graded)) / count($graded), 1) : 'N/A';
                ?>
                <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 border border-blue-300 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-blue-600"><?php echo count($submissions); ?></div>
                        <div class="text-sm text-blue-700 font-medium">Total Submissions</div>
                    </div>
                    <div class="bg-green-50 border border-green-300 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-green-600"><?php echo $gradedCount; ?></div>
                        <div class="text-sm text-green-700 font-medium">Graded</div>
                    </div>
                    <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-yellow-600"><?php echo $pendingCount; ?></div>
                        <div class="text-sm text-yellow-700 font-medium">Pending</div>
                    </div>
                    <div class="bg-purple-50 border border-purple-300 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-purple-600"><?php echo $average; ?></div>
                        <div class="text-sm text-purple-700 font-medium">Average Score</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
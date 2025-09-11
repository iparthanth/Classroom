<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require student login
$auth->requireRole('student');
$user = $auth->getCurrentUser();

$course_id = (int)($_GET['id'] ?? 0);

// Check if student is enrolled in this course
$enrollment = $db->fetchOne(
    "SELECT c.*, u.full_name as teacher_name, e.enrolled_at 
     FROM courses c 
     JOIN enrollments e ON c.id = e.course_id 
     JOIN users u ON c.teacher_id = u.id
     WHERE c.id = ? AND e.student_id = ? AND c.is_active = 1",
    [$course_id, $user['id']]
);

if (!$enrollment) {
    setFlash('error', 'Course not found or you are not enrolled in this course.');
    redirect('/student/dashboard.php');
}

// Get course assignments for this student
$assignments = $db->fetchAll(
    "SELECT a.*, s.submitted_at, s.points_awarded, s.feedback
     FROM assignments a 
     LEFT JOIN submissions s ON a.id = s.assignment_id AND s.student_id = ?
     WHERE a.course_id = ? 
     ORDER BY a.due_date ASC",
    [$user['id'], $course_id]
);

// Get course materials (if any)
$materials = $db->fetchAll(
    "SELECT * FROM course_materials WHERE course_id = ? ORDER BY upload_date DESC",
    [$course_id]
);

$flash = getFlash();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($enrollment['title']); ?> - Course</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563eb',
                        secondary: '#10b981',
                        accent: '#f59e0b'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-primary rounded flex items-center justify-center">
                        <span class="text-white font-bold text-xl">E</span>
                    </div>
                    <h1 class="text-xl font-bold text-gray-800">Course Dashboard</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-primary hover:text-blue-700">My Dashboard</a>
                    <span class="text-gray-600">Hello, <?php echo htmlspecialchars($user['full_name']); ?></span>
                    <a href="../logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Flash Messages -->
        <?php if($flash): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <!-- Course Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-8">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($enrollment['title']); ?></h1>
                    <p class="text-gray-600 text-lg"><?php echo htmlspecialchars($enrollment['course_code']); ?></p>
                    <p class="text-gray-700 mt-2"><?php echo nl2br(htmlspecialchars($enrollment['description'])); ?></p>
                    <div class="mt-4 flex items-center space-x-4 text-sm text-gray-600">
                        <span>👨‍🏫 Instructor: <?php echo htmlspecialchars($enrollment['teacher_name']); ?></span>
                        <span>📅 Enrolled: <?php echo formatDateTime($enrollment['enrolled_at']); ?></span>
                    </div>
                </div>
                <div class="flex flex-col space-y-2">
                    <a href="../whiteboard.php?course_id=<?php echo $course_id; ?>" 
                       class="bg-secondary text-white px-4 py-2 rounded-lg hover:bg-green-700 text-center">
                        🎨 Whiteboard
                    </a>
                    <a href="../chat.php?course_id=<?php echo $course_id; ?>" 
                       class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 text-center">
                        💬 Chat
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Course Assignments -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-2xl font-bold mb-6">Course Assignments</h2>
                    
                    <?php if (empty($assignments)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <div class="text-4xl mb-2">📋</div>
                            <p>No assignments have been posted yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($assignments as $assignment): ?>
                                <div class="border rounded-lg p-4 hover:bg-gray-50">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                            <?php if ($assignment['description']): ?>
                                                <p class="text-gray-600 text-sm mt-1"><?php echo truncate($assignment['description'], 150); ?></p>
                                            <?php endif; ?>
                                            <div class="mt-3 flex items-center space-x-4 text-sm text-gray-600">
                                                <span>📅 Due: <?php echo formatDateTime($assignment['due_date']); ?></span>
                                                <span>🎯 Points: <?php echo $assignment['max_points']; ?></span>
                                                <?php if ($assignment['file_required']): ?>
                                                    <span class="text-orange-600">📎 File Required</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="text-right ml-4">
                                            <?php if ($assignment['submitted_at']): ?>
                                                <div class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium mb-2">
                                                    ✓ Submitted
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    <?php echo formatDateTime($assignment['submitted_at']); ?>
                                                </div>
                                                <?php if ($assignment['points_awarded'] !== null): ?>
                                                    <div class="text-sm font-medium text-blue-600 mt-1">
                                                        Grade: <?php echo $assignment['points_awarded']; ?>/<?php echo $assignment['max_points']; ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php elseif (strtotime($assignment['due_date']) < time()): ?>
                                                <div class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium">
                                                    ⏰ Overdue
                                                </div>
                                            <?php else: ?>
                                                <div class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium mb-2">
                                                    📝 Pending
                                                </div>
                                                <a href="assignment.php?id=<?php echo $assignment['id']; ?>" 
                                                   class="bg-primary text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
                                                    Submit Work
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Show feedback if available -->
                                    <?php if ($assignment['feedback']): ?>
                                        <div class="mt-4 bg-blue-50 border-l-4 border-blue-400 p-3 rounded-r">
                                            <h4 class="text-sm font-medium text-blue-800">Teacher Feedback:</h4>
                                            <p class="text-blue-700 text-sm mt-1"><?php echo nl2br(htmlspecialchars($assignment['feedback'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Course Materials -->
                <?php if (!empty($materials)): ?>
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-2xl font-bold mb-6">Course Materials</h2>
                    <div class="space-y-3">
                        <?php foreach ($materials as $material): ?>
                            <div class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50">
                                <div>
                                    <h4 class="font-medium"><?php echo htmlspecialchars($material['title']); ?></h4>
                                    <?php if ($material['description']): ?>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($material['description']); ?></p>
                                    <?php endif; ?>
                                    <p class="text-xs text-gray-500">Uploaded: <?php echo formatDateTime($material['upload_date']); ?></p>
                                </div>
                                <div>
                                    <?php if ($material['file_path']): ?>
                                        <a href="../<?php echo htmlspecialchars($material['file_path']); ?>" 
                                           class="text-primary hover:text-blue-700" target="_blank">
                                            📎 View
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Assignment Summary -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Assignment Progress</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Assignments</span>
                            <span class="font-semibold"><?php echo count($assignments); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Submitted</span>
                            <span class="font-semibold text-green-600">
                                <?php echo count(array_filter($assignments, function($a) { return $a['submitted_at']; })); ?>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Pending</span>
                            <span class="font-semibold text-blue-600">
                                <?php echo count(array_filter($assignments, function($a) { 
                                    return !$a['submitted_at'] && strtotime($a['due_date']) > time(); 
                                })); ?>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Overdue</span>
                            <span class="font-semibold text-red-600">
                                <?php echo count(array_filter($assignments, function($a) { 
                                    return !$a['submitted_at'] && strtotime($a['due_date']) < time(); 
                                })); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                    <div class="space-y-2">
                        <a href="../whiteboard.php?course_id=<?php echo $course_id; ?>" 
                           class="block w-full bg-secondary text-white text-center py-2 rounded-lg hover:bg-green-700">
                            View Whiteboard
                        </a>
                        <a href="../chat.php?course_id=<?php echo $course_id; ?>" 
                           class="block w-full bg-gray-500 text-white text-center py-2 rounded-lg hover:bg-gray-600">
                            Join Chat
                        </a>
                        <a href="my-submissions.php" 
                           class="block w-full bg-blue-500 text-white text-center py-2 rounded-lg hover:bg-blue-600">
                            My Submissions
                        </a>
                        <a href="dashboard.php" 
                           class="block w-full bg-gray-300 text-gray-700 text-center py-2 rounded-lg hover:bg-gray-400">
                            Back to Dashboard
                        </a>
                    </div>
                </div>

                <!-- Course Info -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Course Information</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Code:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($enrollment['course_code']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Instructor:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($enrollment['teacher_name']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Enrolled:</span>
                            <span class="font-medium"><?php echo date('M j, Y', strtotime($enrollment['enrolled_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

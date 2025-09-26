<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireRole('teacher');
$user = $auth->getCurrentUser();

$course_id = (int)($_GET['id'] ?? 0);

// Get course details - only if this teacher owns the course
$course = $db->fetchOne(
    "SELECT * FROM courses WHERE id = ? AND teacher_id = ?",
    [$course_id, $user['id']]
);

if (!$course) {
    setFlash('error', 'Course not found or you do not have permission to access it.');
    redirect('/teacher/dashboard.php');
}

// Get enrolled students
$students = $db->fetchAll(
    "SELECT u.id, u.full_name, u.email, u.username, e.enrolled_at, e.status 
     FROM enrollments e 
     JOIN users u ON e.student_id = u.id 
     WHERE e.course_id = ? 
     ORDER BY e.enrolled_at DESC",
    [$course_id]
);

// Get course assignments
$assignments = $db->fetchAll(
    "SELECT a.*, 
     (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id) as submission_count
     FROM assignments a 
     WHERE a.course_id = ? 
     ORDER BY a.due_date ASC",
    [$course_id]
);

// Handle student removal
if ($_POST && $_POST['action'] === 'remove_student') {
    $student_id = (int)($_POST['student_id'] ?? 0);
    if ($student_id) {
        try {
            $db->query("DELETE FROM enrollments WHERE course_id = ? AND student_id = ?", [$course_id, $student_id]);
            setFlash('success', 'Student removed from course.');
            redirect("/teacher/course.php?id=$course_id");
        } catch (Exception $e) {
            setFlash('error', 'Failed to remove student.');
        }
    }
}

$flash = getFlash();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Course - <?php echo htmlspecialchars($course['title']); ?></title>
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

        <!-- Course Header -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200 mb-6">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-4">
                <div class="mb-4 lg:mb-0">
                    <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($course['title']); ?></h1>
                    <p class="text-gray-600"><?php echo htmlspecialchars($course['course_code']); ?></p>
                    <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($course['description']); ?></p>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600"><?php echo count($students); ?></div>
                    <div class="text-sm text-blue-700 font-medium">Enrolled Students</div>
                </div>
            </div>
            
            <div class="flex flex-wrap gap-3">
                <a href="assignments.php?course_id=<?php echo $course_id; ?>" 
                   class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-medium">
                    📝 Manage Assignments
                </a>
                <a href="../whiteboard.php?course_id=<?php echo $course_id; ?>" 
                   class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 font-medium">
                    🎨 Whiteboard
                </a>
                <a href="../chat.php?course_id=<?php echo $course_id; ?>" 
                   class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 font-medium">
                    💬 Chat
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Enrolled Students -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Enrolled Students (<?php echo count($students); ?>)</h2>
                
                <?php if (empty($students)): ?>
                    <div class="text-center py-8">
                        <div class="text-gray-400 text-6xl mb-4">👥</div>
                        <p class="text-gray-500">No students enrolled yet.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        <?php foreach ($students as $student): ?>
                            <div class="border border-gray-300 rounded-lg p-4 hover:bg-blue-50 transition-colors">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($student['full_name']); ?></h3>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($student['email']); ?></p>
                                        <p class="text-xs text-gray-500 mt-1">Enrolled: <?php echo formatDateTime($student['enrolled_at']); ?></p>
                                    </div>
                                    <form method="POST" onsubmit="return confirm('Remove this student from the course?')">
                                        <input type="hidden" name="action" value="remove_student">
                                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Assignments -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Assignments (<?php echo count($assignments); ?>)</h2>
                    <a href="assignments.php?course_id=<?php echo $course_id; ?>" 
                       class="text-blue-600 hover:text-blue-800 text-sm font-medium">View All</a>
                </div>
                
                <?php if (empty($assignments)): ?>
                    <div class="text-center py-8">
                        <div class="text-gray-400 text-6xl mb-4">📋</div>
                        <p class="text-gray-500 mb-4">No assignments created yet.</p>
                        <a href="assignments.php?course_id=<?php echo $course_id; ?>" 
                           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-medium">
                            Create Assignment
                        </a>
                    </div>
                <?php else: ?>
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        <?php foreach ($assignments as $assignment): 
                            $isActive = strtotime($assignment['due_date']) > time();
                        ?>
                            <div class="border border-gray-300 rounded-lg p-4 hover:bg-blue-50 transition-colors">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $isActive ? 'Active' : 'Closed'; ?>
                                    </span>
                                </div>
                                <div class="text-sm text-gray-600 mb-3">
                                    <p>Due: <?php echo formatDateTime($assignment['due_date']); ?></p>
                                    <p><?php echo $assignment['submission_count']; ?> submissions • <?php echo $assignment['max_points']; ?> points</p>
                                </div>
                                <div class="flex gap-2">
                                    <a href="grade-assignment.php?id=<?php echo $assignment['id']; ?>" 
                                       class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 font-medium">
                                        Grade
                                    </a>
                                    <a href="assignments.php?course_id=<?php echo $course_id; ?>&edit=<?php echo $assignment['id']; ?>" 
                                       class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700 font-medium">
                                        Edit
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Course Statistics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 border border-blue-300 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-blue-600"><?php echo $course['max_students']; ?></div>
                <div class="text-sm text-blue-700 font-medium">Max Capacity</div>
            </div>
            <div class="bg-green-50 border border-green-300 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-green-600"><?php echo count($students); ?></div>
                <div class="text-sm text-green-700 font-medium">Current Students</div>
            </div>
            <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-yellow-600"><?php echo count($assignments); ?></div>
                <div class="text-sm text-yellow-700 font-medium">Assignments</div>
            </div>
            <div class="bg-purple-50 border border-purple-300 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-purple-600"><?php echo array_sum(array_column($assignments, 'submission_count')); ?></div>
                <div class="text-sm text-purple-700 font-medium">Total Submissions</div>
            </div>
        </div>
    </div>
</body>
</html>
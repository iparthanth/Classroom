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

        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">All Your Assignments (<?php echo count($assignments); ?>)</h2>
            
            <?php if (empty($assignments)): ?>
                <div class="text-center py-12">
                    <div class="text-gray-400 text-6xl mb-4">📋</div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No assignments created yet</h3>
                    <p class="text-gray-500 mb-4">Create your first assignment to get started.</p>
                    <a href="dashboard.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-medium">
                        Go to Dashboard
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach($assignments as $assignment): 
                        $isActive = strtotime($assignment['due_date']) > time();
                        $submissionPercent = $assignment['total_students'] > 0 ? round(($assignment['submission_count'] / $assignment['total_students']) * 100) : 0;
                    ?>
                        <div class="border border-gray-300 rounded-lg p-5 hover:bg-blue-50 transition-colors">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex-1">
                                    <h3 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                    <p class="text-sm text-gray-600 mt-1">
                                        <span class="font-medium">📚 <?php echo htmlspecialchars($assignment['course_title']); ?></span> • 
                                        <span>📅 Due: <?php echo formatDateTime($assignment['due_date']); ?></span> • 
                                        <span>🎯 <?php echo $assignment['max_points']; ?> points</span>
                                    </p>
                                    <?php if($assignment['description']): ?>
                                        <p class="text-sm text-gray-600 mt-2">
                                            <?php echo truncate($assignment['description'], 150); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-4">
                                    <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $isActive ? 'Active' : 'Closed'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Stats and Progress -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div class="bg-blue-50 border border-blue-200 rounded p-3 text-center">
                                    <div class="text-xl font-bold text-blue-600"><?php echo $assignment['total_students']; ?></div>
                                    <div class="text-sm text-blue-700">Total Students</div>
                                </div>
                                <div class="bg-green-50 border border-green-200 rounded p-3 text-center">
                                    <div class="text-xl font-bold text-green-600"><?php echo $assignment['submission_count']; ?></div>
                                    <div class="text-sm text-green-700">Submissions</div>
                                </div>
                                <div class="bg-yellow-50 border border-yellow-200 rounded p-3 text-center">
                                    <div class="text-xl font-bold text-yellow-600"><?php echo $assignment['total_students'] - $assignment['submission_count']; ?></div>
                                    <div class="text-sm text-yellow-700">Pending</div>
                                </div>
                            </div>
                            
                            <?php if ($assignment['total_students'] > 0): ?>
                                <div class="mb-4">
                                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                                        <span>Submission Progress</span>
                                        <span><?php echo $submissionPercent; ?>%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $submissionPercent; ?>%"></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Action Buttons -->
                            <div class="flex flex-wrap gap-3">
                                <a href="grade-assignment.php?id=<?php echo $assignment['id']; ?>" 
                                   class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-medium">
                                    🎯 Grade Submissions (<?php echo $assignment['submission_count']; ?>)
                                </a>
                                <a href="assignments.php?course_id=<?php echo $assignment['course_id']; ?>&edit=<?php echo $assignment['id']; ?>" 
                                   class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 text-sm font-medium">
                                    ✏️ Edit Assignment
                                </a>
                                <a href="course.php?id=<?php echo $assignment['course_id']; ?>" 
                                   class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm font-medium">
                                    📚 View Course
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Summary Statistics -->
                <?php
                $activeCount = count(array_filter($assignments, function($a) { return strtotime($a['due_date']) > time(); }));
                $closedCount = count(array_filter($assignments, function($a) { return strtotime($a['due_date']) <= time(); }));
                $totalSubmissions = array_sum(array_column($assignments, 'submission_count'));
                ?>
                <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 border border-blue-300 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-blue-600"><?php echo count($assignments); ?></div>
                        <div class="text-sm text-blue-700 font-medium">Total Assignments</div>
                    </div>
                    <div class="bg-green-50 border border-green-300 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-green-600"><?php echo $activeCount; ?></div>
                        <div class="text-sm text-green-700 font-medium">Active</div>
                    </div>
                    <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-yellow-600"><?php echo $closedCount; ?></div>
                        <div class="text-sm text-yellow-700 font-medium">Closed</div>
                    </div>
                    <div class="bg-purple-50 border border-purple-300 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-purple-600"><?php echo $totalSubmissions; ?></div>
                        <div class="text-sm text-purple-700 font-medium">Total Submissions</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
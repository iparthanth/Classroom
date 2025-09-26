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
            $db->executeQuery(
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
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-xl shadow-lg p-6 mb-6 text-center">
            <h1 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($course['title']); ?></h1>
            <p class="text-blue-100"><?php echo htmlspecialchars($course['description']); ?></p>
            <p class="text-blue-200 text-sm mt-2"><?php echo htmlspecialchars($course['course_code']); ?></p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Course Assignments</h2>
                        <button onclick="showCreateAssignment()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 font-medium">
                            ➕ Create New Assignment
                        </button>
                    </div>
                    
                    <?php if(empty($assignments)): ?>
                        <div class="text-center py-12">
                            <div class="text-gray-400 text-6xl mb-4">📋</div>
                            <h3 class="text-xl font-semibold text-gray-700 mb-2">No assignments created yet</h3>
                            <p class="text-gray-500 mb-4">Create your first assignment to get started.</p>
                            <button onclick="showCreateAssignment()" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 font-medium">
                                Create Your First Assignment
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach($assignments as $assignment): ?>
                                <div class="border border-gray-300 rounded-lg p-5 hover:bg-blue-50 transition-colors">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h3 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                            <div class="flex items-center space-x-2 mt-1">
                                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                                                    <?php echo $assignment['max_points']; ?> points
                                                </span>
                                                <?php if($assignment['file_required']): ?>
                                                    <span class="bg-teal-100 text-teal-800 px-2 py-1 rounded-full text-xs font-medium">
                                                        File Required
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if($assignment['description']): ?>
                                        <p class="text-gray-600 text-sm mb-3"><?php echo htmlspecialchars($assignment['description']); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="flex justify-between items-center text-sm text-gray-500 mb-3">
                                        <span><strong>Due:</strong> <?php echo formatDateTime($assignment['due_date']); ?></span>
                                        <span><?php echo $assignment['submission_count']; ?> / <?php echo $assignment['total_students']; ?> submitted</span>
                                    </div>
                                    
                                    <a href="grade-assignment.php?id=<?php echo $assignment['id']; ?>" 
                                       class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-medium inline-block">
                                        View Submissions
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Course Stats</h2>
                    
                    <div class="space-y-4">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-blue-600"><?php echo count($assignments); ?></div>
                            <div class="text-sm text-blue-700 font-medium">Total Assignments</div>
                        </div>
                        
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-green-600"><?php echo array_sum(array_column($assignments, 'submission_count')); ?></div>
                            <div class="text-sm text-green-700 font-medium">Total Submissions</div>
                        </div>
                        
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-purple-600"><?php echo !empty($assignments) ? $assignments[0]['total_students'] : 0; ?></div>
                            <div class="text-sm text-purple-700 font-medium">Students Enrolled</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Assignment Modal -->
    <div id="createAssignmentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800">Create New Assignment</h2>
                <button onclick="hideCreateAssignment()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="create_assignment">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Assignment Title</label>
                        <input type="text" name="title" placeholder="e.g., Programming Quiz 1" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" placeholder="Assignment instructions and details..." 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 h-24"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                            <input type="datetime-local" name="due_date" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Max Points</label>
                            <input type="number" name="max_points" value="100" min="1" max="1000" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="file_required" id="file_required" 
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="file_required" class="ml-2 block text-sm text-gray-700">
                            Require file upload from students
                        </label>
                    </div>
                    
                    <div class="flex space-x-3 pt-2">
                        <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-medium">
                            Create Assignment
                        </button>
                        <button type="button" onclick="hideCreateAssignment()" 
                                class="flex-1 bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 font-medium">
                            Cancel
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showCreateAssignment() {
            document.getElementById('createAssignmentModal').classList.remove('hidden');
        }
        
        function hideCreateAssignment() {
            document.getElementById('createAssignmentModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('createAssignmentModal').addEventListener('click', function(event) {
            if (event.target === this) {
                hideCreateAssignment();
            }
        });
    </script>
</body>
</html>
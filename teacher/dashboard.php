<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireRole('teacher');
$user = $auth->getCurrentUser();

// Handle course creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'create_course') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $course_code = sanitizeInput($_POST['course_code'] ?? '');
    $max_students = (int)($_POST['max_students'] ?? 50);
    
    if (!empty($title) && !empty($course_code)) {
        try {
            $db->executeQuery(
                "INSERT INTO courses (title, description, course_code, teacher_id, max_students) VALUES (?, ?, ?, ?, ?)",
                [$title, $description, $course_code, $user['id'], $max_students]
            );
            setFlash('success', 'Course created successfully!');
        } catch (Exception $e) {
            setFlash('error', 'Failed to create course: ' . $e->getMessage());
        }
    } else {
        setFlash('error', 'Title and course code are required');
    }
    redirect('/teacher/dashboard.php');
}

// Get teacher's courses
$courses = $db->fetchAll(
    "SELECT c.*, 
     (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as student_count,
     (SELECT COUNT(*) FROM assignments WHERE course_id = c.id) as assignment_count
     FROM courses c 
     WHERE c.teacher_id = ? 
     ORDER BY c.created_at DESC",
    [$user['id']]
);

// Get recent assignments
$assignments = $db->fetchAll(
    "SELECT a.*, c.title as course_title,
     (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id) as submission_count
     FROM assignments a 
     JOIN courses c ON a.course_id = c.id 
     WHERE c.teacher_id = ?
     ORDER BY a.created_at DESC
     LIMIT 10",
    [$user['id']]
);

$flash = getFlash();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - E-Learning System</title>
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
                    <span class="text-gray-700">Hello, <?php echo htmlspecialchars($user['full_name']); ?></span>
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

        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-xl shadow-lg p-6 mb-6 text-center">
            <h1 class="text-2xl font-bold mb-2">Welcome, Professor <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
            <p class="text-blue-100">Ready to inspire and educate your students?</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- My Courses -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-800">My Courses</h2>
                        <button onclick="showCreateCourse()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 font-medium">
                            Create Course
                        </button>
                    </div>
                    
                    <?php if(empty($courses)): ?>
                        <div class="text-center py-8">
                            <div class="text-gray-400 text-6xl mb-4">📚</div>
                            <p class="text-gray-500 mb-4">You haven't created any courses yet.</p>
                            <button onclick="showCreateCourse()" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 font-medium">
                                Create Your First Course
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach($courses as $course): ?>
                                <div class="border border-gray-300 rounded-lg p-5 hover:bg-blue-50 transition-colors">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($course['title']); ?></h3>
                                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($course['course_code']); ?></p>
                                            <p class="text-sm text-gray-600 mt-2"><?php echo truncate($course['description'], 100); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <div class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                                                <?php echo $course['student_count']; ?> students
                                            </div>
                                            <div class="text-sm text-gray-600 mt-1"><?php echo $course['assignment_count']; ?> assignments</div>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-2 mt-4">
                                        <a href="course.php?id=<?php echo $course['id']; ?>" 
                                           class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 font-medium">
                                            Manage Course
                                        </a>
                                        <a href="../whiteboard.php?course_id=<?php echo $course['id']; ?>" 
                                           class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 font-medium">
                                            Whiteboard
                                        </a>
                                        <a href="../chat.php?course_id=<?php echo $course['id']; ?>" 
                                           class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700 font-medium">
                                            Chat
                                        </a>
                                        <a href="assignments.php?course_id=<?php echo $course['id']; ?>" 
                                           class="bg-purple-600 text-white px-3 py-1 rounded text-sm hover:bg-purple-700 font-medium">
                                            Assignments
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Assignments -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Assignments</h2>
                    
                    <?php if(empty($assignments)): ?>
                        <div class="text-center py-8">
                            <div class="text-gray-400 text-6xl mb-4">📋</div>
                            <p class="text-gray-500">No assignments created yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach($assignments as $assignment): ?>
                                <div class="border border-gray-300 rounded-lg p-4 hover:bg-blue-50 transition-colors">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($assignment['course_title']); ?></p>
                                            <p class="text-xs text-gray-500 mt-1">Due: <?php echo formatDateTime($assignment['due_date']); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm text-gray-600 mb-2"><?php echo $assignment['submission_count']; ?> submissions</div>
                                            <a href="grade-assignment.php?id=<?php echo $assignment['id']; ?>" 
                                               class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 font-medium">
                                                Grade
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Quick Stats -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Quick Stats</h2>
                    
                    <div class="space-y-4">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-blue-600"><?php echo count($courses); ?></div>
                            <div class="text-sm text-blue-700 font-medium">Total Courses</div>
                        </div>
                        
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-green-600"><?php echo array_sum(array_column($courses, 'student_count')); ?></div>
                            <div class="text-sm text-green-700 font-medium">Total Students</div>
                        </div>
                        
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-yellow-600"><?php echo count($assignments); ?></div>
                            <div class="text-sm text-yellow-700 font-medium">Assignments Created</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Quick Actions</h2>
                    
                    <div class="space-y-3">
                        <a href="all-assignments.php" 
                           class="block bg-blue-600 text-white px-4 py-3 rounded-lg hover:bg-blue-700 font-medium text-center">
                            All Assignments
                        </a>
                        <a href="profile.php" 
                           class="block bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 font-medium text-center">
                            Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Course Modal -->
    <div id="createCourseModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800">Create New Course</h2>
                <button onclick="hideCreateCourse()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="create_course">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Course Title</label>
                        <input type="text" name="title" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Course Code</label>
                        <input type="text" name="course_code" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 h-24"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Maximum Students</label>
                        <input type="number" name="max_students" value="50" min="1" max="500" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="flex space-x-3 pt-2">
                        <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-medium">
                            Create Course
                        </button>
                        <button type="button" onclick="hideCreateCourse()" 
                                class="flex-1 bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 font-medium">
                            Cancel
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showCreateCourse() {
            document.getElementById('createCourseModal').classList.remove('hidden');
        }
        
        function hideCreateCourse() {
            document.getElementById('createCourseModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('createCourseModal').addEventListener('click', function(event) {
            if (event.target === this) {
                hideCreateCourse();
            }
        });
    </script>
</body>
</html>
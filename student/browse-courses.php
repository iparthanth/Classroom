<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireRole('student');
$user = $auth->getCurrentUser();

// Handle course enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'enroll') {
    $course_id = (int)($_POST['course_id'] ?? 0);
    
    // Check if already enrolled
    $existing = $db->fetchOne(
        "SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?",
        [$user['id'], $course_id]
    );
    
    if (!$existing) {
        try {
            $db->executeQuery(
                "INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)",
                [$user['id'], $course_id]
            );
            setFlash('success', 'Successfully enrolled in the course!');
        } catch (Exception $e) {
            setFlash('error', 'Failed to enroll: ' . $e->getMessage());
        }
    } else {
        setFlash('info', 'You are already enrolled in this course');
    }
    
    redirect('/student/browse-courses.php');
}

// Get available courses (not enrolled yet)
$available_courses = $db->fetchAll(
    "SELECT c.*, u.full_name as teacher_name,
     (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as student_count,
     CASE 
        WHEN e.id IS NOT NULL THEN 1 
        ELSE 0 
     END as is_enrolled
     FROM courses c 
     JOIN users u ON c.teacher_id = u.id 
     LEFT JOIN enrollments e ON c.id = e.course_id AND e.student_id = ?
     WHERE c.is_active = 1 
     ORDER BY c.created_at DESC",
    [$user['id']]
);

$flash = getFlash();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Courses - E-Learning System</title>
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
                    <h1 class="text-xl font-bold text-gray-800">Browse Courses</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-600 hover:text-gray-800">Back to Dashboard</a>
                    <a href="../logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Flash Messages -->
        <?php if($flash): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-100 text-green-700' : ($flash['type'] === 'info' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700'); ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <!-- Page Title -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Available Courses</h2>
            <p class="text-gray-600">Discover and enroll in courses to start your learning journey</p>
        </div>

        <!-- Courses Grid -->
        <?php if(empty($available_courses)): ?>
            <div class="bg-white rounded-xl shadow p-8 text-center">
                <div class="text-gray-400 text-6xl mb-4">📚</div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No Courses Available</h3>
                <p class="text-gray-600">There are no courses available at the moment. Please check back later.</p>
            </div>
        <?php else: ?>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <?php foreach($available_courses as $course): ?>
                    <div class="bg-white rounded-xl shadow hover:shadow-lg transition-shadow p-6">
                        <div class="mb-4">
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                <?php echo htmlspecialchars($course['title']); ?>
                            </h3>
                            <p class="text-sm text-gray-600 font-medium">
                                <?php echo htmlspecialchars($course['course_code']); ?>
                            </p>
                        </div>
                        
                        <p class="text-gray-700 mb-4">
                            <?php echo htmlspecialchars(truncate($course['description'] ?: 'No description available', 100)); ?>
                        </p>
                        
                        <div class="flex items-center justify-between text-sm text-gray-600 mb-4">
                            <div>
                                <span class="font-medium">Teacher:</span>
                                <?php echo htmlspecialchars($course['teacher_name']); ?>
                            </div>
                            <div class="text-right">
                                <div><?php echo $course['student_count']; ?> students</div>
                                <div class="text-xs">Max: <?php echo $course['max_students']; ?></div>
                            </div>
                        </div>
                        
                        <div class="border-t pt-4">
                            <?php if($course['is_enrolled']): ?>
                                <div class="flex items-center justify-between">
                                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                                        ✓ Enrolled
                                    </span>
                                    <a href="course.php?id=<?php echo $course['id']; ?>" 
                                       class="bg-primary text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
                                        Enter Course
                                    </a>
                                </div>
                            <?php elseif($course['student_count'] >= $course['max_students']): ?>
                                <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium w-full text-center block">
                                    Course Full
                                </span>
                            <?php else: ?>
                                <form method="POST" class="w-full">
                                    <input type="hidden" name="action" value="enroll">
                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                    <button type="submit" 
                                            class="w-full bg-secondary text-white py-2 rounded-lg hover:bg-green-700 transition duration-300">
                                        Enroll Now
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

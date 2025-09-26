<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireRole('student');
$user = $auth->getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'enroll') {
    $courseId = (int)($_POST['course_id'] ?? 0);
    $exists = $db->fetchOne(
        "SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?",
        [$user['id'], $courseId]
    );

    if (!$exists) {
        try {
            $db->executeQuery(
                "INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)",
                [$user['id'], $courseId]
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

$available_courses = $db->fetchAll(
    "SELECT c.*, u.full_name as teacher_name,
         (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as student_count,
         CASE WHEN e.id IS NOT NULL THEN 1 ELSE 0 END as is_enrolled
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
    <meta charset="UTF-8" />
    <title>Browse Courses</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Simple Header -->
    <header class="bg-white border-b">
        <div class="max-w-6xl mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-semibold text-gray-800">Browse Courses</h1>
                    <p class="text-sm text-gray-600">Hello, <?=htmlspecialchars($user['full_name'])?></p>
                </div>
                <div class="space-x-4">
                    <a href="dashboard.php" class="text-gray-600 hover:text-gray-800 text-sm font-medium">Dashboard</a>
                    <a href="../logout.php" class="text-red-600 hover:text-red-800 text-sm font-medium">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-6xl mx-auto px-4 py-6">
        <?php if ($flash): ?>
            <div class="mb-6 p-3 rounded text-sm <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-700' : 
                  ($flash['type'] === 'info' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700') ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <div class="mb-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">Available Courses</h2>
            <p class="text-gray-600">Discover and enroll in courses to start your learning journey</p>
        </div>

        <?php if (empty($available_courses)): ?>
            <div class="bg-white rounded-lg border p-8 text-center">
                <div class="text-gray-400 text-4xl mb-3">📚</div>
                <h3 class="text-lg font-medium text-gray-800 mb-2">No Courses Available</h3>
                <p class="text-gray-600 text-sm">There are no courses available at the moment. Please check back later.</p>
            </div>
        <?php else: ?>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($available_courses as $course): ?>
                    <div class="bg-white rounded-lg border p-5">
                        <div class="mb-4">
                            <h3 class="text-lg font-medium text-gray-800 mb-1"><?= htmlspecialchars($course['title']) ?></h3>
                            <p class="text-sm text-gray-600"><?= htmlspecialchars($course['course_code']) ?></p>
                        </div>
                        
                        <p class="text-gray-600 text-sm mb-4"><?= htmlspecialchars(truncate($course['description'] ?: 'No description available', 100)) ?></p>
                        
                        <div class="text-sm text-gray-600 mb-4 space-y-1">
                            <div class="flex justify-between">
                                <span>Teacher:</span>
                                <span class="font-medium"><?= htmlspecialchars($course['teacher_name']) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Students:</span>
                                <span><?= $course['student_count'] ?> / <?= $course['max_students'] ?? '∞' ?></span>
                            </div>
                        </div>
                        
                        <div class="border-t pt-4">
                            <?php if ($course['is_enrolled']): ?>
                                <div class="flex justify-between items-center">
                                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-medium">Enrolled</span>
                                    <a href="course.php?id=<?= $course['id'] ?>" 
                                       class="bg-blue-600 text-white px-3 py-1 rounded text-sm font-medium hover:bg-blue-700">
                                        Enter Course
                                    </a>
                                </div>
                            <?php elseif (isset($course['max_students']) && $course['student_count'] >= $course['max_students']): ?>
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-medium block text-center">
                                    Course Full
                                </span>
                            <?php else: ?>
                                <form method="POST" class="w-full">
                                    <input type="hidden" name="action" value="enroll" />
                                    <input type="hidden" name="course_id" value="<?= $course['id'] ?>" />
                                    <button type="submit" class="w-full bg-green-600 text-white py-2 rounded text-sm font-medium hover:bg-green-700">
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
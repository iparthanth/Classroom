<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireRole('student');
$user = $auth->getCurrentUser();

$courses = $db->fetchAll("
    SELECT c.*, u.full_name AS teacher_name
      FROM courses c
      JOIN enrollments e ON c.id = e.course_id
      JOIN users u ON c.teacher_id = u.id
     WHERE e.student_id = ? AND c.is_active = 1
", [$user['id']]);

$assignments = $db->fetchAll("
    SELECT a.*, c.title AS course_title, s.submitted_at, s.points_awarded
      FROM assignments a
      JOIN courses c ON a.course_id = c.id
      JOIN enrollments e ON c.id = e.course_id
 LEFT JOIN submissions s ON a.id = s.assignment_id AND s.student_id = e.student_id
     WHERE e.student_id = ?
  ORDER BY a.due_date ASC
  LIMIT 10
", [$user['id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Student Dashboard</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
       
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Simple Header -->
    <header class="bg-white border-b">
        <div class="max-w-6xl mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-semibold text-gray-800">Student Dashboard</h1>
                    <p class="text-sm text-gray-600">Hello, <?=htmlspecialchars($user['full_name'])?></p>
                </div>
                <a href="../logout.php" class="text-red-600 hover:text-red-800 text-sm font-medium">Logout</a>
            </div>
        </div>
    </header>

    <div class="max-w-6xl mx-auto px-4 py-6">
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg p-5 mb-6 text-center">
            <h2 class="text-xl font-semibold mb-2">Welcome back, <?=htmlspecialchars($user['full_name'])?>!</h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- My Courses -->
                <div class="bg-white rounded-lg border p-5">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-800">My Courses</h3>
                        <a href="browse-courses.php" class="text-green-600 hover:text-green-800 text-sm font-medium">Browse More</a>
                    </div>
                    
                    <?php if (empty($courses)): ?>
                        <div class="text-center py-8">
                            <div class="text-gray-400 text-4xl mb-3">📚</div>
                            <p class="text-gray-600 text-sm mb-4">You're not enrolled in any courses yet.</p>
                            <a href="browse-courses.php" class="bg-green-600 text-white px-4 py-2 rounded text-sm font-medium hover:bg-green-700">Find Courses</a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($courses as $course): ?>
                                <div class="border rounded p-4">
                                    <h4 class="font-medium text-gray-800"><?=htmlspecialchars($course['title'])?></h4>
                                    <p class="text-gray-600 text-sm"><?=htmlspecialchars($course['course_code'])?></p>
                                    <p class="text-gray-600 text-sm">Teacher: <?=htmlspecialchars($course['teacher_name'])?></p>
                                    <div class="flex space-x-2 mt-3">
                                        <a href="course.php?id=<?=$course['id']?>" class="bg-blue-600 text-white px-3 py-1 rounded text-sm font-medium hover:bg-blue-700">Enter Course</a>
                                        
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Assignments -->
                <div class="bg-white rounded-lg border p-5">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Recent Assignments</h3>
                    
                    <?php if (empty($assignments)): ?>
                        <div class="text-center py-8">
                            <div class="text-gray-400 text-4xl mb-3">📋</div>
                            <p class="text-gray-600 text-sm">No assignments yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($assignments as $assignment): ?>
                                <div class="border rounded p-4">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-medium text-gray-800"><?=htmlspecialchars($assignment['title'])?></h4>
                                            <p class="text-gray-600 text-sm"><?=htmlspecialchars($assignment['course_title'])?></p>
                                            <p class="text-gray-600 text-xs mt-1">Due: <?=formatDateTime($assignment['due_date'])?></p>
                                        </div>
                                        <div class="text-right">
                                            <?php if ($assignment['submitted_at']): ?>
                                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-medium">Submitted</span>
                                                <?php if ($assignment['points_awarded']): ?>
                                                    <div class="text-blue-600 text-sm mt-1">Score: <?=$assignment['points_awarded']?>%</div>
                                                <?php endif; ?>
                                            <?php elseif (strtotime($assignment['due_date']) < time()): ?>
                                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-medium">Overdue</span>
                                            <?php else: ?>
                                                <a href="assignment.php?id=<?=$assignment['id']?>" class="bg-green-600 text-white px-3 py-1 rounded text-sm font-medium hover:bg-green-700">Submit</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Stats -->
                <div class="bg-white rounded-lg border p-5">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Quick Stats</h3>
                    <div class="space-y-3 text-sm text-gray-700">
                        <div class="flex justify-between">
                            <span>Enrolled Courses</span>
                            <span class="font-medium"><?=count($courses)?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Pending Assignments</span>
                            <span class="font-medium text-blue-600"><?=count(array_filter($assignments, fn($a)=>!$a['submitted_at'] && strtotime($a['due_date'])>time()))?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Completed</span>
                            <span class="font-medium text-green-600"><?=count(array_filter($assignments, fn($a)=>$a['submitted_at']))?></span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-lg border p-5">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="browse-courses.php" class="block w-full bg-blue-600 text-white py-2 rounded text-sm font-medium hover:bg-blue-700 text-center">Browse Courses</a>                       
                        <a href="my-submissions.php" class="block w-full bg-green-600 text-white py-2 rounded text-sm font-medium hover:bg-green-700 text-center">My Submissions</a>
                        <a href="profile.php" class="block w-full bg-gray-600 text-white py-2 rounded text-sm font-medium hover:bg-gray-700 text-center">Edit Profile</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
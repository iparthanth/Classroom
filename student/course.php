<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireRole('student');
$user = $auth->getCurrentUser();
$courseId = (int)($_GET['id'] ?? 0);

$enrollment = $db->fetchOne("
    SELECT c.*, u.full_name AS teacher_name, e.enrolled_at
      FROM courses c
      JOIN enrollments e ON c.id = e.course_id
      JOIN users u ON c.teacher_id = u.id
     WHERE c.id = ? AND e.student_id = ? AND c.is_active = 1
", [$courseId, $user['id']]);

if (!$enrollment) {
    setFlash('error', 'Course not found or you are not enrolled.');
    redirect('/student/dashboard.php');
}

$assignments = $db->fetchAll("
    SELECT a.*, s.submitted_at, s.points_awarded, s.feedback
      FROM assignments a
 LEFT JOIN submissions s ON a.id = s.assignment_id AND s.student_id = ?
     WHERE a.course_id = ?
  ORDER BY a.due_date ASC
", [$user['id'], $courseId]);

$materials = $db->fetchAll("SELECT * FROM course_materials WHERE course_id = ? ORDER BY upload_date DESC", [$courseId]);
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title><?=htmlspecialchars($enrollment['title'])?> - Course</title>
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
                    <h1 class="text-xl font-semibold text-gray-800">Course: <?=htmlspecialchars($enrollment['title'])?></h1>
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
            <div class="mb-6 p-3 rounded text-sm <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <?=htmlspecialchars($flash['message'])?>
            </div>
        <?php endif; ?>

        <!-- Course Header -->
        <div class="bg-white rounded-lg border p-5 mb-6">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-1"><?=htmlspecialchars($enrollment['title'])?></h2>
                    <p class="text-gray-600"><?=htmlspecialchars($enrollment['course_code'])?></p>
                    <p class="text-gray-600 text-sm mt-2"><?=htmlspecialchars($enrollment['description'])?></p>
                    <div class="mt-4 text-sm text-gray-600 space-y-1">
                        <div>Instructor: <?=htmlspecialchars($enrollment['teacher_name'])?></div>
                        <div>Enrolled: <?=formatDateTime($enrollment['enrolled_at'])?></div>
                    </div>
                </div>
                <div class="flex flex-col space-y-2 ml-4">
                    <a href="../whiteboard.php?course_id=<?= $courseId ?>" 
                       class="bg-green-600 text-white px-3 py-2 rounded text-sm font-medium hover:bg-green-700 text-center">Whiteboard</a>
                    <a href="../chat.php?course_id=<?= $courseId ?>" 
                       class="bg-gray-600 text-white px-3 py-2 rounded text-sm font-medium hover:bg-gray-700 text-center">Chat</a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Assignments -->
                <div class="bg-white rounded-lg border p-5">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Course Assignments</h3>
                    <?php if (empty($assignments)): ?>
                        <div class="text-center py-8">
                            <div class="text-gray-400 text-4xl mb-3">📋</div>
                            <p class="text-gray-600 text-sm">No assignments posted yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                        <?php foreach ($assignments as $a): 
                            $due_passed = strtotime($a['due_date']) < time();
                        ?>
                            <div class="border rounded p-4">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-800"><?=htmlspecialchars($a['title'])?></h4>
                                        <?php if ($a['description']): ?>
                                            <p class="text-gray-600 text-sm mt-1"><?=truncate($a['description'],150)?></p>
                                        <?php endif; ?>
                                        <div class="mt-3 text-sm text-gray-600 space-y-1">
                                            <div>Due: <?=formatDateTime($a['due_date'])?></div>
                                            <div>Points: <?=$a['max_points']?></div>
                                            <?php if ($a['file_required']): ?>
                                                <div class="text-blue-600">File Required</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="ml-4 text-right">
                                        <?php if ($a['submitted_at']): ?>
                                            <div class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-medium mb-2">Submitted</div>
                                            <div class="text-xs text-gray-500"><?=formatDateTime($a['submitted_at'])?></div>
                                            <?php if ($a['points_awarded'] !== null): ?>
                                                <div class="text-blue-600 font-medium text-sm mt-1">Grade: <?=$a['points_awarded']?>/<?=$a['max_points']?></div>
                                            <?php endif; ?>
                                        <?php elseif($due_passed): ?>
                                            <div class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-medium">Overdue</div>
                                        <?php else: ?>
                                            <div class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-medium mb-2">Pending</div>
                                            <a href="assignment.php?id=<?=$a['id']?>" class="bg-blue-600 text-white px-3 py-1 rounded text-sm font-medium hover:bg-blue-700">Submit</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($a['feedback']): ?>
                                    <div class="mt-4 bg-blue-50 border-l-4 border-blue-400 p-3 rounded-r">
                                        <h5 class="text-sm font-medium text-blue-800">Teacher Feedback:</h5>
                                        <p class="text-blue-700 text-sm mt-1"><?=nl2br(htmlspecialchars($a['feedback']))?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Course Materials -->
                <?php if (!empty($materials)): ?>
                <div class="bg-white rounded-lg border p-5">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Course Materials</h3>
                    <div class="space-y-3">
                        <?php foreach ($materials as $m): ?>
                        <div class="flex justify-between items-center p-3 border rounded">
                            <div>
                                <h4 class="font-medium text-sm"><?=htmlspecialchars($m['title'])?></h4>
                                <?php if ($m['description']): ?>
                                    <p class="text-gray-600 text-xs"><?=htmlspecialchars($m['description'])?></p>
                                <?php endif; ?>
                                <p class="text-gray-500 text-xs">Uploaded: <?=formatDateTime($m['upload_date'])?></p>
                            </div>
                            <div>
                                <?php if ($m['file_path']): ?>
                                    <a href="../<?=htmlspecialchars($m['file_path'])?>" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View</a>
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
                <!-- Progress -->
                <div class="bg-white rounded-lg border p-5">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Assignment Progress</h3>
                    <div class="space-y-3 text-sm text-gray-700">
                        <div class="flex justify-between">
                            <span>Total Assignments</span>
                            <span class="font-medium"><?=count($assignments)?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Submitted</span>
                            <span class="font-medium text-green-600"><?=count(array_filter($assignments, fn($a)=>$a['submitted_at']))?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Pending</span>
                            <span class="font-medium text-blue-600"><?=count(array_filter($assignments, fn($a)=>!$a['submitted_at'] && strtotime($a['due_date'])>time()))?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Overdue</span>
                            <span class="font-medium text-red-600"><?=count(array_filter($assignments, fn($a)=>!$a['submitted_at'] && strtotime($a['due_date'])<time()))?></span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-lg border p-5">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="../whiteboard.php?course_id=<?=$courseId?>" class="block w-full bg-green-600 text-white py-2 rounded text-sm font-medium hover:bg-green-700 text-center">Whiteboard</a>
                        <a href="../chat.php?course_id=<?=$courseId?>" class="block w-full bg-gray-600 text-white py-2 rounded text-sm font-medium hover:bg-gray-700 text-center">Chat</a>
                        <a href="my-submissions.php" class="block w-full bg-blue-600 text-white py-2 rounded text-sm font-medium hover:bg-blue-700 text-center">My Submissions</a>
                        <a href="dashboard.php" class="block w-full bg-gray-200 text-gray-700 py-2 rounded text-sm font-medium hover:bg-gray-300 text-center">Dashboard</a>
                    </div>
                </div>

                <!-- Course Info -->
                <div class="bg-white rounded-lg border p-5">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Course Information</h3>
                    <div class="space-y-2 text-sm text-gray-700">
                        <div class="flex justify-between">
                            <span>Code:</span>
                            <span class="font-medium"><?=htmlspecialchars($enrollment['course_code'])?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Instructor:</span>
                            <span class="font-medium"><?=htmlspecialchars($enrollment['teacher_name'])?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Enrolled:</span>
                            <span class="font-medium"><?=date('M j, Y', strtotime($enrollment['enrolled_at']))?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
$auth->requireRole('student');
$user = $auth->getCurrentUser();

$submissions = $db->fetchAll(
    "SELECT s.*, a.title as assignment_title, a.max_points, c.title as course_title, c.course_code,
            u.full_name as graded_by_name
     FROM submissions s 
     JOIN assignments a ON s.assignment_id = a.id 
     JOIN courses c ON a.course_id = c.id 
     LEFT JOIN users u ON s.graded_by = u.id
     WHERE s.student_id = ?
     ORDER BY s.submitted_at DESC",
    [$user['id']]
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submissions - E-Learning System</title>
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

    <!-- Main Content Area -->
    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Card Container styled like search form in Railway System -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">My Assignment Submissions</h2>
            
            <?php if(empty($submissions)): ?>
                <div class="text-center py-12">
                    <div class="text-gray-400 text-6xl mb-4">📋</div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No submissions yet</h3>
                    <p class="text-gray-500 mb-4">You haven't submitted any assignments yet.</p>
                    <a href="browse-courses.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-medium">
                        Browse Courses
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach($submissions as $submission): ?>
                        <!-- Submission Card styled like train search result -->
                        <div class="border border-gray-300 rounded-lg p-5 hover:bg-blue-50 transition-colors">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($submission['assignment_title']); ?></h3>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($submission['course_title']); ?> (<?php echo htmlspecialchars($submission['course_code']); ?>)</p>
                                </div>
                                <div>
                                    <?php if($submission['points_awarded'] !== null): ?>
                                        <div class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-bold">
                                            <?php echo $submission['points_awarded']; ?>/<?php echo $submission['max_points']; ?> points
                                        </div>
                                    <?php else: ?>
                                        <div class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-bold">
                                            Pending Grade
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <h4 class="font-semibold text-gray-700 mb-2">Submission Details</h4>
                                    <p class="text-sm text-gray-600"><strong>Submitted:</strong> <?php echo formatDateTime($submission['submitted_at']); ?></p>
                                    <?php if($submission['graded_at']): ?>
                                        <p class="text-sm text-gray-600"><strong>Graded:</strong> <?php echo formatDateTime($submission['graded_at']); ?>
                                        <?php if($submission['graded_by_name']): ?> by <?php echo htmlspecialchars($submission['graded_by_name']); ?><?php endif; ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if($submission['file_path']): ?>
                                    <div>
                                        <h4 class="font-semibold text-gray-700 mb-2">File Submission</h4>
                                        <a href="../download.php?id=<?php echo $submission['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium" target="_blank">
                                            📎 View Submitted File
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if($submission['submission_text']): ?>
                                <div class="mb-4">
                                    <h4 class="font-semibold text-gray-700 mb-2">Text Submission</h4>
                                    <div class="bg-gray-50 p-3 rounded border text-sm">
                                        <?php echo nl2br(htmlspecialchars($submission['submission_text'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if($submission['feedback']): ?>
                                <div>
                                    <h4 class="font-semibold text-gray-700 mb-2">Teacher Feedback</h4>
                                    <div class="bg-blue-50 border-l-4 border-blue-500 p-3 rounded-r text-sm">
                                        <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Summary Stats -->
                <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php
                    $graded = array_filter($submissions, function($s) { return $s['points_awarded'] !== null; });
                    $average = count($graded) > 0 ? round(array_sum(array_map(function($s) { return $s['points_awarded']; }, $graded)) / count($graded), 1) : 'N/A';
                    ?>
                    <div class="bg-blue-50 border border-blue-300 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-blue-600"><?php echo count($submissions); ?></div>
                        <div class="text-sm text-blue-700 font-medium">Total Submissions</div>
                    </div>
                    <div class="bg-green-50 border border-green-300 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-green-600"><?php echo count($graded); ?></div>
                        <div class="text-sm text-green-700 font-medium">Graded</div>
                    </div>
                    <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-yellow-600"><?php echo $average; ?></div>
                        <div class="text-sm text-yellow-700 font-medium">Average Score</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
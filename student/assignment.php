<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireRole('student');
$user         = $auth->getCurrentUser();
$assignmentId = (int)($_GET['id'] ?? 0);

// Fetch assignment if enrolled
$assignment = $db->fetchOne("
    SELECT a.*, c.title AS course_title, c.course_code, u.full_name AS teacher_name
      FROM assignments a
      JOIN courses c ON a.course_id = c.id
      JOIN users u   ON c.teacher_id = u.id
      JOIN enrollments e ON c.id = e.course_id
     WHERE a.id = ? AND e.student_id = ?
", [$assignmentId, $user['id']]);

if (!$assignment) redirect('dashboard.php');

// Existing submission?
$submission = $db->fetchOne(
    "SELECT * FROM submissions WHERE assignment_id=? AND student_id=?",
    [$assignmentId, $user['id']]
);

$isOverdue = strtotime($assignment['due_date']) < time();
$flash     = getFlash();

// Handle submission POST
if ($_SERVER['REQUEST_METHOD']==='POST' && $_POST['action']==='submit' && !$submission) {
    $text = sanitizeInput($_POST['submission_text'] ?? '');
    $file = null;

    if ($assignment['file_required'] && !empty($_FILES['submission_file']['tmp_name'])) {
        $ext   = strtolower(pathinfo($_FILES['submission_file']['name'], PATHINFO_EXTENSION));
        $allow = ['pdf','doc','docx','txt','jpg','jpeg','png'];
        if (in_array($ext, $allow)) {
            $name = "asg_{$assignmentId}_stu_{$user['id']}_" . time() . ".$ext";
            if (move_uploaded_file($_FILES['submission_file']['tmp_name'], "../uploads/$name")) {
                $file = $name;
            } else {
                setFlash('error','File upload failed');
            }
        } else {
            setFlash('error','Invalid file type');
        }
    }

    if ($text || (!$assignment['file_required']) || ($assignment['file_required'] && $file)) {
        try {
            $db->executeQuery(
                "INSERT INTO submissions (assignment_id,student_id,submission_text,file_path)
                 VALUES (?,?,?,?)",
                [$assignmentId, $user['id'], $text, $file]
            );
            setFlash('success','Submitted successfully');
            redirect("assignment.php?id=$assignmentId");
        } catch (Exception $e) {
            setFlash('error','Submission failed');
        }
    } else {
        setFlash('error','Please provide content');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?=htmlspecialchars($assignment['title'])?> – Assignment</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Simple Header -->
    <header class="bg-white border-b">
        <div class="max-w-4xl mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-semibold text-gray-800">Assignment Submission</h1>
                    <p class="text-sm text-gray-600">Hello, <?=htmlspecialchars($user['full_name'])?></p>
                </div>
                <a href="../logout.php" class="text-red-600 hover:text-red-800 text-sm font-medium">Logout</a>
            </div>
        </div>
    </header>

    <div class="max-w-4xl mx-auto px-4 py-6">
        <!-- Flash Messages -->
        <?php if($flash): ?>
            <div class="mb-6 p-3 rounded <?= $flash['type']==='success'?'bg-green-100 text-green-700':'bg-red-100 text-red-700' ?> text-sm">
                <?=htmlspecialchars($flash['message'])?>
            </div>
        <?php endif; ?>

        <!-- Assignment Card -->
        <div class="bg-white rounded-lg border p-5 mb-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-800 mb-1"><?=htmlspecialchars($assignment['title'])?></h2>
                    <p class="text-gray-600"><?=htmlspecialchars($assignment['course_title'])?> (<?=htmlspecialchars($assignment['course_code'])?>)</p>
                    <p class="text-sm text-gray-500 mt-1">Teacher: <?=htmlspecialchars($assignment['teacher_name'])?></p>
                </div>
                <div class="text-right">
                    <div class="text-lg font-medium text-gray-800"><?= $assignment['max_points'] ?> points</div>
                    <div class="text-sm <?= $isOverdue?'text-red-600':'text-gray-600' ?>">
                        Due <?= formatDateTime($assignment['due_date']) ?>
                    </div>
                    <?php if($isOverdue): ?>
                        <span class="inline-block mt-1 bg-red-100 text-red-700 text-xs font-medium px-2 py-1 rounded">Overdue</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="border-t pt-4">
                <h3 class="font-medium text-gray-700 mb-2">Instructions</h3>
                <p class="text-gray-600"><?= htmlspecialchars($assignment['description'] ?: 'No instructions provided.') ?></p>
                <?php if($assignment['file_required']): ?>
                    <div class="mt-3 text-sm text-blue-600">📎 File upload required</div>
                <?php endif; ?>
            </div>
        </div>

        <?php if($submission): ?>
            <!-- Submission Status -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-5 mb-6">
                <div class="flex items-center mb-3">
                    <span class="text-green-600 text-xl mr-2">✓</span>
                    <h3 class="text-lg font-medium text-green-800">Submission Complete</h3>
                </div>
                <p class="text-sm text-gray-600 mb-3">Submitted: <?= formatDateTime($submission['submitted_at']) ?></p>
                
                <?php if($submission['submission_text']): ?>
                    <div class="bg-white p-3 rounded border text-sm mb-3">
                        <p class="text-gray-700"><?= htmlspecialchars($submission['submission_text']) ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if($submission['file_path']): ?>
                    <p class="text-sm text-gray-600 mb-3">File: <?= htmlspecialchars($submission['file_path']) ?></p>
                <?php endif; ?>
                
                <?php if($submission['points_awarded']!==null): ?>
                    <div class="bg-yellow-50 p-3 rounded border">
                        <p class="text-sm font-medium text-gray-700">Grade: <?= $submission['points_awarded'] ?> / <?= $assignment['max_points'] ?></p>
                        <?php if($submission['feedback']): ?>
                            <p class="text-sm text-gray-600 mt-1">Feedback: <?= htmlspecialchars($submission['feedback']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-gray-600 italic">Awaiting grade from instructor</p>
                <?php endif; ?>
            </div>

        <?php elseif($isOverdue): ?>
            <!-- Overdue Message -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-5 text-center">
                <div class="text-red-600 text-3xl mb-3">⏰</div>
                <h3 class="text-lg font-medium text-red-700 mb-2">Assignment Overdue</h3>
                <p class="text-red-600 text-sm">This assignment can no longer be submitted.</p>
            </div>

        <?php else: ?>
            <!-- Submission Form -->
            <div class="bg-white rounded-lg border p-5">
                <h3 class="text-lg font-medium text-gray-800 mb-4">Submit Your Work</h3>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="submit">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Your Response</label>
                        <textarea name="submission_text" required 
                                  class="w-full border rounded p-3 h-32 text-sm" 
                                  placeholder="Type your answer here..."></textarea>
                    </div>

                    <?php if($assignment['file_required']): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Upload File</label>
                            <input type="file" name="submission_file" 
                                   accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png" required 
                                   class="w-full text-sm" />
                            <p class="text-xs text-gray-500 mt-1">Allowed: PDF, DOC, DOCX, TXT, JPG, PNG</p>
                        </div>
                    <?php endif; ?>

                    <div class="flex space-x-3">
                        <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded text-sm font-medium hover:bg-blue-700">
                            Submit Assignment
                        </button>
                        <a href="dashboard.php" class="bg-gray-200 text-gray-700 px-5 py-2 rounded text-sm font-medium hover:bg-gray-300">
                            Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
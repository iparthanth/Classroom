<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireRole('student');
$user = $auth->getCurrentUser();

$assignment_id = (int)($_GET['id'] ?? 0);

// Get assignment details and verify student is enrolled
$assignment = $db->fetchOne(
    "SELECT a.*, c.title as course_title, c.course_code, u.full_name as teacher_name
     FROM assignments a 
     JOIN courses c ON a.course_id = c.id 
     JOIN users u ON c.teacher_id = u.id
     JOIN enrollments e ON c.id = e.course_id
     WHERE a.id = ? AND e.student_id = ?",
    [$assignment_id, $user['id']]
);

if (!$assignment) {
    redirect('/student/dashboard.php');
}

// Check if already submitted
$existing_submission = $db->fetchOne(
    "SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?",
    [$assignment_id, $user['id']]
);

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'submit' && !$existing_submission) {
    $submission_text = sanitizeInput($_POST['submission_text'] ?? '');
    $file_path = null;
    
    // Handle file upload if required
    if ($assignment['file_required'] && isset($_FILES['submission_file'])) {
        $file = $_FILES['submission_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (in_array($file_ext, $allowed_extensions)) {
                $filename = 'assignment_' . $assignment_id . '_student_' . $user['id'] . '_' . time() . '.' . $file_ext;
                $upload_path = '../uploads/' . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $file_path = $filename;
                } else {
                    setFlash('error', 'Failed to upload file');
                }
            } else {
                setFlash('error', 'Invalid file type. Allowed: PDF, DOC, DOCX, TXT, JPG, PNG');
            }
        } else {
            setFlash('error', 'File upload error');
        }
    }
    
    // Submit if we have either text or file (depending on requirements)
    if (!empty($submission_text) || ($assignment['file_required'] && $file_path) || (!$assignment['file_required'])) {
        try {
            $db->executeQuery(
                "INSERT INTO submissions (assignment_id, student_id, submission_text, file_path) VALUES (?, ?, ?, ?)",
                [$assignment_id, $user['id'], $submission_text, $file_path]
            );
            setFlash('success', 'Assignment submitted successfully!');
            redirect('/student/assignment.php?id=' . $assignment_id);
        } catch (Exception $e) {
            setFlash('error', 'Failed to submit assignment: ' . $e->getMessage());
        }
    } else {
        setFlash('error', 'Please provide your submission content');
    }
}

$flash = getFlash();
$is_overdue = strtotime($assignment['due_date']) < time();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($assignment['title']); ?> - Assignment</title>
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
        <div class="max-w-4xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-primary rounded flex items-center justify-center">
                        <span class="text-white font-bold text-xl">📝</span>
                    </div>
                    <h1 class="text-xl font-bold text-gray-800">Assignment</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-600 hover:text-gray-800">Back to Dashboard</a>
                    <a href="../logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Flash Messages -->
        <?php if($flash): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <!-- Assignment Details -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($assignment['title']); ?></h2>
                    <p class="text-gray-600"><?php echo htmlspecialchars($assignment['course_title']); ?> (<?php echo htmlspecialchars($assignment['course_code']); ?>)</p>
                    <p class="text-sm text-gray-500">Professor: <?php echo htmlspecialchars($assignment['teacher_name']); ?></p>
                </div>
                <div class="text-right">
                    <div class="text-lg font-semibold text-gray-900"><?php echo $assignment['max_points']; ?> points</div>
                    <div class="text-sm <?php echo $is_overdue ? 'text-red-600' : 'text-gray-600'; ?>">
                        Due: <?php echo formatDateTime($assignment['due_date']); ?>
                    </div>
                    <?php if($is_overdue): ?>
                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-medium">Overdue</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="border-t pt-4">
                <h3 class="font-semibold mb-2">Instructions:</h3>
                <p class="text-gray-700 whitespace-pre-line"><?php echo htmlspecialchars($assignment['description'] ?: 'No additional instructions provided.'); ?></p>
                
                <?php if($assignment['file_required']): ?>
                    <div class="mt-3 p-3 bg-blue-50 border-l-4 border-blue-400">
                        <p class="text-blue-700 text-sm">📎 This assignment requires a file upload</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if($existing_submission): ?>
            <!-- Existing Submission -->
            <div class="bg-green-50 border border-green-200 rounded-xl p-6">
                <div class="flex items-center mb-4">
                    <span class="text-green-600 text-2xl mr-2">✅</span>
                    <h3 class="text-xl font-semibold text-green-800">Assignment Submitted</h3>
                </div>
                
                <div class="space-y-3 text-green-700">
                    <p><strong>Submitted:</strong> <?php echo formatDateTime($existing_submission['submitted_at']); ?></p>
                    
                    <?php if($existing_submission['submission_text']): ?>
                        <div>
                            <strong>Your Response:</strong>
                            <div class="mt-2 p-3 bg-white border rounded whitespace-pre-line">
                                <?php echo htmlspecialchars($existing_submission['submission_text']); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($existing_submission['file_path']): ?>
                        <p><strong>Uploaded File:</strong> <?php echo htmlspecialchars($existing_submission['file_path']); ?></p>
                    <?php endif; ?>
                    
                    <?php if($existing_submission['points_awarded'] !== null): ?>
                        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                            <p class="text-yellow-800"><strong>Grade:</strong> <?php echo $existing_submission['points_awarded']; ?> / <?php echo $assignment['max_points']; ?> points</p>
                            <?php if($existing_submission['feedback']): ?>
                                <p class="text-yellow-700 mt-2"><strong>Feedback:</strong> <?php echo htmlspecialchars($existing_submission['feedback']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-600"><em>Awaiting grade...</em></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif($is_overdue): ?>
            <!-- Overdue Message -->
            <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
                <div class="text-red-600 text-4xl mb-4">⏰</div>
                <h3 class="text-xl font-semibold text-red-800 mb-2">Assignment Overdue</h3>
                <p class="text-red-700">The due date for this assignment has passed. You can no longer submit.</p>
            </div>
        <?php else: ?>
            <!-- Submission Form -->
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="text-xl font-semibold mb-4">Submit Your Work</h3>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="submit">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Your Response:</label>
                            <textarea 
                                name="submission_text" 
                                class="w-full border rounded-lg p-3 h-32" 
                                placeholder="Enter your answer or response here..."
                                <?php echo !$assignment['file_required'] ? 'required' : ''; ?>
                            ></textarea>
                        </div>
                        
                        <?php if($assignment['file_required']): ?>
                            <div>
                                <label class="block text-sm font-medium mb-2">Upload File (Required):</label>
                                <input 
                                    type="file" 
                                    name="submission_file" 
                                    class="w-full border rounded-lg p-2"
                                    accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png"
                                    required
                                >
                                <p class="text-xs text-gray-500 mt-1">
                                    Accepted formats: PDF, DOC, DOCX, TXT, JPG, PNG (Max 10MB)
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-6 flex space-x-4">
                        <button 
                            type="submit" 
                            class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-medium"
                        >
                            Submit Assignment
                        </button>
                        <a 
                            href="dashboard.php" 
                            class="bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400 font-medium"
                        >
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

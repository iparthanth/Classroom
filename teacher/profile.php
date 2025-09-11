<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require teacher login
$auth->requireRole('teacher');
$user = $auth->getCurrentUser();

$message = '';
$error = '';

// Handle profile update
if ($_POST) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($full_name) || empty($email)) {
        $error = 'Full name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($new_password && strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New password and confirmation do not match.';
    } else {
        try {
            // Check if email is already taken by another user
            $existing = $db->fetchOne(
                "SELECT id FROM users WHERE email = ? AND id != ?",
                [$email, $user['id']]
            );
            
            if ($existing) {
                $error = 'Email address is already in use by another account.';
            } else {
                // Update profile information
                $updateData = [$full_name, $email, $user['id']];
                $updateSql = "UPDATE users SET full_name = ?, email = ? WHERE id = ?";
                
                // If password change is requested, verify current password first
                if ($new_password) {
                    if (!password_verify($current_password, $user['password'])) {
                        $error = 'Current password is incorrect.';
                    } else {
                        $updateSql = "UPDATE users SET full_name = ?, email = ?, password = ? WHERE id = ?";
                        array_splice($updateData, 2, 0, password_hash($new_password, PASSWORD_DEFAULT));
                    }
                }
                
                if (!$error) {
                    $db->query($updateSql, $updateData);
                    $message = 'Profile updated successfully!';
                    
                    // Refresh user data
                    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$user['id']]);
                    $_SESSION['user'] = $user;
                }
            }
        } catch (Exception $e) {
            $error = 'An error occurred while updating your profile. Please try again.';
        }
    }
}

// Get teacher statistics
$stats = [
    'total_courses' => $db->fetchOne("SELECT COUNT(*) as count FROM courses WHERE teacher_id = ?", [$user['id']])['count'],
    'total_students' => $db->fetchOne("SELECT COUNT(DISTINCT e.student_id) as count FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE c.teacher_id = ?", [$user['id']])['count'],
    'total_assignments' => $db->fetchOne("SELECT COUNT(*) as count FROM assignments a JOIN courses c ON a.course_id = c.id WHERE c.teacher_id = ?", [$user['id']])['count'],
    'total_submissions' => $db->fetchOne("SELECT COUNT(*) as count FROM submissions s JOIN assignments a ON s.assignment_id = a.id JOIN courses c ON a.course_id = c.id WHERE c.teacher_id = ?", [$user['id']])['count']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Teacher Dashboard</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
        }
        .navbar {
            background: #fff;
            border-bottom: 1px solid #ddd;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .brand { 
            color: #7c3aed; 
            font-weight: 700; 
            font-size: 18px; 
        }
        .navbar a { 
            text-decoration: none; 
            color: #333; 
            margin-left: 15px; 
        }
        .navbar a:hover { 
            color: #7c3aed; 
        }
        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .box {
            background: #fff;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .profile-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }
        .flash {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .flash.success { 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb; 
        }
        .flash.error { 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
        }
        h1, h2 { 
            color: #333;
            margin: 0 0 15px; 
        }
        h1 { font-size: 24px; }
        h2 { font-size: 18px; }
        h3 { 
            font-size: 16px; 
            color: #333;
            margin: 0 0 10px;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: #7c3aed;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 36px;
            font-weight: bold;
        }
        .profile-info {
            text-align: center;
            margin-bottom: 20px;
        }
        .profile-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        .profile-email {
            color: #666;
            margin-bottom: 5px;
        }
        .profile-role {
            color: #666;
            font-size: 13px;
            text-transform: capitalize;
        }
        .info-list {
            margin: 20px 0;
            padding: 0;
            list-style: none;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #666;
        }
        .info-value {
            color: #333;
            font-weight: 500;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        @media (max-width: 576px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-control:disabled {
            background: #f5f5f5;
            color: #666;
        }
        .form-control:focus {
            outline: none;
            border-color: #7c3aed;
        }
        .form-hint {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        .form-divider {
            margin: 25px 0;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #7c3aed;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .btn-outline {
            background: none;
            border: 1px solid #ddd;
            color: #666;
        }
        .btn-outline:hover {
            background: #f5f5f5;
        }
        .stats-list {
            margin-top: 15px;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }
        .stat-label {
            color: #666;
        }
        .stat-value {
            font-weight: 600;
            color: #7c3aed;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <nav class="navbar">
        <div class="brand">Edit Profile</div>
        <div>
            <a href="dashboard.php">Dashboard</a>
            <span style="color: #666;">Hello, <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" style="color: #dc3545;">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="profile-grid">
            <!-- Profile Information -->
            <div class="box">
                <div class="profile-info">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                    <div class="profile-email"><?php echo htmlspecialchars($user['email']); ?></div>
                    <div class="profile-role"><?php echo htmlspecialchars($user['role']); ?></div>
                </div>
                
                <ul class="info-list">
                    <li class="info-item">
                        <span class="info-label">Username</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                    </li>
                    <li class="info-item">
                        <span class="info-label">Member Since</span>
                        <span class="info-value"><?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                    </li>
                    <li class="info-item">
                        <span class="info-label">Status</span>
                        <span class="info-value">
                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </li>
                </ul>

                <!-- Teaching Statistics -->
                <div class="stats-list">
                    <h3>Teaching Statistics</h3>
                    <div class="stat-item">
                        <span class="stat-label">Courses</span>
                        <span class="stat-value"><?php echo $stats['total_courses']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Students</span>
                        <span class="stat-value"><?php echo $stats['total_students']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Assignments</span>
                        <span class="stat-value"><?php echo $stats['total_assignments']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Submissions</span>
                        <span class="stat-value"><?php echo $stats['total_submissions']; ?></span>
                    </div>
                </div>
            </div>

            <!-- Edit Form -->
            <div class="box">
                <h2>Update Profile Information</h2>
                
                <?php if ($message): ?>
                    <div class="flash success">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="flash error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <!-- Basic Information -->
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>"
                                   class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>"
                                   class="form-control" required>
                        </div>
                    </div>
                    
                    <!-- Read-only fields -->
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Username (Cannot be changed)</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>"
                                   class="form-control" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Role (Cannot be changed)</label>
                            <input type="text" value="<?php echo ucfirst($user['role']); ?>"
                                   class="form-control" disabled>
                        </div>
                    </div>
                    
                    <!-- Password Change Section -->
                    <div class="form-divider">
                        <h3>Change Password (Optional)</h3>
                        <div class="form-group">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" id="current_password" name="current_password"
                                   class="form-control">
                            <div class="form-hint">Required only if changing password</div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" id="new_password" name="new_password"
                                       class="form-control">
                                <div class="form-hint">Minimum 6 characters</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password"
                                       class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="btn-group">
                        <a href="dashboard.php" class="btn btn-outline">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

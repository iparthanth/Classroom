<?php

function handleProfileUpdate($db, $user) {
    $message = '';
    $error = '';

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
                        $db->executeQuery($updateSql, $updateData);
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

    return [
        'message' => $message,
        'error' => $error,
        'user' => $user
    ];
}

function getTeacherStats($db, $user) {
    return [
        'total_courses' => $db->fetchOne(
            "SELECT COUNT(*) as count FROM courses WHERE teacher_id = ?", 
            [$user['id']]
        )['count'],
        'total_students' => $db->fetchOne(
            "SELECT COUNT(DISTINCT e.student_id) as count FROM enrollments e 
             JOIN courses c ON e.course_id = c.id WHERE c.teacher_id = ?", 
            [$user['id']]
        )['count'],
        'total_assignments' => $db->fetchOne(
            "SELECT COUNT(*) as count FROM assignments a 
             JOIN courses c ON a.course_id = c.id WHERE c.teacher_id = ?", 
            [$user['id']]
        )['count'],
        'total_submissions' => $db->fetchOne(
            "SELECT COUNT(*) as count FROM submissions s 
             JOIN assignments a ON s.assignment_id = a.id 
             JOIN courses c ON a.course_id = c.id WHERE c.teacher_id = ?", 
            [$user['id']]
        )['count']
    ];
}
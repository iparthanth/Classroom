<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/profile/profile_logic.php';
require_once '../includes/profile/profile_components.php';

// Require teacher login
$auth->requireRole('teacher');
$user = $auth->getCurrentUser();

// Handle profile update
$result = handleProfileUpdate($db, $user);
$message = $result['message'];
$error = $result['error'];
$user = $result['user'];

// Get teacher statistics
$stats = getTeacherStats($db, $user);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Teacher Dashboard</title>
    <?php renderProfileStyles(); ?>
</head>
<body>
    <?php renderProfileHeader($user, 'Teacher Profile'); ?>

    <div class="container">
        <div class="profile-grid">
            <!-- Profile Sidebar -->
            <?php renderProfileSidebar($user, $stats); ?>

            <!-- Edit Form -->
            <div>
                <?php renderProfileForm($user, $message, $error); ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/profile/profile_logic.php';
require_once '../includes/profile/profile_components.php';

// Require student login
$auth->requireRole('student');
$user = $auth->getCurrentUser();

// Handle profile update
$result = handleProfileUpdate($db, $user);
$message = $result['message'];
$error = $result['error'];
$user = $result['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - E-Learning System</title>
    <?php renderProfileStyles(); ?>
</head>
<body>
    <?php renderProfileHeader($user); ?>

    <div class="container">
        <div class="profile-grid">
            <!-- Profile Sidebar -->
            <?php renderProfileSidebar($user); ?>

            <!-- Edit Form -->
            <div>
                <?php renderProfileForm($user, $message, $error); ?>
            </div>
        </div>
    </div>
</body>
</html>

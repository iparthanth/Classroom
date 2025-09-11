<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require student login
$auth->requireRole('student');
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - E-Learning System</title>
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
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-primary rounded flex items-center justify-center">
                        <span class="text-white font-bold text-xl">E</span>
                    </div>
                    <h1 class="text-xl font-bold text-gray-800">Edit Profile</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-primary hover:text-blue-700">Dashboard</a>
                    <span class="text-gray-600">Hello, <?php echo htmlspecialchars($user['full_name']); ?></span>
                    <a href="../logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile Information -->
            <div class="bg-white rounded-xl shadow p-6">
                <div class="text-center mb-6">
                    <div class="w-24 h-24 bg-primary rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-white font-bold text-3xl">
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                        </span>
                    </div>
                    <h2 class="text-xl font-bold"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="text-sm text-gray-500 capitalize"><?php echo htmlspecialchars($user['role']); ?></p>
                </div>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Username:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Member Since:</span>
                        <span class="font-medium"><?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="font-medium">
                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Edit Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-2xl font-bold mb-6">Update Profile Information</h2>
                    
                    <?php if ($message): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-6">
                        <!-- Basic Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Full Name *
                                </label>
                                <input type="text" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                                       required>
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Email Address *
                                </label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                                       required>
                            </div>
                        </div>
                        
                        <!-- Read-only fields -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Username (Cannot be changed)
                                </label>
                                <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600"
                                       disabled>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Role (Cannot be changed)
                                </label>
                                <input type="text" value="<?php echo ucfirst($user['role']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600"
                                       disabled>
                            </div>
                        </div>
                        
                        <!-- Password Change Section -->
                        <div class="border-t pt-6">
                            <h3 class="text-lg font-semibold mb-4">Change Password (Optional)</h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                                        Current Password
                                    </label>
                                    <input type="password" id="current_password" name="current_password"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                    <p class="text-xs text-gray-500 mt-1">Required only if changing password</p>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                                            New Password
                                        </label>
                                        <input type="password" id="new_password" name="new_password"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                        <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                                    </div>
                                    
                                    <div>
                                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                            Confirm New Password
                                        </label>
                                        <input type="password" id="confirm_password" name="confirm_password"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-3">
                            <a href="dashboard.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-700">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

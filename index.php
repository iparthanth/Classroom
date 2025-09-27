<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// If user is already logged in, redirect to dashboard
if ($auth->isLoggedIn()) {
    redirect('/dashboard.php');
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        $result = $auth->login($username, $password);
        if ($result['success']) {
            redirect('/dashboard.php');
        } else {
            $message = $result['message'];
        }
    } elseif ($action === 'register') {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $full_name = sanitizeInput($_POST['full_name'] ?? '');
        $role = sanitizeInput($_POST['role'] ?? 'student');
        
        $result = $auth->register($username, $email, $password, $full_name, $role);
        $message = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Learning Virtual Classroom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f0f8ff;
            background-image: url('images/classes.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
        }
        
        /* Add an overlay to improve readability */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
    </style>
</head>
<body class="relative">
    <!-- Background overlay for better readability -->
    <div class="overlay"></div>
    
    <!-- Simple Header -->
    <header class="bg-white border-b">
        <div class="max-w-4xl mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-semibold text-gray-800">E-Learning Virtual Classroom</h1>
            </div>
        </div>
    </header>

    <div class="max-w-md mx-auto px-4 py-8 relative z-10">
        <!-- Message Display -->
        <?php if(!empty($message)): ?>
            <div class="mb-6 p-3 rounded text-sm bg-red-100 text-red-700">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <div class="bg-white rounded-lg border p-6 shadow-lg">
            <h2 class="text-lg font-medium text-gray-800 mb-4">Login to Your Account</h2>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username or Email</label>
                    <input type="text" name="username" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded text-sm font-medium hover:bg-blue-700 transition duration-200">
                    Sign In
                </button>
            </form>
        </div>
    </div>
</body>
</html>
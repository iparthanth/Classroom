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
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f0f8ff;
            background-image: url('images/classes.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
        }
        .navbar {
            background: #fff;
            border-bottom: 1px solid #ddd;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar a, .navbar button {
            text-decoration: none;
            color: #000;
            margin-left: 14px;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            background: #28a745;
            color: #fff;
        }
        .navbar button:hover { background: #218838; }
        .brand { color: #28a745; font-weight: 700; font-size: 20px; margin-left: 0; background: none; color: #28a745; }
        .container {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h1 { color: #28a745; font-size: 28px; margin: 0 0 16px; text-align: center; }
        h2 { color: #333; font-size: 22px; margin: 0 0 12px; }
        .hero { text-align: center; margin-bottom: 30px; }
        .features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        .feature-box {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-align: center;
        }
        .auth-forms { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px; }
        .form-box {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #f9f9f9;
        }
        label { display: block; margin-top: 12px; font-weight: 600; }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-top: 6px;
        }
        button {
            width: 100%;
            background: #28a745;
            color: #fff;
            padding: 12px;
            border: 0;
            border-radius: 6px;
            margin-top: 16px;
            cursor: pointer;
        }
        button:hover { background: #218838; }
        .message {
            padding: 12px;
            margin: 16px 0;
            border-radius: 6px;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="brand">E-Learning Virtual Classroom</div>
        <div>
            <button onclick="scrollToAuth()">Get Started</button>
        </div>
    </div>

    <div class="container">
        <div class="hero">
            <h1>Virtual Classroom Management System</h1>
            <p>A simple platform for online learning, teaching, and course management.<br>
               Join as a student or teacher to get started with interactive learning.</p>
        </div>
        
        <?php if(!empty($message)): ?>
        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <div class="features">
            <div class="feature-box">
                <h2>📚 For Students</h2>
                <p>Browse courses, submit assignments, join interactive classes, and track your progress.</p>
            </div>
            <div class="feature-box">
                <h2>👨‍🏫 For Teachers</h2>
                <p>Create courses, manage assignments, use whiteboard, and engage with students in real-time.</p>
            </div>
        </div>
        
        <div id="authSection" class="auth-forms">
            <div class="form-box">
                <h2>Login</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    <label>Username or Email</label>
                    <input type="text" name="username" required>
                    <label>Password</label>
                    <input type="password" name="password" required>
                    <button type="submit">Sign In</button>
                </form>
            </div>
            
            <div class="form-box">
                <h2>Register</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="register">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required>
                    <label>Username</label>
                    <input type="text" name="username" required>
                    <label>Email</label>
                    <input type="email" name="email" required>
                    <label>Password</label>
                    <input type="password" name="password" required>
                    <label>Join as</label>
                    <select name="role">
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                    </select>
                    <button type="submit">Create Account</button>
                </form>
            </div>
        </div>
    </div>


    <script>
        function scrollToAuth() {
            document.getElementById('authSection').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>

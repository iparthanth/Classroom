<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireRole('admin');
$user = $auth->getCurrentUser();

$success_message = '';
$error_message = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_settings') {
        try {
            // This would normally save to a settings table or config file
            // For this demo, we'll just show a success message
            $success_message = 'System settings updated successfully!';
        } catch (Exception $e) {
            $error_message = 'Failed to update settings: ' . $e->getMessage();
        }
    }
}

// Mock settings - in a real application, these would come from a database or config file
$settings = [
    'site_name' => 'E-Learning Virtual Classroom',
    'max_file_size' => '10',
    'allowed_file_types' => 'pdf,doc,docx,txt,jpg,jpeg,png',
    'max_students_per_course' => '50',
    'enable_notifications' => true,
    'enable_chat' => true,
    'enable_whiteboard' => true,
    'maintenance_mode' => false
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin Dashboard</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
            color: #333;
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
            color: #dc3545;
            font-weight: 700; 
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .brand-icon {
            background: #dc3545;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .navbar a { 
            text-decoration: none; 
            color: #333; 
            margin-left: 15px; 
        }
        .navbar a:hover { 
            color: #dc3545;
        }
        .container {
            max-width: 800px;
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
        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #e6f4ea;
            color: #1e7e34;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #fde8e8;
            color: #dc3545;
            border: 1px solid #f5c6cb;
        }
        h1 { 
            font-size: 24px; 
            margin: 0 0 10px; 
        }
        h2 { 
            font-size: 20px; 
            margin: 0 0 15px; 
        }
        h3 {
            font-size: 18px;
            margin: 0 0 20px;
        }
        .subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
            color: #444;
        }
        .form-help {
            color: #666;
            font-size: 13px;
            margin-top: 4px;
        }
        .form-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-input:focus {
            outline: none;
            border-color: #dc3545;
            box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.1);
        }
        .toggle-switch {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .toggle-switch:last-child {
            border-bottom: none;
        }
        .toggle-label {
            font-weight: 500;
        }
        .toggle-description {
            color: #666;
            font-size: 13px;
            margin-top: 2px;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #dc3545;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        .btn-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            color: #fff;
            background: #dc3545;
            transition: opacity 0.2s;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .btn-blue { background: #0066cc; }
        .btn-green { background: #1e7e34; }
        .btn-yellow { background: #ffc107; color: #000; }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar">
        <div class="brand">
            <div class="brand-icon">⚙️</div>
            System Settings
        </div>
        <div>
            <span style="color: #666;">Admin: <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="dashboard.php">Dashboard</a>
            <a href="../logout.php" style="color: #dc3545;">Logout</a>
        </div>
    </nav>

    <div class="container">
        <!-- Page Header -->
        <div class="box">
            <h1>System Settings</h1>
            <div class="subtitle">Configure system-wide settings and preferences</div>
        </div>

        <!-- Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="update_settings">

            <!-- General Settings -->
            <div class="box">
                <h3>General Settings</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Site Name</label>
                        <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>" 
                               class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Max Students per Course</label>
                        <input type="number" name="max_students_per_course" value="<?php echo $settings['max_students_per_course']; ?>" 
                               class="form-input">
                    </div>
                </div>
            </div>

            <!-- File Upload Settings -->
            <div class="box">
                <h3>File Upload Settings</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Max File Size (MB)</label>
                        <input type="number" name="max_file_size" value="<?php echo $settings['max_file_size']; ?>" 
                               class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Allowed File Types</label>
                        <input type="text" name="allowed_file_types" value="<?php echo htmlspecialchars($settings['allowed_file_types']); ?>" 
                               placeholder="pdf,doc,docx,txt,jpg,png" class="form-input">
                        <div class="form-help">Comma-separated list of file extensions</div>
                    </div>
                </div>
            </div>

            <!-- Feature Settings -->
            <div class="box">
                <h3>Feature Settings</h3>
                <div>
                    <div class="toggle-switch">
                        <div>
                            <div class="toggle-label">Enable Notifications</div>
                            <div class="toggle-description">Allow system to send notifications to users</div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="enable_notifications" <?php echo $settings['enable_notifications'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="toggle-switch">
                        <div>
                            <div class="toggle-label">Enable Chat</div>
                            <div class="toggle-description">Allow real-time chat in courses</div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="enable_chat" <?php echo $settings['enable_chat'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="toggle-switch">
                        <div>
                            <div class="toggle-label">Enable Whiteboard</div>
                            <div class="toggle-description">Allow interactive whiteboard in courses</div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="enable_whiteboard" <?php echo $settings['enable_whiteboard'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- System Maintenance -->
            <div class="box">
                <h3>System Maintenance</h3>
                <div class="toggle-switch">
                    <div>
                        <div class="toggle-label">Maintenance Mode</div>
                        <div class="toggle-description">Put the system in maintenance mode (only admins can access)</div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="maintenance_mode" <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <!-- Database Maintenance -->
            <div class="box">
                <h3>Database Maintenance</h3>
                <div class="btn-group">
                    <button type="button" class="btn btn-blue" onclick="alert('Database optimization would run here')">
                        Optimize Database
                    </button>
                    <button type="button" class="btn btn-green" onclick="alert('Database backup would run here')">
                        Backup Database
                    </button>
                    <button type="button" class="btn btn-yellow" onclick="alert('Cache clearing would run here')">
                        Clear Cache
                    </button>
                </div>
            </div>

            <!-- Save Button -->
            <div class="box" style="text-align: right;">
                <button type="submit" class="btn">
                    Save Settings
                </button>
            </div>
        </form>
    </div>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563eb'
                    }
                }
            }
        }
    </script>
</body>
</html>

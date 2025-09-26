<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireRole('admin');
$user = $auth->getCurrentUser();

$success = '';
$error   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_settings') {
    try {
        // Persist settings to DB or config here...
        $success = 'System settings updated successfully!';
    } catch (Exception $e) {
        $error = 'Failed to update settings: ' . $e->getMessage();
    }
    redirect('settings.php');
}

// In real use, load these from database/config
$settings = [
    'site_name'               => 'E-Learning Virtual Classroom',
    'max_file_size'           => '10',
    'allowed_file_types'      => 'pdf,doc,docx,txt,jpg,jpeg,png',
    'max_students_per_course' => '50',
    'enable_notifications'    => true,
    'enable_chat'             => true,
    'enable_whiteboard'       => true,
    'maintenance_mode'        => false,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        * { box-sizing: border-box; margin:0; padding:0 }
        body{font:14px/1.5 Arial,sans-serif;background:#f5f5f5;color:#333}
        .navbar{display:flex;justify-content:space-between;padding:15px 20px;
                background:#fff;border-bottom:1px solid #ddd}
        .brand{display:flex;align-items:center;font-size:18px;font-weight:700;
               color:#28a745;gap:10px}
        .brand-icon{width:32px;height:32px;background:#28a745;color:#fff;
                   display:flex;align-items:center;justify-content:center;
                   border-radius:4px}
        .navbar a{margin-left:15px;color:#333;text-decoration:none}
        .navbar a:hover{color:#28a745}
        .container{max-width:800px;margin:20px auto;padding:0 20px}
        .box{background:#fff;padding:20px;border:1px solid #ddd;
             border-radius:6px;margin-bottom:20px}
        h1,h3{margin-bottom:15px;color:#333;font-weight:700}
        .subtitle{font-size:14px;color:#666;margin-bottom:20px}
        .alert{padding:12px 15px;border-radius:4px;margin-bottom:20px}
        .alert-success{background:#e6f4ea;color:#1e7e34;border:1px solid #c3e6cb}
        .alert-error{background:#fde8e8;color:#dc3545;border:1px solid #f5c6cb}
        .form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
                   gap:20px;margin-bottom:20px}
        .form-group{margin-bottom:15px}
        .form-label{display:block;font-weight:500;margin-bottom:5px;color:#444}
        .form-help{color:#666;font-size:13px;margin-top:4px}
        .form-input{width:100%;padding:8px 12px;border:1px solid #ddd;
                    border-radius:4px;font-size:14px}
        .form-input:focus{outline:none;border-color:#28a745;
                          box-shadow:0 0 0 2px rgba(40,167,69,.1)}
        .toggle-switch{display:flex;justify-content:space-between;
                       align-items:center;padding:10px 0;border-bottom:1px solid #eee}
        .toggle-switch:last-child{border-bottom:none}
        .toggle-label{font-weight:500}
        .toggle-desc{color:#666;font-size:13px;margin-top:2px}
        .switch{position:relative;display:inline-block;width:50px;height:24px}
        .switch input{opacity:0;width:0;height:0}
        .slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;
                background:#ccc;transition:.4s;border-radius:24px}
        .slider:before{position:absolute;content:"";height:16px;width:16px;
                       left:4px;bottom:4px;background:#fff;transition:.4s;
                       border-radius:50%}
        input:checked + .slider{background:#28a745}
        input:checked + .slider:before{transform:translateX(26px)}
        .btn-group{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));
                   gap:10px}
        .btn{padding:8px 16px;border:none;border-radius:4px;font-size:14px;
             font-weight:500;cursor:pointer;text-align:center;color:#fff;
             background:#28a745;transition:opacity .2s;text-decoration:none;
             display:inline-block}
        .btn:hover{opacity:.9}
        .btn-blue{background:#0066cc}
        .btn-green{background:#1e7e34}
        .btn-yellow{background:#ffc107;color:#000}
    </style>
</head>
<body>
<nav class="navbar">
    <div class="brand">
        <div class="brand-icon">⚙️</div>
        System Settings
    </div>
    <div>
        <span style="color:#666">Admin: <?=htmlspecialchars($user['full_name'])?></span>
        <a href="dashboard.php">Dashboard</a>
        <a href="../logout.php">Logout</a>
    </div>
</nav>
<div class="container">
    <div class="box">
        <h1>System Settings</h1>
        <div class="subtitle">Configure system-wide settings and preferences</div>
    </div>

    <?php if($success): ?>
        <div class="alert alert-success"><?=htmlspecialchars($success)?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-error"><?=htmlspecialchars($error)?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="action" value="update_settings"/>

        <div class="box">
            <h3>General Settings</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Site Name</label>
                    <input class="form-input" type="text" name="site_name"
                           value="<?=htmlspecialchars($settings['site_name'])?>"/>
                </div>
                <div class="form-group">
                    <label class="form-label">Max Students per Course</label>
                    <input class="form-input" type="number" name="max_students_per_course"
                           value="<?=htmlspecialchars($settings['max_students_per_course'])?>"/>
                </div>
            </div>
        </div>

        <div class="box">
            <h3>File Upload Settings</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Max File Size (MB)</label>
                    <input class="form-input" type="number" name="max_file_size"
                           value="<?=htmlspecialchars($settings['max_file_size'])?>"/>
                </div>
                <div class="form-group">
                    <label class="form-label">Allowed File Types</label>
                    <input class="form-input" type="text" name="allowed_file_types"
                           value="<?=htmlspecialchars($settings['allowed_file_types'])?>"
                           placeholder="pdf,doc,docx,txt,jpg,png"/>
                    <div class="form-help">Comma-separated list of file extensions</div>
                </div>
            </div>
        </div>

        <div class="box">
            <h3>Feature Settings</h3>
            <?php foreach (['enable_notifications'=>'Allow notifications',
                           'enable_chat'=>'Allow chat',
                           'enable_whiteboard'=>'Allow whiteboard'] as $key=>$desc): ?>
                <div class="toggle-switch">
                    <div>
                        <div class="toggle-label"><?=ucwords(str_replace('_',' ',$key))?></div>
                        <div class="toggle-desc"><?=$desc?></div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="<?=$key?>" <?=!empty($settings[$key])?'checked':''?>/>
                        <span class="slider"></span>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="box">
            <h3>System Maintenance</h3>
            <div class="toggle-switch">
                <div>
                    <div class="toggle-label">Maintenance Mode</div>
                    <div class="toggle-desc">Only admins can access in maintenance</div>
                </div>
                <label class="switch">
                    <input type="checkbox" name="maintenance_mode"
                           <?=!empty($settings['maintenance_mode'])?'checked':''?>/>
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        <div class="box">
            <h3>Database Maintenance</h3>
            <div class="btn-group">
                <button type="button" class="btn btn-blue"
                        onclick="alert('Optimize DB')">Optimize Database</button>
                <button type="button" class="btn btn-green"
                        onclick="alert('Backup DB')">Backup Database</button>
                <button type="button" class="btn btn-yellow"
                        onclick="alert('Clear Cache')">Clear Cache</button>
            </div>
        </div>

        <div class="box" style="text-align:right">
            <button type="submit" class="btn">Save Settings</button>
        </div>
    </form>
</div>
</body>
</html>

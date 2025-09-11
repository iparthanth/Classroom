<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require admin login
$auth->requireRole('admin');
$user = $auth->getCurrentUser();

// Handle user actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'toggle_status') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $current_status = $db->fetchOne("SELECT is_active FROM users WHERE id = ?", [$user_id]);
        
        if ($current_status && $user_id !== $user['id']) { // Don't allow deactivating self
            $new_status = $current_status['is_active'] ? 0 : 1;
            $db->query("UPDATE users SET is_active = ? WHERE id = ?", [$new_status, $user_id]);
            setFlash('success', 'User status updated successfully');
        }
    }
    
    if ($action === 'delete_user') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        if ($user_id && $user_id !== $user['id']) { // Don't allow deleting self
            try {
                $db->query("DELETE FROM users WHERE id = ?", [$user_id]);
                setFlash('success', 'User deleted successfully');
            } catch (Exception $e) {
                setFlash('error', 'Could not delete user. They may have associated data.');
            }
        }
    }
    
    redirect('/admin/users.php');
}

// Get filter parameters
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($role_filter) {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
}

if ($status_filter !== '') {
    $where_conditions[] = "is_active = ?";
    $params[] = (int)$status_filter;
}

if ($search) {
    $where_conditions[] = "(full_name LIKE ? OR email LIKE ? OR username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = empty($where_conditions) ? "" : "WHERE " . implode(" AND ", $where_conditions);

$users = $db->fetchAll(
    "SELECT * FROM users $where_clause ORDER BY created_at DESC",
    $params
);

$flash = getFlash();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
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
        .box {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
        h1 { font-size: 24px; margin: 0; }
        h2 { font-size: 20px; margin: 0 0 15px; }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .form-group { margin-bottom: 15px; }
        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
            color: #444;
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
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            background: #dc3545;
            color: white;
            text-decoration: none;
        }
        .btn:hover { opacity: 0.9; }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            text-align: left;
            padding: 12px;
            background: #f8f9fa;
            font-weight: 600;
            color: #444;
            border-bottom: 2px solid #ddd;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        tr:hover { background: #f8f9fa; }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-blue {
            background: #e6f3ff;
            color: #0066cc;
        }
        .badge-red {
            background: #ffe6e6;
            color: #dc3545;
        }
        .badge-green {
            background: #e6f9f1;
            color: #1e7e34;
        }
        .badge-purple {
            background: #f3e6ff;
            color: #6f42c1;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .stat-box {
            text-align: center;
            padding: 20px;
        }
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #dc3545;
            margin-bottom: 8px;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .container {
                padding: 10px;
            }
            .box {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar">
        <div class="brand">
            <div class="brand-icon">👥</div>
            Manage Users
        </div>
        <div>
            <span style="color: #666;">Admin: <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="dashboard.php">Dashboard</a>
            <a href="../logout.php" style="color: #dc3545;">Logout</a>
        </div>
    </nav>

    <div class="container">
        <!-- Flash Messages -->
        <?php if($flash): ?>
            <div class="alert <?php echo $flash['type'] === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="box">
            <h2>Filter Users</h2>
            <form method="GET" class="form-grid">
                <div class="form-group">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Name, email, or username"
                           class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-input">
                        <option value="">All Roles</option>
                        <option value="student" <?php echo $role_filter === 'student' ? 'selected' : ''; ?>>Students</option>
                        <option value="teacher" <?php echo $role_filter === 'teacher' ? 'selected' : ''; ?>>Teachers</option>
                        <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Administrators</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-input">
                        <option value="">All Status</option>
                        <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="form-group" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn" style="width: 100%;">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Users List -->
        <div class="box">
            <h2>All Users (<?php echo count($users); ?>)</h2>
            
            <?php if (empty($users)): ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <div style="font-size: 48px; margin-bottom: 10px;">👤</div>
                    <p>No users found matching your criteria.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user_row): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <div style="font-weight: 600;">
                                                <?php echo htmlspecialchars($user_row['full_name']); ?>
                                                <?php if ($user_row['id'] === $user['id']): ?>
                                                    <span style="color: #0066cc; font-size: 12px;">(You)</span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="color: #666;"><?php echo htmlspecialchars($user_row['email']); ?></div>
                                            <div style="color: #999; font-size: 12px;">@<?php echo htmlspecialchars($user_row['username']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $user_row['role'] === 'admin' ? 'badge-red' : 
                                                ($user_row['role'] === 'teacher' ? 'badge-purple' : 'badge-blue'); 
                                            ?>">
                                            <?php echo ucfirst($user_row['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $user_row['is_active'] ? 'badge-green' : 'badge-red'; ?>">
                                            <?php echo $user_row['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="color: #666;">
                                            <?php echo formatDate($user_row['created_at']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user_row['id'] !== $user['id']): ?>
                                            <!-- Toggle Status -->
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?php echo $user_row['id']; ?>">
                                                <button type="submit" class="btn" style="background: #0066cc;">
                                                    <?php echo $user_row['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                            
                                            <!-- Delete User -->
                                            <form method="POST" style="display: inline; margin-left: 8px;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user_row['id']; ?>">
                                                <button type="submit" class="btn" style="background: #dc3545;">
                                                    Delete
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: #999;">Current User</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- User Statistics -->
        <div class="stats-grid">
            <div class="box stat-box">
                <div class="stat-number" style="color: #0066cc;">
                    <?php echo count(array_filter($users, function($u) { return $u['role'] === 'student'; })); ?>
                </div>
                <div class="stat-label">Students</div>
            </div>
            <div class="box stat-box">
                <div class="stat-number" style="color: #6f42c1;">
                    <?php echo count(array_filter($users, function($u) { return $u['role'] === 'teacher'; })); ?>
                </div>
                <div class="stat-label">Teachers</div>
            </div>
            <div class="box stat-box">
                <div class="stat-number" style="color: #dc3545;">
                    <?php echo count(array_filter($users, function($u) { return $u['role'] === 'admin'; })); ?>
                </div>
                <div class="stat-label">Administrators</div>
            </div>
        </div>
    </div>
</body>
</html>

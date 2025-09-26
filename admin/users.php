<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireRole('admin');
$current = $auth->getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['user_id'] ?? 0);

    if ($action === 'toggle_status' && $id && $id !== $current['id']) {
        if ($row = $db->fetchOne("SELECT is_active FROM users WHERE id = ?", [$id])) {
            $db->executeQuery("UPDATE users SET is_active = ? WHERE id = ?", [!$row['is_active'], $id]);
            setFlash('success','User status updated');
        }
    }
    if ($action === 'delete_user' && $id && $id !== $current['id']) {
        try {
            $db->executeQuery("DELETE FROM users WHERE id = ?", [$id]);
            setFlash('success','User deleted');
        } catch (Exception $e) {
            setFlash('error','Could not delete user');
        }
    }
    redirect('/admin/users.php');
}

$role   = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$where = []; $params = [];
if ($role)   { $where[] = "role=?";             $params[] = $role; }
if ($status!=='') { $where[] = "is_active=?";  $params[] = (int)$status; }
if ($search) {
    $where[] = "(full_name LIKE ? OR email LIKE ? OR username LIKE ?)";
    $params = array_merge($params, ["%$search%","%$search%","%$search%"]);
}
$clause = $where ? "WHERE ".implode(" AND ",$where) : '';
$users  = $db->fetchAll("SELECT * FROM users $clause ORDER BY created_at DESC", $params);
$flash  = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font:14px/1.5 Arial,sans-serif;background:#f5f5f5;color:#333}
        .navbar{display:flex;justify-content:space-between;padding:15px 20px;
                background:#fff;border-bottom:1px solid #ddd}
        .brand{display:flex;align-items:center;font-size:18px;font-weight:700;
               color:#dc3545;gap:10px}
        .brand-icon{width:32px;height:32px;background:#dc3545;color:#fff;
                   display:flex;align-items:center;justify-content:center;
                   border-radius:4px}
        .navbar a{margin-left:15px;color:#333;text-decoration:none}
        .navbar a:hover{color:#dc3545}
        .container{max-width:1200px;margin:20px auto;padding:0 20px}
        .box{background:#fff;padding:20px;border:1px solid #ddd;
             border-radius:8px;margin-bottom:20px;box-shadow:0 2px 4px rgba(0,0,0,0.1)}
        h2{font-size:20px;margin-bottom:15px;color:#333}
        .alert{padding:12px 15px;border-radius:4px;margin-bottom:20px}
        .alert-success{background:#e6f4ea;color:#1e7e34;border:1px solid #c3e6cb}
        .alert-error{background:#fde8e8;color:#dc3545;border:1px solid #f5c6cb}
        .form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
                   gap:15px;margin-bottom:20px}
        .form-label{display:block;font-weight:500;margin-bottom:5px;color:#444}
        .form-input{width:100%;padding:8px 12px;border:1px solid #ddd;
                    border-radius:4px;font-size:14px}
        .form-input:focus{outline:none;border-color:#dc3545;box-shadow:0 0 0 2px rgba(220,53,69,.1)}
        .btn{padding:8px 16px;border:none;border-radius:4px;font-size:14px;
             font-weight:500;color:#fff;background:#dc3545;cursor:pointer;
             text-decoration:none;display:inline-block}
        .btn:hover{opacity:.9}
        table{width:100%;border-collapse:collapse}
        th,td{padding:12px;text-align:left;border-bottom:1px solid #eee}
        th{background:#f8f9fa;color:#444;font-weight:600;border-bottom:2px solid #ddd}
        tr:hover{background:#f8f9fa}
        .badge{display:inline-block;padding:4px 8px;border-radius:12px;font-size:12px;
               font-weight:600}
        .badge-blue{background:#e6f3ff;color:#0066cc}
        .badge-red{background:#ffe6e6;color:#dc3545}
        .badge-green{background:#e6f9f1;color:#1e7e34}
        .badge-purple{background:#f3e6ff;color:#6f42c1}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
                    gap:20px;margin-top:20px}
        .stat-box{text-align:center;padding:20px}
        .stat-number{font-size:32px;font-weight:700;color:#dc3545;margin-bottom:8px}
        .stat-label{font-size:14px;color:#666}
        @media(max-width:768px){
            .form-grid{grid-template-columns:1fr}
            .container{padding:10px}
            .box{padding:15px}
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="brand"><div class="brand-icon">👥</div>Manage Users</div>
    <div>
        <span style="color:#666">Admin: <?=htmlspecialchars($current['full_name'])?></span>
        <a href="dashboard.php">Dashboard</a>
        <a href="../logout.php" style="color:#dc3545">Logout</a>
    </div>
</nav>

<div class="container">
    <?php if($flash): ?>
        <div class="alert <?= $flash['type']==='success'?'alert-success':'alert-error' ?>">
            <?=htmlspecialchars($flash['message'])?>
        </div>
    <?php endif; ?>

    <div class="box">
        <h2>Filter Users</h2>
        <form method="GET" class="form-grid">
            <div>
                <label class="form-label">Search</label>
                <input class="form-input" type="text" name="search" placeholder="Name, email, username"
                       value="<?=htmlspecialchars($search)?>" />
            </div>
            <div>
                <label class="form-label">Role</label>
                <select class="form-input" name="role">
                    <option value="">All Roles</option>
                    <option value="student" <?= $role==='student'?'selected':''?>>Students</option>
                    <option value="teacher" <?= $role==='teacher'?'selected':''?>>Teachers</option>
                    <option value="admin"   <?= $role==='admin'?'selected':''?>>Admins</option>
                </select>
            </div>
            <div>
                <label class="form-label">Status</label>
                <select class="form-input" name="status">
                    <option value="">All Status</option>
                    <option value="1" <?= $status==='1'?'selected':''?>>Active</option>
                    <option value="0" <?= $status==='0'?'selected':''?>>Inactive</option>
                </select>
            </div>
            <div style="align-self:end">
                <button type="submit" class="btn" style="width:100%">Filter</button>
            </div>
        </form>
    </div>

    <div class="box">
        <h2>All Users (<?=count($users)?>)</h2>
        <?php if (empty($users)): ?>
            <div style="text-align:center;padding:40px;color:#666">
                <div style="font-size:48px;margin-bottom:10px">👤</div>
                <p>No users found.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table>
                    <thead>
                        <tr><th>User</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach($users as $u): ?>
                        <tr>
                            <td>
                                <div style="font-weight:600">
                                    <?=htmlspecialchars($u['full_name'])?>
                                    <?= $u['id']===$current['id']?'<span style="color:#0066cc;font-size:12px">(You)</span>':''?>
                                </div>
                                <div style="color:#666"><?=htmlspecialchars($u['email'])?></div>
                                <div style="color:#999;font-size:12px">@<?=htmlspecialchars($u['username'])?></div>
                            </td>
                            <td>
                                <?php
                                $cls = $u['role']==='admin'?'badge-red':
                                       ($u['role']==='teacher'?'badge-purple':'badge-blue');
                                ?>
                                <span class="badge <?=$cls?>"><?=ucfirst($u['role'])?></span>
                            </td>
                            <td>
                                <span class="badge <?= $u['is_active']?'badge-green':'badge-red'?>">
                                    <?= $u['is_active']?'Active':'Inactive'?>
                                </span>
                            </td>
                            <td><span style="color:#666"><?=formatDate($u['created_at'])?></span></td>
                            <td>
                                <?php if($u['id']!==$current['id']): ?>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="action" value="toggle_status"/>
                                        <input type="hidden" name="user_id" value="<?=$u['id']?>"/>
                                        <button class="btn btn-blue" type="submit">
                                            <?=$u['is_active']?'Deactivate':'Activate'?>
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;margin-left:8px"
                                          onsubmit="return confirm('Delete this user?')">
                                        <input type="hidden" name="action" value="delete_user"/>
                                        <input type="hidden" name="user_id" value="<?=$u['id']?>"/>
                                        <button class="btn" style="background:#dc3545" type="submit">Delete</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color:#999">Current User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="stats-grid">
        <?php 
        $countByRole = ['student'=>0,'teacher'=>0,'admin'=>0];
        foreach($users as $u) $countByRole[$u['role']]++;
        ?>
        <div class="box stat-box">
            <div class="stat-number" style="color:#0066cc"><?=$countByRole['student']?></div>
            <div class="stat-label">Students</div>
        </div>
        <div class="box stat-box">
            <div class="stat-number" style="color:#6f42c1"><?=$countByRole['teacher']?></div>
            <div class="stat-label">Teachers</div>
        </div>
        <div class="box stat-box">
            <div class="stat-number" style="color:#dc3545"><?=$countByRole['admin']?></div>
            <div class="stat-label">Admins</div>
        </div>
    </div>
</div>
</body>
</html>

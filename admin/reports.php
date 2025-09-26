<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireRole('admin');
$user = $auth->getCurrentUser();

// Fetch report data
$course_stats     = $db->fetchAll("
    SELECT c.title, c.course_code,
           COUNT(DISTINCT e.id)       AS enrollment_count,
           COUNT(DISTINCT a.id)       AS assignment_count,
           COUNT(DISTINCT s.id)       AS submission_count
      FROM courses c
 LEFT JOIN enrollments e ON e.course_id = c.id
 LEFT JOIN assignments a ON a.course_id = c.id
 LEFT JOIN submissions s ON s.assignment_id = a.id
     WHERE c.is_active = 1
  GROUP BY c.id
  ORDER BY enrollment_count DESC
");

$assignment_stats = $db->fetchAll("
    SELECT a.title, c.course_code,
           COUNT(DISTINCT e.student_id) AS enrolled_students,
           COUNT(s.id)                   AS submissions,
           ROUND(100 * COUNT(s.id)/COUNT(DISTINCT e.student_id), 2) AS completion_rate
      FROM assignments a
      JOIN courses c      ON c.id = a.course_id
      JOIN enrollments e  ON e.course_id = c.id
 LEFT JOIN submissions s ON s.assignment_id = a.id
  GROUP BY a.id
 HAVING enrolled_students > 0
 ORDER BY completion_rate DESC
");

$user_activity    = $db->fetchAll("
    SELECT u.full_name, u.role, u.created_at,
           COUNT(DISTINCT e.id)    AS enrollments_count,
           COUNT(DISTINCT s.id)    AS submissions_count
      FROM users u
 LEFT JOIN enrollments e ON e.student_id = u.id
 LEFT JOIN submissions s ON s.student_id = u.id
     WHERE u.is_active = 1
  GROUP BY u.id
  ORDER BY u.created_at DESC
  LIMIT 20
");

$monthly_stats    = $db->fetchAll("
    SELECT DATE_FORMAT(created_at,'%Y-%m') AS month,
           COUNT(CASE WHEN role='student' THEN 1 END) AS students,
           COUNT(CASE WHEN role='teacher' THEN 1 END) AS teachers,
           COUNT(*) AS total
      FROM users
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
  GROUP BY month
  ORDER BY month
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Reports</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; color: #333; }
        .navbar { display: flex; justify-content: space-between; padding: 15px 20px; background: #fff; border-bottom: 1px solid #ddd; }
        .brand { font-size: 18px; font-weight: 700; color: #28a745; }
        .navbar a { margin-left: 15px; color: #333; text-decoration: none; }
        .navbar a:hover { color: #28a745; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .box { background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 20px; }
        h1, h2 { margin-bottom: 10px; color: #333; }
        .subtitle { font-size: 14px; color: #666; margin-bottom: 20px; }
        .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .stat-card { border-left: 4px solid #28a745; padding: 15px; margin-bottom: 15px; }
        .stat-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .stat-title { font-weight: 600; }
        .stat-code { font-size: 13px; color: #666; }
        .stat-numbers { text-align: right; }
        .stat-primary { color: #0066cc; }
        .stat-success { color: #1e7e34; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f8f9fa; font-weight: 600; }
        tr:hover { background: #f8f9fa; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 12px; }
        .badge-blue { background: #e6f3ff; color: #0066cc; }
        .badge-purple { background: #f3e8ff; color: #6f42c1; }
        .completion-rate { font-weight: bold; }
        .rate-high { color: #1e7e34; }
        .rate-med { color: #ffc107; }
        .rate-low { color: #dc3545; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="brand">System Reports</div>
        <div>
            <span style="color:#666">Admin: <?=htmlspecialchars($user['full_name'])?></span>
            <a href="dashboard.php">Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </nav>
    <div class="container">
        <div class="box">
            <h1>System Reports & Analytics</h1>
            <div class="subtitle">Overview of platform usage and performance</div>
        </div>

        <div class="box">
            <h2>Monthly Registrations</h2>
            <canvas id="monthlyChart" style="height:300px"></canvas>
        </div>

        <div class="grid-2">
            <div class="box">
                <h2>Top Courses</h2>
                <?php foreach(array_slice($course_stats,0,6) as $c): ?>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <h4 class="stat-title"><?=htmlspecialchars($c['title'])?></h4>
                                <p class="stat-code"><?=htmlspecialchars($c['course_code'])?></p>
                            </div>
                            <div class="stat-numbers">
                                <div class="stat-primary"><?=$c['enrollment_count']?> enrolled</div>
                                <div class="stat-muted"><?=$c['assignment_count']?> assignments</div>
                                <div class="stat-success"><?=$c['submission_count']?> submissions</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="box">
                <h2>Assignment Completion</h2>
                <?php foreach(array_slice($assignment_stats,0,6) as $a): 
                    $cls = $a['completion_rate']>=80?'rate-high':($a['completion_rate']>=60?'rate-med':'rate-low');
                ?>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <h4 class="stat-title"><?=htmlspecialchars($a['title'])?></h4>
                                <p class="stat-code"><?=htmlspecialchars($a['course_code'])?></p>
                            </div>
                            <div>
                                <div class="completion-rate <?=$cls?>"><?=$a['completion_rate']?>%</div>
                                <div class="stat-muted"><?=$a['submissions']?>/<?=$a['enrolled_students']?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="box">
            <h2>Recent User Activity</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>User</th><th>Role</th><th>Joined</th><th>Enrollments</th><th>Submissions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($user_activity as $u): ?>
                            <tr>
                                <td><?=htmlspecialchars($u['full_name'])?></td>
                                <td>
                                    <span class="badge <?= $u['role']==='teacher'?'badge-purple':'badge-blue'?>">
                                        <?=ucfirst($u['role'])?>
                                    </span>
                                </td>
                                <td><?=formatDate($u['created_at'])?></td>
                                <td><?=$u['enrollments_count']?></td>
                                <td><?=$u['submissions_count']?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="box">
            <h2>Monthly Registration Stats</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>Month</th><th>Students</th><th>Teachers</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($monthly_stats as $m): ?>
                            <tr>
                                <td><strong><?=date('M Y',strtotime($m['month'].'-01'))?></strong></td>
                                <td><?=$m['students']?></td>
                                <td><?=$m['teachers']?></td>
                                <td><strong><?=$m['total']?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        const data = <?php echo json_encode($monthly_stats); ?>;
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(d=> new Date(d.month+'-01')
                    .toLocaleDateString('en-US',{month:'short',year:'numeric'})),
                datasets: [{
                    label: 'Students',
                    data: data.map(d=>d.students),
                    borderColor: '#0066cc', backgroundColor: 'rgba(0,102,204,0.1)', tension:0.3
                },{
                    label: 'Teachers',
                    data: data.map(d=>d.teachers),
                    borderColor: '#6f42c1', backgroundColor: 'rgba(111,66,193,0.1)', tension:0.3
                }]
            },
            options: {
                scales: {
                    y:{beginAtZero:true,grid:{color:'#eee'},ticks:{color:'#666'}},
                    x:{grid:{display:false}}
                },
                plugins:{legend:{position:'top',labels:{boxWidth:12,usePointStyle:true}}},
                interaction:{mode:'index',intersect:false},
                elements:{point:{radius:3,hoverRadius:5}}
            }
        });
    </script>
</body>
</html>

<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireRole('admin');
$user = $auth->getCurrentUser();

// Get various statistics for reports
$course_stats = $db->fetchAll(
    "SELECT c.title, c.course_code, 
            COUNT(e.id) as enrollment_count,
            COUNT(a.id) as assignment_count,
            COUNT(s.id) as submission_count
     FROM courses c
     LEFT JOIN enrollments e ON c.id = e.course_id
     LEFT JOIN assignments a ON c.id = a.course_id
     LEFT JOIN submissions s ON a.id = s.assignment_id
     WHERE c.is_active = 1
     GROUP BY c.id
     ORDER BY enrollment_count DESC"
);

$user_activity = $db->fetchAll(
    "SELECT u.full_name, u.role, u.created_at,
            COUNT(CASE WHEN u.role = 'student' THEN s.id END) as submissions_count,
            COUNT(CASE WHEN u.role = 'student' THEN e.id END) as enrollments_count
     FROM users u
     LEFT JOIN enrollments e ON u.id = e.student_id
     LEFT JOIN submissions s ON u.id = s.student_id
     WHERE u.is_active = 1
     GROUP BY u.id
     ORDER BY u.created_at DESC
     LIMIT 20"
);

// Monthly registration statistics
$monthly_stats = $db->fetchAll(
    "SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(CASE WHEN role = 'student' THEN 1 END) as students,
        COUNT(CASE WHEN role = 'teacher' THEN 1 END) as teachers,
        COUNT(*) as total
     FROM users 
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
     GROUP BY DATE_FORMAT(created_at, '%Y-%m')
     ORDER BY month DESC"
);

// Assignment completion rates
$assignment_stats = $db->fetchAll(
    "SELECT a.title, c.course_code,
            COUNT(DISTINCT e.student_id) as enrolled_students,
            COUNT(s.id) as submissions,
            ROUND((COUNT(s.id) / COUNT(DISTINCT e.student_id)) * 100, 2) as completion_rate
     FROM assignments a
     JOIN courses c ON a.course_id = c.id
     JOIN enrollments e ON c.id = e.course_id
     LEFT JOIN submissions s ON a.id = s.assignment_id
     GROUP BY a.id
     HAVING enrolled_students > 0
     ORDER BY completion_rate DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Reports - Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            max-width: 1200px;
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
        .grid-2col {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .stat-card {
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin-bottom: 15px;
            background: #fff;
            border-radius: 0 4px 4px 0;
        }
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        .stat-title {
            font-weight: 600;
            margin: 0;
        }
        .stat-code {
            color: #666;
            font-size: 13px;
            margin: 4px 0 0;
        }
        .stat-numbers {
            text-align: right;
            font-size: 14px;
        }
        .stat-numbers div {
            margin-bottom: 4px;
        }
        .stat-primary { color: #0066cc; }
        .stat-success { color: #1e7e34; }
        .stat-muted { color: #666; }
        
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f8f9fa;
            text-align: left;
            padding: 12px;
            font-size: 13px;
            font-weight: 600;
            color: #666;
            border-bottom: 2px solid #ddd;
            white-space: nowrap;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-blue {
            background: #e6f3ff;
            color: #0066cc;
        }
        .badge-purple {
            background: #f3e8ff;
            color: #6f42c1;
        }
        .completion-rate {
            font-size: 18px;
            font-weight: bold;
        }
        .rate-high { color: #1e7e34; }
        .rate-medium { color: #ffc107; }
        .rate-low { color: #dc3545; }
        h1 { 
            font-size: 24px; 
            margin: 0 0 10px; 
        }
        h2 { 
            font-size: 20px; 
            margin: 0 0 15px; 
        }
        .subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar">
        <div class="brand">
            <div class="brand-icon">📊</div>
            System Reports
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
            <h1>System Reports & Analytics</h1>
            <div class="subtitle">Comprehensive overview of platform usage and performance</div>
        </div>

        <!-- Monthly Registration Chart -->
        <div class="box">
            <h2>Monthly User Registrations</h2>
            <div style="height: 300px; position: relative;">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>

        <div class="grid-2col">
            <!-- Course Statistics -->
            <div class="box">
                <h2>Course Performance</h2>
                <div>
                    <?php foreach(array_slice($course_stats, 0, 8) as $course): ?>
                        <div class="stat-card">
                            <div class="stat-header">
                                <div>
                                    <h4 class="stat-title"><?php echo htmlspecialchars($course['title']); ?></h4>
                                    <p class="stat-code"><?php echo htmlspecialchars($course['course_code']); ?></p>
                                </div>
                                <div class="stat-numbers">
                                    <div class="stat-primary"><?php echo $course['enrollment_count']; ?> enrolled</div>
                                    <div class="stat-muted"><?php echo $course['assignment_count']; ?> assignments</div>
                                    <div class="stat-success"><?php echo $course['submission_count']; ?> submissions</div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Assignment Completion Rates -->
            <div class="box">
                <h2>Assignment Completion Rates</h2>
                <div>
                    <?php foreach(array_slice($assignment_stats, 0, 8) as $assignment): ?>
                        <div class="stat-card">
                            <div class="stat-header">
                                <div>
                                    <h4 class="stat-title"><?php echo htmlspecialchars($assignment['title']); ?></h4>
                                    <p class="stat-code"><?php echo htmlspecialchars($assignment['course_code']); ?></p>
                                </div>
                                <div style="text-align: right;">
                                    <div class="completion-rate <?php echo $assignment['completion_rate'] >= 80 ? 'rate-high' : ($assignment['completion_rate'] >= 60 ? 'rate-medium' : 'rate-low'); ?>">
                                        <?php echo $assignment['completion_rate']; ?>%
                                    </div>
                                    <div class="stat-muted">
                                        <?php echo $assignment['submissions']; ?>/<?php echo $assignment['enrolled_students']; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- User Activity Table -->
        <div class="box">
            <h2>Recent User Activity</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Enrollments</th>
                            <th>Submissions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($user_activity as $activity): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($activity['full_name']); ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $activity['role'] === 'teacher' ? 'badge-purple' : 'badge-blue'; ?>">
                                        <?php echo ucfirst($activity['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="stat-muted"><?php echo formatDate($activity['created_at']); ?></span>
                                </td>
                                <td>
                                    <?php echo $activity['enrollments_count'] ?: '0'; ?>
                                </td>
                                <td>
                                    <?php echo $activity['submissions_count'] ?: '0'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Monthly Statistics Table -->
        <div class="box">
            <h2>Monthly Registration Statistics</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Students</th>
                            <th>Teachers</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($monthly_stats as $stat): ?>
                            <tr>
                                <td>
                                    <strong><?php echo date('F Y', strtotime($stat['month'] . '-01')); ?></strong>
                                </td>
                                <td style="color: #0066cc;">
                                    <?php echo $stat['students']; ?>
                                </td>
                                <td style="color: #6f42c1;">
                                    <?php echo $stat['teachers']; ?>
                                </td>
                                <td>
                                    <strong><?php echo $stat['total']; ?></strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Update Chart.js styles to match our design
        Chart.defaults.color = '#666';
        Chart.defaults.borderColor = '#eee';
        Chart.defaults.font.family = 'Arial, sans-serif';
        
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = <?php echo json_encode($monthly_stats); ?>;
        
        const chartData = {
            labels: monthlyData.map(item => {
                const date = new Date(item.month + '-01');
                return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            }).reverse(),
            datasets: [
                {
                    label: 'Students',
                    data: monthlyData.map(item => item.students).reverse(),
                    backgroundColor: 'rgba(0, 102, 204, 0.1)',
                    borderColor: '#0066cc',
                    borderWidth: 2,
                    tension: 0.3
                },
                {
                    label: 'Teachers',
                    data: monthlyData.map(item => item.teachers).reverse(),
                    backgroundColor: 'rgba(111, 66, 193, 0.1)',
                    borderColor: '#6f42c1',
                    borderWidth: 2,
                    tension: 0.3
                }
            ]
        };

        new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: '#666'
                        },
                        grid: {
                            color: '#eee'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                elements: {
                    point: {
                        radius: 3,
                        hoverRadius: 5
                    }
                }
            }
        });
    </script>

    <script>
        // Monthly Registration Chart
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = <?php echo json_encode($monthly_stats); ?>;
        
        const chartData = {
            labels: monthlyData.map(item => {
                const date = new Date(item.month + '-01');
                return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            }).reverse(),
            datasets: [
                {
                    label: 'Students',
                    data: monthlyData.map(item => item.students).reverse(),
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 2
                },
                {
                    label: 'Teachers',
                    data: monthlyData.map(item => item.teachers).reverse(),
                    backgroundColor: 'rgba(147, 51, 234, 0.5)',
                    borderColor: 'rgb(147, 51, 234)',
                    borderWidth: 2
                }
            ]
        };

        new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>

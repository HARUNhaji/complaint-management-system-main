<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Get admin data from session
$admin_name = $_SESSION['user_name'] ?? 'Admin';
$admin_email = $_SESSION['user_email'] ?? 'admin@example.com';

// Handle export requests - SEPARATE FROM MAIN PAGE
if (isset($_GET['export'])) {
    include('../config/db.php');
    
    // Fetch report data
    $report_sql = "SELECT c.id as complaint_id, c.title, c.category, c.status, c.created_at, 
                   u.fullname, u.email, u.phone 
                   FROM complaints c 
                   JOIN users u ON c.student_id = u.id 
                   ORDER BY c.created_at DESC";
    $report_result = mysqli_query($conn, $report_sql);
    
    // Fetch statistics
    $total_sql = "SELECT COUNT(*) as total FROM complaints";
    $total_result = mysqli_query($conn, $total_sql);
    $total_row = mysqli_fetch_assoc($total_result);
    $total_complaints = $total_row['total'];
    
    $resolved_sql = "SELECT COUNT(*) as resolved FROM complaints WHERE status = 'Resolved' OR status = 'resolved'";
    $resolved_result = mysqli_query($conn, $resolved_sql);
    $resolved_row = mysqli_fetch_assoc($resolved_result);
    $resolved_complaints = $resolved_row['resolved'];
    
    $pending_sql = "SELECT COUNT(*) as pending FROM complaints WHERE status = 'Pending' OR status = 'pending'";
    $pending_result = mysqli_query($conn, $pending_sql);
    $pending_row = mysqli_fetch_assoc($pending_result);
    $pending_complaints = $pending_row['pending'];
    
    $in_review_sql = "SELECT COUNT(*) as in_review FROM complaints WHERE status = 'In Review' OR status = 'in review'";
    $in_review_result = mysqli_query($conn, $in_review_sql);
    $in_review_row = mysqli_fetch_assoc($in_review_result);
    $in_review_complaints = $in_review_row['in_review'];
    
    $export_type = $_GET['export'];
    
    if ($export_type == 'pdf') {
        // Generate HTML for PDF
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <title>Complaint Management System - Report</title>
            <meta charset="UTF-8">
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                }
                
                body {
                    background-color: #f8f9fa;
                    color: #333;
                    line-height: 1.6;
                    padding: 30px;
                }
                
                .container {
                    max-width: 1200px;
                    margin: 0 auto;
                    background: white;
                    padding: 40px;
                    border-radius: 15px;
                    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
                }
                
                .header {
                    text-align: center;
                    margin-bottom: 40px;
                    padding-bottom: 20px;
                    border-bottom: 3px solid #78FECF;
                }
                
                .header h1 {
                    color: #2c3e50;
                    font-size: 32px;
                    margin-bottom: 10px;
                    font-weight: 700;
                }
                
                .header h2 {
                    color: #3498db;
                    font-size: 24px;
                    font-weight: 500;
                    margin-bottom: 20px;
                }
                
                .report-info {
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 10px;
                    margin-bottom: 30px;
                    border-left: 5px solid #3498db;
                }
                
                .report-info p {
                    margin: 5px 0;
                    color: #555;
                }
                
                .stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 20px;
                    margin: 30px 0;
                }
                
                .stat-card {
                    background: white;
                    padding: 25px;
                    border-radius: 12px;
                    text-align: center;
                    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
                    transition: transform 0.3s ease;
                    border-top: 4px solid;
                }
                
                .stat-card:hover {
                    transform: translateY(-5px);
                }
                
                .stat-card.total {
                    border-top-color: #3498db;
                }
                
                .stat-card.resolved {
                    border-top-color: #27ae60;
                }
                
                .stat-card.pending {
                    border-top-color: #f39c12;
                }
                
                .stat-card.review {
                    border-top-color: #9b59b6;
                }
                
                .stat-card h3 {
                    font-size: 16px;
                    color: #666;
                    margin-bottom: 10px;
                    font-weight: 600;
                }
                
                .stat-number {
                    font-size: 36px;
                    font-weight: 700;
                    color: #2c3e50;
                }
                
                .section-title {
                    font-size: 22px;
                    color: #2c3e50;
                    margin: 40px 0 20px 0;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #eee;
                }
                
                .table-container {
                    overflow-x: auto;
                    margin: 30px 0;
                    border-radius: 10px;
                    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
                }
                
                table {
                    width: 100%;
                    border-collapse: collapse;
                    min-width: 800px;
                }
                
                thead {
                    background: linear-gradient(135deg, #78FECF, #3498db);
                    color: white;
                }
                
                th {
                    padding: 15px;
                    text-align: left;
                    font-weight: 600;
                    font-size: 14px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                
                td {
                    padding: 15px;
                    border-bottom: 1px solid #eee;
                    font-size: 14px;
                }
                
                tbody tr {
                    transition: background-color 0.2s;
                }
                
                tbody tr:hover {
                    background-color: #f8f9fa;
                }
                
                tbody tr:nth-child(even) {
                    background-color: #fafafa;
                }
                
                tbody tr:nth-child(even):hover {
                    background-color: #f1f1f1;
                }
                
                .status-badge {
                    display: inline-block;
                    padding: 5px 15px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                
                .status-resolved {
                    background-color: #d4edda;
                    color: #155724;
                }
                
                .status-pending {
                    background-color: #fff3cd;
                    color: #856404;
                }
                
                .status-review {
                    background-color: #d1ecf1;
                    color: #0c5460;
                }
                
                .footer {
                    text-align: center;
                    margin-top: 50px;
                    padding-top: 20px;
                    border-top: 1px solid #eee;
                    color: #666;
                    font-size: 12px;
                }
                
                .logo {
                    text-align: center;
                    margin-bottom: 20px;
                }
                
                .logo h1 {
                    color: #2c3e50;
                    font-size: 28px;
                    margin-bottom: 5px;
                }
                
                .logo .subtitle {
                    color: #3498db;
                    font-size: 14px;
                    text-transform: uppercase;
                    letter-spacing: 2px;
                }
                
                @media print {
                    body {
                        padding: 0;
                    }
                    
                    .container {
                        box-shadow: none;
                        padding: 20px;
                    }
                    
                    .stat-card:hover {
                        transform: none;
                    }
                    
                    tbody tr:hover {
                        background-color: transparent;
                    }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="logo">
                    <h1>Complaint Management System</h1>
                    <div class="subtitle">Official Report</div>
                </div>
                
                <div class="header">
                    <h2>Complaint Analysis Report</h2>
                </div>
                
                <div class="report-info">
                    <p><strong>Report Generated:</strong> ' . date('F j, Y \a\t g:i A') . '</p>
                    <p><strong>Generated By:</strong> ' . htmlspecialchars($admin_name) . '</p>
                    <p><strong>Report Period:</strong> All Time</p>
                </div>
                
                <h3 class="section-title">📊 Quick Statistics</h3>
                <div class="stats-grid">
                    <div class="stat-card total">
                        <h3>Total Complaints</h3>
                        <div class="stat-number">' . $total_complaints . '</div>
                    </div>
                    <div class="stat-card resolved">
                        <h3>Resolved</h3>
                        <div class="stat-number">' . $resolved_complaints . '</div>
                    </div>
                    <div class="stat-card pending">
                        <h3>Pending</h3>
                        <div class="stat-number">' . $pending_complaints . '</div>
                    </div>
                    <div class="stat-card review">
                        <h3>In Review</h3>
                        <div class="stat-number">' . $in_review_complaints . '</div>
                    </div>
                </div>
                
                <h3 class="section-title">📋 Complaint Details</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Complaint ID</th>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>';
        
        if ($report_result && mysqli_num_rows($report_result) > 0) {
            while ($row = mysqli_fetch_assoc($report_result)) {
                $status_class = '';
                $status_text = ucfirst($row['status']);
                if (strtolower($row['status']) == 'resolved') {
                    $status_class = 'status-resolved';
                } elseif (strtolower($row['status']) == 'pending') {
                    $status_class = 'status-pending';
                } elseif (strtolower($row['status']) == 'in review') {
                    $status_class = 'status-review';
                } else {
                    $status_class = 'status-pending';
                }
                
                $html .= '<tr>
                    <td><strong>#CMP' . $row['complaint_id'] . '</strong></td>
                    <td>' . htmlspecialchars($row['fullname']) . '</td>
                    <td>' . htmlspecialchars($row['email']) . '</td>
                    <td>' . htmlspecialchars($row['phone'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($row['title']) . '</td>
                    <td>' . htmlspecialchars(ucfirst($row['category'])) . '</td>
                    <td><span class="status-badge ' . $status_class . '">' . $status_text . '</span></td>
                    <td>' . date('M d, Y', strtotime($row['created_at'])) . '</td>
                </tr>';
            }
        } else {
            $html .= '<tr>
                <td colspan="8" style="text-align: center; padding: 30px; color: #666;">
                    <div style="font-size: 16px; margin-bottom: 10px;">📭</div>
                    No complaints found in the system
                </td>
            </tr>';
        }
        
        $html .= '</tbody>
                    </table>
                </div>
                
                <div class="footer">
                    <p>© ' . date('Y') . ' Complaint Management System. All rights reserved.</p>
                    <p>This is an official system-generated report. For any queries, contact system administrator.</p>
                    <p style="margin-top: 10px; font-size: 10px; color: #999;">Report ID: CMS-' . date('Ymd-His') . '-' . rand(1000, 9999) . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="Complaint_Report_' . date('Y-m-d') . '.html"');
        echo $html;
        exit();
        
    } elseif ($export_type == 'excel') {
        // Generate Excel
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="Complaint_Report_' . date('Y-m-d') . '.xls"');
        
        echo '<html><head><meta charset="UTF-8"></head><body>';
        echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
        echo '<tr style="background-color: #78FECF; font-weight: bold; text-align: center;">';
        echo '<th colspan="8" style="font-size: 16px; padding: 15px;">Complaint Management System - Report</th>';
        echo '</tr>';
        echo '<tr style="background-color: #f2f2f2;">';
        echo '<td colspan="8" style="padding: 10px;">';
        echo '<strong>Generated:</strong> ' . date('Y-m-d H:i:s') . ' | ';
        echo '<strong>By:</strong> ' . $admin_name . ' | ';
        echo '<strong>Total Complaints:</strong> ' . $total_complaints;
        echo '</td>';
        echo '</tr>';
        echo '<tr style="background-color: #3498db; color: white; font-weight: bold;">';
        echo '<th>Complaint ID</th>';
        echo '<th>Student Name</th>';
        echo '<th>Email</th>';
        echo '<th>Phone</th>';
        echo '<th>Title</th>';
        echo '<th>Category</th>';
        echo '<th>Status</th>';
        echo '<th>Date</th>';
        echo '</tr>';
        
        if ($report_result) {
            while ($row = mysqli_fetch_assoc($report_result)) {
                echo '<tr>';
                echo '<td>#CMP' . $row['complaint_id'] . '</td>';
                echo '<td>' . $row['fullname'] . '</td>';
                echo '<td>' . $row['email'] . '</td>';
                echo '<td>' . ($row['phone'] ?? 'N/A') . '</td>';
                echo '<td>' . $row['title'] . '</td>';
                echo '<td>' . $row['category'] . '</td>';
                echo '<td>' . ucfirst($row['status']) . '</td>';
                echo '<td>' . date('Y-m-d', strtotime($row['created_at'])) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="8" style="text-align: center; padding: 20px;">No complaints found</td></tr>';
        }
        
        echo '<tr style="background-color: #f2f2f2; font-style: italic;">';
        echo '<td colspan="8" style="text-align: center; padding: 10px; font-size: 12px;">';
        echo '© ' . date('Y') . ' Complaint Management System. Generated on ' . date('Y-m-d H:i:s');
        echo '</td>';
        echo '</tr>';
        echo '</table>';
        echo '</body></html>';
        exit();
    }
    
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Complaint Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-accent: #78FECF;
            --background-base: #E5EAFA;
            --success-text: #093824;
            --secondary: #C6CCB2;
            --error-state: #BF4E30;
        }
        
        body {
            background-color: var(--background-base);
            overflow-x: hidden;
        }
        
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #1e293b;
            color: white;
            transition: all 0.3s;
            z-index: 100;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar.collapsed {
            width: 70px;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #374151;
        }
        
        .sidebar-user {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .sidebar-user img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .sidebar-user-info {
            flex: 1;
        }
        
        .sidebar-user-name {
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .sidebar-user-email {
            font-size: 0.7rem;
            color: #94a3b8;
        }
        
        .sidebar-menu {
            padding: 10px 0;
        }
        
        .sidebar-menu-item {
            padding: 12px 20px;
            color: #e2e8f0;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }
        
        .sidebar-menu-item:hover, .sidebar-menu-item.active {
            background: #334155;
            color: white;
        }
        
        .sidebar-menu-item i {
            margin-right: 10px;
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }
        
        .sidebar-menu-item span {
            transition: opacity 0.3s;
        }
        
        .sidebar.collapsed .sidebar-menu-item span {
            opacity: 0;
            width: 0;
            height: 0;
            overflow: hidden;
        }
        
        .sidebar-toggle {
            position: absolute;
            right: -12px;
            top: 50%;
            background: #1e293b;
            border: 2px solid white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 101;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .sidebar.collapsed + .main-content {
            margin-left: 70px;
        }
        
        .report-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card-stat {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s;
        }
        
        .card-stat:hover {
            transform: translateY(-5px);
        }
        
        .card-header-stat {
            background: var(--primary-accent);
            color: #000;
            font-weight: 600;
            padding: 15px;
        }
        
        .card-body-stat {
            padding: 20px;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--success-text);
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .filters {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .btn-download {
            background: var(--primary-accent);
            border: none;
            color: #000;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 8px;
        }
        
        .btn-download:hover {
            background: #67e8b8;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-menu-item span {
                opacity: 0;
                width: 0;
                height: 0;
                overflow: hidden;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Reports & Analytics</h1>
            <div class="d-flex">
                <!-- Date filters -->
                <div class="me-2">
                    <input type="date" id="startDate" class="form-control" placeholder="From Date">
                </div>
                <div class="me-2">
                    <input type="date" id="endDate" class="form-control" placeholder="To Date">
                </div>
                <button class="btn btn-outline-secondary me-2" onclick="applyFilters()">Apply Filters</button>
                
                <!-- Export Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-download dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-download me-1"></i>Export
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="?export=pdf"><i class="bi bi-file-earmark-pdf me-2"></i>Download PDF (HTML)</a></li>
                        <li><a class="dropdown-item" href="?export=excel"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Download Excel</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Report Cards -->
        <?php
        include('../config/db.php');

        // Get complaint counts
        $total_sql = "SELECT COUNT(*) as count FROM complaints";
        $total_result = mysqli_query($conn, $total_sql);
        $total_row = $total_result ? mysqli_fetch_assoc($total_result) : ['count' => 0];
        $total_complaints = $total_row['count'];

        $resolved_sql = "SELECT COUNT(*) as count FROM complaints WHERE status = 'Resolved' OR status = 'resolved'";
        $resolved_result = mysqli_query($conn, $resolved_sql);
        $resolved_row = $resolved_result ? mysqli_fetch_assoc($resolved_result) : ['count' => 0];
        $resolved_complaints = $resolved_row['count'];

        $pending_sql = "SELECT COUNT(*) as count FROM complaints WHERE status = 'Pending' OR status = 'pending'";
        $pending_result = mysqli_query($conn, $pending_sql);
        $pending_row = $pending_result ? mysqli_fetch_assoc($pending_result) : ['count' => 0];
        $pending_complaints = $pending_row['count'];

        $in_review_sql = "SELECT COUNT(*) as count FROM complaints WHERE status = 'In Review' OR status = 'in review'";
        $in_review_result = mysqli_query($conn, $in_review_sql);
        $in_review_row = $in_review_result ? mysqli_fetch_assoc($in_review_result) : ['count' => 0];
        $in_review_complaints = $in_review_row['count'];

        $students_sql = "SELECT COUNT(*) as count FROM users WHERE role = 'student'";
        $students_result = mysqli_query($conn, $students_sql);
        $students_row = $students_result ? mysqli_fetch_assoc($students_result) : ['count' => 0];
        $total_students = $students_row['count'];

        // Get data for charts
        $status_data_sql = "SELECT status, COUNT(*) as count FROM complaints GROUP BY status";
        $status_data_result = mysqli_query($conn, $status_data_sql);
        $status_labels = [];
        $status_counts = [];
        if ($status_data_result) {
            while ($row = mysqli_fetch_assoc($status_data_result)) {
                $status_labels[] = $row['status'];
                $status_counts[] = $row['count'];
            }
        }

        $category_data_sql = "SELECT category, COUNT(*) as count FROM complaints GROUP BY category";
        $category_data_result = mysqli_query($conn, $category_data_sql);
        $category_labels = [];
        $category_counts = [];
        if ($category_data_result) {
            while ($row = mysqli_fetch_assoc($category_data_result)) {
                $category_labels[] = $row['category'];
                $category_counts[] = $row['count'];
            }
        }

        $user_growth_sql = "SELECT DATE(created_at) as date, COUNT(*) as count FROM users WHERE role = 'student' GROUP BY DATE(created_at) ORDER BY created_at ASC";
        $user_growth_result = mysqli_query($conn, $user_growth_sql);
        $user_dates = [];
        $user_counts = [];
        if ($user_growth_result) {
            while ($row = mysqli_fetch_assoc($user_growth_result)) {
                $user_dates[] = $row['date'];
                $user_counts[] = $row['count'];
            }
        }

        mysqli_close($conn);
        ?>

        <!-- Report Cards -->
        <div class="report-cards">
            <div class="card card-stat">
                <div class="card-header card-header-stat">
                    <i class="bi bi-journal-text me-2"></i>Total Complaints
                </div>
                <div class="card-body card-body-stat">
                    <div class="stat-number"><?php echo $total_complaints; ?></div>
                    <div class="stat-label">All complaints</div>
                </div>
            </div>

            <div class="card card-stat">
                <div class="card-header card-header-stat" style="background-color: #90EE90; color: #000;">
                    <i class="bi bi-check-circle me-2"></i>Resolved Complaints
                </div>
                <div class="card-body card-body-stat">
                    <div class="stat-number"><?php echo $resolved_complaints; ?></div>
                    <div class="stat-label">Successfully resolved</div>
                </div>
            </div>

            <div class="card card-stat">
                <div class="card-header card-header-stat" style="background-color: #FFEC8B; color: #000;">
                    <i class="bi bi-clock-history me-2"></i>Pending Complaints
                </div>
                <div class="card-body card-body-stat">
                    <div class="stat-number"><?php echo $pending_complaints; ?></div>
                    <div class="stat-label">Awaiting action</div>
                </div>
            </div>

            <div class="card card-stat">
                <div class="card-header card-header-stat" style="background-color: #ADD8E6; color: #000;">
                    <i class="bi bi-people me-2"></i>Total Students
                </div>
                <div class="card-body card-body-stat">
                    <div class="stat-number"><?php echo $total_students; ?></div>
                    <div class="stat-label">Active students</div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row">
            <div class="col-lg-6">
                <div class="chart-container">
                    <h5 class="mb-4">Complaints by Status</h5>
                    <canvas id="statusChart" width="400" height="200"></canvas>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-container">
                    <h5 class="mb-4">Complaints by Category</h5>
                    <canvas id="categoryChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="chart-container">
                    <h5 class="mb-4">Total Users Over Time</h5>
                    <canvas id="userGrowthChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Reports Table -->
        <div class="card rounded-3 shadow-sm mt-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="bi bi-table me-2"></i>Complaint Reports</h5>
            </div>
            <div class="card-body">
                <?php
                include('../config/db.php');
                // Fetch report data
                $report_sql = "SELECT c.id as complaint_id, c.title, c.category, c.status, c.created_at, u.fullname FROM complaints c JOIN users u ON c.student_id = u.id ORDER BY c.created_at DESC LIMIT 20";
                $report_result = mysqli_query($conn, $report_sql);
                ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Complaint ID</th>
                                <th>Student Name</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($report_result): ?>
                            <?php while ($row = mysqli_fetch_assoc($report_result)): ?>
                            <tr>
                                <td>#CMP<?php echo $row['complaint_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td>
                                    <?php 
                                    $status = strtolower($row['status']);
                                    if ($status == 'resolved'): 
                                    ?>
                                        <span class="badge bg-success">Resolved</span>
                                    <?php elseif ($status == 'pending'): ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php elseif ($status == 'in review'): ?>
                                        <span class="badge bg-info">In Review</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo ucfirst($status); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Error loading complaints</td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($report_result && mysqli_num_rows($report_result) == 0): ?>
                            <tr>
                                <td colspan="6" class="text-center">No complaints found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php
                mysqli_close($conn);
                ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('collapsed');
        });

        // Apply filters function
        function applyFilters() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            if (startDate || endDate) {
                alert('Filter functionality will be implemented soon!');
                // You can implement AJAX call here to filter data
            } else {
                alert('Please select date range to filter');
            }
        }

        // Charts
        // Complaints by Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($status_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($status_counts); ?>,
                    backgroundColor: [
                        '#FFEC8B',
                        '#ADD8E6',
                        '#90EE90',
                        '#DDA0DD'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Complaints by Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($category_labels); ?>,
                datasets: [{
                    label: 'Number of Complaints',
                    data: <?php echo json_encode($category_counts); ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 205, 86, 0.5)',
                        'rgba(75, 192, 192, 0.5)',
                        'rgba(153, 102, 255, 0.5)'
                    ],
                    borderColor: [
                        'rgb(255, 99, 132)',
                        'rgb(54, 162, 235)',
                        'rgb(255, 205, 86)',
                        'rgb(75, 192, 192)',
                        'rgb(153, 102, 255)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // User Growth Chart
        const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
        const userGrowthChart = new Chart(userGrowthCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($user_dates); ?>,
                datasets: [{
                    label: 'Total Users',
                    data: <?php echo json_encode($user_counts); ?>,
                    fill: false,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
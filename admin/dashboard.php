<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../config/db_connect.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // Log the attempt to access the page without proper authentication
    error_log("Unauthorized access attempt to admin dashboard. User ID: " . ($_SESSION['user_id'] ?? 'Not set') . ", Role: " . ($_SESSION['user_role'] ?? 'Not set'));
    
    // Redirect to the login page
    header("Location: ../index.php?error=Unauthorized");
    exit();
}

// Log successful entry into the dashboard
error_log("Admin dashboard accessed by User ID: " . $_SESSION['user_id']);


// Fetch Dashboard Data
$total_users_query = "SELECT COUNT(*) AS count FROM users WHERE role = 'user'";
$total_users_result = $conn->query($total_users_query);
if ($total_users_result) {
    $total_users = $total_users_result->fetch_assoc()['count'];
    error_log("Total users: $total_users");
} else {
    error_log("Error fetching total users: " . $conn->error);
    $total_users = 0;
}

$total_departments_query = "SELECT COUNT(*) AS count FROM departments";
$total_departments_result = $conn->query($total_departments_query);
if ($total_departments_result) {
    $total_departments = $total_departments_result->fetch_assoc()['count'];
    error_log("Total departments: $total_departments");
} else {
    error_log("Error fetching total departments: " . $conn->error);
    $total_departments = 0;
}

$total_courses_query = "SELECT COUNT(*) AS count FROM courses";
$total_courses_result = $conn->query($total_courses_query);
if ($total_courses_result) {
    $total_courses = $total_courses_result->fetch_assoc()['count'];
    error_log("Total courses: $total_courses");
} else {
    error_log("Error fetching total courses: " . $conn->error);
    $total_courses = 0;
}

$scheduled_exams_query = "SELECT COUNT(*) AS count FROM exam_schedules";
$scheduled_exams_result = $conn->query($scheduled_exams_query);
if ($scheduled_exams_result) {
    $scheduled_exams_count = $scheduled_exams_result->fetch_assoc()['count'];
    error_log("Scheduled exams: $scheduled_exams_count");
} else {
    error_log("Error fetching scheduled exams: " . $conn->error);
    $scheduled_exams_count = 0;
}

// --- Chart Data Fetching --- 
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : '';
$filter_department = isset($_GET['filter_department']) ? $_GET['filter_department'] : '';
$filter_user = isset($_GET['filter_user']) ? $_GET['filter_user'] : '';

$chart_sql = "SELECT u.name AS user_name, er.score, er.submitted_at, d.name AS department_name
              FROM exam_results er
              JOIN users u ON er.user_id = u.id
              LEFT JOIN departments d ON u.department_id = d.id
              WHERE 1=1";

$params = [];
$types = '';

if (!empty($filter_month)) {
    $chart_sql .= " AND DATE_FORMAT(er.submitted_at, '%Y-%m') = ?";
    $params[] = $filter_month;
    $types .= 's';
}
if (!empty($filter_department)) {
    $chart_sql .= " AND u.department_id = ?";
    $params[] = $filter_department;
    $types .= 'i';
}
if (!empty($filter_user)) {
    $chart_sql .= " AND er.user_id = ?";
    $params[] = $filter_user;
    $types .= 'i';
}

$chart_sql .= " ORDER BY er.submitted_at ASC";

$chart_stmt = $conn->prepare($chart_sql);
if ($chart_stmt === false) {
    error_log("Error preparing chart query: " . $conn->error);
} else {
    if ($params) {
        $chart_stmt->bind_param($types, ...$params);
    }
    $chart_stmt->execute();
    $chart_result = $chart_stmt->get_result();
    error_log("Chart query executed successfully.");
}

$chart_labels = [];
$chart_data = [];

while ($row = $chart_result->fetch_assoc()) {
    $chart_labels[] = $row['user_name'] . " (" . date('M d, Y', strtotime($row['submitted_at'])) . ")";
    $chart_data[] = $row['score'];
}

// Fetch data for filter dropdowns
$departments_filter_result = $conn->query("SELECT * FROM departments ORDER BY name");
$users_filter_result = $conn->query("SELECT id, name FROM users WHERE role = 'user' ORDER BY name");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <h2>Admin Panel</h2>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_departments.php">Manage Departments</a></li>
                <li><a href="manage_users.php">Manage Users</a></li>
                <li><a href="manage_courses.php">Manage Courses</a></li>
                <li><a href="manage_exams.php">Manage Exams</a></li>
                <li><a href="assign_courses.php">Assign Courses</a></li>
                <li><a href="schedule_exams.php">Schedule Exams</a></li>
                <li><a href="exam_reports.php">Exam Reports</a></li>
                <li><a href="course_status_report.php">Course Status Report</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
        <div class="main-content">
            <header>
                <div class="app-header">
                    <h1>Poonam Management Training and Online Exam Module</h1>
                </div>
                <h2>Welcome, <?php echo $_SESSION['user_name']; ?>!</h2>
            </header>
            <main>
                <h3>Dashboard Overview</h3>
                <div class="dashboard-widgets">
                    <div class="widget total-users">
                        <h4>Total Users</h4>
                        <p><?php echo $total_users; ?></p>
                    </div>
                    <div class="widget total-departments">
                        <h4>Total Departments</h4>
                        <p><?php echo $total_departments; ?></p>
                    </div>
                    <div class="widget total-courses">
                        <h4>Total Courses</h4>
                        <p><?php echo $total_courses; ?></p>
                    </div>
                    <div class="widget scheduled-exams">
                        <h4>Scheduled Exams</h4>
                        <p><?php echo $scheduled_exams_count; ?></p>
                    </div>
                </div>

                <div class="chart-section">
                    <h3>Exam Scores Bar Chart</h3>
                    <div class="filter-form">
                        <form action="" method="get">
                            <label for="filter_month">Month:</label>
                            <input type="month" id="filter_month" name="filter_month" value="<?php echo htmlspecialchars($filter_month); ?>">
                            
                            <label for="filter_department">Department:</label>
                            <select id="filter_department" name="filter_department">
                                <option value="">All Departments</option>
                                <?php while ($row = $departments_filter_result->fetch_assoc()): ?>
                                <option value="<?php echo $row['id']; ?>" <?php if ($filter_department == $row['id']) echo 'selected'; ?>><?php echo $row['name']; ?></option>
                                <?php endwhile; ?>
                            </select>

                            <label for="filter_user">User:</label>
                            <select id="filter_user" name="filter_user">
                                <option value="">All Users</option>
                                <?php while ($row = $users_filter_result->fetch_assoc()): ?>
                                <option value="<?php echo $row['id']; ?>" <?php if ($filter_user == $row['id']) echo 'selected'; ?>><?php echo $row['name']; ?></option>
                                <?php endwhile; ?>
                            </select>

                            <button type="submit">Apply Filters</button>
                        </form>
                    </div>
                    <canvas id="examScoresChart"></canvas>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('examScoresChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($chart_labels); ?>,
                        datasets: [{
                            label: 'Exam Score',
                            data: <?php echo json_encode($chart_data); ?>,
                            backgroundColor: 'rgba(75, 192, 192, 0.6)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            } else {
                console.error('Element with ID \'examScoresChart\' not found.');
            }
        });
    </script>
<?php 
// Log that the page has finished rendering
error_log("Admin dashboard rendered successfully for User ID: " . $_SESSION['user_id']);
include '../config/footer.php'; 
?>
</body>
</html>
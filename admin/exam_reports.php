<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Build the base query
$sql = "SELECT exam_results.*, users.name AS user_name, users.email, exams.title AS exam_title, departments.name AS department_name 
        FROM exam_results 
        JOIN users ON exam_results.user_id = users.id 
        JOIN exams ON exam_results.exam_id = exams.id
        LEFT JOIN departments ON users.department_id = departments.id WHERE 1=1";

$params = [];
$types = '';

// Apply filters
if (!empty($_GET['department_id'])) {
    $sql .= " AND users.department_id = ?";
    $params[] = $_GET['department_id'];
    $types .= 'i';
}
if (!empty($_GET['user_id'])) {
    $sql .= " AND exam_results.user_id = ?";
    $params[] = $_GET['user_id'];
    $types .= 'i';
}
if (!empty($_GET['exam_id'])) {
    $sql .= " AND exam_results.exam_id = ?";
    $params[] = $_GET['exam_id'];
    $types .= 'i';
}

$sql .= " ORDER BY submitted_at DESC";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$results = $stmt->get_result();

// Fetch data for filters
$departments_result = $conn->query("SELECT * FROM departments ORDER BY name");
$users_result = $conn->query("SELECT id, name FROM users WHERE role = 'user' ORDER BY name");
$exams_result = $conn->query("SELECT * FROM exams ORDER BY title");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Reports</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
        <div class="main-content">
            <header>
                <h2>Exam Reports</h2>
            </header>
            <main>
                <div class="filter-form">
                    <form action="" method="get">
                        <select name="department_id">
                            <option value="">Filter by Department</option>
                            <?php while ($row = $departments_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>" <?php if (isset($_GET['department_id']) && $_GET['department_id'] == $row['id']) echo 'selected'; ?>><?php echo $row['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                        <select name="user_id">
                            <option value="">Filter by User</option>
                            <?php while ($row = $users_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>" <?php if (isset($_GET['user_id']) && $_GET['user_id'] == $row['id']) echo 'selected'; ?>><?php echo $row['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                        <select name="exam_id">
                            <option value="">Filter by Exam</option>
                            <?php while ($row = $exams_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>" <?php if (isset($_GET['exam_id']) && $_GET['exam_id'] == $row['id']) echo 'selected'; ?>><?php echo $row['title']; ?></option>
                            <?php endwhile; ?>
                        </select>
                        <button type="submit">Filter</button>
                    </form>
                </div>
                <div class="report-table">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Exam</th>
                                <th>Score (%)</th>
                                <th>Date Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $results->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['user_name']; ?></td>
                                <td><?php echo $row['email']; ?></td>
                                <td><?php echo $row['department_name'] ?? 'N/A'; ?></td>
                                <td><?php echo $row['exam_title']; ?></td>
                                <td><?php echo $row['score']; ?></td>
                                <td><?php echo $row['submitted_at']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
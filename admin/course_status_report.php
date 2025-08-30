<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php?error=Unauthorized");
    exit();
}

// Handle search and filter
$search_user = $_GET['search_user'] ?? '';
$filter_course = $_GET['filter_course'] ?? '';

$sql = "SELECT u.name AS user_name, u.email, c.title AS course_title, uc.status 
        FROM user_courses uc 
        JOIN users u ON uc.user_id = u.id
        JOIN courses c ON uc.course_id = c.id
        WHERE u.role = 'user'";

$params = [];
$types = '';

if (!empty($search_user)) {
    $sql .= " AND u.email LIKE ?";
    $params[] = "%" . $search_user . "%";
    $types .= 's';
}

if (!empty($filter_course)) {
    $sql .= " AND c.id = ?";
    $params[] = $filter_course;
    $types .= 'i';
}

$sql .= " ORDER BY u.name, c.title";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$report_result = $stmt->get_result();

// Fetch all courses for the filter dropdown
$courses_sql = "SELECT id, title FROM courses ORDER BY title";
$courses_result = $conn->query($courses_sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Status Report</title>
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
                <li><a href="course_status_report.php">Course Status Report</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
        <div class="main-content">
            <header>
                <h2>Course Status Report</h2>
            </header>
            <main>
                <div class="filter-form">
                    <form action="" method="get">
                        <input type="text" name="search_user" placeholder="Search by user email..." value="<?php echo htmlspecialchars($search_user); ?>">
                        <select name="filter_course">
                            <option value="">All Courses</option>
                            <?php while ($course = $courses_result->fetch_assoc()): ?>
                                <option value="<?php echo $course['id']; ?>" <?php if ($filter_course == $course['id']) echo 'selected'; ?>><?php echo htmlspecialchars($course['title']); ?></option>
                            <?php endwhile; ?>
                        </select>
                        <button type="submit">Filter</button>
                    </form>
                </div>
                <div class="report-table">
                    <table>
                        <thead>
                            <tr>
                                <th>User Name</th>
                                <th>User Email</th>
                                <th>Course Title</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($report_result->num_rows > 0): ?>
                                <?php while ($row = $report_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['course_title']); ?></td>
                                        <td>
                                            <span class="status-<?php echo htmlspecialchars($row['status']); ?>">
                                                <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">No records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
</body>
</html>

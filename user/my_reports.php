<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'user') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Build the base query
$sql = "SELECT exam_results.*, exams.title AS exam_title 
        FROM exam_results 
        JOIN exams ON exam_results.exam_id = exams.id 
        WHERE exam_results.user_id = ?";

$params = [$user_id];
$types = 'i';

// Apply filter
if (!empty($_GET['exam_id'])) {
    $sql .= " AND exam_results.exam_id = ?";
    $params[] = $_GET['exam_id'];
    $types .= 'i';
}

$sql .= " ORDER BY submitted_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$results = $stmt->get_result();

// Fetch data for exam filter
$exams_filter_result = $conn->query("SELECT * FROM exams ORDER BY title");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Exam Reports</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <h2>User Panel</h2>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="my_courses.php">My Courses</a></li>
                <li><a href="my_exams.php">My Exams</a></li>
                <li><a href="my_reports.php">My Reports</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
        <div class="main-content">
            <header>
                <h2>My Exam Reports</h2>
            </header>
            <main>
                <div class="filter-form">
                    <form action="" method="get">
                        <select name="exam_id">
                            <option value="">Filter by Exam</option>
                            <?php while ($row = $exams_filter_result->fetch_assoc()): ?>
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
                                <th>Exam Title</th>
                                <th>Score (%)</th>
                                <th>Date Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $results->fetch_assoc()): ?>
                            <tr>
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
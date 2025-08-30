<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$exam_id = $_GET['id'];

// Update Exam
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_exam'])) {
    $title = $_POST['title'];
    $duration = $_POST['duration'];

    $sql = "UPDATE exams SET title = ?, duration_in_minutes = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $title, $duration, $exam_id);
    $stmt->execute();
    header("Location: manage_exams.php");
    exit();
}

// Fetch Exam
$sql = "SELECT * FROM exams WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$result = $stmt->get_result();
$exam = $result->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Exam</title>
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
                <li><a href="exam_reports.php">Exam Reports</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
        <div class="main-content">
            <header>
                <h2>Edit Exam</h2>
            </header>
            <main>
                <div class="edit-exam-form">
                    <form action="" method="post">
                        <input type="text" name="title" value="<?php echo $exam['title']; ?>" required>
                        <input type="number" name="duration" value="<?php echo $exam['duration_in_minutes']; ?>" required>
                        <button type="submit" name="update_exam">Update Exam</button>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
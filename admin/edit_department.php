<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$department_id = $_GET['id'];

// Update Department
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_department'])) {
    $department_name = $_POST['department_name'];
    $sql = "UPDATE departments SET name = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $department_name, $department_id);
    $stmt->execute();
    header("Location: manage_departments.php");
    exit();
}

// Fetch Department
$sql = "SELECT * FROM departments WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();
$department = $result->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Department</title>
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
                <h2>Edit Department</h2>
            </header>
            <main>
                <div class="edit-department-form">
                    <form action="" method="post">
                        <input type="text" name="department_name" value="<?php echo $department['name']; ?>" required>
                        <button type="submit" name="update_department">Update Department</button>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
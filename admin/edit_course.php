<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$course_id = $_GET['id'];

// Update Course
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_course'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category = $_POST['category'];

    $sql = "UPDATE courses SET title = ?, description = ?, category = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $title, $description, $category, $course_id);
    $stmt->execute();
    header("Location: manage_courses.php");
    exit();
}

// Fetch Course
$sql = "SELECT * FROM courses WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course</title>
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
                <h2>Edit Course</h2>
            </header>
            <main>
                <div class="edit-course-form">
                    <form action="" method="post">
                        <input type="text" name="title" value="<?php echo $course['title']; ?>" required>
                        <textarea name="description"><?php echo $course['description']; ?></textarea>
                        <input type="text" name="category" value="<?php echo $course['category']; ?>" placeholder="Course Category">
                        <button type="submit" name="update_course">Update Course</button>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
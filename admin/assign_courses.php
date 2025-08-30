<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Handle Assignment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_course'])) {
    $course_id = $_POST['course_id'];
    $assign_to = $_POST['assign_to']; // 'department' or 'user'

    if ($assign_to == 'department') {
        $department_id = $_POST['department_id'];
        $sql = "SELECT id FROM users WHERE department_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $user_id = $row['id'];
            // Avoid duplicate assignments
            $check_sql = "SELECT id FROM user_courses WHERE user_id = ? AND course_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ii", $user_id, $course_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows == 0) {
                $insert_sql = "INSERT INTO user_courses (user_id, course_id) VALUES (?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("ii", $user_id, $course_id);
                $insert_stmt->execute();
            }
        }
    } elseif ($assign_to == 'user') {
        $user_id = $_POST['user_id'];
        // Avoid duplicate assignments
        $check_sql = "SELECT id FROM user_courses WHERE user_id = ? AND course_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $user_id, $course_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows == 0) {
            $insert_sql = "INSERT INTO user_courses (user_id, course_id) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ii", $user_id, $course_id);
            $insert_stmt->execute();
        }
    }
}

// Fetch data for dropdowns
$courses_result = $conn->query("SELECT * FROM courses ORDER BY title");
$departments_result = $conn->query("SELECT * FROM departments ORDER BY name");
$users_result = $conn->query("SELECT id, name, email FROM users WHERE role = 'user' ORDER BY name");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Courses</title>
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
                <h2>Assign Courses</h2>
            </header>
            <main>
                <div class="assign-course-form">
                    <form action="" method="post">
                        <select name="course_id" required>
                            <option value="">Select Course</option>
                            <?php while ($row = $courses_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo $row['title']; ?></option>
                            <?php endwhile; ?>
                        </select>

                        <select name="assign_to" required onchange="toggleAssignee(this.value)">
                            <option value="">Assign To...</option>
                            <option value="department">Department</option>
                            <option value="user">Individual User</option>
                        </select>

                        <div id="department-select" style="display:none;">
                            <select name="department_id">
                                <option value="">Select Department</option>
                                <?php while ($row = $departments_result->fetch_assoc()): ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div id="user-select" style="display:none;">
                            <select name="user_id">
                                <option value="">Select User</option>
                                <?php while ($row = $users_result->fetch_assoc()): ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?> (<?php echo $row['email']; ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <button type="submit" name="assign_course">Assign Course</button>
                    </form>
                </div>
            </main>
        </div>
    </div>
    <script>
        function toggleAssignee(value) {
            document.getElementById('department-select').style.display = value === 'department' ? 'block' : 'none';
            document.getElementById('user-select').style.display = value === 'user' ? 'block' : 'none';
        }
    </script>
</body>
</html>
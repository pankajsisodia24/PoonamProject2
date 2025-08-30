<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_GET['id'];

// Update User
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $department_id = $_POST['department_id'];
    $role = $_POST['role'];
    $status = $_POST['status'];

    $sql = "UPDATE users SET name = ?, email = ?, department_id = ?, role = ?, status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisii", $name, $email, $department_id, $role, $status, $user_id);
    $stmt->execute();
    header("Location: manage_users.php");
    exit();
}

// Fetch User
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch Departments for dropdown
$departments_result = $conn->query("SELECT * FROM departments ORDER BY name");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
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
                <h2>Edit User</h2>
            </header>
            <main>
                <div class="edit-user-form">
                    <form action="" method="post">
                        <input type="text" name="name" value="<?php echo $user['name']; ?>" required>
                        <input type="email" name="email" value="<?php echo $user['email']; ?>" required>
                        <select name="department_id">
                            <option value="">No Department</option>
                            <?php while ($row = $departments_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>" <?php if ($user['department_id'] == $row['id']) echo 'selected'; ?>><?php echo $row['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                        <select name="role" required>
                            <option value="user" <?php if ($user['role'] == 'user') echo 'selected'; ?>>User</option>
                            <option value="admin" <?php if ($user['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                        </select>
                        <select name="status" required>
                            <option value="active" <?php if ($user['status'] == 'active') echo 'selected'; ?>>Active</option>
                            <option value="inactive" <?php if ($user['status'] == 'inactive') echo 'selected'; ?>>Inactive</option>
                        </select>
                        <button type="submit" name="update_user">Update User</button>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
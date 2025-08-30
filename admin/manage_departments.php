<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Add Department
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_department'])) {
    $department_name = $_POST['department_name'];
    $sql = "INSERT INTO departments (name) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $department_name);
    $stmt->execute();
}

// Fetch Departments
$departments_result = $conn->query("SELECT * FROM departments ORDER BY name");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Departments</title>
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
                <h2>Manage Departments</h2>
            </header>
            <main>
                <div class="add-department-form">
                    <h3>Add New Department</h3>
                    <form action="" method="post">
                        <input type="text" name="department_name" placeholder="Department Name" required>
                        <button type="submit" name="add_department">Add Department</button>
                    </form>
                </div>
                <div class="department-list">
                    <h3>Existing Departments</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $departments_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo $row['name']; ?></td>
                                <td>
                                    <a href="edit_department.php?id=<?php echo $row['id']; ?>">Edit</a>
                                    <a href="delete_department.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                                </td>
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
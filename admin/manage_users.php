<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Add User
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $department_id = $_POST['department_id'];
    $role = $_POST['role'];
    $status = $_POST['status'];

    $sql = "INSERT INTO users (name, email, password, department_id, role, status) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssis", $name, $email, $password, $department_id, $role, $status);
    $stmt->execute();
}

// Fetch Users
$users_result = $conn->query("SELECT users.*, departments.name AS department_name FROM users LEFT JOIN departments ON users.department_id = departments.id ORDER BY users.name");

// Fetch Departments for dropdown
$departments_result = $conn->query("SELECT * FROM departments ORDER BY name");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
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
                <h2>Manage Users</h2>
            </header>
            <main>
                <div class="add-user-form">
                    <h3>Add New User</h3>
                    <form action="" method="post">
                        <input type="text" name="name" placeholder="Full Name" required>
                        <input type="email" name="email" placeholder="Email" required>
                        <input type="password" name="password" placeholder="Password" required>
                        <select name="department_id">
                            <option value="">No Department</option>
                            <?php while ($row = $departments_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                        <select name="role" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                        <select name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <button type="submit" name="add_user">Add User</button>
                    </form>
                </div>
                <div class="user-list">
                    <h3>Existing Users</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $users_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo $row['name']; ?></td>
                                <td><?php echo $row['email']; ?></td>
                                <td><?php echo $row['department_name'] ?? 'N/A'; ?></td>
                                <td><?php echo $row['role']; ?></td>
                                <td><?php echo $row['status']; ?></td>
                                <td>
                                    <a href="edit_user.php?id=<?php echo $row['id']; ?>">Edit</a>
                                    <a href="delete_user.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                                    <a href="toggle_user_status.php?id=<?php echo $row['id']; ?>&current_status=<?php echo $row['status']; ?>">
                                        <?php echo ($row['status'] == 'active') ? 'Deactivate' : 'Activate'; ?>
                                    </a>
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
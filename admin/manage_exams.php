<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Add Exam
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_exam'])) {
    $title = $_POST['title'];
    $duration = $_POST['duration'];

    $sql = "INSERT INTO exams (title, duration_in_minutes) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $title, $duration);
    $stmt->execute();
}

// Fetch Exams
$exams_result = $conn->query("SELECT * FROM exams ORDER BY title");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exams</title>
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
                <h2>Manage Exams</h2>
            </header>
            <main>
                <div class="add-exam-form">
                    <h3>Add New Exam</h3>
                    <form action="" method="post">
                        <input type="text" name="title" placeholder="Exam Title" required>
                        <input type="number" name="duration" placeholder="Duration (in minutes)" required>
                        <button type="submit" name="add_exam">Add Exam</button>
                    </form>
                </div>
                <div class="exam-list">
                    <h3>Existing Exams</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Duration (mins)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $exams_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo $row['title']; ?></td>
                                <td><?php echo $row['duration_in_minutes']; ?></td>
                                <td>
                                    <a href="manage_questions.php?exam_id=<?php echo $row['id']; ?>">Manage Questions</a>
                                    <a href="edit_exam.php?id=<?php echo $row['id']; ?>">Edit</a>
                                    <a href="delete_exam.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
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
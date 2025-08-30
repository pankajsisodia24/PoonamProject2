<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Add Course
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_course'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category = $_POST['category'];

    $sql = "INSERT INTO courses (title, description, category) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error); // Add this line for error checking
    }

    $stmt->bind_param("sss", $title, $description, $category);
    $stmt->execute();
}

// Fetch Courses
$courses_result = $conn->query("SELECT * FROM courses ORDER BY title");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses</title>
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
                <h2>Manage Courses</h2>
            </header>
            <main>
                <div class="add-course-form">
                    <h3>Add New Course</h3>
                    <form action="" method="post">
                        <input type="text" name="title" placeholder="Course Title" required>
                        <textarea name="description" placeholder="Course Description"></textarea>
                        <input type="text" name="category" placeholder="Course Category">
                        <button type="submit" name="add_course">Add Course</button>
                    </form>
                </div>
                <div class="course-list">
                    <h3>Existing Courses</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $courses_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo $row['title']; ?></td>
                                <td><?php echo $row['description']; ?></td>
                                <td><?php echo $row['category']; ?></td>
                                <td>
                                    <a href="edit_course.php?id=<?php echo $row['id']; ?>">Edit</a>
                                    <a href="delete_course.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                                    <a href="manage_course_materials.php?course_id=<?php echo $row['id']; ?>">Manage Materials</a>
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
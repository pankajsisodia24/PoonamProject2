<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Handle Scheduling
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['schedule_exam'])) {
    $exam_id = $_POST['exam_id'];
    $assign_to = $_POST['assign_to'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    if ($assign_to == 'department') {
        $department_id = $_POST['department_id'];
        $sql = "INSERT INTO exam_schedules (exam_id, department_id, start_time, end_time) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiss", $exam_id, $department_id, $start_time, $end_time);
        $stmt->execute();
    } elseif ($assign_to == 'user') {
        $user_id = $_POST['user_id'];
        $sql = "INSERT INTO exam_schedules (exam_id, user_id, start_time, end_time) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiss", $exam_id, $user_id, $start_time, $end_time);
        $stmt->execute();
    }
}

// Handle Delete Schedule
if (isset($_GET['delete_schedule_id'])) {
    $schedule_id = $_GET['delete_schedule_id'];
    $sql = "DELETE FROM exam_schedules WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    header("Location: schedule_exams.php");
    exit();
}

// Fetch data for dropdowns
$exams_result = $conn->query("SELECT * FROM exams ORDER BY title");
$departments_result = $conn->query("SELECT * FROM departments ORDER BY name");
$users_result = $conn->query("SELECT id, name, email FROM users WHERE role = 'user' ORDER BY name");

// Fetch existing schedules for display
$schedules_sql = "SELECT es.id, e.title AS exam_title, u.name AS user_name, d.name AS department_name, es.start_time, es.end_time 
                  FROM exam_schedules es
                  JOIN exams e ON es.exam_id = e.id
                  LEFT JOIN users u ON es.user_id = u.id
                  LEFT JOIN departments d ON es.department_id = d.id
                  ORDER BY es.start_time DESC";
$schedules_result = $conn->query($schedules_sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Exams</title>
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
                <h2>Schedule Exams</h2>
            </header>
            <main>
                <div class="schedule-exam-form">
                    <h3>Schedule New Exam</h3>
                    <form action="" method="post">
                        <select name="exam_id" required>
                            <option value="">Select Exam</option>
                            <?php while ($row = $exams_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo $row['title']; ?></option>
                            <?php endwhile; ?>
                        </select>

                        <select name="assign_to" required onchange="toggleAssignee(this.value)">
                            <option value="">Schedule For...</option>
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

                        <label>Start Time</label>
                        <input type="datetime-local" name="start_time" required>

                        <label>End Time</label>
                        <input type="datetime-local" name="end_time" required>

                        <button type="submit" name="schedule_exam">Schedule Exam</button>
                    </form>
                </div>

                <div class="scheduled-exams-list">
                    <h3>Existing Exam Schedules</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Exam</th>
                                <th>Scheduled For</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $schedules_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['exam_title']; ?></td>
                                <td>
                                    <?php 
                                        if ($row['user_name']) {
                                            echo $row['user_name'];
                                        } elseif ($row['department_name']) {
                                            echo $row['department_name'] . ' (Department)';
                                        } else {
                                            echo 'N/A';
                                        }
                                    ?>
                                </td>
                                <td><?php echo $row['start_time']; ?></td>
                                <td><?php echo $row['end_time']; ?></td>
                                <td>
                                    <a href="reschedule_exam.php?id=<?php echo $row['id']; ?>">Reschedule</a> |
                                    <a href="schedule_exams.php?delete_schedule_id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this schedule?')">Delete</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
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
<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$schedule_id = $_GET['id'];

// Handle Reschedule
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reschedule_exam'])) {
    $new_start_time = $_POST['new_start_time'];
    $new_end_time = $_POST['new_end_time'];

    $sql = "UPDATE exam_schedules SET start_time = ?, end_time = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $new_start_time, $new_end_time, $schedule_id);
    $stmt->execute();
    header("Location: schedule_exams.php");
    exit();
}

// Fetch current schedule details
$sql = "SELECT es.id, e.title AS exam_title, u.name AS user_name, d.name AS department_name, es.start_time, es.end_time 
          FROM exam_schedules es
          JOIN exams e ON es.exam_id = e.id
          LEFT JOIN users u ON es.user_id = u.id
          LEFT JOIN departments d ON es.department_id = d.id
          WHERE es.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$result = $stmt->get_result();
$schedule = $result->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Exam</title>
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
                <h2>Reschedule Exam</h2>
            </header>
            <main>
                <div class="reschedule-form">
                    <h3>Reschedule Exam: <?php echo $schedule['exam_title']; ?></h3>
                    <p><strong>Scheduled For:</strong> 
                        <?php 
                            if ($schedule['user_name']) {
                                echo $schedule['user_name'];
                            } elseif ($schedule['department_name']) {
                                echo $schedule['department_name'] . ' (Department)';
                            } else {
                                echo 'N/A';
                            }
                        ?>
                    </p>
                    <form action="" method="post">
                        <label>Current Start Time: <?php echo $schedule['start_time']; ?></label>
                        <label>New Start Time</label>
                        <input type="datetime-local" name="new_start_time" value="<?php echo date('Y-m-d\TH:i', strtotime($schedule['start_time'])); ?>" required>

                        <label>Current End Time: <?php echo $schedule['end_time']; ?></label>
                        <label>New End Time</label>
                        <input type="datetime-local" name="new_end_time" value="<?php echo date('Y-m-d\TH:i', strtotime($schedule['end_time'])); ?>" required>

                        <button type="submit" name="reschedule_exam">Reschedule Exam</button>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
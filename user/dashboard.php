<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'user') {
    error_log("User Dashboard: Session check failed. User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . ", Role: " . (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'NOT SET'));
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's department
$user_sql = "SELECT department_id FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_dept = $user_result->fetch_assoc();
$department_id = $user_dept['department_id'];

// Fetch Dashboard Data for User
$total_attempted_exams_query = $conn->prepare("SELECT COUNT(er.id) AS count FROM exam_results er JOIN exams e ON er.exam_id = e.id WHERE er.user_id = ?");
$total_attempted_exams_query->bind_param("i", $user_id);
$total_attempted_exams_query->execute();
$total_attempted_exams = $total_attempted_exams_query->get_result()->fetch_assoc()['count'];

$average_score_query = $conn->prepare("SELECT AVG(er.score) AS avg_score FROM exam_results er JOIN exams e ON er.exam_id = e.id WHERE er.user_id = ?");
$average_score_query->bind_param("i", $user_id);
$average_score_query->execute();
$average_score_result = $average_score_query->get_result()->fetch_assoc();
$average_score = round($average_score_result['avg_score'], 2);

// For Training Complete Progress Bar - NEW LOGIC
$total_assigned_courses_stmt = $conn->prepare("SELECT COUNT(*) AS count FROM user_courses WHERE user_id = ?");
$total_assigned_courses_stmt->bind_param("i", $user_id);
$total_assigned_courses_stmt->execute();
$total_assigned_courses = $total_assigned_courses_stmt->get_result()->fetch_assoc()['count'];

$completed_courses_stmt = $conn->prepare("SELECT COUNT(*) AS count FROM user_courses WHERE user_id = ? AND status = 'completed'");
$completed_courses_stmt->bind_param("i", $user_id);
$completed_courses_stmt->execute();
$completed_courses_count = $completed_courses_stmt->get_result()->fetch_assoc()['count'];

$training_progress_percentage = ($total_assigned_courses > 0) ? round(($completed_courses_count / $total_assigned_courses) * 100) : 0;

// Fetch scheduled exams for the user and their department (upcoming or active)
$scheduled_exams_sql = "SELECT e.title, es.start_time, es.end_time
                        FROM exam_schedules es
                        JOIN exams e ON es.exam_id = e.id
                        WHERE (es.user_id = ? OR es.department_id = ?)
                        AND es.end_time >= NOW()
                        ORDER BY es.start_time ASC";

$scheduled_exams_stmt = $conn->prepare($scheduled_exams_sql);
$scheduled_exams_stmt->bind_param("ii", $user_id, $department_id);
$scheduled_exams_stmt->execute();
$scheduled_exams_result = $scheduled_exams_stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <h2>User Panel</h2>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="my_courses.php">My Courses</a></li>
                <li><a href="my_exams.php">My Exams</a></li>
                <li><a href="my_reports.php">My Reports</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
        <div class="main-content">
            <header>
                <div class="app-header">
                    <h1>Poonam Management Training and Online Exam Module</h1>
                </div>
                <h2>Welcome, <?php echo $_SESSION['user_name']; ?>!</h2>
            </header>
            <main>
                <h3>Your Dashboard</h3>
                <p>Here you can access your assigned courses and exams.</p>

                <div class="dashboard-widgets">
                    <div class="widget total-attempted-exams">
                        <h4>Total Attempted Exams</h4>
                        <p><?php echo $total_attempted_exams; ?></p>
                    </div>
                    <div class="widget average-score">
                        <h4>Exam Average Score</h4>
                        <p><?php echo $average_score; ?>%</p>
                    </div>
                    <div class="widget training-progress-widget">
                        <h4>Training Complete</h4>
                        <div class="progress-bar-container">
                            <div class="progress-bar" style="width: <?php echo $training_progress_percentage; ?>%;">
                                <?php echo $training_progress_percentage; ?>%
                            </div>
                        </div>
                        <p><?php echo $completed_courses_count; ?> / <?php echo $total_assigned_courses; ?> Courses Completed</p>
                    </div>
                </div>

                <div class="scheduled-exams-notification">
                    <h4>Upcoming/Active Exams:</h4>
                    <?php if ($scheduled_exams_result->num_rows > 0): ?>
                        <ul>
                            <?php while ($exam = $scheduled_exams_result->fetch_assoc()): ?>
                                <li>
                                    <strong><?php echo $exam['title']; ?></strong>:
                                    From <?php echo date('M d, Y H:i', strtotime($exam['start_time'])); ?>
                                    to <?php echo date('M d, Y H:i', strtotime($exam['end_time'])); ?>
                                    <?php if (strtotime($exam['start_time']) <= time() && strtotime($exam['end_time']) >= time()): ?>
                                        <span style="color: green; font-weight: bold;">(Active Now!)</span>
                                    <?php elseif (strtotime($exam['start_time']) > time()): ?>
                                        <span style="color: blue;">(Upcoming)</span>
                                    <?php endif; ?>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p>No upcoming or active exams scheduled.</p>
                    <?php endif; ?>
                </div>

                <!-- Add more dashboard widgets here -->
            </main>
        </div>
    </div>
<?php include '../config/footer.php'; ?>
</body>
</html>
<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'user') {
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

// Fetch all exam results for the user once for efficiency
$results_sql = "SELECT exam_id FROM exam_results WHERE user_id = ?";
$results_stmt = $conn->prepare($results_sql);
$results_stmt->bind_param("i", $user_id);
$results_stmt->execute();
$results_result = $results_stmt->get_result();
$completed_exams = [];
while ($row = $results_result->fetch_assoc()) {
    $completed_exams[] = $row['exam_id'];
}

// Fetch scheduled exams for the user and their department
$sql = "SELECT e.id, e.title, e.duration_in_minutes, es.start_time, es.end_time
        FROM exam_schedules es
        JOIN exams e ON es.exam_id = e.id
        WHERE (es.user_id = ? OR es.department_id = ?)
        ORDER BY es.start_time ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $department_id);
$stmt->execute();
$exams_result = $stmt->get_result();

$current_time = time();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Exams</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .status-completed {
            color: purple;
            font-weight: bold;
        }
        .status-active {
            color: green;
            font-weight: bold;
        }
        .status-upcoming {
            color: blue;
        }
        .status-expired {
            color: red;
        }
        .start-button {
            background-color: #28a745; /* Green */
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .start-button:hover {
            background-color: #218838;
        }
        .report-button {
            background-color: #007bff; /* Blue */
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .report-button:hover {
            background-color: #0069d9;
        }
    </style>
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
                <h2>My Exams</h2>
            </header>
            <main>
                <div class="exam-list">
                    <table>
                        <thead>
                            <tr>
                                <th>Exam Title</th>
                                <th>Duration (mins)</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($exam = $exams_result->fetch_assoc()): 
                                $start_timestamp = strtotime($exam['start_time']);
                                $end_timestamp = strtotime($exam['end_time']);
                                $exam_id = $exam['id'];
                                $status_class = '';
                                $status_text = '';
                                $action_html = '';

                                if (in_array($exam_id, $completed_exams)) {
                                    $status_class = 'status-completed';
                                    $status_text = 'Completed';
                                    $action_html = '<a href="my_reports.php?exam_id=' . $exam_id . '" class="report-button">View Report</a>';
                                } elseif ($current_time >= $start_timestamp && $current_time <= $end_timestamp) {
                                    $status_class = 'status-active';
                                    $status_text = 'Active';
                                    $action_html = '<a href="take_exam.php?exam_id=' . $exam_id . '" class="start-button">Start Exam</a>';
                                } elseif ($current_time < $start_timestamp) {
                                    $status_class = 'status-upcoming';
                                    $status_text = 'Upcoming';
                                    $action_html = '<span class="status-upcoming">Not Yet Active</span>';
                                } else {
                                    $status_class = 'status-expired';
                                    $status_text = 'Expired';
                                    $action_html = '<span class="status-expired">Not Attempted</span>';
                                }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                <td><?php echo htmlspecialchars($exam['duration_in_minutes']); ?></td>
                                <td><?php echo htmlspecialchars($exam['start_time']); ?></td>
                                <td><?php echo htmlspecialchars($exam['end_time']); ?></td>
                                <td class="<?php echo $status_class; ?>"><?php echo htmlspecialchars($status_text); ?></td>
                                <td><?php echo $action_html; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
<?php include '../config/footer.php'; ?>
</body>
</html>
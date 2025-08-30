<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'user') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check for course_id and material_id in URL
if (!isset($_GET['course_id']) || !isset($_GET['material_id'])) {
    header("Location: my_courses.php?error=Invalid Link");
    exit();
}
$course_id = (int)$_GET['course_id'];
$material_id = (int)$_GET['material_id'];

// Handle POST request to mark course as complete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_complete'])) {
    $update_sql = "UPDATE user_courses SET status = 'completed' WHERE user_id = ? AND course_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $user_id, $course_id);
    $update_stmt->execute();
    header("Location: my_courses.php?message=Course marked as complete!");
    exit();
}

// --- Fetch data for the page ---

// Fetch Course Info
$course_sql = "SELECT title FROM courses WHERE id = ?";
$course_stmt = $conn->prepare($course_sql);
$course_stmt->bind_param("i", $course_id);
$course_stmt->execute();
$course_result = $course_stmt->get_result();
$course = $course_result->fetch_assoc();
if (!$course) {
    die("Course not found.");
}

// Fetch Material Info
$material_sql = "SELECT * FROM course_materials WHERE id = ? AND course_id = ?";
$material_stmt = $conn->prepare($material_sql);
$material_stmt->bind_param("ii", $material_id, $course_id);
$material_stmt->execute();
$material_result = $material_stmt->get_result();
$material = $material_result->fetch_assoc();
if (!$material) {
    die("Material not found.");
}

// Fetch course status
$status_sql = "SELECT status FROM user_courses WHERE user_id = ? AND course_id = ?";
$status_stmt = $conn->prepare($status_sql);
$status_stmt->bind_param("ii", $user_id, $course_id);
$status_stmt->execute();
$status_result = $status_stmt->get_result();
$course_status = $status_result->fetch_assoc()['status'];

// Automatically mark the material as viewed (original functionality)
$check_view_sql = "SELECT id FROM user_material_views WHERE user_id = ? AND material_id = ?";
$check_view_stmt = $conn->prepare($check_view_sql);
$check_view_stmt->bind_param("ii", $user_id, $material_id);
$check_view_stmt->execute();
if ($check_view_stmt->get_result()->num_rows == 0) {
    $insert_view_sql = "INSERT INTO user_material_views (user_id, material_id) VALUES (?, ?)";
    $insert_view_stmt = $conn->prepare($insert_view_sql);
    $insert_view_stmt->bind_param("ii", $user_id, $material_id);
    $insert_view_stmt->execute();
}

// Construct the correct, web-accessible path for the material
$material_web_path = str_replace('..', '/e_learning_platform', $material['file_path']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Material: <?php echo htmlspecialchars($material['file_name']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .material-viewer {
            width: 100%;
            height: 80vh; /* 80% of the viewport height */
            border: 1px solid #ccc;
        }
        .complete-button {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            margin-top: 20px;
        }
        .complete-button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
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
                    <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                </div>
                <h2>Viewing: <?php echo htmlspecialchars($material['file_name']); ?></h2>
            </header>
            <main>
                <div class="material-content">
                    <?php 
                        $file_type = $material['file_type'];
                        if (strpos($file_type, 'pdf') !== false) {
                            echo '<iframe src="' . htmlspecialchars($material_web_path) . '" class="material-viewer"></iframe>';
                        } elseif (strpos($file_type, 'image') !== false) {
                            echo '<img src="' . htmlspecialchars($material_web_path) . '" alt="' . htmlspecialchars($material['file_name']) . '" style="max-width: 100%;">';
                        } elseif (strpos($file_type, 'video') !== false) {
                            echo '<video controls class="material-viewer"><source src="' . htmlspecialchars($material_web_path) . '" type="' . htmlspecialchars($file_type) . '">Your browser does not support the video tag.</video>';
                        } else {
                            echo '<p>Unsupported file type. <a href="' . htmlspecialchars($material_web_path) . '" target="_blank">Download the material</a>.</p>';
                        }
                    ?>
                </div>
                <form action="" method="post">
                    <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                    <button type="submit" name="mark_complete" class="complete-button" <?php if ($course_status == 'completed') echo 'disabled'; ?>>
                        <?php echo ($course_status == 'completed') ? 'Course Already Completed' : 'Mark Course as Complete'; ?>
                    </button>
                </form>
            </main>
        </div>
    </div>
</body>
</html>
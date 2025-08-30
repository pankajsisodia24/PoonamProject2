<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$question_id = $_GET['id'];

// Update Question
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_question'])) {
    $question_text = $_POST['question_text'];
    $score = $_POST['score'];
    $question_image = $_POST['existing_image'];

    if (isset($_FILES['question_image']) && $_FILES['question_image']['error'] == 0) {
        // First, delete the old image if it exists
        if ($question_image && file_exists($question_image)) {
            unlink($question_image);
        }
        $image_name = uniqid() . '-' . $_FILES['question_image']['name'];
        $image_path = "../uploads/" . $image_name;
        move_uploaded_file($_FILES['question_image']['tmp_name'], $image_path);
        $question_image = $image_path;
    }

    $sql = "UPDATE questions SET question_text = ?, question_image_path = ?, score = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $question_text, $question_image, $score, $question_id);
    $stmt->execute();
    
    $sql = "SELECT exam_id FROM questions WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $question = $result->fetch_assoc();
    $exam_id = $question['exam_id'];

    header("Location: manage_questions.php?exam_id=" . $exam_id);
    exit();
}

// Fetch Question
$sql = "SELECT * FROM questions WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $question_id);
$stmt->execute();
$result = $stmt->get_result();
$question = $result->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Question</title>
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
                <h2>Edit Question</h2>
            </header>
            <main>
                <div class="edit-question-form">
                    <form action="" method="post" enctype="multipart/form-data">
                        <textarea name="question_text" required><?php echo $question['question_text']; ?></textarea>
                        <input type="number" name="score" value="<?php echo $question['score']; ?>" min="1" required>
                        <label>Current Image</label>
                        <?php if ($question['question_image_path']): ?>
                        <img src="<?php echo $question['question_image_path']; ?>" width="100">
                        <?php endif; ?>
                        <input type="hidden" name="existing_image" value="<?php echo $question['question_image_path']; ?>">
                        <label>Upload New Image (Optional)</label>
                        <input type="file" name="question_image">
                        <button type="submit" name="update_question">Update Question</button>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
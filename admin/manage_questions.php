<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$exam_id = $_GET['exam_id'];

// Handle Add Question
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_question'])) {
    $question_text = $_POST['question_text'];
    $score = $_POST['score'];
    $question_image = '';

    if (isset($_FILES['question_image']) && $_FILES['question_image']['error'] == 0) {
        $image_name = uniqid() . '-' . $_FILES['question_image']['name'];
        $image_path = "../uploads/" . $image_name;
        move_uploaded_file($_FILES['question_image']['tmp_name'], $image_path);
        $question_image = $image_path;
    }

    $sql = "INSERT INTO questions (exam_id, question_text, question_image_path, score) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $exam_id, $question_text, $question_image, $score);
    $stmt->execute();
}

// Fetch Exam Info
$sql = "SELECT title FROM exams WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$result = $stmt->get_result();
$exam = $result->fetch_assoc();

// Fetch Questions
$sql = "SELECT * FROM questions WHERE exam_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$questions_result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions</title>
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
                <h2>Manage Questions for: <?php echo $exam['title']; ?></h2>
            </header>
            <main>
                <div class="add-question-form">
                    <h3>Add New Question</h3>
                    <form action="" method="post" enctype="multipart/form-data">
                        <textarea name="question_text" placeholder="Question Text" required></textarea>
                        <input type="number" name="score" placeholder="Score for this question" value="1" min="1" required>
                        <label>Question Image (Optional)</label>
                        <input type="file" name="question_image">
                        <button type="submit" name="add_question">Add Question</button>
                    </form>
                </div>
                <div class="question-list">
                    <h3>Existing Questions</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Question Text</th>
                                <th>Score</th>
                                <th>Image</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $questions_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo $row['question_text']; ?></td>
                                <td><?php echo $row['score']; ?></td>
                                <td>
                                    <?php if ($row['question_image_path']): ?>
                                    <img src="<?php echo $row['question_image_path']; ?>" width="100">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="manage_options.php?question_id=<?php echo $row['id']; ?>">Manage Options</a>
                                    <a href="edit_question.php?id=<?php echo $row['id']; ?>">Edit</a>
                                    <a href="delete_question.php?id=<?php echo $row['id']; ?>&exam_id=<?php echo $exam_id; ?>" onclick="return confirm('Are you sure?')">Delete</a>
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
<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$question_id = $_GET['question_id'];

// Handle Add Option
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_option'])) {
    $option_text = $_POST['option_text'];
    $is_correct = isset($_POST['is_correct']) ? 1 : 0;
    $option_image = '';

    if (isset($_FILES['option_image']) && $_FILES['option_image']['error'] == 0) {
        $image_name = uniqid() . '-' . $_FILES['option_image']['name'];
        $image_path = "../uploads/" . $image_name;
        move_uploaded_file($_FILES['option_image']['tmp_name'], $image_path);
        $option_image = $image_path;
    }

    // If this option is correct, set other options for this question to incorrect
    if ($is_correct) {
        $sql = "UPDATE options SET is_correct = 0 WHERE question_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
    }

    $sql = "INSERT INTO options (question_id, option_text, option_image_path, is_correct) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $question_id, $option_text, $option_image, $is_correct);
    $stmt->execute();
}

// Handle Delete Option
if (isset($_GET['delete_option_id'])) {
    $option_id = $_GET['delete_option_id'];
    // First, get the file path to delete the file from the server
    $sql = "SELECT option_image_path FROM options WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $option_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($row['option_image_path'] && file_exists($row['option_image_path'])) {
            unlink($row['option_image_path']);
        }
    }

    // Then, delete the record from the database
    $sql = "DELETE FROM options WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $option_id);
    $stmt->execute();
    header("Location: manage_options.php?question_id=" . $question_id);
    exit();
}

// Fetch Question Info and Exam ID
$sql = "SELECT question_text, exam_id FROM questions WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $question_id);
$stmt->execute();
$result = $stmt->get_result();
$question = $result->fetch_assoc();
$exam_id = $question['exam_id']; // Get exam_id here

// Fetch Options
$sql = "SELECT * FROM options WHERE question_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $question_id);
$stmt->execute();
$options_result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Options</title>
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
                <h2>Manage Options for: <?php echo $question['question_text']; ?></h2>
                <a href="manage_questions.php?exam_id=<?php echo $exam_id; ?>" class="button">Back to Questions</a>
            </header>
            <main>
                <div class="add-option-form">
                    <h3>Add New Option</h3>
                    <form action="" method="post" enctype="multipart/form-data">
                        <input type="text" name="option_text" placeholder="Option Text" required>
                        <label>Option Image (Optional)</label>
                        <input type="file" name="option_image">
                        <label>
                            <input type="checkbox" name="is_correct" value="1">
                            Is this the correct answer?
                        </label>
                        <button type="submit" name="add_option">Add Option</button>
                    </form>
                </div>
                <div class="option-list">
                    <h3>Existing Options</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Option Text</th>
                                <th>Image</th>
                                <th>Is Correct?</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $options_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo $row['option_text']; ?></td>
                                <td>
                                    <?php if ($row['option_image_path']): ?>
                                    <img src="<?php echo $row['option_image_path']; ?>" width="50">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['is_correct'] ? 'Yes' : 'No'; ?></td>
                                <td>
                                    <a href="manage_options.php?question_id=<?php echo $question_id; ?>&delete_option_id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
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
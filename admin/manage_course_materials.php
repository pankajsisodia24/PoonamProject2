 <?php
session_start();
include '../config/db_connect.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php?error=Unauthorized");
    exit();
}

// Get course_id from URL
if (!isset($_GET['course_id'])) {
    die("Course ID is required.");
}
$course_id = (int)$_GET['course_id'];

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_material_id'])) {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }

    $material_id = (int)$_POST['delete_material_id'];
    // Add logic here to delete the actual file from the server if needed
    $sql = "DELETE FROM course_materials WHERE id = ? AND course_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed on delete: " . $conn->error);
    }
    $stmt->bind_param("ii", $material_id, $course_id);
    $stmt->execute();
    header("Location: manage_course_materials.php?course_id=" . $course_id);
    exit();
}

// Handle File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['course_material'])) {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }

    $section_name = $_POST['section_name'] ?: 'Uncategorized';
    $file = $_FILES['course_material'];

    // File upload logic
    $upload_dir = '../uploads/course_materials/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $file_name = uniqid() . '-' . basename($file['name']);
    $file_path = $upload_dir . $file_name;
    $file_type = $file['type'];

    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        $sql = "INSERT INTO course_materials (course_id, section_name, file_name, file_path, file_type) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Prepare failed on insert: " . $conn->error);
        }
        $stmt->bind_param("issss", $course_id, $section_name, $file['name'], $file_path, $file_type);
        $stmt->execute();
    } else {
        die("Failed to upload file.");
    }
    header("Location: manage_course_materials.php?course_id=" . $course_id);
    exit();
}


// Fetch Course Info
$sql = "SELECT title FROM courses WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed on fetching course info: " . $conn->error);
}
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();
if (!$course) {
    die("Course not found.");
}

// Fetch Course Materials grouped by section
$sql = "SELECT * FROM course_materials WHERE course_id = ? ORDER BY section_name, file_name";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed on fetching course materials: " . $conn->error);
}
$stmt->bind_param("i", $course_id);
$stmt->execute();
$materials_result = $stmt->get_result();

$materials_by_section = [];
while ($row = $materials_result->fetch_assoc()) {
    $section = $row['section_name'] ?: 'Uncategorized';
    $materials_by_section[$section][] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Course Materials - <?php echo htmlspecialchars($course['title']); ?></title>
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
                <li><a href="course_status_report.php">Course Status Report</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
        <div class="main-content">
            <header>
                <h2>Manage Materials for: <?php echo htmlspecialchars($course['title']); ?></h2>
            </header>
            <main>
                <div class="upload-form">
                    <h3>Upload New Material</h3>
                    <form action="manage_course_materials.php?course_id=<?php echo $course_id; ?>" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="text" name="section_name" placeholder="Section Name (e.g., Introduction)">
                        <input type="file" name="course_material" required>
                        <button type="submit">Upload</button>
                    </form>
                </div>
                <div class="material-list">
                    <h3>Uploaded Materials</h3>
                    <?php if (empty($materials_by_section)): ?>
                        <p>No materials uploaded for this course yet.</p>
                    <?php else: ?>
                        <?php foreach ($materials_by_section as $section => $materials): ?>
                            <h4>Section: <?php echo htmlspecialchars($section); ?></h4>
                            <table>
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>File Type</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($materials as $material): ?>
                                    <tr>
                                        <td><a href="<?php echo htmlspecialchars($material['file_path']); ?>" target="_blank"><?php echo htmlspecialchars($material['file_name']); ?></a></td>
                                        <td><?php echo htmlspecialchars($material['file_type']); ?></td>
                                        <td>
                                            <form action="manage_course_materials.php?course_id=<?php echo $course_id; ?>" method="post" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="delete_material_id" value="<?php echo $material['id']; ?>">
                                                <button type="submit" onclick="return confirm('Are you sure?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
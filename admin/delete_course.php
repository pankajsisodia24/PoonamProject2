<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id'])) {
    $course_id = $_GET['id'];

    // First, delete associated materials and files
    $sql = "SELECT file_path FROM course_materials WHERE course_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (file_exists($row['file_path'])) {
            unlink($row['file_path']);
        }
    }

    // Delete from course_materials table
    $sql = "DELETE FROM course_materials WHERE course_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();

    // Finally, delete the course
    $sql = "DELETE FROM courses WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
}

header("Location: manage_courses.php");
exit();
?>
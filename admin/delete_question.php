<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id'])) {
    $question_id = $_GET['id'];
    $exam_id = $_GET['exam_id'];

    // First, delete the image file if it exists
    $sql = "SELECT question_image_path FROM questions WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($row['question_image_path'] && file_exists($row['question_image_path'])) {
            unlink($row['question_image_path']);
        }
    }

    // Delete the question from the database
    $sql = "DELETE FROM questions WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
}

header("Location: manage_questions.php?exam_id=" . $exam_id);
exit();
?>
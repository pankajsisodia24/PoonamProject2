<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id'])) {
    $exam_id = $_GET['id'];
    $sql = "DELETE FROM exams WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
}

header("Location: manage_exams.php");
exit();
?>
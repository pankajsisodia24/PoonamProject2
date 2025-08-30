<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id']) && isset($_GET['current_status'])) {
    $user_id = $_GET['id'];
    $current_status = $_GET['current_status'];

    $new_status = ($current_status == 'active') ? 'inactive' : 'active';

    $sql = "UPDATE users SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_status, $user_id);
    $stmt->execute();
}

header("Location: manage_users.php");
exit();
?>
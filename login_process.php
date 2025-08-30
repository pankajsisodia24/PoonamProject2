<?php
session_start();
include 'config/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // In a real application, you should use password_verify()
        if (password_verify($password, $user['password'])) {
            if ($user['status'] == 'inactive') {
                header("Location: index.php?error=Your account is inactive. Please contact support.");
                exit();
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];

            error_log("Login Success: User ID = " . $_SESSION['user_id'] . ", Role = " . $_SESSION['user_role'] . ", Name = " . $_SESSION['user_name']);

            if ($user['role'] == 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: user/dashboard.php");
            }
            exit();
        } else {
            header("Location: index.php?error=Invalid credentials");
            exit();
        }
    } else {
        header("Location: index.php?error=User not found");
        exit();
    }
}
?>
<?php
session_start();
include 'config/db_connect.php';

if (!isset($_GET['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_GET['user_id'];

// Handle password reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // In a real application, you should hash the password using password_hash()
        $hashed_password = $new_password; // Plain text for now

        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashed_password, $user_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $success = "Password reset successfully. You can now log in.";
            // Optionally, redirect to login page after a delay
            header("Refresh: 3; URL=index.php");
        } else {
            $error = "Failed to reset password.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - E-Learning Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Reset Password</h2>
        <?php if (isset($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <p style="color: green;"><?php echo $success; ?></p>
        <?php endif; ?>
        <form action="" method="post">
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" name="new_password" id="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>
            <button type="submit" name="reset_password">Set New Password</button>
        </form>
    </div>
</body>
</html>
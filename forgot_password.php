<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - E-Learning Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Forgot Password</h2>
        <form action="forgot_password_process.php" method="post">
            <div class="form-group">
                <label for="email">Enter your email address:</label>
                <input type="email" name="email" id="email" required>
            </div>
            <button type="submit">Reset Password</button>
        </form>
        <p class="back-to-login-link"><a href="index.php">Back to Login</a></p>
    </div>
</body>
</html>
<?php
date_default_timezone_set('Asia/Kolkata'); // Set timezone at the very beginning

// Start session after timezone is set
session_start();

$message = '';
if (isset($_GET['registration_success']) && $_GET['registration_success'] == 1) {
    $message = "Registration successful! You can now log in.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-Learning Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .success-message {
            color: green;
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if (!empty($message)): ?>
            <p class="success-message"><?php echo $message; ?></p>
        <?php endif; ?>
        <form action="login_process.php" method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        <p class="forgot-password-link"><a href="forgot_password.php">Forgot Password?</a></p>
        <p class="register-link">Don't have an account? <a href="register_admin.php">Register here</a></p>
    </div>
</body>
</html>
<?php
session_start();
include 'config/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];

        // !!! SECURITY WARNING !!!
        // In a real application, you would NOT directly redirect to reset_password.php
        // with just the user ID. Instead, you would:
        // 1. Generate a unique, time-limited token.
        // 2. Store this token in the database associated with the user ID.
        // 3. Email a link to the user containing this token (e.g., reset_password.php?token=YOUR_TOKEN).
        // 4. The reset_password.php page would then verify the token before allowing a password change.
        // This simplified version is for demonstration purposes ONLY as per user request.

        header("Location: reset_password.php?user_id=" . $user_id); // INSECURE FOR PRODUCTION
        exit();
    } else {
        header("Location: forgot_password.php?error=Email not found.");
        exit();
    }
}
?>
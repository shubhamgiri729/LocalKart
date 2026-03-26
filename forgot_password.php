<?php

require_once 'config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $token = bin2hex(random_bytes(32));

            $stmt = $pdo->prepare("UPDATE users SET reset_token = ? WHERE email = ?");
            $stmt->execute([$token, $email]);
            $resetLink = "http://localhost/reset_password.php?token=" . $token;

            $message = "
                <div class='msg msg-success'>
                    Password reset link has been generated successfully.
                </div>
                <p><strong>Reset Link (for localhost):</strong><br>
                <a href='$resetLink'>$resetLink</a></p>
            ";
        } else {
            $message = "<div class='msg msg-error'>No account found with this email.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <div class="auth-container">

        <h2>Forgot Password</h2>

        <form method="POST">
            <label>Email Address</label>
            <input type="email" name="email" required placeholder="Enter your registered email">

            <button type="submit" class="btn">Send Reset Link</button>
        </form>

        <?php if (!empty($message)) echo $message; ?>

        <p class="text-center">
            Remembered your password?
            <a href="login.php">Login here</a>
        </p>

    </div>

</body>

</html>
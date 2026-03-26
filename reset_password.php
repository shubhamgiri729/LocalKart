<?php
require_once 'config.php';

$message = '';
$messageType = 'error';
$user = null;

if (!isset($_GET['token']) || empty($_GET['token'])) {
    $message = "❌ Invalid or missing reset token.";
} else {
    $token = trim($_GET['token']);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $message = "❌ Invalid or expired reset token.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm)) {
        $message = "⚠️ Please fill in all fields.";
    } elseif ($password !== $confirm) {
        $message = "⚠️ Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $message = "⚠️ Password must be at least 6 characters long.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            "UPDATE users 
             SET password = ?, reset_token = NULL 
             WHERE reset_token = ?"
        );
        $stmt->execute([$hashedPassword, $token]);

        $message = "✅ Password reset successful! <a href='login.php'>Login here</a>";
        $messageType = 'success';
        $user = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>

    <style>
        body {
            font-family: system-ui, Arial, sans-serif;
            background: #f8f9fa;
        }

        .container {
            max-width: 420px;
            margin: 80px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #007BFF;
            margin-bottom: 20px;
        }

        label {
            font-weight: 500;
        }

        input,
        button {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        button {
            background: #28A745;
            color: #fff;
            border: none;
            margin-top: 15px;
            cursor: pointer;
        }

        button:hover {
            background: #218838;
        }

        .msg {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            font-size: 0.95em;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>

    <script>
        function validateForm() {
            const p1 = document.querySelector('[name="password"]').value;
            const p2 = document.querySelector('[name="confirm_password"]').value;

            if (p1 !== p2) {
                alert("Passwords do not match!");
                return false;
            }
            return true;
        }
    </script>
</head>

<body>
    <div class="container">
        <h2>Reset Password</h2>

        <?php if (!empty($message)): ?>
            <div class="msg <?= $messageType ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($user): ?>
            <form method="POST" onsubmit="return validateForm()">
                <label>New Password</label>
                <input type="password" name="password" required>

                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>

                <button type="submit">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>
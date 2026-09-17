<?php
require_once 'config.php';

$message = '';
$messageType = 'error';
$user = null;
$token = null;

if (!isset($_GET['token']) || empty($_GET['token'])) {
    $message = "❌ Invalid or missing reset token.";
} else {
    $token = trim($_GET['token']);

    $stmt = $pdo->prepare("SELECT id, reset_token_expires FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $message = "❌ Invalid or expired reset token.";
    } elseif (empty($user['reset_token_expires']) || strtotime($user['reset_token_expires']) < time()) {
        // Token exists but has expired (or predates the expiry column) — clear it so it can't be reused.
        $clear = $pdo->prepare("UPDATE users SET reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
        $clear->execute([$user['id']]);
        $user = null;
        $message = "❌ This reset link has expired. Please request a new one.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    requireCsrf();

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
             SET password = ?, reset_token = NULL, reset_token_expires = NULL 
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f8f9fa;
            color: #333;
        }

        .container {
            max-width: 420px;
            margin: 80px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            border: 1px solid #eee;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        h2 {
            text-align: center;
            color: #007BFF;
            margin-bottom: 20px;
        }

        label {
            font-weight: 600;
            color: #555;
        }

        input,
        button {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-family: inherit;
            font-size: 15px;
            box-sizing: border-box;
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        }

        input:focus {
            outline: none;
            border-color: #007BFF;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
        }

        button {
            background: #28A745;
            color: #fff;
            border: none;
            font-weight: 500;
            margin-top: 15px;
            cursor: pointer;
        }

        button:hover {
            background: #218838;
            box-shadow: 0 2px 6px rgba(40, 167, 69, 0.25);
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
    <?php include 'partials/header.php'; ?>
    <div class="container">
        <h2>Reset Password</h2>

        <?php if (!empty($message)): ?>
            <div class="msg <?= $messageType ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($user): ?>
            <form method="POST" onsubmit="return validateForm()">
                <?php csrfField(); ?>
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
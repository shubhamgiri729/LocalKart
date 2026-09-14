<?php

require_once 'config.php';

if (isLoggedIn()) {
    redirectByRole('admin.php', 'shopkeeper.php', 'customer.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {

            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            redirectByRole('admin.php', 'shopkeeper.php', 'customer.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Multi-Vendor Marketplace</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f8f9fa;
            color: #333;
            min-height: 100vh;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-form {
            background: white;
            padding: 40px;
            border-radius: 8px;
            border: 1px solid #eee;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        h2 {
            color: #007BFF;
            text-align: center;
            margin-bottom: 20px;
        }

        label {
            font-weight: 600;
            margin-top: 10px;
            display: block;
            color: #555;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-family: inherit;
            font-size: 15px;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        input:focus {
            outline: none;
            border-color: #007BFF;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
        }

        button {
            width: 100%;
            background: #007BFF;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            font-size: 15px;
            margin-top: 15px;
            cursor: pointer;
            transition: background 0.15s, box-shadow 0.15s;
        }

        button:hover {
            background: #0056b3;
            box-shadow: 0 2px 6px rgba(0, 123, 255, 0.25);
        }

        .msg-error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px 14px;
            border-radius: 4px;
            border: 1px solid #f5c6cb;
            margin-bottom: 15px;
            text-align: center;
        }

        a {
            display: block;
            text-align: center;
            margin-top: 12px;
            color: #007BFF;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <?php include 'partials/header.php'; ?>

    <div class="container">
        <div class="login-form">
            <h2>Login</h2>

            <?php if ($error): ?>
                <div class="msg-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                    value="<?= htmlspecialchars($username) ?>" required>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>

                <button type="submit">Login</button>
            </form>

            <a href="forgot_password.php">Forgot Password?</a>
            <a href="register.php">Register as Shopkeeper or Customer</a>
        </div>
    </div>

</body>

</html>
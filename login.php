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
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
        }

        nav {
            background: #007BFF;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
        }

        nav a {
            color: white;
            text-decoration: none;
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
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #007BFF;
            text-align: center;
            margin-bottom: 20px;
        }

        label {
            font-weight: bold;
            margin-top: 10px;
            display: block;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        button {
            width: 100%;
            background: #007BFF;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 4px;
            margin-top: 15px;
            cursor: pointer;
        }

        button:hover {
            background: #0056b3;
        }

        .msg-error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
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

    <nav>
        <strong>Multi-Vendor Marketplace</strong>
        <a href="index.php">Home</a>
    </nav>

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
<?php

require_once 'config.php';

if (isLoggedIn()) {
    redirectByRole('admin.php', 'shopkeeper.php', 'customer.php');
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    if ($role !== 'customer' && $role !== 'shopkeeper') {
        $error = 'Invalid role selected.';
    } else {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "SELECT id FROM users WHERE username = ? OR email = ?"
            );
            $stmt->execute([$username, $email]);

            if ($stmt->fetch()) {
                $error = 'Username or email already exists.';
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO users (username, email, password, role)
                     VALUES (?, ?, ?, ?)"
                );
                $stmt->execute([$username, $email, $hashedPassword, $role]);

                $userId = $pdo->lastInsertId();


                if ($role === 'shopkeeper') {

                    $storeName = trim($_POST['store_name'] ?? '');
                    $address   = trim($_POST['address'] ?? '');

                    if (empty($storeName) || empty($address)) {
                        throw new Exception(
                            'Store name and address are required for shopkeepers.'
                        );
                    }

                    $stmt = $pdo->prepare(
                        "INSERT INTO vendors (user_id, store_name, address)
                         VALUES (?, ?, ?)"
                    );
                    $stmt->execute([$userId, $storeName, $address]);
                }

                $success = 'Registration successful! Redirecting to login...';
                header("refresh:2; url=login.php");
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Multi-Vendor Marketplace</title>

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

        .register-form {
            background: white;
            padding: 40px;
            border-radius: 8px;
            border: 1px solid #eee;
            width: 100%;
            max-width: 450px;
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

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-family: inherit;
            font-size: 15px;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        input:focus,
        select:focus,
        textarea:focus {
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

        .msg-success {
            background: #d4edda;
            color: #155724;
            padding: 10px 14px;
            border-radius: 4px;
            border: 1px solid #c3e6cb;
            margin-bottom: 15px;
            text-align: center;
        }

        .role-section {
            display: none;
        }

        .role-section.active {
            display: block;
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
        <div class="register-form">
            <h2>Register</h2>

            <?php if ($error): ?>
                <div class="msg-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="msg-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST">
                <label>Username</label>
                <input type="text" name="username" required>

                <label>Email</label>
                <input type="email" name="email" required>

                <label>Password</label>
                <input type="password" name="password" minlength="6" required>

                <label>Role</label>
                <select name="role" id="role" onchange="toggleRole()" required>
                    <option value="">Select role</option>
                    <option value="customer">Customer</option>
                    <option value="shopkeeper">Shopkeeper</option>
                </select>

                <div id="shopkeeperFields" class="role-section">
                    <label>Store Name</label>
                    <input type="text" name="store_name">

                    <label>Address</label>
                    <textarea name="address"></textarea>
                </div>

                <button type="submit">Register</button>
            </form>

            <a href="login.php">Already have an account? Login</a>
        </div>
    </div>

    <script>
        function toggleRole() {
            const role = document.getElementById('role').value;
            document.getElementById('shopkeeperFields')
                .classList.toggle('active', role === 'shopkeeper');
        }
    </script>

</body>

</html>
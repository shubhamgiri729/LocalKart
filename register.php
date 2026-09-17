<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirectByRole('admin.php', 'shopkeeper.php', 'customer.php');
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

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
<title>Register — LocalKart</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --paper: #FBF8F1;
    --paper-alt: #F1E9DA;
    --ink: #23291D;
    --ink-soft: #565C4E;
    --line: #E3DBC8;
    --moss: #2F5233;
    --moss-dark: #20391F;
    --marigold: #E7A62F;
    --marigold-dark: #C98A1B;
    --brick: #A63D2F;
    --white: #FFFFFF;
    --radius-card: 12px;
    --radius-pill: 999px;
    --shadow-soft: 0 1px 2px rgba(35,41,29,0.06), 0 6px 16px rgba(35,41,29,0.05);
  }

  * { box-sizing: border-box; }
  body {
    margin: 0;
    background: var(--paper);
    color: var(--ink);
    font-family: 'Inter', sans-serif;
    line-height: 1.55;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
  }

  h1, h2, h3, h4 {
    font-family: 'Fraunces', serif;
    color: var(--ink);
    font-weight: 600;
    letter-spacing: -0.01em;
  }

  a { color: inherit; text-decoration: none; }

  .container {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px 20px;
  }

  .register-form {
    background: var(--white);
    padding: 40px;
    border-radius: var(--radius-card);
    border: 1px solid var(--line);
    width: 100%;
    max-width: 440px;
    box-shadow: var(--shadow-soft);
  }

  h2 {
    font-size: 26px;
    text-align: center;
    margin-bottom: 24px;
  }

  label {
    font-weight: 600;
    margin-top: 16px;
    display: block;
    color: var(--ink);
    font-size: 13.5px;
  }

  input,
  select,
  textarea {
    width: 100%;
    padding: 11px 14px;
    margin-top: 6px;
    border: 1px solid var(--line);
    border-radius: 8px;
    font-family: inherit;
    font-size: 14px;
    background: var(--white);
    color: var(--ink);
    transition: border-color 0.15s, box-shadow 0.15s;
  }

  input:focus,
  select:focus,
  textarea:focus {
    outline: none;
    border-color: var(--moss);
    box-shadow: 0 0 0 3px rgba(47, 82, 51, 0.15);
  }

  button {
    width: 100%;
    background: var(--moss);
    color: white;
    padding: 12px;
    border: none;
    border-radius: var(--radius-pill);
    font-weight: 600;
    font-size: 14px;
    margin-top: 24px;
    cursor: pointer;
    transition: background 0.15s, box-shadow 0.15s;
  }

  button:hover {
    background: var(--moss-dark);
    box-shadow: 0 6px 16px rgba(47, 82, 51, 0.28);
  }

  .msg-error {
    background: #FCE8E6;
    color: var(--brick);
    padding: 12px 16px;
    border-radius: 8px;
    border: 1px solid #FAD2D0;
    margin-bottom: 20px;
    text-align: center;
    font-size: 14px;
  }

  .msg-success {
    background: #EAF3EC;
    color: var(--moss-dark);
    padding: 12px 16px;
    border-radius: 8px;
    border: 1px solid #C8DED0;
    margin-bottom: 20px;
    text-align: center;
    font-size: 14px;
  }

  .role-section {
    display: none;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed var(--line);
  }

  .role-section.active {
    display: block;
  }

  .auth-link {
    display: block;
    text-align: center;
    margin-top: 20px;
    color: var(--ink-soft);
    font-size: 13.5px;
  }

  .auth-link a {
    color: var(--moss);
    font-weight: 600;
  }

  .auth-link a:hover {
    text-decoration: underline;
  }
</style>
</head>
<body>

    <?php include 'partials/header.php'; ?>

    <div class="container">
        <div class="register-form">
            <h2>Create Account</h2>

            <?php if ($error): ?>
                <div class="msg-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="msg-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php csrfField(); ?>
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required placeholder="Choose a username">

                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="name@example.com">

                <label for="password">Password</label>
                <input type="password" id="password" name="password" minlength="6" required placeholder="At least 6 characters">

                <label for="role">I want to join as</label>
                <select name="role" id="role" onchange="toggleRole()" required>
                    <option value="">Select your role</option>
                    <option value="customer">Customer</option>
                    <option value="shopkeeper">Shopkeeper (Vendor)</option>
                </select>

                <div id="shopkeeperFields" class="role-section">
                    <label for="store_name">Store Name</label>
                    <input type="text" id="store_name" name="store_name" placeholder="Your Local Store Name">

                    <label for="address">Store Address</label>
                    <textarea id="address" name="address" rows="3" placeholder="Where is your physical store located?"></textarea>
                </div>

                <button type="submit">Create Account</button>
            </form>

            <div class="auth-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>

    <script>
        function toggleRole() {
            const role = document.getElementById('role').value;
            document.getElementById('shopkeeperFields')
                .classList.toggle('active', role === 'shopkeeper');
        }
    </script>

</body>
</html>
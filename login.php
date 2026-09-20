<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirectByRole('admin.php', 'shopkeeper.php', 'customer.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $ip = clientIp();

    if (tooManyLoginAttempts($pdo, $ip)) {
        $error = 'Too many failed login attempts. Please wait ' . LOGIN_ATTEMPT_WINDOW_MINUTES . ' minutes and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                clearLoginAttempts($pdo, $ip);

                // Regenerate the session ID now that the user is authenticated,
                // so a session ID issued before login (which an attacker could
                // have planted, e.g. via a shared link) can't be reused to hijack
                // this now-authenticated session. true = destroy the old session.
                session_regenerate_id(true);

                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];

                redirectByRole('admin.php', 'shopkeeper.php', 'customer.php');
                exit;
            } else {
                recordFailedLogin($pdo, $ip, $username);
                $error = 'Invalid username or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — LocalKart</title>
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
    --shadow-lift: 0 10px 28px rgba(35,41,29,0.12);
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

  .login-form {
    background: var(--white);
    padding: 40px;
    border-radius: var(--radius-card);
    border: 1px solid var(--line);
    width: 100%;
    max-width: 420px;
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

  input {
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

  input:focus {
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

  .auth-links {
    margin-top: 20px;
    text-align: center;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .auth-links a {
    color: var(--ink-soft);
    font-size: 13.5px;
    transition: color 0.15s;
  }

  .auth-links a:hover {
    color: var(--moss);
    text-decoration: underline;
  }
</style>
</head>
<body>

    <?php include 'partials/header.php'; ?>

    <div class="container">
        <div class="login-form">
            <h2>Welcome Back</h2>

            <?php if ($error): ?>
                <div class="msg-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php csrfField(); ?>
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                    value="<?= htmlspecialchars($username) ?>" required placeholder="Enter your username">

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">

                <button type="submit">Sign In</button>
            </form>

            <div class="auth-links">
                <a href="forgot_password.php">Forgot Password?</a>
                <a href="register.php">Don't have an account? Register</a>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>

</body>
</html>
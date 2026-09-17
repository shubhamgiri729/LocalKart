<?php
require_once 'config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $email = trim($_POST['email']);

    if (!empty($email)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 30 * 60); // token valid for 30 minutes

                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
                $stmt->execute([$token, $expires, $email]);
                $resetLink = "http://localhost/reset_password.php?token=" . $token;

                $message = "
                    <div class='msg msg-success'>
                        Password reset link has been generated successfully. It expires in 30 minutes.<br><br>
                        <strong>Reset Link (for localhost):</strong><br>
                        <a href='$resetLink'>$resetLink</a>
                    </div>
                ";
            } else {
                $message = "<div class='msg msg-error'>No account found with this email.</div>";
            }
        } catch (PDOException $e) {
            $message = "<div class='msg msg-error'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password — LocalKart</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        h2 {
            font-family: 'Fraunces', serif;
            color: var(--ink);
            font-weight: 600;
            letter-spacing: -0.01em;
            text-align: center;
            margin-top: 0;
            margin-bottom: 24px;
            font-size: 28px;
        }

        a { color: var(--moss); text-decoration: none; font-weight: 500; }
        a:hover { text-decoration: underline; }

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .auth-container {
            background: var(--white);
            padding: 40px;
            border-radius: var(--radius-card);
            border: 1px solid var(--line);
            width: 100%;
            max-width: 420px;
            box-shadow: var(--shadow-soft);
        }

        .auth-container label {
            font-weight: 600;
            color: var(--ink-soft);
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .auth-container input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid var(--line);
            font-family: inherit;
            font-size: 14px;
            background: var(--white);
            color: var(--ink);
            margin-bottom: 16px;
            box-sizing: border-box;
        }

        .auth-container input:focus {
            outline: none;
            border-color: var(--moss);
            box-shadow: 0 0 0 3px rgba(47, 82, 51, 0.15);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            background: var(--moss);
            color: white;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-weight: 600;
            font-size: 15px;
            padding: 12px 20px;
            border-radius: var(--radius-pill);
            transition: background .15s, box-shadow .15s;
            margin-top: 4px;
        }

        .btn:hover {
            background: var(--moss-dark);
            box-shadow: 0 6px 16px rgba(47,82,51,.28);
        }

        .msg {
            margin-top: 20px;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
        }

        .msg-success {
            background: #EAF3EC;
            color: var(--moss-dark);
            border: 1px solid #C8DED0;
        }

        .msg-error {
            background: #FCE8E6;
            color: var(--brick);
            border: 1px solid #FAD2D0;
        }

        .msg a {
            word-break: break-all;
            color: var(--moss-dark);
            text-decoration: underline;
        }

        .text-center {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: var(--ink-soft);
        }
    </style>
</head>
<body>

    <?php include 'partials/header.php'; ?>

    <div class="main-content">
        <div class="auth-container">
            <h2>Forgot Password</h2>

            <form method="POST">
                <?php csrfField(); ?>
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="Enter your registered email">

                <button type="submit" class="btn">Send Reset Link</button>
            </form>

            <?php if (!empty($message)) echo $message; ?>

            <div class="text-center">
                Remembered your password? 
                <a href="login.php">Login here</a>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>

</body>
</html>
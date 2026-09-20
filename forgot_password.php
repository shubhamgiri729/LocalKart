<?php
require_once 'config.php';
require_once 'mailer.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='msg msg-error'>Please enter a valid email address.</div>";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $mailFailed = false;

            if ($user) {
                // The raw token only ever goes into the email. The database stores a
                // SHA-256 hash of it (64 hex chars, fits reset_token VARCHAR(64)), so a
                // leaked database can't be used to reset anyone's password.
                $token     = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expires   = date('Y-m-d H:i:s', time() + 30 * 60); // valid for 30 minutes

                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
                $stmt->execute([$tokenHash, $expires, $user['id']]);

                $resetLink = rtrim(APP_URL, '/') . '/reset_password.php?token=' . $token;
                $safeName  = htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');

                $htmlBody = "
                    <div style='font-family:Arial,sans-serif;max-width:480px;margin:auto;color:#23291D'>
                        <h2 style='color:#2F5233'>Reset your LocalKart password</h2>
                        <p>Hi {$safeName},</p>
                        <p>We received a request to reset your password. Click the button below to choose a new one.
                           This link expires in 30 minutes.</p>
                        <p style='margin:24px 0'>
                            <a href='{$resetLink}'
                               style='background:#2F5233;color:#fff;padding:12px 24px;border-radius:999px;text-decoration:none;font-weight:600'>
                                Reset Password
                            </a>
                        </p>
                        <p style='font-size:13px;color:#565C4E'>Or copy this link into your browser:<br>{$resetLink}</p>
                        <p style='font-size:13px;color:#565C4E'>If you didn't request this, you can safely ignore this email — your password won't change.</p>
                    </div>
                ";
                $textBody = "Hi {$user['username']},\n\n"
                    . "We received a request to reset your LocalKart password. Open this link to choose a new one (valid for 30 minutes):\n\n"
                    . "{$resetLink}\n\n"
                    . "If you didn't request this, you can ignore this email.";

                if (!sendMail($email, 'Reset your LocalKart password', $htmlBody, $textBody)) {
                    $mailFailed = true;
                    // Don't leave a live token behind for an email that never went out.
                    $pdo->prepare("UPDATE users SET reset_token = NULL, reset_token_expires = NULL WHERE id = ?")
                        ->execute([$user['id']]);
                }
            }

            if ($mailFailed) {
                $message = "<div class='msg msg-error'>We couldn't send the email right now. Please try again in a few minutes.</div>";
            } else {
                // Same wording whether or not the address is registered, so this form
                // can't be used to find out which emails have accounts.
                $message = "<div class='msg msg-success'>If an account exists for that email, a password reset link has been sent. Please check your inbox (and spam folder). The link expires in 30 minutes.</div>";
            }
        } catch (PDOException $e) {
            error_log('forgot_password DB error: ' . $e->getMessage());
            $message = "<div class='msg msg-error'>Something went wrong. Please try again.</div>";
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

        @media (max-width: 480px) {
            .main-content { padding: 24px 16px; }
            .auth-container { padding: 28px 22px; }
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
<?php
require_once 'config.php';

if (!isLoggedIn() || getUserRole() !== 'customer') {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($subject === '' || $message === '') {
        $error = "Please fill in all fields.";
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO helpdesk (user_id, subject, message, status)
                 VALUES (?, ?, ?, 'open')"
            );
            $stmt->execute([$userId, $subject, $message]);
            $success = "Your support ticket has been submitted successfully.";
        } catch (PDOException $e) {
            $error = "Unable to submit ticket. Please try again later.";
        }
    }
}

$tickets = [];
try {
    $stmt = $pdo->prepare(
        "SELECT * FROM helpdesk
         WHERE user_id = ?
         ORDER BY created_at DESC"
    );
    $stmt->execute([$userId]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Unable to load tickets.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Help Desk - Customer Support</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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
        }

        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
        }

        h2,
        h3 {
            color: #007BFF;
            margin-bottom: 15px;
        }

        .section {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #eee;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            margin-bottom: 20px;
        }

        input,
        textarea {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-family: inherit;
            font-size: 15px;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: #007BFF;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
        }

        button {
            background: #007BFF;
            color: #fff;
            padding: 10px 18px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            margin-top: 10px;
            transition: background 0.15s, box-shadow 0.15s;
        }

        button:hover {
            background: #0056b3;
            box-shadow: 0 2px 6px rgba(0, 123, 255, 0.25);
        }

        .msg-success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #c3e6cb;
        }

        .msg-error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
        }

        .ticket {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #eee;
            margin-bottom: 15px;
        }

        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 13px;
            display: inline-block;
        }

        .open {
            background: #ffc107;
        }

        .in_progress {
            background: #17a2b8;
            color: #fff;
        }

        .closed {
            background: #6c757d;
            color: #fff;
        }

        .response {
            background: #e9ffe9;
            padding: 10px;
            margin-top: 10px;
            border-left: 4px solid #28a745;
            border-radius: 4px;
        }
    </style>
</head>

<body>

    <?php include 'partials/header.php'; ?>

    <div class="container">

        <?php if ($success): ?>
            <div class="msg-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="msg-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="section">
            <h3>Create Support Ticket</h3>
            <form method="POST">
                <label>Subject</label>
                <input type="text" name="subject" required>

                <label>Message</label>
                <textarea name="message" rows="5" required></textarea>

                <button type="submit">Submit Ticket</button>
            </form>
        </div>

        <div class="section">
            <h3>Your Tickets</h3>

            <?php if (empty($tickets)): ?>
                <p>No tickets found.</p>
            <?php else: ?>
                <?php foreach ($tickets as $ticket): ?>
                    <div class="ticket">
                        <p><strong>Subject:</strong> <?= htmlspecialchars($ticket['subject']) ?></p>
                        <p><strong>Message:</strong><br><?= nl2br(htmlspecialchars($ticket['message'])) ?></p>
                        <p>
                            <strong>Status:</strong>
                            <span class="status <?= $ticket['status'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                            </span>
                        </p>

                        <?php if (!empty($ticket['response'])): ?>
                            <div class="response">
                                <strong>Admin Reply:</strong><br>
                                <?= nl2br(htmlspecialchars($ticket['response'])) ?>
                            </div>
                        <?php else: ?>
                            <em>No reply yet.</em>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</body>

</html>
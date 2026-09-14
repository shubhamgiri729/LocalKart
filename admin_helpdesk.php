<?php

require_once 'config.php';

if (!isLoggedIn() || getUserRole() !== 'admin') {
    header("Location: login.php");
    exit();
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id'])) {

    $ticketId = (int)$_POST['ticket_id'];
    $response = trim($_POST['response']);

    if (!empty($response)) {
        $stmt = $conn->prepare("
            UPDATE helpdesk 
            SET response = ?, status = 'answered', updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("si", $response, $ticketId);
        $stmt->execute();
        $stmt->close();
    }
}

$tickets = [];
$query = "
    SELECT h.*, u.username
    FROM helpdesk h
    JOIN users u ON h.user_id = u.id
    ORDER BY h.created_at DESC
";

$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $tickets[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Help Desk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        h1 {
            color: #007BFF;
            margin: 20px 0;
        }

        .ticket {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #eee;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        }

        .ticket p {
            margin: 6px 0;
        }

        .status {
            font-size: 0.85em;
            padding: 4px 10px;
            border-radius: 4px;
        }

        .open {
            background: #ffc107;
            color: #212529;
        }

        .answered {
            background: #28a745;
            color: white;
        }

        .closed {
            background: #6c757d;
            color: white;
        }

        .response-box textarea {
            width: 100%;
            height: 80px;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            margin-top: 10px;
            font-family: inherit;
            font-size: 15px;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .response-box textarea:focus {
            outline: none;
            border-color: #007BFF;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
        }

        button {
            margin-top: 8px;
            background: #007BFF;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.15s, box-shadow 0.15s;
        }

        button:hover {
            background: #0056b3;
            box-shadow: 0 2px 6px rgba(0, 123, 255, 0.25);
        }

    </style>
</head>

<body>

    <?php include 'partials/header.php'; ?>

    <div class="container">
        <h1>📩 Help Desk Queries</h1>

    <?php if (empty($tickets)): ?>
        <p>No helpdesk tickets available.</p>
    <?php endif; ?>

    <?php foreach ($tickets as $ticket): ?>
        <div class="ticket">

            <p><strong>User:</strong> <?= htmlspecialchars($ticket['username']) ?></p>
            <p><strong>Subject:</strong> <?= htmlspecialchars($ticket['subject']) ?></p>
            <p><strong>Message:</strong><br>
                <?= nl2br(htmlspecialchars($ticket['message'])) ?>
            </p>

            <p>
                <strong>Status:</strong>
                <span class="status <?= htmlspecialchars($ticket['status']) ?>">
                    <?= ucfirst($ticket['status']) ?>
                </span>
            </p>

            <?php if (!empty($ticket['response'])): ?>
                <p><strong>Response:</strong><br>
                    <?= nl2br(htmlspecialchars($ticket['response'])) ?>
                </p>
            <?php endif; ?>

            <form method="POST" class="response-box">
                <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                <textarea name="response" placeholder="Write your response..." required></textarea>
                <button type="submit">Reply</button>
            </form>

        </div>
    <?php endforeach; ?>

    </div>
</body>

</html>

<?php $conn->close(); ?>
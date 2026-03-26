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
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 20px;
        }

        nav {
            background: #007BFF;
            color: white;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 6px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin: 0 8px;
        }

        nav a:hover {
            text-decoration: underline;
        }

        h1 {
            color: #007BFF;
            margin: 20px 0;
        }

        .ticket {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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
        }

        button {
            margin-top: 8px;
            background: #007BFF;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #0056b3;
        }

        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>

<body>

    <nav>
        <strong>Admin Dashboard</strong>
        <div>
            <a href="adminhelpdesk.php">Help Desk</a>
            <a href="products.php">Products</a>
            <a href="index.php">Home</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

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

</body>

</html>

<?php $conn->close(); ?>
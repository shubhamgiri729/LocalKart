<?php
require_once 'config.php';

if (!isLoggedIn() || getUserRole() !== 'shopkeeper') {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id, store_name FROM vendors WHERE user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$vendor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vendor) {
    die("Vendor profile not found. Please contact admin.");
}

$vendorId = $vendor['id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id'])) {
    requireCsrf();

    $ticketId = (int) $_POST['ticket_id'];
    $response = trim($_POST['response'] ?? '');
    $status   = $_POST['status'] === 'closed' ? 'closed' : 'answered';

    if ($response === '') {
        $message = '<div class="msg msg-error">Reply cannot be empty.</div>';
    } else {
        // vendor_id is checked here too, not just at page load — a shopkeeper
        // can only ever update a ticket that belongs to their own shop.
        $stmt = $pdo->prepare(
            "UPDATE helpdesk
             SET response = ?, status = ?, updated_at = NOW()
             WHERE id = ? AND vendor_id = ?"
        );
        $stmt->execute([$response, $status, $ticketId, $vendorId]);
        header("Location: shopkeeper_helpdesk.php?msg=" . urlencode("Reply sent"));
        exit();
    }
}

if (isset($_GET['msg'])) {
    $message = '<div class="msg msg-success">' . htmlspecialchars($_GET['msg']) . '</div>';
}

$stmt = $pdo->prepare(
    "SELECT h.*, u.username
     FROM helpdesk h
     JOIN users u ON h.user_id = u.id
     WHERE h.vendor_id = ?
     ORDER BY FIELD(h.status, 'open', 'answered', 'closed'), h.created_at DESC"
);
$stmt->execute([$vendorId]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Support Tickets — <?= htmlspecialchars($vendor['store_name']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    body { margin: 0; background: var(--paper); color: var(--ink); font-family: 'Inter', sans-serif; line-height: 1.55; }
    h1, h2, h3, h4 { font-family: 'Fraunces', serif; color: var(--ink); font-weight: 600; letter-spacing: -0.01em; }
    a { color: inherit; text-decoration: none; }
    p { color: var(--ink-soft); font-size: 14px; margin: 8px 0; }

    .container { max-width: 1100px; margin: 0 auto; padding: 40px 20px 70px; }

    .page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 30px; }
    .page-header h2 { font-size: 28px; margin: 0; }
    .page-header p { margin: 4px 0 0; }

    .btn {
        display: inline-flex; align-items: center; justify-content: center; background: var(--moss); color: white;
        border: none; cursor: pointer; font-family: inherit; font-weight: 600; font-size: 14px;
        padding: 10px 20px; border-radius: var(--radius-pill); transition: background .15s, box-shadow .15s;
    }
    .btn:hover { background: var(--moss-dark); box-shadow: 0 6px 16px rgba(47,82,51,.28); }
    .btn-sm { padding: 6px 14px; font-size: 13px; }

    .msg { margin-bottom: 24px; padding: 12px 16px; border-radius: 8px; font-size: 14px; }
    .msg-success { background: #EAF3EC; color: var(--moss-dark); border: 1px solid #C8DED0; }
    .msg-error { background: #FCE8E6; color: var(--brick); border: 1px solid #FAD2D0; }

    .ticket {
        background: var(--white); border: 1px solid var(--line); border-radius: var(--radius-card);
        padding: 24px; margin-bottom: 20px; box-shadow: var(--shadow-soft);
        opacity: 0; transform: translateY(10px); animation: riseIn .35s ease forwards;
    }
    @keyframes riseIn { to { opacity: 1; transform: translateY(0); } }

    .ticket-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--line); font-size: 14px; }

    .status { font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; padding: 4px 10px; border-radius: var(--radius-pill); font-weight: 600; }
    .status-open { background: #FEF3C7; color: #B45309; }
    .status-answered { background: #E2EEF7; color: #3178C6; }
    .status-closed { background: var(--paper-alt); color: var(--ink-soft); }

    .message-block { background: var(--paper); padding: 12px 16px; border-radius: 8px; border-left: 4px solid var(--moss); margin: 12px 0; }
    .response-history { background: #EAF3EC; padding: 12px 16px; border-radius: 8px; border-left: 4px solid var(--moss-dark); margin: 12px 0; }

    .reply-form { display: flex; gap: 10px; align-items: flex-start; flex-wrap: wrap; margin-top: 12px; }
    textarea {
        flex: 1; min-width: 240px; height: 70px; padding: 12px; border-radius: 8px; border: 1px solid var(--line);
        font-family: inherit; font-size: 14px; background: var(--white); color: var(--ink); resize: vertical;
    }
    textarea:focus { outline: none; border-color: var(--moss); box-shadow: 0 0 0 3px rgba(47, 82, 51, 0.15); }
    select { padding: 10px 12px; border-radius: 8px; border: 1px solid var(--line); font-family: inherit; font-size: 14px; background: var(--white); color: var(--ink); }

    @media (max-width: 480px) {
        .container { padding: 24px 14px 50px; }
        .page-header { flex-direction: column; align-items: flex-start; }
        .ticket { padding: 18px; }
        .ticket-meta { flex-direction: column; align-items: flex-start; gap: 6px; }
    }
</style>
</head>
<body>

    <?php include 'partials/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <div>
                <h2>Support Tickets</h2>
                <p>Questions from customers, addressed to <?= htmlspecialchars($vendor['store_name']) ?>.</p>
            </div>
            <a href="shopkeeper.php" class="btn">Back to Dashboard</a>
        </div>

        <?= $message ?>

        <?php if (empty($tickets)): ?>
            <div class="ticket"><p>No tickets for your shop yet.</p></div>
        <?php else: ?>
            <?php foreach ($tickets as $i => $ticket): ?>
                <div class="ticket" style="animation-delay:<?= $i * 0.05 ?>s">
                    <div class="ticket-meta">
                        <span><strong>Customer:</strong> <?= htmlspecialchars($ticket['username']) ?></span>
                        <span class="status status-<?= htmlspecialchars($ticket['status']) ?>">
                            <?= ucfirst($ticket['status']) ?>
                        </span>
                    </div>

                    <p><strong>Subject:</strong> <?= htmlspecialchars($ticket['subject']) ?></p>

                    <div class="message-block">
                        <?= nl2br(htmlspecialchars($ticket['message'])) ?>
                    </div>

                    <?php if (!empty($ticket['response'])): ?>
                        <div class="response-history">
                            <strong style="color:var(--moss-dark);">Your reply:</strong><br>
                            <?= nl2br(htmlspecialchars($ticket['response'])) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($ticket['status'] !== 'closed'): ?>
                        <form method="POST" class="reply-form">
                            <?php csrfField(); ?>
                            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                            <textarea name="response" placeholder="Write your reply..." required><?= htmlspecialchars($ticket['response'] ?? '') ?></textarea>
                            <select name="status">
                                <option value="answered">Reply &amp; keep open</option>
                                <option value="closed">Reply &amp; close ticket</option>
                            </select>
                            <button type="submit" class="btn btn-sm">Send</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include 'partials/footer.php'; ?>

</body>
</html>

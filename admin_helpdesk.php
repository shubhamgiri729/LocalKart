<?php
require_once 'config.php';

if (!isLoggedIn() || getUserRole() !== 'admin') {
    header("Location: login.php");
    exit();
}

$statusFilter = $_GET['status'] ?? '';
$vendorFilter = isset($_GET['vendor']) ? (int) $_GET['vendor'] : 0;

$where = [];
$params = [];

if (in_array($statusFilter, ['open', 'answered', 'closed'], true)) {
    $where[] = "h.status = ?";
    $params[] = $statusFilter;
}
if ($vendorFilter > 0) {
    $where[] = "h.vendor_id = ?";
    $params[] = $vendorFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$tickets = [];
$error = '';
try {
    $stmt = $pdo->prepare("
        SELECT h.*, u.username, v.store_name
        FROM helpdesk h
        JOIN users u ON h.user_id = u.id
        JOIN vendors v ON h.vendor_id = v.id
        $whereSql
        ORDER BY h.created_at DESC
    ");
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Unable to load tickets.";
}

$counts = ['open' => 0, 'answered' => 0, 'closed' => 0];
try {
    $stmt = $pdo->query("SELECT status, COUNT(*) AS total FROM helpdesk GROUP BY status");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $counts[$row['status']] = (int) $row['total'];
    }
} catch (PDOException $e) {

}

$vendors = $pdo->query("SELECT id, store_name FROM vendors ORDER BY store_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Help Desk Tracking — LocalKart</title>
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

    .page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; }
    .page-header h2 { font-size: 28px; margin: 0; }
    .page-header p { margin: 4px 0 0; }

    .btn {
        display: inline-flex; align-items: center; justify-content: center; background: var(--moss); color: white;
        border: none; cursor: pointer; font-family: inherit; font-weight: 600; font-size: 14px;
        padding: 10px 20px; border-radius: var(--radius-pill); transition: background .15s, box-shadow .15s;
    }
    .btn:hover { background: var(--moss-dark); box-shadow: 0 6px 16px rgba(47,82,51,.28); }

    /* Summary strip — always shows totals across every shop, regardless of filters below. */
    .summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 24px; }
    .summary-card { background: var(--white); border: 1px solid var(--line); border-radius: var(--radius-card); padding: 16px 18px; box-shadow: var(--shadow-soft); }
    .summary-card .num { font-family: 'Fraunces', serif; font-size: 26px; font-weight: 700; }
    .summary-card .label { font-size: 12.5px; color: var(--ink-soft); text-transform: uppercase; letter-spacing: 0.04em; margin-top: 2px; }
    .summary-card.open .num { color: #B45309; }
    .summary-card.answered .num { color: #3178C6; }
    .summary-card.closed .num { color: var(--ink-soft); }

    .filters { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .filters select {
        padding: 9px 14px; border-radius: var(--radius-pill); border: 1px solid var(--line);
        font-family: inherit; font-size: 13.5px; background: var(--white); color: var(--ink); cursor: pointer;
    }

    .msg-error { background: #FCE8E6; color: var(--brick); padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #FAD2D0; font-size: 14px; }

    table { width: 100%; border-collapse: collapse; background: var(--white); border-radius: var(--radius-card); overflow: hidden; box-shadow: var(--shadow-soft); }
    th, td { text-align: left; padding: 12px 14px; border-bottom: 1px solid var(--line); font-size: 13.5px; vertical-align: top; }
    th { background: var(--paper-alt); font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.04em; color: var(--ink-soft); }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: var(--paper); }

    .status { font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.04em; padding: 3px 9px; border-radius: var(--radius-pill); font-weight: 700; white-space: nowrap; }
    .status-open { background: #FEF3C7; color: #B45309; }
    .status-answered { background: #E2EEF7; color: #3178C6; }
    .status-closed { background: var(--paper-alt); color: var(--ink-soft); }

    .msg-cell { max-width: 260px; color: var(--ink-soft); }
    .empty-row td { text-align: center; padding: 40px; color: var(--ink-soft); }
</style>
</head>
<body>

    <?php include 'partials/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <div>
                <h2>Help Desk Tracking</h2>
                <p>Every shop's tickets, for oversight. Replies and closing are handled by the shop itself.</p>
            </div>
            <a href="admin.php" class="btn">Back to Dashboard</a>
        </div>

        <div class="summary">
            <div class="summary-card open">
                <div class="num"><?= $counts['open'] ?></div>
                <div class="label">Open</div>
            </div>
            <div class="summary-card answered">
                <div class="num"><?= $counts['answered'] ?></div>
                <div class="label">Answered</div>
            </div>
            <div class="summary-card closed">
                <div class="num"><?= $counts['closed'] ?></div>
                <div class="label">Closed</div>
            </div>
        </div>

        <form method="GET" class="filters">
            <select name="status" onchange="this.form.submit()">
                <option value="">All statuses</option>
                <option value="open" <?= $statusFilter === 'open' ? 'selected' : '' ?>>Open</option>
                <option value="answered" <?= $statusFilter === 'answered' ? 'selected' : '' ?>>Answered</option>
                <option value="closed" <?= $statusFilter === 'closed' ? 'selected' : '' ?>>Closed</option>
            </select>
            <select name="vendor" onchange="this.form.submit()">
                <option value="0">All shops</option>
                <?php foreach ($vendors as $v): ?>
                    <option value="<?= $v['id'] ?>" <?= $vendorFilter === (int)$v['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($v['store_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($error): ?>
            <div class="msg-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Shop</th>
                    <th>Customer</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Reply</th>
                    <th>Opened</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                    <tr class="empty-row"><td colspan="6">No tickets match this filter.</td></tr>
                <?php else: ?>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><?= htmlspecialchars($ticket['store_name']) ?></td>
                            <td><?= htmlspecialchars($ticket['username']) ?></td>
                            <td><?= htmlspecialchars($ticket['subject']) ?></td>
                            <td><span class="status status-<?= htmlspecialchars($ticket['status']) ?>"><?= ucfirst($ticket['status']) ?></span></td>
                            <td class="msg-cell"><?= $ticket['response'] ? htmlspecialchars($ticket['response']) : '—' ?></td>
                            <td><?= htmlspecialchars(date('d M Y', strtotime($ticket['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php include 'partials/footer.php'; ?>

</body>
</html>
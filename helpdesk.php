<?php
require_once 'config.php';

if (!isLoggedIn() || getUserRole() !== 'customer') {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

$vendors = $pdo->query("SELECT id, store_name FROM vendors ORDER BY store_name")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $vendorId = (int) ($_POST['vendor_id'] ?? 0);
    $subject  = trim($_POST['subject'] ?? '');
    $message  = trim($_POST['message'] ?? '');

    if ($vendorId <= 0 || $subject === '' || $message === '') {
        $error = "Please choose a shop and fill in all fields.";
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO helpdesk (user_id, vendor_id, subject, message, status)
                 VALUES (?, ?, ?, ?, 'open')"
            );
            $stmt->execute([$userId, $vendorId, $subject, $message]);
            $success = "Your ticket has been sent to the shop. They'll respond here.";
        } catch (PDOException $e) {
            $error = "Unable to submit ticket. Please try again later.";
        }
    }
}

$tickets = [];
try {
    $stmt = $pdo->prepare(
        "SELECT h.*, v.store_name
         FROM helpdesk h
         JOIN vendors v ON h.vendor_id = v.id
         WHERE h.user_id = ?
         ORDER BY h.created_at DESC"
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Help Desk — LocalKart Support</title>
<style>
  :root{
    --paper:#FBF8F1;
    --paper-alt:#F1E9DA;
    --ink:#23291D;
    --ink-soft:#565C4E;
    --line:#E3DBC8;
    --moss:#2F5233;
    --moss-dark:#20391F;
    --marigold:#E7A62F;
    --marigold-dark:#C98A1B;
    --brick:#A63D2F;
    --white:#FFFFFF;
    --radius-card:10px;
    --radius-pill:999px;
    --shadow-soft: 0 1px 2px rgba(35,41,29,0.06), 0 6px 16px rgba(35,41,29,0.05);
  }
  *{box-sizing:border-box;}
  body{ margin:0; background:var(--paper); color:var(--ink); font-family:'Inter',sans-serif; line-height:1.55; }
  h1,h2,h3,h4{ font-family:'Fraunces',serif; color:var(--ink); margin:0; font-weight:600; letter-spacing:-0.01em; }
  p{margin:0;} a{color:inherit; text-decoration:none;}
  .wrap{max-width:900px; margin:0 auto; padding:44px 28px 70px;}

  .page-title{ margin-bottom:28px; }
  .page-title h2{ font-size:28px; color:var(--moss-dark); }
  .page-title p{ color:var(--ink-soft); font-size:14.5px; margin-top:4px; }

  .section{ background:var(--white); padding:28px; border-radius:var(--radius-card); border:1px solid var(--line); box-shadow:var(--shadow-soft); margin-bottom:24px; }
  .section h3{ font-size:20px; margin-bottom:18px; color:var(--ink); }

  label{ display:block; font-weight:500; font-size:13.5px; color:var(--ink-soft); margin-bottom:6px; }
  input, textarea, select{
    width:100%; padding:11px 14px; margin-bottom:16px; border-radius:8px; border:1px solid var(--line);
    font:inherit; font-size:14.5px; background:var(--white); color:var(--ink); transition:border-color .15s, box-shadow .15s;
  }
  input:focus, textarea:focus, select:focus{
    outline:none; border-color:var(--moss); box-shadow:0 0 0 3px rgba(47,82,51,0.12);
  }

  .btn{
    display:inline-flex; align-items:center; justify-content:center; gap:8px; border:none; cursor:pointer;
    font-family:inherit; font-weight:600; font-size:14px; padding:11px 24px; border-radius:var(--radius-pill);
    background:var(--moss); color:#fff; transition:background .15s, box-shadow .15s;
  }
  .btn:hover{ background:var(--moss-dark); box-shadow:0 6px 16px rgba(47,82,51,.28); }

  .msg-success{ background:#E2EFE3; color:var(--moss-dark); padding:12px 16px; border-radius:8px; margin-bottom:20px; border:1px solid #C4E2C7; font-size:14px; }
  .msg-error{ background:#FDF2F0; color:var(--brick); padding:12px 16px; border-radius:8px; margin-bottom:20px; border:1px solid #F8D7DA; font-size:14px; }

  .ticket{ background:var(--paper); padding:20px; border-radius:8px; border:1px solid var(--line); margin-bottom:16px; opacity:0; transform:translateY(10px); animation:riseIn .35s ease forwards; }
  @keyframes riseIn{ to{ opacity:1; transform:translateY(0); } }
  .ticket-header{ display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:6px; flex-wrap:wrap; }
  .ticket-subject{ font-weight:600; font-size:16px; color:var(--ink); }
  .ticket-shop{ font-size:12.5px; color:var(--ink-soft); margin-bottom:10px; }
  .ticket-shop strong{ color:var(--moss-dark); }

  .status{ padding:4px 10px; border-radius:var(--radius-pill); font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.03em; white-space:nowrap; }
  .status.open{ background:var(--marigold); color:#fff; }
  .status.answered{ background:#3178C6; color:#fff; }
  .status.closed{ background:var(--ink-soft); color:#fff; }

  .ticket-msg{ color:var(--ink-soft); font-size:14px; margin-bottom:14px; white-space:pre-line; }
  .response{ background:var(--white); padding:14px; margin-top:12px; border-left:3px solid var(--moss); border-radius:4px; font-size:13.5px; }
  .response strong{ color:var(--moss-dark); display:block; margin-bottom:4px; }
  .no-reply{ font-size:13px; color:var(--ink-soft); font-style:italic; }
</style>
</head>
<body>

<?php include 'partials/header.php'; ?>

<div class="wrap">
    <div class="page-title">
        <h2>Customer Support Help Desk</h2>
        <p>Pick the shop your question is about — your ticket goes straight to them.</p>
    </div>

    <?php if ($success): ?>
        <div class="msg-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="msg-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="section">
        <h3>Create Support Ticket</h3>
        <form method="POST">
            <?php csrfField(); ?>
            <label>Which shop is this about?</label>
            <select name="vendor_id" required>
                <option value="">Select a shop…</option>
                <?php foreach ($vendors as $vendor): ?>
                    <option value="<?= $vendor['id'] ?>">
                        <?= htmlspecialchars($vendor['store_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Issue Subject</label>
            <input type="text" name="subject" placeholder="Briefly describe your issue..." required>

            <label>Detailed Message</label>
            <textarea name="message" rows="5" placeholder="Provide order number, product name, or specific details..." required></textarea>

            <button type="submit" class="btn">Submit Ticket</button>
        </form>
    </div>

    <div class="section">
        <h3>Your Support Tickets</h3>

        <?php if (empty($tickets)): ?>
            <p style="color:var(--ink-soft); font-size:14px;">You haven't submitted any support tickets yet.</p>
        <?php else: ?>
            <?php foreach ($tickets as $i => $ticket): ?>
                <div class="ticket" style="animation-delay:<?= $i * 0.05 ?>s">
                    <div class="ticket-header">
                        <span class="ticket-subject"><?= htmlspecialchars($ticket['subject']) ?></span>
                        <span class="status <?= htmlspecialchars($ticket['status']) ?>">
                            <?= ucfirst($ticket['status']) ?>
                        </span>
                    </div>
                    <div class="ticket-shop">To: <strong><?= htmlspecialchars($ticket['store_name']) ?></strong></div>
                    <div class="ticket-msg"><?= htmlspecialchars($ticket['message']) ?></div>

                    <?php if (!empty($ticket['response'])): ?>
                        <div class="response">
                            <strong><?= htmlspecialchars($ticket['store_name']) ?> replied:</strong>
                            <?= htmlspecialchars($ticket['response']) ?>
                        </div>
                    <?php else: ?>
                        <div class="no-reply">Awaiting a reply from the shop.</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'partials/footer.php'; ?>

</body>
</html>
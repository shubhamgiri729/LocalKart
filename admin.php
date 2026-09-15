<?php
require_once 'config.php';

if (!isLoggedIn() || getUserRole() !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';

if (isset($_GET['msg'])) {
    $message = '<div class="msg msg-success">' . htmlspecialchars($_GET['msg']) . '</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_vendor_verification') {
    $vendorId = (int) $_POST['vendor_id'];
    $currentStatus = (int) $_POST['current_status'];
    $newStatus = $currentStatus ? 0 : 1;

    try {
        $stmt = $pdo->prepare("UPDATE vendors SET verified = ? WHERE id = ?");
        $stmt->execute([$newStatus, $vendorId]);
        header("Location: admin.php?msg=Vendor verification updated");
        exit();
    } catch (PDOException $e) {
        $message = '<div class="msg msg-error">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_category') {
    $categoryName = trim($_POST['cat_name']);

    if ($categoryName === '') {
        $message = '<div class="msg msg-error">Category name cannot be empty</div>';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$categoryName]);
            header("Location: admin.php?msg=Category added successfully");
            exit();
        } catch (PDOException $e) {
            $message = '<div class="msg msg-error">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$vendors = $pdo->query("
    SELECT v.*, u.username, u.email
    FROM vendors v
    JOIN users u ON v.user_id = u.id
    ORDER BY v.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$orders = $pdo->query("
    SELECT o.*, u.username AS customer_name
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    ORDER BY o.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard — LocalKart</title>
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
    max-width: 1100px;
    width: 100%;
    margin: 0 auto;
    padding: 40px 20px;
  }

  .admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 30px;
  }

  .admin-header h2 {
    font-size: 30px;
    margin: 0;
  }

  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--moss);
    color: white;
    border: none;
    cursor: pointer;
    font-family: inherit;
    font-weight: 600;
    font-size: 14px;
    padding: 10px 20px;
    border-radius: var(--radius-pill);
    transition: background .15s, box-shadow .15s;
  }

  .btn:hover {
    background: var(--moss-dark);
    box-shadow: 0 6px 16px rgba(47,82,51,.28);
  }

  .btn-sm {
    padding: 6px 14px;
    font-size: 13px;
  }

  .msg {
    margin-bottom: 24px;
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

  section {
    background: var(--white);
    border: 1px solid var(--line);
    border-radius: var(--radius-card);
    padding: 24px;
    margin-bottom: 30px;
    box-shadow: var(--shadow-soft);
  }

  section h2 {
    font-size: 20px;
    margin-top: 0;
    margin-bottom: 16px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--line);
  }

  table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    font-size: 14px;
    margin-top: 8px;
  }

  th, td {
    padding: 12px 14px;
    border-bottom: 1px solid var(--line);
  }

  th {
    font-weight: 600;
    color: var(--ink-soft);
    background: var(--paper);
  }

  tr:last-child td {
    border-bottom: none;
  }

  .category-form {
    display: flex;
    gap: 10px;
    margin-bottom: 16px;
    max-width: 450px;
  }

  .category-form input {
    flex: 1;
    padding: 10px 14px;
    border: 1px solid var(--line);
    border-radius: 8px;
    font-family: inherit;
    font-size: 14px;
    background: var(--white);
    color: var(--ink);
  }

  .category-form input:focus {
    outline: none;
    border-color: var(--moss);
    box-shadow: 0 0 0 3px rgba(47, 82, 51, 0.15);
  }

  .category-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  .category-list li {
    background: var(--paper-alt);
    border: 1px solid var(--line);
    padding: 6px 14px;
    border-radius: var(--radius-pill);
    font-size: 13.5px;
    font-weight: 500;
  }

  p {
    color: var(--ink-soft);
    font-size: 14px;
    margin: 8px 0;
  }
</style>
</head>
<body>

    <?php include 'partials/header.php'; ?>

    <div class="container">
        <div class="admin-header">
            <h2>Admin Dashboard</h2>
            <a href="admin_helpdesk.php" class="btn">Manage Tickets</a>
        </div>

        <?= $message ?>

        <section>
            <h2>Registered Users</h2>
            <?php if ($users): ?>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                    </tr>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['role']) ?></td>
                            <td><?= $u['created_at'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>No users found.</p>
            <?php endif; ?>
        </section>

        <section>
            <h2>Vendor Management</h2>
            <?php if ($vendors): ?>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Store Name</th>
                        <th>Owner</th>
                        <th>Verified</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($vendors as $v): ?>
                        <tr>
                            <td><?= $v['id'] ?></td>
                            <td><?= htmlspecialchars($v['store_name']) ?></td>
                            <td><?= htmlspecialchars($v['username']) ?></td>
                            <td><?= $v['verified'] ? 'Yes' : 'No' ?></td>
                            <td>
                                <form method="post" style="margin:0;">
                                    <input type="hidden" name="action" value="toggle_vendor_verification">
                                    <input type="hidden" name="vendor_id" value="<?= $v['id'] ?>">
                                    <input type="hidden" name="current_status" value="<?= $v['verified'] ?>">
                                    <button type="submit" class="btn btn-sm">
                                        <?= $v['verified'] ? 'Unverify' : 'Verify' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>No vendors found.</p>
            <?php endif; ?>
        </section>

        <section>
            <h2>Marketplace Orders</h2>
            <?php if ($orders): ?>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><?= $o['id'] ?></td>
                            <td><?= htmlspecialchars($o['customer_name']) ?></td>
                            <td>₹<?= number_format($o['total'], 2) ?></td>
                            <td><?= htmlspecialchars($o['status']) ?></td>
                            <td><?= $o['created_at'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>No orders found.</p>
            <?php endif; ?>
        </section>

        <section>
            <h2>Product Categories</h2>

            <form method="post" class="category-form">
                <input type="hidden" name="action" value="add_category">
                <input type="text" name="cat_name" placeholder="Enter category name" required>
                <button type="submit" class="btn btn-sm">Add Category</button>
            </form>

            <?php if ($categories): ?>
                <ul class="category-list">
                    <?php foreach ($categories as $c): ?>
                        <li><?= htmlspecialchars($c['name']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No categories found.</p>
            <?php endif; ?>
        </section>
    </div>

    <?php include 'partials/footer.php'; ?>

</body>
</html>
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
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <?php include 'partials/header.php'; ?>

    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <h2 style="margin:0;">Admin Panel</h2>
            <a href="admin_helpdesk.php" class="btn">Manage Tickets</a>
        </div>

        <?= $message ?>

        <section>
            <h2>Users</h2>
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
            <h2>Vendors</h2>
            <?php if ($vendors): ?>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Store</th>
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
                                <form method="post">
                                    <input type="hidden" name="action" value="toggle_vendor_verification">
                                    <input type="hidden" name="vendor_id" value="<?= $v['id'] ?>">
                                    <input type="hidden" name="current_status" value="<?= $v['verified'] ?>">
                                    <button type="submit">
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
            <h2>Orders</h2>
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
            <h2>Categories</h2>

            <form method="post">
                <input type="hidden" name="action" value="add_category">
                <input type="text" name="cat_name" placeholder="Category name" required>
                <button type="submit">Add</button>
            </form>

            <?php if ($categories): ?>
                <ul>
                    <?php foreach ($categories as $c): ?>
                        <li><?= htmlspecialchars($c['name']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No categories found.</p>
            <?php endif; ?>
        </section>

    </div>
</body>

</html>
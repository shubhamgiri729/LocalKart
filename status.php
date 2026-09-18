<?php
require_once 'config.php';

if (!isLoggedIn() || getUserRole() !== 'shopkeeper') {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$error = '';
$message = '';

$stmt = $pdo->prepare("
    SELECT v.* 
    FROM vendors v 
    JOIN users u ON v.user_id = u.id 
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$vendor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vendor) {
    die("Vendor profile not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dispatch_order'], $_POST['order_id'])) {
    requireCsrf();

    $orderId = (int) $_POST['order_id'];

    try {
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'dispatched'
            WHERE id = ?
            AND id IN (
                SELECT o.id FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                WHERE oi.vendor_id = ?
            )
        ");
        $stmt->execute([$orderId, $vendor['id']]);

        header("Location: status.php?msg=Order #$orderId dispatched successfully");
        exit();
    } catch (PDOException $e) {
        $error = "Failed to update order status.";
    }
}

$stmt = $pdo->prepare("
    SELECT oi.*, o.status, o.created_at, 
           p.name AS product_name, 
           u.username AS customer_name
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    JOIN users u ON o.customer_id = u.id
    WHERE oi.vendor_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$vendor['id']]);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Order Status - LocalKart</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <?php include 'partials/header.php'; ?>

<div class="container">
    <h2>Order Status</h2>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3>My Orders</h3>

        <?php if (empty($sales)): ?>
            <p>No orders found.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Product</th>
                        <th>Customer</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $sale): ?>
                        <tr>
                            <td>#<?= $sale['order_id']; ?></td>
                            <td><?= htmlspecialchars($sale['product_name']); ?></td>
                            <td><?= htmlspecialchars($sale['customer_name']); ?></td>
                            <td><?= $sale['quantity']; ?></td>
                            <td>₹<?= number_format($sale['price'], 2); ?></td>
                            <td>₹<?= number_format($sale['price'] * $sale['quantity'], 2); ?></td>
                            <td><?= ucfirst($sale['status']); ?></td>
                            <td><?= $sale['created_at']; ?></td>
                            <td>
                                <?php if ($sale['status'] === 'pending'): ?>
                                    <form method="POST" style="margin:0;"
                                          onsubmit="return confirm('Mark as dispatched?');">
                                        <?php csrfField(); ?>
                                        <input type="hidden" name="order_id" value="<?= $sale['order_id']; ?>">
                                        <button type="submit" name="dispatch_order" value="1" class="btn btn-success">
                                            Dispatch
                                        </button>
                                    </form>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

</body>
</html>

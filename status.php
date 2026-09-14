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

if (isset($_GET['action'], $_GET['order_id']) && $_GET['action'] === 'dispatch') {
    $orderId = (int)$_GET['order_id'];

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
                                    <a href="?action=dispatch&order_id=<?= $sale['order_id']; ?>"
                                        class="btn btn-success"
                                        onclick="return confirm('Mark as dispatched?');">
                                        Dispatch
                                    </a>
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

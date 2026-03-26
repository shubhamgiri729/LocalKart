<?php
require_once 'config.php';

if (!isLoggedIn() || getUserRole() !== 'customer') {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cartCount = array_sum($_SESSION['cart']);
}

$orders = [];
$error = '';

try {
    $stmt = $pdo->prepare("
        SELECT 
            o.id,
            o.status,
            o.created_at,
            COUNT(oi.id) AS item_count,
            SUM(oi.quantity * oi.price) AS order_total
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.customer_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "⚠️ Unable to fetch your orders at the moment.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Multi-Vendor Marketplace</title>

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
            max-width: 1200px;
            margin: auto;
            padding: 20px;
        }

        nav {
            background: #007BFF;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        nav a {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background 0.3s;
        }

        nav a:hover {
            background: #0056b3;
        }

        h2,
        h3 {
            color: #007BFF;
            margin-bottom: 15px;
        }

        .section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .cart-summary {
            background: #e9ecef;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        th {
            background: #f1f1f1;
        }

        tr:nth-child(even) {
            background: #f8f9fa;
        }

        .btn {
            background: #007BFF;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            transition: 0.3s;
            display: inline-block;
        }

        .btn:hover {
            background: #0056b3;
        }

        .btn-success {
            background: #007BFF;
        }

        .btn-success:hover {
            background: #0056b3;
        }

        .msg-error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
            text-align: center;
        }

        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                gap: 10px;
            }

            .section {
                padding: 10px;
            }
        }
    </style>
</head>

<body>

    <nav>
        <div><strong>👤 Customer Dashboard</strong></div>
        <div>
            <a href="helpdesk.php">Help Desk</a>
            <a href="index.php">Home</a> |
            <a href="products.php">Browse Products</a> |
            <a href="cart.php">Cart (<?php echo $cartCount; ?>)</a> |
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">

        <?php if (!empty($error)): ?>
            <div class="msg-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="section">
            <h3>🛒 Cart Summary</h3>
            <div class="cart-summary">
                <p>You have <strong><?php echo $cartCount; ?></strong> item(s) in your cart.</p>
                <a href="cart.php" class="btn btn-success">View Cart</a>
            </div>
        </div>

        <div class="section">
            <h3>📦 My Orders (<?php echo count($orders); ?>)</h3>

            <?php if (empty($orders)): ?>
                <p>No orders yet. <a href="products.php">Start shopping</a> 🛍️</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo $order['created_at']; ?></td>
                            <td><?php echo $order['item_count']; ?></td>
                            <td>₹<?php echo number_format($order['order_total'], 2); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($order['status'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

    </div>
</body>

</html>
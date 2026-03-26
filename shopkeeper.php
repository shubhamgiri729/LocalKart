<?php
require_once 'config.php';

if (!isLoggedIn() || getUserRole() !== 'shopkeeper') {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$message = '';
$messageType = 'success';


$stmt = $pdo->prepare("
    SELECT v.* 
    FROM vendors v 
    WHERE v.user_id = ?
");
$stmt->execute([$userId]);
$vendor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vendor) {
    die("Vendor profile not found. Please complete vendor registration.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_delivered'], $_POST['order_id'])) {
    $orderId = (int)$_POST['order_id'];

    try {
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'delivered'
            WHERE id = ?
            AND id IN (
                SELECT order_id FROM order_items WHERE vendor_id = ?
            )
        ");
        $stmt->execute([$orderId, $vendor['id']]);

        header("Location: shopkeeper.php?msg=Order marked as delivered successfully!");
        exit();
    } catch (PDOException $e) {
        $message = "Error updating order status.";
        $messageType = 'error';
    }
}

if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
    $productId = (int)$_GET['id'];

    // Remove product image
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ? AND vendor_id = ?");
    $stmt->execute([$productId, $vendor['id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product && !empty($product['image'])) {
        $imgPath = 'uploads/products/' . $product['image'];
        if (file_exists($imgPath)) {
            unlink($imgPath);
        }
    }

    // Delete product record
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND vendor_id = ?");
    $stmt->execute([$productId, $vendor['id']]);

    header("Location: shopkeeper.php?msg=Product deleted successfully!");
    exit();
}

$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.vendor_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$vendor['id']]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT 
        oi.*, 
        o.status, 
        o.created_at,
        p.name,
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopkeeper Dashboard</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, Arial, sans-serif;
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
        }

        nav a {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
        }

        nav a:hover {
            background: #0056b3;
            border-radius: 4px;
        }

        .section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        th {
            background: #f1f1f1;
        }

        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            color: white;
            border: none;
            cursor: pointer;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-danger {
            background: #dc3545;
        }

        .msg-success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .msg-error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
    </style>

    <script>
        function toggleDetails(id) {
            const el = document.getElementById('details-' + id);
            el.style.display = (el.style.display === 'none') ? 'block' : 'none';
        }
    </script>
</head>

<body>

    <nav>
        <strong><?= htmlspecialchars($vendor['store_name']) ?> Dashboard</strong>
        <div>
            <a href="index.php">Home</a>
            <a href="products.php">Browse</a>
            <a href="add_product.php">Add Product</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">

        <?php if (isset($_GET['msg'])): ?>
            <div class="msg-success"><?= htmlspecialchars($_GET['msg']) ?></div>
        <?php endif; ?>

        <div class="section">
            <h3>Store Information</h3>
            <p><strong>Name:</strong> <?= htmlspecialchars($vendor['store_name']) ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($vendor['address']) ?></p>
            <p><strong>Verified:</strong> <?= $vendor['verified'] ? 'Yes' : 'No' ?></p>
        </div>

        <div class="section">
            <h3>My Products (<?= count($products) ?>)</h3>

            <?php if (empty($products)): ?>
                <p>No products added yet.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td>
                                <img src="<?= !empty($p['image']) && file_exists('uploads/products/' . $p['image'])
                                                ? 'uploads/products/' . $p['image'] : 'assets/no-image.png' ?>" class="product-img">
                            </td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td><?= htmlspecialchars($p['category_name'] ?? 'Uncategorized') ?></td>
                            <td>₹<?= number_format($p['price'], 2) ?></td>
                            <td><?= $p['stock'] ?></td>
                            <td>
                                <a class="btn btn-danger"
                                    href="?action=delete&id=<?= $p['id'] ?>"
                                    onclick="return confirm('Delete this product?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <!-- 🧾 ORDERS -->
        <div class="section">
            <h3>My Orders</h3>

            <?php
            $orders = [];
            foreach ($sales as $s) {
                $orders[$s['order_id']][] = $s;
            }
            ?>

            <?php if (empty($orders)): ?>
                <p>No orders yet.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>

                    <?php foreach ($orders as $id => $items):
                        $total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
                        $status = $items[0]['status'];
                    ?>
                        <tr onclick="toggleDetails(<?= $id ?>)" style="cursor:pointer;">
                            <td>#<?= $id ?></td>
                            <td><?= htmlspecialchars($items[0]['customer_name']) ?></td>
                            <td>₹<?= number_format($total, 2) ?></td>
                            <td><?= htmlspecialchars($status) ?></td>
                            <td>
                                <?php if ($status === 'pending'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="order_id" value="<?= $id ?>">
                                        <button class="btn btn-success" name="mark_delivered">Deliver</button>
                                    </form>
                                <?php else: ?>
                                    Delivered
                                <?php endif; ?>
                            </td>
                        </tr>

                        <tr id="details-<?= $id ?>" style="display:none;">
                            <td colspan="5">
                                <strong>Order Items:</strong>
                                <ul>
                                    <?php foreach ($items as $it): ?>
                                        <li><?= htmlspecialchars($it['name']) ?> × <?= $it['quantity'] ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                        </tr>

                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

    </div>
</body>

</html>
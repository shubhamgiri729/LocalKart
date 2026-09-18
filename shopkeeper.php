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
    requireCsrf();

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'], $_POST['product_id'])) {
    requireCsrf();

    $productId = (int) $_POST['product_id'];

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

    // Delete product record — vendor_id in the WHERE clause is what stops a
    // shopkeeper from deleting a product that isn't theirs, even if they
    // guess or tamper with another store's product_id.
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
<title><?= htmlspecialchars($vendor['store_name']) ?> Dashboard — LocalKart</title>
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

  .main-content {
    flex: 1;
    padding: 40px 20px;
  }

  .container {
    max-width: 1200px;
    margin: auto;
  }

  .dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 30px;
  }

  .dashboard-header h2 {
    font-size: 30px;
    margin: 0;
  }

  .section {
    background: var(--white);
    padding: 28px;
    border-radius: var(--radius-card);
    border: 1px solid var(--line);
    margin-bottom: 24px;
    box-shadow: var(--shadow-soft);
  }

  .section h3 {
    font-size: 20px;
    margin-top: 0;
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--line);
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 12px;
    font-size: 14px;
  }

  th, td {
    padding: 12px 14px;
    text-align: left;
    border-bottom: 1px solid var(--line);
  }

  th {
    background: var(--paper-alt);
    font-weight: 600;
    color: var(--ink);
  }

  tr:hover td {
    background: rgba(241, 233, 218, 0.4);
  }

  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 20px;
    border-radius: var(--radius-pill);
    color: white;
    border: none;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    font-family: inherit;
    transition: background 0.15s, box-shadow 0.15s;
    background: var(--moss);
  }

  .btn:hover {
    background: var(--moss-dark);
    box-shadow: 0 6px 16px rgba(47, 82, 51, 0.28);
  }

  .btn-sm {
    padding: 6px 14px;
    font-size: 13px;
  }

  .btn-success {
    background: var(--moss);
  }

  .btn-danger {
    background: var(--brick);
  }

  .btn-danger:hover {
    background: #8A3024;
    box-shadow: 0 6px 16px rgba(166, 61, 47, 0.28);
  }

  .msg-success {
    background: #EAF3EC;
    color: var(--moss-dark);
    padding: 12px 16px;
    border-radius: 8px;
    border: 1px solid #C8DED0;
    margin-bottom: 24px;
    font-size: 14px;
  }

  .msg-error {
    background: #FCE8E6;
    color: var(--brick);
    padding: 12px 16px;
    border-radius: 8px;
    border: 1px solid #FAD2D0;
    margin-bottom: 24px;
    font-size: 14px;
  }

  .product-img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid var(--line);
  }

  .store-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
  }

  .store-info-item {
    background: var(--paper);
    padding: 16px;
    border-radius: 8px;
    border: 1px solid var(--line);
  }

  .store-info-item span {
    display: block;
    font-size: 12px;
    color: var(--ink-soft);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 4px;
  }

  .store-info-item strong {
    font-size: 15px;
    color: var(--ink);
  }

  .order-details-row {
    background: var(--paper);
  }

  .order-details-row ul {
    margin: 0;
    padding-left: 20px;
    color: var(--ink-soft);
    font-size: 13.5px;
  }
</style>

<script>
    function toggleDetails(id) {
        const el = document.getElementById('details-' + id);
        el.style.display = (el.style.display === 'none') ? 'table-row' : 'none';
    }
</script>
</head>
<body>

    <?php include 'partials/header.php'; ?>

    <div class="main-content">
        <div class="container">
            <div class="dashboard-header">
                <h2><?= htmlspecialchars($vendor['store_name']) ?> Dashboard</h2>
                <a href="add_product.php" class="btn">+ Add Product</a>
            </div>

            <?php if (isset($_GET['msg'])): ?>
                <div class="msg-success"><?= htmlspecialchars($_GET['msg']) ?></div>
            <?php endif; ?>

            <!-- Store Information Section -->
            <div class="section">
                <h3>Store Profile</h3>
                <div class="store-info-grid">
                    <div class="store-info-item">
                        <span>Store Name</span>
                        <strong><?= htmlspecialchars($vendor['store_name']) ?></strong>
                    </div>
                    <div class="store-info-item">
                        <span>Store Address</span>
                        <strong><?= htmlspecialchars($vendor['address']) ?></strong>
                    </div>
                    <div class="store-info-item">
                        <span>Verification Status</span>
                        <strong><?= $vendor['verified'] ? 'Verified Partner' : 'Pending Verification' ?></strong>
                    </div>
                </div>
            </div>

            <!-- Products Section -->
            <div class="section">
                <h3>My Products (<?= count($products) ?>)</h3>

                <?php if (empty($products)): ?>
                    <p style="color: var(--ink-soft); margin: 0;">No products added yet. Click "+ Add Product" to list your first item.</p>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $p): ?>
                                    <tr>
                                        <td>#<?= $p['id'] ?></td>
                                        <td>
                                            <img src="<?= !empty($p['image']) && file_exists('uploads/products/' . $p['image'])
                                                    ? 'uploads/products/' . $p['image'] : 'assets/no-image.png' ?>" class="product-img">
                                        </td>
                                        <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                        <td><?= htmlspecialchars($p['category_name'] ?? 'Uncategorized') ?></td>
                                        <td>₹<?= number_format($p['price'], 2) ?></td>
                                        <td><?= $p['stock'] ?></td>
                                        <td>
                                            <form method="POST" style="margin:0;"
                                                  onsubmit="return confirm('Delete this product?')">
                                                <?php csrfField(); ?>
                                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                                <button type="submit" name="delete_product" value="1" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Orders Section -->
            <div class="section">
                <h3>Customer Orders</h3>

                <?php
                $orders = [];
                foreach ($sales as $s) {
                    $orders[$s['order_id']][] = $s;
                }
                ?>

                <?php if (empty($orders)): ?>
                    <p style="color: var(--ink-soft); margin: 0;">No orders received yet.</p>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $id => $items):
                                    $total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
                                    $status = $items[0]['status'];
                                ?>
                                    <tr onclick="toggleDetails(<?= $id ?>)" style="cursor:pointer;" title="Click to view item details">
                                        <td><strong>#<?= $id ?></strong></td>
                                        <td><?= htmlspecialchars($items[0]['customer_name']) ?></td>
                                        <td>₹<?= number_format($total, 2) ?></td>
                                        <td>
                                            <span style="text-transform: capitalize; font-weight: 500; color: <?= $status === 'delivered' ? 'var(--moss)' : 'var(--marigold-dark)' ?>;">
                                                <?= htmlspecialchars($status) ?>
                                            </span>
                                        </td>
                                        <td onclick="event.stopPropagation()">
                                            <?php if ($status === 'pending'): ?>
                                                <form method="POST" style="margin: 0;">
                                                    <?php csrfField(); ?>
                                                    <input type="hidden" name="order_id" value="<?= $id ?>">
                                                    <button class="btn btn-sm btn-success" name="mark_delivered">Mark Delivered</button>
                                                </form>
                                            <?php else: ?>
                                                <span style="color: var(--ink-soft); font-size: 13px;">Fulfilled</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <tr id="details-<?= $id ?>" class="order-details-row" style="display:none;">
                                        <td colspan="5">
                                            <strong style="display:block; margin-bottom: 6px; color: var(--ink);">Order Items Breakdown:</strong>
                                            <ul>
                                                <?php foreach ($items as $it): ?>
                                                    <li><?= htmlspecialchars($it['name']) ?> &times; <?= $it['quantity'] ?> (₹<?= number_format($it['price'], 2) ?> each)</li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <?php include 'partials/footer.php'; ?>

</body>
</html>
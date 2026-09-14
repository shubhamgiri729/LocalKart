<?php
require_once 'config.php';

if (
    isLoggedIn() &&
    getUserRole() === 'customer' &&
    isset($_GET['action'], $_GET['id']) &&
    $_GET['action'] === 'add'
) {
    $productId = (int) $_GET['id'];

    if ($productId > 0) {
        $_SESSION['cart'] ??= [];
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + 1;
    }

    header("Location: products.php?msg=" . urlencode("Product added to cart!"));
    exit();
}

$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$whereClause = $categoryFilter ? "WHERE p.category_id = ?" : "";
$params = $categoryFilter ? [$categoryFilter] : [];


$categories = $pdo
    ->query("SELECT * FROM categories ORDER BY name")
    ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Products - Multi-Vendor eCommerce</title>

    <style>
        :root {
            --primary: #007BFF;
            --primary-hover: #0056b3;
            --success: #28a745;
            --bg: #f4f7f6;
            --text-main: #2d3436;
            --text-muted: #636e72;
            --shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text-main);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 30px 20px;
        }

        /* Filter Section Styling */
        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .filter-box {
            background: #fff;
            padding: 8px 15px;
            border-radius: 50px;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
        }

        .filter-box label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-right: 10px;
        }

        .filter-box select {
            border: none;
            outline: none;
            font-size: 0.9rem;
            color: var(--primary);
            font-weight: 700;
            cursor: pointer;
            background: transparent;
        }

        /* Success Message */
        .msg-success {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 5px solid var(--success);
            font-weight: 500;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .product {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .product:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow);
        }

        .product img {
            width: 100%;
            height: 220px;
            object-fit: contain;
            border-radius: 8px;
            margin-bottom: 15px;
            background: #fafafa;
        }

        .product h3 {
            font-size: 1.15rem;
            margin-bottom: 8px;
            color: var(--text-main);
        }

        .product-info {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .product-info strong {
            color: #2d3436;
        }

        .price-tag {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-main);
            margin: 15px 0;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            font-weight: 600;
            transition: background 0.2s;
            margin-top: auto;
        }

        .btn:hover {
            background: var(--primary-hover);
        }

        .no-results {
            text-align: center;
            grid-column: 1 / -1;
            padding: 50px;
            color: var(--text-muted);
        }
    </style>
</head>

<body>
    <?php include 'partials/header.php'; ?>

    <div class="container">
        <div class="header-flex">
            <h1>Explore Products</h1>

            <div class="filter-box">
                <label>Category</label>
                <select onchange="location='products.php?category='+this.value">
                    <option value="0">All Items</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php if (!empty($_GET['msg'])): ?>
            <div class="msg-success">✓ <?= htmlspecialchars($_GET['msg']) ?></div>
        <?php endif; ?>

        <div class="product-grid">
            <?php
            $stmt = $pdo->prepare("
                SELECT p.*, v.store_name, c.name AS category_name
                FROM products p
                JOIN vendors v ON p.vendor_id = v.id
                LEFT JOIN categories c ON p.category_id = c.id
                $whereClause
                ORDER BY p.created_at DESC
            ");
            $stmt->execute($params);

            if ($stmt->rowCount() === 0): ?>
                <div class="no-results">
                    <h3>No products found in this category.</h3>
                    <p>Try switching to a different filter or check back later!</p>
                </div>
            <?php endif;

            while ($p = $stmt->fetch(PDO::FETCH_ASSOC)):
                $imageName = $p['image'] ?? '';
                $serverPath = __DIR__ . '/uploads/products/' . $imageName;
                
                if (!empty($imageName) && file_exists($serverPath)) {
                    $image = 'uploads/products/' . $imageName;
                } else {
                    $image = 'uploads/products/football.png'; // Fallback to an image that exists
                }
            ?>
                <div class="product">
                    <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                    <h3><?= htmlspecialchars($p['name']) ?></h3>

                    <p class="product-info"><strong>Store:</strong> <?= htmlspecialchars($p['store_name']) ?></p>
                    <p class="product-info"><strong>Category:</strong> <?= htmlspecialchars($p['category_name'] ?? 'Uncategorized') ?></p>

                    <div class="price-tag">₹<?= number_format($p['price'], 2) ?></div>

                    <?php if (isLoggedIn() && getUserRole() === 'customer'): ?>
                        <a href="?action=add&id=<?= $p['id'] ?>" class="btn">Add to Cart</a>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>

</html>
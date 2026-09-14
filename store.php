<?php

require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid store ID.');
}

$storeId = (int) $_GET['id'];

$stmt = $pdo->prepare(
    "SELECT store_name FROM vendors WHERE id = ?"
);
$stmt->execute([$storeId]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    die('Store not found.');
}

$stmt = $pdo->prepare(
    "SELECT id, name, price, stock, image
     FROM products
     WHERE vendor_id = ?"
);
$stmt->execute([$storeId]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($store['store_name']) ?> - Store</title>

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f8f9fa;
            margin: 0;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        h1 {
            color: #007BFF;
            text-align: center;
            margin-bottom: 25px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
        }

        .product {
            background: white;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #eee;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: box-shadow 0.15s, transform 0.15s;
        }

        .product:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .product-img {
            width: 100%;
            height: 220px;
            object-fit: contain;
            border-radius: 8px;
            border: 1px solid #eee;
            background: #fafafa;
            margin-bottom: 10px;
        }

        .product h3 {
            color: #007BFF;
            margin-bottom: 8px;
        }

        .btn {
            display: inline-block;
            padding: 10px 16px;
            background: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
            font-weight: 500;
            transition: background 0.15s, box-shadow 0.15s;
        }

        .btn:hover {
            background: #0056b3;
            box-shadow: 0 2px 6px rgba(0, 123, 255, 0.25);
        }
    </style>
</head>

<body>

    <?php include 'partials/header.php'; ?>

    <div class="container">
        <h1><?= htmlspecialchars($store['store_name']) ?></h1>

        <div class="product-grid">
            <?php if ($products): ?>
                <?php foreach ($products as $product): ?>

                    <div class="product">
                        <?php
                        $imagePath = "uploads/products/" . htmlspecialchars($product['image']);
                        if (!empty($product['image']) && file_exists($imagePath)) {
                            echo "<img src='$imagePath' class='product-img'>";
                        } else {
                            echo "<img src='uploads/products/default.jpg' class='product-img'>";
                        }
                        ?>

                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p><strong>Price:</strong> ₹<?= number_format($product['price'], 2) ?></p>
                        <p><strong>Stock:</strong> <?= $product['stock'] ?></p>

                        <?php if (isLoggedIn() && getUserRole() === 'customer'): ?>
                            <a href="cart.php?action=add&id=<?= $product['id'] ?>" class="btn">
                                Add to Cart
                            </a>
                        <?php endif; ?>
                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <p>No products available in this store.</p>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>
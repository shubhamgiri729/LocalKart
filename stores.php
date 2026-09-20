<?php
require_once 'config.php';
$stores = [];
$ratingsByVendor = [];
try {
    $stmt = $pdo->query("SELECT id, store_name FROM vendors ORDER BY store_name ASC");
    $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $ratingsByVendor = $pdo->query(
        "SELECT vendor_id, ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS total
         FROM vendor_ratings
         GROUP BY vendor_id"
    )->fetchAll(PDO::FETCH_UNIQUE);
} catch (PDOException $e) {
    $error = "Failed to load stores.";
}
?>
<!DOCTYPE html>  
<html lang="en">
<head>  
    <meta charset="UTF-8">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">  
    <title>Vendor Marketplace - LocalKart</title>
    <style>  
        body {  
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;  
            background: #FBF8F1;  
            margin: 0;  
            color: #333;  
            line-height: 1.5;  
        }

        .container {  
            max-width: 1200px;  
            margin: 40px auto;  
            padding: 0 20px;  
        }

        h1 {  
            color: #2F5233;  
            margin-bottom: 25px;  
            font-size: 2rem;
        }

        .store-grid {  
            display: grid;  
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));  
            gap: 24px;  
        }

        .store {  
            background: white;  
            padding: 28px 20px;  
            border-radius: 12px;  
            border: 1px solid rgba(0, 0, 0, 0.04);  
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);  
            text-align: center;  
            transition: transform 0.2s ease, box-shadow 0.2s ease;  
        }

        .store:hover {  
            transform: translateY(-4px);  
            box-shadow: 0 8px 24px rgba(47, 82, 51, 0.08);  
        }

        .store h3 {  
            color: #2F5233;  
            margin-bottom: 16px;  
            font-size: 1.25rem;
        }

        .store-rating {
            color: #565C4E;
            font-size: 0.85rem;
            margin: -8px 0 16px;
        }

        .store-rating .star {
            color: #E7A62F;
        }

        .btn {  
            display: inline-block;  
            padding: 10px 20px;  
            background: #2F5233;  
            color: white;  
            text-decoration: none;  
            border-radius: 8px;  
            font-weight: 500;  
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;  
        }

        .btn:hover {  
            background: #20391F;  
            transform: translateY(-1px);  
            box-shadow: 0 4px 12px rgba(47, 82, 51, 0.25);  
        }  

        @media (max-width: 480px) {
            .container { margin: 24px auto; padding: 0 16px; }
            h1 { font-size: 1.6rem; }
        }
    </style>  
</head>
<body>  
    <?php include 'partials/header.php'; ?>

    <div class="container">  
        <h1>Vendor Stores</h1>

        <div class="store-grid">  
            <?php if (!empty($stores)): ?>  
                <?php foreach ($stores as $store): ?>  
                    <?php $rating = $ratingsByVendor[$store['id']] ?? null; ?>
                    <div class="store">  
                        <h3><?= htmlspecialchars($store['store_name']) ?></h3>
                        <div class="store-rating">
                            <?php if ($rating): ?>
                                <span class="star">★</span> <?= $rating['avg_rating'] ?>
                                (<?= $rating['total'] ?> rating<?= $rating['total'] == 1 ? '' : 's' ?>)
                            <?php else: ?>
                                No ratings yet
                            <?php endif; ?>
                        </div>
                        <a href="store.php?id=<?= $store['id'] ?>" class="btn">Visit Store</a>  
                    </div>  
                <?php endforeach; ?>  
            <?php else: ?>  
                <p style="color:red;"><?= $error ?? 'No stores available.' ?></p>  
            <?php endif; ?>  
        </div>  
    </div>

    <?php include 'partials/footer.php'; ?>
</body>
</html>
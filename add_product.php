<?php
require_once "config.php";

if (!isLoggedIn() || getUserRole() !== 'shopkeeper') {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id FROM vendors WHERE user_id = ?");
$stmt->execute([$userId]);
$vendor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vendor) {
    die("Vendor profile not found. Please contact admin.");
}

$vendorId = $vendor['id'];

$action = 'add';
$productId = null;
$product = [];
$error = $success = '';

if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit') {
    $productId = (int)$_GET['id'];

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND vendor_id = ?");
    $stmt->execute([$productId, $vendorId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("Location: shopkeeper.php?msg=Product not found.");
        exit();
    }

    $action = 'edit';
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $name         = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price       = (float)$_POST['price'];
    $stock       = (int)$_POST['stock'];
    $categoryId  = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

    $imageName = $product['image'] ?? null;

    if (!empty($_FILES['image']['name'])) {

        $uploadDir = "uploads/products/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $file = $_FILES['image'];
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
        $allowedMime = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png'  => ['png'],
            'image/gif'  => ['gif'],
        ];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = "Image upload failed. Please try again.";
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $error = "Image size must be less than 2MB.";
        } elseif (!in_array(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), $allowedExt, true)) {
            $error = "Only JPG, JPEG, PNG, or GIF allowed.";
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $realMime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $imageInfo = @getimagesize($file['tmp_name']);

            if (!isset($allowedMime[$realMime]) || $imageInfo === false) {
                $error = "That file doesn't look like a valid image.";
            } else {
                $ext = $allowedMime[$realMime][0];
                $fileName = bin2hex(random_bytes(16)) . '.' . $ext;
                $target = $uploadDir . $fileName;

                if (!move_uploaded_file($file['tmp_name'], $target)) {
                    $error = "Image upload failed.";
                } else {
                    if ($action === 'edit' && !empty($product['image']) && file_exists($uploadDir . $product['image'])) {
                        unlink($uploadDir . $product['image']);
                    }
                    $imageName = $fileName;
                }
            }
        }
    }

    if (empty($name) || $price <= 0 || $stock < 0) {
        $error = "Invalid input values.";
    }

    if (!$error) {
        if ($action === 'add') {
            $stmt = $pdo->prepare("
                INSERT INTO products 
                (vendor_id, category_id, name, description, image, price, stock)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$vendorId, $categoryId, $name, $description, $imageName, $price, $stock]);

            $success = "Product added successfully!";
        } else {
            $stmt = $pdo->prepare("
                UPDATE products 
                SET category_id=?, name=?, description=?, image=?, price=?, stock=?
                WHERE id=? AND vendor_id=?
            ");
            $stmt->execute([$categoryId, $name, $description, $imageName, $price, $stock, $productId, $vendorId]);

            $success = "Product updated successfully!";
        }

        header("Location: shopkeeper.php?msg=" . urlencode($success));
        exit();
    }
}

$productData = $product ?: [
    'name' => '',
    'description' => '',
    'price' => '',
    'stock' => '',
    'category_id' => '',
    'image' => ''
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo ucfirst($action); ?> Product — LocalKart</title>
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

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .container {
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
            background: var(--white);
            padding: 40px;
            border-radius: var(--radius-card);
            border: 1px solid var(--line);
            box-shadow: var(--shadow-soft);
        }

        h2 {
            font-family: 'Fraunces', serif;
            color: var(--ink);
            font-weight: 600;
            letter-spacing: -0.01em;
            text-align: center;
            margin-top: 0;
            margin-bottom: 24px;
            font-size: 28px;
        }

        label {
            font-weight: 600;
            margin-top: 16px;
            display: block;
            color: var(--ink-soft);
            font-size: 14px;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 12px 14px;
            margin-top: 6px;
            border: 1px solid var(--line);
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            background: var(--white);
            color: var(--ink);
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--moss);
            box-shadow: 0 0 0 3px rgba(47, 82, 51, 0.15);
        }

        button {
            margin-top: 24px;
            width: 100%;
            padding: 12px 20px;
            background: var(--moss);
            color: var(--white);
            border: none;
            border-radius: var(--radius-pill);
            font-family: inherit;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.15s, box-shadow 0.15s;
        }

        button:hover {
            background: var(--moss-dark);
            box-shadow: 0 6px 16px rgba(47,82,51,.28);
        }

        .msg-error {
            background: #FCE8E6;
            color: var(--brick);
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid #FAD2D0;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .msg-success {
            background: #EAF3EC;
            color: var(--moss-dark);
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid #C8DED0;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .preview-img-container {
            margin-top: 10px;
        }

        img.current-product-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--line);
        }

        @media (max-width: 480px) {
            .main-content { padding: 24px 16px; }
            .container { padding: 28px 22px; }
        }
    </style>
</head>

<body>

    <?php include 'partials/header.php'; ?>

    <div class="main-content">
        <div class="container">
            <h2><?php echo ucfirst($action); ?> Product</h2>

            <?php if ($error): ?><div class="msg-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <?php csrfField(); ?>

                <label>Product Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($productData['name']); ?>" required placeholder="Enter product name">

                <label>Description</label>
                <textarea name="description" required placeholder="Describe your product"><?php echo htmlspecialchars($productData['description']); ?></textarea>

                <label>Product Image</label>
                <input type="file" name="image">
                <?php if (!empty($productData['image'])): ?>
                    <div class="preview-img-container">
                        <img src="uploads/products/<?php echo htmlspecialchars($productData['image']); ?>" class="current-product-img" alt="Product Image">
                    </div>
                <?php endif; ?>

                <label>Price (₹)</label>
                <input type="number" step="0.01" min="0.01" name="price" value="<?php echo htmlspecialchars($productData['price']); ?>" required placeholder="0.00">

                <label>Stock</label>
                <input type="number" min="0" name="stock" value="<?php echo htmlspecialchars($productData['stock']); ?>" required placeholder="0">

                <label>Category</label>
                <select name="category_id">
                    <option value="">No Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"
                            <?php if ($productData['category_id'] == $cat['id']) echo "selected"; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit"><?php echo ucfirst($action); ?> Product</button>
            </form>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>

</body>

</html>
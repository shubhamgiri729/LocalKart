<?php
require_once "config.php";

if (!isLoggedIn() || getUserRole() !== 'shopkeeper') {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("SELECT id FROM vendors WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$vendor = $result->fetch_assoc();
$stmt->close();

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

    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND vendor_id = ?");
    $stmt->bind_param("ii", $productId, $vendorId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        header("Location: shopkeeper.php?msg=Product not found.");
        exit();
    }

    $action = 'edit';
}

$categories = [];
$result = $conn->query("SELECT * FROM categories ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name        = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price       = (float)$_POST['price'];
    $stock       = (int)$_POST['stock'];
    $categoryId  = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

    $imageName = $product['image'] ?? null;

    if (!empty($_FILES['image']['name'])) {

        $uploadDir = "uploads/products/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES['image']['name']);
        $target   = $uploadDir . $fileName;
        $ext      = strtolower(pathinfo($target, PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $allowed)) {
            $error = "Only JPG, JPEG, PNG, or GIF allowed.";
        } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            $error = "Image size must be less than 2MB.";
        } elseif (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $error = "Image upload failed.";
        } else {
            if ($action === 'edit' && !empty($product['image']) && file_exists($uploadDir . $product['image'])) {
                unlink($uploadDir . $product['image']);
            }
            $imageName = $fileName;
        }
    }

    if (empty($name) || $price <= 0 || $stock < 0) {
        $error = "Invalid input values.";
    }

    if (!$error) {
        if ($action === 'add') {
            $stmt = $conn->prepare("
                INSERT INTO products 
                (vendor_id, category_id, name, description, image, price, stock)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "iisssdi",
                $vendorId,
                $categoryId,
                $name,
                $description,
                $imageName,
                $price,
                $stock
            );
            $stmt->execute();
            $stmt->close();

            $success = "Product added successfully!";
        } else {
            $stmt = $conn->prepare("
                UPDATE products 
                SET category_id=?, name=?, description=?, image=?, price=?, stock=?
                WHERE id=? AND vendor_id=?
            ");
            $stmt->bind_param(
                "isssdiii",
                $categoryId,
                $name,
                $description,
                $imageName,
                $price,
                $stock,
                $productId,
                $vendorId
            );
            $stmt->execute();
            $stmt->close();

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
    <title><?php echo ucfirst($action); ?> Product</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
        }

        nav {
            background: #007BFF;
            padding: 15px;
            color: #fff;
            display: flex;
            justify-content: space-between;
        }

        nav a {
            color: #fff;
            text-decoration: none;
            margin: 0 8px;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
        }

        label {
            font-weight: bold;
            margin-top: 15px;
            display: block;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
        }

        button {
            margin-top: 20px;
            width: 100%;
            padding: 12px;
            background: #007BFF;
            color: #fff;
            border: none;
        }

        .msg-error {
            background: #f8d7da;
            padding: 10px;
            margin-bottom: 15px;
        }

        .msg-success {
            background: #d4edda;
            padding: 10px;
            margin-bottom: 15px;
        }

        img {
            width: 150px;
            margin-top: 10px;
            border-radius: 6px;
        }
    </style>
</head>

<body>

    <nav>
        <strong>Vendor Dashboard</strong>
        <div>
            <a href="shopkeeper.php">Dashboard</a>
            <a href="products.php">Products</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <h2><?php echo ucfirst($action); ?> Product</h2>

        <?php if ($error): ?><div class="msg-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <label>Product Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($productData['name']); ?>" required>

            <label>Description</label>
            <textarea name="description" required><?php echo htmlspecialchars($productData['description']); ?></textarea>

            <label>Product Image</label>
            <input type="file" name="image">
            <?php if (!empty($productData['image'])): ?>
                <img src="uploads/products/<?php echo htmlspecialchars($productData['image']); ?>">
            <?php endif; ?>

            <label>Price (₹)</label>
            <input type="number" step="0.01" min="0.01" name="price" value="<?php echo htmlspecialchars($productData['price']); ?>" required>

            <label>Stock</label>
            <input type="number" min="0" name="stock" value="<?php echo htmlspecialchars($productData['stock']); ?>" required>

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

</body>

</html>

<?php $conn->close(); ?>
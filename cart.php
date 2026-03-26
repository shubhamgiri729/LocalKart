<?php
require_once "config.php";

if (!isLoggedIn() || getUserRole() !== 'customer') {
    header("Location: login.php?msg=Login required for cart.");
    exit();
}

$userId = $_SESSION['user_id'];

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}


if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_GET['action'], $_GET['id'])) {
    $productId = (int)$_GET['id'];

    if ($_GET['action'] === 'add') {
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + 1;
        header("Location: cart.php?msg=Product added to cart!");
        exit();
    }

    if ($_GET['action'] === 'remove') {
        unset($_SESSION['cart'][$productId]);
        header("Location: cart.php?msg=Product removed from cart!");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $id => $qty) {
        $qty = max(0, (int)$qty);
        if ($qty > 0) {
            $_SESSION['cart'][$id] = $qty;
        } else {
            unset($_SESSION['cart'][$id]);
        }
    }
    header("Location: cart.php?msg=Cart updated!");
    exit();
}

$cartItems = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    $ids = implode(',', array_keys($_SESSION['cart']));
    $sql = "
        SELECT p.*, v.store_name
        FROM products p
        JOIN vendors v ON p.vendor_id = v.id
        WHERE p.id IN ($ids)
    ";

    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $qty = $_SESSION['cart'][$row['id']];
        if ($qty > $row['stock']) $qty = $row['stock'];

        $subtotal = $qty * $row['price'];
        $total += $subtotal;

        $cartItems[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'store_name' => $row['store_name'],
            'price' => $row['price'],
            'stock' => $row['stock'],
            'quantity' => $qty,
            'subtotal' => $subtotal,
            'image' => $row['image']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        nav {
            background: #007BFF;
            color: #fff;
            padding: 12px;
            display: flex;
            justify-content: space-between;
        }

        nav a {
            color: #fff;
            margin: 0 6px;
            text-decoration: none;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
        }

        th {
            background: #f1f1f1;
        }

        .btn {
            padding: 6px 12px;
            background: #007BFF;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
        }

        .btn-danger {
            background: #DC3545;
        }

        .btn-success {
            background: #28A745;
        }

        .msg {
            background: #d4edda;
            padding: 10px;
            margin: 10px 0;
        }

        .cart-img {
            width: 70px;
            height: 70px;
            object-fit: contain;
            border: 1px solid #ccc;
        }

        .total {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            margin-top: 15px;
        }
    </style>
</head>

<body>

    <nav>
        <strong>🛍️ LocalKart</strong>
        <div>
            <a href="index.php">Home</a>
            <a href="products.php">Products</a>
            <a href="customer.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <h1>🛒 Shopping Cart</h1>

        <?php if (isset($_GET['msg'])): ?>
            <div class="msg"><?php echo htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <p>Your cart is empty.</p>
            <a href="products.php" class="btn btn-success">Browse Products</a>
        <?php else: ?>

            <form method="POST">
                <input type="hidden" name="update_cart" value="1">

                <table>
                    <tr>
                        <th>Image</th>
                        <th>Product</th>
                        <th>Store</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>

                    <?php foreach ($cartItems as $item): ?>
                        <tr>
                            <td>
                                <?php
                                $img = "uploads/products/" . $item['image'];
                                if (!empty($item['image']) && file_exists($img)) {
                                    echo "<img src='$img' class='cart-img'>";
                                } else {
                                    echo "<img src='uploads/products/default.jpg' class='cart-img'>";
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['store_name']); ?></td>
                            <td>₹<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <input type="number" name="quantity[<?php echo $item['id']; ?>]"
                                    value="<?php echo $item['quantity']; ?>" min="0">
                            </td>
                            <td>₹<?php echo number_format($item['subtotal'], 2); ?></td>
                            <td>
                                <a class="btn btn-danger"
                                    href="cart.php?action=remove&id=<?php echo $item['id']; ?>"
                                    onclick="return confirm('Remove item?')">Remove</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                </table>

                <div style="text-align:right;margin-top:15px;">
                    <button class="btn btn-success" type="submit">Update Cart</button>
                    <a href="checkout.php" class="btn btn-success">Checkout</a>
                </div>

                <div class="total">Total: ₹<?php echo number_format($total, 2); ?></div>
            </form>

        <?php endif; ?>
    </div>
</body>

</html>

<?php $conn->close(); ?>
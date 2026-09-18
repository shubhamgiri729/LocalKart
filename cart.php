<?php
require_once "config.php";

if (!isLoggedIn() || getUserRole() !== 'customer') {
    header("Location: login.php?msg=Login required for cart.");
    exit();
}

$userId = $_SESSION['user_id'];

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
    requireCsrf();

    foreach ($_POST['quantity'] as $id => $qty) {
        $id = (int)$id;
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
    $cartIds = array_map('intval', array_keys($_SESSION['cart']));
    $placeholders = implode(',', array_fill(0, count($cartIds), '?'));

    $sql = "
        SELECT p.*, v.store_name
        FROM products p
        JOIN vendors v ON p.vendor_id = v.id
        WHERE p.id IN ($placeholders)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($cartIds);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
<title>Shopping Cart — LocalKart</title>
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
  }

  h1, h2, h3, h4 {
    font-family: 'Fraunces', serif;
    color: var(--ink);
    font-weight: 600;
    letter-spacing: -0.01em;
  }

  a { color: inherit; text-decoration: none; }

  .wrap {
    max-width: 1180px;
    margin: 0 auto;
    padding: 0 28px;
  }

  .page-head {
    padding: 40px 0 24px;
  }
  .page-head h1 { font-size: 32px; }

  .container {
    max-width: 1180px;
    margin: 0 auto;
    padding: 0 28px 70px;
  }

  .section-card {
    background: var(--white);
    border: 1px solid var(--line);
    border-radius: var(--radius-card);
    padding: 24px;
    box-shadow: var(--shadow-soft);
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 4px;
    font-size: 14px;
  }

  th, td {
    border-bottom: 1px solid var(--line);
    padding: 16px 12px;
    text-align: left;
    vertical-align: middle;
  }

  th {
    background: var(--paper-alt);
    font-weight: 600;
    color: var(--ink);
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
  }

  tr:last-child td {
    border-bottom: none;
  }

  tr:hover td {
    background: rgba(241, 233, 218, 0.4);
  }

  .cart-img {
    width: 64px;
    height: 64px;
    object-fit: cover;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: var(--paper-alt);
    display: block;
  }

  input[type="number"] {
    width: 70px;
    padding: 8px 10px;
    border: 1px solid var(--line);
    border-radius: 6px;
    font: inherit;
    font-size: 13.5px;
    background: var(--white);
    color: var(--ink);
  }

  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: none;
    cursor: pointer;
    font-family: inherit;
    font-weight: 600;
    font-size: 14px;
    padding: 11px 22px;
    border-radius: var(--radius-pill);
    transition: background .15s, color .15s, transform .1s, box-shadow .15s;
  }
  .btn-primary {
    background: var(--moss);
    color: #fff;
  }
  .btn-primary:hover {
    background: var(--moss-dark);
    box-shadow: 0 6px 16px rgba(47,82,51,.28);
  }
  .btn-danger {
    background: #FCE8E6;
    color: var(--brick);
    padding: 8px 14px;
    font-size: 13px;
  }
  .btn-danger:hover {
    background: var(--brick);
    color: #fff;
  }
  .btn-sm {
    padding: 8px 16px;
    font-size: 13px;
  }

  .msg {
    background: #E6F4EA;
    color: #137333;
    padding: 14px 18px;
    border-radius: 8px;
    margin-bottom: 24px;
    border: 1px solid #CEEAD6;
    font-size: 14px;
  }

  .cart-actions-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid var(--line);
    flex-wrap: wrap;
    gap: 16px;
  }

  .total-display {
    font-family: 'Fraunces', serif;
    font-size: 24px;
    font-weight: 600;
    color: var(--ink);
  }

  .empty-state {
    text-align: center;
    padding: 40px 0;
    color: var(--ink-soft);
  }
  .empty-state p { margin-bottom: 16px; font-size: 16px; }

  @media (max-width: 768px) {
    .cart-actions-row { flex-direction: column; align-items: stretch; }
    .total-display { text-align: center; }
  }
</style>
</head>

<body>

<?php include 'partials/header.php'; ?>

<div class="wrap page-head">
  <h1>Shopping Cart</h1>
</div>

<div class="container">
  <?php if (isset($_GET['msg'])): ?>
      <div class="msg"><?php echo htmlspecialchars($_GET['msg']); ?></div>
  <?php endif; ?>

  <div class="section-card">
    <?php if (empty($cartItems)): ?>
        <div class="empty-state">
          <p>Your cart is currently empty.</p>
          <a href="products.php" class="btn btn-primary">Browse Products 🛍️</a>
        </div>
    <?php else: ?>

        <form method="POST">
            <?php csrfField(); ?>
            <input type="hidden" name="update_cart" value="1">

            <div style="overflow-x: auto;">
              <table>
                  <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product</th>
                        <th>Store</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($cartItems as $item): ?>
                        <tr>
                            <td>
                                <?php
                                $img = "uploads/products/" . htmlspecialchars($item['image']);
                                if (!empty($item['image']) && file_exists($img)) {
                                    echo "<img src='$img' class='cart-img'>";
                                } else {
                                    echo "<img src='uploads/products/default.jpg' class='cart-img'>";
                                }
                                ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                            <td><span style="color: var(--ink-soft); font-size: 13px;"><?php echo htmlspecialchars($item['store_name']); ?></span></td>
                            <td>₹<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <input type="number" name="quantity[<?php echo $item['id']; ?>]"
                                    value="<?php echo $item['quantity']; ?>" min="0" max="<?php echo $item['stock']; ?>">
                            </td>
                            <td><strong>₹<?php echo number_format($item['subtotal'], 2); ?></strong></td>
                            <td>
                                <a class="btn btn-danger"
                                    href="cart.php?action=remove&id=<?php echo $item['id']; ?>"
                                    onclick="return confirm('Remove item from cart?')">Remove</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                  </tbody>
              </table>
            </div>

            <div class="cart-actions-row">
                <div style="display: flex; gap: 12px;">
                    <button class="btn btn-sm btn-primary" style="background: var(--ink-soft);" type="submit">Update Cart</button>
                    <a href="products.php" class="btn btn-sm btn-primary" style="background: transparent; color: var(--ink); border: 1px solid var(--line);">Continue Shopping</a>
                </div>
                
                <div style="display: flex; align-items: center; gap: 24px;">
                    <div class="total-display">Total: ₹<?php echo number_format($total, 2); ?></div>
                    <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
                </div>
            </div>
        </form>

    <?php endif; ?>
  </div>
</div>

<?php include 'partials/footer.php'; ?>

</body>
</html>

<?php
require_once 'config.php';

if (!isLoggedIn() || getUserRole() !== 'customer') {
    header("Location: login.php?error=Access denied");
    exit();
}

if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    header("Location: cart.php?error=Cart is empty");
    exit();
}

$cartItems = [];
$total = 0;
$error = '';
try {
    foreach ($_SESSION['cart'] as $productId => $quantity) {
        if ($quantity > 0) {
            $stmt = $pdo->prepare("
                SELECT p.id, p.name, p.price, p.stock, p.vendor_id, v.store_name 
                FROM products p 
                JOIN vendors v ON p.vendor_id = v.id 
                WHERE p.id = ?
            ");
            $stmt->execute([$productId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($item && $item['stock'] >= $quantity) {
                $item['quantity'] = $quantity;
                $item['subtotal'] = $item['price'] * $quantity;
                $total += $item['subtotal'];
                $cartItems[] = $item;
            } else {
                unset($_SESSION['cart'][$productId]);
                $error = 'Some items were removed due to low stock or unavailability.';
            }
        }
    }
    
    if (empty($cartItems)) {
        unset($_SESSION['cart']);
        header("Location: cart.php?error=No valid items in cart");
        exit();
    }
} catch (PDOException $e) {
    $error = "Error fetching cart details. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout — LocalKart</title>
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
    --shadow-lift: 0 10px 28px rgba(35,41,29,0.12);
  }

  * { box-sizing: border-box; }
  body { margin: 0; background: var(--paper); color: var(--ink); font-family: 'Inter', sans-serif; line-height: 1.55; }
  h1, h2, h3, h4 { font-family: 'Fraunces', serif; color: var(--ink); font-weight: 600; letter-spacing: -0.01em; }
  a { color: inherit; text-decoration: none; }

  .wrap { max-width: 1180px; margin: 0 auto; padding: 0 28px; }

  .page-head { padding: 40px 0 24px; }
  .page-head h1 { font-size: 32px; }
  .page-head p { color: var(--ink-soft); margin-top: 6px; font-size: 14.5px; }

  .checkout-container {
    max-width: 1180px;
    margin: 0 auto;
    padding: 0 28px 70px;
  }

  .section {
    background: var(--white);
    border: 1px solid var(--line);
    border-radius: var(--radius-card);
    padding: 32px;
    box-shadow: var(--shadow-soft);
    margin-bottom: 24px;
  }

  .section h2 { font-size: 24px; margin-bottom: 20px; }
  .section h3 { font-size: 18px; margin-bottom: 16px; margin-top: 24px; }

  .form-group { margin-bottom: 18px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
  .form-group label { font-weight: 600; display: block; margin-bottom: 6px; color: var(--ink); font-size: 13.5px; }
  .form-group input, .form-group select, .form-group textarea {
    width: 100%;
    padding: 11px 14px;
    border: 1px solid var(--line);
    border-radius: 8px;
    font: inherit;
    font-size: 14px;
    background: var(--white);
    color: var(--ink);
    transition: border-color .15s, box-shadow .15s;
  }
  .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    outline: none;
    border-color: var(--moss);
    box-shadow: 0 0 0 3px rgba(47, 82, 51, 0.15);
  }

  .table-wrapper { overflow-x: auto; margin-bottom: 16px; }
  table { border-collapse: collapse; width: 100%; font-size: 14px; }
  th, td { border-bottom: 1px solid var(--line); padding: 14px 12px; text-align: left; }
  th { background: var(--paper-alt); font-weight: 600; color: var(--ink); font-size: 13px; text-transform: uppercase; letter-spacing: 0.03em; }
  tr:last-child td { border-bottom: none; }
  tr:hover td { background: rgba(241, 233, 218, 0.4); }

  .total {
    font-family: 'Fraunces', serif;
    font-size: 20px;
    font-weight: 600;
    color: var(--ink);
    text-align: right;
    margin-top: 16px;
    padding: 16px;
    background: var(--paper-alt);
    border-radius: 8px;
    border: 1px solid var(--line);
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
    padding: 12px 24px;
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
  .btn-secondary {
    background: var(--paper-alt);
    color: var(--ink);
    border: 1px solid var(--line);
  }
  .btn-secondary:hover {
    background: var(--line);
  }

  .msg-error {
    background: #FCE8E6;
    color: var(--brick);
    padding: 14px 18px;
    border-radius: 8px;
    margin-bottom: 24px;
    border: 1px solid #FAD2D0;
    font-size: 14px;
  }

  @media (max-width: 768px) {
    .form-group { grid-template-columns: 1fr; gap: 12px; }
    .section { padding: 20px; }
  }
</style>
</head>
<body>
    <?php include 'partials/header.php'; ?>

    <div class="wrap page-head">
      <h1>Secure Checkout</h1>
      <p>Review your cart items and complete your shipping information</p>
    </div>

    <div class="checkout-container">
        <?php if ($error): ?>
            <div class="msg-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="section">
            <h3>🛒 Order Summary</h3>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Vendor Store</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($item['store_name']); ?></td>
                                <td>₹<?php echo number_format($item['price'], 2); ?></td> 
                                <td><?php echo $item['quantity']; ?></td>
                                <td><strong>₹<?php echo number_format($item['subtotal'], 2); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="total">Order Total: ₹<?php echo number_format($total, 2); ?></div>

            <form method="POST" action="process_order.php" style="margin-top: 30px;">
                <h3>📦 Billing & Shipping Information</h3>
                <div class="form-group">
                    <div>
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required placeholder="John Doe">
                    </div>
                    <div>
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required placeholder="john@example.com" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <div>
                        <label for="address">Street Address *</label>
                        <textarea id="address" name="address" rows="3" required placeholder="123 Main St, Apt 4B"></textarea>
                    </div>
                    <div>
                        <label for="city">City *</label>
                        <input type="text" id="city" name="city" required placeholder="Mumbai">
                    </div>
                </div>
                <div class="form-group">
                    <div>
                        <label for="zip_code">ZIP/Postal Code *</label>
                        <input type="text" id="zip_code" name="zip_code" required placeholder="400001">
                    </div>
                    <div>
                        <label for="country">Country *</label>
                        <input type="text" id="country" name="country" required value="India" placeholder="India">
                    </div>
                </div>
                <div class="form-group">
                    <div>
                        <label for="payment_method">Payment Method *</label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="">Choose a method...</option>
                            <option value="card">Credit/Debit Card (UPI, Visa, Mastercard)</option>
                            <option value="paypal">Online Wallet</option>
                            <option value="cod">Cash on Delivery (COD)</option>
                        </select>
                    </div>
                    <div></div> 
                </div>
                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 30px;">
                    <a href="cart.php" class="btn btn-secondary">Back to Cart</a>
                    <button type="submit" class="btn btn-primary">Place Order & Pay Now</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>
</body>
</html>
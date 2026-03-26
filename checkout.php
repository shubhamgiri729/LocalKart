<?php
require_once 'config.php';

// Start the session if not already started (assuming config.php handles this)
// if (session_status() == PHP_SESSION_NONE) {
//     session_start();
// }

if (!isLoggedIn() || getUserRole() !== 'customer') {
    header("Location: login.php?error=Access denied");
    exit();
}

if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    header("Location: cart.php?error=Cart is empty");
    exit();
}

// Fetch cart details with product/vendor info for display
$cartItems = [];
$total = 0;
$error = '';
try {
    // Assuming $pdo is initialized in config.php
    foreach ($_SESSION['cart'] as $productId => $quantity) {
        if ($quantity > 0) {
            // Use prepared statements to prevent SQL injection
            $stmt = $pdo->prepare("
                SELECT p.id, p.name, p.price, p.stock, p.vendor_id, v.store_name 
                FROM products p 
                JOIN vendors v ON p.vendor_id = v.id 
                WHERE p.id = ?
            ");
            $stmt->execute([$productId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Validate product existence and stock
            if ($item && $item['stock'] >= $quantity) {
                $item['quantity'] = $quantity;
                $item['subtotal'] = $item['price'] * $quantity;
                $total += $item['subtotal'];
                $cartItems[] = $item;
            } else {
                // Remove out-of-stock or invalid item from cart
                unset($_SESSION['cart'][$productId]);
                $error = 'Some items were removed due to low stock or unavailability.';
            }
        }
    }
    
    // Check if the cart is now empty after validation
    if (empty($cartItems)) {
        unset($_SESSION['cart']);
        header("Location: cart.php?error=No valid items in cart");
        exit();
    }
} catch (PDOException $e) {
    // Log the error (optional) and show a user-friendly message
    // error_log("Database Error: " . $e->getMessage());
    $error = "Error fetching cart details. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Multi-Vendor eCommerce</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; line-height: 1.6; color: #333; background: #f8f9fa; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        nav { background: #007BFF; color: white; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 100; }
        nav a { color: white; text-decoration: none; padding: 5px 10px; border-radius: 4px; transition: background 0.3s; }
        nav a:hover { background: #0056b3; }
        h2, h3 { color: #007BFF; margin-bottom: 15px; }
        .section { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group label { font-weight: bold; display: block; margin-bottom: 5px; color: #555; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #007BFF; box-shadow: 0 0 0 2px rgba(0,123,255,0.25); }
        .btn { background: #007BFF; color: white; padding: 10px 20px; text-decoration: none; border: none; border-radius: 4px; cursor: pointer; transition: background 0.3s; display: inline-block; margin: 5px; font-size: 16px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28A745; }
        .btn-success:hover { background: #218838; }
        .table-wrapper { overflow-x: auto; margin-bottom: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #f8f9fa; font-weight: bold; }
        tr:nth-child(even) { background: #f8f9fa; }
        tr:hover { background: #e9ecef; }
        .total { font-size: 1.2em; font-weight: bold; color: #007BFF; text-align: right; margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 4px; }
        .msg-error, .msg-success { padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center; }
        .msg-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .msg-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        @media (max-width: 768px) {
            .form-group { grid-template-columns: 1fr; gap: 10px; }
            nav { flex-direction: column; gap: 10px; padding: 10px; }
            .section { padding: 15px; }
            .table-wrapper { overflow-x: auto; }
            input, select, textarea { font-size: 18px; } /* Mobile-friendly input size */
            .total { text-align: center; }
        }
    </style>
</head>
<body>
    <nav>
        <div><strong>Checkout - Multi-Vendor eCommerce</strong></div>
        <div>
            <a href="helpdesk.php">Help Desk</a>
            <a href="customer.php">My Dashboard</a> |
            <a href="cart.php">Cart (<?php echo count($cartItems); ?> items)</a> |
            <a href="products.php">Continue Shopping</a> |
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if ($error): ?>
            <div class="msg-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="section">
            <h2>Secure Checkout</h2>
            
            <h3>Order Summary</h3>
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
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['store_name']); ?></td>
                                <td>₹<?php echo number_format($item['price'], 2); ?></td> 
                                <td><?php echo $item['quantity']; ?></td>
                                <td>₹<?php echo number_format($item['subtotal'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="total">Order Total: ₹<?php echo number_format($total, 2); ?></div>

            <form method="POST" action="process_order.php">
                <h3>Billing & Shipping Information</h3>
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
                            <option value="card">Credit/Debit Card (e.g., UPI, Visa, Mastercard)</option>
                            <option value="paypal">Online Wallet</option>
                            <option value="cod">Cash on Delivery (COD)</option>
                        </select>
                    </div>
                    <div></div> </div>
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">Place Order & Pay Now</button>
                    <a href="cart.php" class="btn">Back to Cart</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn() || getUserRole() !== 'customer') {
    header("Location: login.php?error=" . urlencode("Access denied."));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    header("Location: cart.php?error=" . urlencode("Invalid request or empty cart."));
    exit();
}

requireCsrf();

$full_name       = trim($_POST['full_name'] ?? '');
$email           = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$address         = trim($_POST['address'] ?? '');
$city            = trim($_POST['city'] ?? '');
$zip_code        = trim($_POST['zip_code'] ?? '');
$country         = trim($_POST['country'] ?? '');
$payment_method  = trim($_POST['payment_method'] ?? '');

if (
    !$full_name || !$email || !$address ||
    !$city || !$zip_code || !$country || !$payment_method
) {
    header("Location: checkout.php?error=" . urlencode("Please fill in all required fields."));
    exit();
}

$razorpay_order_id = null;
$razorpay_payment_id = null;

if ($payment_method === 'razorpay') {
    $razorpay_order_id   = trim($_POST['razorpay_order_id'] ?? '');
    $razorpay_payment_id = trim($_POST['razorpay_payment_id'] ?? '');
    $razorpay_signature  = trim($_POST['razorpay_signature'] ?? '');
    $pending             = $_SESSION['razorpay_pending'] ?? null;

    $signatureValid = $razorpay_order_id && $razorpay_payment_id && $razorpay_signature
        && razorpayVerifySignature($razorpay_order_id, $razorpay_payment_id, $razorpay_signature);

    // The order_id Razorpay signed must also match the one *this server*
    // created in razorpay_create_order.php for this cart — otherwise a
    // genuinely valid signature from a completely different (e.g. much
    // cheaper) payment could be replayed against this order.
    $orderMatchesPending = $pending && $pending['order_id'] === $razorpay_order_id;

    if (!$signatureValid || !$orderMatchesPending) {
        header("Location: checkout.php?error=" . urlencode("Payment verification failed. If money was deducted, it will be refunded — please contact support."));
        exit();
    }

    unset($_SESSION['razorpay_pending']);
} elseif ($payment_method !== 'cod') {
    header("Location: checkout.php?error=" . urlencode("Please choose a valid payment method."));
    exit();
}
// 'cod' needs no payment verification — the customer pays on delivery.

$customer_id = $_SESSION['user_id'];
$total = 0;
$order_items = [];
$cart_warning = '';

try {
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        if ($quantity <= 0) continue;

        $stmt = $pdo->prepare(
            "SELECT id, price, stock, vendor_id FROM products WHERE id = ?"
        );
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product && $product['stock'] >= $quantity) {
            $total += $product['price'] * $quantity;
            $order_items[] = [
                'product_id' => $product['id'],
                'vendor_id'  => $product['vendor_id'],
                'quantity'   => $quantity,
                'price'      => $product['price']
            ];
        } else {
            $cart_warning = "Some items were removed due to insufficient stock.";
            unset($_SESSION['cart'][$product_id]);
        }
    }

    if (empty($order_items)) {
        unset($_SESSION['cart']);
        header("Location: checkout.php?error=" . urlencode("No valid items to purchase."));
        exit();
    }

    // For Razorpay, make sure what was actually charged still matches what
    // the cart adds up to now. These can drift apart if, say, another
    // customer bought the last unit of something between the payment
    // starting and finishing — rare, but this is the check that catches it
    // rather than silently creating an order for the wrong amount.
    if ($payment_method === 'razorpay') {
        $chargedPaise = $pending['amount'] ?? null;
        $expectedPaise = (int) round($total * 100);

        if ($chargedPaise === null || $chargedPaise !== $expectedPaise) {
            header("Location: checkout.php?error=" . urlencode("Your cart changed during payment. Please contact support with your payment ID for a refund: " . $razorpay_payment_id));
            exit();
        }
    }

    if ($cart_warning) {
        $_SESSION['cart_message'] = $cart_warning;
    }
} catch (PDOException $e) {
    header("Location: checkout.php?error=" . urlencode("Database error."));
    exit();
}

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare(
        "INSERT INTO orders (
            customer_id, total, payment_method, razorpay_order_id, razorpay_payment_id, status,
            shipping_name, shipping_email, shipping_address, shipping_city, shipping_zip, shipping_country,
            created_at
        )
         VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, NOW())"
    );
    $stmt->execute([
        $customer_id, $total, $payment_method, $razorpay_order_id, $razorpay_payment_id,
        $full_name, $email, $address, $city, $zip_code, $country
    ]);
    $order_id = $pdo->lastInsertId();

    $item_stmt = $pdo->prepare(
        "INSERT INTO order_items (order_id, product_id, vendor_id, quantity, price, status)
         VALUES (?, ?, ?, ?, ?, 'pending')"
    );

    foreach ($order_items as $item) {
        $item_stmt->execute([
            $order_id,
            $item['product_id'],
            $item['vendor_id'],
            $item['quantity'],
            $item['price']
        ]);
    }

    $stock_stmt = $pdo->prepare(
        "UPDATE products SET stock = stock - ?
         WHERE id = ? AND stock >= ?"
    );

    foreach ($order_items as $item) {
        $stock_stmt->execute([
            $item['quantity'],
            $item['product_id'],
            $item['quantity']
        ]);

        if ($stock_stmt->rowCount() === 0) {
            throw new Exception("Stock update failed.");
        }
    }

    $pdo->commit();
    unset($_SESSION['cart']);

    header(
        "Location: customer.php?msg=" .
            urlencode("Order placed successfully! Order ID: $order_id | Total: ₹" . number_format($total, 2))
    );
    exit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Order Error: " . $e->getMessage());
    header("Location: checkout.php?error=" . urlencode("Order processing failed."));
    exit();
}

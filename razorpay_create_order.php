<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || getUserRole() !== 'customer') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit();
}

requireCsrf();

if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Your cart is empty.']);
    exit();
}

// The amount charged is always computed here, from the cart and the real
// product prices in the database — never from anything the browser sends.
// A client could otherwise tamper with a posted amount and pay ₹1 for a
// ₹5,000 order.
$total = 0;

try {
    foreach ($_SESSION['cart'] as $productId => $quantity) {
        if ($quantity <= 0) continue;

        $stmt = $pdo->prepare("SELECT price, stock FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product && $product['stock'] >= $quantity) {
            $total += $product['price'] * $quantity;
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not calculate order total.']);
    exit();
}

if ($total <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'No valid items in your cart.']);
    exit();
}

$amountInPaise = (int) round($total * 100);
$receipt = 'lk_' . $_SESSION['user_id'] . '_' . time();

$razorpayOrder = razorpayCreateOrder($amountInPaise, $receipt);

if (!$razorpayOrder || empty($razorpayOrder['id'])) {
    http_response_code(502);
    echo json_encode(['error' => 'Could not start payment. Please try again.']);
    exit();
}

// Stash what we just charged for, so process_order.php can cross-check the
// completed payment's amount against this instead of trusting the browser.
$_SESSION['razorpay_pending'] = [
    'order_id' => $razorpayOrder['id'],
    'amount'   => $amountInPaise,
];

echo json_encode([
    'razorpay_order_id' => $razorpayOrder['id'],
    'amount'            => $amountInPaise,
    'currency'          => 'INR',
    'key_id'            => RAZORPAY_KEY_ID,
]);

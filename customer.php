<?php
require_once 'config.php';

if (!isLoggedIn() || getUserRole() !== 'customer') {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cartCount = array_sum($_SESSION['cart']);
}

$orders = [];
$error = '';

try {
    $stmt = $pdo->prepare("
        SELECT 
            o.id,
            o.status,
            o.created_at,
            COUNT(oi.id) AS item_count,
            SUM(oi.quantity * oi.price) AS order_total
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.customer_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "⚠️ Unable to fetch your orders at the moment.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Dashboard — LocalKart</title>
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

  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

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
  .page-head p { color: var(--ink-soft); margin-top: 6px; font-size: 14.5px; }

  .dashboard-grid {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 28px;
    max-width: 1180px;
    margin: 0 auto;
    padding: 0 28px 70px;
    align-items: start;
  }

  /* Grid items default to min-width: auto, so wide content (like the
     orders table) can force a track wider than the viewport instead of
     scrolling inside its own overflow-x wrapper. Force them to shrink
     to their track so the wrapper's scroll actually kicks in. */
  .dashboard-grid > div {
    min-width: 0;
  }

  .section {
    background: var(--white);
    border: 1px solid var(--line);
    border-radius: var(--radius-card);
    padding: 24px;
    box-shadow: var(--shadow-soft);
    margin-bottom: 24px;
  }

  .section h3 {
    font-size: 18px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .cart-summary {
    background: var(--paper-alt);
    padding: 18px;
    border-radius: 8px;
    text-align: center;
    border: 1px solid var(--line);
  }
  .cart-summary p {
    font-size: 14px;
    color: var(--ink-soft);
    margin-bottom: 12px;
  }

  table {
    border-collapse: collapse;
    width: 100%;
    margin-top: 6px;
    font-size: 14px;
  }

  th, td {
    border-bottom: 1px solid var(--line);
    padding: 14px 12px;
    text-align: left;
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

  .status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: var(--radius-pill);
    font-size: 11.5px;
    font-weight: 600;
    background: var(--paper-alt);
    color: var(--ink-soft);
    text-transform: capitalize;
  }
  .status-badge.completed, .status-badge.delivered { background: #E6F4EA; color: #137333; }
  .status-badge.pending { background: #FEF7E0; color: #B06000; }
  .status-badge.cancelled { background: #FCE8E6; color: #C5221F; }

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
  .btn-sm {
    padding: 8px 16px;
    font-size: 13px;
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

  .empty-state {
    color: var(--ink-soft);
    font-size: 14.5px;
    padding: 20px 0;
    text-align: center;
  }
  .empty-state a {
    color: var(--moss);
    font-weight: 600;
    text-decoration: underline;
  }

  @media (max-width: 900px) {
    .dashboard-grid {
      grid-template-columns: 1fr;
    }
  }
</style>
</head>

<body>

<?php include 'partials/header.php'; ?>

<div class="wrap page-head">
  <h1>Customer Dashboard</h1>
  <p>Manage your account, view your active cart, and track orders</p>
</div>

<div class="dashboard-grid">
  <!-- Sidebar / Quick Overview -->
  <div>
    <div class="section">
      <h3>🛒 Cart Summary</h3>
      <div class="cart-summary">
        <p>You have <strong><?php echo $cartCount; ?></strong> item(s) waiting in your cart.</p>
        <a href="cart.php" class="btn btn-primary btn-sm" style="width: 100%;">View Cart</a>
      </div>
    </div>
  </div>

  <!-- Main Content Area -->
  <div>
    <?php if (!empty($error)): ?>
        <div class="msg-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="section">
      <h3>📦 My Orders (<?php echo count($orders); ?>)</h3>

      <?php if (empty($orders)): ?>
          <div class="empty-state">
            <p>You haven't placed any orders yet. <a href="products.php">Start shopping local 🛍️</a></p>
          </div>
      <?php else: ?>
          <div style="overflow-x: auto;">
            <table>
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Date</th>
                  <th>Items</th>
                  <th>Total</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $order): ?>
                    <?php 
                        $statusClass = strtolower(htmlspecialchars($order['status']));
                    ?>
                    <tr>
                        <td><strong>#<?php echo $order['id']; ?></strong></td>
                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                        <td><?php echo $order['item_count']; ?> item(s)</td>
                        <td><strong>₹<?php echo number_format($order['order_total'], 2); ?></strong></td>
                        <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo ucfirst(htmlspecialchars($order['status'])); ?></span></td>
                    </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'partials/footer.php'; ?>

</body>
</html>
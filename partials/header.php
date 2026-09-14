<?php
/**
 * Shared site header/nav. Same markup on every page — include this instead of
 * writing a page-specific <nav> block. Requires config.php to already be
 * required (for isLoggedIn(), getUserRole(), and the session).
 */

$role = getUserRole();

$dashboardLink = match ($role) {
    'admin'      => 'admin.php',
    'shopkeeper' => 'shopkeeper.php',
    'customer'   => 'customer.php',
    default      => null,
};

$cartCount = 0;
if ($role === 'customer' && isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cartCount = array_sum($_SESSION['cart']);
}

$currentPage = basename($_SERVER['SCRIPT_NAME']);

function navActive(string $page, string $current): string
{
    return $page === $current ? 'active' : '';
}
?>
<link rel="stylesheet" href="assets/css/header.css">
<header class="site-header">
    <nav>
        <a class="brand" href="index.php">🛍️ LocalKart</a>
        <div class="nav-links">
            <a href="index.php" class="<?= navActive('index.php', $currentPage) ?>">Home</a>
            <a href="products.php" class="<?= navActive('products.php', $currentPage) ?>">Products</a>

            <?php if ($dashboardLink): ?>
                <a href="<?= $dashboardLink ?>" class="<?= navActive($dashboardLink, $currentPage) ?>">Dashboard</a>
            <?php endif; ?>

            <?php if ($role === 'customer'): ?>
                <a href="cart.php" class="<?= navActive('cart.php', $currentPage) ?>">
                    🛒 Cart<?= $cartCount > 0 ? " ($cartCount)" : '' ?>
                </a>
            <?php endif; ?>

            <a href="helpdesk.php" class="<?= navActive('helpdesk.php', $currentPage) ?>">Help Desk</a>

            <?php if (isLoggedIn()): ?>
                <span class="nav-user">Hi, <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

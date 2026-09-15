<?php

$role = getUserRole();

$dashboardLink = match ($role) {
    'admin'      => 'admin.php',
    'shopkeeper' => 'shopkeeper.php',
    'customer'   => 'customer.php',
    default      => null,
};

$helpdeskLink = match ($role) {
    'admin'      => 'admin_helpdesk.php',
    'shopkeeper' => 'shopkeeper_helpdesk.php',
    'customer'   => 'helpdesk.php',
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
        <a class="brand" href="index.php"><span class="brand-icon">🧺</span> LocalKart</a>
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

            <a href="stores.php" class="<?= navActive('stores.php', $currentPage) ?>">Stores</a>

            <?php if ($helpdeskLink): ?>
                <a href="<?= $helpdeskLink ?>" class="<?= navActive($helpdeskLink, $currentPage) ?>">Help Desk</a>
            <?php else: ?>
                <a href="contact.php" class="<?= navActive('contact.php', $currentPage) ?>">Contact Us</a>
            <?php endif; ?>

            <?php if (isLoggedIn()): ?>
                <span class="nav-user">Hi, <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
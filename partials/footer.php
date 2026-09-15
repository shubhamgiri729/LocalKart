<?php
$footerRole = getUserRole();
?>
<link rel="stylesheet" href="assets/css/footer.css">
<footer class="site-footer">
    <div class="footer-top">
        <div class="footer-brand">
            <a class="footer-logo" href="index.php">🧺 LocalKart</a>
            <p>A marketplace for the shops already on your street — grocers, bakers,
                tailors and more, all in one basket.</p>
        </div>

        <div class="footer-col">
            <h6>Shop</h6>
            <a href="products.php">All products</a>
            <a href="stores.php">All stores</a>
            <a href="cart.php">Your cart</a>
        </div>

        <div class="footer-col">
            <h6>Sell</h6>
            <?php if ($footerRole === 'shopkeeper'): ?>
                <a href="shopkeeper.php">Vendor dashboard</a>
                <a href="add_product.php">Add a product</a>
            <?php else: ?>
                <a href="register.php">Register your store</a>
                <a href="login.php">Vendor login</a>
            <?php endif; ?>
        </div>

        <div class="footer-col">
            <h6>Support</h6>
            <a href="helpdesk.php">Help desk</a>
            <a href="status.php">Track an order</a>
            <?php if (!isLoggedIn()): ?>
                <a href="login.php">Log in</a>
            <?php endif; ?>
            <a href="contact.php">Contact us</a>
        </div>
    </div>

    <div class="footer-bottom">
        <span>&copy; <?= date('Y') ?> LocalKart. A local-vendor marketplace.</span>
        <span>Made for neighbourhoods, not warehouses.</span>
    </div>
</footer>

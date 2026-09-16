<?php
require_once 'config.php';

$stores = [];
$products = [];

try {
    $stmt = $pdo->query("SELECT id, store_name FROM vendors ORDER BY store_name ASC LIMIT 4");
    $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Failed to load stores.";
}

try {

    $stmt = $pdo->query("SELECT id, name, price, image FROM products ORDER BY created_at DESC LIMIT 4");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Failed to load products.";
}

$stats = ['store_count' => 0, 'product_count' => 0, 'avg_rating' => null];
try {
    $stmt = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM vendors)            AS store_count,
            (SELECT COUNT(*) FROM products)            AS product_count,
            (SELECT ROUND(AVG(rating), 1) FROM vendor_ratings) AS avg_rating
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Hero still renders fine with the zeroed-out defaults above.
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LocalKart - Multi-Vendor Marketplace</title>

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #FBF8F1;
            margin: 0;
            color: #333;
            line-height: 1.5;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        h1 {
            color: #2F5233;
            margin-bottom: 20px;
        }

        .intro {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 40px;
            min-height: 450px;
            width: 160vh;
        }

        .tag-line {
            flex: 1;
            max-width: 550px;
            margin-bottom: 0;
            height: auto;
            width: auto;
        }

        .tag-line h1 {
            font-size: 2.8rem;
            margin-bottom: 10px;
            font-family: 'Fraunces', serif;
            text-align: left;
            width: 100%;
        }

        .tag-line span {
            color: #20391F;
            font-size: 3.2rem;
            display: block;
            width: 200px
        }

        .tag-line p {
            font-size: 1.3rem;
            color: #565C4E;
            text-align: left;
            margin: 0;
        }

        .hero-stats {
            display: flex;
            gap: 30px;
            margin-top: 20px;
            font-size: 1.1rem;
        }

        .hero-stats strong {
            font-size: 1.5rem;
            color: #2F5233;
        }

        .hero-stats span {
            display: block;
            font-size: 0.9rem;
            color: #565C4E;
        }

        .tag-board {
            position: relative;
            flex: 1;
            height: 380px;
            width: 80%;
            background-color: transparent;
            padding: 0;
            font-family: system-ui, -apple-system, sans-serif;
            overflow: visible;
        }

        .tag {
            position: absolute;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #FFFFFF;
            padding: 12px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            font-size: 15px;
            font-weight: 500;
            color: #1A1A1A;
            border: 1px solid rgba(0, 0, 0, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            white-space: nowrap;
        }

        .tag:hover {
            transform: scale(1.03) translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            z-index: 10;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .tag1 {
            top: 10px;
            left: 20%;
            transform: rotate(3deg);
        }

        .tag2 {
            top: 80px;
            left: 0%;
            transform: rotate(-4deg);
        }

        .tag3 {
            top: 60px;
            right: 10%;
            transform: rotate(2deg);
        }

        .tag4 {
            top: 190px;
            left: 15%;
            transform: rotate(-2deg);
        }

        .tag5 {
            top: 170px;
            right: 5%;
            transform: rotate(4deg);
        }

        .tag6 {
            bottom: 20px;
            left: 5%;
            transform: rotate(-3deg);
        }

        .tag7 {
            bottom: 10px;
            right: 25%;
            transform: rotate(1deg);
        }

        .trust {
            padding: 20px 0;
            text-align: center;
        }

        .wrap span{
            display: inline-block;
            color: #2F5233;
            /* background: #2F5233; */
            margin: 0 15px;
            font-size: 1.1rem;
            font-weight: 500;
            height: 1.5rem;
            width: auto;
            border: 1px solid #2F5233;
            border-radius: 5px;
            padding: 5px 10px;
        }

        .wrap span:hover{
            color: #FFFF;
            background: #2F5233;
            cursor: pointer;
            transition: background 0.15s, box-shadow 0.15s;
        }

        .section-header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            margin-bottom: 20px;
            margin-top: 80px;
        }

        .section-header h1 {
            margin: 0;
        }

        .show-more a {
            color: #2F5233;
            text-decoration: none;
            font-weight: 500;
            font-size: 1.125rem;
        }

        .show-more a:hover {
            text-decoration: underline;
        }

        .store-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .store {
            background: white;
            padding: 24px 20px;
            border-radius: 8px;
            border: 1px solid #eee;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            text-align: center;
            transition: box-shadow 0.15s, transform 0.15s;
        }

        .store:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .store h3 {
            color: #2F5233;
            margin-bottom: 12px;
        }


        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .product {
            background: white;
            padding: 24px 20px;
            border-radius: 8px;
            border: 1px solid #eee;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            text-align: center;
            transition: box-shadow 0.15s, transform 0.15s;
        }

        .product:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .product h3 {
            color: #2F5233;
            margin-bottom: 12px;
        }


        .btn {
            display: inline-block;
            padding: 9px 18px;
            background: #FFFF;
            color: #2F5233;
            border: 1px solid #2F5233;
            text-decoration: none;
            border-radius: 3px;
            font-weight: 500;
            transition: background 0.15s, box-shadow 0.15s;
        }

        .btn:hover {
            background: #2F5233;
            color: #FFFF;
            box-shadow: 0 2px 6px rgba(0, 123, 255, 0.25);
        }
    </style>
</head>

<body>
    <?php include 'partials/header.php'; ?>

    <div class="container">

        <section class="intro">
            <section class="tag-line">
                <h1><span>Your neighbourhood,</span> now open online.</h1>
                <p>Order from the grocers, bakers and shopkeepers around you — one basket, one checkout, delivered by people who know your street.</p>
                <div class="hero-stats">
                    <p><strong><?= $stats['store_count'] ?></strong><span>local stores</span></p>
                    <p><strong><?= $stats['product_count'] ?></strong><span>products listed</span></p>
                    <p><strong><?= $stats['avg_rating'] ? $stats['avg_rating'] . '★' : '—' ?></strong><span>average store rating</span></p>
                </div>
            </section>
            <div class="tag-board">
                <div class="tag tag1"><span class="dot" style="background:#2F5233"></span>Grocery &amp; produce</div>
                <div class="tag tag2"><span class="dot" style="background:#E7A62F"></span>Bakery</div>
                <div class="tag tag3"><span class="dot" style="background:#A63D2F"></span>Electronics</div>
                <div class="tag tag4"><span class="dot" style="background:#2F5233"></span>Fashion &amp; textiles</div>
                <div class="tag tag5"><span class="dot" style="background:#E7A62F"></span>Books &amp; stationery</div>
                <div class="tag tag6"><span class="dot" style="background:#A63D2F"></span>Home &amp; living</div>
                <div class="tag tag7"><span class="dot" style="background:#2F5233"></span>Florists</div>
            </div>
        </section>

        <div class="trust">
            <div class="wrap">
                <span>✔ Verified local vendors</span>
                <span>💳 Secure checkout</span>
                <span>📦 Same-day delivery in your area</span>
                <span>💬 Real replies from real shop owners</span>
            </div>
        </div>

        <div class="section-header">
            <h1>Stores near you</h1>
            <nav class="show-more">
                <a href="stores.php">see all stores ..</a>
            </nav>
        </div>
        <div class="store-grid">
            <?php if (!empty($stores)): ?>
                <?php foreach ($stores as $store): ?>
                    <div class="store">
                        <h3><?= htmlspecialchars($store['store_name']) ?></h3>
                        <a href="store.php?id=<?= $store['id'] ?>" class="btn">Visit Store</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:red;"><?= $error ?? 'No stores available.' ?></p>
            <?php endif; ?>
        </div>

        <div class="section-header" id="best-products">
            <h1>Best Products</h1>
            <nav class="show-more">
                <a href="products.php">see all products ..</a>
            </nav>
        </div>

        <div class="product-grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="product">
                        <?php if (!empty($product['image'])): ?>
                            <img src="uploads/products/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img" style="width: 100%; height: auto; border-radius: 8px; margin-bottom: 12px;">
                        <?php endif; ?>

                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p>Price: $<?= number_format($product['price'], 2) ?></p>

                        <!-- Check if user is logged in -->
                        <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                            <a href="product.php?id=<?= $product['id'] ?>" class="btn">Add to Cart</a>
                        <?php else: ?>
                            <a href="login.php" class="btn">Login to Add to Cart</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:red;"><?= $error ?? 'No products available.' ?></p>
            <?php endif; ?>
        </div>
    </div>



    <div id="chat-widget" class="collapsed">
        <div id="chat-header">
            <span>💬 Chat with Assistant</span>
            <span id="chat-toggle-icon">▲</span>
        </div>
        <div id="chat-body" style="display: none;">
            <div class="chat-message chat-assistant">Hi there! How can I help you navigate LocalKart today?</div>
        </div>
        <div id="chat-footer" style="display: none;">
            <input type="text" id="chat-input" placeholder="Type a message..." />
            <button id="chat-send">Send</button>
        </div>
    </div>

    <style>
        #chat-widget {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 280px;
            background: white;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            z-index: 9999;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        #chat-widget.collapsed {
            width: auto;
            border-radius: 30px;
            box-shadow: 0 6px 20px rgba(47, 82, 51, 0.2);
        }

        #chat-header {
            background: #2F5233;
            color: white;
            padding: 14px 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 600;
            font-size: 14px;
            user-select: none;
            transition: background 0.2s;
        }

        #chat-header:hover {
            background: #20391F;
        }

        #chat-toggle-icon {
            font-size: 10px;
            transition: transform 0.3s ease;
            margin-left: 10px;
        }

        #chat-widget.collapsed #chat-toggle-icon {
            transform: rotate(180deg);
        }

        #chat-body {
            height: 180px;
            padding: 15px;
            overflow-y: auto;
            background: #FAF8F5;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .chat-message {
            margin: 0;
            padding: 10px 14px;
            border-radius: 10px;
            max-width: 85%;
            font-size: 13.5px;
            line-height: 1.4;
            animation: fadeInMsg 0.2s ease-in-out;
        }

        @keyframes fadeInMsg {
            from {
                opacity: 0;
                transform: translateY(4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .chat-user {
            background: #2F5233;
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 2px;
        }

        .chat-assistant {
            background: #FFFFFF;
            color: #333;
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-bottom-left-radius: 2px;
        }

        #chat-footer {
            display: flex;
            border-top: 1px solid #eee;
            background: white;
            padding: 8px;
            gap: 8px;
        }

        #chat-footer input {
            flex: 1;
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
            font-size: 13.5px;
            transition: border-color 0.2s;
        }

        #chat-footer input:focus {
            border-color: #2F5233;
        }

        #chat-footer button {
            background: #2F5233;
            color: white;
            border: none;
            padding: 0 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        #chat-footer button:hover {
            background: #20391F;
        }
    </style>

    <script>
        const chatWidget = document.getElementById('chat-widget');
        const chatHeader = document.getElementById('chat-header');
        const chatBody = document.getElementById('chat-body');
        const chatInput = document.getElementById('chat-input');
        const chatSend = document.getElementById('chat-send');
        let open = false; // Starts closed/collapsed by default

        chatHeader.onclick = () => {
            open = !open;
            chatBody.style.display = open ? 'flex' : 'none';
            document.getElementById('chat-footer').style.display = open ? 'flex' : 'none';
            chatWidget.classList.toggle('collapsed', !open);
            if (open) {
                chatInput.focus();
                chatBody.scrollTop = chatBody.scrollHeight;
            }
        };

        function sendMessage() {
            const msg = chatInput.value.trim();
            if (!msg) return;

            const userDiv = document.createElement('div');
            userDiv.className = 'chat-message chat-user';
            userDiv.textContent = msg;
            chatBody.appendChild(userDiv);

            chatInput.value = '';
            chatBody.scrollTop = chatBody.scrollHeight;

            fetch('chat_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'message=' + encodeURIComponent(msg)
                })
                .then(res => res.json())
                .then(data => {
                    const botDiv = document.createElement('div');
                    botDiv.className = 'chat-message chat-assistant';
                    botDiv.textContent = data.reply || 'No response';
                    chatBody.appendChild(botDiv);
                    chatBody.scrollTop = chatBody.scrollHeight;
                });
        }

        chatSend.onclick = sendMessage;
        chatInput.onkeydown = e => {
            if (e.key === 'Enter') sendMessage();
        };
    </script>
    <?php include 'partials/footer.php'; ?>
</body>

</html>
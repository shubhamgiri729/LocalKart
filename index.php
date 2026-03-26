<?php
require_once 'config.php';

$stores = [];

try {
    $stmt = $pdo->query("SELECT id, store_name FROM vendors ORDER BY store_name ASC");
    $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Failed to load stores.";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multi-Vendor Marketplace</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            margin: 0;
        }

        nav {
            background: #007BFF;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            color: white;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 10px;
        }

        nav a:hover {
            text-decoration: underline;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        h1 {
            color: #007BFF;
        }

        .store-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .store {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .store h3 {
            color: #007BFF;
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>

<body>
    <nav>
        <strong>🛍️ LocalKart</strong>
        <div>
            <?php if (isLoggedIn()): ?>
                <span>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="helpdesk.php">Help Desk</a>
                <a href="cart.php">Cart</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="helpdesk.php">Help Desk</a>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <h1>All Stores</h1>

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
    </div>

    <div id="chat-widget">
        <div id="chat-header">Chat with Assistant</div>
        <div id="chat-body"></div>
        <div id="chat-footer">
            <input type="text" id="chat-input" placeholder="Type a message..." />
            <button id="chat-send">Send</button>
        </div>
    </div>

    <style>
        #chat-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 300px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
            z-index: 9999;
        }

        #chat-header {
            background: #007BFF;
            color: white;
            padding: 10px;
            cursor: pointer;
            text-align: center;
            font-weight: bold;
        }

        #chat-body {
            flex: 1;
            padding: 10px;
            overflow-y: auto;
            background: #f8f9fa;
        }

        .chat-message {
            margin: 6px 0;
            padding: 6px 10px;
            border-radius: 6px;
            max-width: 85%;
        }

        .chat-user {
            background: #007BFF;
            color: white;
            margin-left: auto;
        }

        .chat-assistant {
            background: #e2e3e5;
        }

        #chat-footer {
            display: flex;
            border-top: 1px solid #ddd;
        }

        #chat-footer input {
            flex: 1;
            padding: 8px;
            border: none;
        }

        #chat-footer button {
            background: #007BFF;
            color: white;
            border: none;
            padding: 0 16px;
        }
    </style>

    <script>
        const chatHeader = document.getElementById('chat-header');
        const chatBody = document.getElementById('chat-body');
        const chatInput = document.getElementById('chat-input');
        const chatSend = document.getElementById('chat-send');
        let open = true;

        chatHeader.onclick = () => {
            open = !open;
            chatBody.style.display = open ? 'block' : 'none';
            document.getElementById('chat-footer').style.display = open ? 'flex' : 'none';
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

</body>

</html>
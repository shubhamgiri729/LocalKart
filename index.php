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
    <title>LocalKart - Multi-Vendor Marketplace</title>

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f8f9fa;
            margin: 0;
            color: #333;
            line-height: 1.5;
        }

        .container {
            max-width: 1200px;
            margin: 30px;
            padding: 0 20px;
        }

        h1 {
            color: #007BFF;
            margin-bottom: 20px;
        }

        .store-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
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
            color: #007BFF;
            margin-bottom: 12px;
        }

        .btn {
            display: inline-block;
            padding: 9px 18px;
            background: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            transition: background 0.15s, box-shadow 0.15s;
        }

        .btn:hover {
            background: #0056b3;
            box-shadow: 0 2px 6px rgba(0, 123, 255, 0.25);
        }
    </style>
</head>

<body>
    <?php include 'partials/header.php'; ?>

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
            width: 250px;
            height: 420px;
            max-height: calc(100vh - 40px);
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            z-index: 9999;
        }

        #chat-widget.collapsed {
            height: auto;
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
            min-height: 0;
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
        const chatWidget = document.getElementById('chat-widget');
        const chatHeader = document.getElementById('chat-header');
        const chatBody = document.getElementById('chat-body');
        const chatInput = document.getElementById('chat-input');
        const chatSend = document.getElementById('chat-send');
        let open = true;

        chatHeader.onclick = () => {
            open = !open;
            chatBody.style.display = open ? 'block' : 'none';
            document.getElementById('chat-footer').style.display = open ? 'flex' : 'none';
            chatWidget.classList.toggle('collapsed', !open);
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
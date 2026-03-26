<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["reply" => "Invalid request method."]);
    exit;
}

$userMessage = trim($_POST['message'] ?? '');
if (!$userMessage) {
    echo json_encode(["reply" => "Please enter a message."]);
    exit;
}

$lowerMsg = strtolower($userMessage);

// --- [KEEPING YOUR DATABASE LOGIC THE SAME] ---

if (preg_match('/price of (.+)/i', $userMessage, $matches)) {
    $productName = trim($matches[1]);
    // Note: Ensure your column names match your DB (e.g., 'name' vs 'product_name')
    $stmt = $pdo->prepare("SELECT name, price, image, description FROM products WHERE name LIKE ?");
    $stmt->execute(["%$productName%"]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $response = "🛒 <b>" . htmlspecialchars($product['name']) . "</b><br>";
        $response .= "💰 Price: ₹" . htmlspecialchars($product['price']) . "<br>";
        if (!empty($product['description'])) {
            $response .= "📄 Description: " . htmlspecialchars($product['description']) . "<br>";
        }
        if (!empty($product['image'])) {
            $response .= "<img src='uploads/products/" . htmlspecialchars($product['image']) . "' width='150' style='margin-top:10px;border-radius:8px;'>";
        }
    } else {
        $response = "❌ Sorry, no product found with that name.";
    }
    echo json_encode(["reply" => $response]);
    exit;
}


$systemPrompt = "You are a helpful chatbot for a multi-vendor marketplace. Keep answers short.";

$data = [
    "model" => "llama3-8b-8192", 
    "messages" => [
        ["role" => "system", "content" => $systemPrompt],
        ["role" => "user", "content" => $userMessage]
    ],
    "temperature" => 0.7,
    "max_tokens" => 300
];

$ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . GROQ_API_KEY, 
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);

if ($response === false) {
    echo json_encode(["reply" => "⚠️ Error connecting to AI server: " . curl_error($ch)]);
    exit;
}

$result = json_decode($response, true);

if (isset($result['error'])) {
    echo json_encode(["reply" => "AI Error: " . $result['error']['message']]);
    exit;
}

$reply = $result['choices'][0]['message']['content'] ?? "Sorry, I couldn't find an answer.";
echo json_encode(["reply" => $reply]);
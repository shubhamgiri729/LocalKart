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

if (mb_strlen($userMessage) > 500) {
    echo json_encode(["reply" => "Please keep your message under 500 characters."]);
    exit;
}

$lowerMsg = strtolower($userMessage);


if (preg_match('/price of (.+)/i', $userMessage, $matches)) {
    $productName = trim($matches[1]);
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


if (!GEMINI_API_KEY) {
    echo json_encode(["reply" => "⚠️ Chat assistant is temporarily unavailable (no API key configured)."]);
    exit;
}

// Throttle the paid Gemini calls: at most 20 per 10 minutes per session, so the
// public chat endpoint can't be used to burn through the API quota.
$now = time();
$_SESSION['chat_hits'] = array_values(array_filter(
    $_SESSION['chat_hits'] ?? [],
    fn($t) => $now - $t < 600
));
if (count($_SESSION['chat_hits']) >= 20) {
    echo json_encode(["reply" => "You're sending messages quickly — please wait a few minutes and try again."]);
    exit;
}
$_SESSION['chat_hits'][] = $now;

$systemPrompt = "You are a helpful chatbot for a multi-vendor marketplace. Keep answers short.";

// Gemini's REST API (generateContent) has no separate "system" role field the way OpenAI/Groq
// do — the recommended way to set behavior is a top-level system_instruction, with the actual
// conversation going in "contents".
$data = [
    "system_instruction" => [
        "parts" => [["text" => $systemPrompt]]
    ],
    "contents" => [
        ["role" => "user", "parts" => [["text" => $userMessage]]]
    ],
    "generationConfig" => [
        "temperature" => 0.7,
        "maxOutputTokens" => 300
    ]
];

$url = "https://generativelanguage.googleapis.com/v1beta/models/" . GEMINI_MODEL . ":generateContent";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "x-goog-api-key: " . GEMINI_API_KEY,
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

$reply = $result['candidates'][0]['content']['parts'][0]['text'] ?? "Sorry, I couldn't find an answer.";
echo json_encode(["reply" => $reply]);
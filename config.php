<?php

// Session cookie hardening — set before session_start() so it actually
// applies to the cookie PHP issues.
// - HttpOnly: JavaScript can't read the session cookie (mitigates XSS
//   stealing the session).
// - SameSite=Lax: the cookie isn't sent on cross-site requests initiated
//   by other sites, which blocks most CSRF vectors as a second layer on
//   top of the csrf_token checks already in this app.
// - Secure: only sent over HTTPS. Detected automatically so this still
//   works locally over plain HTTP in XAMPP, and switches on by itself
//   once deployed behind HTTPS — nothing to toggle manually at deploy time.
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => $isHttps,
]);

session_start();

/**
 * Minimal .env loader — no Composer/dotenv dependency needed. Reads KEY=VALUE lines from a
 * .env file in the same directory (which is git-ignored, see .gitignore) and exposes them via
 * getenv(). Lines starting with # are treated as comments and skipped.
 */
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");

        if (getenv($key) === false) {
            putenv("$key=$value");
        }
    }
}

loadEnv(__DIR__ . '/.env');

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'multi_vendor_ecommerce');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Sorry, the site is temporarily unavailable. Please try again later.');
}
// Gemini configuration.
// The API key must come from the environment and must never be hardcoded.
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
define('GEMINI_MODEL', getenv('GEMINI_MODEL') ?: 'gemini-3.6-flash');

// Base URL of the site, used to build absolute links (e.g. the password
// reset link sent by email). Set APP_URL in .env once you have a real
// domain — falls back to localhost for local development.
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/LocalKart');

// SMTP configuration for outgoing email (password reset, etc.). See
// .env.example for where to get these from a provider like Gmail or Brevo.
define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'no-reply@localkart.test');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'LocalKart');

// Where messages from the Contact Us form are delivered. Defaults to the SMTP
// account itself, so it works out of the box once SMTP is configured.
define('CONTACT_EMAIL', getenv('CONTACT_EMAIL') ?: (getenv('SMTP_USERNAME') ?: ''));

// Razorpay. RAZORPAY_KEY_ID is safe to expose to the browser (checkout.php
// prints it into the page for Checkout.js) — RAZORPAY_KEY_SECRET must never
// leave the server; it's only used server-side in razorpay_create_order.php
// and process_order.php to call the Razorpay API and verify payments.
define('RAZORPAY_KEY_ID', getenv('RAZORPAY_KEY_ID') ?: '');
define('RAZORPAY_KEY_SECRET', getenv('RAZORPAY_KEY_SECRET') ?: '');

/**

 * @return bool
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Get the logged-in user's role
 *
 * @return string|null
 */
function getUserRole(): ?string
{
    return $_SESSION['role'] ?? null;
}

/**
 * Redirect user based on role
 *
 * @param string $adminPage
 * @param string $shopkeeperPage
 * @param string $customerPage
 * @return void
 */
function redirectByRole(
    string $adminPage,
    string $shopkeeperPage,
    string $customerPage
): void {

    $role = getUserRole();

    switch ($role) {
        case 'admin':
            header("Location: $adminPage");
            break;

        case 'shopkeeper':
            header("Location: $shopkeeperPage");
            break;

        case 'customer':
            header("Location: $customerPage");
            break;

        default:
            header("Location: login.php");
            break;
    }

    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

/**
 * Return the CSRF token for this session, generating one on first use.
 * Call this wherever a form is rendered and echo it into a hidden field
 * named "csrf_token".
 *
 * @return string
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Echo a ready-to-use hidden <input> carrying the CSRF token. Drop this
 * right after the opening <form> tag of every form that submits via POST.
 *
 * @return void
 */
function csrfField(): void
{
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

/**
 * Create a Razorpay order via their REST API. This must happen server-side
 * (it needs the secret key) before the Checkout.js widget opens — Razorpay
 * requires a valid order_id up front, it doesn't accept an arbitrary amount
 * typed into the frontend.
 *
 * @param int $amountInPaise Amount in paise (₹1 = 100 paise). Always compute
 *                           this from the server-side cart total, never from
 *                           anything the client sent — otherwise a tampered
 *                           request could create an order for any amount.
 * @param string $receipt A short reference string, e.g. "order_rcpt_7".
 * @return array|false Decoded Razorpay order on success, false on failure.
 */
function razorpayCreateOrder(int $amountInPaise, string $receipt): array|false
{
    if (!RAZORPAY_KEY_ID || !RAZORPAY_KEY_SECRET) {
        error_log('Razorpay keys are not configured in .env');
        return false;
    }

    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_USERPWD => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode([
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'receipt' => $receipt,
            'payment_capture' => 1,
        ]),
        CURLOPT_TIMEOUT => 15,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        error_log("Razorpay order creation failed: HTTP $httpCode $curlError $response");
        return false;
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : false;
}

/**
 * Verify a completed Razorpay payment actually belongs to this order and
 * wasn't forged. Razorpay signs order_id + "|" + payment_id with your key
 * secret (HMAC-SHA256); recomputing that signature server-side and comparing
 * it is the only way to trust a payment_id the browser sends back — the
 * browser's word alone is not enough, anyone could POST a fake payment_id.
 */
function razorpayVerifySignature(string $orderId, string $paymentId, string $signature): bool
{
    if (!RAZORPAY_KEY_SECRET) {
        return false;
    }

    $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, RAZORPAY_KEY_SECRET);

    return hash_equals($expected, $signature);
}

/**
 * Halt the request with a 403 if the submitted csrf_token doesn't match
 * the one stored in the session. Call this as the first line of every
 * POST handler, before any other $_POST access.
 *
 * hash_equals() is used instead of === for a timing-safe comparison.
 *
 * @return void
 */
function requireCsrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';

    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submitted)) {
        http_response_code(403);
        die('Your session expired or this request could not be verified. Please go back, refresh the page, and try again.');
    }
}

/**
 * Recompute an order's overall status from the per-vendor status of its
 * order_items, and write it back to orders.status.
 *
 * order_items.status is the source of truth (each vendor dispatches/
 * delivers their own line items independently). orders.status is kept
 * as a derived summary column — customer.php and admin.php show a single
 * badge per order, and this is what feeds it:
 *   - 'pending'    if every item is still pending
 *   - 'dispatched' if at least one item has moved but not all are delivered
 *   - 'delivered'  once every item in the order is delivered
 *
 * Call this after any code path that changes an order_items.status value.
 *
 * @return void
 */
function recomputeOrderStatus(PDO $pdo, int $orderId): void
{
    $stmt = $pdo->prepare("SELECT status FROM order_items WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!$statuses) {
        return;
    }

    if (!in_array('pending', $statuses, true) && !in_array('dispatched', $statuses, true)) {
        $overall = 'delivered';
    } elseif (in_array('dispatched', $statuses, true) || in_array('delivered', $statuses, true)) {
        $overall = 'dispatched';
    } else {
        $overall = 'pending';
    }

    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$overall, $orderId]);
}

// --- Login rate limiting -----------------------------------------------
// Backed by the login_attempts table (see database.sql /
// migration_shipping_and_item_status.sql) rather than in-memory state, so
// the limit holds across requests, PHP-FPM workers, and server restarts.

define('LOGIN_ATTEMPT_LIMIT', 5);
define('LOGIN_ATTEMPT_WINDOW_MINUTES', 15);

/**
 * Best-effort client IP. Trusts X-Forwarded-For only if you configure a
 * reverse proxy that sets it — on plain XAMPP/Apache this is just
 * REMOTE_ADDR. Good enough to throttle a single brute-forcing client;
 * it isn't meant to defeat a determined, distributed attacker (that
 * needs a WAF/CDN in front of the app, which is outside this app's scope).
 *
 * @return string
 */
function clientIp(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Whether this IP has hit the failed-login limit within the current
 * window and should be blocked from attempting another login.
 *
 * @return bool
 */
function tooManyLoginAttempts(PDO $pdo, string $ip): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM login_attempts
         WHERE ip_address = ? AND attempted_at > (NOW() - INTERVAL ? MINUTE)"
    );
    $stmt->execute([$ip, LOGIN_ATTEMPT_WINDOW_MINUTES]);

    return (int) $stmt->fetchColumn() >= LOGIN_ATTEMPT_LIMIT;
}

/**
 * Record a failed login attempt for this IP, and opportunistically prune
 * attempts older than a day so the table doesn't grow forever (no cron
 * needed for a project this size — a 1-in-20 chance per failed attempt
 * keeps it bounded without adding overhead to every single request).
 *
 * @return void
 */
function recordFailedLogin(PDO $pdo, string $ip, string $username): void
{
    $stmt = $pdo->prepare(
        "INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)"
    );
    $stmt->execute([$ip, substr($username, 0, 50)]);

    if (random_int(1, 20) === 1) {
        $pdo->exec("DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)");
    }
}

/**
 * Clear this IP's recent failed attempts after a successful login, so a
 * legitimate user who mistyped their password a few times isn't left
 * sitting near the limit.
 *
 * @return void
 */
function clearLoginAttempts(PDO $pdo, string $ip): void
{
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
    $stmt->execute([$ip]);
}

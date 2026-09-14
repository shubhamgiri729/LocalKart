<?php

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
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'multi_vendor_ecommerce');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Gemini configuration.
// The API key must come from the environment and must never be hardcoded.
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
define('GEMINI_MODEL', getenv('GEMINI_MODEL') ?: 'gemini-3.6-flash');

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

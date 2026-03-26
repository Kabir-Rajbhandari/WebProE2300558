<?php
// ---> Database connection settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');         
define('DB_PASS', '');             
define('DB_NAME', 'eduskill_db');

// ---> Application configuration constants
define('APP_NAME', 'EduSkill Marketplace');
define('APP_URL', 'http://localhost/ems');
define('UPLOAD_DIR', __DIR__ . '/../uploads/documents/');
define('ALLOWED_FILE_TYPES', ['application/pdf', 'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// ---> Initialize user session with secure cookie settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

// ---> Error reporting and logging configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// ---> Establish database connection with error handling
function getDbConnection(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        error_log('DB Connection failed: ' . $conn->connect_error);
        http_response_code(500);
        include __DIR__ . '/../error.php';
        exit;
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// ---> Remove HTML tags and escape user input for security
function sanitize(string $data): string {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Check if user is logged in
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . APP_URL . '/pages/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

// Check if user has required role
function requireRole(string $role): void {
    requireLogin();
    if ($_SESSION['user_role'] !== $role) {
        header('Location: ' . APP_URL . '/pages/error.php?code=403');
        exit;
    }
}

// Redirect to another page
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// Store a message to show on next page
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Get and clear the stored message
function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Display the stored message as an alert
function renderFlash(): void {
    $flash = getFlash();
    if (!$flash) return;

    $typeMap = [
        'success' => 'alert-success-ems',
        'error'   => 'alert-danger-ems',
        'warning' => 'alert-warning-ems',
        'info'    => 'alert-info-ems',
    ];
    $iconMap = [
        'success' => 'fa-check-circle',
        'error'   => 'fa-times-circle',
        'warning' => 'fa-exclamation-triangle',
        'info'    => 'fa-info-circle',
    ];

    $class = $typeMap[$flash['type']] ?? 'alert-info-ems';
    $icon  = $iconMap[$flash['type']] ?? 'fa-info-circle';
    $msg   = htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');

    echo "<div class='alert-ems {$class}'>
            <i class='fas {$icon}'></i>
            <span>{$msg}</span>
          </div>";
}

// Store password as plain text
function hashPassword(string $password): string {
    return $password; // Plain text - no hashing
}

// Check if password matches
function verifyPassword(string $password, string $hash): bool {
    return $password === $hash; // Direct string comparison
}

// Create receipt number format
function generateReceiptNumber(int $enrollId): string {
    return 'EMS-' . date('Y') . '-' . str_pad($enrollId, 6, '0', STR_PAD_LEFT);
}

// Format currency in Ringgit
function formatCurrency(float $amount): string {
    return 'RM ' . number_format($amount, 2);
}

// Get current user name from session
function getSessionUserName(): string {
    return htmlspecialchars($_SESSION['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
}

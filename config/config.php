<?php
/**
 * Invoicent SaaS Application
 * Database Configuration File
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'invoicent_db');
define('DB_PORT', 3306);

// Application Settings
define('APP_NAME', 'Invoicent');
define('APP_URL', 'http://localhost/invoicent');
define('APP_ENV', 'development'); // Change to 'production' for live

// Session Configuration
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
define('REMEMBER_ME_DURATION', 2592000); // 30 days in seconds

// Security Settings
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 900); // 15 minutes

// File Upload Settings
define('MAX_UPLOAD_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// PDF Settings
define('PDF_PAGE_FORMAT', 'A5');
define('PDF_MARGIN_LEFT', 10);
define('PDF_MARGIN_RIGHT', 10);
define('PDF_MARGIN_TOP', 10);
define('PDF_MARGIN_BOTTOM', 10);

// Email Configuration (for invoice sending)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('SMTP_FROM_NAME', 'Invoicent');

// WhatsApp Configuration (integrate with WhatsApp API)
define('WHATSAPP_API_URL', 'https://api.whatsapp.com/send');
define('WHATSAPP_BUSINESS_PHONE', '1234567890');

// Currency Symbols
define('CURRENCY_SYMBOLS', [
    'NGN' => '₦',
    'USD' => '$',
    'GBP' => '£',
    'EUR' => '€'
]);

// Create database connection using MySQLi
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4
    $conn->set_charset("utf8mb4");
    
    // Enable error reporting in development
    if (APP_ENV === 'development') {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    }
    
} catch (Exception $e) {
    // Log error
    error_log($e->getMessage());
    
    // Show user-friendly message
    if (APP_ENV === 'production') {
        die("An error occurred. Please try again later.");
    } else {
        die("Database Error: " . $e->getMessage());
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    session_set_cookie_params([
        'httponly' => true,
        'secure' => (APP_ENV === 'production'),
        'samesite' => 'Strict'
    ]);
}

// Helper function to generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

// Helper function to verify CSRF token
function verifyCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRY) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Helper function to execute prepared statements
function executeQuery($conn, $query, $params = [], $types = '') {
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    return $stmt;
}

// Helper function for error logging
function logError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message";
    
    if (!empty($context)) {
        $log_message .= " | Context: " . json_encode($context);
    }
    
    error_log($log_message, 3, __DIR__ . '/../logs/error.log');
}

// Helper function for activity logging
function logActivity($user_id, $action, $details = '') {
    global $conn;
    
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $query = "INSERT INTO login_logs (user_id, action, ip_address, user_agent, details, created_at) 
                  VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = executeQuery($conn, $query, [$user_id, $action, $ip_address, $user_agent, $details], 'issss');
        $stmt->close();
    } catch (Exception $e) {
        logError("Failed to log activity", ['error' => $e->getMessage()]);
    }
}
?>

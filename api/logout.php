<?php
/**
 * Invoicent SaaS Application
 * Logout Handler
 */

require_once __DIR__ . '/../config/config.php';

// Check if user is authenticated
if (empty($_SESSION['user_id'])) {
    header('Location: /invoicent/auth/login.html');
    exit;
}

$user_id = $_SESSION['user_id'];

// Remove session from database
$session_id = session_id();
$stmt = $conn->prepare("DELETE FROM sessions WHERE id = ? AND user_id = ?");
$stmt->bind_param("si", $session_id, $user_id);
$stmt->execute();
$stmt->close();

// Log activity
logActivity($user_id, 'logout', 'User logged out');

// Destroy session
session_destroy();

// Redirect to login
header('Location: /invoicent/auth/login.html');
exit;
?>

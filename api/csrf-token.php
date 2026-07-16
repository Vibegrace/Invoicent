<?php
/**
 * CSRF Token API
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

echo json_encode([
    'csrf_token' => generateCSRFToken()
]);
?>

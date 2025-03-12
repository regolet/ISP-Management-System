<?php
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/PaymentWebhook.php';

// Get raw POST data
$payload = file_get_contents('php://input');
$headers = getallheaders();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize webhook handler
$webhookHandler = new PaymentWebhook($db);

try {
    // Determine payment provider from request path
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $provider = end($pathParts);

    if (!in_array($provider, ['stripe', 'paypal'])) {
        throw new \Exception('Invalid payment provider');
    }

    // Set response headers
    header('Content-Type: application/json');

    // Log incoming webhook
    error_log(sprintf(
        "[Webhook] Received %s webhook: %s",
        $provider,
        substr($payload, 0, 1000)
    ));

    // Handle webhook
    $result = $webhookHandler->handleWebhook(
        $provider,
        json_decode($payload, true),
        $headers
    );

    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Webhook processed successfully'
    ]);

} catch (\Exception $e) {
    // Log error
    error_log(sprintf(
        "[Webhook Error] %s: %s",
        $e->getMessage(),
        $e->getTraceAsString()
    ));

    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error processing webhook: ' . $e->getMessage()
    ]);
}

<?php
/**
 * Main entry point for the Clairvoyance KPI API
 */

// Allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// For debugging - log all requests
error_log("API Request: " . $_SERVER['REQUEST_URI']);

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Load dependencies
require_once __DIR__ . '/vendor/autoload.php'; // Composer autoloader
require_once __DIR__ . '/config/database.php';

// Initialize request processing
$request = $_SERVER['REQUEST_URI'];

// Check if there's a query parameter called 'request' from .htaccess
if (isset($_GET['request'])) {
    // This means .htaccess is correctly rewriting the URL
    $requestPath = $_GET['request'];
    error_log("Request from GET parameter: " . $requestPath);
    $request = explode('/', $requestPath);
} else {
    // Parse the URL manually
    $request = explode('?', $request)[0]; // Remove query parameters
    $request = trim($request, '/');
    error_log("Parsed request path: " . $request);
    
    // Split the path into segments
    $request = explode('/', $request);
}

// Debug - show request segments
error_log("Request segments: " . print_r($request, true));

// Remove path segments leading to 'api' if they exist
$apiPos = array_search('api', $request);
if ($apiPos !== false) {
    // Remove everything up to and including 'api'
    $request = array_slice($request, $apiPos + 1);
    error_log("Removed path prefix, new request: " . print_r($request, true));
}

// Include the appropriate router file based on the request
try {
    if (empty($request[0])) {
        // Default route
        echo json_encode(['status' => 'success', 'message' => 'Welcome to Clairvoyance KPI API']);
    } elseif ($request[0] === 'auth') {
        // Authentication routes
        error_log("Loading auth routes for action: " . ($request[1] ?? 'none'));
        require_once __DIR__ . '/routes/auth.php';
    } else {
        // API routes
        error_log("Loading API routes for resource: " . $request[0]);
        require_once __DIR__ . '/routes/api.php';
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
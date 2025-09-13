<?php
/**
 * Route Testing Script for Clairvoyance KPI API
 * Run this script to test all API endpoints
 */

// Set up the environment
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/api/test';
$_SERVER['HTTP_ORIGIN'] = 'http://localhost';

// Include the bootstrap
require_once __DIR__ . '/bootstrap.php';

echo "🧪 Testing Clairvoyance KPI API Routes\n";
echo "=====================================\n\n";

// Test data
$testUser = [
    'email' => 'test@example.com',
    'password' => 'password123',
    'role' => 'viewer'
];

$testKpi = [
    'name' => 'Test KPI',
    'direction' => 'higher_is_better',
    'target' => 100.0,
    'rag_red' => 50.0,
    'rag_amber' => 75.0,
    'format_prefix' => '$',
    'format_suffix' => ''
];

$testDashboard = [
    'name' => 'Test Dashboard',
    'description' => 'Test Description',
    'layout' => '[{"id":1,"x":0,"y":0,"w":4,"h":9,"title":"Test Widget","type":"line","kpi_id":1}]'
];

// Helper function to make requests
function makeRequest($method, $endpoint, $data = null, $headers = []) {
    $url = "http://localhost/clairvoyance-v3/api" . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge([
        'Content-Type: application/json',
        'Accept: application/json'
    ], $headers));
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

// Test functions
function testRoute($name, $method, $endpoint, $data = null, $expectedCode = 200) {
    echo "Testing $name... ";
    
    $response = makeRequest($method, $endpoint, $data);
    
    if ($response['code'] == $expectedCode) {
        echo "✅ PASS\n";
        return $response;
    } else {
        echo "❌ FAIL (Expected: $expectedCode, Got: {$response['code']})\n";
        if ($response['body']) {
            echo "   Error: " . ($response['body']['error'] ?? 'Unknown error') . "\n";
        }
        return $response;
    }
}

// Run tests
echo "1. Testing Authentication Routes\n";
echo "--------------------------------\n";

// Test registration
$regResponse = testRoute('User Registration', 'POST', '/auth/register', $testUser, 201);
if ($regResponse['code'] == 201) {
    $userId = $regResponse['body']['data']['id'] ?? null;
    echo "   User ID: $userId\n";
}

// Test login
$loginResponse = testRoute('User Login', 'POST', '/auth/login', [
    'email' => $testUser['email'],
    'password' => $testUser['password']
], 200);

// Extract session cookie if login was successful
$sessionCookie = '';
if ($loginResponse['code'] == 200) {
    echo "   Login successful\n";
    // In a real test, you'd extract the session cookie here
}

echo "\n2. Testing KPI Routes\n";
echo "---------------------\n";

// Test KPI creation (should fail without auth)
testRoute('KPI Creation (No Auth)', 'POST', '/kpis', $testKpi, 401);

// Test KPI listing (should fail without auth)
testRoute('KPI Listing (No Auth)', 'GET', '/kpis', null, 401);

echo "\n3. Testing Dashboard Routes\n";
echo "---------------------------\n";

// Test dashboard creation (should fail without auth)
testRoute('Dashboard Creation (No Auth)', 'POST', '/dashboards', $testDashboard, 401);

// Test dashboard listing (should fail without auth)
testRoute('Dashboard Listing (No Auth)', 'GET', '/dashboards', null, 401);

echo "\n4. Testing Admin Routes\n";
echo "-----------------------\n";

// Test admin user listing (should fail without auth)
testRoute('Admin User Listing (No Auth)', 'GET', '/admin/users', null, 401);

echo "\n5. Testing Error Routes\n";
echo "-----------------------\n";

// Test non-existent route
testRoute('Non-existent Route', 'GET', '/nonexistent', null, 404);

// Test invalid method
testRoute('Invalid Method', 'DELETE', '/auth/login', null, 405);

echo "\n✅ Route testing completed!\n";
echo "\nNote: Most routes will return 401 (Unauthorized) because we're not sending session cookies.\n";
echo "This is expected behavior - the routes are working, but authentication is required.\n";
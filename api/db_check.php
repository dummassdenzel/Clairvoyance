<?php
// Database check script

require_once __DIR__ . '/config/database.php';

// Output as JSON
header('Content-Type: application/json');

try {
    // Connect to database
    $conn = new Connection();
    $pdo = $conn->connect();
    
    // Check if connection was successful
    $tables = [];
    $result = $pdo->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    // Check users table structure
    $usersStructure = [];
    $result = $pdo->query("DESCRIBE users");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $usersStructure[] = $row;
    }
    
    // Output result
    echo json_encode([
        'status' => 'success',
        'message' => 'Database connection successful',
        'tables' => $tables,
        'users_structure' => $usersStructure,
        'php_version' => PHP_VERSION,
        'pdo_version' => $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION)
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
} 
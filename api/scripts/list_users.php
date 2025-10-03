<?php
// Usage: php list_users.php

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

// Include database configuration
require_once __DIR__ . '/../config/database.php';

try {
    // Create database connection
    $dsn = "mysql:host=" . SERVER . ";dbname=" . DATABASE . ";charset=utf8mb4";
    $pdo = new PDO($dsn, USER, PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Get all users
    $stmt = $pdo->query("SELECT id, email, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "No users found in the database.\n";
        exit(0);
    }
    
    echo "Users in the system:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-5s %-30s %-10s %-20s\n", "ID", "Email", "Role", "Created");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($users as $user) {
        $created = date('Y-m-d H:i:s', strtotime($user['created_at']));
        printf("%-5s %-30s %-10s %-20s\n", 
            $user['id'], 
            $user['email'], 
            $user['role'], 
            $created
        );
    }
    
    echo str_repeat("-", 80) . "\n";
    echo "Total users: " . count($users) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

exit(0);

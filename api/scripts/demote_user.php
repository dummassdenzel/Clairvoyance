<?php
// Usage: php demote_user.php <user_email>

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

if ($argc < 2) {
    echo "Usage: php " . basename(__FILE__) . " <user_email>\n";
    exit(1);
}

// Include database configuration
require_once __DIR__ . '/../config/database.php';

$email = $argv[1];
$roleToAssign = 'editor';

try {
    // Create database connection
    $dsn = "mysql:host=" . SERVER . ";dbname=" . DATABASE . ";charset=utf8mb4";
    $pdo = new PDO($dsn, USER, PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // First, check if user exists
    $stmt = $pdo->prepare("SELECT id, email, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "Error: User with email '{$email}' not found.\n";
        exit(1);
    }
    
    echo "Found user: ID={$user['id']}, Email={$user['email']}, Current Role={$user['role']}\n";
    
    // Update the user's role
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $result = $stmt->execute([$roleToAssign, $user['id']]);
    
    if ($result) {
        echo "Successfully demoted user '{$email}' to '{$roleToAssign}'.\n";
    } else {
        echo "Error: Could not demote user. Database update failed.\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

exit(0);

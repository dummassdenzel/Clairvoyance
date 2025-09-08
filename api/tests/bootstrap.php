<?php

/**
 * Test Bootstrap
 * 
 * This file sets up the testing environment for our model tests.
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Set up test environment
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone for consistent testing
date_default_timezone_set('UTC');

// Load environment variables for testing
if (file_exists(__DIR__ . '/../.env.test')) {
    $lines = file(__DIR__ . '/../.env.test', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Initialize the application
use Core\Application;

$app = Application::getInstance();

echo "🧪 Test Environment Initialized\n";
echo "📦 Application loaded successfully\n";
echo "🔗 Dependency injection container ready\n\n";
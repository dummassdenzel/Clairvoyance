<?php

// 1. Require the Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// 2. Load the .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 3. Test if we can read the environment variable
echo "Testing environment variable access:\n";
echo "-------------------------------------\n";

// Test direct $_ENV access
echo "JWT_KEY from \$_ENV: ";
var_dump($_ENV['JWT_KEY'] ?? 'Not found');

// Test getenv() function
echo "\nJWT_KEY from getenv(): ";
var_dump(getenv('JWT_KEY'));



require_once __DIR__ . '/environment.php';
echo "\nJWT_KEY from Environment class: ";
var_dump(Environment::getInstance()->get('JWT_KEY'));
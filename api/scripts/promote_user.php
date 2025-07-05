<?php
// Usage: php promote_user.php <user_email>

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

if ($argc < 2) {
    echo "Usage: php " . basename(__FILE__) . " <user_email>\n";
    exit(1);
}

require_once __DIR__ . '/../models/User.php';

$email = $argv[1];
$roleToAssign = 'admin';

$user = new User();
$result = $user->updateRoleByEmail($email, $roleToAssign);

if ($result['success']) {
    echo "Successfully promoted user '{$email}' to '{$roleToAssign}'.\n";
} else {
    echo "Error: Could not promote user. Reason: {$result['error']}\n";
    exit(1);
}

exit(0);

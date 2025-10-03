<?php

require_once __DIR__ . '/../bootstrap.php';

use Controllers\UserController;

$controller = new UserController();

// The global $request variable is parsed in index.php
// Example: /api/users/123 -> $request = ['users', '123']
$id = $request[1] ?? null;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($id) {
            $controller->getOne((int)$id);
        } else {
            $controller->listAll();
        }
        break;

    case 'POST':
        $controller->findByEmail();
        break;

    case 'PUT':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'User ID is required for updating']);
        }
        $controller->updateRole((int)$id);
        break;

    case 'DELETE':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'User ID is required for deletion']);
        }
        $controller->delete((int)$id);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}
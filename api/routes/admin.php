<?php

// Bootstrap the application
require_once __DIR__ . '/../bootstrap.php';

use Controllers\AdminController;
use Controllers\AdminStatsController;

// The global $request variable is parsed in index.php
// For /api/admin/users/123, $request would be ['admin', 'users', '123']

$adminController = new AdminController();
$adminStatsController = new AdminStatsController();

$resource = $request[1] ?? null; // e.g., 'users', 'stats'
$id = $request[2] ?? null;       // e.g., '123'

// Handle stats endpoint
if ($resource === 'stats') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $adminStatsController->getSystemStats();
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    }
    exit();
}

if ($resource !== 'users') {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Resource not found.']);
    exit();
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // GET /api/admin/users
        if ($id === null) {
            $adminController->listUsers();
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Resource not found.']);
        }
        break;

    case 'POST':
        // POST /api/admin/users
        $adminController->createUser();
        break;

    case 'PUT':
        // PUT /api/admin/users/{id}
        if (is_numeric($id)) {
            $adminController->updateUserRole($id);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'A numeric User ID is required for updating.']);
        }
        break;

    case 'DELETE':
        // DELETE /api/admin/users/{id}
        if (is_numeric($id)) {
            $adminController->deleteUser($id);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'A numeric User ID is required for deleting.']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
        break;
}


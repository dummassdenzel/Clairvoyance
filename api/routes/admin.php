<?php

require_once __DIR__ . '/../controllers/AdminController.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';

// The global $request variable is parsed in index.php
// For /api/admin/users/123, $request would be ['admin', 'users', '123']

$authMiddleware = new AuthMiddleware();
$adminController = new AdminController();

// First, ensure the user is an admin for all routes in this file.
$user = $authMiddleware->authenticate();
if (!$user || !$authMiddleware->verifyRole($user, 'admin')) {
    Response::forbidden('Access denied. Administrator privileges required.');
    exit;
}

$resource = $request[1] ?? null; // e.g., 'users'
$id = $request[2] ?? null;       // e.g., '123'

if ($resource !== 'users') {
    Response::notFound('Resource not found.');
    exit();
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // GET /api/admin/users
        if ($id === null) {
            $adminController->listUsers();
        } else {
            Response::notFound('Resource not found.');
        }
        break;

    case 'PUT':
        // PUT /api/admin/users/{id}
        if (is_numeric($id)) {
            $adminController->updateUserRole($id);
        } else {
            Response::badRequest('A numeric User ID is required for updating.');
        }
        break;

    case 'DELETE':
        // DELETE /api/admin/users/{id}
        if (is_numeric($id)) {
            $adminController->deleteUser($id);
        } else {
            Response::badRequest('A numeric User ID is required for deleting.');
        }
        break;

    default:
        Response::methodNotAllowed();
        break;
}


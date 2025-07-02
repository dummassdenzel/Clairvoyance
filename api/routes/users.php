<?php
// User registration route
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';

$roleMiddleware = new RoleMiddleware();
$controller = new UserController();

// All user management endpoints are restricted to admins.
$roleMiddleware->requireAdmin();

// The global $request variable is parsed in index.php
// Example: /api/users/123 -> $request = ['users', '123']
$id = $request[1] ?? null;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($id) {
            $controller->getById($id);
        } else {
            $controller->listAll();
        }
        break;


    case 'PUT':
        if (!$id) {
            Response::badRequest('User ID is required for updating.');
        }
        $controller->update($id);
        break;

    case 'DELETE':
        if (!$id) {
            Response::badRequest('User ID is required for deletion.');
        }
        $controller->delete($id);
        break;

    default:
        Response::methodNotAllowed();
        break;
}
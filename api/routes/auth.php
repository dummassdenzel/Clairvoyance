<?php

require_once __DIR__ . '/../bootstrap.php';

use Controllers\UserController;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$controller = new UserController();
$action = $request[1] ?? null;

switch ($action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->login();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
        }
        break;

    case 'register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->register();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
        }
        break;

    case 'logout':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->logout();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
        }
        break;

    case 'me':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $controller->me();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Authentication endpoint not found']);
        break;
}
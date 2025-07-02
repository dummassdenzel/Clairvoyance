<?php

require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../utils/Response.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$controller = new UserController();
$action = $request[1] ?? null;

switch ($action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $controller->login($data);
        } else {
            Response::error('Method Not Allowed', null, 405);
        }
        break;

    case 'register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $controller->register($data);
        } else {
            Response::error('Method Not Allowed', null, 405);
        }
        break;

    case 'logout':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            session_unset();
            session_destroy();
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            Response::success('Logged out successfully');
        } else {
            Response::error('Method Not Allowed', null, 405);
        }
        break;

    case 'me':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (isset($_SESSION['user'])) {
                Response::success('Session is valid.', ['user' => $_SESSION['user']]);
            } else {
                Response::error('No active session.', null, 401);
            }
        } else {
            Response::error('Method Not Allowed', null, 405);
        }
        break;

    default:
        Response::error('Authentication endpoint not found.', null, 404);
        break;
}
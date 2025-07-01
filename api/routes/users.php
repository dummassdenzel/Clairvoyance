<?php
// User registration route
require_once __DIR__ . '/../controllers/UserController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    (new UserController())->register();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    (new UserController())->listAll();
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
} 
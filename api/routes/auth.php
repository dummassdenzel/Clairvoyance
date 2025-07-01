<?php
/**
 * Authentication routes for the Clairvoyance KPI API
 */

require_once __DIR__ . '/../controllers/UserController.php';
session_start();

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'logout') {
        // Logout: destroy session
        session_unset();
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out']);
    } else {
        // Login
        $data = json_decode(file_get_contents('php://input'), true);
        (new UserController())->login($data);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
} 
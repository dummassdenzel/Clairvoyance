<?php
require_once __DIR__ . '/../controllers/DashboardController.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'assign_viewer') {
        (new DashboardController())->assignViewer();
    } else if ($action === 'remove_viewer') {
        (new DashboardController())->removeViewer();
    } else {
        (new DashboardController())->create();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        (new DashboardController())->get();
    } else {
        (new DashboardController())->listAll();
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
} 
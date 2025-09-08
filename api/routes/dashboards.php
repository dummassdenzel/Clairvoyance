<?php

require_once __DIR__ . '/../bootstrap.php';

use Controllers\DashboardController;

$controller = new DashboardController();

// The global $request variable is parsed in index.php
// Example: /api/dashboards/123/viewers/456 -> $request = ['dashboards', '123', 'viewers', '456']
$part1 = $request[1] ?? null;
$part2 = $request[2] ?? null;
$part3 = $request[3] ?? null;

// --- Route for dashboard sharing ---
if ($part1 === 'share' && isset($part2)) {
    $token = $part2;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->redeemShareLink($token);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    exit();
}

// --- Sub-resource routing for /dashboards/{id}/viewers and /dashboards/{id}/share ---  
if (is_numeric($part1) && $part2 === 'share') {
    $dashboardId = (int)$part1;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->generateShareLink($dashboardId);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    exit();
}

if (is_numeric($part1) && $part2 === 'viewers') {
    $dashboardId = (int)$part1;
    $userId = is_numeric($part3) ? (int)$part3 : null;

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            $controller->assignViewer($dashboardId);
            break;
        case 'DELETE':
            if (!$userId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'User ID is required to remove a viewer']);
            }
            $controller->removeViewer($dashboardId, $userId);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed for this resource']);
            break;
    }
    exit(); // Stop further processing
}

// --- Route for dashboard reports ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && is_numeric($part1) && $part2 === 'report') {
    $dashboardId = (int)$part1;
    $controller->getReportData($dashboardId);
    exit();
}

// --- Primary resource routing for /dashboards --- 
$id = is_numeric($part1) ? (int)$part1 : null;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($id) {
            $controller->get($id);
        } else {
            // If part1 exists but is not numeric, it's an invalid path
            if ($part1 !== null) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid dashboard ID specified']);
            } else {
                $controller->listAll();
            }
        }
        break;

    case 'POST':
        $controller->create();
        break;

    case 'PUT':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'A numeric Dashboard ID is required for updating']);
        }
        $controller->update($id);
        break;

    case 'DELETE':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'A numeric Dashboard ID is required for deletion']);
        }
        $controller->delete($id);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}
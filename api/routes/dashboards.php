<?php

require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';

$roleMiddleware = new RoleMiddleware();
$controller = new DashboardController();

// The global $request variable is parsed in index.php
// Example: /api/dashboards/123/viewers/456 -> $request = ['dashboards', '123', 'viewers', '456']
$part1 = $request[1] ?? null;
$part2 = $request[2] ?? null;
$part3 = $request[3] ?? null;

// --- Route for dashboard sharing ---
if ($part1 === 'share' && isset($part2)) {
    $token = $part2;
    $roleMiddleware->requireLogin(); // Any logged-in user can redeem
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->redeemShareLink($token);
    } else {
        Response::methodNotAllowed();
    }
    exit();
}

// --- Sub-resource routing for /dashboards/{id}/viewers and /dashboards/{id}/share ---  
if (is_numeric($part1) && $part2 === 'share') {
    $dashboardId = $part1;
    $roleMiddleware->requireEditor(); // Only editors can generate share links
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->generateShareLink($dashboardId);
    } else {
        Response::methodNotAllowed();
    }
    exit();
}

if (is_numeric($part1) && $part2 === 'viewers') {
    $dashboardId = $part1;
    $userId = is_numeric($part3) ? $part3 : null;

    $roleMiddleware->requireEditor(); // Only editors can manage viewers
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            $controller->assignViewer($dashboardId);
            break;
        case 'DELETE':
            if (!$userId) {
                Response::error('User ID is required to remove a viewer.', null, 400);
            }
            $controller->removeViewer($dashboardId, $userId);
            break;
        default:
            Response::error('Method not allowed for this resource.', null, 405);
            break;
    }
    exit(); // Stop further processing
}

// --- Primary resource routing for /dashboards --- 
$id = is_numeric($part1) ? $part1 : null;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $roleMiddleware->requireViewer();
        if ($id) {
            $controller->get($id);
        } else {
            // If part1 exists but is not numeric, it's an invalid path
            if ($part1 !== null) {
                Response::error('Invalid dashboard ID specified.', null, 400);
            } else {
                $controller->listAll();
            }
        }
        break;

    case 'POST':
        $roleMiddleware->requireEditor();
        $controller->create();
        break;

    case 'PUT':
        $roleMiddleware->requireEditor();
        if (!$id) {
            Response::error('A numeric Dashboard ID is required for updating.', null, 400);
        }
        $controller->update($id);
        break;

    case 'DELETE':
        $roleMiddleware->requireEditor();
        if (!$id) {
            Response::error('A numeric Dashboard ID is required for deletion.', null, 400);
        }
        $controller->delete($id);
        break;

    default:
        Response::error('Method not allowed.', null, 405);
        break;
}
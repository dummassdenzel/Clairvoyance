<?php

require_once __DIR__ . '/../controllers/KpiController.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';

$roleMiddleware = new RoleMiddleware();
$controller = new KpiController();

// The global $request variable is parsed in index.php
// Example: /api/kpis/123/entries -> $request = ['kpis', '123', 'entries']
$part1 = $request[1] ?? null;
$part2 = $request[2] ?? null;

// --- Sub-resource routing for /kpis/{id}/entries ---
if (is_numeric($part1) && $part2 === 'entries') {
    $kpiId = $part1;
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $roleMiddleware->requireViewer();
        $controller->listEntries($kpiId);
    } else {
        Response::error('Method not allowed for this resource.', null, 405);
    }
    exit(); // Stop further processing
}

// --- Primary resource routing for /kpis ---
$id = is_numeric($part1) ? $part1 : null;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $roleMiddleware->requireViewer();
        if ($id) {
            // The getById method is not yet implemented in the controller
            // For now, we can add a placeholder response.
            Response::error('Fetching a single KPI is not yet supported.', null, 501); 
            // $controller->getById($id);
        } else {
             if ($part1 !== null) {
                Response::error('Invalid KPI ID specified.', null, 400);
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
            Response::error('A numeric KPI ID is required for updating.', null, 400);
        }
        $controller->update($id);
        break;

    case 'DELETE':
        $roleMiddleware->requireEditor();
        if (!$id) {
            Response::error('A numeric KPI ID is required for deletion.', null, 400);
        }
        $controller->delete($id);
        break;

    default:
        Response::error('Method not allowed.', null, 405);
        break;
}
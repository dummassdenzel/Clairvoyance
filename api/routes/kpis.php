<?php

require_once __DIR__ . '/../bootstrap.php';

use Controllers\KpiController;

$controller = new KpiController();

// The global $request variable is parsed in index.php
// Example: /api/kpis/123/entries -> $request = ['kpis', '123', 'entries']
$part1 = $request[1] ?? null;
$part2 = $request[2] ?? null;

// --- Sub-resource routing for /kpis/{id}/entries and /kpis/{id}/aggregate ---
if (is_numeric($part1) && in_array($part2, ['entries', 'aggregate'])) {
    $kpiId = (int)$part1;
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($part2 === 'entries') {
            $controller->listEntries($kpiId);
        } elseif ($part2 === 'aggregate') {
            $controller->getAggregate($kpiId);
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed for this resource']);
    }
    exit(); // Stop further processing
}

// --- Primary resource routing for /kpis ---
$id = is_numeric($part1) ? (int)$part1 : null;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($id) {
            $controller->getOne($id);
        } else {
             if ($part1 !== null) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid KPI ID specified']);
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
            echo json_encode(['success' => false, 'error' => 'A numeric KPI ID is required for updating']);
        }
        $controller->update($id);
        break;

    case 'DELETE':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'A numeric KPI ID is required for deletion']);
        }
        $controller->delete($id);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}
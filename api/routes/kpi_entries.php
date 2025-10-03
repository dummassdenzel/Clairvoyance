<?php

require_once __DIR__ . '/../bootstrap.php';

use Controllers\KpiEntryController;

$controller = new KpiEntryController();

// The global $request variable is parsed in index.php
// Example: /api/kpi_entries/123 -> $request = ['kpi_entries', '123']
$part1 = $request[1] ?? null;
$id = is_numeric($part1) ? (int)$part1 : null;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        // Differentiate between a JSON body and a file upload
        if (isset($_FILES['file'])) {
            $controller->uploadCsv();
        } else {
            $controller->create();
        }
        break;

    case 'PUT':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'A numeric KPI Entry ID is required for updating']);
        } else {
            $controller->update($id);
        }
        break;

    case 'DELETE':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'A numeric KPI Entry ID is required for deletion']);
        } else {
            $controller->delete($id);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}
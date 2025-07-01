<?php
require_once __DIR__ . '/../controllers/KpiEntryController.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'upload_csv') {
        (new KpiEntryController())->uploadCsv();
    } else {
        (new KpiEntryController())->create();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['kpi_id'])) {
        (new KpiEntryController())->listByKpiId();
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Missing kpi_id parameter']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
} 
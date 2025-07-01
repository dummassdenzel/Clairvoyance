<?php
require_once __DIR__ . '/../controllers/KpiEntryController.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'upload_csv') {
        (new KpiEntryController())->uploadCsv();
    } else {
        (new KpiEntryController())->create();
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
} 
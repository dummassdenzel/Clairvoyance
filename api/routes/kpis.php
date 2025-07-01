<?php
require_once __DIR__ . '/../controllers/KpiController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    (new KpiController())->create();
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
} 
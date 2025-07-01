<?php
require_once __DIR__ . '/../controllers/KpiController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    (new KpiController())->create();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    (new KpiController())->listAll();
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
} 
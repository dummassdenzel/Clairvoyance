<?php
class KpiEntryController {
    public function create() {
        require_once __DIR__ . '/../models/KpiEntry.php';
        session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'editor') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['kpi_id'], $data['date'], $data['value'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }
        $entry = new KpiEntry();
        $result = $entry->create($data['kpi_id'], $data['date'], $data['value']);
        if ($result['success']) {
            http_response_code(201);
            echo json_encode(['id' => $result['id']]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['error']]);
        }
    }

    public function uploadCsv() {
        require_once __DIR__ . '/../models/KpiEntry.php';
        session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'editor') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'CSV file is required']);
            return;
        }
        $kpiEntry = new KpiEntry();
        $result = $kpiEntry->bulkInsertFromCsv($_FILES['file']['tmp_name']);
        http_response_code(200);
        echo json_encode($result);
    }

    public function listByKpiId() {
        require_once __DIR__ . '/../models/KpiEntry.php';
        if (!isset($_GET['kpi_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing kpi_id parameter']);
            return;
        }
        $kpi_id = intval($_GET['kpi_id']);
        $entry = new KpiEntry();
        $result = $entry->listByKpiId($kpi_id);
        http_response_code(200);
        echo json_encode($result);
    }
} 
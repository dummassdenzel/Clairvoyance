<?php
class KpiController {
    public function create() {
        require_once __DIR__ . '/../models/Kpi.php';
        session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'editor') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['name'], $data['target'], $data['rag_red'], $data['rag_amber'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }
        $kpi = new Kpi();
        $result = $kpi->create($data['name'], $data['target'], $data['rag_red'], $data['rag_amber'], $_SESSION['user_id']);
        if ($result['success']) {
            http_response_code(201);
            echo json_encode(['id' => $result['id']]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['error']]);
        }
    }

    public function listAll() {
        require_once __DIR__ . '/../models/Kpi.php';
        $kpi = new Kpi();
        $result = $kpi->listAll();
        http_response_code(200);
        echo json_encode(['kpis' => $result]);
    }
} 
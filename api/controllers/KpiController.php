<?php
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../models/Kpi.php';
require_once __DIR__ . '/../models/KpiEntry.php';

class KpiController {
    public function create() {
        // Middleware handles auth, role checks, and starts the session.
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['name'], $data['target'], $data['rag_red'], $data['rag_amber'])) {
            Response::error('Missing required fields: name, target, rag_red, rag_amber.', null, 400);
            return;
        }

        $kpi = new Kpi();
        $userId = $_SESSION['user']['id'];
        $result = $kpi->create($data['name'], $data['target'], $data['rag_red'], $data['rag_amber'], $userId);

        if ($result['success']) {
            Response::success('KPI created successfully.', ['id' => $result['id']], 201);
        } else {
            Response::error($result['error'], null, 400);
        }
    }

    public function listAll() {
        // Middleware handles auth and role checks.
        $kpi = new Kpi();
        $result = $kpi->listAll();
        Response::success('KPIs retrieved successfully.', ['kpis' => $result]);
    }

    public function listEntries($kpiId)
    {
        $entry = new KpiEntry();
        $result = $entry->listByKpiId($kpiId);
        Response::success('Entries retrieved successfully.', $result);
    }
}
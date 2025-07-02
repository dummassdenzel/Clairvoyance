<?php
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../models/Kpi.php';
require_once __DIR__ . '/../models/KpiEntry.php';

class KpiController {
    public function update($kpiId) {
        // Middleware handles auth, role checks, and starts the session.
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $_SESSION['user']['id'];

        if (!isset($data['name'], $data['target'], $data['rag_red'], $data['rag_amber'])) {
            Response::error('Missing required fields.', null, 400);
            return;
        }

        $kpi = new Kpi();
        $result = $kpi->update($kpiId, $data, $userId);

        if ($result['success']) {
            Response::success('KPI updated successfully.', $result['data']);
        } else {
            Response::error($result['error'], null, $result['code'] ?? 400);
        }
    }

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

    public function delete($kpiId) {
        // Middleware handles auth, role checks, and starts the session.
        $kpi = new Kpi();
        $userId = $_SESSION['user']['id'];
        $result = $kpi->delete($kpiId, $userId);

        if ($result['success']) {
            // Use 204 No Content for successful deletions with no response body.
            Response::success('KPI deleted successfully.', null, 204);
        } else {
            // Use the error code from the model if provided, otherwise default.
            Response::error($result['error'], null, $result['code'] ?? 400);
        }
    }

    public function listAll() {
        // Middleware handles auth and role checks.
        $userId = $_SESSION['user']['id'];
        $kpi = new Kpi();
        $result = $kpi->listAll($userId);
        Response::success('KPIs retrieved successfully.', $result);
    }

    public function listEntries($kpiId)
    {
        $entry = new KpiEntry();
        $result = $entry->listByKpiId($kpiId);
        Response::success('Entries retrieved successfully.', $result);
    }
}
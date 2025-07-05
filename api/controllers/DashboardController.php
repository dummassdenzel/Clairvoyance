<?php
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../models/Dashboard.php';

class DashboardController {

    public function create() {
        // Middleware handles auth, role checks, and session start.
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['name'], $data['layout'])) {
            Response::error('Missing required fields: name, layout.', null, 400);
            return;
        }

        $dashboard = new Dashboard();
        $userId = $_SESSION['user']['id'];
        $result = $dashboard->create($data['name'], json_encode($data['layout']), $userId);

        if ($result['success']) {
            Response::success('Dashboard created successfully.', ['id' => $result['id']], 201);
        } else {
            Response::error($result['error'], null, 400);
        }
    }

    public function get($id) {
        // Middleware handles auth and role checks.
        if (!$id) {
            Response::error('Missing dashboard ID.', null, 400);
            return;
        }

        $dashboard = new Dashboard();
        $userId = $_SESSION['user']['id'];
        $userRole = $_SESSION['user']['role'];
        $result = $dashboard->getById($id, $userId, $userRole);

        if ($result['success']) {
            Response::success('Dashboard retrieved successfully.', ['dashboard' => $result['dashboard']]);
        } else {
            Response::error($result['error'], null, 403);
        }
    }

    public function listAll() {
        // Middleware handles auth and role checks.
        $dashboard = new Dashboard();
        $userId = $_SESSION['user']['id'];
        $userRole = $_SESSION['user']['role'];
        $result = $dashboard->listAll($userId, $userRole);
        Response::success('Dashboards retrieved successfully.', ['dashboards' => $result]);
    }

    public function update($id) {
        // Middleware handles auth and role checks.
        if (!$id) {
            Response::error('Missing dashboard ID.', null, 400);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data)) {
            Response::error('No update data provided.', null, 400);
            return;
        }

        // If layout data is present, it must be JSON encoded before saving.
        if (isset($data['layout'])) {
            $data['layout'] = json_encode($data['layout']);
        }

        $dashboard = new Dashboard();
        $userId = $_SESSION['user']['id'];
        $result = $dashboard->update($id, $data, $userId);

        if ($result['success']) {
            Response::success('Dashboard updated successfully.');
        } else {
            Response::error($result['error'], null, 400);
        }
    }

    public function delete($id) {
        // Middleware handles auth and role checks.
        if (!$id) {
            Response::error('Missing dashboard ID.', null, 400);
            return;
        }

        $dashboard = new Dashboard();
        $userId = $_SESSION['user']['id'];
        $result = $dashboard->delete($id, $userId);

        if ($result['success']) {
            Response::success('Dashboard deleted successfully.');
        } else {
            Response::error($result['error'], null, 400);
        }
    }

    public function assignViewer($dashboardId) {
        // Middleware handles auth and role checks.
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['user_id'])) {
            Response::error('Missing required field: user_id.', null, 400);
            return;
        }

        $dashboard = new Dashboard();
        $result = $dashboard->assignViewer($dashboardId, $data['user_id']);

        if ($result['success']) {
            Response::success('Viewer assigned successfully.');
        } else {
            Response::error($result['error'], null, 400);
        }
    }

    public function generateShareLink($dashboardId) {
        $userId = $_SESSION['user']['id'];
        $dashboard = new Dashboard();
        $result = $dashboard->createShareToken($dashboardId, $userId);

        if ($result['success']) {
            Response::success('Share token created successfully.', ['token' => $result['token']]);
        } else {
            Response::error($result['error'], null, 403);
        }
    }

    public function redeemShareLink($token) {
        $userId = $_SESSION['user']['id'];
        $dashboard = new Dashboard();
        $result = $dashboard->redeemToken($token, $userId);

        if ($result['success']) {
            Response::success($result['message'], ['dashboard_id' => $result['dashboard_id']]);
        } else {
            Response::error($result['error'], null, 400);
        }
    }

    public function getReportData($id) {
        // Middleware handles auth and role checks.

        $userId = $_SESSION['user']['id'];
        $userRole = $_SESSION['user']['role'];

        $dashboard = new Dashboard();
                $result = $dashboard->getReportData($id, $userId, $userRole);

        if ($result['success']) {
            Response::success('Report data retrieved successfully.', $result['report_data']);
        } else {
            Response::error($result['error'], null, 403);
        }
    }

    public function removeViewer($dashboardId, $userId) {
        // Middleware handles auth and role checks.
        $dashboard = new Dashboard();
        $result = $dashboard->removeViewer($dashboardId, $userId);

        if ($result['success']) {
            Response::success('Viewer removed successfully.');
        } else {
            Response::error($result['error'], null, 400);
        }
    }
} 
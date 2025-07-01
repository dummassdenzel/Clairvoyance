<?php
class DashboardController {
    public function create() {
        require_once __DIR__ . '/../models/Dashboard.php';
        session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'editor') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['name'], $data['widgets'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }
        $dashboard = new Dashboard();
        $result = $dashboard->create($data['name'], json_encode($data['widgets']), $_SESSION['user_id']);
        if ($result['success']) {
            http_response_code(201);
            echo json_encode(['id' => $result['id']]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['error']]);
        }
    }

    public function get() {
        require_once __DIR__ . '/../models/Dashboard.php';
        session_start();
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing dashboard id']);
            return;
        }
        $dashboard = new Dashboard();
        $result = $dashboard->getById($id, $_SESSION['user_id'], $_SESSION['role']);
        if ($result['success']) {
            http_response_code(200);
            echo json_encode($result['dashboard']);
        } else {
            http_response_code(403);
            echo json_encode(['error' => $result['error']]);
        }
    }

    public function assignViewer() {
        require_once __DIR__ . '/../models/Dashboard.php';
        session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'editor') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['dashboard_id'], $data['user_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing dashboard_id or user_id']);
            return;
        }
        $dashboard = new Dashboard();
        $result = $dashboard->assignViewer($data['dashboard_id'], $data['user_id']);
        if ($result['success']) {
            http_response_code(200);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['error']]);
        }
    }

    public function listAll() {
        require_once __DIR__ . '/../models/Dashboard.php';
        session_start();
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }
        $dashboard = new Dashboard();
        $result = $dashboard->listAll($_SESSION['user_id'], $_SESSION['role']);
        http_response_code(200);
        echo json_encode(['dashboards' => $result]);
    }

    public function removeViewer() {
        require_once __DIR__ . '/../models/Dashboard.php';
        session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'editor') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['dashboard_id'], $data['user_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing dashboard_id or user_id']);
            return;
        }
        $dashboard = new Dashboard();
        $result = $dashboard->removeViewer($data['dashboard_id'], $data['user_id']);
        if ($result['success']) {
            http_response_code(200);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['error']]);
        }
    }
} 
<?php
class UserController {
    public function register() {
        require_once __DIR__ . '/../models/User.php';
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['email'], $data['password'], $data['role'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }
        $user = new User();
        $result = $user->create($data['email'], $data['password'], $data['role']);
        if ($result['success']) {
            http_response_code(201);
            echo json_encode(['id' => $result['id']]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['error']]);
        }
    }

    public function login($data) {
        require_once __DIR__ . '/../models/User.php';
        if (!isset($data['email'], $data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing email or password']);
            return;
        }
        $user = new User();
        $result = $user->authenticate($data['email'], $data['password']);
        if ($result['success']) {
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['role'] = $result['user']['role'];
            http_response_code(200);
            echo json_encode(['success' => true, 'user' => $result['user']]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => $result['error']]);
        }
    }

    public function listAll() {
        require_once __DIR__ . '/../models/User.php';
        $user = new User();
        $result = $user->listAll();
        http_response_code(200);
        echo json_encode(['users' => $result]);
    }
} 
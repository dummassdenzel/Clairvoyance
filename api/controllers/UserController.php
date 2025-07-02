<?php
class UserController {
        public function register($data) {
        require_once __DIR__ . '/../models/User.php';

        if (!isset($data['email'], $data['password'])) {
            Response::error('Missing required fields: email, password.', null, 400);
            return;
        }

        // Default role to 'viewer' for public registration
        $role = $data['role'] ?? 'viewer';

        $user = new User();
        $result = $user->create($data['email'], $data['password'], $role);

        if ($result['success']) {
            Response::success('User registered successfully.', ['id' => $result['id']], 201);
        } else {
            Response::error($result['error'], null, 400);
        }
    }

    public function login($data) {
        require_once __DIR__ . '/../models/User.php';
        if (!isset($data['email'], $data['password'])) {
            Response::error('Missing email or password', null, 400);
            return;
        }
        $user = new User();
        $result = $user->authenticate($data['email'], $data['password']);
        if ($result['success']) {
            $_SESSION['user'] = [
                'id' => $result['user']['id'],
                'email' => $result['user']['email'],
                'role' => $result['user']['role']
            ];
            Response::success('Login successful', ['user' => $result['user']]);
        } else {
            Response::error($result['error'], null, 401);
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
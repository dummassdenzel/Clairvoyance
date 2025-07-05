<?php
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class AdminController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function listUsers() {
        // Auth is handled by the router/middleware
        $users = $this->userModel->listAll();
        Response::success('Users retrieved successfully', ['users' => $users]);
    }

    public function updateUserRole($id) {
        // Auth is handled by the router/middleware
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($id) || !isset($data['role'])) {
            Response::error('User ID and role are required.', null, 400);
            return;
        }

        // Prevent an admin from accidentally removing the last admin role
        if ($this->isLastAdmin($id, $data['role'])) {
            Response::error('Cannot remove the last admin.', null, 400);
            return;
        }

        $result = $this->userModel->updateUserRole($id, $data['role']);

        if ($result['success']) {
            Response::success('User role updated successfully.');
        } else {
            Response::error($result['error'], null, 400);
        }
    }

    public function deleteUser($id) {
        // Auth is handled by the router/middleware
        if (!isset($id)) {
            Response::error('User ID is required.', null, 400);
            return;
        }

        // Prevent an admin from deleting themselves or the last admin
        if ($id == $_SESSION['user']['id']) {
            Response::error('Admins cannot delete their own account.', null, 400);
            return;
        }

        if ($this->isLastAdmin($id)) {
            Response::error('Cannot delete the last admin.', null, 400);
            return;
        }

        $result = $this->userModel->deleteUser($id);

        if ($result['success']) {
            Response::success('User deleted successfully.');
        } else {
            Response::error($result['error'], null, 400);
        }
    }

    public function createUser() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['email']) || !isset($data['password']) || !isset($data['role'])) {
            Response::error('Email, password, and role are required.', 400);
            return;
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email format.', 400);
            return;
        }

        if ($this->userModel->findByEmail($data['email'])) {
            Response::error('User with this email already exists.', 409);
            return;
        }

        try {
            $result = $this->userModel->create($data['email'], $data['password'], $data['role']);
            if ($result['success']) {
                $newUser = $this->userModel->findById($result['id']);
                Response::success('User created successfully.', ['user' => $newUser], 201);
            } else {
                Response::error($result['error'] ?? 'Failed to create user.', 500);
            }
        } catch (\Exception $e) {
            Response::error('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Checks if the user being modified is the last admin.
     */
    private function isLastAdmin($userId, $newRole = null) {
        $allUsers = $this->userModel->listAll();
        $targetUser = null;
        $adminCount = 0;

        foreach ($allUsers as $user) {
            if ($user['role'] === 'admin') {
                $adminCount++;
            }
            if ($user['id'] == $userId) {
                $targetUser = $user;
            }
        }

        // If the target user is an admin and they are the only one
        if ($targetUser && $targetUser['role'] === 'admin' && $adminCount === 1) {
            // And if the action is to delete them or change their role
            if ($newRole === null || $newRole !== 'admin') {
                return true;
            }
        }

        return false;
    }
}

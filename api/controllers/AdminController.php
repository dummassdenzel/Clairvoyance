<?php

namespace Controllers;

use Core\BaseController;
use Services\UserService;
use Services\AuthService;

class AdminController extends BaseController {
    private UserService $userService;
    private AuthService $authService;

    public function __construct() {
        parent::__construct();
        $this->userService = $this->getService(UserService::class);
        $this->authService = $this->getService(AuthService::class);
    }

    public function listUsers() {
        try {
            $this->authService->requireRole('admin');
            $users = $this->userService->listAllUsers();
            $this->jsonResponse(['success' => true, 'message' => 'Users retrieved successfully', 'users' => $users]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function updateUserRole($id) {
        try {
            $this->authService->requireRole('admin');
            
            $data = $this->getRequestData();

        if (!isset($id) || !isset($data['role'])) {
                $this->jsonResponse(['success' => false, 'error' => 'User ID and role are required.'], 400);
            return;
        }

            $result = $this->userService->updateUserRole((int)$id, $data['role']);

        if ($result['success']) {
                $this->jsonResponse(['success' => true, 'message' => $result['message']]);
        } else {
                $this->jsonResponse(['success' => false, 'error' => $result['error']], 400);
            }
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function deleteUser($id) {
        try {
            $this->authService->requireRole('admin');
            
        if (!isset($id)) {
                $this->jsonResponse(['success' => false, 'error' => 'User ID is required.'], 400);
            return;
        }

            // Prevent an admin from deleting themselves
            $currentUser = $this->authService->getCurrentUser();
            if ($id == $currentUser['id']) {
                $this->jsonResponse(['success' => false, 'error' => 'Admins cannot delete their own account.'], 400);
            return;
        }

            $result = $this->userService->deleteUser((int)$id);

        if ($result['success']) {
                $this->jsonResponse(['success' => true, 'message' => $result['message']]);
        } else {
                $this->jsonResponse(['success' => false, 'error' => $result['error']], 400);
            }
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function createUser() {
        try {
            $this->authService->requireRole('admin');
            
            $data = $this->getRequestData();
            $result = $this->userService->createUser($data);

            if ($result['success']) {
                $this->jsonResponse(['success' => true, 'message' => 'User created successfully.', 'user' => $result['user']], 201);
            } else {
                $this->jsonResponse(['success' => false, 'error' => $result['error']], 400);
            }
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }
}

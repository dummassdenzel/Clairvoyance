<?php

namespace Services;

use Models\User;

class AuthService
{
    private User $userModel;

    public function __construct(User $userModel)
    {
        $this->userModel = $userModel;
    }

    public function login(string $email, string $password): array
    {
        $result = $this->userModel->authenticate($email, $password);
        
        if ($result['success']) {
            $_SESSION['user'] = $result['user'];
            return ['success' => true, 'user' => $result['user']];
        }
        
        return ['success' => false, 'error' => $result['error']];
    }

    public function logout(): void
    {
        session_destroy();
    }

    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user']);
    }

    public function getCurrentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public function hasRole(string $role): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        return $_SESSION['user']['role'] === $role || $_SESSION['user']['role'] === 'admin';
    }

    public function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Authentication required', 401);
        }
    }

    public function requireRole(string $role): void
    {
        $this->requireAuth();
        
        if (!$this->hasRole($role)) {
            throw new \Exception('Insufficient permissions', 403);
        }
    }
}

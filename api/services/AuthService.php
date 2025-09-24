<?php

namespace Services;

use Models\User;

class AuthService
{
    private User $userModel;
    private \PDO $db;

    public function __construct(User $userModel, \PDO $db)
    {
        $this->userModel = $userModel;
        $this->db = $db;
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
        
        // Admin has all permissions
        if ($_SESSION['user']['role'] === 'admin') {
            return true;
        }
        
        // Editor is the universal role for non-admin users
        if ($_SESSION['user']['role'] === 'editor') {
            return true; // Editor can do everything except admin functions
        }
        
        return false;
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

    /**
     * Check if user has specific permission level for a dashboard
     */
    public function hasDashboardPermission(int $dashboardId, string $permissionLevel = 'viewer'): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        $userId = $_SESSION['user']['id'];
        
        // Admin has all permissions
        if ($_SESSION['user']['role'] === 'admin') {
            return true;
        }

        // Check dashboard ownership first
        if ($this->isDashboardOwner($dashboardId, $userId)) {
            return true; // Owners have all permissions
        }

        // Check dashboard access permissions
        return $this->checkDashboardAccess($dashboardId, $userId, $permissionLevel);
    }

    /**
     * Check if user is the owner of a dashboard
     */
    public function isDashboardOwner(int $dashboardId, int $userId): bool
    {
        try {
            $stmt = $this->db->prepare('
                SELECT 1 FROM dashboards 
                WHERE id = ? AND user_id = ?
            ');
            $stmt->execute([$dashboardId, $userId]);
            return $stmt->fetch() !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check dashboard access permissions
     */
    private function checkDashboardAccess(int $dashboardId, int $userId, string $requiredPermission): bool
    {
        try {
            // Define permission hierarchy
            $permissionLevels = ['viewer' => 1, 'editor' => 2, 'owner' => 3];
            $requiredLevel = $permissionLevels[$requiredPermission] ?? 1;
            
            $stmt = $this->db->prepare('
                SELECT permission_level FROM dashboard_access 
                WHERE dashboard_id = ? AND user_id = ?
            ');
            $stmt->execute([$dashboardId, $userId]);
            $result = $stmt->fetch();
            
            if (!$result) {
                return false;
            }
            
            $userLevel = $permissionLevels[$result['permission_level']] ?? 0;
            return $userLevel >= $requiredLevel;
            
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Require specific dashboard permission
     */
    public function requireDashboardPermission(int $dashboardId, string $permissionLevel = 'viewer'): void
    {
        $this->requireAuth();
        
        if (!$this->hasDashboardPermission($dashboardId, $permissionLevel)) {
            throw new \Exception('Insufficient dashboard permissions', 403);
        }
    }

}

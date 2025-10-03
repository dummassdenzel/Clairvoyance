<?php

namespace Models;

use PDO;
use PDOException;

class Dashboard
{
    private PDO $db;
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    
    // CRUD Operations
    public function create(string $name, string $description, string $layout, int $userId): array
    {
        try {
            $this->db->beginTransaction();
            
            // Create the dashboard
            $stmt = $this->db->prepare('INSERT INTO dashboards (name, description, layout, user_id) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $description, $layout, $userId]);
            $dashboardId = $this->db->lastInsertId();
            
            // Add owner to dashboard_access table
            $accessStmt = $this->db->prepare('INSERT INTO dashboard_access (dashboard_id, user_id, permission_level) VALUES (?, ?, ?)');
            $accessStmt->execute([$dashboardId, $userId, 'owner']);
            
            $this->db->commit();
            return ['success' => true, 'id' => $dashboardId];
        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM dashboards WHERE id = ?');
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function findByUserId(int $userId): array
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM dashboards WHERE user_id = ? ORDER BY created_at DESC');
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function findAll(): array
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM dashboards ORDER BY created_at DESC');
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function update(int $id, array $data): bool
    {
        try {
            $fields = [];
            $params = [];
            
            foreach ($data as $key => $value) {
                if (in_array($key, ['name', 'description', 'layout'])) {
                    $fields[] = "$key = ?";
                    $params[] = is_array($value) ? json_encode($value) : $value;
                }
            }

            if (empty($fields)) {
                return false;
            }

            $params[] = $id;
            $sql = 'UPDATE dashboards SET ' . implode(', ', $fields) . ', updated_at = CURRENT_TIMESTAMP WHERE id = ?';

            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function delete(int $id): bool
    {
        try {
            $this->db->beginTransaction();
            
            // Delete related access records first
            $stmt = $this->db->prepare('DELETE FROM dashboard_access WHERE dashboard_id = ?');
            $stmt->execute([$id]);
            
            // Delete related share tokens
            $stmt = $this->db->prepare('DELETE FROM dashboard_share_tokens WHERE dashboard_id = ?');
            $stmt->execute([$id]);
            
            // Delete the dashboard
            $stmt = $this->db->prepare('DELETE FROM dashboards WHERE id = ?');
            $stmt->execute([$id]);
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    // Dashboard Access Management (Permission-Based System)
    public function addUserAccess(int $dashboardId, int $userId, string $permissionLevel = 'viewer'): bool
    {
        try {
            // Check if access already exists
            if ($this->hasUserAccess($dashboardId, $userId)) {
                // Update existing access
                $stmt = $this->db->prepare('
                    UPDATE dashboard_access 
                    SET permission_level = ?, created_at = CURRENT_TIMESTAMP 
                    WHERE dashboard_id = ? AND user_id = ?
                ');
                return $stmt->execute([$permissionLevel, $dashboardId, $userId]);
            } else {
                // Add new access
                $stmt = $this->db->prepare('
                    INSERT INTO dashboard_access (dashboard_id, user_id, permission_level) 
                    VALUES (?, ?, ?)
                ');
                return $stmt->execute([$dashboardId, $userId, $permissionLevel]);
            }
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function removeUserAccess(int $dashboardId, int $userId): bool
    {
        try {
            $stmt = $this->db->prepare('DELETE FROM dashboard_access WHERE dashboard_id = ? AND user_id = ?');
            return $stmt->execute([$dashboardId, $userId]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function getDashboardUsers(int $dashboardId): array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT u.id, u.email, u.role, da.permission_level, da.created_at as access_granted_at 
                FROM dashboard_access da 
                JOIN users u ON da.user_id = u.id 
                WHERE da.dashboard_id = ? 
                ORDER BY da.permission_level DESC, da.created_at DESC
            ');
            $stmt->execute([$dashboardId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function hasUserAccess(int $dashboardId, int $userId): bool
    {
        try {
            $stmt = $this->db->prepare('
                SELECT 1 FROM dashboard_access 
                WHERE dashboard_id = ? AND user_id = ? 
                LIMIT 1
            ');
            $stmt->execute([$dashboardId, $userId]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getUserPermissionLevel(int $dashboardId, int $userId): ?string
    {
        try {
            $stmt = $this->db->prepare('
                SELECT permission_level FROM dashboard_access 
                WHERE dashboard_id = ? AND user_id = ?
            ');
            $stmt->execute([$dashboardId, $userId]);
            $result = $stmt->fetch();
            return $result ? $result['permission_level'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    // Legacy methods for backward compatibility (deprecated)
    public function addViewer(int $dashboardId, int $userId): bool
    {
        return $this->addUserAccess($dashboardId, $userId, 'viewer');
    }
    
    public function removeViewer(int $dashboardId, int $userId): bool
    {
        return $this->removeUserAccess($dashboardId, $userId);
    }
    
    public function getViewers(int $dashboardId): array
    {
        return $this->getDashboardUsers($dashboardId);
    }
    
    public function hasViewerAccess(int $dashboardId, int $userId): bool
    {
        return $this->hasUserAccess($dashboardId, $userId);
    }
    
    public function getDashboardsByViewer(int $userId): array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT d.*, u.email as owner_email, da.permission_level
                FROM dashboards d 
                JOIN dashboard_access da ON d.id = da.dashboard_id 
                JOIN users u ON d.user_id = u.id 
                WHERE da.user_id = ? 
                ORDER BY d.created_at DESC
            ');
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getDashboardsByUser(int $userId): array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT DISTINCT d.*, u.email as owner_email, 
                       COALESCE(da.permission_level, 
                                CASE WHEN d.user_id = ? THEN "owner" ELSE "viewer" END) as permission_level
                FROM dashboards d 
                JOIN users u ON d.user_id = u.id 
                LEFT JOIN dashboard_access da ON d.id = da.dashboard_id AND da.user_id = ?
                WHERE d.user_id = ? OR da.user_id = ?
                ORDER BY d.created_at DESC
            ');
            $stmt->execute([$userId, $userId, $userId, $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Data Validation Methods
    public function validateLayout(string $layout): bool
    {
        $decoded = json_decode($layout, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded);
    }
    
    public function validateName(string $name): bool
    {
        return !empty(trim($name)) && strlen($name) <= 255;
    }
    
    public function validateDescription(string $description): bool
    {
        return strlen($description) <= 65535; // TEXT field limit
    }
    
    // Utility Methods
    public function getOwner(int $dashboardId): ?array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT u.id, u.email, u.role 
                FROM dashboards d 
                JOIN users u ON d.user_id = u.id 
                WHERE d.id = ?
            ');
            $stmt->execute([$dashboardId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function exists(int $id): bool
    {
        try {
            $stmt = $this->db->prepare('SELECT 1 FROM dashboards WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function getStats(int $userId): array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT 
                    COUNT(*) as total_dashboards,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent_dashboards
                FROM dashboards 
                WHERE user_id = ?
            ');
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get user's access information for a specific dashboard
     */
    public function getUserAccess(int $dashboardId, int $userId): ?array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT da.*, u.email 
                FROM dashboard_access da 
                JOIN users u ON da.user_id = u.id 
                WHERE da.dashboard_id = ? AND da.user_id = ?
            ');
            $stmt->execute([$dashboardId, $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }
}
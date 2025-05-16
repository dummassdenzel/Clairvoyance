<?php

require_once __DIR__ . '/../config/database.php';

class Dashboard
{
    private $pdo;
    
    public function __construct()
    {
        $conn = new Connection();
        $this->pdo = $conn->connect();
    }
    
    /**
     * Get all dashboards for a user
     * 
     * @param int $userId User ID
     * @return array Array of dashboards
     */
    public function getAllForUser($userId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM dashboards
                WHERE user_id = :user_id
                ORDER BY is_default DESC, created_at DESC
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching dashboards: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get a dashboard by ID
     * 
     * @param int $id Dashboard ID
     * @return array|null Dashboard data or null if not found
     */
    public function getById($id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT d.*, u.username
                FROM dashboards d
                LEFT JOIN users u ON d.user_id = u.id
                WHERE d.id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching dashboard by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get dashboard with widgets
     * 
     * @param int $id Dashboard ID
     * @return array|null Dashboard data with widgets or null if not found
     */
    public function getWithWidgets($id)
    {
        try {
            // Get dashboard
            $dashboard = $this->getById($id);
            if (!$dashboard) {
                return null;
            }
            
            // Get widgets for dashboard
            $stmt = $this->pdo->prepare("
                SELECT w.*, k.name as kpi_name, k.unit as kpi_unit
                FROM widgets w
                LEFT JOIN kpis k ON w.kpi_id = k.id
                WHERE w.dashboard_id = :dashboard_id
                ORDER BY w.position_y, w.position_x
            ");
            $stmt->bindParam(':dashboard_id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $widgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $dashboard['widgets'] = $widgets;
            return $dashboard;
        } catch (PDOException $e) {
            error_log("Error fetching dashboard with widgets: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create a new dashboard
     * 
     * @param array $data Dashboard data
     * @return array|bool Created dashboard ID or false on failure
     */
    public function create($data)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO dashboards (user_id, name, description, is_default)
                VALUES (:user_id, :name, :description, :is_default)
            ");
            
            $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindParam(':description', $data['description'] ?? null, PDO::PARAM_STR);
            $isDefault = isset($data['is_default']) ? ($data['is_default'] ? 1 : 0) : 0;
            $stmt->bindParam(':is_default', $isDefault, PDO::PARAM_INT);
            
            $result = $stmt->execute();
            if ($result) {
                $id = $this->pdo->lastInsertId();
                return $this->getById($id);
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error creating dashboard: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a dashboard
     * 
     * @param int $id Dashboard ID
     * @param array $data Updated dashboard data
     * @return bool Success status
     */
    public function update($id, $data)
    {
        try {
            $setFields = [];
            $params = [':id' => $id];
            
            // Build dynamic SET clause
            foreach ($data as $key => $value) {
                if (in_array($key, ['name', 'description', 'is_default'])) {
                    if ($key === 'is_default') {
                        $value = $value ? 1 : 0;
                    }
                    $setFields[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
            }
            
            if (empty($setFields)) {
                return false; // Nothing to update
            }
            
            $sql = "UPDATE dashboards SET " . implode(', ', $setFields) . " WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating dashboard: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a dashboard
     * 
     * @param int $id Dashboard ID
     * @return bool Success status
     */
    public function delete($id)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM dashboards WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting dashboard: " . $e->getMessage());
            return false;
        }
    }
} 
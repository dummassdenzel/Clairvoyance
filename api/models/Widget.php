<?php

require_once __DIR__ . '/../config/database.php';

class Widget
{
    private $pdo;
    
    public function __construct()
    {
        $conn = new Connection();
        $this->pdo = $conn->connect();
    }
    
    /**
     * Get all widgets for a user
     * 
     * @param int $userId User ID
     * @return array Array of widgets
     */
    public function getAllForUser($userId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT w.*, d.name as dashboard_name, k.name as kpi_name, k.unit as kpi_unit
                FROM widgets w
                JOIN dashboards d ON w.dashboard_id = d.id
                JOIN kpis k ON w.kpi_id = k.id
                WHERE d.user_id = :user_id
                ORDER BY d.name, w.position_y, w.position_x
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching widgets: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all widgets for a dashboard
     * 
     * @param int $dashboardId Dashboard ID
     * @return array Array of widgets
     */
    public function getAllForDashboard($dashboardId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT w.*, k.name as kpi_name, k.unit as kpi_unit
                FROM widgets w
                JOIN kpis k ON w.kpi_id = k.id
                WHERE w.dashboard_id = :dashboard_id
                ORDER BY w.position_y, w.position_x
            ");
            $stmt->bindParam(':dashboard_id', $dashboardId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching widgets for dashboard: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get a widget by ID
     * 
     * @param int $id Widget ID
     * @return array|null Widget data or null if not found
     */
    public function getById($id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT w.*, d.name as dashboard_name, k.name as kpi_name, k.unit as kpi_unit
                FROM widgets w
                JOIN dashboards d ON w.dashboard_id = d.id
                JOIN kpis k ON w.kpi_id = k.id
                WHERE w.id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching widget by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if a user has access to a widget
     * 
     * @param int $widgetId Widget ID
     * @param int $userId User ID
     * @return bool True if user has access, false otherwise
     */
    public function userHasAccess($widgetId, $userId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM widgets w
                JOIN dashboards d ON w.dashboard_id = d.id
                WHERE w.id = :widget_id AND d.user_id = :user_id
            ");
            $stmt->bindParam(':widget_id', $widgetId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Error checking widget access: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a new widget
     * 
     * @param array $data Widget data
     * @return array|bool Created widget data or false on failure
     */
    public function create($data)
    {
        try {
            // Prepare settings JSON if provided
            $settings = isset($data['settings']) ? json_encode($data['settings']) : null;
            
            $stmt = $this->pdo->prepare("
                INSERT INTO widgets (
                    dashboard_id, kpi_id, title, widget_type, 
                    position_x, position_y, width, height, settings
                )
                VALUES (
                    :dashboard_id, :kpi_id, :title, :widget_type,
                    :position_x, :position_y, :width, :height, :settings
                )
            ");
            
            // Prepare variables for binding
            $dashboardId = $data['dashboard_id'];
            $kpiId = $data['kpi_id'];
            $title = $data['title'];
            $widgetType = $data['widget_type'];
            $positionX = isset($data['position_x']) ? $data['position_x'] : 0;
            $positionY = isset($data['position_y']) ? $data['position_y'] : 0;
            $width = isset($data['width']) ? $data['width'] : 1;
            $height = isset($data['height']) ? $data['height'] : 1;
            
            $stmt->bindParam(':dashboard_id', $dashboardId, PDO::PARAM_INT);
            $stmt->bindParam(':kpi_id', $kpiId, PDO::PARAM_INT);
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':widget_type', $widgetType, PDO::PARAM_STR);
            $stmt->bindParam(':position_x', $positionX, PDO::PARAM_INT);
            $stmt->bindParam(':position_y', $positionY, PDO::PARAM_INT);
            $stmt->bindParam(':width', $width, PDO::PARAM_INT);
            $stmt->bindParam(':height', $height, PDO::PARAM_INT);
            $stmt->bindParam(':settings', $settings, PDO::PARAM_STR);
            
            $result = $stmt->execute();
            if ($result) {
                $id = $this->pdo->lastInsertId();
                return $this->getById($id);
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error creating widget: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a widget
     * 
     * @param int $id Widget ID
     * @param array $data Updated widget data
     * @return bool Success status
     */
    public function update($id, $data)
    {
        try {
            $setFields = [];
            $params = [':id' => $id];
            
            // Build dynamic SET clause
            foreach ($data as $key => $value) {
                if (in_array($key, ['dashboard_id', 'kpi_id', 'title', 'widget_type', 'position_x', 'position_y', 'width', 'height'])) {
                    $setFields[] = "$key = :$key";
                    $params[":$key"] = $value;
                } elseif ($key === 'settings') {
                    $setFields[] = "settings = :settings";
                    $params[":settings"] = json_encode($value);
                }
            }
            
            if (empty($setFields)) {
                return false; // Nothing to update
            }
            
            $sql = "UPDATE widgets SET " . implode(', ', $setFields) . " WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating widget: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a widget
     * 
     * @param int $id Widget ID
     * @return bool Success status
     */
    public function delete($id)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM widgets WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting widget: " . $e->getMessage());
            return false;
        }
    }
}

<?php

require_once __DIR__ . '/../config/database.php';

class Kpi
{
    private $pdo;
    
    public function __construct()
    {
        $conn = new Connection();
        $this->pdo = $conn->connect();
    }
    
    /**
     * Get all KPIs
     * 
     * @return array Array of KPIs
     */
    public function getAll()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT k.*, c.name as category_name, u.username
                FROM kpis k
                LEFT JOIN categories c ON k.category_id = c.id
                LEFT JOIN users u ON k.user_id = u.id
                ORDER BY k.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching KPIs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get a KPI by ID
     * 
     * @param int $id KPI ID
     * @return array|null KPI data or null if not found
     */
    public function getById($id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT k.*, c.name as category_name, u.username
                FROM kpis k
                LEFT JOIN categories c ON k.category_id = c.id
                LEFT JOIN users u ON k.user_id = u.id
                WHERE k.id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching KPI by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create a new KPI
     * 
     * @param array $data KPI data
     * @return array|bool Created KPI ID or false on failure
     */
    public function create($data)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO kpis (user_id, category_id, name, unit, target)
                VALUES (:user_id, :category_id, :name, :unit, :target)
            ");
            
            // Prepare variables for binding (can't use expressions directly with bindParam)
            $userId = $data['user_id'];
            $categoryId = $data['category_id'];
            $name = $data['name'];
            $unit = isset($data['unit']) ? $data['unit'] : null;
            $target = isset($data['target']) ? $data['target'] : null;
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':unit', $unit, PDO::PARAM_STR);
            $stmt->bindParam(':target', $target, PDO::PARAM_STR);
            
            $result = $stmt->execute();
            if ($result) {
                $id = $this->pdo->lastInsertId();
                return $this->getById($id);
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error creating KPI: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a KPI
     * 
     * @param int $id KPI ID
     * @param array $data Updated KPI data
     * @return bool Success status
     */
    public function update($id, $data)
    {
        try {
            $setFields = [];
            $params = [':id' => $id];
            
            // Build dynamic SET clause
            foreach ($data as $key => $value) {
                if (in_array($key, ['name', 'unit', 'target', 'category_id'])) {
                    $setFields[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
            }
            
            if (empty($setFields)) {
                return false; // Nothing to update
            }
            
            $sql = "UPDATE kpis SET " . implode(', ', $setFields) . " WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating KPI: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a KPI
     * 
     * @param int $id KPI ID
     * @return bool Success status
     */
    public function delete($id)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM kpis WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting KPI: " . $e->getMessage());
            return false;
        }
    }
} 
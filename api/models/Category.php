<?php

require_once __DIR__ . '/../config/database.php';

class Category
{
    private $pdo;
    
    public function __construct()
    {
        $conn = new Connection();
        $this->pdo = $conn->connect();
    }
    
    /**
     * Get all categories
     * 
     * @return array Array of categories
     */
    public function getAll()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.*, u.username
                FROM categories c
                LEFT JOIN users u ON c.user_id = u.id
                ORDER BY c.name ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching categories: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get a category by ID
     * 
     * @param int $id Category ID
     * @return array|null Category data or null if not found
     */
    public function getById($id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.*, u.username
                FROM categories c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching category by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create a new category
     * 
     * @param array $data Category data
     * @return array|bool Created category or false on failure
     */
    public function create($data)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO categories (name, user_id)
                VALUES (:name, :user_id)
            ");
            
            $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
            
            $result = $stmt->execute();
            if ($result) {
                $id = $this->pdo->lastInsertId();
                return $this->getById($id);
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error creating category: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a category
     * 
     * @param int $id Category ID
     * @param array $data Updated category data
     * @return bool Success status
     */
    public function update($id, $data)
    {
        try {
            $setFields = [];
            $params = [':id' => $id];
            
            // Build dynamic SET clause
            foreach ($data as $key => $value) {
                if (in_array($key, ['name'])) {
                    $setFields[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
            }
            
            if (empty($setFields)) {
                return false; // Nothing to update
            }
            
            $sql = "UPDATE categories SET " . implode(', ', $setFields) . " WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating category: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a category
     * 
     * @param int $id Category ID
     * @return bool Success status
     */
    public function delete($id)
    {
        try {
            // Check if category is used by any KPIs
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM kpis WHERE category_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                // Category is in use, cannot delete
                return false;
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM categories WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting category: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get KPIs for a specific category
     * 
     * @param int $id Category ID
     * @return array Array of KPIs
     */
    public function getKpis($id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT k.*, u.username
                FROM kpis k
                LEFT JOIN users u ON k.user_id = u.id
                WHERE k.category_id = :id
                ORDER BY k.created_at DESC
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching KPIs for category: " . $e->getMessage());
            return [];
        }
    }
}

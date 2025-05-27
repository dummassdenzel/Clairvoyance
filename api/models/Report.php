<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Report model for handling report operations
 */
class Report
{
    private $pdo;
    
    public function __construct()
    {
        $conn = new Connection();
        $this->pdo = $conn->connect();
    }
    
    /**
     * Get all reports for a user
     * 
     * @param int $userId User ID
     * @return array List of reports
     */
    public function getAllByUser($userId)
    {
        $sql = "SELECT * FROM reports WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get a report by ID
     * 
     * @param int $id Report ID
     * @return array|false Report data or false if not found
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM reports WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create a new report
     * 
     * @param array $data Report data
     * @return int|false The new report ID or false on failure
     */
    public function create($data)
    {
        $sql = "INSERT INTO reports (user_id, dashboard_id, name, format, file_path, created_at) 
                VALUES (:user_id, :dashboard_id, :name, :format, :file_path, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':dashboard_id', $data['dashboard_id'], PDO::PARAM_INT);
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindParam(':format', $data['format'], PDO::PARAM_STR);
        $stmt->bindParam(':file_path', $data['file_path'], PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Delete a report
     * 
     * @param int $id Report ID
     * @return bool Success status
     */
    public function delete($id)
    {
        $sql = "DELETE FROM reports WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
}

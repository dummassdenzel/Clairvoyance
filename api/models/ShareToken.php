<?php

namespace Models;

use PDO;
use PDOException;

class ShareToken
{
    private PDO $db;
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    
    // CRUD Operations
    public function create(int $dashboardId, string $token, string $expiresAt): array
    {
        try {
            $stmt = $this->db->prepare('INSERT INTO dashboard_share_tokens (dashboard_id, token, expires_at) VALUES (?, ?, ?)');
            $stmt->execute([$dashboardId, $token, $expiresAt]);
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function findByToken(string $token): ?array
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM dashboard_share_tokens WHERE token = ?');
            $stmt->execute([$token]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function findByDashboardId(int $dashboardId): array
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM dashboard_share_tokens WHERE dashboard_id = ? ORDER BY created_at DESC');
            $stmt->execute([$dashboardId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->db->prepare('DELETE FROM dashboard_share_tokens WHERE id = ?');
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function deleteByToken(string $token): bool
    {
        try {
            $stmt = $this->db->prepare('DELETE FROM dashboard_share_tokens WHERE token = ?');
            return $stmt->execute([$token]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function deleteExpired(): int
    {
        try {
            $stmt = $this->db->prepare('DELETE FROM dashboard_share_tokens WHERE expires_at < NOW()');
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    // Validation Methods
    public function isValid(string $token): bool
    {
        try {
            $stmt = $this->db->prepare('SELECT 1 FROM dashboard_share_tokens WHERE token = ? AND expires_at > NOW() LIMIT 1');
            $stmt->execute([$token]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function isExpired(string $token): bool
    {
        try {
            $stmt = $this->db->prepare('SELECT expires_at FROM dashboard_share_tokens WHERE token = ? LIMIT 1');
            $stmt->execute([$token]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return true; // Token doesn't exist, consider it expired
            }
            
            return new \DateTime($result['expires_at']) < new \DateTime();
        } catch (PDOException $e) {
            return true;
        }
    }
    
    // Utility Methods
    public function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
    
    public function generateExpirationDate(int $days = 7): string
    {
        return date('Y-m-d H:i:s', strtotime("+{$days} days"));
    }
    
    public function getStats(int $dashboardId): array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT 
                    COUNT(*) as total_tokens,
                    COUNT(CASE WHEN expires_at > NOW() THEN 1 END) as active_tokens,
                    COUNT(CASE WHEN expires_at <= NOW() THEN 1 END) as expired_tokens
                FROM dashboard_share_tokens 
                WHERE dashboard_id = ?
            ');
            $stmt->execute([$dashboardId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }
}
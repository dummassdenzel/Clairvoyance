<?php

namespace Models;

use PDO;
use PDOException;

class User
{
    private PDO $db;
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    
    // CRUD Operations
    public function create(string $email, string $password, string $role): array
    {
        try {
            // Hash the password before storing
            $hashedPassword = $this->hashPassword($password);
            
            $stmt = $this->db->prepare('INSERT INTO users (email, password, role) VALUES (?, ?, ?)');
            $stmt->execute([$email, $hashedPassword, $role]);
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare('SELECT id, email, role, created_at, updated_at FROM users WHERE id = ?');
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function findByEmail(string $email): ?array
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function authenticate(string $email, string $password): array
    {
        try {
            $stmt = $this->db->prepare('SELECT id, email, password, role FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($password, $user['password'])) {
                return ['success' => false, 'error' => 'Invalid credentials'];
            }
            
            unset($user['password']);
            return ['success' => true, 'user' => $user];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function updateRole(int $id, string $role): array
    {
        try {
            $stmt = $this->db->prepare('UPDATE users SET role = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
            $success = $stmt->execute([$role, $id]);
            
            if ($success) {
                return ['success' => true, 'message' => 'Role updated successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to update role'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function delete(int $id): array
    {
        try {
            $stmt = $this->db->prepare('DELETE FROM users WHERE id = ?');
            $success = $stmt->execute([$id]);
            
            if ($success) {
                return ['success' => true, 'message' => 'User deleted successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to delete user'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function listAll(): array
    {
        try {
            $stmt = $this->db->prepare('SELECT id, email, role, created_at, updated_at FROM users ORDER BY created_at DESC');
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Data validation methods
    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public function validateRole(string $role): bool
    {
        return in_array($role, ['viewer', 'editor', 'admin']);
    }
    
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
} 
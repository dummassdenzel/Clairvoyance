<?php

require_once __DIR__ . '/../config/database.php';

class User
{
    private $pdo;
    
    public function __construct()
    {
        $conn = new Connection();
        $this->pdo = $conn->connect();
    }
    
    /**
     * Find a user by ID
     * 
     * @param int $id User ID
     * @return array|null User data or null if not found
     */
    public function findById($id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error finding user by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Find a user by username
     * 
     * @param string $username Username
     * @return array|null User data or null if not found
     */
    public function findByUsername($username)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error finding user by username: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Find a user by email
     * 
     * @param string $email Email
     * @return array|null User data or null if not found
     */
    public function findByEmail($email)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error finding user by email: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create a new user
     * 
     * @param array $data User data
     * @return array|bool Created user data or false on failure
     */
    public function create($data)
    {
        error_log("Attempting to create user: " . json_encode($data));
        
        try {
            // Debug the SQL statement
            $sql = "INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, :role)";
            error_log("SQL: " . $sql);
            
            $stmt = $this->pdo->prepare($sql);
            
            // Debug bound parameters
            error_log("Parameters: username=" . $data['username'] . ", email=" . $data['email'] . ", role=" . $data['role']);
            
            $stmt->bindParam(':username', $data['username'], PDO::PARAM_STR);
            $stmt->bindParam(':email', $data['email'], PDO::PARAM_STR);
            $stmt->bindParam(':password_hash', $data['password_hash'], PDO::PARAM_STR);
            $stmt->bindParam(':role', $data['role'], PDO::PARAM_STR);
            
            $result = $stmt->execute();
            error_log("Execute result: " . ($result ? "true" : "false"));
            
            if ($result) {
                $id = $this->pdo->lastInsertId();
                error_log("New user ID: " . $id);
                return $this->findById($id);
            }
            
            error_log("Failed to create user, execute returned false");
            return false;
        } catch (PDOException $e) {
            error_log("PDO Exception creating user: " . $e->getMessage());
            error_log("Error code: " . $e->getCode());
            return false;
        } catch (Exception $e) {
            error_log("General Exception creating user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a user
     * 
     * @param int $id User ID
     * @param array $data Updated user data
     * @return bool Success status
     */
    public function update($id, $data)
    {
        try {
            $setFields = [];
            $params = [':id' => $id];
            
            // Build dynamic SET clause
            foreach ($data as $key => $value) {
                if (in_array($key, ['username', 'email', 'password_hash', 'role'])) {
                    $setFields[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
            }
            
            if (empty($setFields)) {
                return false; // Nothing to update
            }
            
            $sql = "UPDATE users SET " . implode(', ', $setFields) . " WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a user
     * 
     * @param int $id User ID
     * @return bool Success status
     */
    public function delete($id)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting user: " . $e->getMessage());
            return false;
        }
    }
} 
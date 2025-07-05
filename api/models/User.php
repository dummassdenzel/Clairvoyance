<?php
class User {
    private $db;
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = (new Connection())->connect();
    }
    public function create($email, $password, $role) {
        try {
            $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Email already exists'];
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare('INSERT INTO users (email, password, role) VALUES (?, ?, ?)');
            $stmt->execute([$email, $hash, $role]);
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    public function authenticate($email, $password) {
        try {
            $stmt = $this->db->prepare('SELECT id, email, password, role FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if (!$user) {
                return ['success' => false, 'error' => 'Invalid email or password'];
            }
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'error' => 'Invalid email or password'];
            }
            unset($user['password']);
            return ['success' => true, 'user' => $user];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    public function updateRoleByEmail($email, $role) {
        try {
            // First, check if the user exists
            $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                return ['success' => false, 'error' => 'User not found'];
            }

            // Update the user's role
            $stmt = $this->db->prepare('UPDATE users SET role = ? WHERE email = ?');
            $stmt->execute([$role, $email]);

            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateUserRole($id, $role) {
        try {
            // Validate role
            if (!in_array($role, ['viewer', 'editor', 'admin'])) {
                return ['success' => false, 'error' => 'Invalid role specified'];
            }

            $stmt = $this->db->prepare('UPDATE users SET role = ? WHERE id = ?');
            $stmt->execute([$role, $id]);

            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'error' => 'User not found or role is already set'];
            }

            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deleteUser($id) {
        try {
            $stmt = $this->db->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'error' => 'User not found'];
            }

            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function listAll() {
        try {
            $stmt = $this->db->prepare('SELECT id, email, role FROM users');
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
} 
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
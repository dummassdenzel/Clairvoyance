<?php
class Kpi {
    private $db;
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = (new Connection())->connect();
    }
    public function create($name, $target, $rag_red, $rag_amber, $user_id) {
        try {
            $stmt = $this->db->prepare('INSERT INTO kpis (name, target, rag_red, rag_amber, user_id) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, $target, $rag_red, $rag_amber, $user_id]);
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    public function listAll() {
        try {
            $stmt = $this->db->prepare('SELECT * FROM kpis');
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
} 
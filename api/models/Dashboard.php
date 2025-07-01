<?php
class Dashboard {
    private $db;
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = (new Connection())->connect();
    }
    public function create($name, $layout, $user_id) {
        try {
            $stmt = $this->db->prepare('INSERT INTO dashboards (name, layout, user_id) VALUES (?, ?, ?)');
            $stmt->execute([$name, $layout, $user_id]);
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    public function getById($id, $user_id, $role) {
        try {
            $stmt = $this->db->prepare('SELECT * FROM dashboards WHERE id = ?');
            $stmt->execute([$id]);
            $dashboard = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$dashboard) {
                return ['success' => false, 'error' => 'Dashboard not found'];
            }
            if ($role === 'editor' && $dashboard['user_id'] == $user_id) {
                return ['success' => true, 'dashboard' => $dashboard];
            }
            // Check dashboard_access for viewers
            $stmt = $this->db->prepare('SELECT * FROM dashboard_access WHERE dashboard_id = ? AND user_id = ?');
            $stmt->execute([$id, $user_id]);
            if ($stmt->fetch()) {
                return ['success' => true, 'dashboard' => $dashboard];
            }
            return ['success' => false, 'error' => 'Access denied'];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    public function assignViewer($dashboard_id, $user_id) {
        try {
            // Check if already assigned
            $stmt = $this->db->prepare('SELECT * FROM dashboard_access WHERE dashboard_id = ? AND user_id = ?');
            $stmt->execute([$dashboard_id, $user_id]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Viewer already assigned to this dashboard'];
            }
            $stmt = $this->db->prepare('INSERT INTO dashboard_access (dashboard_id, user_id) VALUES (?, ?)');
            $stmt->execute([$dashboard_id, $user_id]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    public function listAll($user_id, $role) {
        try {
            if ($role === 'editor') {
                $stmt = $this->db->prepare('SELECT * FROM dashboards WHERE user_id = ?');
                $stmt->execute([$user_id]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->db->prepare('SELECT d.* FROM dashboards d JOIN dashboard_access da ON d.id = da.dashboard_id WHERE da.user_id = ?');
                $stmt->execute([$user_id]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            return [];
        }
    }
} 
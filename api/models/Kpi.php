<?php
class Kpi {
    private $db;
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = (new Connection())->connect();
    }
    public function update($kpiId, $data, $userId) {
        try {
            // First, verify ownership to prevent unauthorized updates.
            $stmt = $this->db->prepare('SELECT user_id FROM kpis WHERE id = ?');
            $stmt->execute([$kpiId]);
            $kpi = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$kpi) {
                return ['success' => false, 'error' => 'KPI not found.', 'code' => 404];
            }

            if ($kpi['user_id'] != $userId) {
                return ['success' => false, 'error' => 'You do not have permission to update this KPI.', 'code' => 403];
            }

            // Update the KPI record.
            $stmt = $this->db->prepare('UPDATE kpis SET name = ?, target = ?, rag_red = ?, rag_amber = ? WHERE id = ?');
            $stmt->execute([$data['name'], $data['target'], $data['rag_red'], $data['rag_amber'], $kpiId]);

            // Fetch the updated KPI to return it in the response.
            $stmt = $this->db->prepare('SELECT * FROM kpis WHERE id = ?');
            $stmt->execute([$kpiId]);
            $updatedKpi = $stmt->fetch(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => $updatedKpi];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'code' => 500];
        }
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
    public function delete($kpiId, $userId) {
        try {
            $this->db->beginTransaction();

            // First, verify ownership to prevent unauthorized deletions.
            $stmt = $this->db->prepare('SELECT user_id FROM kpis WHERE id = ?');
            $stmt->execute([$kpiId]);
            $kpi = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$kpi) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'KPI not found.', 'code' => 404];
            }

            if ($kpi['user_id'] != $userId) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'You do not have permission to delete this KPI.', 'code' => 403];
            }

            // Delete all entries for this KPI first to maintain foreign key constraints.
            $stmt = $this->db->prepare('DELETE FROM kpi_entries WHERE kpi_id = ?');
            $stmt->execute([$kpiId]);

            // Now, delete the KPI itself.
            $stmt = $this->db->prepare('DELETE FROM kpis WHERE id = ?');
            $stmt->execute([$kpiId]);

            $this->db->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage(), 'code' => 500];
        }
    }

    public function listAll($userId) {
        try {
            $stmt = $this->db->prepare('SELECT * FROM kpis WHERE user_id = ?');
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
} 
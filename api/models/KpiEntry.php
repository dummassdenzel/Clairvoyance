<?php
class KpiEntry {
    private $db;
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = (new Connection())->connect();
    }
    public function create($kpi_id, $date, $value) {
        try {
            $stmt = $this->db->prepare('INSERT INTO kpi_entries (kpi_id, date, value) VALUES (?, ?, ?)');
            $stmt->execute([$kpi_id, $date, $value]);
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    public function bulkInsertFromCsv($csvPath) {
        $inserted = 0;
        $failed = 0;
        $errors = [];
        if (($handle = fopen($csvPath, 'r')) !== false) {
            $header = fgetcsv($handle);
            $expected = ['kpi_id', 'date', 'value'];
            if (array_map('strtolower', $header) !== $expected) {
                fclose($handle);
                return ['inserted' => 0, 'failed' => 0, 'errors' => ['Invalid CSV header. Expected: kpi_id,date,value']];
            }
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) !== 3) {
                    $failed++;
                    $errors[] = ['row' => $row, 'error' => 'Invalid column count'];
                    continue;
                }
                list($kpi_id, $date, $value) = $row;
                if (!is_numeric($kpi_id) || !strtotime($date) || !is_numeric($value)) {
                    $failed++;
                    $errors[] = ['row' => $row, 'error' => 'Invalid data types'];
                    continue;
                }
                try {
                    $stmt = $this->db->prepare('INSERT INTO kpi_entries (kpi_id, date, value) VALUES (?, ?, ?)');
                    $stmt->execute([$kpi_id, $date, $value]);
                    $inserted++;
                } catch (PDOException $e) {
                    $failed++;
                    $errors[] = ['row' => $row, 'error' => $e->getMessage()];
                }
            }
            fclose($handle);
        } else {
            return ['inserted' => 0, 'failed' => 0, 'errors' => ['Could not open CSV file']];
        }
        return ['inserted' => $inserted, 'failed' => $failed, 'errors' => $errors];
    }
    public function getAggregateValue($kpi_id, $aggregationType, $startDate = null, $endDate = null)
    {
        $baseSql = 'FROM kpi_entries WHERE kpi_id = ?';
        $params = [$kpi_id];

        if ($startDate) {
            $baseSql .= ' AND date >= ?';
            $params[] = $startDate;
        }

        if ($endDate) {
            $baseSql .= ' AND date <= ?';
            $params[] = $endDate;
        }

        $sql = '';
        switch ($aggregationType) {
            case 'sum':
                $sql = 'SELECT SUM(value) as value ' . $baseSql;
                break;
            case 'average':
                $sql = 'SELECT AVG(value) as value ' . $baseSql;
                break;
            case 'latest':
                $sql = 'SELECT value ' . $baseSql . ' ORDER BY date DESC LIMIT 1';
                break;
            default:
                return null; // Invalid aggregation type
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // In a real app, you might log the error
            return null;
        }
    }

    public function listByKpiId($kpi_id, $startDate = null, $endDate = null) {
        try {
            $sql = 'SELECT date, value FROM kpi_entries WHERE kpi_id = ?';
            $params = [$kpi_id];

            if ($startDate) {
                $sql .= ' AND date >= ?';
                $params[] = $startDate;
            }

            if ($endDate) {
                $sql .= ' AND date <= ?';
                $params[] = $endDate;
            }

            $sql .= ' ORDER BY date ASC';

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
} 
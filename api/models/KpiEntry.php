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
} 
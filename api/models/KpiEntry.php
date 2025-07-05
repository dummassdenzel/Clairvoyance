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
    public function bulkInsertFromCsv($kpi_id, $csvPath) {
        $report = ['inserted' => 0, 'failed' => 0, 'errors' => []];
        $rowsToInsert = [];

        if (($handle = fopen($csvPath, 'r')) === false) {
            $report['errors'][] = 'Could not open CSV file.';
            return $report;
        }

        // Read header and validate
        $header = array_map('trim', array_map('strtolower', fgetcsv($handle)));
        $expected = ['date', 'value'];
        if ($header !== $expected) {
            fclose($handle);
            $report['errors'][] = 'Invalid CSV header. Expected columns: date, value. Found: ' . implode(',', $header);
            return $report;
        }

        // Read rows and perform validation
        $lineNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;
            if (count($row) !== 2) {
                $report['failed']++;
                $report['errors'][] = "Row {$lineNumber}: Invalid column count. Expected 2, got " . count($row) . ".";
                continue;
            }

            list($date, $value) = $row;
            $trimmedDate = trim($date);
            $trimmedValue = trim($value);

            // Validate date format (YYYY-MM-DD)
            $d = DateTime::createFromFormat('Y-m-d', $trimmedDate);
            if (!$d || $d->format('Y-m-d') !== $trimmedDate) {
                $report['failed']++;
                $report['errors'][] = "Row {$lineNumber}: Invalid date format for '{$date}'. Please use YYYY-MM-DD.";
                continue;
            }

            // Validate value is numeric
            if (!is_numeric($trimmedValue)) {
                $report['failed']++;
                $report['errors'][] = "Row {$lineNumber}: Value '{$value}' is not a valid number.";
                continue;
            }

            $rowsToInsert[] = ['date' => $trimmedDate, 'value' => $trimmedValue];
        }
        fclose($handle);

        // If validation errors occurred, stop before inserting anything
        if ($report['failed'] > 0) {
            return $report;
        }

        // All rows are valid, proceed with database transaction
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare('INSERT INTO kpi_entries (kpi_id, date, value) VALUES (?, ?, ?)');
            
            foreach ($rowsToInsert as $row) {
                $stmt->execute([$kpi_id, $row['date'], $row['value']]);
                $report['inserted']++;
            }

            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            $report['inserted'] = 0;
            $report['failed'] = count($rowsToInsert);
            $report['errors'][] = 'Database error: ' . $e->getMessage();
        }

        return $report;
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
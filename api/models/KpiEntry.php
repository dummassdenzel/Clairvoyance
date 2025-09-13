<?php

namespace Models;

use PDO;
use PDOException;

class KpiEntry
{
    private PDO $db;
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    
    // CRUD Operations
    public function create(int $kpiId, string $date, float $value): array
    {
        try {
            $stmt = $this->db->prepare('INSERT INTO kpi_entries (kpi_id, date, value) VALUES (?, ?, ?)');
            $stmt->execute([$kpiId, $date, $value]);
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM kpi_entries WHERE id = ?');
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function findByKpiId(int $kpiId, ?string $startDate = null, ?string $endDate = null): array
    {
        try {
            $sql = 'SELECT * FROM kpi_entries WHERE kpi_id = ?';
            $params = [$kpiId];
            
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
    
    public function findByDateRange(int $kpiId, string $startDate, string $endDate): array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT * FROM kpi_entries 
                WHERE kpi_id = ? AND date BETWEEN ? AND ? 
                ORDER BY date ASC
            ');
            $stmt->execute([$kpiId, $startDate, $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function update(int $id, array $data): bool
    {
        try {
            $fields = [];
            $params = [];
            
            $allowedFields = ['date', 'value'];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $fields[] = "$key = ?";
                    $params[] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $params[] = $id;
            $sql = 'UPDATE kpi_entries SET ' . implode(', ', $fields) . ', updated_at = CURRENT_TIMESTAMP WHERE id = ?';
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->db->prepare('DELETE FROM kpi_entries WHERE id = ?');
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function deleteByKpiId(int $kpiId): bool
    {
        try {
            $stmt = $this->db->prepare('DELETE FROM kpi_entries WHERE kpi_id = ?');
            return $stmt->execute([$kpiId]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Data Aggregation Methods
    public function getAggregateValue(int $kpiId, string $aggregationType, ?string $startDate = null, ?string $endDate = null): ?array
    {
        $baseSql = 'FROM kpi_entries WHERE kpi_id = ?';
        $params = [$kpiId];

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
            case 'avg':
                $sql = 'SELECT AVG(value) as value ' . $baseSql;
                break;
            case 'latest':
                $sql = 'SELECT value ' . $baseSql . ' ORDER BY date DESC LIMIT 1';
                break;
            case 'earliest':
                $sql = 'SELECT value ' . $baseSql . ' ORDER BY date ASC LIMIT 1';
                break;
            case 'min':
                $sql = 'SELECT MIN(value) as value ' . $baseSql;
                break;
            case 'max':
                $sql = 'SELECT MAX(value) as value ' . $baseSql;
                break;
            case 'count':
                $sql = 'SELECT COUNT(*) as value ' . $baseSql;
                break;
            default:
                return null;
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function getLatestValue(int $kpiId): ?float
    {
        try {
            $stmt = $this->db->prepare('SELECT value FROM kpi_entries WHERE kpi_id = ? ORDER BY date DESC LIMIT 1');
            $stmt->execute([$kpiId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (float)$result['value'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function getEarliestValue(int $kpiId): ?float
    {
        try {
            $stmt = $this->db->prepare('SELECT value FROM kpi_entries WHERE kpi_id = ? ORDER BY date ASC LIMIT 1');
            $stmt->execute([$kpiId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (float)$result['value'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function getValueByDate(int $kpiId, string $date): ?float
    {
        try {
            $stmt = $this->db->prepare('SELECT value FROM kpi_entries WHERE kpi_id = ? AND date = ? LIMIT 1');
            $stmt->execute([$kpiId, $date]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (float)$result['value'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    // Bulk Operations
    public function bulkInsert(int $kpiId, array $entries): array
    {
        $report = ['inserted' => 0, 'failed' => 0, 'errors' => []];
        
        if (empty($entries)) {
            $report['errors'][] = 'No entries provided';
            return $report;
        }
        
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare('INSERT INTO kpi_entries (kpi_id, date, value) VALUES (?, ?, ?)');
            
            foreach ($entries as $entry) {
                if (!isset($entry['date']) || !isset($entry['value'])) {
                    $report['failed']++;
                    $report['errors'][] = 'Missing date or value in entry';
                    continue;
                }
                
                try {
                    $stmt->execute([$kpiId, $entry['date'], $entry['value']]);
                    $report['inserted']++;
                } catch (PDOException $e) {
                    $report['failed']++;
                    $report['errors'][] = 'Database error for entry: ' . $e->getMessage();
                }
            }
            
            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            $report['errors'][] = 'Transaction failed: ' . $e->getMessage();
        }
        
        return $report;
    }
    
    // Data Validation Methods
    public function validateDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    public function validateValue($value): bool
    {
        return is_numeric($value) && $value >= 0;
    }
    
    public function validateEntry(array $entry): array
    {
        $errors = [];
        
        if (!isset($entry['date'])) {
            $errors[] = 'Date is required';
        } elseif (!$this->validateDate($entry['date'])) {
            $errors[] = 'Invalid date format. Use YYYY-MM-DD';
        }
        
        if (!isset($entry['value'])) {
            $errors[] = 'Value is required';
        } elseif (!$this->validateValue($entry['value'])) {
            $errors[] = 'Value must be a positive number';
        }
        
        return $errors;
    }
    
    // Utility Methods
    public function exists(int $id): bool
    {
        try {
            $stmt = $this->db->prepare('SELECT 1 FROM kpi_entries WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function hasEntryForDate(int $kpiId, string $date): bool
    {
        try {
            $stmt = $this->db->prepare('SELECT 1 FROM kpi_entries WHERE kpi_id = ? AND date = ? LIMIT 1');
            $stmt->execute([$kpiId, $date]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function getStats(int $kpiId): array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT 
                    COUNT(*) as total_entries,
                    MIN(date) as earliest_date,
                    MAX(date) as latest_date,
                    MIN(value) as min_value,
                    MAX(value) as max_value,
                    AVG(value) as avg_value,
                    SUM(value) as sum_value
                FROM kpi_entries 
                WHERE kpi_id = ?
            ');
            $stmt->execute([$kpiId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function getDateRange(int $kpiId): ?array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT MIN(date) as start_date, MAX(date) as end_date 
                FROM kpi_entries 
                WHERE kpi_id = ?
            ');
            $stmt->execute([$kpiId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function getMissingDates(int $kpiId, string $startDate, string $endDate): array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT DISTINCT date 
                FROM kpi_entries 
                WHERE kpi_id = ? AND date BETWEEN ? AND ?
            ');
            $stmt->execute([$kpiId, $startDate, $endDate]);
            $existingDates = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'date');
            
            $allDates = [];
            $current = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            
            while ($current <= $end) {
                $allDates[] = $current->format('Y-m-d');
                $current->add(new \DateInterval('P1D'));
            }
            
            return array_diff($allDates, $existingDates);
        } catch (PDOException $e) {
            return [];
        }
    }
} 
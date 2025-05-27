<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Kpi.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

class ImportService
{
    private $pdo;
    
    public function __construct()
    {
        $conn = new Connection();
        $this->pdo = $conn->connect();
    }
    
    /**
     * Import data from spreadsheet file (CSV, XLSX, XLS, etc.)
     * 
     * @param string $file Path to the spreadsheet file
     * @param int $kpiId KPI ID to associate data with
     * @param array $mapping Column mapping configuration
     * @param string $fileType File type (csv, xlsx, xls)
     * @return array Import result statistics
     */
    public function importFromSpreadsheet($file, $kpiId, $mapping, $fileType)
    {
        $result = [
            'imported_rows' => 0,
            'skipped_rows' => 0,
            'errors' => []
        ];
        
        // Check if file exists
        if (!file_exists($file)) {
            $result['errors'][] = 'File not found';
            return $result;
        }
        
        try {
            // Load spreadsheet
            $reader = IOFactory::createReaderForFile($file);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file);
            
            // Get active sheet
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = 
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            
            // Get header row
            $headerRow = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $headerRow[] = $worksheet->getCellByColumnAndRow($col, 1)->getValue();
            }
            
            // Find column indexes based on mapping
            $timestampIndex = array_search($mapping['timestamp_column'], $headerRow);
            $valueIndex = array_search($mapping['value_column'], $headerRow);
            
            if ($timestampIndex === false || $valueIndex === false) {
                $result['errors'][] = 'Timestamp or value column not found in file';
                return $result;
            }
            
            // Adjust for 0-based array vs 1-based spreadsheet columns
            $timestampIndex++;
            $valueIndex++;
            
            // Prepare statement for inserting measurements
            $stmt = $this->pdo->prepare("
                INSERT INTO measurements (kpi_id, value, timestamp)
                VALUES (:kpi_id, :value, :timestamp)
            ");
            
            // Process data rows
            for ($row = 2; $row <= $highestRow; $row++) {
                // Extract data from row
                $timestamp = trim($worksheet->getCellByColumnAndRow($timestampIndex, $row)->getValue());
                $value = trim($worksheet->getCellByColumnAndRow($valueIndex, $row)->getValue());
                
                // Skip empty rows
                if (empty($timestamp) || empty($value)) {
                    $result['skipped_rows']++;
                    continue;
                }
                
                // Convert Excel timestamp if needed
                if ($timestamp instanceof \DateTime) {
                    $timestamp = $timestamp->format('Y-m-d H:i:s');
                }
                // Format timestamp based on provided format if it's a string
                else if (isset($mapping['timestamp_format']) && is_string($timestamp)) {
                    $date = \DateTime::createFromFormat($mapping['timestamp_format'], $timestamp);
                    if ($date) {
                        $timestamp = $date->format('Y-m-d H:i:s');
                    } else {
                        $result['skipped_rows']++;
                        continue;
                    }
                }
                
                // Validate numeric value
                if (!is_numeric($value)) {
                    $result['skipped_rows']++;
                    continue;
                }
                
                try {
                    // Bind parameters
                    $kpiIdParam = $kpiId;
                    $valueParam = $value;
                    $timestampParam = $timestamp;
                    
                    $stmt->bindParam(':kpi_id', $kpiIdParam, PDO::PARAM_INT);
                    $stmt->bindParam(':value', $valueParam, PDO::PARAM_STR);
                    $stmt->bindParam(':timestamp', $timestampParam, PDO::PARAM_STR);
                    
                    // Execute the statement
                    if ($stmt->execute()) {
                        $result['imported_rows']++;
                    } else {
                        $result['skipped_rows']++;
                    }
                } catch (\PDOException $e) {
                    $result['errors'][] = "Error on row $row: " . $e->getMessage();
                    $result['skipped_rows']++;
                }
            }
            
            return $result;
            
        } catch (\Exception $e) {
            $result['errors'][] = 'Error processing file: ' . $e->getMessage();
            return $result;
        }
    }
    
    /**
     * Import data from CSV file (wrapper for importFromSpreadsheet)
     * 
     * @param string $file Path to the CSV file
     * @param int $kpiId KPI ID to associate data with
     * @param array $mapping Column mapping configuration
     * @return array Import result statistics
     */
    public function importFromCsv($file, $kpiId, $mapping)
    {
        return $this->importFromSpreadsheet($file, $kpiId, $mapping, 'csv');
    }
    
    /**
     * Import data from Excel file (wrapper for importFromSpreadsheet)
     * 
     * @param string $file Path to the Excel file
     * @param int $kpiId KPI ID to associate data with
     * @param array $mapping Column mapping configuration
     * @return array Import result statistics
     */
    public function importFromExcel($file, $kpiId, $mapping)
    {
        return $this->importFromSpreadsheet($file, $kpiId, $mapping, 'xlsx');
    }
    
    /**
     * Import data from JSON file
     * 
     * @param string $file Path to the JSON file
     * @param int $kpiId KPI ID to associate data with
     * @param array $mapping Field mapping configuration
     * @return array Import result statistics
     */
    public function importFromJson($file, $kpiId, $mapping)
    {
        $result = [
            'imported_rows' => 0,
            'skipped_rows' => 0,
            'errors' => []
        ];
        
        // Check if file exists
        if (!file_exists($file)) {
            $result['errors'][] = 'File not found';
            return $result;
        }
        
        // Read and decode JSON file
        $jsonContent = file_get_contents($file);
        $data = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $result['errors'][] = 'Invalid JSON file: ' . json_last_error_msg();
            return $result;
        }
        
        // Check if data is an array
        if (!is_array($data)) {
            $result['errors'][] = 'JSON file must contain an array of measurements';
            return $result;
        }
        
        // Prepare statement for inserting measurements
        $stmt = $this->pdo->prepare("
            INSERT INTO measurements (kpi_id, value, timestamp)
            VALUES (:kpi_id, :value, :timestamp)
        ");
        
        // Process data items
        $rowNumber = 0;
        foreach ($data as $item) {
            $rowNumber++;
            
            // Extract data from item
            $timestamp = isset($item[$mapping['timestamp_field']]) ? trim($item[$mapping['timestamp_field']]) : null;
            $value = isset($item[$mapping['value_field']]) ? trim($item[$mapping['value_field']]) : null;
            
            // Validate data
            if (empty($timestamp) || empty($value) || !is_numeric($value)) {
                $result['skipped_rows']++;
                continue;
            }
            
            // Format timestamp based on provided format
            if (isset($mapping['timestamp_format'])) {
                $date = DateTime::createFromFormat($mapping['timestamp_format'], $timestamp);
                if ($date) {
                    $timestamp = $date->format('Y-m-d H:i:s');
                } else {
                    $result['skipped_rows']++;
                    continue;
                }
            }
            
            try {
                // Bind parameters
                $kpiIdParam = $kpiId;
                $valueParam = $value;
                $timestampParam = $timestamp;
                
                $stmt->bindParam(':kpi_id', $kpiIdParam, PDO::PARAM_INT);
                $stmt->bindParam(':value', $valueParam, PDO::PARAM_STR);
                $stmt->bindParam(':timestamp', $timestampParam, PDO::PARAM_STR);
                
                // Execute the statement
                if ($stmt->execute()) {
                    $result['imported_rows']++;
                } else {
                    $result['skipped_rows']++;
                }
            } catch (PDOException $e) {
                $result['errors'][] = "Error on item $rowNumber: " . $e->getMessage();
                $result['skipped_rows']++;
            }
        }
        
        return $result;
    }
    
    /**
     * Validate the file type
     * 
     * @param string $file File path
     * @param string $expectedType Expected file type (csv, xlsx, xls, json)
     * @return bool Whether the file is of the expected type
     */
    public function validateFileType($file, $expectedType)
    {
        $fileInfo = pathinfo($file);
        $extension = strtolower($fileInfo['extension'] ?? '');
        
        switch ($expectedType) {
            case 'csv':
                return $extension === 'csv';
            case 'xlsx':
                return $extension === 'xlsx';
            case 'xls':
                return $extension === 'xls';
            case 'json':
                return $extension === 'json';
            case 'spreadsheet':
                return in_array($extension, ['csv', 'xlsx', 'xls']);
            default:
                return false;
        }
    }
    
    /**
     * Create a sample template file for data import
     * 
     * @param string $format Format of the template (csv, xlsx, json)
     * @return string Path to the generated template file
     */
    public function createTemplateFile($format)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'kpi_template_');
        
        switch ($format) {
            case 'csv':
                $csvFile = $tempFile . '.csv';
                rename($tempFile, $csvFile);
                
                $handle = fopen($csvFile, 'w');
                fputcsv($handle, ['Date', 'Value']);
                fputcsv($handle, ['2025-01-01', '100']);
                fputcsv($handle, ['2025-01-02', '105']);
                fputcsv($handle, ['2025-01-03', '110']);
                fclose($handle);
                
                return $csvFile;
                
            case 'xlsx':
                $xlsxFile = $tempFile . '.xlsx';
                rename($tempFile, $xlsxFile);
                
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                
                $sheet->setCellValue('A1', 'Date');
                $sheet->setCellValue('B1', 'Value');
                $sheet->setCellValue('A2', '2025-01-01');
                $sheet->setCellValue('B2', '100');
                $sheet->setCellValue('A3', '2025-01-02');
                $sheet->setCellValue('B3', '105');
                $sheet->setCellValue('A4', '2025-01-03');
                $sheet->setCellValue('B4', '110');
                
                $writer = new Xlsx($spreadsheet);
                $writer->save($xlsxFile);
                
                return $xlsxFile;
                
            case 'json':
                $jsonFile = $tempFile . '.json';
                rename($tempFile, $jsonFile);
                
                $data = [
                    ['date' => '2025-01-01', 'value' => 100],
                    ['date' => '2025-01-02', 'value' => 105],
                    ['date' => '2025-01-03', 'value' => 110]
                ];
                
                file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT));
                
                return $jsonFile;
                
            default:
                throw new \Exception('Unsupported template format');
        }
    }
}

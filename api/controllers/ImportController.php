<?php

require_once __DIR__ . '/../services/ImportService.php';
require_once __DIR__ . '/../models/Kpi.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class ImportController
{
    private $importService;
    private $kpiModel;
    
    public function __construct()
    {
        $this->importService = new ImportService();
        $this->kpiModel = new Kpi();
    }
    
    /**
     * Import data from a file
     * 
     * @param array $data Import configuration
     * @param object $user Current user
     */
    public function importData($data, $user)
    {
        // Validate required fields
        $validation = Validator::validateRequired($data, ['kpi_id']);
        if (!$validation['isValid']) {
            Response::error($validation['message']);
            return;
        }
        
        // Check if KPI exists
        $kpi = $this->kpiModel->getById($data['kpi_id']);
        if (!$kpi) {
            Response::notFound('KPI not found');
            return;
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            Response::error('No file uploaded or upload error');
            return;
        }
        
        // Get file info
        $file = $_FILES['file'];
        $fileName = $file['name'];
        $fileTmpPath = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileType = $file['type'];
        
        // Check file size (max 10MB)
        $maxFileSize = 10 * 1024 * 1024; // 10MB
        if ($fileSize > $maxFileSize) {
            Response::error('File size exceeds the limit (10MB)');
            return;
        }
        
        // Get file extension
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Process based on file type
        $result = null;
        $mapping = isset($data['mapping']) ? json_decode($data['mapping'], true) : [];
        
        if (!$mapping) {
            Response::error('Invalid mapping configuration');
            return;
        }
        
        try {
            switch ($fileExtension) {
                case 'csv':
                    // Validate mapping
                    if (!isset($mapping['timestamp_column']) || !isset($mapping['value_column'])) {
                        Response::error('CSV mapping must include timestamp_column and value_column');
                        return;
                    }
                    
                    $result = $this->importService->importFromCsv($fileTmpPath, $data['kpi_id'], $mapping);
                    break;
                
                case 'xlsx':
                case 'xls':
                    // Validate mapping
                    if (!isset($mapping['timestamp_column']) || !isset($mapping['value_column'])) {
                        Response::error('Excel mapping must include timestamp_column and value_column');
                        return;
                    }
                    
                    $result = $this->importService->importFromExcel($fileTmpPath, $data['kpi_id'], $mapping);
                    break;
                    
                case 'json':
                    // Validate mapping
                    if (!isset($mapping['timestamp_field']) || !isset($mapping['value_field'])) {
                        Response::error('JSON mapping must include timestamp_field and value_field');
                        return;
                    }
                    
                    $result = $this->importService->importFromJson($fileTmpPath, $data['kpi_id'], $mapping);
                    break;
                    
                default:
                    Response::error('Unsupported file format. Please upload CSV, Excel, or JSON files.');
                    return;
            }
            
            // Check for import errors
            if (!empty($result['errors'])) {
                Response::error('Import completed with errors', [
                    'result' => $result,
                    'kpi' => $kpi
                ]);
                return;
            }
            
            // Return success response
            Response::success('Data imported successfully', [
                'result' => $result,
                'kpi' => $kpi
            ]);
            
        } catch (Exception $e) {
            Response::error('Import failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get import templates
     */
    public function getTemplates()
    {
        $templates = [
            'csv' => [
                'description' => 'CSV template for KPI data import',
                'format' => 'CSV (Comma Separated Values)',
                'example' => "Date,Value\n2025-01-01,100\n2025-01-02,105\n2025-01-03,110",
                'mapping_example' => [
                    'timestamp_column' => 'Date',
                    'value_column' => 'Value',
                    'timestamp_format' => 'Y-m-d'
                ]
            ],
            'json' => [
                'description' => 'JSON template for KPI data import',
                'format' => 'JSON (JavaScript Object Notation)',
                'example' => json_encode([
                    ['date' => '2025-01-01', 'value' => 100],
                    ['date' => '2025-01-02', 'value' => 105],
                    ['date' => '2025-01-03', 'value' => 110]
                ], JSON_PRETTY_PRINT),
                'mapping_example' => [
                    'timestamp_field' => 'date',
                    'value_field' => 'value',
                    'timestamp_format' => 'Y-m-d'
                ]
            ]
        ];
        
        Response::success('Import templates retrieved successfully', $templates);
    }
    
    /**
     * Validate import data before actual import
     * 
     * @param array $data Validation configuration
     */
    public function validateImport($data)
    {
        // Validate required fields
        $validation = Validator::validateRequired($data, ['kpi_id']);
        if (!$validation['isValid']) {
            Response::error($validation['message']);
            return;
        }
        
        // Check if KPI exists
        $kpi = $this->kpiModel->getById($data['kpi_id']);
        if (!$kpi) {
            Response::notFound('KPI not found');
            return;
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            Response::error('No file uploaded or upload error');
            return;
        }
        
        // Get file info
        $file = $_FILES['file'];
        $fileName = $file['name'];
        $fileTmpPath = $file['tmp_name'];
        $fileSize = $file['size'];
        
        // Check file size (max 10MB)
        $maxFileSize = 10 * 1024 * 1024; // 10MB
        if ($fileSize > $maxFileSize) {
            Response::error('File size exceeds the limit (10MB)');
            return;
        }
        
        // Get file extension
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Validate file type
        if (!in_array($fileExtension, ['csv', 'json'])) {
            Response::error('Unsupported file format. Please upload CSV or JSON files.');
            return;
        }
        
        // Validate mapping
        $mapping = isset($data['mapping']) ? json_decode($data['mapping'], true) : [];
        if (!$mapping) {
            Response::error('Invalid mapping configuration');
            return;
        }
        
        // Validate mapping based on file type
        if ($fileExtension === 'csv' && (!isset($mapping['timestamp_column']) || !isset($mapping['value_column']))) {
            Response::error('CSV mapping must include timestamp_column and value_column');
            return;
        } else if ($fileExtension === 'json' && (!isset($mapping['timestamp_field']) || !isset($mapping['value_field']))) {
            Response::error('JSON mapping must include timestamp_field and value_field');
            return;
        }
        
        // Return success response
        Response::success('File and mapping validated successfully', [
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'file_type' => $fileExtension,
            'kpi' => $kpi,
            'mapping' => $mapping
        ]);
    }
}

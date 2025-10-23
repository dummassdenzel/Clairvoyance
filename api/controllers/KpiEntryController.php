<?php

namespace Controllers;

use Core\BaseController;
use Services\KpiEntryService;
use Services\AuthService;

class KpiEntryController extends BaseController
{
    private KpiEntryService $kpiEntryService;
    private AuthService $authService;

    public function __construct()
    {
        parent::__construct();
        $this->kpiEntryService = $this->getService(\Services\KpiEntryService::class);
        $this->authService = $this->getService(\Services\AuthService::class);
    }

    public function create(): void
    {
        try {
            $this->authService->requireRole('editor');
            
            $data = $this->getRequestData();
            
            if (!$this->validateRequired($data, ['kpi_id', 'date', 'value'])) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Missing required fields: kpi_id, date, value'
                ], 400);
                return;
            }

            $currentUser = $this->getCurrentUser();
            
            $result = $this->kpiEntryService->add(
                $currentUser, 
                (int)$data['kpi_id'], 
                $data['date'], 
                $data['value']
            );

            $this->jsonResponse([
                'success' => true,
                'message' => 'KPI Entry created successfully',
                'data' => ['id' => $result['id']]
            ], 201);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

    public function uploadCsv(): void
    {
        try {
            $this->authService->requireRole('editor');
            
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'CSV file is required or upload failed'
                ], 400);
                return;
            }

            if (!isset($_POST['kpi_id']) || !is_numeric($_POST['kpi_id'])) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'A numeric kpi_id is required'
                ], 400);
                return;
            }

            $kpiId = (int)$_POST['kpi_id'];
            $currentUser = $this->getCurrentUser();

            // Get column mapping from POST data
            $dateColumn = $_POST['date_column'] ?? null;
            $valueColumn = $_POST['value_column'] ?? null;
            $hasHeader = isset($_POST['has_header']) ? (bool)$_POST['has_header'] : true;

            // Parse CSV file with enhanced parsing
            $csvData = $this->parseCsvFileEnhanced($_FILES['file']['tmp_name'], $dateColumn, $valueColumn, $hasHeader);
            
            if (empty($csvData)) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'No valid data found in CSV file'
                ], 400);
                return;
            }

            $result = $this->kpiEntryService->bulkInsert($currentUser, $kpiId, $csvData);

            // Check if the bulk insert was successful
            if (isset($result['success']) && $result['success'] === false) {
                // This is an error from the service layer (validation errors, etc.)
                $this->jsonResponse([
                    'success' => false,
                    'error' => $result['error']
                ], $result['code'] ?? 400);
                return;
            }

            // This is the result from the model's bulkInsert method
            $inserted = $result['inserted'] ?? 0;
            $failed = $result['failed'] ?? 0;
            $errors = $result['errors'] ?? [];

            if ($inserted > 0) {
                $message = "CSV uploaded successfully. {$inserted} entries added";
                if ($failed > 0) {
                    $message .= ", {$failed} entries failed";
                }
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'inserted' => $inserted,
                        'failed' => $failed,
                        'errors' => $errors
                    ]
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'No entries were added. ' . implode(', ', $errors)
                ], 400);
            }

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

    public function previewCsv(): void
    {
        try {
            $this->authService->requireRole('editor');
            
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'CSV file is required or upload failed'
                ], 400);
                return;
            }

            // Parse CSV file to get headers and preview data
            $previewData = $this->getCsvPreview($_FILES['file']['tmp_name']);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $previewData
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

    public function update(int $entryId): void
    {
        try {
            $this->authService->requireRole('editor');
            
            if (!$entryId) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Missing KPI Entry ID'
                ], 400);
                return;
            }

            $data = $this->getRequestData();
            
            if (empty($data)) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'No update data provided'
                ], 400);
                return;
            }

            $currentUser = $this->getCurrentUser();
            
            $success = $this->kpiEntryService->update($currentUser, $entryId, $data);

            if ($success) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'KPI Entry updated successfully'
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Failed to update KPI entry'
                ], 500);
            }

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

    public function delete(int $entryId): void
    {
        try {
            $this->authService->requireRole('editor');
            
            if (!$entryId) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Missing KPI Entry ID'
                ], 400);
                return;
            }

            $currentUser = $this->getCurrentUser();
            
            $success = $this->kpiEntryService->delete($currentUser, $entryId);

            if ($success) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'KPI Entry deleted successfully'
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Failed to delete KPI entry'
                ], 500);
            }

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

    private function parseCsvFile(string $filePath): array
    {
        $data = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle === false) {
            throw new \Exception('Could not read CSV file');
        }

        // Skip header row if it exists
        $firstRow = fgetcsv($handle);
        if ($firstRow && (strtolower($firstRow[0]) === 'date' || strtolower($firstRow[0]) === 'value')) {
            // This is a header row, skip it
        } else {
            // First row is data, reset file pointer
            rewind($handle);
        }

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 2) {
                $data[] = [
                    'date' => trim($row[0]),
                    'value' => trim($row[1])
                ];
            }
        }

        fclose($handle);
        return $data;
    }

    private function parseCsvFileEnhanced(string $filePath, ?string $dateColumn, ?string $valueColumn, bool $hasHeader = true): array
    {
        $data = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle === false) {
            throw new \Exception('Could not read CSV file');
        }

        $headers = [];
        $dateIndex = 0;
        $valueIndex = 1;

        // Read headers if present
        if ($hasHeader) {
            $headers = fgetcsv($handle);
            if ($headers === false) {
                fclose($handle);
                throw new \Exception('Could not read CSV headers');
            }
            
            // Find column indices
            if ($dateColumn !== null) {
                $dateIndex = array_search($dateColumn, $headers);
                if ($dateIndex === false) {
                    fclose($handle);
                    throw new \Exception("Date column '{$dateColumn}' not found in CSV");
                }
            }
            
            if ($valueColumn !== null) {
                $valueIndex = array_search($valueColumn, $headers);
                if ($valueIndex === false) {
                    fclose($handle);
                    throw new \Exception("Value column '{$valueColumn}' not found in CSV");
                }
            }
        } else {
            // Use numeric indices if no headers
            $dateIndex = $dateColumn ? (int)$dateColumn : 0;
            $valueIndex = $valueColumn ? (int)$valueColumn : 1;
        }

        $rowCount = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $rowCount++;
            
            if (count($row) <= max($dateIndex, $valueIndex)) {
                continue; // Skip rows that don't have enough columns
            }

            $dateValue = trim($row[$dateIndex]);
            $valueValue = trim($row[$valueIndex]);

            // Skip empty rows
            if (empty($dateValue) && empty($valueValue)) {
                continue;
            }

            // Parse and validate date
            $parsedDate = $this->parseDate($dateValue);
            if (!$parsedDate) {
                continue; // Skip invalid dates
            }

            // Parse and validate value
            $parsedValue = $this->parseValue($valueValue);
            if ($parsedValue === null) {
                continue; // Skip invalid values
            }

            $data[] = [
                'date' => $parsedDate,
                'value' => $parsedValue
            ];
        }

        fclose($handle);
        return $data;
    }

    private function getCsvPreview(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        
        if ($handle === false) {
            throw new \Exception('Could not read CSV file');
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            throw new \Exception('Could not read CSV headers');
        }

        $previewRows = [];
        $rowCount = 0;
        
        // Read first 5 rows for preview
        while (($row = fgetcsv($handle)) !== false && $rowCount < 5) {
            $previewRows[] = $row;
            $rowCount++;
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'preview_rows' => $previewRows,
            'total_rows' => $rowCount + 1 // +1 for header
        ];
    }

    private function parseDate(string $dateString): ?string
    {
        $dateString = trim($dateString);
        
        // Try different date formats
        $formats = [
            'Y-m-d',           // 2025-01-15
            'm/d/Y',           // 01/15/2025
            'd/m/Y',           // 15/01/2025
            'Y-m-d H:i:s',     // 2025-01-15 10:30:00
            'm/d/Y H:i:s',     // 01/15/2025 10:30:00
            'd/m/Y H:i:s',     // 15/01/2025 10:30:00
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $dateString);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        // Try strtotime as fallback
        $timestamp = strtotime($dateString);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    private function parseValue(string $valueString): ?float
    {
        $valueString = trim($valueString);
        
        // Remove common currency symbols and formatting
        $valueString = str_replace(['$', '€', '£', ',', '%'], '', $valueString);
        
        // Handle percentage values
        if (strpos($valueString, '%') !== false) {
            $valueString = str_replace('%', '', $valueString);
        }
        
        // Convert to float
        $value = filter_var($valueString, FILTER_VALIDATE_FLOAT);
        
        return $value !== false ? $value : null;
    }
}
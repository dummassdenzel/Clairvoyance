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

            // Parse CSV file
            $csvData = $this->parseCsvFile($_FILES['file']['tmp_name']);
            
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
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'CSV processed successfully',
                    'data' => [
                        'inserted' => $inserted,
                        'failed' => $failed,
                        'errors' => $errors
                    ]
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'No entries were inserted. ' . implode('; ', $errors)
                ], 400);
            }

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
}
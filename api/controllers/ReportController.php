<?php

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/FileUtils.php';
require_once __DIR__ . '/../models/Dashboard.php';
require_once __DIR__ . '/../models/Kpi.php';
require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../services/ExportService.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Controller for Report operations
 */
class ReportController
{
    private $dashboardModel;
    private $kpiModel;
    private $reportModel;
    private $exportService;
    private $pdo;
    
    public function __construct()
    {
        $this->dashboardModel = new Dashboard();
        $this->kpiModel = new Kpi();
        $this->reportModel = new Report();
        $this->exportService = new ExportService();
        
        $conn = new Connection();
        $this->pdo = $conn->connect();
    }
    
    /**
     * Get all reports for a user
     * 
     * @param object $user Current user
     */
    public function getAll($user)
    {
        try {
            // Get reports for the user
            $reports = $this->reportModel->getAllByUser($user->id);
            
            Response::success('Reports retrieved successfully', $reports);
        } catch (PDOException $e) {
            Response::error('Failed to retrieve reports: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a single report
     * 
     * @param int $id Report ID
     * @param object $user Current user
     */
    public function getOne($id, $user)
    {
        try {
            // Get report by ID
            $report = $this->reportModel->getById($id);
            
            if (!$report) {
                Response::notFound('Report not found');
                return;
            }
            
            // Check if user has access to the report
            if ($report['user_id'] != $user->id && $user->role !== 'admin') {
                Response::error('You do not have permission to view this report', null, 403);
                return;
            }
            
            Response::success('Report retrieved successfully', $report);
        } catch (PDOException $e) {
            Response::error('Failed to retrieve report: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a new report
     * 
     * @param array $data Report data
     * @param object $user Current user
     */
    public function create($data, $user)
    {
        // Validate required fields
        $validation = Validator::validateRequired($data, ['dashboard_id', 'name', 'format']);
        if (!$validation['isValid']) {
            Response::error($validation['message']);
            return;
        }
        
        try {
            // Check if dashboard exists
            $dashboard = $this->dashboardModel->getById($data['dashboard_id']);
            if (!$dashboard) {
                Response::notFound('Dashboard not found');
                return;
            }
            
            // Check if user has access to the dashboard
            if ($dashboard['user_id'] != $user->id && $user->role !== 'admin') {
                Response::error('You do not have permission to create a report for this dashboard', null, 403);
                return;
            }
            
            // Validate format
            $format = strtolower($data['format']);
            if (!in_array($format, ['csv', 'xlsx', 'pdf', 'json'])) {
                Response::error('Unsupported report format. Please use CSV, Excel, PDF, or JSON.');
                return;
            }
            
            // Get time range if provided
            $timeRange = null;
            if (isset($data['time_range']) && isset($data['time_range']['start']) && isset($data['time_range']['end'])) {
                $timeRange = [
                    'start' => $data['time_range']['start'],
                    'end' => $data['time_range']['end']
                ];
            }
            
            // Generate the report file
            $exportFile = null;
            switch ($format) {
                case 'csv':
                    $exportFile = $this->exportService->exportDashboardToCsv($data['dashboard_id'], $timeRange);
                    break;
                case 'xlsx':
                    $exportFile = $this->exportService->exportDashboardToExcel($data['dashboard_id'], $timeRange);
                    break;
                case 'pdf':
                    $exportFile = $this->exportService->exportDashboardToPdf($data['dashboard_id'], $timeRange);
                    break;
                case 'json':
                    $exportFile = $this->exportService->exportDashboardToJson($data['dashboard_id'], $timeRange);
                    break;
            }
            
            if (!$exportFile || !file_exists($exportFile)) {
                Response::error('Failed to generate report file');
                return;
            }
            
            // Store the report in the database
            $reportData = [
                'user_id' => $user->id,
                'dashboard_id' => $data['dashboard_id'],
                'name' => $data['name'],
                'format' => $format,
                'file_path' => $exportFile
            ];
            
            $reportId = $this->reportModel->create($reportData);
            
            if (!$reportId) {
                Response::error('Failed to save report record');
                return;
            }
            
            Response::success('Report created successfully', [
                'id' => $reportId,
                'name' => $data['name'],
                'format' => $format,
                'dashboard_id' => $data['dashboard_id'],
                'download_url' => '/api/reports/' . $reportId . '/download'
            ]);
            
        } catch (Exception $e) {
            Response::error('Failed to create report: ' . $e->getMessage());
        }
    }
    
    /**
     * Download a report
     * 
     * @param int $id Report ID
     * @param object $user Current user
     */
    public function downloadReport($id, $user)
    {
        try {
            // Get report by ID
            $report = $this->reportModel->getById($id);
            
            if (!$report) {
                Response::notFound('Report not found');
                return;
            }
            
            // Check if user has access to the report
            if ($report['user_id'] != $user->id && $user->role !== 'admin') {
                Response::error('You do not have permission to download this report', null, 403);
                return;
            }
            
            // Check if file exists
            if (!file_exists($report['file_path'])) {
                Response::error('Report file not found');
                return;
            }
            
            // Set content type based on format
            $contentType = 'application/octet-stream';
            switch ($report['format']) {
                case 'csv':
                    $contentType = 'text/csv';
                    break;
                case 'xlsx':
                    $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                    break;
                case 'pdf':
                    $contentType = 'application/pdf';
                    break;
                case 'json':
                    $contentType = 'application/json';
                    break;
            }
            
            // Generate filename
            $filename = FileUtils::sanitizeFilename($report['name']) . '.' . $report['format'];
            
            // Set headers for file download
            header('Content-Type: ' . $contentType);
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($report['file_path']));
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Output file contents
            readfile($report['file_path']);
            exit;
            
        } catch (Exception $e) {
            Response::error('Failed to download report: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete a report
     * 
     * @param int $id Report ID
     * @param object $user Current user
     */
    public function delete($id, $user)
    {
        try {
            // Get report by ID
            $report = $this->reportModel->getById($id);
            
            if (!$report) {
                Response::notFound('Report not found');
                return;
            }
            
            // Check if user has access to the report
            if ($report['user_id'] != $user->id && $user->role !== 'admin') {
                Response::error('You do not have permission to delete this report', null, 403);
                return;
            }
            
            // Delete the report file
            if (file_exists($report['file_path'])) {
                @unlink($report['file_path']);
            }
            
            // Delete the report record
            if (!$this->reportModel->delete($id)) {
                Response::error('Failed to delete report');
                return;
            }
            
            Response::success('Report deleted successfully');
            
        } catch (Exception $e) {
            Response::error('Failed to delete report: ' . $e->getMessage());
        }
    }
}

// Using FileUtils::sanitizeFilename instead of local function